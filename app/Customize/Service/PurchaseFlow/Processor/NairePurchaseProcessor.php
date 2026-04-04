<?php

/*
 * 名入れ機能: 購入確定時の名入れ情報永続化プロセッサ
 *
 * PurchaseProcessor を実装し、購入フローの中で名入れ情報を
 * セッションから取得して NaireInfo エンティティとして永続化する。
 *
 * - prepare(): セッションの名入れ情報を NaireInfo として作成・persist
 * - commit(): セッションの名入れ情報をクリア
 * - rollback(): NaireInfo を削除
 */

namespace Customize\Service\PurchaseFlow\Processor;

use Customize\Entity\NaireInfo;
use Customize\EventListener\AddCartNaireListener;
use Doctrine\ORM\EntityManagerInterface;
use Eccube\Entity\ItemHolderInterface;
use Eccube\Entity\Order;
use Eccube\Service\PurchaseFlow\PurchaseContext;
use Eccube\Service\PurchaseFlow\PurchaseProcessor;
use Symfony\Component\HttpFoundation\RequestStack;

class NairePurchaseProcessor implements PurchaseProcessor
{
    /**
     * @var EntityManagerInterface
     */
    private EntityManagerInterface $entityManager;

    /**
     * @var RequestStack
     */
    private RequestStack $requestStack;

    public function __construct(
        EntityManagerInterface $entityManager,
        RequestStack $requestStack
    ) {
        $this->entityManager = $entityManager;
        $this->requestStack = $requestStack;
    }

    /**
     * 受注の仮確定処理: セッションの名入れ情報を NaireInfo として永続化する。
     *
     * {@inheritdoc}
     */
    public function prepare(ItemHolderInterface $target, PurchaseContext $context): void
    {
        if (!$target instanceof Order) {
            return;
        }

        $request = $this->requestStack->getCurrentRequest();
        if ($request === null) {
            return;
        }

        $session = $request->getSession();
        $naireInfo = $session->get(AddCartNaireListener::SESSION_NAIRE_KEY, []);

        if (empty($naireInfo)) {
            return;
        }

        // 受注明細ごとに名入れ情報を紐付ける
        foreach ($target->getOrderItems() as $orderItem) {
            // 商品明細のみ対象
            if (!$orderItem->isProduct()) {
                continue;
            }

            $productClass = $orderItem->getProductClass();
            if ($productClass === null) {
                continue;
            }

            $productClassId = (string) $productClass->getId();

            if (isset($naireInfo[$productClassId]) && !empty($naireInfo[$productClassId])) {
                // 既存の NaireInfo がある場合は更新、なければ新規作成
                $existingNaireInfo = $orderItem->getNaireInfo();
                if ($existingNaireInfo !== null) {
                    $existingNaireInfo->setNaireText($naireInfo[$productClassId]);
                } else {
                    $entity = new NaireInfo();
                    $entity->setNaireText($naireInfo[$productClassId]);
                    $entity->setOrderItem($orderItem);
                    $orderItem->setNaireInfo($entity);
                    $this->entityManager->persist($entity);
                }
            }
        }
    }

    /**
     * 受注の確定処理: セッションの名入れ情報をクリアする。
     *
     * {@inheritdoc}
     */
    public function commit(ItemHolderInterface $target, PurchaseContext $context): void
    {
        if (!$target instanceof Order) {
            return;
        }

        $request = $this->requestStack->getCurrentRequest();
        if ($request === null) {
            return;
        }

        $request->getSession()->remove(AddCartNaireListener::SESSION_NAIRE_KEY);
    }

    /**
     * 仮確定した受注データの取り消し処理: NaireInfo を削除する。
     *
     * {@inheritdoc}
     */
    public function rollback(ItemHolderInterface $target, PurchaseContext $context): void
    {
        if (!$target instanceof Order) {
            return;
        }

        foreach ($target->getOrderItems() as $orderItem) {
            if (!$orderItem->isProduct()) {
                continue;
            }

            $naireInfo = $orderItem->getNaireInfo();
            if ($naireInfo !== null) {
                $orderItem->setNaireInfo(null);
                $this->entityManager->remove($naireInfo);
            }
        }
    }
}
