<?php

use yii\db\Migration;

/**
 * Class m221228_082222_category_filter
 */
class m221228_082222_category_filter extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('category_filter', [
            'id' => $this->primaryKey(),
            'category_id' => $this->integer(),
            'filter_id' => $this->integer(),
            'value_id' => $this->integer(),
            'value_ru' => $this->string(),
            'value_en' => $this->string(),
            'value_uz' => $this->string()
        ]);

        $this->addForeignKey('category_filter_category_fk', 'category_filter', 'category_id', 'category', 'id', 'CASCADE');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m221228_082222_category_filter cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m221228_082222_category_filter cannot be reverted.\n";

        return false;
    }
    */
}
