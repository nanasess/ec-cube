<?php

namespace Customize\EventSubscriber;

use Customize\Service\SalePriceService;
use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Events;
use Eccube\Entity\ProductClass;
use Eccube\Service\TaxRuleService;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Doctrine postLoad で ProductClass にセール価格を自動セットする
 *
 * 管理画面の受注編集コンテキストではセール価格のセットを行わない。
 * これにより PriceChangeValidator が誤検知するのを防ぐ。
 */
class SalePriceEventSubscriber implements EventSubscriber
{
    /**
     * @var SalePriceService
     */
    private $salePriceService;

    /**
     * @var TaxRuleService
     */
    private $taxRuleService;

    /**
     * @var RequestStack
     */
    private $requestStack;

    public function __construct(
        SalePriceService $salePriceService,
        TaxRuleService $taxRuleService,
        RequestStack $requestStack
    ) {
        $this->salePriceService = $salePriceService;
        $this->taxRuleService = $taxRuleService;
        $this->requestStack = $requestStack;
    }

    public function getSubscribedEvents(): array
    {
        return [
            Events::postLoad,
        ];
    }

    public function postLoad(LifecycleEventArgs $args): void
    {
        $entity = $args->getObject();

        if (!$entity instanceof ProductClass) {
            return;
        }

        // 管理画面の受注編集コンテキストではセール価格をセットしない
        if ($this->isAdminOrderEditContext()) {
            return;
        }

        $salePrice = $this->salePriceService->getSalePrice($entity);
        $saleConfig = $this->salePriceService->getAppliedSaleConfig($entity);

        if ($salePrice !== null && $saleConfig !== null) {
            $entity->setSalePrice($salePrice);
            $entity->setAppliedSaleConfig($saleConfig);

            // セール価格の税込金額を計算
            $salePriceIncTax = $this->taxRuleService->getPriceIncTax(
                $salePrice,
                $entity->getProduct(),
                $entity
            );
            $entity->setSalePriceIncTax($salePriceIncTax);

            // price02IncTax をセール価格の税込に上書きすることで、
            // カートフローの PriceChangeValidator との整合性を保つ。
            // price02（税抜、永続フィールド）は変更しない。
            $entity->setPrice02IncTax($salePriceIncTax);
        }
    }

    /**
     * 管理画面の受注編集コンテキストかどうかを判定する
     *
     * 受注編集のほか、商品管理画面でも price02IncTax の上書きが
     * 混乱を招くためスキップ対象とする。
     */
    private function isAdminOrderEditContext(): bool
    {
        $request = $this->requestStack->getCurrentRequest();
        if ($request === null) {
            return false;
        }

        $route = $request->attributes->get('_route', '');

        // 管理画面の受注編集関連ルートをチェック
        if (in_array($route, [
            'admin_order_edit',
            'admin_order_new',
            'admin_shipping_update_order',
        ], true)) {
            return true;
        }

        // 管理画面の商品編集関連ルートもスキップ
        // （price02IncTax 上書きによる編集画面の混乱を防ぐ）
        if (str_starts_with($route, 'admin_product_product_')) {
            return true;
        }

        return false;
    }
}
