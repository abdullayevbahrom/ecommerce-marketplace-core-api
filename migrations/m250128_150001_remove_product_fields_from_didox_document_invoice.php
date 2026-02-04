<?php

use yii\db\Migration;

/*
 * Handles removing product-related columns from table `{{%didox_document_invoice}}`.
 * These fields are being moved to `{{%didox_document_included_products}}` table.
 * 
 * This migration removes individual product fields from the invoice table and replaces them
 * with computed totals that will be calculated from all products in the new products table.
 * 
 * MANUAL SQL EQUIVALENT:
 * ===================
 * 
 * To perform this migration manually, execute the following SQL commands:
 * 
  -- Remove product-related columns
  ALTER TABLE `didox_document_invoice` DROP COLUMN `ikpu_code`;
  ALTER TABLE `didox_document_invoice` DROP COLUMN `ikpu_name`;
  ALTER TABLE `didox_document_invoice` DROP COLUMN `package_code`;
  ALTER TABLE `didox_document_invoice` DROP COLUMN `package_name`;
  ALTER TABLE `didox_document_invoice` DROP COLUMN `product_quantity`;
  ALTER TABLE `didox_document_invoice` DROP COLUMN `product_price`;
  ALTER TABLE `didox_document_invoice` DROP COLUMN `product_marks`;
  ALTER TABLE `didox_document_invoice` DROP COLUMN `product_barcode`;
  ALTER TABLE `didox_document_invoice` DROP COLUMN `product_origin`;
  ALTER TABLE `didox_document_invoice` DROP COLUMN `vat_rate`;
  ALTER TABLE `didox_document_invoice` DROP COLUMN `total_sum`;
  ALTER TABLE `didox_document_invoice` DROP COLUMN `total_vat_sum`;
  ALTER TABLE `didox_document_invoice` DROP COLUMN `total_delivery_sum_with_vat`;
  ALTER TABLE `didox_document_invoice` DROP COLUMN `without_vat`;
  ALTER TABLE `didox_document_invoice` DROP COLUMN `without_excise`;
  ALTER TABLE `didox_document_invoice` DROP COLUMN `excise_rate`;
  ALTER TABLE `didox_document_invoice` DROP COLUMN `excise_sum`;
  ALTER TABLE `didox_document_invoice` DROP COLUMN `lgota_id`;
  ALTER TABLE `didox_document_invoice` DROP COLUMN `lgota_type`;
  ALTER TABLE `didox_document_invoice` DROP COLUMN `lgota_name`;
  ALTER TABLE `didox_document_invoice` DROP COLUMN `lgota_vat_sum`;
  ALTER TABLE `didox_document_invoice` DROP COLUMN `warehouse_id`;
  ALTER TABLE `didox_document_invoice` DROP COLUMN `committent_name`;
  ALTER TABLE `didox_document_invoice` DROP COLUMN `committent_tin`;
  ALTER TABLE `didox_document_invoice` DROP COLUMN `committent_vat_reg_code`;
  ALTER TABLE `didox_document_invoice` DROP COLUMN `committent_vat_reg_status`;
  
  -- Add computed totals columns for invoice level (sum of all products)
  ALTER TABLE `didox_document_invoice` ADD COLUMN `total_sum` decimal(15,2) DEFAULT NULL COMMENT 'Общая стоимость поставки всех товаров';
  ALTER TABLE `didox_document_invoice` ADD COLUMN `total_vat_sum` decimal(15,2) DEFAULT NULL COMMENT 'Общая сумма НДС всех товаров';
  ALTER TABLE `didox_document_invoice` ADD COLUMN `total_delivery_sum_with_vat` decimal(15,2) DEFAULT NULL COMMENT 'Общая стоимость с НДС всех товаров';
  
  ROLLBACK SQL:
 * =============
 * -- Remove the new computed totals columns
 * ALTER TABLE `didox_document_invoice` DROP COLUMN `total_sum`;
 * ALTER TABLE `didox_document_invoice` DROP COLUMN `total_vat_sum`;
 * ALTER TABLE `didox_document_invoice` DROP COLUMN `total_delivery_sum_with_vat`;
 * 
 * -- Re-add all the removed product-related columns (with original structure)
 * ALTER TABLE `didox_document_invoice` ADD COLUMN `ikpu_code` varchar(17) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Код ИКПУ (CatalogCode)';
 * ALTER TABLE `didox_document_invoice` ADD COLUMN `ikpu_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Название ИКПУ (CatalogName)';
 * ALTER TABLE `didox_document_invoice` ADD COLUMN `package_code` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Код упаковки (PackageCode)';
 * ALTER TABLE `didox_document_invoice` ADD COLUMN `package_name` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Название упаковки (PackageName)';
 * ALTER TABLE `didox_document_invoice` ADD COLUMN `product_quantity` decimal(15,3) DEFAULT '1.000' COMMENT 'Количество (Count)';
 * ALTER TABLE `didox_document_invoice` ADD COLUMN `product_price` decimal(15,2) DEFAULT NULL COMMENT 'Цена за единицу (Summa)';
 * ALTER TABLE `didox_document_invoice` ADD COLUMN `product_marks` text COLLATE utf8mb4_unicode_ci COMMENT 'Маркировки (Marks)';
 * ALTER TABLE `didox_document_invoice` ADD COLUMN `product_barcode` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Штрих-код (Barcode)';
 * ALTER TABLE `didox_document_invoice` ADD COLUMN `product_origin` int DEFAULT '4' COMMENT 'Происхождение товара (Origin)';
 * ALTER TABLE `didox_document_invoice` ADD COLUMN `vat_rate` decimal(5,2) DEFAULT '12.00' COMMENT 'Ставка НДС (VatRate)';
 * ALTER TABLE `didox_document_invoice` ADD COLUMN `total_sum` decimal(15,2) DEFAULT NULL COMMENT 'Стоимость поставки (DeliverySum)';
 * ALTER TABLE `didox_document_invoice` ADD COLUMN `total_vat_sum` decimal(15,2) DEFAULT NULL COMMENT 'Сумма НДС (VatSum)';
 * ALTER TABLE `didox_document_invoice` ADD COLUMN `total_delivery_sum_with_vat` decimal(15,2) DEFAULT NULL COMMENT 'Стоимость с НДС (DeliverySumWithVat)';
 * ALTER TABLE `didox_document_invoice` ADD COLUMN `without_vat` tinyint(1) DEFAULT '0' COMMENT 'Без НДС (WithoutVat)';
 * ALTER TABLE `didox_document_invoice` ADD COLUMN `without_excise` tinyint(1) DEFAULT '1' COMMENT 'Без акциза (WithoutExcise)';
 * ALTER TABLE `didox_document_invoice` ADD COLUMN `excise_rate` decimal(5,2) DEFAULT '0.00' COMMENT 'Ставка акциза (ExciseRate)';
 * ALTER TABLE `didox_document_invoice` ADD COLUMN `excise_sum` decimal(15,2) DEFAULT '0.00' COMMENT 'Сумма акциза (ExciseSum)';
 * ALTER TABLE `didox_document_invoice` ADD COLUMN `lgota_id` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Код льготы (LgotaId)';
 * ALTER TABLE `didox_document_invoice` ADD COLUMN `lgota_type` tinyint DEFAULT NULL COMMENT 'Тип льготы: 1-НДС, 2-налог с оборота (LgotaType)';
 * ALTER TABLE `didox_document_invoice` ADD COLUMN `lgota_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Название льготы (LgotaName)';
 * ALTER TABLE `didox_document_invoice` ADD COLUMN `lgota_vat_sum` decimal(15,2) DEFAULT '0.00' COMMENT 'Льготная сумма НДС (LgotaVatSum)';
 * ALTER TABLE `didox_document_invoice` ADD COLUMN `warehouse_id` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'ID склада (WarehouseId)';
 * ALTER TABLE `didox_document_invoice` ADD COLUMN `committent_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Наименование комитента (CommittentName)';
 * ALTER TABLE `didox_document_invoice` ADD COLUMN `committent_tin` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'ИНН комитента (CommittentTin)';
 * ALTER TABLE `didox_document_invoice` ADD COLUMN `committent_vat_reg_code` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Рег.код НДС комитента (CommittentVatRegCode)';
 * ALTER TABLE `didox_document_invoice` ADD COLUMN `committent_vat_reg_status` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Статус рег.кода НДС комитента (CommittentVatRegStatus)';
 */
