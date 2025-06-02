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

use Eccube\Attribute\CartFlow;
use Eccube\Attribute\EntityExtension;
use Eccube\Attribute\FormAppend;
use Eccube\Attribute\ForwardOnly;
use Eccube\Attribute\OrderFlow;
use Eccube\Attribute\ShoppingFlow;
use Eccube\Tests\EccubeTestCase;

/**
 * PHP 8 Attribute機能のテスト
 */
class AttributeTest extends EccubeTestCase
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
     * CartFlow Attribute のテスト
     */
    public function testCartFlowAttribute()
    {
        $reflection = new \ReflectionClass(TestCartFlowProcessor::class);
        $attributes = $reflection->getAttributes(CartFlow::class);
        
        $this->assertCount(1, $attributes);
        $attribute = $attributes[0]->newInstance();
        $this->assertInstanceOf(CartFlow::class, $attribute);
    }

    /**
     * OrderFlow Attribute のテスト
     */
    public function testOrderFlowAttribute()
    {
        $reflection = new \ReflectionClass(TestOrderFlowProcessor::class);
        $attributes = $reflection->getAttributes(OrderFlow::class);
        
        $this->assertCount(1, $attributes);
        $attribute = $attributes[0]->newInstance();
        $this->assertInstanceOf(OrderFlow::class, $attribute);
    }

    /**
     * ShoppingFlow Attribute のテスト
     */
    public function testShoppingFlowAttribute()
    {
        $reflection = new \ReflectionClass(TestShoppingFlowProcessor::class);
        $attributes = $reflection->getAttributes(ShoppingFlow::class);
        
        $this->assertCount(1, $attributes);
        $attribute = $attributes[0]->newInstance();
        $this->assertInstanceOf(ShoppingFlow::class, $attribute);
    }

    /**
     * EntityExtension Attribute のテスト
     */
    public function testEntityExtensionAttribute()
    {
        $reflection = new \ReflectionClass(TestEntityExtensionTrait::class);
        $attributes = $reflection->getAttributes(EntityExtension::class);
        
        $this->assertCount(1, $attributes);
        $attribute = $attributes[0]->newInstance();
        $this->assertInstanceOf(EntityExtension::class, $attribute);
        $this->assertEquals('Eccube\\Entity\\Product', $attribute->value);
    }

    /**
     * FormAppend Attribute のテスト
     */
    public function testFormAppendAttribute()
    {
        $reflection = new \ReflectionClass(TestFormAppendEntity::class);
        $property = $reflection->getProperty('testField');
        $attributes = $property->getAttributes(FormAppend::class);
        
        $this->assertCount(1, $attributes);
        $attribute = $attributes[0]->newInstance();
        $this->assertInstanceOf(FormAppend::class, $attribute);
        $this->assertEquals('Symfony\\Component\\Form\\Extension\\Core\\Type\\TextType', $attribute->type);
        $this->assertEquals(['label' => 'テストフィールド'], $attribute->options);
    }

    /**
     * ForwardOnly Attribute のテスト
     */
    public function testForwardOnlyAttribute()
    {
        $reflection = new \ReflectionClass(TestForwardOnlyController::class);
        $method = $reflection->getMethod('testAction');
        $attributes = $method->getAttributes(ForwardOnly::class);
        
        $this->assertCount(1, $attributes);
        $attribute = $attributes[0]->newInstance();
        $this->assertInstanceOf(ForwardOnly::class, $attribute);
    }
}

// テスト用クラス

#[CartFlow]
class TestCartFlowProcessor
{
    // テスト用のクラス
}

#[OrderFlow]
class TestOrderFlowProcessor
{
    // テスト用のクラス
}

#[ShoppingFlow]
class TestShoppingFlowProcessor
{
    // テスト用のクラス
}

#[EntityExtension('Eccube\\Entity\\Product')]
class TestEntityExtensionTrait
{
    // テスト用のトレイト
}

class TestFormAppendEntity
{
    #[FormAppend(type: 'Symfony\\Component\\Form\\Extension\\Core\\Type\\TextType', options: ['label' => 'テストフィールド'])]
    private $testField;
}

class TestForwardOnlyController
{
    #[ForwardOnly]
    public function testAction()
    {
        // テスト用のメソッド
    }
}
