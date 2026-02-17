<?php

use yii\db\Migration;

class m221228_082222_category_filter extends Migration
{
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

    public function safeDown()
    {
        $this->dropTable('category_filter');
    }
}
