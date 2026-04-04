<?php

namespace Customize\Controller\Mypage;

use Customize\Repository\ReferralConfigRepository;
use Customize\Repository\ReferralCouponRepository;
use Eccube\Controller\AbstractController;
use Eccube\Entity\Customer;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * マイページ: 紹介クーポン一覧コントローラ.
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
     * マイページ: 自分の紹介クーポン一覧を表示する.
     *
     * @Route("/mypage/referral_coupon", name="mypage_referral_coupon", methods={"GET"})
     *
     * @Template("Mypage/referral_coupon.twig")
     */
    public function index(Request $request): array
    {
        /** @var Customer $Customer */
        $Customer = $this->getUser();

        $config = $this->configRepository->get();
        $Coupons = $this->couponRepository->findByReferrer($Customer);

        return [
            'Coupons' => $Coupons,
            'Config' => $config,
        ];
    }
}
