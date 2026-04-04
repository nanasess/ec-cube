<?php

declare(strict_types=1);

/*
 * dtb_product_class に品切れ種別カラム (out_of_stock_type) を追加するマイグレーション
 */

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260404000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'dtb_product_class に out_of_stock_type カラムを追加';
    }

    public function up(Schema $schema): void
    {
        if (!$schema->hasTable('dtb_product_class')) {
            return;
        }

        $table = $schema->getTable('dtb_product_class');
        if ($table->hasColumn('out_of_stock_type')) {
            return;
        }

        $table->addColumn('out_of_stock_type', 'smallint', [
            'notnull' => false,
            'default' => null,
            'comment' => '品切れ種別 (1:欠品, 2:廃盤)',
        ]);
    }

    public function down(Schema $schema): void
    {
        if (!$schema->hasTable('dtb_product_class')) {
            return;
        }

        $table = $schema->getTable('dtb_product_class');
        if ($table->hasColumn('out_of_stock_type')) {
            $table->dropColumn('out_of_stock_type');
        }
    }
}
