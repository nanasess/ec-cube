<?php

declare(strict_types=1);

/*
 * 名入れ機能: マイグレーション
 *
 * dtb_product に naire_enabled カラムを追加し、
 * dtb_order_item に naire_text カラムを追加する。
 */

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260404000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '名入れ機能: dtb_product に naire_enabled カラム、dtb_order_item に naire_text カラムを追加';
    }

    public function up(Schema $schema): void
    {
        // dtb_product に naire_enabled カラムを追加（名入れ対応商品フラグ）
        if ($schema->hasTable('dtb_product')) {
            $table = $schema->getTable('dtb_product');
            if (!$table->hasColumn('naire_enabled')) {
                $table->addColumn('naire_enabled', 'boolean', [
                    'notnull' => false,
                    'default' => false,
                ]);
            }
        }

        // dtb_order_item に naire_text カラムを追加（受注明細ごとの名入れテキスト）
        if ($schema->hasTable('dtb_order_item')) {
            $table = $schema->getTable('dtb_order_item');
            if (!$table->hasColumn('naire_text')) {
                $table->addColumn('naire_text', 'string', [
                    'length' => 255,
                    'notnull' => false,
                ]);
            }
        }
    }

    public function down(Schema $schema): void
    {
        // dtb_order_item から naire_text カラムを削除
        if ($schema->hasTable('dtb_order_item')) {
            $table = $schema->getTable('dtb_order_item');
            if ($table->hasColumn('naire_text')) {
                $table->dropColumn('naire_text');
            }
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
