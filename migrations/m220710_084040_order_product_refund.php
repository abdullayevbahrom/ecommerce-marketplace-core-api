<?php

use yii\db\Migration;

/**
 * Class m220710_084040_order_product_refund
 */
class m220710_084040_order_product_refund extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('order_product_refund', [
            'id' => $this->primaryKey(),
            'order_product_id' => $this->integer(),
            'message' => $this->text(),
            'date' => $this->timestamp()
        ]);

        $this->addForeignKey('order_product_refund_o_fk', 'order_product_refund', 'order_product_id', 'order_product', 'id', 'CASCADE');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m220710_084040_order_product_refund cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m220710_084040_order_product_refund cannot be reverted.\n";

        return false;
    }
    */
}
