<?php

namespace Customize\Controller;

use Customize\Entity\ReferralCoupon;
use Customize\Repository\ReferralConfigRepository;
use Customize\Repository\ReferralCouponRepository;
use Customize\Service\PurchaseFlow\Processor\ReferralCouponDiscountProcessor;
use Eccube\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * 購入画面での紹介クーポン適用/解除コントローラ.
 */
class ReferralCouponController extends AbstractController
{
    private ReferralCouponRepository $couponRepository;
    private ReferralConfigRepository $configRepository;

    public function __construct(
        ReferralCouponRepository $couponRepository,
        ReferralConfigRepository $configRepository
    ) {
        $this->couponRepository = $couponRepository;
        $this->configRepository = $configRepository;
    }

    /**
     * 紹介クーポンを適用する.
     *
     * @Route("/shopping/referral_coupon", name="shopping_referral_coupon", methods={"POST"})
     */
    public function apply(Request $request): Response
    {
        // CSRF トークン検証
        if (!$this->isCsrfTokenValid('referral_coupon', $request->request->get('_token'))) {
            $this->addError('不正なリクエストです。');

            return $this->redirectToRoute('shopping');
        }

        // 機能が有効かチェック
        $config = $this->configRepository->get();
        if (!$config->isEnabled()) {
            $this->addError('紹介クーポン機能は現在無効です。');

            return $this->redirectToRoute('shopping');
        }

        $couponCode = trim($request->request->get('referral_coupon_code', ''));
        if (empty($couponCode)) {
            $this->addError('クーポンコードを入力してください。');

            return $this->redirectToRoute('shopping');
        }

        // クーポンの存在・有効性チェック
        $Coupon = $this->couponRepository->findUsableByCouponCode($couponCode);
        if ($Coupon === null) {
            $this->addError('無効なクーポンコードです。');

            return $this->redirectToRoute('shopping');
        }

        // 自分自身が発行したクーポンは使用不可
        $Customer = $this->getUser();
        if ($Customer && $Coupon->getReferrer()->getId() === $Customer->getId()) {
            $this->addError('ご自身が発行した紹介クーポンはご利用いただけません。');

            return $this->redirectToRoute('shopping');
        }

        // セッションにクーポンコードを保存
        $session = $request->getSession();
        $session->set(ReferralCouponDiscountProcessor::SESSION_KEY, $couponCode);

        $this->addSuccess('紹介クーポンを適用しました。');

        return $this->redirectToRoute('shopping');
    }

    /**
     * 紹介クーポンを解除する.
     *
     * @Route("/shopping/referral_coupon/remove", name="shopping_referral_coupon_remove", methods={"POST"})
     */
    public function remove(Request $request): Response
    {
        // CSRF トークン検証
        if (!$this->isCsrfTokenValid('referral_coupon', $request->request->get('_token'))) {
            $this->addError('不正なリクエストです。');

            return $this->redirectToRoute('shopping');
        }

        $session = $request->getSession();
        $session->remove(ReferralCouponDiscountProcessor::SESSION_KEY);

        $this->addSuccess('紹介クーポンを解除しました。');

        return $this->redirectToRoute('shopping');
    }
}
