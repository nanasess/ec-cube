<?php

declare(strict_types=1);

/*
 * 名入れ機能: マイグレーション
 *
 * dtb_product に naire_enabled カラムを追加し、
 * dtb_naire_info テーブルを新規作成する。
 */

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260404000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '名入れ機能: dtb_product に naire_enabled カラム追加、dtb_naire_info テーブル作成';
    }

    public function up(Schema $schema): void
    {
        // dtb_product に naire_enabled カラムを追加
        if ($schema->hasTable('dtb_product')) {
            $table = $schema->getTable('dtb_product');
            if (!$table->hasColumn('naire_enabled')) {
                $table->addColumn('naire_enabled', 'boolean', [
                    'notnull' => false,
                    'default' => false,
                ]);
            }
        }

        // dtb_naire_info テーブルを作成
        if (!$schema->hasTable('dtb_naire_info')) {
            $table = $schema->createTable('dtb_naire_info');
            $table->addColumn('id', 'integer', [
                'autoincrement' => true,
                'unsigned' => true,
            ]);
            $table->addColumn('order_item_id', 'integer', [
                'unsigned' => true,
                'notnull' => true,
            ]);
            $table->addColumn('naire_text', 'string', [
                'length' => 255,
                'notnull' => true,
            ]);
            $table->addColumn('create_date', 'datetimetz', [
                'notnull' => true,
            ]);
            $table->addColumn('update_date', 'datetimetz', [
                'notnull' => true,
            ]);
            $table->setPrimaryKey(['id']);
            $table->addForeignKeyConstraint('dtb_order_item', ['order_item_id'], ['id'], [], 'FK_naire_info_order_item');
            $table->addUniqueIndex(['order_item_id'], 'UNIQ_naire_info_order_item_id');
        }
    }

    public function down(Schema $schema): void
    {
        // dtb_naire_info テーブルを削除
        if ($schema->hasTable('dtb_naire_info')) {
            $schema->dropTable('dtb_naire_info');
        }

        // dtb_product から naire_enabled カラムを削除
        if ($schema->hasTable('dtb_product')) {
            $table = $schema->getTable('dtb_product');
            if ($table->hasColumn('naire_enabled')) {
                $table->dropColumn('naire_enabled');
            }
        }
    }
}
