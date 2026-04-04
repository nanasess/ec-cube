<?php

/*
 * 管理画面の商品編集画面に品切れ種別フィールドを挿入する EventSubscriber
 */

namespace Customize\EventListener;

use Eccube\Event\TemplateEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class AdminProductEditSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            '@admin/Product/product.twig' => 'onAdminProductEdit',
        ];
    }

    public function onAdminProductEdit(TemplateEvent $event): void
    {
        // 在庫無制限チェックボックスの後に品切れ種別プルダウンを挿入
        $snippet = <<<'TWIG'
{% if form.class.out_of_stock_type is defined %}
<div class="row mb-2">
    <div class="col-3">
        <span>{{ 'admin.product.out_of_stock_type'|trans }}</span>
    </div>
    <div class="col">
        {{ form_widget(form.class.out_of_stock_type) }}
        {{ form_errors(form.class.out_of_stock_type) }}
    </div>
</div>
{% endif %}
TWIG;

        $source = $event->getSource();

        // 在庫無制限の後（stock_unlimited errors の後の </div></div></div> の後）に挿入
        $search = '{{ form_errors(form.class.stock_unlimited) }}
                                            </div>
                                        </div>
                                    </div>
                                {% endif %}';

        $replacement = '{{ form_errors(form.class.stock_unlimited) }}
                                            </div>
                                        </div>
                                    </div>
                                {% endif %}
                                ' . $snippet;

        $event->setSource(str_replace($search, $replacement, $source));
    }
}
