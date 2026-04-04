<?php

namespace Customize\Service\PurchaseFlow\Processor;

use Customize\Repository\ReferralCouponRepository;
use Customize\Service\ReferralCouponMailService;
use Doctrine\ORM\EntityManagerInterface;
use Eccube\Entity\ItemHolderInterface;
use Eccube\Entity\Order;
use Eccube\Service\PurchaseFlow\PurchaseContext;
use Eccube\Service\PurchaseFlow\PurchaseProcessor;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * 紹介クーポン購入プロセッサ.
 *
 * prepare(): クーポンを「使用済」にし被紹介者・注文情報を記録する.
 * commit():  紹介者にポイントを付与する.
 * rollback(): prepare の変更を元に戻す.
 */
class ReferralCouponPurchaseProcessor implements PurchaseProcessor
{
    private EntityManagerInterface $entityManager;
    private ReferralCouponRepository $couponRepository;
    private RequestStack $requestStack;
    private ReferralCouponMailService $mailService;

    public function __construct(
        EntityManagerInterface $entityManager,
        ReferralCouponRepository $couponRepository,
        RequestStack $requestStack,
        ReferralCouponMailService $mailService
    ) {
        $this->entityManager = $entityManager;
        $this->couponRepository = $couponRepository;
        $this->requestStack = $requestStack;
        $this->mailService = $mailService;
    }

    /**
     * {@inheritdoc}
     *
     * クーポンステータスを「使用済」に変更し、被紹介者・注文情報を記録する.
     */
    public function prepare(ItemHolderInterface $target, PurchaseContext $context): void
    {
        if (!$target instanceof Order) {
            return;
        }

        $session = $this->requestStack->getSession();
        $couponCode = $session->get(ReferralCouponDiscountProcessor::SESSION_KEY);

        if (empty($couponCode)) {
            return;
        }

        $Coupon = $this->couponRepository->findUsableByCouponCode($couponCode);
        if ($Coupon === null) {
            return;
        }

        $Customer = $target->getCustomer();
        if ($Customer === null) {
            return;
        }

        // 自分自身のクーポンは使用不可（二重チェック）
        if ($Coupon->getReferrer()->getId() === $Customer->getId()) {
            return;
        }

        // クーポンを使用済みに更新
        $Coupon->setStatus(\Customize\Entity\ReferralCoupon::STATUS_USED);
        $Coupon->setReferee($Customer);
        $Coupon->setRefereeOrder($target);
        $Coupon->setUsedAt(new \DateTime());
    }

    /**
     * {@inheritdoc}
     *
     * 紹介者にポイントを付与し、付与済みフラグを立てる.
     */
    public function commit(ItemHolderInterface $target, PurchaseContext $context): void
    {
        if (!$target instanceof Order) {
            return;
        }

        $Coupon = $this->couponRepository->findByOrder($target);
        if ($Coupon === null) {
            return;
        }

        // 既にポイント付与済みの場合はスキップ（二重付与防止）
        if ($Coupon->isReferrerPointGranted()) {
            return;
        }

        // 紹介者にポイント付与
        $Referrer = $Coupon->getReferrer();
        $pointToAdd = $Coupon->getReferrerPoint();
        $Referrer->setPoint(intval($Referrer->getPoint()) + $pointToAdd);
        $Coupon->setReferrerPointGranted(true);

        // 紹介者へポイント付与通知メールを送信
        try {
            $this->mailService->sendReferrerRewardMail($Coupon);
        } catch (\Exception $e) {
            log_warning('紹介者へのポイント付与通知メール送信に失敗しました。', ['error' => $e->getMessage()]);
        }
    }

    /**
     * {@inheritdoc}
     *
     * prepare の変更を元に戻す（クーポンを未使用に復元、被紹介者情報クリア）.
     */
    public function rollback(ItemHolderInterface $itemHolder, PurchaseContext $context): void
    {
        if (!$itemHolder instanceof Order) {
            return;
        }

        $Coupon = $this->couponRepository->findByOrder($itemHolder);
        if ($Coupon === null) {
            return;
        }

        // クーポンを未使用に戻す
        $Coupon->setStatus(\Customize\Entity\ReferralCoupon::STATUS_UNUSED);
        $Coupon->setReferee(null);
        $Coupon->setRefereeOrder(null);
        $Coupon->setUsedAt(null);
    }
}
