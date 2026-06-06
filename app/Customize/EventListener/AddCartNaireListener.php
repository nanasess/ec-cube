<?php

/*
 * 名入れ機能: カート追加時のセッション保存リスナー
 *
 * CartItem の __sleep() は ['product_class_id', 'price', 'quantity'] のみ返すため、
 * 名入れテキストはセッションに別キーで保持する。
 * product_class_id をキーとして名入れテキストを保存する。
 */

namespace Customize\EventListener;

use Eccube\Event\EccubeEvents;
use Eccube\Event\EventArgs;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class AddCartNaireListener implements EventSubscriberInterface
{
    /** セッションに名入れ情報を保存するキー */
    public const SESSION_NAIRE_KEY = 'eccube.naire_info';

    /**
     * @var RequestStack
     */
    private RequestStack $requestStack;

    public function __construct(RequestStack $requestStack)
    {
        $this->requestStack = $requestStack;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents(): array
    {
        return [
            EccubeEvents::FRONT_PRODUCT_CART_ADD_COMPLETE => 'onCartAddComplete',
        ];
    }

    /**
     * カート追加完了時に名入れテキストをセッションに保存する。
     *
     * @param EventArgs $event
     */
    public function onCartAddComplete(EventArgs $event): void
    {
        $request = $this->requestStack->getCurrentRequest();
        if ($request === null) {
            return;
        }

        $session = $request->getSession();
        $form = $event->getArgument('form');

        // フォームに naire_text フィールドが存在しない場合はスキップ
        if (!$form->has('naire_text')) {
            return;
        }

        $naireText = $form->get('naire_text')->getData();

        // 名入れテキストが空の場合はスキップ
        if (empty($naireText)) {
            return;
        }

        // AddCartType のフィールドはルート名（add_cart プレフィックスなし）で POST されるため、
        // ルートの ProductClass パラメータから product_class_id を取得する。
        $productClassId = $request->request->get('ProductClass');

        if ($productClassId === null || $productClassId === '') {
            return;
        }

        // セッションに保存 (product_class_id => naire_text)
        $naireInfo = $session->get(self::SESSION_NAIRE_KEY, []);
        $naireInfo[$productClassId] = $naireText;
        $session->set(self::SESSION_NAIRE_KEY, $naireInfo);
    }
}
