<?php

/*
 * 発送日基準 売上集計フォームタイプ
 */

namespace Customize\Form\Type\Admin;

use Eccube\Entity\Master\OrderStatus;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class SalesReportType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $today = new \DateTime('today');
        $monthAgo = (new \DateTime('today'))->modify('-1 month');

        $builder
            // 日付種別（出荷日 / お届け予定日）
            ->add('date_type', ChoiceType::class, [
                'label' => '日付種別',
                'choices' => [
                    '出荷日' => 'shipping_date',
                    'お届け予定日' => 'shipping_delivery_date',
                ],
                'expanded' => false,
                'multiple' => false,
                'data' => 'shipping_date',
            ])
            // 集計開始日
            ->add('date_start', DateType::class, [
                'label' => '開始日',
                'widget' => 'single_text',
                'format' => 'yyyy-MM-dd',
                'html5' => true,
                'data' => $monthAgo,
                'constraints' => [
                    new Assert\NotBlank(),
                ],
            ])
            // 集計終了日
            ->add('date_end', DateType::class, [
                'label' => '終了日',
                'widget' => 'single_text',
                'format' => 'yyyy-MM-dd',
                'html5' => true,
                'data' => $today,
                'constraints' => [
                    new Assert\NotBlank(),
                ],
            ])
            // 集計単位（日別/月別/年別）
            ->add('unit', ChoiceType::class, [
                'label' => '集計単位',
                'choices' => [
                    '日別' => 'daily',
                    '月別' => 'monthly',
                    '年別' => 'yearly',
                ],
                'expanded' => false,
                'multiple' => false,
                'data' => 'daily',
            ])
            // 受注ステータス（複数選択）
            ->add('order_statuses', EntityType::class, [
                'label' => '受注ステータス',
                'class' => OrderStatus::class,
                'choice_label' => 'name',
                'multiple' => true,
                'expanded' => true,
                'required' => false,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'csrf_protection' => true,
        ]);
    }
}
