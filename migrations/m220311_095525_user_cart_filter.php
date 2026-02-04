<?php

use yii\db\Migration;

/**
 * Class m220311_095525_user_cart_filter
 */
class m220311_095525_user_cart_filter extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('user_cart_filter', [
            'id' => $this->primaryKey(),
            'user_cart_id' => $this->integer(),
            'product_filter_id' => $this->integer()
        ]);

        $this->addForeignKey('user_cart_filter_u_fk', 'user_cart_filter', 'user_cart_id', 'user_cart', 'id', 'CASCADE');
        $this->addForeignKey('user_cart_filter_p_fk', 'user_cart_filter', 'product_filter_id', 'product_filter', 'id', 'CASCADE');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m220311_095525_user_cart_filter cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m220311_095525_user_cart_filter cannot be reverted.\n";

        return false;
    }
    */
}
