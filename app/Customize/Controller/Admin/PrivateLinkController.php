<?php

/*
 * 限定公開リンク 管理画面コントローラ
 *
 * 商品ごとの限定公開リンクの一覧表示・新規発行・無効化を行う。
 */

namespace Customize\Controller\Admin;

use Customize\Entity\PrivateLink;
use Customize\Form\Type\Admin\PrivateLinkType;
use Customize\Repository\PrivateLinkRepository;
use Eccube\Controller\AbstractController;
use Eccube\Entity\Product;
use Eccube\Repository\ProductRepository;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
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

    public function __construct(
        PrivateLinkRepository $privateLinkRepository,
        ProductRepository $productRepository
    ) {
        $this->privateLinkRepository = $privateLinkRepository;
        $this->productRepository = $productRepository;
    }

    /**
     * 限定公開リンク一覧・新規発行
     *
     * @Route("/%eccube_admin_route%/product/{id}/private_link", name="admin_product_private_link", requirements={"id" = "\d+"}, methods={"GET", "POST"})
     * @Template("@admin/Product/private_link.twig")
     */
    public function index(Request $request, int $id)
    {
        $Product = $this->productRepository->find($id);

        if ($Product === null) {
            throw new NotFoundHttpException();
        }

        $PrivateLink = new PrivateLink();
        $PrivateLink->setProduct($Product);

        $form = $this->createForm(PrivateLinkType::class, $PrivateLink);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // トークン生成
            $token = $this->privateLinkRepository->generateUniqueToken();
            $PrivateLink->setToken($token);

            $em = $this->entityManager;
            $em->persist($PrivateLink);
            $em->flush();

            $this->addSuccess('限定公開リンクを発行しました。', 'admin');

            return $this->redirectToRoute('admin_product_private_link', ['id' => $id]);
        }

        // 既存リンク一覧
        $PrivateLinks = $this->privateLinkRepository->findByProduct($Product);

        return [
            'Product' => $Product,
            'PrivateLinks' => $PrivateLinks,
            'form' => $form->createView(),
        ];
    }

    /**
     * 限定公開リンク無効化
     *
     * @Route("/%eccube_admin_route%/product/{id}/private_link/{link_id}/disable", name="admin_product_private_link_disable", requirements={"id" = "\d+", "link_id" = "\d+"}, methods={"PUT"})
     */
    public function disable(Request $request, int $id, int $link_id)
    {
        $this->isTokenValid();

        $PrivateLink = $this->privateLinkRepository->find($link_id);

        if ($PrivateLink === null || $PrivateLink->getProduct()->getId() !== $id) {
            throw new NotFoundHttpException();
        }

        $PrivateLink->setIsActive(false);

        $em = $this->entityManager;
        $em->flush();

        $this->addSuccess('限定公開リンクを無効化しました。', 'admin');

        return $this->redirectToRoute('admin_product_private_link', ['id' => $id]);
    }

    /**
     * 限定公開リンク有効化
     *
     * @Route("/%eccube_admin_route%/product/{id}/private_link/{link_id}/enable", name="admin_product_private_link_enable", requirements={"id" = "\d+", "link_id" = "\d+"}, methods={"PUT"})
     */
    public function enable(Request $request, int $id, int $link_id)
    {
        $this->isTokenValid();

        $PrivateLink = $this->privateLinkRepository->find($link_id);

        if ($PrivateLink === null || $PrivateLink->getProduct()->getId() !== $id) {
            throw new NotFoundHttpException();
        }

        $PrivateLink->setIsActive(true);

        $em = $this->entityManager;
        $em->flush();

        $this->addSuccess('限定公開リンクを有効化しました。', 'admin');

        return $this->redirectToRoute('admin_product_private_link', ['id' => $id]);
    }

    /**
     * 限定公開リンク削除
     *
     * @Route("/%eccube_admin_route%/product/{id}/private_link/{link_id}/delete", name="admin_product_private_link_delete", requirements={"id" = "\d+", "link_id" = "\d+"}, methods={"DELETE"})
     */
    public function delete(Request $request, int $id, int $link_id)
    {
        $this->isTokenValid();

        $PrivateLink = $this->privateLinkRepository->find($link_id);

        if ($PrivateLink === null || $PrivateLink->getProduct()->getId() !== $id) {
            throw new NotFoundHttpException();
        }

        $em = $this->entityManager;
        $em->remove($PrivateLink);
        $em->flush();

        $this->addSuccess('限定公開リンクを削除しました。', 'admin');

        return $this->redirectToRoute('admin_product_private_link', ['id' => $id]);
    }
}
