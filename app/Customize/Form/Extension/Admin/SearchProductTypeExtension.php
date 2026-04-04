<?php

/*
 * 管理画面の商品検索フォームに在庫数量の範囲指定フィールドを追加する FormExtension
 */

namespace Customize\Form\Extension\Admin;

use Eccube\Form\Type\Admin\SearchProductType;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints as Assert;

class SearchProductTypeExtension extends AbstractTypeExtension
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        // 在庫数量の下限
        $builder->add('stock_quantity_min', IntegerType::class, [
            'label' => '在庫数量(下限)',
            'required' => false,
            'constraints' => [
                new Assert\Type(['type' => 'integer', 'message' => '数値を入力してください。']),
                new Assert\GreaterThanOrEqual(['value' => 0, 'message' => '0以上の値を入力してください。']),
            ],
            'attr' => [
                'placeholder' => '下限',
            ],
        ]);

        // 在庫数量の上限
        $builder->add('stock_quantity_max', IntegerType::class, [
            'label' => '在庫数量(上限)',
            'required' => false,
            'constraints' => [
                new Assert\Type(['type' => 'integer', 'message' => '数値を入力してください。']),
                new Assert\GreaterThanOrEqual(['value' => 0, 'message' => '0以上の値を入力してください。']),
            ],
            'attr' => [
                'placeholder' => '上限',
            ],
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public static function getExtendedTypes(): iterable
    {
        yield SearchProductType::class;
    }
}
