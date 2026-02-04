<?php

use yii\db\Migration;

/**
 * Class m230601_172019_create_pay_keeper_transaction
 */
class m230601_172019_create_pay_keeper_transaction extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('pay_keeper_transaction', [
            'id' => $this->primaryKey(),
            'transaction_id' => $this->string(),
            'order_id' => $this->string(),
            'amount' => $this->float(),
            'status' => $this->tinyInteger()->defaultValue(0),
            'currency' => $this->integer(),
            'request' => $this->text(),
            'response' => $this->text(),
            'date' => $this->integer()
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m230601_172019_create_pay_keeper_transaction cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m230601_172019_create_pay_keeper_transaction cannot be reverted.\n";

        return false;
    }
    */
}
