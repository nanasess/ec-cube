<?php

namespace Customize\Form\Extension;

use Eccube\Form\Type\Shopping\OrderType;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * 注文フォームに紹介クーポンコード入力欄を追加するフォーム拡張.
 */
class OrderTypeReferralCouponExtension extends AbstractTypeExtension
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        if ($options['skip_add_form']) {
            return;
        }

        $builder->add('referral_coupon_code', TextType::class, [
            'required' => false,
            'mapped' => false,
            'attr' => [
                'placeholder' => '紹介クーポンコードを入力',
                'maxlength' => 12,
            ],
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public static function getExtendedTypes(): iterable
    {
        yield OrderType::class;
    }
}
