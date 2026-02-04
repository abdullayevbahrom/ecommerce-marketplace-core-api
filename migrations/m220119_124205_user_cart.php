<?php

use yii\db\Migration;

/**
 * Class m220119_124205_user_cart
 */
class m220119_124205_user_cart extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('user_cart', [
            'id' => $this->primaryKey(),
            'user_id' => $this->integer(),
            'product_id' => $this->integer(),
            'amount' => $this->double(),
            'price' => $this->double(),
            'date' => $this->timestamp()
        ]);

        $this->addForeignKey('user_cart_u_fk', 'user_cart', 'user_id', 'user', 'id', 'CASCADE');
        $this->addForeignKey('user_cart_p_fk', 'user_cart', 'product_id', 'product', 'id', 'CASCADE');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m220119_124205_user_cart cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m220119_124205_user_cart cannot be reverted.\n";

        return false;
    }
    */
}
