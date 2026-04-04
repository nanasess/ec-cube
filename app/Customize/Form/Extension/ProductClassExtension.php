<?php

/*
 * ProductClassEditType / ProductClassType に品切れ種別フィールドを追加する FormExtension
 *
 * 規格あり商品の規格編集画面（ProductClassEditType）と
 * 規格なし商品の商品編集画面（ProductClassType）の両方に対応する。
 */

namespace Customize\Form\Extension;

use Eccube\Form\Type\Admin\ProductClassEditType;
use Eccube\Form\Type\Admin\ProductClassType;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;

class ProductClassExtension extends AbstractTypeExtension
{
    /** 欠品（一時的な在庫切れ） */
    public const OUT_OF_STOCK_TYPE_SHORTAGE = 1;

    /** 廃盤（恒久的に販売終了） */
    public const OUT_OF_STOCK_TYPE_DISCONTINUED = 2;

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('out_of_stock_type', ChoiceType::class, [
            'label' => 'admin.product.out_of_stock_type',
            'required' => false,
            'placeholder' => 'admin.product.out_of_stock_type__unset',
            'choices' => [
                'admin.product.out_of_stock_type__shortage' => self::OUT_OF_STOCK_TYPE_SHORTAGE,
                'admin.product.out_of_stock_type__discontinued' => self::OUT_OF_STOCK_TYPE_DISCONTINUED,
            ],
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public static function getExtendedTypes(): iterable
    {
        yield ProductClassEditType::class;
        yield ProductClassType::class;
    }
}
