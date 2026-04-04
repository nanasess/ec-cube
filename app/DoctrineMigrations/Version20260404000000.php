<?php

declare(strict_types=1);

/*
 * 限定公開リンク テーブル作成マイグレーション
 */

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260404000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '限定公開リンク（dtb_private_link）テーブルを作成';
    }

    public function up(Schema $schema): void
    {
        if ($schema->hasTable('dtb_private_link')) {
            return;
        }

        $table = $schema->createTable('dtb_private_link');
        $table->addColumn('id', 'integer', [
            'autoincrement' => true,
            'unsigned' => true,
        ]);
        $table->addColumn('product_id', 'integer', [
            'unsigned' => true,
        ]);
        $table->addColumn('customer_id', 'integer', [
            'unsigned' => true,
            'notnull' => false,
        ]);
        $table->addColumn('token', 'string', [
            'length' => 64,
        ]);
        $table->addColumn('expires_at', 'datetimetz', [
            'notnull' => false,
        ]);
        $table->addColumn('is_active', 'boolean', [
            'default' => true,
        ]);
        $table->addColumn('create_date', 'datetimetz');
        $table->addColumn('update_date', 'datetimetz');
        $table->addColumn('discriminator_type', 'string', [
            'length' => 255,
        ]);

        $table->setPrimaryKey(['id']);
        $table->addUniqueIndex(['token'], 'dtb_private_link_token_idx');
        $table->addForeignKeyConstraint('dtb_product', ['product_id'], ['id'], [], 'fk_private_link_product');
        $table->addForeignKeyConstraint('dtb_customer', ['customer_id'], ['id'], [], 'fk_private_link_customer');
    }

    public function down(Schema $schema): void
    {
        if ($schema->hasTable('dtb_private_link')) {
            $schema->dropTable('dtb_private_link');
        }
    }
}
