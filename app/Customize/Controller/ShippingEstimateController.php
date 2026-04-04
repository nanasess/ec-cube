<?php

/*
 * 送料見積もりAPIコントローラ。
 * 都道府県IDを指定して、利用可能な配送方法ごとの送料をJSON形式で返す。
 */

namespace Customize\Controller;

use Eccube\Controller\AbstractController;
use Eccube\Repository\DeliveryFeeRepository;
use Eccube\Repository\Master\PrefRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

class ShippingEstimateController extends AbstractController
{
    /** @var DeliveryFeeRepository */
    private $deliveryFeeRepository;

    /** @var PrefRepository */
    private $prefRepository;

    public function __construct(
        DeliveryFeeRepository $deliveryFeeRepository,
        PrefRepository $prefRepository
    ) {
        $this->deliveryFeeRepository = $deliveryFeeRepository;
        $this->prefRepository = $prefRepository;
    }

    /**
     * 指定された都道府県の送料一覧を返すAPIエンドポイント。
     *
     * @Route("/api/shipping_estimate/{pref_id}", name="shipping_estimate", methods={"GET"}, requirements={"pref_id" = "\d+"})
     *
     * @param int $pref_id 都道府県ID
     *
     * @return JsonResponse
     */
    public function estimate(int $pref_id): JsonResponse
    {
        /** @var Pref|null $Pref */
        $Pref = $this->prefRepository->find($pref_id);

        if ($Pref === null) {
            return $this->json([
                'error' => '指定された都道府県が見つかりません',
            ], 404);
        }

        // 指定された都道府県に紐づく送料を全配送方法分取得
        $deliveryFees = $this->deliveryFeeRepository->findBy(['Pref' => $Pref]);

        $fees = [];
        foreach ($deliveryFees as $deliveryFee) {
            $delivery = $deliveryFee->getDelivery();
            // 非公開の配送方法は除外（visible が false のもの）
            if ($delivery === null || !$delivery->isVisible()) {
                continue;
            }
            $fees[] = [
                'delivery_name' => $delivery->getName(),
                'fee' => (int) $deliveryFee->getFee(),
            ];
        }

        return $this->json([
            'pref_name' => $Pref->getName(),
            'fees' => $fees,
        ]);
    }
}
