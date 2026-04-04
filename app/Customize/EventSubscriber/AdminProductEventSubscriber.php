<?php

/*
 * 管理画面 商品編集ページに「限定公開リンク管理」ボタンを挿入するイベントサブスクライバ
 *
 * TemplateEvent を利用して、商品編集画面のメモ欄の下にリンク管理画面への
 * 導線ボタンを追加する。
 */

namespace Customize\EventSubscriber;

use Eccube\Event\TemplateEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class AdminProductEventSubscriber implements EventSubscriberInterface
{
    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents(): array
    {
        return [
            '@admin/Product/product.twig' => 'onAdminProductEdit',
        ];
    }

    /**
     * 商品編集画面に限定公開リンク管理ボタンを挿入
     *
     * 商品が新規作成（ID未設定）の場合は表示しない。
     */
    public function onAdminProductEdit(TemplateEvent $event): void
    {
        $parameters = $event->getParameters();

        // 新規作成時はProduct.idがnullのため表示しない
        if (!isset($parameters['Product']) || $parameters['Product']->getId() === null) {
            return;
        }

        // スニペットテンプレートを追加（</body>直前に出力される）
        $event->addSnippet('@admin/Product/private_link_button.twig');
    }
}
