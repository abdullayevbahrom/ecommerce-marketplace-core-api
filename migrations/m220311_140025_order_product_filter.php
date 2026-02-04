<?php

use yii\db\Migration;

/**
 * Class m220311_140025_order_product_filter
 */
class m220311_140025_order_product_filter extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('order_product_filter', [
            'id' => $this->primaryKey(),
            'order_product_id' => $this->integer(),
            'product_filter_id' => $this->integer()
        ]);

        $this->addForeignKey('order_product_filter_o_fk', 'order_product_filter', 'order_product_id', 'order_product', 'id', 'CASCADE');
        $this->addForeignKey('order_product_filter_p_fk', 'order_product_filter', 'product_filter_id', 'product_filter', 'id', 'CASCADE');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m220311_140025_order_product_filter cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m220311_140025_order_product_filter cannot be reverted.\n";

        return false;
    }
    */
}
