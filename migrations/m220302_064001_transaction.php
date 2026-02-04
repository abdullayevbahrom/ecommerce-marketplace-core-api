<?php

use yii\db\Migration;

/**
 * Class m220302_064001_transaction
 */
class m220302_064001_transaction extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('transaction', [
            'id' => $this->primaryKey(),
            'user_id' => $this->integer(),
            'order_id' => $this->integer(),
            'type_transaction' => $this->string(),
            'type_payment' => $this->string(),
            'amount' => $this->double(),
            'status' => $this->integer()->notNull()->defaultValue(0),
            'date' => $this->timestamp()
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m220302_064001_transaction cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m220302_064001_transaction cannot be reverted.\n";

        return false;
    }
    */
}
