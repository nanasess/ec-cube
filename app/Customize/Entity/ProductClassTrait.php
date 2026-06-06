<?php

namespace Customize\Entity;

use Eccube\Annotation\EntityExtension;

/**
 * ProductClass へのセール価格拡張
 *
 * 非永続フィールドとしてセール価格情報を保持する。
 * Doctrine postLoad イベントで自動セットされる。
 *
 * @EntityExtension("Eccube\Entity\ProductClass")
 */
trait ProductClassTrait
{
    /**
     * セール適用後の販売価格（税抜）
     * null の場合はセール非適用
     *
     * @var string|null
     */
    private $sale_price;

    /**
     * 適用されたセール設定
     *
     * @var SaleConfig|null
     */
    private $appliedSaleConfig;

    /**
     * セール適用後の販売価格（税込）
     *
     * @var string|null
     */
    private $sale_price_inc_tax;

    /**
     * セール上書き前の元の販売価格（税込）。表示の取り消し線用。
     * postLoad で price02IncTax を上書きする前に退避する。
     *
     * @var string|null
     */
    private $original_price02_inc_tax;

    /**
     * セール価格を取得
     */
    public function getSalePrice(): ?string
    {
        return $this->sale_price;
    }

    /**
     * セール価格をセット
     */
    public function setSalePrice(?string $sale_price): self
    {
        $this->sale_price = $sale_price;

        return $this;
    }

    /**
     * 適用されたセール設定を取得
     */
    public function getAppliedSaleConfig(): ?SaleConfig
    {
        return $this->appliedSaleConfig;
    }

    /**
     * 適用されたセール設定をセット
     */
    public function setAppliedSaleConfig(?SaleConfig $saleConfig): self
    {
        $this->appliedSaleConfig = $saleConfig;

        return $this;
    }

    /**
     * セール適用後の販売価格（税込）を取得
     */
    public function getSalePriceIncTax(): ?string
    {
        return $this->sale_price_inc_tax;
    }

    /**
     * セール適用後の販売価格（税込）をセット
     */
    public function setSalePriceIncTax(?string $sale_price_inc_tax): self
    {
        $this->sale_price_inc_tax = $sale_price_inc_tax;

        return $this;
    }

    /**
     * セール上書き前の元の販売価格（税込）を取得。
     * 退避されていない場合は現在の price02IncTax を返す。
     */
    public function getOriginalPrice02IncTax(): ?string
    {
        return $this->original_price02_inc_tax ?? $this->getPrice02IncTax();
    }

    /**
     * セール上書き前の元の販売価格（税込）をセット
     */
    public function setOriginalPrice02IncTax(?string $price): self
    {
        $this->original_price02_inc_tax = $price;

        return $this;
    }

    /**
     * セール中かどうかを判定
     */
    public function isOnSale(): bool
    {
        return $this->sale_price !== null;
    }

    /**
     * 実効販売価格（税抜）を取得
     *
     * セール中はセール価格、そうでなければ通常の price02 を返す
     */
    public function getEffectivePrice02(): ?string
    {
        if ($this->isOnSale()) {
            return $this->sale_price;
        }

        return $this->getPrice02();
    }
}
