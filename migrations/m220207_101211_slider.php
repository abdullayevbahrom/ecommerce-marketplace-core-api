<?php

use yii\db\Migration;

/**
 * Class m220207_101211_slider
 */
class m220207_101211_slider extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('slider', [
            'id' => $this->primaryKey(),
            'type' => $this->string(),
            'name_ru' => $this->string(),
            'name_uz' => $this->string(),
            'name_en' => $this->string(),
            'content_ru' => $this->text(),
            'content_uz' => $this->text(),
            'content_en' => $this->text(),
            'link' => $this->string(),
            'status' => $this->integer()->notNull()->defaultValue(1),
            'sort' => $this->integer()->notNull()->defaultValue(0),
            'date' => $this->timestamp()
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m220207_101211_slider cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m220207_101211_slider cannot be reverted.\n";

        return false;
    }
    */
}
