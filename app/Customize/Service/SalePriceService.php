<?php

namespace Customize\Service;

use Customize\Entity\SaleConfig;
use Customize\Repository\SaleConfigRepository;
use Eccube\Entity\ProductClass;

/**
 * セール価格計算サービス
 *
 * リクエストスコープのキャッシュで N+1 問題を回避する。
 */
class SalePriceService
{
    /**
     * @var SaleConfigRepository
     */
    private $saleConfigRepository;

    /**
     * リクエストスコープキャッシュ: ProductClass ID => [salePrice, SaleConfig]
     *
     * @var array<int, array{0: string|null, 1: SaleConfig|null}>
     */
    private $cache = [];

    /**
     * アクティブセールのキャッシュ
     *
     * @var SaleConfig[]|null
     */
    private $activeSalesCache;

    public function __construct(SaleConfigRepository $saleConfigRepository)
    {
        $this->saleConfigRepository = $saleConfigRepository;
    }

    /**
     * ProductClass の現在のセール価格（税抜）を取得する
     *
     * @return string|null セール価格。セール非適用の場合は null
     */
    public function getSalePrice(ProductClass $productClass): ?string
    {
        return $this->getSalePriceAt($productClass);
    }

    /**
     * 指定日時点のセール価格（税抜）を取得する
     *
     * @param ProductClass $productClass 対象の商品規格
     * @param \DateTimeInterface|null $date 判定基準日時（null の場合は現在日時）
     *
     * @return string|null セール価格。セール非適用の場合は null
     */
    public function getSalePriceAt(ProductClass $productClass, ?\DateTimeInterface $date = null): ?string
    {
        $result = $this->resolve($productClass, $date);

        return $result[0];
    }

    /**
     * ProductClass に適用されるセール設定を取得する
     */
    public function getAppliedSaleConfig(ProductClass $productClass, ?\DateTimeInterface $date = null): ?SaleConfig
    {
        $result = $this->resolve($productClass, $date);

        return $result[1];
    }

    /**
     * セール価格と設定を解決する（キャッシュ付き）
     *
     * @return array{0: string|null, 1: SaleConfig|null}
     */
    private function resolve(ProductClass $productClass, ?\DateTimeInterface $date = null): array
    {
        // 日時指定ありの場合はキャッシュを使わない（受注編集時の特定時点計算用）
        if ($date !== null) {
            return $this->doResolve($productClass, $date);
        }

        $pcId = $productClass->getId();
        if ($pcId !== null && isset($this->cache[$pcId])) {
            return $this->cache[$pcId];
        }

        $result = $this->doResolve($productClass, null);

        if ($pcId !== null) {
            $this->cache[$pcId] = $result;
        }

        return $result;
    }

    /**
     * 実際のセール価格解決ロジック
     *
     * @return array{0: string|null, 1: SaleConfig|null}
     */
    private function doResolve(ProductClass $productClass, ?\DateTimeInterface $date): array
    {
        $saleConfig = $this->saleConfigRepository->findActiveSaleForProductClass($productClass, $date);

        if ($saleConfig === null) {
            return [null, null];
        }

        $originalPrice = $productClass->getPrice02();
        if ($originalPrice === null) {
            return [null, null];
        }

        $salePrice = $saleConfig->calcSalePrice($originalPrice);

        return [$salePrice, $saleConfig];
    }

    /**
     * キャッシュをクリアする（テスト用）
     */
    public function clearCache(): void
    {
        $this->cache = [];
        $this->activeSalesCache = null;
    }
}
