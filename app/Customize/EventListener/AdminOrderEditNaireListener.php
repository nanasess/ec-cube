<?php

/*
 * 名入れ機能: 管理画面 受注編集への名入れ表示注入
 *
 * 受注編集テンプレート(@admin/Order/edit.twig)の受注明細ループに、
 * OrderItem.naire_text を表示するスニペットを TemplateEvent で注入する。
 * （FormExtension で追加したフィールドはテンプレートに form_row が無いと
 * 表示されないため、ここでソースに直接表示を差し込む）
 */

namespace Customize\EventListener;

use Eccube\Event\TemplateEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class AdminOrderEditNaireListener implements EventSubscriberInterface
{
    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents(): array
    {
        return [
            '@admin/Order/edit.twig' => 'onAdminOrderEdit',
        ];
    }

    /**
     * 受注明細の商品名表示の直後に名入れテキストを差し込む。
     *
     * @param TemplateEvent $event
     */
    public function onAdminOrderEdit(TemplateEvent $event): void
    {
        $source = $event->getSource();

        // 受注明細ループ内（OrderItem がスコープにある箇所）のアンカー
        $anchor = '{{ form_errors(orderItemForm.product_name) }}';
        if (strpos($source, $anchor) === false) {
            return;
        }

        $snippet = $anchor."\n"
            .'{% if OrderItem.naire_text is defined and OrderItem.naire_text is not empty %}'
            ."\n".'    <div class="mt-1"><span class="badge bg-info text-dark">名入れ</span> {{ OrderItem.naire_text }}</div>'
            ."\n".'{% endif %}';

        $event->setSource(str_replace($anchor, $snippet, $source));
    }
}
