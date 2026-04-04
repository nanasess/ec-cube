<?php

namespace Customize\Service\PurchaseFlow\Processor;

use Customize\Entity\CrossProductDiscount;
use Customize\Repository\CrossProductDiscountRepository;
use Doctrine\ORM\EntityManagerInterface;
use Eccube\Entity\ItemHolderInterface;
use Eccube\Entity\Master\OrderItemType;
use Eccube\Entity\Master\TaxDisplayType;
use Eccube\Entity\Master\TaxType;
use Eccube\Entity\Order;
use Eccube\Entity\OrderItem;
use Eccube\Service\PurchaseFlow\DiscountProcessor;
use Eccube\Service\PurchaseFlow\PurchaseContext;

/**
 * セット割引プロセッサ
 *
 * 同一カート/注文内で、トリガー商品とターゲット商品の両方が存在する場合に
 * ターゲット商品に対して割引明細を追加する。
 *
 * DiscountProcessor インターフェースを実装し、cart と shopping の両方のフローに登録する。
 */
class CrossProductDiscountProcessor implements DiscountProcessor
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var CrossProductDiscountRepository
     */
    private $crossProductDiscountRepository;

    public function __construct(
        EntityManagerInterface $entityManager,
        CrossProductDiscountRepository $crossProductDiscountRepository
    ) {
        $this->entityManager = $entityManager;
        $this->crossProductDiscountRepository = $crossProductDiscountRepository;
    }

    /**
     * セット割引の明細を削除する
     *
     * PurchaseFlow が最初にこのメソッドを呼び出し、既存の割引明細をクリアする。
     * processor_name が自クラスの FQCN に一致する明細のみを削除する。
     */
    public function removeDiscountItem(ItemHolderInterface $itemHolder, PurchaseContext $context): void
    {
        foreach ($itemHolder->getItems() as $item) {
            if ($item instanceof OrderItem && $item->getProcessorName() === self::class) {
                $itemHolder->removeOrderItem($item);
                $this->entityManager->remove($item);
            }
        }
    }

    /**
     * セット割引の明細を追加する
     *
     * カート/注文内の商品明細を走査し、有効なセット割引ルールに合致する
     * 組み合わせがあればターゲット商品に対する割引明細を追加する。
     */
    public function addDiscountItem(ItemHolderInterface $itemHolder, PurchaseContext $context)
    {
        // カート内の商品明細を収集
        $productItems = $this->getProductItems($itemHolder);
        if (empty($productItems)) {
            return;
        }

        // カート内の商品IDリストを取得
        $productIds = $this->extractProductIds($productItems);
        if (empty($productIds)) {
            return;
        }

        // 有効なセット割引ルールを取得（trigger と target の両方がカート内にあるもの）
        $rules = $this->crossProductDiscountRepository->findEnabledRulesByProductIds($productIds);
        if (empty($rules)) {
            return;
        }

        // 各ルールについて割引を適用
        // target_product ごとの割引額を集計（同一ターゲットに複数ルールが適用される場合は全て適用）
        $discounts = $this->calculateDiscounts($rules, $productItems);

        // 割引明細を追加
        foreach ($discounts as $discount) {
            $this->addDiscountOrderItem($itemHolder, $discount['name'], $discount['amount']);
        }
    }

    /**
     * カート/注文内の商品明細を取得する
     *
     * @return OrderItem[]
     */
    private function getProductItems(ItemHolderInterface $itemHolder): array
    {
        $productItems = [];
        foreach ($itemHolder->getItems() as $item) {
            if ($item instanceof OrderItem && $item->isProduct()) {
                $productItems[] = $item;
            }
        }

        return $productItems;
    }

    /**
     * 商品明細から商品IDリストを抽出する（重複排除）
     *
     * @param OrderItem[] $productItems
     *
     * @return int[]
     */
    private function extractProductIds(array $productItems): array
    {
        $productIds = [];
        foreach ($productItems as $item) {
            $product = $item->getProduct();
            if ($product !== null) {
                $productIds[$product->getId()] = $product->getId();
            }
        }

        return array_values($productIds);
    }

    /**
     * ルールに基づいて割引額を計算する
     *
     * trigger_product が複数個カートにあっても割引は1回のみ適用。
     * 同一 target_product に複数ルールが適用される場合は全て適用する。
     *
     * @param CrossProductDiscount[] $rules
     * @param OrderItem[] $productItems
     *
     * @return array{name: string, amount: int}[]
     */
    private function calculateDiscounts(array $rules, array $productItems): array
    {
        // target商品ごとに最も安い単価を把握（割引計算用）
        $targetPrices = [];
        foreach ($productItems as $item) {
            $product = $item->getProduct();
            if ($product === null) {
                continue;
            }
            $pid = $product->getId();
            $price = (int) $item->getPriceIncTax();
            // 同一商品の複数規格がある場合は最小単価を使用
            if (!isset($targetPrices[$pid]) || $price < $targetPrices[$pid]) {
                $targetPrices[$pid] = $price;
            }
        }

        $discounts = [];
        // ルールごとの重複防止用（同一ルールIDは1回のみ）
        $appliedRuleIds = [];

        foreach ($rules as $rule) {
            if (in_array($rule->getId(), $appliedRuleIds, true)) {
                continue;
            }
            $appliedRuleIds[] = $rule->getId();

            $targetProduct = $rule->getTargetProduct();
            $targetProductId = $targetProduct->getId();

            if (!isset($targetPrices[$targetProductId])) {
                continue;
            }

            $price = $targetPrices[$targetProductId];
            $discountAmount = $this->calcDiscountAmount($rule, $price);

            if ($discountAmount <= 0) {
                continue;
            }

            $discounts[] = [
                'name' => 'セット割引: ' . $targetProduct->getName(),
                'amount' => $discountAmount,
            ];
        }

        return $discounts;
    }

    /**
     * ルールと商品単価から割引額を計算する
     *
     * @param CrossProductDiscount $rule セット割引ルール
     * @param int $price ターゲット商品の税込単価
     *
     * @return int 割引額（正の値）
     */
    private function calcDiscountAmount(CrossProductDiscount $rule, int $price): int
    {
        $value = (float) $rule->getDiscountValue();

        if ($rule->getDiscountType() === CrossProductDiscount::DISCOUNT_TYPE_PERCENT) {
            // 定率割引: 単価 * (割引率 / 100)
            $amount = (int) floor($price * $value / 100);
        } else {
            // 定額割引
            $amount = (int) floor($value);
        }

        // 割引額が商品単価を超えないように調整
        if ($amount > $price) {
            $amount = $price;
        }

        return $amount;
    }

    /**
     * 割引明細を追加する
     *
     * OrderItemType::DISCOUNT で不課税（NON_TAXABLE）の明細として追加する。
     * 合計金額が0円以下にならないよう調整する。
     *
     * @param ItemHolderInterface $itemHolder
     * @param string $productName 割引明細の商品名
     * @param int $discountAmount 割引額（正の値）
     */
    private function addDiscountOrderItem(
        ItemHolderInterface $itemHolder,
        string $productName,
        int $discountAmount
    ): void {
        // 合計金額が0円以下にならないよう調整
        $total = $itemHolder->getTotal();
        if ($total <= 0) {
            return;
        }
        if ($discountAmount > $total) {
            $discountAmount = $total;
        }

        /** @var OrderItemType $DiscountType */
        $DiscountType = $this->entityManager->find(OrderItemType::class, OrderItemType::DISCOUNT);
        /** @var TaxDisplayType $TaxInclude */
        $TaxInclude = $this->entityManager->find(TaxDisplayType::class, TaxDisplayType::INCLUDED);
        /** @var TaxType $NonTaxable */
        $NonTaxable = $this->entityManager->find(TaxType::class, TaxType::NON_TAXABLE);

        $OrderItem = new OrderItem();
        $OrderItem->setProductName($productName)
            ->setPrice(-1 * $discountAmount)
            ->setQuantity(1)
            ->setTax(0)
            ->setTaxRate(0)
            ->setRoundingType(null)
            ->setOrderItemType($DiscountType)
            ->setTaxDisplayType($TaxInclude)
            ->setTaxType($NonTaxable)
            ->setProcessorName(self::class);

        if ($itemHolder instanceof Order) {
            $OrderItem->setOrder($itemHolder);
        }

        $itemHolder->addItem($OrderItem);
    }
}
