<?php

namespace Customize\Form\Type\Admin;

use Customize\Entity\CrossProductDiscount;
use Eccube\Entity\Product;
use Eccube\Repository\ProductRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

/**
 * セット割引ルールのフォームタイプ
 */
class CrossProductDiscountType extends AbstractType
{
    /**
     * @var ProductRepository
     */
    private $productRepository;

    public function __construct(ProductRepository $productRepository)
    {
        $this->productRepository = $productRepository;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('triggerProduct', EntityType::class, [
                'class' => Product::class,
                'choice_label' => 'name',
                'label' => 'トリガー商品（カートに入っていると割引発動）',
                'placeholder' => '商品を選択してください',
                'query_builder' => function (ProductRepository $repo) {
                    return $repo->createQueryBuilder('p')
                        ->orderBy('p.id', 'ASC');
                },
                'constraints' => [
                    new Assert\NotBlank(),
                ],
            ])
            ->add('targetProduct', EntityType::class, [
                'class' => Product::class,
                'choice_label' => 'name',
                'label' => 'ターゲット商品（割引対象）',
                'placeholder' => '商品を選択してください',
                'query_builder' => function (ProductRepository $repo) {
                    return $repo->createQueryBuilder('p')
                        ->orderBy('p.id', 'ASC');
                },
                'constraints' => [
                    new Assert\NotBlank(),
                    new Assert\Callback(function ($targetProduct, ExecutionContextInterface $context) {
                        $form = $context->getRoot();
                        $triggerProduct = $form->get('triggerProduct')->getData();
                        // トリガーとターゲットが同一商品でないことを検証
                        if ($triggerProduct !== null && $targetProduct !== null
                            && $triggerProduct->getId() === $targetProduct->getId()) {
                            $context->buildViolation('トリガー商品とターゲット商品に同じ商品は指定できません。')
                                ->addViolation();
                        }
                    }),
                ],
            ])
            ->add('discountType', ChoiceType::class, [
                'label' => '割引種別',
                'choices' => [
                    '定額（円）' => CrossProductDiscount::DISCOUNT_TYPE_FIXED,
                    '定率（%）' => CrossProductDiscount::DISCOUNT_TYPE_PERCENT,
                ],
                'expanded' => false,
                'constraints' => [
                    new Assert\NotBlank(),
                ],
            ])
            ->add('discountValue', NumberType::class, [
                'label' => '割引値',
                'constraints' => [
                    new Assert\NotBlank(),
                    new Assert\Positive(),
                ],
                'attr' => [
                    'placeholder' => '例: 50（定額なら50円引き、定率なら50%引き）',
                ],
            ])
            ->add('enabled', CheckboxType::class, [
                'label' => '有効',
                'required' => false,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => CrossProductDiscount::class,
        ]);
    }
}
