<?php

use yii\db\Migration;

/**
 * Class m220110_093302_filter
 */
class m220110_093302_filter extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('filter', [
            'id' => $this->primaryKey(),
            'parent_id' => $this->integer()->notNull()->defaultValue(0),
            'category_id' => $this->integer(),
            'sub_category_id' => $this->integer(),
            'category_tree' => $this->string(),
            'type' => $this->string(),
            'name_ru' => $this->string(),
            'name_uz' => $this->string(),
            'name_en' => $this->string(),
            'value_ru' => $this->string(),
            'value_uz' => $this->string(),
            'value_en' => $this->string(),
            'status' => $this->integer()->notNull()->defaultValue(0),
            'date' => $this->timestamp()
        ]);

        $this->addForeignKey('filter_category_fk', 'filter', 'category_id', 'category', 'id', 'CASCADE');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m220110_093302_filter cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m220110_093302_filter cannot be reverted.\n";

        return false;
    }
    */
}
