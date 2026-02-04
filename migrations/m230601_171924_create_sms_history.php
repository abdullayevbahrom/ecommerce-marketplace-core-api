<?php

use yii\db\Migration;

/**
 * Class m230601_171924_create_sms_history
 */
class m230601_171924_create_sms_history extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {

        $this->createTable('sms_history', [
            'id' => $this->primaryKey(),
            'text' => $this->text(),
            'phone' => $this->string(),
            'status' => $this->tinyInteger()->defaultValue(0),
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
        echo "m230601_171924_create_sms_history cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m230601_171924_create_sms_history cannot be reverted.\n";

        return false;
    }
    */
}
