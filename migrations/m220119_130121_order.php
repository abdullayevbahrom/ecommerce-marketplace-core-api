<?php

use yii\db\Migration;

/**
 * Class m220119_130121_order
 */
class m220119_130121_order extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('order', [
            'id' => $this->primaryKey(),
            'user_id' => $this->integer(),
            'payment_id' => $this->integer(),
            'delivery_id' => $this->integer(),
            'logist_id' => $this->integer(),
            'price' => $this->double(),
            'amount' => $this->double(),
            'receiver' => $this->integer()->notNull()->defaultValue(0),
            'name' => $this->string(),
            'lastname' => $this->string(),
            'email' => $this->string(),
            'phone' => $this->text(),
            'address' => $this->text(),
            'comment' => $this->text(),
            'status' => $this->integer()->notNull()->defaultValue(0),
            'status_payment' => $this->integer()->notNull()->defaultValue(0),
            'date' => $this->timestamp()
        ]);

        $this->createIndex('id', 'order', 'id', true);
        $this->addForeignKey('order_user_fk', 'order', 'user_id', 'user', 'id', 'CASCADE');
        $this->addForeignKey('order_payment_fk', 'order', 'payment_id', 'category', 'id', 'CASCADE');
        $this->addForeignKey('order_delivery_fk', 'order', 'delivery_id', 'delivery', 'id', 'CASCADE');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m220119_130121_order cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m220119_130121_order cannot be reverted.\n";

        return false;
    }
    */
}
