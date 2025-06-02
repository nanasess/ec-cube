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

use Eccube\Attribute\EntityExtension;
use Eccube\Service\EntityProxyService;
use Eccube\Tests\EccubeTestCase;

/**
 * EntityExtension Attribute統合テスト
 */
class EntityExtensionAttributeTest extends EccubeTestCase
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
     * EntityExtension Attribute が正しく認識されることをテスト
     */
    public function testEntityExtensionAttributeRecognition()
    {
        $reflection = new \ReflectionClass(TestEntityExtensionAttributeTrait::class);
        $attributes = $reflection->getAttributes(EntityExtension::class);
        
        $this->assertCount(1, $attributes);
        $attribute = $attributes[0]->newInstance();
        $this->assertInstanceOf(EntityExtension::class, $attribute);
        $this->assertEquals('Eccube\\Entity\\Product', $attribute->value);
    }

    /**
     * EntityProxyService でAttribute が正しく処理されることをテスト
     */
    public function testEntityProxyServiceProcessesAttributes()
    {
        $entityManager = self::getContainer()->get('doctrine.orm.entity_manager');
        $eccubeConfig = self::getContainer()->get('Eccube\\Common\\EccubeConfig');
        
        $service = new EntityProxyService($entityManager, $eccubeConfig);
        
        // リフレクションを使用してprivateメソッドにアクセス
        $reflectionService = new \ReflectionClass($service);
        $scanTraitsMethod = $reflectionService->getMethod('scanTraits');
        $scanTraitsMethod->setAccessible(true);
        
        // ダミーのトレイトファイルリストを作成
        $traitFiles = [
            [
                TestEntityExtensionAttributeTrait::class => 'dummy/path/TestEntityExtensionAttributeTrait.php'
            ]
        ];
        
        // scanTraits メソッドを呼び出し
        $result = $scanTraitsMethod->invoke($service, $traitFiles);
        
        // 結果の確認
        $this->assertIsArray($result);
        
        // 最初の要素をチェック（Attribute処理の結果）
        if (!empty($result) && !empty($result[0])) {
            $this->assertArrayHasKey('Eccube\\Entity\\Product', $result[0]);
            $this->assertContains(TestEntityExtensionAttributeTrait::class, $result[0]['Eccube\\Entity\\Product']);
        }
    }

    /**
     * 複数のEntityExtension Attribute のテスト
     */
    public function testMultipleEntityExtensionAttributes()
    {
        // 第1のトレイト
        $reflection1 = new \ReflectionClass(TestEntityExtensionAttributeTrait::class);
        $attributes1 = $reflection1->getAttributes(EntityExtension::class);
        $this->assertCount(1, $attributes1);
        
        $attribute1 = $attributes1[0]->newInstance();
        $this->assertEquals('Eccube\\Entity\\Product', $attribute1->value);
        
        // 第2のトレイト
        $reflection2 = new \ReflectionClass(TestCustomerEntityExtensionAttributeTrait::class);
        $attributes2 = $reflection2->getAttributes(EntityExtension::class);
        $this->assertCount(1, $attributes2);
        
        $attribute2 = $attributes2[0]->newInstance();
        $this->assertEquals('Eccube\\Entity\\Customer', $attribute2->value);
    }
}

// テスト用Trait クラス

#[EntityExtension('Eccube\\Entity\\Product')]
trait TestEntityExtensionAttributeTrait
{
    /**
     * 追加のプロパティ
     */
    private $additionalProperty;
    
    public function getAdditionalProperty()
    {
        return $this->additionalProperty;
    }
    
    public function setAdditionalProperty($additionalProperty)
    {
        $this->additionalProperty = $additionalProperty;
    }
}

#[EntityExtension('Eccube\\Entity\\Customer')]
trait TestCustomerEntityExtensionAttributeTrait
{
    /**
     * カスタマー追加プロパティ
     */
    private $customerAdditionalProperty;
    
    public function getCustomerAdditionalProperty()
    {
        return $this->customerAdditionalProperty;
    }
    
    public function setCustomerAdditionalProperty($customerAdditionalProperty)
    {
        $this->customerAdditionalProperty = $customerAdditionalProperty;
    }
}