class m250128_150001_remove_product_fields_from_didox_document_invoice extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        // Remove product-related columns that are now in didox_document_included_products
        $this->dropColumn('{{%didox_document_invoice}}', 'ikpu_code');
        $this->dropColumn('{{%didox_document_invoice}}', 'ikpu_name');
        $this->dropColumn('{{%didox_document_invoice}}', 'package_code');
        $this->dropColumn('{{%didox_document_invoice}}', 'package_name');
        $this->dropColumn('{{%didox_document_invoice}}', 'product_quantity');
        $this->dropColumn('{{%didox_document_invoice}}', 'product_price');
        $this->dropColumn('{{%didox_document_invoice}}', 'product_marks');
        $this->dropColumn('{{%didox_document_invoice}}', 'product_barcode');
        $this->dropColumn('{{%didox_document_invoice}}', 'product_origin');
        $this->dropColumn('{{%didox_document_invoice}}', 'vat_rate');
        $this->dropColumn('{{%didox_document_invoice}}', 'total_sum');
        $this->dropColumn('{{%didox_document_invoice}}', 'total_vat_sum');
        $this->dropColumn('{{%didox_document_invoice}}', 'total_delivery_sum_with_vat');
        $this->dropColumn('{{%didox_document_invoice}}', 'without_vat');
        $this->dropColumn('{{%didox_document_invoice}}', 'without_excise');
        $this->dropColumn('{{%didox_document_invoice}}', 'excise_rate');
        $this->dropColumn('{{%didox_document_invoice}}', 'excise_sum');
        $this->dropColumn('{{%didox_document_invoice}}', 'lgota_id');
        $this->dropColumn('{{%didox_document_invoice}}', 'lgota_type');
        $this->dropColumn('{{%didox_document_invoice}}', 'lgota_name');
        $this->dropColumn('{{%didox_document_invoice}}', 'lgota_vat_sum');
        $this->dropColumn('{{%didox_document_invoice}}', 'warehouse_id');
        $this->dropColumn('{{%didox_document_invoice}}', 'committent_name');
        $this->dropColumn('{{%didox_document_invoice}}', 'committent_tin');
        $this->dropColumn('{{%didox_document_invoice}}', 'committent_vat_reg_code');
        $this->dropColumn('{{%didox_document_invoice}}', 'committent_vat_reg_status');
        
        // Add computed totals columns for invoice level (sum of all products)
        $this->addColumn('{{%didox_document_invoice}}', 'total_sum', $this->decimal(15, 2)->comment('Общая стоимость поставки всех товаров'));
        $this->addColumn('{{%didox_document_invoice}}', 'total_vat_sum', $this->decimal(15, 2)->comment('Общая сумма НДС всех товаров'));
        $this->addColumn('{{%didox_document_invoice}}', 'total_delivery_sum_with_vat', $this->decimal(15, 2)->comment('Общая стоимость с НДС всех товаров'));
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        // Remove the new computed totals columns
        $this->dropColumn('{{%didox_document_invoice}}', 'total_sum');
        $this->dropColumn('{{%didox_document_invoice}}', 'total_vat_sum');
        $this->dropColumn('{{%didox_document_invoice}}', 'total_delivery_sum_with_vat');
        
        // Re-add all the removed product-related columns
        $this->addColumn('{{%didox_document_invoice}}', 'ikpu_code', $this->string(17)->comment('Код ИКПУ (CatalogCode)'));
        $this->addColumn('{{%didox_document_invoice}}', 'ikpu_name', $this->string(255)->comment('Название ИКПУ (CatalogName)'));
        $this->addColumn('{{%didox_document_invoice}}', 'package_code', $this->string(20)->comment('Код упаковки (PackageCode)'));
        $this->addColumn('{{%didox_document_invoice}}', 'package_name', $this->string(50)->comment('Название упаковки (PackageName)'));
        $this->addColumn('{{%didox_document_invoice}}', 'product_quantity', $this->decimal(15, 3)->defaultValue(1.000)->comment('Количество (Count)'));
        $this->addColumn('{{%didox_document_invoice}}', 'product_price', $this->decimal(15, 2)->comment('Цена за единицу (Summa)'));
        $this->addColumn('{{%didox_document_invoice}}', 'product_marks', $this->text()->comment('Маркировки (Marks)'));
        $this->addColumn('{{%didox_document_invoice}}', 'product_barcode', $this->string(100)->comment('Штрих-код (Barcode)'));
        $this->addColumn('{{%didox_document_invoice}}', 'product_origin', $this->integer()->defaultValue(4)->comment('Происхождение товара (Origin)'));
        $this->addColumn('{{%didox_document_invoice}}', 'vat_rate', $this->decimal(5, 2)->defaultValue(12.00)->comment('Ставка НДС (VatRate)'));
        $this->addColumn('{{%didox_document_invoice}}', 'total_sum', $this->decimal(15, 2)->comment('Стоимость поставки (DeliverySum)'));
        $this->addColumn('{{%didox_document_invoice}}', 'total_vat_sum', $this->decimal(15, 2)->comment('Сумма НДС (VatSum)'));
        $this->addColumn('{{%didox_document_invoice}}', 'total_delivery_sum_with_vat', $this->decimal(15, 2)->comment('Стоимость с НДС (DeliverySumWithVat)'));
        $this->addColumn('{{%didox_document_invoice}}', 'without_vat', $this->boolean()->defaultValue(false)->comment('Без НДС (WithoutVat)'));
        $this->addColumn('{{%didox_document_invoice}}', 'without_excise', $this->boolean()->defaultValue(true)->comment('Без акциза (WithoutExcise)'));
        $this->addColumn('{{%didox_document_invoice}}', 'excise_rate', $this->decimal(5, 2)->defaultValue(0.00)->comment('Ставка акциза (ExciseRate)'));
        $this->addColumn('{{%didox_document_invoice}}', 'excise_sum', $this->decimal(15, 2)->defaultValue(0.00)->comment('Сумма акциза (ExciseSum)'));
        $this->addColumn('{{%didox_document_invoice}}', 'lgota_id', $this->string(20)->comment('Код льготы (LgotaId)'));
        $this->addColumn('{{%didox_document_invoice}}', 'lgota_type', $this->tinyInteger()->comment('Тип льготы: 1-НДС, 2-налог с оборота (LgotaType)'));
        $this->addColumn('{{%didox_document_invoice}}', 'lgota_name', $this->string(255)->comment('Название льготы (LgotaName)'));
        $this->addColumn('{{%didox_document_invoice}}', 'lgota_vat_sum', $this->decimal(15, 2)->defaultValue(0.00)->comment('Льготная сумма НДС (LgotaVatSum)'));
        $this->addColumn('{{%didox_document_invoice}}', 'warehouse_id', $this->string(50)->comment('ID склада (WarehouseId)'));
        $this->addColumn('{{%didox_document_invoice}}', 'committent_name', $this->string(255)->comment('Наименование комитента (CommittentName)'));
        $this->addColumn('{{%didox_document_invoice}}', 'committent_tin', $this->string(20)->comment('ИНН комитента (CommittentTin)'));
        $this->addColumn('{{%didox_document_invoice}}', 'committent_vat_reg_code', $this->string(50)->comment('Рег.код НДС комитента (CommittentVatRegCode)'));
        $this->addColumn('{{%didox_document_invoice}}', 'committent_vat_reg_status', $this->string(20)->comment('Статус рег.кода НДС комитента (CommittentVatRegStatus)'));
    }
} 