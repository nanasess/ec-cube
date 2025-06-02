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

use Eccube\Attribute\ForwardOnly;
use Eccube\Tests\EccubeTestCase;

/**
 * ForwardOnly Attribute統合テスト
 */
class ForwardOnlyAttributeTest extends EccubeTestCase
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
     * ForwardOnly Attribute が正しく認識されることをテスト
     */
    public function testForwardOnlyAttributeRecognition()
    {
        $reflection = new \ReflectionClass(TestForwardOnlyAttributeController::class);
        $method = $reflection->getMethod('forwardOnlyAction');
        $attributes = $method->getAttributes(ForwardOnly::class);
        
        $this->assertCount(1, $attributes);
        $attribute = $attributes[0]->newInstance();
        $this->assertInstanceOf(ForwardOnly::class, $attribute);
    }

    /**
     * ForwardOnly Attribute のConfigurationInterface実装テスト
     */
    public function testForwardOnlyAttributeConfiguration()
    {
        $reflection = new \ReflectionClass(TestForwardOnlyAttributeController::class);
        $method = $reflection->getMethod('forwardOnlyAction');
        $attributes = $method->getAttributes(ForwardOnly::class);
        
        $attribute = $attributes[0]->newInstance();
        
        // ConfigurationInterface の実装をテスト
        $this->assertTrue($attribute instanceof \Sensio\Bundle\FrameworkExtraBundle\Configuration\ConfigurationInterface);
        $this->assertTrue(method_exists($attribute, 'getAliasName'));
        $this->assertTrue(method_exists($attribute, 'allowArray'));
        
        $this->assertEquals('forward_only', $attribute->getAliasName());
        $this->assertFalse($attribute->allowArray());
    }

    /**
     * 複数メソッドでのForwardOnly Attribute テスト
     */
    public function testMultipleForwardOnlyAttributes()
    {
        $reflection = new \ReflectionClass(TestMultipleForwardOnlyAttributeController::class);
        
        // 最初のメソッド
        $method1 = $reflection->getMethod('action1');
        $attributes1 = $method1->getAttributes(ForwardOnly::class);
        $this->assertCount(1, $attributes1);
        
        // 2番目のメソッド
        $method2 = $reflection->getMethod('action2');
        $attributes2 = $method2->getAttributes(ForwardOnly::class);
        $this->assertCount(1, $attributes2);
        
        // ForwardOnly がないメソッド
        $method3 = $reflection->getMethod('normalAction');
        $attributes3 = $method3->getAttributes(ForwardOnly::class);
        $this->assertCount(0, $attributes3);
    }

    /**
     * ForwardOnly Attribute のネストしたクラステスト
     */
    public function testNestedClassForwardOnlyAttribute()
    {
        $reflection = new \ReflectionClass(TestNestedForwardOnlyController::class);
        $method = $reflection->getMethod('nestedAction');
        $attributes = $method->getAttributes(ForwardOnly::class);
        
        $this->assertCount(1, $attributes);
        $attribute = $attributes[0]->newInstance();
        $this->assertInstanceOf(ForwardOnly::class, $attribute);
    }
}

// テスト用Controller クラス

class TestForwardOnlyAttributeController
{
    #[ForwardOnly]
    public function forwardOnlyAction()
    {
        return 'forward only action';
    }
    
    public function normalAction()
    {
        return 'normal action';
    }
}

class TestMultipleForwardOnlyAttributeController
{
    #[ForwardOnly]
    public function action1()
    {
        return 'action1';
    }
    
    #[ForwardOnly]
    public function action2()
    {
        return 'action2';
    }
    
    public function normalAction()
    {
        return 'normal action';
    }
}

class TestNestedForwardOnlyController
{
    #[ForwardOnly]
    public function nestedAction()
    {
        return 'nested action';
    }
}
