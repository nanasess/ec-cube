<?php

/*
 * 品切れ種別（欠品/廃盤）を ProductClass に追加する Trait
 *
 * null/0: 未設定（従来互換: 欠品扱い）
 * 1: 欠品（一時的な在庫切れ）
 * 2: 廃盤（恒久的に販売終了）
 */

namespace Customize\Entity;

use Doctrine\ORM\Mapping as ORM;
use Eccube\Annotation\EntityExtension;

/**
 * @EntityExtension("Eccube\Entity\ProductClass")
 */
trait ProductClassTrait
{
    /** 欠品（一時的な在庫切れ） */
    public const OUT_OF_STOCK_TYPE_SHORTAGE = 1;

    /** 廃盤（恒久的に販売終了） */
    public const OUT_OF_STOCK_TYPE_DISCONTINUED = 2;

    /**
     * 品切れ種別
     *
     * @var int|null
     *
     * @ORM\Column(name="out_of_stock_type", type="smallint", nullable=true)
     */
    private $out_of_stock_type;

    /**
     * 品切れ種別を取得する
     *
     * @return int|null
     */
    public function getOutOfStockType(): ?int
    {
        return $this->out_of_stock_type;
    }

    /**
     * 品切れ種別を設定する
     *
     * @param int|null $outOfStockType
     *
     * @return $this
     */
    public function setOutOfStockType(?int $outOfStockType): self
    {
        $this->out_of_stock_type = $outOfStockType;

        return $this;
    }

    /**
     * 廃盤かどうかを判定する
     *
     * @return bool
     */
    public function isDiscontinued(): bool
    {
        return $this->out_of_stock_type === self::OUT_OF_STOCK_TYPE_DISCONTINUED;
    }
}
