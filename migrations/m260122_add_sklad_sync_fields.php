<?php

use yii\db\Migration;

/**
 * Class m260122_add_sklad_sync_fields
 */
class m260122_add_sklad_sync_fields extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        // Add sklad_product_id and sync_status to product table
        $this->addColumn('product', 'sklad_product_id', $this->integer()->defaultValue(null));
        $this->addColumn('product', 'sync_status', $this->tinyInteger()->defaultValue(0)->comment('0: Pending, 1: Synced, 2: Failed'));
        
        $this->createIndex('idx-product-sklad_product_id', 'product', 'sklad_product_id');
        $this->createIndex('idx-product-sync_status', 'product', 'sync_status');

        // Add sklad_product_id to order_product table
        $this->addColumn('order_product', 'sklad_product_id', $this->integer()->defaultValue(null));
        $this->createIndex('idx-order_product-sklad_product_id', 'order_product', 'sklad_product_id');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropIndex('idx-product-sklad_product_id', 'product');
        $this->dropIndex('idx-product-sync_status', 'product');
        $this->dropColumn('product', 'sklad_product_id');
        $this->dropColumn('product', 'sync_status');

        $this->dropIndex('idx-order_product-sklad_product_id', 'order_product');
        $this->dropColumn('order_product', 'sklad_product_id');
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {
        // SQL Commands for manual execution:
        
        // ALTER TABLE `product` ADD COLUMN `sklad_product_id` INT(11) NULL DEFAULT NULL;
        // ALTER TABLE `product` ADD COLUMN `sync_status` TINYINT(3) DEFAULT 0 COMMENT '0: Pending, 1: Synced, 2: Failed';
        // CREATE INDEX `idx-product-sklad_product_id` ON `product` (`sklad_product_id`);
        // CREATE INDEX `idx-product-sync_status` ON `product` (`sync_status`);
        
        // ALTER TABLE `order_product` ADD COLUMN `sklad_product_id` INT(11) NULL DEFAULT NULL;
        // CREATE INDEX `idx-order_product-sklad_product_id` ON `order_product` (`sklad_product_id`);
    }

    public function down()
    {
        echo "m260122_add_sklad_sync_fields cannot be reverted.\n";

        return false;
    }
    */
}

