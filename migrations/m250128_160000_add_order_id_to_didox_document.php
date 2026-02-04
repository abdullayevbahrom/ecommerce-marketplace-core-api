<?php

use yii\db\Migration;

/**
 * Adds order_id column to didox_document table to link documents with orders
 * This enables creation of arbitrary contracts based on existing orders
 */
class m250128_160000_add_order_id_to_didox_document extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        // Add order_id column (nullable) to didox_document table
        $this->addColumn('{{%didox_document}}', 'order_id', $this->integer()->null()->comment('FK to orders table'));
        
        // Add index for better performance
        $this->createIndex('idx-didox_document-order_id', '{{%didox_document}}', 'order_id');
        
        // Add foreign key constraint (assuming you have orders table)
        // Note: Uncomment if orders table exists and has 'id' primary key
        // $this->addForeignKey(
        //     'fk-didox_document-order_id',
        //     '{{%didox_document}}',
        //     'order_id',
        //     '{{%orders}}', // or your actual orders table name
        //     'id',
        //     'SET NULL', // On delete set null to preserve documents
        //     'CASCADE'
        // );
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        // Drop foreign key constraint first (if it was created)
        // $this->dropForeignKey('fk-didox_document-order_id', '{{%didox_document}}');
        
        // Drop index
        $this->dropIndex('idx-didox_document-order_id', '{{%didox_document}}');
        
        // Drop column
        $this->dropColumn('{{%didox_document}}', 'order_id');
    }

    /*
    // Manual SQL for direct database execution:
    
    -- Add order_id column:
    ALTER TABLE `didox_document` ADD COLUMN `order_id` int DEFAULT NULL COMMENT 'FK to orders table';
    CREATE INDEX `idx-didox_document-order_id` ON `didox_document` (`order_id`);
    
    -- To rollback:
    DROP INDEX `idx-didox_document-order_id` ON `didox_document`;
    ALTER TABLE `didox_document` DROP COLUMN `order_id`;
    */
} 