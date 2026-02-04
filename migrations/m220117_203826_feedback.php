<?php

use yii\db\Migration;

/**
 * Class m220117_203826_feedback
 */
class m220117_203826_feedback extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('feedback', [
            'id' => $this->primaryKey(),
            'name' => $this->string(),
            'email' => $this->string(),
            'message' => $this->text(),
            'status' => $this->integer()->notNull()->defaultValue(0),
            'date' => $this->timestamp()
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m220117_203826_feedback cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m220117_203826_feedback cannot be reverted.\n";

        return false;
    }
    */
}
