<?php

/*
 * 商品レベルの品切れ種別集約を Product に追加する Trait
 *
 * 全規格の品切れ種別を走査し、商品としての品切れ種別を返す。
 * - 在庫がある規格が1つでもあれば null（品切れではない）
 * - 全規格が品切れで、1つでも欠品（null/0/1）があれば「欠品」
 * - 全規格が廃盤の場合のみ「廃盤」
 */

namespace Customize\Entity;

use Eccube\Annotation\EntityExtension;

/**
 * @EntityExtension("Eccube\Entity\Product")
 */
trait ProductTrait
{
    /**
     * 商品レベルの品切れ種別を取得する
     *
     * 在庫がある場合は null を返す。
     * 品切れの場合:
     *   - 全規格が廃盤 → OUT_OF_STOCK_TYPE_DISCONTINUED (2)
     *   - それ以外 → OUT_OF_STOCK_TYPE_SHORTAGE (1)
     *
     * @return int|null
     */
    public function getOutOfStockType(): ?int
    {
        // 在庫がある場合は品切れ種別なし
        if ($this->getStockFind()) {
            return null;
        }

        $hasShortage = false;
        $hasDiscontinued = false;
        $hasVisibleClass = false;

        foreach ($this->getProductClasses() as $ProductClass) {
            // 非表示の規格はスキップ
            if (!$ProductClass->isVisible()) {
                continue;
            }

            $ClassCategory1 = $ProductClass->getClassCategory1();
            $ClassCategory2 = $ProductClass->getClassCategory2();
            if ($ClassCategory1 && !$ClassCategory1->isVisible()) {
                continue;
            }
            if ($ClassCategory2 && !$ClassCategory2->isVisible()) {
                continue;
            }

            $hasVisibleClass = true;
            $type = $ProductClass->getOutOfStockType();

            if ($type === 2) {
                $hasDiscontinued = true;
            } else {
                // null, 0, 1 はすべて欠品扱い
                $hasShortage = true;
            }
        }

        // 有効な規格がない場合は欠品扱い
        if (!$hasVisibleClass) {
            return 1;
        }

        // 1つでも欠品があれば商品全体は欠品
        if ($hasShortage) {
            return 1;
        }

        // 全規格が廃盤の場合のみ廃盤
        if ($hasDiscontinued) {
            return 2;
        }

        // フォールバック: 欠品扱い
        return 1;
    }
}
