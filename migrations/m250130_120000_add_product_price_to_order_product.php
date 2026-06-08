<?php

use yii\db\Migration;

/**
 * Class m250130_120000_add_product_price_to_order_product
 */
class m250130_120000_add_product_price_to_order_product extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        // Add product_price field to store total price (price * amount)
        $this->addColumn('order_product', 'product_price', $this->decimal(30, 2)->null()->comment('Total price for this product (unit_price * amount)'));

        // Update existing records to set product_price = price (assuming price was already total)
        $this->execute('UPDATE order_product SET product_price = price WHERE price IS NOT NULL');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropColumn('order_product', 'product_price');
    }

    /*
    // Manual SQL for adjustments if needed:
    ALTER TABLE `order_product` 
    ADD COLUMN `product_price` DECIMAL(15,2) DEFAULT NULL COMMENT 'Total price for this product (unit_price * amount)';

    UPDATE order_product SET product_price = price WHERE price IS NOT NULL;

    // To rollback:
    ALTER TABLE `order_product` 
    DROP COLUMN `product_price`;
    */
}
