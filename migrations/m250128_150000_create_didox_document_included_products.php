<?php

use yii\db\Migration;

/*
 * Handles the creation of table `{{%didox_document_included_products}}`.
 * 
 * This migration creates a new table to store product information for DIDOX documents,
 * separating product data from the main invoice table to align with DIDOX API structure.
 * 
 * MANUAL SQL EQUIVALENT:
 * ===================
 * 
 * To perform this migration manually, execute the following SQL commands:
 * 
  CREATE TABLE `didox_document_included_products` (
    `id` int NOT NULL AUTO_INCREMENT,
    `document_id` int NOT NULL COMMENT 'FK to didox_document',
    `ord_no` int DEFAULT '1' COMMENT 'Порядковый номер (OrdNo)',
   `name` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'Наименование товара (Name)',
   `catalog_code` varchar(17) COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'Код ИКПУ (CatalogCode)',
    `catalog_name` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'Название ИКПУ (CatalogName)',
   `marks` text COLLATE utf8mb4_general_ci COMMENT 'Маркировки (Marks)',
    `barcode` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'Штрих-код (Barcode)',
    `package_code` varchar(20) COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'Код упаковки (PackageCode)',
    `package_name` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'Название упаковки (PackageName)',
    `count` decimal(15,3) DEFAULT '1.000' COMMENT 'Количество (Count)',
    `summa` decimal(15,2) DEFAULT NULL COMMENT 'Цена за единицу (Summa)',
    `delivery_sum` decimal(15,2) DEFAULT NULL COMMENT 'Стоимость поставки (DeliverySum)',
    `delivery_sum_with_vat` decimal(15,2) DEFAULT NULL COMMENT 'Стоимость с НДС (DeliverySumWithVat)',
    `vat_rate` decimal(5,2) DEFAULT '12.00' COMMENT 'Ставка НДС (VatRate)',
    `vat_sum` decimal(15,2) DEFAULT NULL COMMENT 'Сумма НДС (VatSum)',
    `without_vat` tinyint(1) DEFAULT '0' COMMENT 'Без НДС (WithoutVat)',
    `excise_rate` decimal(5,2) DEFAULT '0.00' COMMENT 'Ставка акциза (ExciseRate)',
    `excise_sum` decimal(15,2) DEFAULT '0.00' COMMENT 'Сумма акциза (ExciseSum)',
    `without_excise` tinyint(1) DEFAULT '1' COMMENT 'Без акциза (WithoutExcise)',
    `lgota_id` varchar(20) COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'Код льготы (LgotaId)',
    `lgota_type` tinyint DEFAULT NULL COMMENT 'Тип льготы: 1-НДС, 2-налог с оборота (LgotaType)',
    `lgota_name` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'Название льготы (LgotaName)',
    `lgota_vat_sum` decimal(15,2) DEFAULT '0.00' COMMENT 'Льготная сумма НДС (LgotaVatSum)',
    `committent_name` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'Наименование комитента (CommittentName)',
    `committent_tin` varchar(20) COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'ИНН комитента (CommittentTin)',
    `committent_vat_reg_code` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'Рег.код НДС комитента (CommittentVatRegCode)',
    `committent_vat_reg_status` varchar(20) COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'Статус рег.кода НДС комитента (CommittentVatRegStatus)',
    `warehouse_id` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'ID склада (WarehouseId)',
    `origin` int DEFAULT '4' COMMENT 'Происхождение товара (Origin)',
    `measure_id` varchar(20) COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'НЕ ИСПОЛЬЗУЕТСЯ (MeasureId)',
    `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx-didox_document_included_products-document_id` (`document_id`),
    KEY `idx-didox_document_included_products-ord_no` (`ord_no`),
    KEY `idx-didox_document_included_products-catalog_code` (`catalog_code`),
    KEY `idx-didox_document_included_products-package_code` (`package_code`),
    CONSTRAINT `fk-didox_document_included_products-document_id` FOREIGN KEY (`document_id`) REFERENCES `didox_document` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
 * 
 * ROLLBACK SQL:
 * =============
 * DROP TABLE IF EXISTS `didox_document_included_products`;
 */
