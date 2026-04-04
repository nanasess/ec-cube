<?php

namespace Customize\Service\PurchaseFlow\Processor;

use Customize\Entity\ReferralCoupon;
use Customize\Repository\ReferralCouponRepository;
use Doctrine\ORM\EntityManagerInterface;
use Eccube\Entity\ItemHolderInterface;
use Eccube\Entity\Master\OrderItemType;
use Eccube\Entity\Master\TaxDisplayType;
use Eccube\Entity\Master\TaxType;
use Eccube\Entity\Order;
use Eccube\Entity\OrderItem;
use Eccube\Service\PurchaseFlow\DiscountProcessor;
use Eccube\Service\PurchaseFlow\ProcessResult;
use Eccube\Service\PurchaseFlow\PurchaseContext;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * 紹介クーポン割引プロセッサ.
 *
 * セッションに保存されたクーポンコードを元に、注文に値引き明細を追加する.
 * 管理画面（orderフロー）ではセッションを参照せず、既存の割引明細を尊重する.
 */
class ReferralCouponDiscountProcessor implements DiscountProcessor
{
    /** セッションキー */
    public const SESSION_KEY = 'referral_coupon_code';

    private EntityManagerInterface $entityManager;
    private ReferralCouponRepository $couponRepository;
    private RequestStack $requestStack;

    public function __construct(
        EntityManagerInterface $entityManager,
        ReferralCouponRepository $couponRepository,
        RequestStack $requestStack
    ) {
        $this->entityManager = $entityManager;
        $this->couponRepository = $couponRepository;
        $this->requestStack = $requestStack;
    }

    /**
     * {@inheritdoc}
     *
     * 既存の紹介クーポン割引明細を削除する.
     */
    public function removeDiscountItem(ItemHolderInterface $itemHolder, PurchaseContext $context): void
    {
        if (!$this->supports($itemHolder, $context)) {
            return;
        }

        foreach ($itemHolder->getItems() as $item) {
            if ($item->getProcessorName() === self::class) {
                $itemHolder->removeOrderItem($item);
                $this->entityManager->remove($item);
            }
        }
    }

    /**
     * {@inheritdoc}
     *
     * セッション上のクーポンコードを検証し、値引き明細を追加する.
     */
    public function addDiscountItem(ItemHolderInterface $itemHolder, PurchaseContext $context): ?ProcessResult
    {
        if (!$this->supports($itemHolder, $context)) {
            return null;
        }

        // 管理画面（受注編集）ではセッションを参照しない
        if ($context->isOrderFlow()) {
            return null;
        }

        $session = $this->requestStack->getSession();
        $couponCode = $session->get(self::SESSION_KEY);

        if (empty($couponCode)) {
            return null;
        }

        $Coupon = $this->couponRepository->findUsableByCouponCode($couponCode);
        if ($Coupon === null) {
            // 無効なクーポンの場合はセッションからクリアしてワーニングを返す
            $session->remove(self::SESSION_KEY);

            return ProcessResult::warn('紹介クーポンが無効です。', self::class);
        }

        /** @var Order $Order */
        $Order = $itemHolder;

        // 自分自身が発行したクーポンは使用不可
        $Customer = $Order->getCustomer();
        if ($Customer && $Coupon->getReferrer()->getId() === $Customer->getId()) {
            $session->remove(self::SESSION_KEY);

            return ProcessResult::warn('ご自身が発行した紹介クーポンはご利用いただけません。', self::class);
        }

        // 割引額を決定（注文合計が割引額より小さい場合は注文合計を上限とする）
        $discountAmount = $Coupon->getDiscountAmount();
        $currentTotal = $itemHolder->getTotal();
        if ($currentTotal < $discountAmount) {
            $discountAmount = max(0, $currentTotal);
        }

        if ($discountAmount <= 0) {
            return null;
        }

        // 値引き明細を追加
        $DiscountType = $this->entityManager->find(OrderItemType::class, OrderItemType::DISCOUNT);
        $TaxInclude = $this->entityManager->find(TaxDisplayType::class, TaxDisplayType::INCLUDED);
        $NonTaxable = $this->entityManager->find(TaxType::class, TaxType::NON_TAXABLE);

        $OrderItem = new OrderItem();
        $OrderItem->setProductName('紹介クーポン値引き')
            ->setPrice($discountAmount * -1)
            ->setQuantity(1)
            ->setTax(0)
            ->setTaxRate(0)
            ->setRoundingType(null)
            ->setOrderItemType($DiscountType)
            ->setTaxDisplayType($TaxInclude)
            ->setTaxType($NonTaxable)
            ->setOrder($Order)
            ->setProcessorName(self::class);

        $itemHolder->addItem($OrderItem);

        return null;
    }

    /**
     * Processor が実行可能かどうかを判定する.
     */
    private function supports(ItemHolderInterface $itemHolder, PurchaseContext $context): bool
    {
        if (!$itemHolder instanceof Order) {
            return false;
        }

        return true;
    }
}
