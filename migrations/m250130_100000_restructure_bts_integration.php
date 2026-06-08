<?php

use yii\db\Migration;

/**
 * Class m250130_100000_restructure_bts_integration
 * 
 * This migration restructures BTS integration by:
 * 1. Moving bts_id from order to order_product level
 * 2. Adding delivery_cost to order table for total BTS delivery cost
 * 3. Adding optional address field to order_product for individual shipping addresses
 * 4. Adding stock_id to order_product to track which stock the product comes from
 */
class m250130_100000_restructure_bts_integration extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        // Add new fields to order_product table
        $this->addColumn('order_product', 'bts_id', $this->string()->null()->comment('BTS tracking ID for this product group'));
        $this->addColumn('order_product', 'bts_status', $this->string()->null()->comment('BTS status for this product group'));
        $this->addColumn('order_product', 'bts_status_info', $this->text()->null()->comment('BTS status information for this product group'));
        $this->addColumn('order_product', 'bts_price', $this->decimal(30,2)->null()->comment('BTS delivery cost for this product group'));
        $this->addColumn('order_product', 'address', $this->text()->null()->comment('Custom delivery address for this product (nullable)'));
        $this->addColumn('order_product', 'stock_id', $this->integer()->null()->comment('Stock ID where this product is located'));
        
        // Add delivery_cost to order table for total delivery cost
        $this->addColumn('order', 'delivery_cost', $this->decimal(10,2)->null()->comment('Total delivery cost for the order'));
        
        // Create indexes for better performance
        $this->createIndex('idx-order_product-bts_id', 'order_product', 'bts_id');
        $this->createIndex('idx-order_product-stock_id', 'order_product', 'stock_id');
        $this->createIndex('idx-order-delivery_cost', 'order', 'delivery_cost');
        
        // Add foreign key for stock_id if stock table exists
        try {
            $this->addForeignKey('fk-order_product-stock_id', 'order_product', 'stock_id', 'stock', 'id', 'SET NULL');
        } catch (\Exception $e) {
            // Stock table might not exist yet, ignore this error
            echo "Warning: Could not create foreign key for stock_id. Stock table might not exist.\n";
        }
        
        // Migrate existing data from order.bts_* fields to order_product
        // $this->execute("
        //     UPDATE order_product op 
        //     INNER JOIN `order` o ON op.order_id = o.id 
        //     SET 
        //         op.bts_id = o.bts_id,
        //         op.bts_status = o.bts_status,
        //         op.bts_status_info = o.bts_status_info,
        //         op.bts_price = o.bts_price
        //     WHERE o.bts_id IS NOT NULL
        // ");
        
        // // Remove old BTS fields from order table
        // $this->dropColumn('order', 'bts_id');
        // $this->dropColumn('order', 'bts_status');
        // $this->dropColumn('order', 'bts_status_info');
        // $this->dropColumn('order', 'bts_price');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        // Re-add BTS fields to order table
        // $this->addColumn('order', 'bts_id', $this->string()->null()->comment('BTS tracking ID'));
        // $this->addColumn('order', 'bts_status', $this->string()->null()->comment('BTS status'));
        // $this->addColumn('order', 'bts_status_info', $this->text()->null()->comment('BTS status information'));
        // $this->addColumn('order', 'bts_price', $this->decimal(10,2)->null()->comment('BTS delivery cost'));
        
        // // Migrate data back from order_product to order (take first non-null value)
        // $this->execute("
        //     UPDATE `order` o 
        //     INNER JOIN (
        //         SELECT order_id, 
        //                MIN(bts_id) as bts_id,
        //                MIN(bts_status) as bts_status,
        //                MIN(bts_status_info) as bts_status_info,
        //                SUM(bts_price) as bts_price
        //         FROM order_product 
        //         WHERE bts_id IS NOT NULL 
        //         GROUP BY order_id
        //     ) op ON o.id = op.order_id
        //     SET 
        //         o.bts_id = op.bts_id,
        //         o.bts_status = op.bts_status,
        //         o.bts_status_info = op.bts_status_info,
        //         o.bts_price = op.bts_price
        // ");
        
        // Drop foreign key first
        try {
            $this->dropForeignKey('fk-order_product-stock_id', 'order_product');
        } catch (\Exception $e) {
            // Foreign key might not exist, ignore
        }
        
        // Drop indexes
        $this->dropIndex('idx-order_product-bts_id', 'order_product');
        $this->dropIndex('idx-order_product-stock_id', 'order_product');
        $this->dropIndex('idx-order-delivery_cost', 'order');
        
        // Drop new fields from order_product
        $this->dropColumn('order_product', 'bts_id');
        $this->dropColumn('order_product', 'bts_status');
        $this->dropColumn('order_product', 'bts_status_info');
        $this->dropColumn('order_product', 'bts_price');
        $this->dropColumn('order_product', 'address');
        $this->dropColumn('order_product', 'stock_id');
        
        // Drop delivery_cost from order
        $this->dropColumn('order', 'delivery_cost');
    }

    /*
    // Manual SQL execution (if Yii2 migration fails):
    
    -- Up migration:
    ALTER TABLE `order_product` 
    ADD COLUMN `bts_id` VARCHAR(255) DEFAULT NULL COMMENT 'BTS tracking ID for this product group',
    ADD COLUMN `bts_status` VARCHAR(255) DEFAULT NULL COMMENT 'BTS status for this product group',
    ADD COLUMN `bts_status_info` TEXT DEFAULT NULL COMMENT 'BTS status information for this product group',
    ADD COLUMN `bts_price` DECIMAL(10,2) DEFAULT NULL COMMENT 'BTS delivery cost for this product group',
    ADD COLUMN `address` TEXT DEFAULT NULL COMMENT 'Custom delivery address for this product (nullable)',
    ADD COLUMN `stock_id` INT DEFAULT NULL COMMENT 'Stock ID where this product is located';
    
    ALTER TABLE `order` 
    ADD COLUMN `delivery_cost` DECIMAL(10,2) DEFAULT NULL COMMENT 'Total delivery cost for the order';
    
    CREATE INDEX `idx-order_product-bts_id` ON `order_product` (`bts_id`);
    CREATE INDEX `idx-order_product-stock_id` ON `order_product` (`stock_id`);
    CREATE INDEX `idx-order-delivery_cost` ON `order` (`delivery_cost`);
    
    -- Add foreign key (if stock table exists)
    ALTER TABLE `order_product` ADD CONSTRAINT `fk-order_product-stock_id` 
    FOREIGN KEY (`stock_id`) REFERENCES `stock` (`id`) ON DELETE SET NULL;
    
    -- Migrate existing data
    UPDATE order_product op 
    INNER JOIN `order` o ON op.order_id = o.id 
    SET 
        op.bts_id = o.bts_id,
        op.bts_status = o.bts_status,
        op.bts_status_info = o.bts_status_info,
        op.bts_price = o.bts_price
    WHERE o.bts_id IS NOT NULL;
    
    -- Remove old fields
    ALTER TABLE `order` 
    DROP COLUMN `bts_id`,
    DROP COLUMN `bts_status`,
    DROP COLUMN `bts_status_info`,
    DROP COLUMN `bts_price`;
    
    -- Down migration:
    ALTER TABLE `order` 
    ADD COLUMN `bts_id` VARCHAR(255) DEFAULT NULL COMMENT 'BTS tracking ID',
    ADD COLUMN `bts_status` VARCHAR(255) DEFAULT NULL COMMENT 'BTS status',
    ADD COLUMN `bts_status_info` TEXT DEFAULT NULL COMMENT 'BTS status information',
    ADD COLUMN `bts_price` DECIMAL(10,2) DEFAULT NULL COMMENT 'BTS delivery cost';
    
    -- Migrate data back
    UPDATE `order` o 
    INNER JOIN (
        SELECT order_id, 
               MIN(bts_id) as bts_id,
               MIN(bts_status) as bts_status,
               MIN(bts_status_info) as bts_status_info,
               SUM(bts_price) as bts_price
        FROM order_product 
        WHERE bts_id IS NOT NULL 
        GROUP BY order_id
    ) op ON o.id = op.order_id
    SET 
        o.bts_id = op.bts_id,
        o.bts_status = op.bts_status,
        o.bts_status_info = op.bts_status_info,
        o.bts_price = op.bts_price;
    
    -- Drop constraints and columns
    ALTER TABLE `order_product` DROP FOREIGN KEY `fk-order_product-stock_id`;
    DROP INDEX `idx-order_product-bts_id` ON `order_product`;
    DROP INDEX `idx-order_product-stock_id` ON `order_product`;
    DROP INDEX `idx-order-delivery_cost` ON `order`;
    
    ALTER TABLE `order_product` 
    DROP COLUMN `bts_id`,
    DROP COLUMN `bts_status`,
    DROP COLUMN `bts_status_info`,
    DROP COLUMN `bts_price`,
    DROP COLUMN `address`,
    DROP COLUMN `stock_id`;
    
    ALTER TABLE `order` DROP COLUMN `delivery_cost`;
    */
}
