<?php

use yii\db\Migration;

class m190809_064443_category extends Migration
{
    public function safeUp()
    {
        $this->createTable('category', [
            'id' => $this->primaryKey(),
            'parent_id' => $this->integer()->notNull()->defaultValue(0),
            'type' => $this->string(),
            'name_mini' => $this->string(),
            'name_ru' => $this->string(),
            'name_uz' => $this->string(),
            'name_en' => $this->string(),
            'description_ru' => $this->text(),
            'description_uz' => $this->text(),
            'description_en' => $this->text(),
            'option_ru' => $this->text(),
            'option_uz' => $this->text(),
            'option_en' => $this->text(),
            'sort' => $this->integer()->notNull()->defaultValue(0),
            'status' => $this->integer()->notNull()->defaultValue(0),
            'main' => $this->integer()->notNull()->defaultValue(0),
            'date' => $this->timestamp(),
            'is_filter' => $this->integer()->null(),
            'popular' => $this->integer()->null(),
            'deleted_at' => $this->dateTime()->null()
        ]);

        $this->createIndex('id', 'category', 'id', true);
    }

    public function safeDown()
    {
        $this->dropTable('category');
    }
}