<?php

/*
 * 名入れ機能: 受注フロー連携プロセッサ
 *
 * カート段階でセッションに保持した名入れテキストを、受注明細(OrderItem)の
 * naire_text カラムへ反映する。
 *
 * - ItemHolderPreprocessor: shopping フロー（/shopping・/shopping/confirm）で
 *   実行され、セッションの名入れテキストを OrderItem.naire_text にコピーする。
 *   確定前にコピーするため、注文確認画面でも名入れが表示される。
 * - PurchaseProcessor::commit(): 購入確定時にセッションの名入れ情報をクリアする。
 */

namespace Customize\Service\PurchaseFlow\Processor;

use Customize\EventListener\AddCartNaireListener;
use Eccube\Entity\ItemHolderInterface;
use Eccube\Entity\Order;
use Eccube\Service\PurchaseFlow\ItemHolderPreprocessor;
use Eccube\Service\PurchaseFlow\PurchaseContext;
use Eccube\Service\PurchaseFlow\PurchaseProcessor;
use Symfony\Component\HttpFoundation\RequestStack;

class NaireProcessor implements ItemHolderPreprocessor, PurchaseProcessor
{
    /**
     * @var RequestStack
     */
    private RequestStack $requestStack;

    public function __construct(RequestStack $requestStack)
    {
        $this->requestStack = $requestStack;
    }

    /**
     * shopping フローの前処理: セッションの名入れテキストを OrderItem へコピーする。
     *
     * {@inheritdoc}
     */
    public function process(ItemHolderInterface $itemHolder, PurchaseContext $context): void
    {
        if (!$itemHolder instanceof Order) {
            return;
        }

        $naireInfo = $this->getSessionNaireInfo();
        if (empty($naireInfo)) {
            return;
        }

        foreach ($itemHolder->getOrderItems() as $orderItem) {
            // 商品明細のみ対象
            if (!$orderItem->isProduct()) {
                continue;
            }
            $productClass = $orderItem->getProductClass();
            if ($productClass === null) {
                continue;
            }
            $productClassId = (string) $productClass->getId();
            if (isset($naireInfo[$productClassId]) && $naireInfo[$productClassId] !== '') {
                $orderItem->setNaireText($naireInfo[$productClassId]);
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function prepare(ItemHolderInterface $target, PurchaseContext $context): void
    {
        // 名入れテキストは process() で OrderItem に反映済みのため、ここでは何もしない。
    }

    /**
     * 購入確定時: セッションの名入れ情報をクリアする。
     *
     * {@inheritdoc}
     */
    public function commit(ItemHolderInterface $target, PurchaseContext $context): void
    {
        $request = $this->requestStack->getCurrentRequest();
        if ($request === null) {
            return;
        }
        $request->getSession()->remove(AddCartNaireListener::SESSION_NAIRE_KEY);
    }

    /**
     * {@inheritdoc}
     */
    public function rollback(ItemHolderInterface $itemHolder, PurchaseContext $context): void
    {
        // OrderItem.naire_text は受注のロールバックに追従するため、個別処理は不要。
    }

    /**
     * セッションから名入れ情報 (product_class_id => naire_text) を取得する。
     *
     * @return array
     */
    private function getSessionNaireInfo(): array
    {
        $request = $this->requestStack->getCurrentRequest();
        if ($request === null) {
            return [];
        }

        return $request->getSession()->get(AddCartNaireListener::SESSION_NAIRE_KEY, []);
    }
}
