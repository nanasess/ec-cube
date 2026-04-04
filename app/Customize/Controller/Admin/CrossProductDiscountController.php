<?php

namespace Customize\Controller\Admin;

use Customize\Entity\CrossProductDiscount;
use Customize\Form\Type\Admin\CrossProductDiscountType;
use Customize\Repository\CrossProductDiscountRepository;
use Eccube\Common\EccubeNav;
use Eccube\Controller\AbstractController;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * セット割引管理コントローラ
 *
 * 商品Aがカートにある場合に商品Bに割引を適用するルールのCRUD管理を行う。
 */
class CrossProductDiscountController extends AbstractController implements EccubeNav
{
    /**
     * @var CrossProductDiscountRepository
     */
    private $crossProductDiscountRepository;

    public function __construct(CrossProductDiscountRepository $crossProductDiscountRepository)
    {
        $this->crossProductDiscountRepository = $crossProductDiscountRepository;
    }

    /**
     * 管理画面ナビ定義（商品管理 > セット割引設定）
     */
    public static function getNav(): array
    {
        return [
            'product' => [
                'children' => [
                    'cross_product_discount' => [
                        'name' => 'セット割引設定',
                        'url' => 'admin_cross_product_discount',
                    ],
                ],
            ],
        ];
    }

    /**
     * セット割引ルール一覧・新規作成
     *
     * @Route("/%eccube_admin_route%/product/cross_product_discount", name="admin_cross_product_discount", methods={"GET", "POST"})
     * @Template("@admin/Product/cross_product_discount.twig")
     *
     * @return array|RedirectResponse
     */
    public function index(Request $request)
    {
        $CrossProductDiscount = new CrossProductDiscount();
        $CrossProductDiscount->setEnabled(true);

        $form = $this->createForm(CrossProductDiscountType::class, $CrossProductDiscount);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->persist($CrossProductDiscount);
            $this->entityManager->flush();

            $this->addSuccess('admin.common.save_complete', 'admin');

            return $this->redirectToRoute('admin_cross_product_discount');
        }

        $rules = $this->crossProductDiscountRepository->findAll();

        return [
            'form' => $form->createView(),
            'rules' => $rules,
        ];
    }

    /**
     * セット割引ルール編集
     *
     * @Route("/%eccube_admin_route%/product/cross_product_discount/{id}/edit", requirements={"id" = "\d+"}, name="admin_cross_product_discount_edit", methods={"GET", "POST"})
     * @Template("@admin/Product/cross_product_discount_edit.twig")
     *
     * @return array|RedirectResponse
     */
    public function edit(Request $request, CrossProductDiscount $CrossProductDiscount)
    {
        $form = $this->createForm(CrossProductDiscountType::class, $CrossProductDiscount);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->flush();

            $this->addSuccess('admin.common.save_complete', 'admin');

            return $this->redirectToRoute('admin_cross_product_discount');
        }

        return [
            'form' => $form->createView(),
            'rule' => $CrossProductDiscount,
        ];
    }

    /**
     * セット割引ルール削除
     *
     * @Route("/%eccube_admin_route%/product/cross_product_discount/{id}/delete", requirements={"id" = "\d+"}, name="admin_cross_product_discount_delete", methods={"DELETE"})
     */
    public function delete(Request $request, CrossProductDiscount $CrossProductDiscount)
    {
        $this->isTokenValid();

        try {
            $this->entityManager->remove($CrossProductDiscount);
            $this->entityManager->flush();

            $this->addSuccess('admin.common.delete_complete', 'admin');
        } catch (\Exception $e) {
            $this->addError('admin.common.delete_error', 'admin');
            log_error('セット割引ルール削除エラー', [$CrossProductDiscount->getId(), $e]);
        }

        return $this->redirectToRoute('admin_cross_product_discount');
    }
}
