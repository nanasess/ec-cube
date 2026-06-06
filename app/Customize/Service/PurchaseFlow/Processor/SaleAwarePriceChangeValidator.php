<?php

namespace Customize\Service\PurchaseFlow\Processor;

use Eccube\Entity\ItemInterface;
use Eccube\Entity\OrderItem;
use Eccube\Service\PurchaseFlow\Processor\PriceChangeValidator;
use Eccube\Service\PurchaseFlow\PurchaseContext;

/**
 * セール価格対応の価格変更検知バリデータ
 *
 * コアの PriceChangeValidator は OrderItem を price02(税抜・元価格)と比較するため、
 * セール価格(SalePricePreprocessor が設定)が適用された OrderItem では
 * 「販売価格が変更されました」と誤検知し、注文確認画面から先に進めなくなる。
 *
 * セール中の ProductClass を持つ OrderItem は、SalePricePreprocessor が
 * 検証後に毎回その時点のセール価格を設定するため、価格変更検知をスキップする。
 * セール非適用の場合・カート明細(CartItem)はコアの挙動に委譲する。
 */
class SaleAwarePriceChangeValidator extends PriceChangeValidator
{
    public function validate(ItemInterface $item, PurchaseContext $context)
    {
        if (!$item->isProduct()) {
            return;
        }

        if ($item instanceof OrderItem) {
            $productClass = $item->getProductClass();
            if ($productClass !== null
                && method_exists($productClass, 'isOnSale')
                && $productClass->isOnSale()
            ) {
                // セール適用中の OrderItem は価格変更検知をスキップする。
                // 実際のセール価格は SalePricePreprocessor が設定する。
                return;
            }
        }

        // セール非適用・CartItem はコアの検証に委譲
        parent::validate($item, $context);
    }
}
