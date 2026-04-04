<?php

namespace Customize\Service\PurchaseFlow\Processor;

use Customize\Service\SalePriceService;
use Eccube\Entity\Cart;
use Eccube\Entity\CartItem;
use Eccube\Entity\ItemHolderInterface;
use Eccube\Entity\Order;
use Eccube\Entity\OrderItem;
use Eccube\Service\PurchaseFlow\ItemHolderPreprocessor;
use Eccube\Service\PurchaseFlow\PurchaseContext;

/**
 * セール価格をPurchaseFlowで適用するPreprocessor
 *
 * TaxProcessorより前（priority: 1100）で実行し、商品明細のpriceにセール価格をセットする。
 *
 * - フロント購入フロー時: 現在のセール価格を適用
 * - カートフロー時: カートの価格をセール価格（税込）で更新
 * - 管理画面受注編集時: OrderItemに記録済みの価格を尊重し、上書きしない
 */
class SalePricePreprocessor implements ItemHolderPreprocessor
{
    /**
     * @var SalePriceService
     */
    private $salePriceService;

    public function __construct(SalePriceService $salePriceService)
    {
        $this->salePriceService = $salePriceService;
    }

    public function process(ItemHolderInterface $itemHolder, PurchaseContext $context): void
    {
        // 管理画面受注編集時はスキップ（管理者の裁量を尊重）
        if ($context->isOrderFlow()) {
            return;
        }

        // カートフロー: CartItem の price（税込）をセール価格で更新
        if ($itemHolder instanceof Cart) {
            $this->processCart($itemHolder);

            return;
        }

        // ショッピングフロー: OrderItem の price（税抜）をセール価格で更新
        if ($itemHolder instanceof Order) {
            $this->processOrder($itemHolder);
        }
    }

    /**
     * カートフローでのセール価格適用
     *
     * CartItem の price は税込金額のため、セール価格の税込金額をセットする。
     * postLoad で ProductClass.price02IncTax もセール価格税込に上書きしているため、
     * PriceChangeValidator との整合性も保たれる。
     */
    private function processCart(Cart $cart): void
    {
        foreach ($cart->getCartItems() as $cartItem) {
            if (!$cartItem instanceof CartItem) {
                continue;
            }

            $productClass = $cartItem->getProductClass();
            if ($productClass === null) {
                continue;
            }

            // postLoad で price02IncTax がセール価格税込に上書きされているので、
            // CartItem の price もそれに合わせて更新する
            if ($productClass->isOnSale()) {
                $cartItem->setPrice($productClass->getPrice02IncTax());
            }
        }
    }

    /**
     * ショッピングフローでのセール価格適用
     *
     * OrderItem に対してセール価格（税抜）をセットし、セール情報を記録する。
     */
    private function processOrder(Order $order): void
    {
        foreach ($order->getOrderItems() as $orderItem) {
            if (!$orderItem instanceof OrderItem) {
                continue;
            }

            if (!$orderItem->isProduct()) {
                continue;
            }

            $productClass = $orderItem->getProductClass();
            if ($productClass === null) {
                continue;
            }

            $salePrice = $this->salePriceService->getSalePrice($productClass);
            $saleConfig = $this->salePriceService->getAppliedSaleConfig($productClass);

            if ($salePrice !== null && $saleConfig !== null) {
                // セール情報を OrderItem に記録
                $orderItem->setSaleConfigId($saleConfig->getId());
                $orderItem->setOriginalPrice($productClass->getPrice02());

                // セール価格を適用
                $orderItem->setPrice($salePrice);
            }
        }
    }
}
