<?php

use yii\db\Migration;

/**
 * Creates table `didox_document_arbitrary` for arbitrary contract documents
 * This table stores data needed for DIDOX "Произвольный документ" document type
 */
class m250128_160001_create_didox_document_arbitrary extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%didox_document_arbitrary}}', [
            'id' => $this->primaryKey(),
            'document_id' => $this->integer()->notNull()->comment('FK to didox_document'),
            
            // Document Information
            'document_no' => $this->string(100)->comment('Номер документа'),
            'document_date' => $this->date()->comment('Дата документа'),
            'document_name' => $this->string(255)->comment('Наименование документа'),
            
            // Contract Information
            'contract_no' => $this->string(100)->comment('Номер договора'),
            'contract_date' => $this->date()->comment('Дата договора'),
            
            // Seller Information (Поставщик)
            'seller_tin' => $this->string(20)->notNull()->comment('ИНН поставщика'),
            'seller_name' => $this->string(255)->notNull()->comment('Наименование поставщика'),
            'seller_address' => $this->text()->notNull()->comment('Адрес поставщика'),
            'seller_branch_code' => $this->string(50)->comment('Код филиала поставщика'),
            'seller_branch_name' => $this->string(255)->comment('Наименование филиала поставщика'),
            
            // Buyer Information (Покупатель)
            'buyer_tin' => $this->string(20)->notNull()->comment('ИНН/ПИНФЛ покупателя'),
            'buyer_name' => $this->string(255)->notNull()->comment('Наименование покупателя'),
            'buyer_address' => $this->text()->notNull()->comment('Адрес покупателя'),
            'buyer_branch_code' => $this->string(50)->comment('Код филиала покупателя'),
            'buyer_branch_name' => $this->string(255)->comment('Наименование филиала покупателя'),
            
            // PDF Document
            'pdf_file_content' => $this->longText()->comment('PDF content in base64 format'),
            'pdf_file_name' => $this->string(255)->comment('Original PDF filename'),
            'pdf_file_size' => $this->integer()->comment('PDF file size in bytes'),
            
            // Timestamps
            'created_at' => $this->dateTime(),
            'updated_at' => $this->dateTime(),
        ]);

        // Add indexes
        $this->createIndex('idx-didox_document_arbitrary-document_id', '{{%didox_document_arbitrary}}', 'document_id');
        $this->createIndex('idx-didox_document_arbitrary-document_no', '{{%didox_document_arbitrary}}', 'document_no');
        $this->createIndex('idx-didox_document_arbitrary-contract_no', '{{%didox_document_arbitrary}}', 'contract_no');
        $this->createIndex('idx-didox_document_arbitrary-seller_tin', '{{%didox_document_arbitrary}}', 'seller_tin');
        $this->createIndex('idx-didox_document_arbitrary-buyer_tin', '{{%didox_document_arbitrary}}', 'buyer_tin');
        
        // Add foreign key constraint
        $this->addForeignKey(
            'fk-didox_document_arbitrary-document_id',
            '{{%didox_document_arbitrary}}',
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
        // Drop foreign key
        $this->dropForeignKey('fk-didox_document_arbitrary-document_id', '{{%didox_document_arbitrary}}');
        
        // Drop table
        $this->dropTable('{{%didox_document_arbitrary}}');
    }

    /*
    // Manual SQL for direct database execution:
    
    CREATE TABLE `didox_document_arbitrary` (
      `id` int NOT NULL AUTO_INCREMENT,
      `document_id` int NOT NULL COMMENT 'FK to didox_document',
      `document_no` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'Номер документа',
      `document_date` date DEFAULT NULL COMMENT 'Дата документа',
      `document_name` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'Наименование документа',
      `contract_no` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'Номер договора',
      `contract_date` date DEFAULT NULL COMMENT 'Дата договора',
      `seller_tin` varchar(20) COLLATE utf8mb4_general_ci NOT NULL COMMENT 'ИНН поставщика',
      `seller_name` varchar(255) COLLATE utf8mb4_general_ci NOT NULL COMMENT 'Наименование поставщика',
      `seller_address` text COLLATE utf8mb4_general_ci NOT NULL COMMENT 'Адрес поставщика',
      `seller_branch_code` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'Код филиала поставщика',
      `seller_branch_name` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'Наименование филиала поставщика',
      `buyer_tin` varchar(20) COLLATE utf8mb4_general_ci NOT NULL COMMENT 'ИНН/ПИНФЛ покупателя',
      `buyer_name` varchar(255) COLLATE utf8mb4_general_ci NOT NULL COMMENT 'Наименование покупателя',
      `buyer_address` text COLLATE utf8mb4_general_ci NOT NULL COMMENT 'Адрес покупателя',
      `buyer_branch_code` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'Код филиала покупателя',
      `buyer_branch_name` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'Наименование филиала покупателя',
      `pdf_file_content` longtext COLLATE utf8mb4_general_ci COMMENT 'PDF content in base64 format',
      `pdf_file_name` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'Original PDF filename',
      `pdf_file_size` int DEFAULT NULL COMMENT 'PDF file size in bytes',
      `created_at` datetime DEFAULT NULL,
      `updated_at` datetime DEFAULT NULL,
      PRIMARY KEY (`id`),
      KEY `idx-didox_document_arbitrary-document_id` (`document_id`),
      KEY `idx-didox_document_arbitrary-document_no` (`document_no`),
      KEY `idx-didox_document_arbitrary-contract_no` (`contract_no`),
      KEY `idx-didox_document_arbitrary-seller_tin` (`seller_tin`),
      KEY `idx-didox_document_arbitrary-buyer_tin` (`buyer_tin`),
      CONSTRAINT `fk-didox_document_arbitrary-document_id` FOREIGN KEY (`document_id`) REFERENCES `didox_document` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
    
    -- To rollback:
    DROP TABLE `didox_document_arbitrary`;
    */
} 