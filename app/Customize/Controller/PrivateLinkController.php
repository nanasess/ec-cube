<?php

/*
 * 限定公開リンク フロントコントローラ
 *
 * トークン付きURLで非公開商品の閲覧・カート追加を可能にする。
 */

namespace Customize\Controller;

use Customize\Repository\PrivateLinkRepository;
use Eccube\Controller\AbstractController;
use Eccube\Entity\Customer;
use Eccube\Event\EccubeEvents;
use Eccube\Event\EventArgs;
use Eccube\Form\Type\AddCartType;
use Eccube\Repository\CustomerFavoriteProductRepository;
use Eccube\Repository\ProductRepository;
use Eccube\Service\CartService;
use Eccube\Service\PurchaseFlow\PurchaseContext;
use Eccube\Service\PurchaseFlow\PurchaseFlow;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;

class PrivateLinkController extends AbstractController
{
    /**
     * @var PrivateLinkRepository
     */
    private $privateLinkRepository;

    /**
     * @var ProductRepository
     */
    private $productRepository;

    /**
     * @var CustomerFavoriteProductRepository
     */
    private $customerFavoriteProductRepository;

    /**
     * @var CartService
     */
    private $cartService;

    /**
     * @var PurchaseFlow
     */
    private $purchaseFlow;

    public function __construct(
        PrivateLinkRepository $privateLinkRepository,
        ProductRepository $productRepository,
        CustomerFavoriteProductRepository $customerFavoriteProductRepository,
        CartService $cartService,
        PurchaseFlow $cartPurchaseFlow
    ) {
        $this->privateLinkRepository = $privateLinkRepository;
        $this->productRepository = $productRepository;
        $this->customerFavoriteProductRepository = $customerFavoriteProductRepository;
        $this->cartService = $cartService;
        $this->purchaseFlow = $cartPurchaseFlow;
    }

    /**
     * 限定公開リンクによる商品詳細表示
     *
     * トークンを検証し、有効な場合は非公開商品でも表示する。
     * customer_id が設定されている場合はログイン顧客との一致を確認する。
     *
     * @Route("/products/private/{token}", name="product_private_link", methods={"GET"}, requirements={"token" = "[0-9a-f]{64}"})
     * @Template("Product/detail.twig")
     */
    public function detail(Request $request, string $token): array
    {
        $PrivateLink = $this->privateLinkRepository->findValidByToken($token);

        if ($PrivateLink === null) {
            throw new NotFoundHttpException();
        }

        // 顧客制限チェック
        $this->checkCustomerAccess($PrivateLink);

        $Product = $this->productRepository->findWithSortedClassCategories($PrivateLink->getProduct()->getId());

        if ($Product === null) {
            throw new NotFoundHttpException();
        }

        // カート追加フォーム（action URLを限定公開用に差し替え）
        $builder = $this->formFactory->createNamedBuilder(
            '',
            AddCartType::class,
            null,
            [
                'product' => $Product,
                'id_add_product_id' => false,
            ]
        );

        $event = new EventArgs(
            [
                'builder' => $builder,
                'Product' => $Product,
            ],
            $request
        );
        $this->eventDispatcher->dispatch($event, EccubeEvents::FRONT_PRODUCT_DETAIL_INITIALIZE);

        $is_favorite = false;
        if ($this->isGranted('ROLE_USER')) {
            $Customer = $this->getUser();
            $is_favorite = $this->customerFavoriteProductRepository->isFavorite($Customer, $Product);
        }

        return [
            'title' => '',
            'subtitle' => $Product->getName(),
            'form' => $builder->getForm()->createView(),
            'Product' => $Product,
            'is_favorite' => $is_favorite,
            'private_link_token' => $token,
        ];
    }

