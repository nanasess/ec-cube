<?php

namespace Customize\Repository;

use Customize\Entity\SaleConfig;
use Doctrine\Persistence\ManagerRegistry;
use Eccube\Entity\ProductClass;
use Eccube\Repository\AbstractRepository;

/**
 * セール設定リポジトリ
 */
class SaleConfigRepository extends AbstractRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SaleConfig::class);
    }

    /**
     * 現在有効なセール設定を全て取得する
     *
     * @return SaleConfig[]
     */
    public function findActiveSales(?\DateTimeInterface $now = null): array
    {
        $now = $now ?? new \DateTimeImmutable();

        $qb = $this->createQueryBuilder('sc')
            ->where('sc.enabled = :enabled')
            ->andWhere('(sc.start_date IS NULL OR sc.start_date <= :now)')
            ->andWhere('(sc.end_date IS NULL OR sc.end_date >= :now)')
            ->setParameter('enabled', true)
            ->setParameter('now', $now);

        return $qb->getQuery()->getResult();
    }

    /**
     * 指定した ProductClass に適用可能なアクティブセール設定を取得する
     *
     * 複数セールが該当する場合は、最も割引額が大きいものを返す
     */
    public function findActiveSaleForProductClass(ProductClass $productClass, ?\DateTimeInterface $now = null): ?SaleConfig
    {
        $product = $productClass->getProduct();
        if ($product === null) {
            return null;
        }

        $categoryIds = [];
        foreach ($product->getProductCategories() as $pc) {
            $categoryIds[] = $pc->getCategory()->getId();
        }

        if (empty($categoryIds)) {
            return null;
        }

        $activeSales = $this->findActiveSales($now);

        $bestSale = null;
        $bestPrice = null;
        $originalPrice = $productClass->getPrice02();

        if ($originalPrice === null) {
            return null;
        }

        foreach ($activeSales as $sale) {
            // カテゴリが一致するかチェック
            $matched = false;
            foreach ($sale->getCategories() as $category) {
                if (in_array($category->getId(), $categoryIds)) {
                    $matched = true;
                    break;
                }
            }

            if (!$matched) {
                continue;
            }

            $salePrice = $sale->calcSalePrice($originalPrice);

            // 最も割引額が大きい（＝セール価格が最も低い）ものを選ぶ
            if ($bestPrice === null || bccomp($salePrice, $bestPrice, 0) < 0) {
                $bestPrice = $salePrice;
                $bestSale = $sale;
            }
        }

        return $bestSale;
    }
}
