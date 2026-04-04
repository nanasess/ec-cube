<?php

namespace Customize\Service;

use Customize\Entity\ReferralCoupon;
use Eccube\Entity\BaseInfo;
use Eccube\Entity\Order;
use Eccube\Repository\BaseInfoRepository;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Twig\Environment;

/**
 * 紹介クーポン関連メール送信サービス.
 */
class ReferralCouponMailService
{
    private MailerInterface $mailer;
    private Environment $twig;
    private BaseInfo $BaseInfo;

    public function __construct(
        MailerInterface $mailer,
        Environment $twig,
        BaseInfoRepository $baseInfoRepository
    ) {
        $this->mailer = $mailer;
        $this->twig = $twig;
        $this->BaseInfo = $baseInfoRepository->get();
    }

    /**
     * クーポン発行通知メールを購入者に送信する.
     */
    public function sendCouponIssuedMail(ReferralCoupon $Coupon, Order $Order): void
    {
        log_info('紹介クーポン発行通知メール送信開始');

        $Customer = $Coupon->getReferrer();
        $body = $this->twig->render('@Customize/mail/referral_coupon_issued.twig', [
            'Customer' => $Customer,
            'Coupon' => $Coupon,
            'Order' => $Order,
            'BaseInfo' => $this->BaseInfo,
        ]);

        $message = (new Email())
            ->subject('[' . $this->BaseInfo->getShopName() . '] 紹介クーポンを発行しました')
            ->from(new Address($this->BaseInfo->getEmail01(), $this->BaseInfo->getShopName()))
            ->to($Customer->getEmail())
            ->replyTo($this->BaseInfo->getEmail03())
            ->returnPath($this->BaseInfo->getEmail04())
            ->text($body);

        $this->mailer->send($message);

        log_info('紹介クーポン発行通知メール送信完了');
    }

    /**
     * 紹介者へポイント付与通知メールを送信する.
     */
    public function sendReferrerRewardMail(ReferralCoupon $Coupon): void
    {
        log_info('紹介報酬通知メール送信開始');

        $Referrer = $Coupon->getReferrer();
        $body = $this->twig->render('@Customize/mail/referral_reward_notify.twig', [
            'Referrer' => $Referrer,
            'Coupon' => $Coupon,
            'BaseInfo' => $this->BaseInfo,
        ]);

        $message = (new Email())
            ->subject('[' . $this->BaseInfo->getShopName() . '] 紹介ポイントが付与されました')
            ->from(new Address($this->BaseInfo->getEmail01(), $this->BaseInfo->getShopName()))
            ->to($Referrer->getEmail())
            ->replyTo($this->BaseInfo->getEmail03())
            ->returnPath($this->BaseInfo->getEmail04())
            ->text($body);

        $this->mailer->send($message);

        log_info('紹介報酬通知メール送信完了');
    }

    /**
     * 紹介者へポイント回収通知メールを送信する.
     */
    public function sendReferrerPointRevokedMail(ReferralCoupon $Coupon): void
    {
        log_info('紹介ポイント回収通知メール送信開始');

        $Referrer = $Coupon->getReferrer();
        $body = $this->twig->render('@Customize/mail/referral_point_revoked.twig', [
            'Referrer' => $Referrer,
            'Coupon' => $Coupon,
            'BaseInfo' => $this->BaseInfo,
        ]);

        $message = (new Email())
            ->subject('[' . $this->BaseInfo->getShopName() . '] 紹介ポイントが回収されました')
            ->from(new Address($this->BaseInfo->getEmail01(), $this->BaseInfo->getShopName()))
            ->to($Referrer->getEmail())
            ->replyTo($this->BaseInfo->getEmail03())
            ->returnPath($this->BaseInfo->getEmail04())
            ->text($body);

        $this->mailer->send($message);

        log_info('紹介ポイント回収通知メール送信完了');
    }
}
