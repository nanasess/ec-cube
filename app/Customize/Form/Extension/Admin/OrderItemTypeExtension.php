<?php

/*
 * 名入れ機能: 管理画面の受注明細フォーム拡張
 *
 * 受注明細に名入れ情報を読み取り専用で表示するためのフォーム拡張。
 */

namespace Customize\Form\Extension\Admin;

use Eccube\Form\Type\Admin\OrderItemType;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

class OrderItemTypeExtension extends AbstractTypeExtension
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        // POST_SET_DATA で OrderItem の NaireInfo を取得し、読み取り専用フィールドとして追加
        $builder->addEventListener(FormEvents::POST_SET_DATA, function (FormEvent $event) {
            $orderItem = $event->getData();
            $form = $event->getForm();

            if ($orderItem === null) {
                return;
            }

            // OrderItem に NaireInfo が紐付いている場合のみフィールドを追加
            if (method_exists($orderItem, 'getNaireInfo') && $orderItem->getNaireInfo() !== null) {
                $form->add('naire_text_display', TextType::class, [
                    'mapped' => false,
                    'required' => false,
                    'label' => '名入れテキスト',
                    'data' => $orderItem->getNaireInfo()->getNaireText(),
                    'attr' => [
                        'readonly' => true,
                        'class' => 'form-control-plaintext',
                    ],
                ]);
            }
        });
    }

    /**
     * {@inheritdoc}
     */
    public static function getExtendedTypes(): iterable
    {
        yield OrderItemType::class;
    }
}
