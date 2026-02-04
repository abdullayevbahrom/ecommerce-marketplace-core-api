<?php

use yii\db\Migration;

/**
 * Class m220609_054336_logist
 */
class m220609_054336_logist extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('logist', [
            'id' => $this->primaryKey(),
            'name_ru' => $this->string(),
            'name_uz' => $this->string(),
            'name_en' => $this->string(),
            'description_ru' => $this->string(),
            'description_uz' => $this->string(),
            'description_en' => $this->string(),
            'contact_user' => $this->string(),
            'contact_phone' => $this->string(),
            'sort' => $this->integer()->notNull()->defaultValue(0),
            'status' => $this->integer()->notNull()->defaultValue(1),
            'date' => $this->timestamp()
        ]);

        $this->createIndex('id', 'logist', 'id', true);
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m220609_054336_logist cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m220609_054336_logist cannot be reverted.\n";

        return false;
    }
    */
}
