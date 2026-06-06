<?php

namespace Customize\Form\Type\Admin;

use Customize\Entity\SaleConfig;
use Eccube\Repository\CategoryRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * セール設定フォームタイプ
 */
class SaleConfigType extends AbstractType
{
    /**
     * @var CategoryRepository
     */
    private $categoryRepository;

    public function __construct(CategoryRepository $categoryRepository)
    {
        $this->categoryRepository = $categoryRepository;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'セール名',
                'constraints' => [
                    new Assert\NotBlank(),
                    new Assert\Length(['max' => 255]),
                ],
            ])
            ->add('discount_type', ChoiceType::class, [
                'label' => '割引タイプ',
                'choices' => [
                    '割引率（%）' => SaleConfig::DISCOUNT_TYPE_RATE,
                    '固定値引き（円）' => SaleConfig::DISCOUNT_TYPE_AMOUNT,
                ],
                'constraints' => [
                    new Assert\NotBlank(),
                ],
            ])
            ->add('discount_value', NumberType::class, [
                'label' => '割引値',
                'constraints' => [
                    new Assert\NotBlank(),
                    new Assert\Positive(),
                ],
            ])
            ->add('start_date', DateTimeType::class, [
                'label' => '開始日時',
                'required' => false,
                'widget' => 'single_text',
                // エンティティの start_date は datetimetz(\DateTime)のため input も datetime に揃える
                'input' => 'datetime',
            ])
            ->add('end_date', DateTimeType::class, [
                'label' => '終了日時',
                'required' => false,
                'widget' => 'single_text',
                // エンティティの end_date は datetimetz(\DateTime)のため input も datetime に揃える
                'input' => 'datetime',
            ])
            ->add('Categories', EntityType::class, [
                'label' => '対象カテゴリ',
                'class' => \Eccube\Entity\Category::class,
                'choice_label' => 'name',
                'multiple' => true,
                'expanded' => true,
                'query_builder' => function (CategoryRepository $er) {
                    return $er->createQueryBuilder('c')
                        ->orderBy('c.sort_no', 'DESC');
                },
                'constraints' => [
                    new Assert\NotBlank(['message' => '対象カテゴリを1つ以上選択してください。']),
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => SaleConfig::class,
        ]);
    }
}
