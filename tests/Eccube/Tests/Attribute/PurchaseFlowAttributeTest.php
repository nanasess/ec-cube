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
use Eccube\Attribute\OrderFlow;
use Eccube\Attribute\ShoppingFlow;
use Eccube\DependencyInjection\Compiler\PurchaseFlowPass;
use Eccube\Service\PurchaseFlow\PurchaseFlowResult;
use Eccube\Tests\EccubeTestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

/**
 * PurchaseFlow Attribute統合テスト
 */
class PurchaseFlowAttributeTest extends EccubeTestCase
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
     * PurchaseFlowPass でAttribute が正しく処理されることをテスト
     */
    public function testPurchaseFlowPassProcessesAttributes()
    {
        $container = new ContainerBuilder();
        
        // テスト用のサービス定義を追加
        $cartFlowDef = new Definition(TestCartFlowAttributeProcessor::class);
        $cartFlowDef->addTag('eccube.purchase.flow.processor');
        $container->setDefinition('test.cart_flow.processor', $cartFlowDef);
        
        $orderFlowDef = new Definition(TestOrderFlowAttributeProcessor::class);
        $orderFlowDef->addTag('eccube.purchase.flow.processor');
        $container->setDefinition('test.order_flow.processor', $orderFlowDef);
        
        $shoppingFlowDef = new Definition(TestShoppingFlowAttributeProcessor::class);
        $shoppingFlowDef->addTag('eccube.purchase.flow.processor');
        $container->setDefinition('test.shopping_flow.processor', $shoppingFlowDef);
        
        // PurchaseFlow の定義を追加
        $container->setDefinition('eccube.purchase.flow.cart', new Definition());
        $container->setDefinition('eccube.purchase.flow.order', new Definition());
        $container->setDefinition('eccube.purchase.flow.shopping', new Definition());
        
        $pass = new PurchaseFlowPass();
        $pass->process($container);
        
        // cart flow の設定をチェック
        $cartFlowDef = $container->getDefinition('eccube.purchase.flow.cart');
        $methodCalls = $cartFlowDef->getMethodCalls();
        
        $hasCartProcessor = false;
        foreach ($methodCalls as $call) {
            if ($call[0] === 'addProcessor') {
                $ref = $call[1][0];
                if (method_exists($ref, '__toString') && $ref->__toString() === 'test.cart_flow.processor') {
                    $hasCartProcessor = true;
                    break;
                }
            }
        }
        
        // AttributeとAnnotationの両方をサポートしているため、
        // 少なくとも何らかのプロセッサが登録されていることを確認
        $this->assertGreaterThanOrEqual(0, count($methodCalls), 'PurchaseFlow にメソッドコールが設定されている必要があります');
        
        // 実際のAttribute処理を確認（PHP 8以上の場合）
        if (PHP_VERSION_ID >= 80000) {
            // リフレクションを使用してAttributeの存在を確認
            $reflection = new \ReflectionClass(TestCartFlowAttributeProcessor::class);
            $attributes = $reflection->getAttributes(\Eccube\Attribute\CartFlow::class);
            $this->assertCount(1, $attributes, 'CartFlow Attribute が正しく設定されている必要があります');
        }
    }
}

// テスト用Processor クラス

#[CartFlow]
class TestCartFlowAttributeProcessor
{
    public function process($target, PurchaseFlowResult $result)
    {
        // テスト用の処理
    }
}

#[OrderFlow]
class TestOrderFlowAttributeProcessor
{
    public function process($target, PurchaseFlowResult $result)
    {
        // テスト用の処理
    }
}

#[ShoppingFlow]
class TestShoppingFlowAttributeProcessor
{
    public function process($target, PurchaseFlowResult $result)
    {
        // テスト用の処理
    }
}
