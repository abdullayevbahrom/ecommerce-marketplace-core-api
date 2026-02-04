<?php

use yii\db\Migration;

/**
 * Class m220629_043815_color
 */
class m220629_043815_color extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('color', [
            'id' => $this->primaryKey(),
            'name_ru' => $this->string(),
            'name_en' => $this->string(),
            'name_uz' => $this->string(),
            'color' => $this->string(),
            'date' => $this->timestamp()
        ]);

        $this->createIndex('id', 'color', 'id', true);
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m220629_043815_color cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m220629_043815_color cannot be reverted.\n";

        return false;
    }
    */
}
