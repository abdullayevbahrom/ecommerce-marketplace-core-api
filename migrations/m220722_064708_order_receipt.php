<?php

use yii\db\Migration;

/**
 * Class m220722_064708_order_receipt
 */
class m220722_064708_order_receipt extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('order_receipt', [
            'id' => $this->primaryKey(),
            'order_id' => $this->integer(),
            'receipt_id' => $this->string(),
            'status' => $this->integer(),
            'date' => $this->timestamp()
        ]);

        $this->addforeignKey('order_receipt_o_fk', 'order_receipt', 'order_id', 'order', 'id', 'CASCADE');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m220722_064708_order_receipt cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m220722_064708_order_receipt cannot be reverted.\n";

        return false;
    }
    */
}
