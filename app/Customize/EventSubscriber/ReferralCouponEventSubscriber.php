<?php

namespace Customize\EventSubscriber;

use Customize\Entity\ReferralCoupon;
use Customize\Repository\ReferralConfigRepository;
use Customize\Repository\ReferralCouponRepository;
use Customize\Service\ReferralCouponMailService;
use Doctrine\ORM\EntityManagerInterface;
use Eccube\Entity\Order;
use Eccube\Event\EccubeEvents;
use Eccube\Event\EventArgs;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * 購入完了時に紹介クーポンを自動発行する EventSubscriber.
 *
 * FRONT_SHOPPING_COMPLETE_INITIALIZE イベントをリッスンし、
 * 購入者が会員の場合にクーポンを発行する.
 */
class ReferralCouponEventSubscriber implements EventSubscriberInterface
{
    private EntityManagerInterface $entityManager;
    private ReferralConfigRepository $configRepository;
    private ReferralCouponRepository $couponRepository;
    private ReferralCouponMailService $mailService;

    public function __construct(
        EntityManagerInterface $entityManager,
        ReferralConfigRepository $configRepository,
        ReferralCouponRepository $couponRepository,
        ReferralCouponMailService $mailService
    ) {
        $this->entityManager = $entityManager;
        $this->configRepository = $configRepository;
        $this->couponRepository = $couponRepository;
        $this->mailService = $mailService;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents(): array
    {
        return [
            EccubeEvents::FRONT_SHOPPING_COMPLETE_INITIALIZE => 'onShoppingComplete',
        ];
    }

    /**
     * 購入完了時にクーポンを発行する.
     */
    public function onShoppingComplete(EventArgs $event): void
    {
        $config = $this->configRepository->get();

        // 機能が無効の場合はスキップ
        if (!$config->isEnabled()) {
            return;
        }

        /** @var Order $Order */
        $Order = $event->getArgument('Order');
        $Customer = $Order->getCustomer();

        // 非会員は対象外
        if ($Customer === null) {
            return;
        }

        // 発行上限チェック
        $issuedCount = $this->couponRepository->countByReferrer($Customer);
        if ($issuedCount >= $config->getMaxCouponsPerCustomer()) {
            log_info('紹介クーポン発行上限に達しています。', [
                'customer_id' => $Customer->getId(),
                'issued_count' => $issuedCount,
                'max' => $config->getMaxCouponsPerCustomer(),
            ]);

            return;
        }

        // クーポンコード生成（ユニーク保証のためリトライ）
        $couponCode = $this->generateUniqueCouponCode();

        // 有効期限の計算
        $expiresAt = new \DateTime();
        $expiresAt->modify('+' . $config->getExpiryDays() . ' days');

        // クーポンエンティティを作成
        $Coupon = new ReferralCoupon();
        $Coupon->setCouponCode($couponCode)
            ->setReferrer($Customer)
            ->setDiscountAmount($config->getDiscountAmount())
            ->setReferrerPoint($config->getReferrerPoint())
            ->setExpiresAt($expiresAt);

        $this->entityManager->persist($Coupon);
        $this->entityManager->flush();

        log_info('紹介クーポンを発行しました。', [
            'customer_id' => $Customer->getId(),
            'coupon_code' => $couponCode,
        ]);

        // クーポン発行通知メールを送信
        try {
            $this->mailService->sendCouponIssuedMail($Coupon, $Order);
        } catch (\Exception $e) {
            log_warning('紹介クーポン発行通知メール送信に失敗しました。', ['error' => $e->getMessage()]);
        }
    }

    /**
     * ユニークなクーポンコードを生成する.
     */
    private function generateUniqueCouponCode(): string
    {
        $maxRetries = 10;
        for ($i = 0; $i < $maxRetries; $i++) {
            $code = ReferralCoupon::generateCouponCode();
            $existing = $this->couponRepository->findOneBy(['coupon_code' => $code]);
            if ($existing === null) {
                return $code;
            }
        }

        throw new \RuntimeException('ユニークなクーポンコードの生成に失敗しました。');
    }
}
