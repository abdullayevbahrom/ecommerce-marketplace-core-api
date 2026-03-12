<?php

use yii\db\Migration;

/**
 * Creates product_asl_belgisi table — ASL Belgisi registry entries.
 * Products connect via product.barcode = product_asl_belgisi.gtin (one GTIN → many products).
 *
 * Manual SQL (run in phpMyAdmin or MySQL client):
 *
 * -- UP:
 * CREATE TABLE `product_asl_belgisi` (
 *     `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
 *     `gtin` VARCHAR(14) NOT NULL COMMENT 'GTIN barcode',
 *     `asl_product_id` VARCHAR(64) NULL COMMENT 'Product ID from ASL Belgisi registry',
 *     `product_name_ru` VARCHAR(500) NULL COMMENT 'Product name (RU) from ASL',
 *     `product_name_uz` VARCHAR(500) NULL COMMENT 'Product name (UZ) from ASL',
 *     `inn` VARCHAR(20) NULL COMMENT 'INN of product owner in ASL',
 *     `product_group` VARCHAR(100) NULL COMMENT 'Product group (e.g. alcohol, tobacco)',
 *     `status` VARCHAR(20) NULL COMMENT 'ASL status (PUBLISHED, etc.)',
 *     `checked_at` DATETIME NOT NULL COMMENT 'When the check was performed',
 *     `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 *     PRIMARY KEY (`id`),
 *     UNIQUE INDEX `idx_pab_gtin` (`gtin`),
 *     INDEX `idx_pab_asl_product_id` (`asl_product_id`)
 * ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='ASL Belgisi registry — linked to products via barcode=gtin';
 *
 * -- DOWN:
 * DROP TABLE IF EXISTS `product_asl_belgisi`;
 *
 * -- After running UP, mark as applied:
 * INSERT INTO `migration` (`version`, `apply_time`) VALUES ('m260311_100000_create_product_asl_belgisi_table', UNIX_TIMESTAMP());
 */
class m260311_100000_create_product_asl_belgisi_table extends Migration
{
    public function safeUp()
    {
        $this->createTable('{{%product_asl_belgisi}}', [
            'id' => $this->primaryKey()->unsigned(),
            'gtin' => $this->string(14)->notNull()->comment('GTIN barcode'),
            'asl_product_id' => $this->string(64)->null()->comment('Product ID from ASL Belgisi registry'),
            'product_name_ru' => $this->string(500)->null()->comment('Product name (RU) from ASL'),
            'product_name_uz' => $this->string(500)->null()->comment('Product name (UZ) from ASL'),
            'inn' => $this->string(20)->null()->comment('INN of product owner in ASL'),
            'product_group' => $this->string(100)->null()->comment('Product group (e.g. alcohol, tobacco)'),
            'status' => $this->string(20)->null()->comment('ASL status (PUBLISHED, etc.)'),
            'checked_at' => $this->dateTime()->notNull()->comment('When the check was performed'),
            'created_at' => $this->dateTime()->notNull()->defaultExpression('CURRENT_TIMESTAMP'),
        ], 'ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT="ASL Belgisi registry"');

        $this->createIndex('idx_pab_gtin', '{{%product_asl_belgisi}}', 'gtin', true); // UNIQUE
        $this->createIndex('idx_pab_asl_product_id', '{{%product_asl_belgisi}}', 'asl_product_id');
    }

    public function safeDown()
    {
        $this->dropTable('{{%product_asl_belgisi}}');
    }
}
