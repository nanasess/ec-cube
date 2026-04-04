<?php

/*
 * 管理画面の商品検索クエリに在庫数量の範囲条件を追加する QueryCustomizer
 *
 * - メインクエリ（pc エイリアスがJOIN済み）: 直接 pc.stock で条件指定
 * - カウントクエリ（pc エイリアスが未JOIN）: EXISTS サブクエリで対応
 * - 在庫無制限商品（stock_unlimited = true）は数量検索から除外する
 */

namespace Customize\Doctrine\Query;

use Doctrine\ORM\QueryBuilder;
use Eccube\Doctrine\Query\QueryCustomizer;
use Eccube\Repository\QueryKey;

class StockQuantityCustomizer implements QueryCustomizer
{
    /**
     * {@inheritdoc}
     */
    public function customize(QueryBuilder $builder, $params, $queryKey): void
    {
        // フォームデータから在庫数量の範囲を取得
        $stockMin = $params['stock_quantity_min'] ?? null;
        $stockMax = $params['stock_quantity_max'] ?? null;

        // 両方とも未指定の場合は何もしない
        if ($stockMin === null && $stockMax === null) {
            return;
        }

        // pc エイリアスがJOIN済みかどうかを判定
        $hasJoinedPc = $this->hasAlias($builder, 'pc');

        if ($hasJoinedPc) {
            // メインクエリ: pc エイリアスが利用可能
            $this->applyDirectCondition($builder, $stockMin, $stockMax);
        } else {
            // カウントクエリ: EXISTS サブクエリで対応
            $this->applySubqueryCondition($builder, $stockMin, $stockMax);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getQueryKey(): string
    {
        return QueryKey::PRODUCT_SEARCH_ADMIN;
    }

    /**
     * pc エイリアスが JOIN 済みのクエリに直接条件を追加する
     */
    private function applyDirectCondition(QueryBuilder $builder, ?int $stockMin, ?int $stockMax): void
    {
        // 在庫無制限商品を除外
        $builder->andWhere('pc.stock_unlimited = false');

        if ($stockMin !== null) {
            $builder
                ->andWhere('pc.stock >= :stock_quantity_min')
                ->setParameter('stock_quantity_min', $stockMin);
        }

        if ($stockMax !== null) {
            $builder
                ->andWhere('pc.stock <= :stock_quantity_max')
                ->setParameter('stock_quantity_max', $stockMax);
        }
    }

    /**
     * pc エイリアスが未JOIN のカウントクエリに EXISTS サブクエリで条件を追加する
     */
    private function applySubqueryCondition(QueryBuilder $builder, ?int $stockMin, ?int $stockMax): void
    {
        $subConditions = [
            'pcs.Product = p',
            'pcs.visible = true',
            'pcs.stock_unlimited = false',
        ];

        if ($stockMin !== null) {
            $subConditions[] = 'pcs.stock >= :stock_quantity_min';
            $builder->setParameter('stock_quantity_min', $stockMin);
        }

        if ($stockMax !== null) {
            $subConditions[] = 'pcs.stock <= :stock_quantity_max';
            $builder->setParameter('stock_quantity_max', $stockMax);
        }

        $subDql = sprintf(
            'SELECT pcs.id FROM \Eccube\Entity\ProductClass pcs WHERE %s',
            implode(' AND ', $subConditions)
        );

        $builder->andWhere(sprintf('EXISTS (%s)', $subDql));
    }

    /**
     * QueryBuilder に指定のエイリアスが JOIN されているかどうかを判定する
     */
    private function hasAlias(QueryBuilder $builder, string $alias): bool
    {
        $joins = $builder->getDQLPart('join');
        foreach ($joins as $rootAlias => $joinList) {
            foreach ($joinList as $join) {
                if ($join->getAlias() === $alias) {
                    return true;
                }
            }
        }

        return false;
    }
}
