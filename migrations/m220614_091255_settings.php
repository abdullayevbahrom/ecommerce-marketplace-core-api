<?php

use yii\db\Migration;

/**
 * Class m220614_091255_settings
 */
class m220614_091255_settings extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('settings', [
            'id' => $this->primaryKey(),
            'type' => $this->string(),
            'content' => $this->string(),
            'main' => $this->integer()->notNull()->defaultValue(0),
            'date' => $this->timestamp()
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m220614_091255_settings cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m220614_091255_settings cannot be reverted.\n";

        return false;
    }
    */
}
