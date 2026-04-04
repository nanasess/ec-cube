<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * ��介クーポン機能のテーブルを作成するマイグレーション.
 */
final class Version20260404000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '紹介��ーポン機能: dtb_referral_coupon, dtb_referral_config テーブルの作成';
    }

    public function up(Schema $schema): void
    {
        // dtb_referral_config テーブル
        if (!$schema->hasTable('dtb_referral_config')) {
            $configTable = $schema->createTable('dtb_referral_config');
            $configTable->addColumn('id', 'integer', ['unsigned' => true, 'autoincrement' => true]);
            $configTable->addColumn('discount_amount', 'integer', ['default' => 500]);
            $configTable->addColumn('referrer_point', 'integer', ['default' => 500]);
            $configTable->addColumn('expiry_days', 'integer', ['default' => 30]);
            $configTable->addColumn('max_coupons_per_customer', 'integer', ['default' => 10]);
            $configTable->addColumn('enabled', 'boolean', ['default' => true]);
            $configTable->setPrimaryKey(['id']);
        }

        // dtb_referral_coupon テーブル
        if (!$schema->hasTable('dtb_referral_coupon')) {
            $couponTable = $schema->createTable('dtb_referral_coupon');
            $couponTable->addColumn('id', 'integer', ['unsigned' => true, 'autoincrement' => true]);
            $couponTable->addColumn('coupon_code', 'string', ['length' => 12]);
            $couponTable->addColumn('referrer_id', 'integer', ['unsigned' => true]);
            $couponTable->addColumn('referee_id', 'integer', ['unsigned' => true, 'notnull' => false]);
            $couponTable->addColumn('referee_order_id', 'integer', ['unsigned' => true, 'notnull' => false]);
            $couponTable->addColumn('discount_amount', 'integer');
            $couponTable->addColumn('referrer_point', 'integer');
            $couponTable->addColumn('referrer_point_granted', 'boolean', ['default' => false]);
            $couponTable->addColumn('status', 'smallint', ['default' => 0]);
            $couponTable->addColumn('expires_at', 'datetimetz', ['notnull' => false]);
            $couponTable->addColumn('used_at', 'datetimetz', ['notnull' => false]);
            $couponTable->addColumn('create_date', 'datetimetz');
            $couponTable->addColumn('update_date', 'datetimetz');
            $couponTable->setPrimaryKey(['id']);
            $couponTable->addUniqueIndex(['coupon_code'], 'dtb_referral_coupon_code_idx');
            $couponTable->addIndex(['referrer_id'], 'dtb_referral_coupon_referrer_idx');
            $couponTable->addIndex(['status'], 'dtb_referral_coupon_status_idx');
            $couponTable->addForeignKeyConstraint('dtb_customer', ['referrer_id'], ['id']);
            $couponTable->addForeignKeyConstraint('dtb_customer', ['referee_id'], ['id']);
            $couponTable->addForeignKeyConstraint('dtb_order', ['referee_order_id'], ['id']);
        }

        // デフォルト設定レコードを挿入
        $this->addSql("INSERT INTO dtb_referral_config (id, discount_amount, referrer_point, expiry_days, max_coupons_per_customer, enabled) VALUES (1, 500, 500, 30, 10, true)");
    }

    public function down(Schema $schema): void
    {
        if ($schema->hasTable('dtb_referral_coupon')) {
            $schema->dropTable('dtb_referral_coupon');
        }
        if ($schema->hasTable('dtb_referral_config')) {
            $schema->dropTable('dtb_referral_config');
        }
    }
}
