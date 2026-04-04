<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * セール価格機能用マイグレーション
 *
 * - dtb_sale_config: セール設定テーブル
 * - dtb_sale_config_category: セール設定×カテゴリ中間テーブル
 * - dtb_order_item に sale_config_id, original_price カラム追加
 */
final class Version20260404000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'セール価格の期間限定価格設定機能用テーブル作成';
    }

    public function up(Schema $schema): void
    {
        // dtb_sale_config テーブル
        if (!$schema->hasTable('dtb_sale_config')) {
            $table = $schema->createTable('dtb_sale_config');
            $table->addColumn('id', 'integer', ['unsigned' => true, 'autoincrement' => true]);
            $table->addColumn('name', 'string', ['length' => 255]);
            $table->addColumn('discount_type', 'smallint');
            $table->addColumn('discount_value', 'decimal', ['precision' => 12, 'scale' => 2]);
            $table->addColumn('start_date', 'datetimetz', ['notnull' => false]);
            $table->addColumn('end_date', 'datetimetz', ['notnull' => false]);
            $table->addColumn('enabled', 'boolean', ['default' => false]);
            $table->addColumn('create_date', 'datetimetz');
            $table->addColumn('update_date', 'datetimetz');
            $table->addColumn('discriminator_type', 'string', ['length' => 255]);
            $table->setPrimaryKey(['id']);
        }

        // dtb_sale_config_category 中間テーブル
        if (!$schema->hasTable('dtb_sale_config_category')) {
            $table = $schema->createTable('dtb_sale_config_category');
            $table->addColumn('sale_config_id', 'integer', ['unsigned' => true]);
            $table->addColumn('category_id', 'integer', ['unsigned' => true]);
            $table->setPrimaryKey(['sale_config_id', 'category_id']);
            $table->addForeignKeyConstraint('dtb_sale_config', ['sale_config_id'], ['id'], ['onDelete' => 'CASCADE']);
            $table->addForeignKeyConstraint('dtb_category', ['category_id'], ['id'], ['onDelete' => 'CASCADE']);
        }

        // dtb_order_item にセール情報カラムを追加
        $orderItemTable = $schema->getTable('dtb_order_item');
        if (!$orderItemTable->hasColumn('sale_config_id')) {
            $orderItemTable->addColumn('sale_config_id', 'integer', ['notnull' => false]);
        }
        if (!$orderItemTable->hasColumn('original_price')) {
            $orderItemTable->addColumn('original_price', 'decimal', ['precision' => 12, 'scale' => 2, 'notnull' => false]);
        }
    }

    public function down(Schema $schema): void
    {
        // dtb_order_item からセール情報カラムを削除
        $orderItemTable = $schema->getTable('dtb_order_item');
        if ($orderItemTable->hasColumn('sale_config_id')) {
            $orderItemTable->dropColumn('sale_config_id');
        }
        if ($orderItemTable->hasColumn('original_price')) {
            $orderItemTable->dropColumn('original_price');
        }

        // 中間テーブル削除
        if ($schema->hasTable('dtb_sale_config_category')) {
            $schema->dropTable('dtb_sale_config_category');
        }

        // dtb_sale_config テーブル削除
        if ($schema->hasTable('dtb_sale_config')) {
            $schema->dropTable('dtb_sale_config');
        }
    }
}
