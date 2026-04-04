<?php

/*
 * 限定公開リンク フォームタイプ
 *
 * 管理画面での限定公開リンク新規発行用フォーム。
 */

namespace Customize\Form\Type\Admin;

use Customize\Entity\PrivateLink;
use Eccube\Entity\Customer;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PrivateLinkType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('Customer', EntityType::class, [
                'class' => Customer::class,
                'choice_label' => function (Customer $customer) {
                    return sprintf(
                        '%s (%s)',
                        $customer->getName01() . ' ' . $customer->getName02(),
                        $customer->getEmail()
                    );
                },
                'required' => false,
                'placeholder' => '制限なし（トークンで誰でもアクセス可）',
                'label' => '対象顧客',
                'attr' => [
                    'class' => 'form-control',
                ],
            ])
            ->add('expires_at', DateTimeType::class, [
                'required' => false,
                'widget' => 'single_text',
                'label' => '有効期限',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => '未設定の場合は無期限',
                ],
            ]);
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => PrivateLink::class,
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix(): string
    {
        return 'admin_private_link';
    }
}