class m250128_150000_create_didox_document_included_products extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%didox_document_included_products}}', [
            'id' => $this->primaryKey(),
            'document_id' => $this->integer()->notNull()->comment('FK to didox_document'),
            'ord_no' => $this->integer()->defaultValue(1)->comment('Порядковый номер (OrdNo)'),
            
            // Product Information
            'name' => $this->string(255)->comment('Наименование товара (Name)'),
            'catalog_code' => $this->string(17)->comment('Код ИКПУ (CatalogCode)'),
            'catalog_name' => $this->string(255)->comment('Название ИКПУ (CatalogName)'),
            'marks' => $this->text()->comment('Маркировки (Marks)'),
            'barcode' => $this->string(100)->comment('Штрих-код (Barcode)'),
            
            // Package Information
            'package_code' => $this->string(20)->comment('Код упаковки (PackageCode)'),
            'package_name' => $this->string(50)->comment('Название упаковки (PackageName)'),
            
            // Quantity and Pricing
            'count' => $this->decimal(15, 3)->defaultValue(1.000)->comment('Количество (Count)'),
            'summa' => $this->decimal(15, 2)->comment('Цена за единицу (Summa)'),
            'delivery_sum' => $this->decimal(15, 2)->comment('Стоимость поставки (DeliverySum)'),
            'delivery_sum_with_vat' => $this->decimal(15, 2)->comment('Стоимость с НДС (DeliverySumWithVat)'),
            
            // VAT Information
            'vat_rate' => $this->decimal(5, 2)->defaultValue(12.00)->comment('Ставка НДС (VatRate)'),
            'vat_sum' => $this->decimal(15, 2)->comment('Сумма НДС (VatSum)'),
            'without_vat' => $this->boolean()->defaultValue(false)->comment('Без НДС (WithoutVat)'),
            
            // Excise Information
            'excise_rate' => $this->decimal(5, 2)->defaultValue(0.00)->comment('Ставка акциза (ExciseRate)'),
            'excise_sum' => $this->decimal(15, 2)->defaultValue(0.00)->comment('Сумма акциза (ExciseSum)'),
            'without_excise' => $this->boolean()->defaultValue(true)->comment('Без акциза (WithoutExcise)'),
            
            // Lgota (Benefits) Information
            'lgota_id' => $this->string(20)->comment('Код льготы (LgotaId)'),
            'lgota_type' => $this->tinyInteger()->comment('Тип льготы: 1-НДС, 2-налог с оборота (LgotaType)'),
            'lgota_name' => $this->string(255)->comment('Название льготы (LgotaName)'),
            'lgota_vat_sum' => $this->decimal(15, 2)->defaultValue(0.00)->comment('Льготная сумма НДС (LgotaVatSum)'),
            
            // Committent Information (for three-party invoices)
            'committent_name' => $this->string(255)->comment('Наименование комитента (CommittentName)'),
            'committent_tin' => $this->string(20)->comment('ИНН комитента (CommittentTin)'),
            'committent_vat_reg_code' => $this->string(50)->comment('Рег.код НДС комитента (CommittentVatRegCode)'),
            'committent_vat_reg_status' => $this->string(20)->comment('Статус рег.кода НДС комитента (CommittentVatRegStatus)'),
            
            // Other Information
            'warehouse_id' => $this->string(50)->comment('ID склада (WarehouseId)'),
            'origin' => $this->integer()->defaultValue(4)->comment('Происхождение товара (Origin)'),
            'measure_id' => $this->string(20)->comment('НЕ ИСПОЛЬЗУЕТСЯ (MeasureId)'),
            
            'created_at' => $this->timestamp()->defaultExpression('CURRENT_TIMESTAMP'),
            'updated_at' => $this->timestamp()->defaultExpression('CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'),
        ]);

        // Add indexes
        $this->createIndex('idx-didox_document_included_products-document_id', '{{%didox_document_included_products}}', 'document_id');
        $this->createIndex('idx-didox_document_included_products-ord_no', '{{%didox_document_included_products}}', 'ord_no');
        $this->createIndex('idx-didox_document_included_products-catalog_code', '{{%didox_document_included_products}}', 'catalog_code');
        $this->createIndex('idx-didox_document_included_products-package_code', '{{%didox_document_included_products}}', 'package_code');
        
        // Add foreign key constraint
        $this->addForeignKey(
            'fk-didox_document_included_products-document_id',
            '{{%didox_document_included_products}}',
            'document_id',
            '{{%didox_document}}',
            'id',
            'CASCADE',
            'CASCADE'
        );
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        // Drop foreign key constraint
        $this->dropForeignKey('fk-didox_document_included_products-document_id', '{{%didox_document_included_products}}');
        
        // Drop table
        $this->dropTable('{{%didox_document_included_products}}');
    }
} 