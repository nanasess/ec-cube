<?php

namespace Customize\Repository;

use Customize\Entity\ReferralCoupon;
use Eccube\Entity\Customer;
use Eccube\Entity\Order;
use Doctrine\Persistence\ManagerRegistry;
use Eccube\Repository\AbstractRepository;

/**
 * 紹介クーポンリポジトリ.
 */
class ReferralCouponRepository extends AbstractRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ReferralCoupon::class);
    }

    /**
     * クーポンコードから有効なクーポンを取得する.
     */
    public function findUsableByCouponCode(string $couponCode): ?ReferralCoupon
    {
        $coupon = $this->findOneBy(['coupon_code' => $couponCode]);
        if ($coupon === null) {
            return null;
        }

        if (!$coupon->isUsable()) {
            return null;
        }

        return $coupon;
    }

    /**
     * 注文に紐づくクーポンを取得する.
     */
    public function findByOrder(Order $Order): ?ReferralCoupon
    {
        return $this->findOneBy(['RefereeOrder' => $Order]);
    }

    /**
     * 指定会員が発行したクーポン一覧を取得する.
     *
     * @return ReferralCoupon[]
     */
    public function findByReferrer(Customer $Customer): array
    {
        return $this->findBy(
            ['Referrer' => $Customer],
            ['create_date' => 'DESC']
        );
    }

    /**
     * 指定会員が発行した未使用クーポン数を取得する.
     */
    public function countUnusedByReferrer(Customer $Customer): int
    {
        return $this->count([
            'Referrer' => $Customer,
            'status' => ReferralCoupon::STATUS_UNUSED,
        ]);
    }

    /**
     * 指定会員が発行したクーポン総数を取得する.
     */
    public function countByReferrer(Customer $Customer): int
    {
        return $this->count(['Referrer' => $Customer]);
    }
}
