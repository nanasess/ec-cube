<?php

namespace Customize\EventSubscriber;

use Customize\Entity\ReferralCoupon;
use Customize\Repository\ReferralCouponRepository;
use Customize\Service\ReferralCouponMailService;
use Eccube\Entity\Order;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Workflow\Event\Event;

/**
 * 受注ステータス遷移時に紹介クーポンのポイント回収/再付与を行う EventSubscriber.
 *
 * - 返品時: 紹介者ポイント回収、クーポンステータスを返品取消に
 * - キャンセル時: 返品時と同じ処理
 * - 返品取消時: 紹介者ポイント再付与、クーポンステータスを使用済に戻す
 */
class ReferralCouponOrderStateSubscriber implements EventSubscriberInterface
{
    private ReferralCouponRepository $couponRepository;
    private ReferralCouponMailService $mailService;

    public function __construct(
        ReferralCouponRepository $couponRepository,
        ReferralCouponMailService $mailService
    ) {
        $this->couponRepository = $couponRepository;
        $this->mailService = $mailService;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents(): array
    {
        return [
            'workflow.order.transition.return' => [['onReturn']],
            'workflow.order.transition.cancel' => [['onCancel']],
            'workflow.order.transition.cancel_return' => [['onCancelReturn']],
        ];
    }

    /**
     * 返品時: 紹介者ポイント回収、クーポンを返品取消に.
     */
    public function onReturn(Event $event): void
    {
        $Order = $this->getOrder($event);
        $this->revokeReferrerPoint($Order);
    }

    /**
     * キャンセル時: 紹介者ポイント回収、クーポンを返品取消に.
     */
    public function onCancel(Event $event): void
    {
        $Order = $this->getOrder($event);
        $this->revokeReferrerPoint($Order);
    }

    /**
     * 返品取消時: 紹介者ポイント再付与、クーポンを使用済に戻す.
     */
    public function onCancelReturn(Event $event): void
    {
        $Order = $this->getOrder($event);
        $Coupon = $this->couponRepository->findByOrder($Order);

        if ($Coupon === null) {
            return;
        }

        // ステータスが返品取消でない場合はスキップ
        if ($Coupon->getStatus() !== ReferralCoupon::STATUS_REVOKED) {
            return;
        }

        // 既にポイント付与済みの場合はスキップ（二重付与防止）
        if ($Coupon->isReferrerPointGranted()) {
            return;
        }

        // 紹介者にポイント再付与
        $Referrer = $Coupon->getReferrer();
        $pointToAdd = $Coupon->getReferrerPoint();
        $Referrer->setPoint(intval($Referrer->getPoint()) + $pointToAdd);

        // クーポンステータスを使用済に戻す
        $Coupon->setStatus(ReferralCoupon::STATUS_USED);
        $Coupon->setReferrerPointGranted(true);

        log_info('返品取消により紹介者へポイントを再付与しました。', [
            'coupon_id' => $Coupon->getId(),
            'referrer_id' => $Referrer->getId(),
            'point' => $pointToAdd,
        ]);
    }

    /**
     * 紹介者のポイントを回収し、クーポンを返品取消ステータスにする.
     */
    private function revokeReferrerPoint(Order $Order): void
    {
        $Coupon = $this->couponRepository->findByOrder($Order);

        if ($Coupon === null) {
            return;
        }

        // ポイント未付与の場合は回収不要
        if (!$Coupon->isReferrerPointGranted()) {
            // ステータスだけ更新
            $Coupon->setStatus(ReferralCoupon::STATUS_REVOKED);

            return;
        }

        // 紹介者のポイントを回収（負にならないよう0に丸める）
        $Referrer = $Coupon->getReferrer();
        $pointToRevoke = $Coupon->getReferrerPoint();
        $newPoint = intval($Referrer->getPoint()) - $pointToRevoke;
        $Referrer->setPoint(max(0, $newPoint));

        // クーポンステータスを返品取消に更新
        $Coupon->setStatus(ReferralCoupon::STATUS_REVOKED);
        $Coupon->setReferrerPointGranted(false);

        log_info('返品/キャンセルにより紹介者のポイントを回収しました。', [
            'coupon_id' => $Coupon->getId(),
            'referrer_id' => $Referrer->getId(),
            'revoked_point' => $pointToRevoke,
            'new_point' => max(0, $newPoint),
        ]);

        // 紹介者へポイント回収通知メールを送信
        try {
            $this->mailService->sendReferrerPointRevokedMail($Coupon);
        } catch (\Exception $e) {
            log_warning('紹介者へのポイント回収通知メール送信に失敗しました。', ['error' => $e->getMessage()]);
        }
    }

    /**
     * イベントから注文エンティティを取得する.
     */
    private function getOrder(Event $event): Order
    {
        return $event->getSubject()->getOrder();
    }
}
