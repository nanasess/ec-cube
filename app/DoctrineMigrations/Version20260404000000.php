<?php

declare(strict_types=1);

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

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * セット割引テーブル（dtb_cross_product_discount）を作成する。
 *
 * 商品Aがカートにある場合に商品Bに割引を適用するルールを管理する。
 */
final class Version20260404000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'セット割引テーブル dtb_cross_product_discount を作成';
    }

    public function up(Schema $schema): void
    {
        if ($schema->hasTable('dtb_cross_product_discount')) {
            return;
        }

        $table = $schema->createTable('dtb_cross_product_discount');
        $table->addColumn('id', 'integer', [
            'autoincrement' => true,
            'unsigned' => true,
        ]);
        $table->addColumn('trigger_product_id', 'integer', [
            'unsigned' => true,
            'notnull' => true,
        ]);
        $table->addColumn('target_product_id', 'integer', [
            'unsigned' => true,
            'notnull' => true,
        ]);
        $table->addColumn('discount_type', 'smallint', [
            'unsigned' => true,
            'default' => 1,
            'comment' => '1=定額, 2=定率',
        ]);
        $table->addColumn('discount_value', 'decimal', [
            'precision' => 12,
            'scale' => 2,
        ]);
        $table->addColumn('enabled', 'boolean', [
            'default' => true,
        ]);
        $table->addColumn('create_date', 'datetimetz');
        $table->addColumn('update_date', 'datetimetz');

        $table->setPrimaryKey(['id']);

        // 外部キー制約
        $table->addForeignKeyConstraint(
            'dtb_product',
            ['trigger_product_id'],
            ['id'],
            [],
            'fk_cross_product_discount_trigger'
        );
        $table->addForeignKeyConstraint(
            'dtb_product',
            ['target_product_id'],
            ['id'],
            [],
            'fk_cross_product_discount_target'
        );

        // インデックス
        $table->addIndex(['trigger_product_id'], 'idx_cross_product_discount_trigger');
        $table->addIndex(['target_product_id'], 'idx_cross_product_discount_target');
        $table->addIndex(['enabled'], 'idx_cross_product_discount_enabled');
    }

    public function down(Schema $schema): void
    {
        if ($schema->hasTable('dtb_cross_product_discount')) {
            $schema->dropTable('dtb_cross_product_discount');
        }
    }
}