    /**
     * 限定公開リンク経由のカート追加
     *
     * @Route("/products/private/{token}/add_cart", name="product_private_link_add_cart", methods={"POST"}, requirements={"token" = "[0-9a-f]{64}"})
     */
    public function addCart(Request $request, string $token)
    {
        $PrivateLink = $this->privateLinkRepository->findValidByToken($token);

        if ($PrivateLink === null) {
            throw new NotFoundHttpException();
        }

        // 顧客制限チェック
        $this->checkCustomerAccess($PrivateLink);

        $Product = $PrivateLink->getProduct();

        // エラーメッセージの配列
        $errorMessages = [];

        $builder = $this->formFactory->createNamedBuilder(
            '',
            AddCartType::class,
            null,
            [
                'product' => $Product,
                'id_add_product_id' => false,
            ]
        );

        $event = new EventArgs(
            [
                'builder' => $builder,
                'Product' => $Product,
            ],
            $request
        );
        $this->eventDispatcher->dispatch($event, EccubeEvents::FRONT_PRODUCT_CART_ADD_INITIALIZE);

        /** @var \Symfony\Component\Form\FormInterface $form */
        $form = $builder->getForm();
        $form->handleRequest($request);

        if (!$form->isValid()) {
            throw new NotFoundHttpException();
        }

        $addCartData = $form->getData();

        log_info(
            '限定公開リンク経由カート追加処理開始',
            [
                'product_id' => $Product->getId(),
                'product_class_id' => $addCartData['product_class_id'],
                'quantity' => $addCartData['quantity'],
            ]
        );

        // カートへ追加
        $this->cartService->addProduct($addCartData['product_class_id'], $addCartData['quantity']);

        // 明細の正規化
        $Carts = $this->cartService->getCarts();
        foreach ($Carts as $Cart) {
            $result = $this->purchaseFlow->validate($Cart, new PurchaseContext($Cart, $this->getUser()));
            // 復旧不可のエラーが発生した場合は追加した明細を削除
            if ($result->hasError()) {
                $this->cartService->removeProduct($addCartData['product_class_id']);
                foreach ($result->getErrors() as $error) {
                    $errorMessages[] = $error->getMessage();
                }
            }
            foreach ($result->getWarning() as $warning) {
                $errorMessages[] = $warning->getMessage();
            }
        }

        $this->cartService->save();

        log_info(
            '限定公開リンク経由カート追加処理完了',
            [
                'product_id' => $Product->getId(),
                'product_class_id' => $addCartData['product_class_id'],
                'quantity' => $addCartData['quantity'],
            ]
        );

        $event = new EventArgs(
            [
                'form' => $form,
                'Product' => $Product,
            ],
            $request
        );
        $this->eventDispatcher->dispatch($event, EccubeEvents::FRONT_PRODUCT_CART_ADD_COMPLETE);

        if ($event->getResponse() !== null) {
            return $event->getResponse();
        }

        if ($request->isXmlHttpRequest()) {
            // ajaxリクエストの場合はJSON形式で返す
            $messages = [];

            if (empty($errorMessages)) {
                $done = true;
                array_push($messages, trans('front.product.add_cart_complete'));
            } else {
                $done = false;
                $messages = $errorMessages;
            }

            return $this->json(['done' => $done, 'messages' => $messages]);
        } else {
            // ajax以外の場合はカート画面へリダイレクト
            foreach ($errorMessages as $errorMessage) {
                $this->addRequestError($errorMessage);
            }

            return $this->redirectToRoute('cart');
        }
    }

    /**
     * 顧客制限チェック
     *
     * customer_id が設定されている場合、ログイン中の顧客と一致するか確認する。
     *
     * @param \Customize\Entity\PrivateLink $PrivateLink
     *
     * @throws AccessDeniedHttpException 顧客が一致しない場合
     * @throws NotFoundHttpException 顧客制限ありでログインしていない場合
     */
    private function checkCustomerAccess(\Customize\Entity\PrivateLink $PrivateLink): void
    {
        $Customer = $PrivateLink->getCustomer();

        // 顧客制限なしの場合はチェック不要
        if ($Customer === null) {
            return;
        }

        // ログインしていない場合は404
        if (!$this->isGranted('ROLE_USER')) {
            throw new NotFoundHttpException();
        }

        /** @var Customer $LoggedInCustomer */
        $LoggedInCustomer = $this->getUser();

        // 顧客IDが一致しない場合はアクセス拒否
        if ($LoggedInCustomer->getId() !== $Customer->getId()) {
            throw new AccessDeniedHttpException();
        }
    }
}
