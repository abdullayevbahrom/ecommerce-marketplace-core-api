<?php

use yii\db\Migration;

/**
 * Class m220119_130125_order_product
 */
class m220119_130125_order_product extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('order_product', [
            'id' => $this->primaryKey(),
            'user_id' => $this->integer(),
            'order_id' => $this->integer(),
            'product_id' => $this->integer(),
            'shop_id' => $this->integer(),
            'amount' => $this->double(),
            'price' => $this->double(),
            'date' => $this->timestamp()
        ]);

        $this->addForeignKey('order_product_o_fk', 'order_product', 'order_id', 'order', 'id', 'CASCADE');
        $this->addForeignKey('order_product_p_fk', 'order_product', 'product_id', 'product', 'id', 'CASCADE');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m220119_130125_order_product cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m220119_130125_order_product cannot be reverted.\n";

        return false;
    }
    */
}
