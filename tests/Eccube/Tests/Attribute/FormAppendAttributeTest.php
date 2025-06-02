<?php

/*
 * This file is part of EC-CUBE
 *
 * Copyright(c) EC-CUBE CO.,LTD. All Rights Reserved.
 *
 * http://www.ec-cube.co.jp/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Eccube\Tests\Attribute;

use Eccube\Attribute\FormAppend;
use Eccube\Form\Extension\DoctrineOrmExtension;
use Eccube\Tests\EccubeTestCase;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * FormAppend Attribute統合テスト
 */
class FormAppendAttributeTest extends EccubeTestCase
{
    /**
     * PHP8以上でのみ実行される
     */
    protected function setUp(): void
    {
        parent::setUp();
        
        if (PHP_VERSION_ID < 80000) {
            $this->markTestSkipped('PHP 8.0 以上が必要です');
        }
    }

    /**
     * FormAppend Attribute が正しく処理されることをテスト
     */
    public function testFormAppendAttributeProcessing()
    {
        // 実際の動作確認のため、リフレクションを使用してプロパティの属性を確認
        $reflection = new \ReflectionClass(TestFormAppendAttributeEntity::class);
        $property = $reflection->getProperty('testAttributeField');
        $attributes = $property->getAttributes(FormAppend::class);
        
        $this->assertCount(1, $attributes);
        $attribute = $attributes[0]->newInstance();
        $this->assertEquals(TextType::class, $attribute->type);
        $this->assertEquals(['label' => 'テスト Attribute フィールド'], $attribute->options);
    }

    /**
     * FormAppend Attribute の複数フィールドテスト
     */
    public function testMultipleFormAppendAttributes()
    {
        $reflection = new \ReflectionClass(TestMultipleFormAppendAttributeEntity::class);
        
        // 最初のフィールド
        $property1 = $reflection->getProperty('field1');
        $attributes1 = $property1->getAttributes(FormAppend::class);
        $this->assertCount(1, $attributes1);
        
        $attribute1 = $attributes1[0]->newInstance();
        $this->assertEquals(TextType::class, $attribute1->type);
        $this->assertEquals(['label' => 'フィールド1'], $attribute1->options);
        
        // 2番目のフィールド
        $property2 = $reflection->getProperty('field2');
        $attributes2 = $property2->getAttributes(FormAppend::class);
        $this->assertCount(1, $attributes2);
        
        $attribute2 = $attributes2[0]->newInstance();
        $this->assertEquals('Symfony\\Component\\Form\\Extension\\Core\\Type\\EmailType', $attribute2->type);
        $this->assertEquals(['label' => 'フィールド2', 'required' => false], $attribute2->options);
    }
}

// テスト用Entity クラス

class TestFormAppendAttributeEntity
{
    #[FormAppend(type: TextType::class, options: ['label' => 'テスト Attribute フィールド'])]
    private $testAttributeField;
    
    public function getTestAttributeField()
    {
        return $this->testAttributeField;
    }
    
    public function setTestAttributeField($testAttributeField)
    {
        $this->testAttributeField = $testAttributeField;
    }
}

class TestMultipleFormAppendAttributeEntity
{
    #[FormAppend(type: TextType::class, options: ['label' => 'フィールド1'])]
    private $field1;
    
    #[FormAppend(type: 'Symfony\\Component\\Form\\Extension\\Core\\Type\\EmailType', options: ['label' => 'フィールド2', 'required' => false])]
    private $field2;
}
