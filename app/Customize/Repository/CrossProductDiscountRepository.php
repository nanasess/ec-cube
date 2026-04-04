<?php

namespace Customize\Repository;

use Customize\Entity\CrossProductDiscount;
use Doctrine\Persistence\ManagerRegistry;
use Eccube\Repository\AbstractRepository;

/**
 * セット割引ルールのリポジトリ
 */
class CrossProductDiscountRepository extends AbstractRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CrossProductDiscount::class);
    }

    /**
     * 有効なルールを全件取得する
     *
     * @return CrossProductDiscount[]
     */
    public function findEnabledRules(): array
    {
        return $this->createQueryBuilder('cpd')
            ->where('cpd.enabled = :enabled')
            ->setParameter('enabled', true)
            ->orderBy('cpd.id', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * 指定された商品IDリストに関連する有効なルールを取得する
     *
     * カート内の商品IDをもとに、trigger_product_id または target_product_id が
     * それらに含まれるルールのみを返す。
     *
     * @param int[] $productIds カート内の商品IDリスト
     *
     * @return CrossProductDiscount[]
     */
    public function findEnabledRulesByProductIds(array $productIds): array
    {
        if (empty($productIds)) {
            return [];
        }

        return $this->createQueryBuilder('cpd')
            ->innerJoin('cpd.triggerProduct', 'tp')
            ->innerJoin('cpd.targetProduct', 'tgt')
            ->where('cpd.enabled = :enabled')
            ->andWhere('tp.id IN (:productIds)')
            ->andWhere('tgt.id IN (:productIds)')
            ->setParameter('enabled', true)
            ->setParameter('productIds', $productIds)
            ->orderBy('cpd.id', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * 全件取得（管理画面用）
     *
     * @return CrossProductDiscount[]
     */
    public function findAll(): array
    {
        return $this->createQueryBuilder('cpd')
            ->orderBy('cpd.id', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
