<?php

namespace Customize\Controller\Admin;

use Customize\Entity\SaleConfig;
use Customize\Form\Type\Admin\SaleConfigType;
use Customize\Repository\SaleConfigRepository;
use Eccube\Common\EccubeNav;
use Eccube\Controller\AbstractController;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;

/**
 * セール管理コントローラ
 *
 * セール設定の一覧表示・作成・編集・有効/無効トグル・削除を行う。
 */
class SaleConfigController extends AbstractController implements EccubeNav
{
    /**
     * @var SaleConfigRepository
     */
    private $saleConfigRepository;

    public function __construct(SaleConfigRepository $saleConfigRepository)
    {
        $this->saleConfigRepository = $saleConfigRepository;
    }

    /**
     * @return array
     */
    public static function getNav(): array
    {
        return [
            'product' => [
                'children' => [
                    'sale_config' => [
                        'name' => 'セール管理',
                        'url' => 'admin_sale_config_index',
                    ],
                ],
            ],
        ];
    }

    /**
     * セール一覧
     *
     * @Route("/%eccube_admin_route%/product/sale", name="admin_sale_config_index", methods={"GET"})
     * @Template("@admin/Product/sale_index.twig")
     */
    public function index(): array
    {
        $SaleConfigs = $this->saleConfigRepository->findBy([], ['id' => 'DESC']);

        return [
            'SaleConfigs' => $SaleConfigs,
        ];
    }

    /**
     * セール新規作成
     *
     * @Route("/%eccube_admin_route%/product/sale/new", name="admin_sale_config_new", methods={"GET", "POST"})
     * @Template("@admin/Product/sale_edit.twig")
     */
    public function create(Request $request)
    {
        $SaleConfig = new SaleConfig();
        $form = $this->createForm(SaleConfigType::class, $SaleConfig);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->persist($SaleConfig);
            $this->entityManager->flush();

            $this->addSuccess('admin.common.save_complete', 'admin');

            return $this->redirectToRoute('admin_sale_config_edit', ['id' => $SaleConfig->getId()]);
        }

        return [
            'form' => $form->createView(),
            'SaleConfig' => $SaleConfig,
        ];
    }

    /**
     * セール編集
     *
     * @Route("/%eccube_admin_route%/product/sale/{id}/edit", name="admin_sale_config_edit", requirements={"id" = "\d+"}, methods={"GET", "POST"})
     * @Template("@admin/Product/sale_edit.twig")
     */
    public function edit(Request $request, int $id)
    {
        $SaleConfig = $this->saleConfigRepository->find($id);
        if ($SaleConfig === null) {
            throw new NotFoundHttpException();
        }

        $form = $this->createForm(SaleConfigType::class, $SaleConfig);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->flush();

            $this->addSuccess('admin.common.save_complete', 'admin');

            return $this->redirectToRoute('admin_sale_config_edit', ['id' => $SaleConfig->getId()]);
        }

        return [
            'form' => $form->createView(),
            'SaleConfig' => $SaleConfig,
        ];
    }

    /**
     * セール有効/無効トグル
     *
     * @Route("/%eccube_admin_route%/product/sale/{id}/toggle", name="admin_sale_config_toggle", requirements={"id" = "\d+"}, methods={"POST"})
     */
    public function toggle(Request $request, int $id): Response
    {
        $this->isTokenValid();

        $SaleConfig = $this->saleConfigRepository->find($id);
        if ($SaleConfig === null) {
            throw new NotFoundHttpException();
        }

        $SaleConfig->setEnabled(!$SaleConfig->isEnabled());
        $this->entityManager->flush();

        if ($SaleConfig->isEnabled()) {
            $this->addSuccess('セールを有効にしました。', 'admin');
        } else {
            $this->addSuccess('セールを無効にしました。', 'admin');
        }

        return $this->redirectToRoute('admin_sale_config_index');
    }

    /**
     * セール削除
     *
     * @Route("/%eccube_admin_route%/product/sale/{id}/delete", name="admin_sale_config_delete", requirements={"id" = "\d+"}, methods={"DELETE"})
     */
    public function delete(Request $request, int $id): Response
    {
        $this->isTokenValid();

        $SaleConfig = $this->saleConfigRepository->find($id);
        if ($SaleConfig === null) {
            throw new NotFoundHttpException();
        }

        $this->entityManager->remove($SaleConfig);
        $this->entityManager->flush();

        $this->addSuccess('admin.common.delete_complete', 'admin');

        return $this->redirectToRoute('admin_sale_config_index');
    }
}
