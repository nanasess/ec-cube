<?php

/*
 * 名入れ機能: カートフォーム拡張
 *
 * 商品詳細ページの「カートに入れる」フォームに名入れテキスト入力欄を追加する。
 * 名入れ非対応商品の場合はフィールドを除去する。
 */

namespace Customize\Form\Extension;

use Eccube\Form\Type\AddCartType;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Validator\Constraints as Assert;

class AddCartTypeExtension extends AbstractTypeExtension
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $Product = $options['product'] ?? null;

        // 名入れ対応商品の場合のみフィールドを追加
        if ($Product && method_exists($Product, 'isNaireEnabled') && $Product->isNaireEnabled()) {
            $builder->add('naire_text', TextType::class, [
                'mapped' => false,
                'required' => false,
                'label' => '名入れテキスト',
                'attr' => [
                    'maxlength' => 255,
                    'placeholder' => '名入れテキストを入力してください',
                ],
                'constraints' => [
                    new Assert\Length([
                        'max' => 255,
                        'maxMessage' => '名入れテキストは{{ limit }}文字以内で入力してください。',
                    ]),
                ],
            ]);
        }
    }

    /**
     * {@inheritdoc}
     */
    public static function getExtendedTypes(): iterable
    {
        yield AddCartType::class;
    }
}
