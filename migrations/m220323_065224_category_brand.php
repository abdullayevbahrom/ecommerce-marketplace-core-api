<?php

use yii\db\Migration;

class m220323_065224_category_brand extends Migration
{
    public function safeUp()
    {
        $this->createTable('category_brand', [
            'id' => $this->primaryKey(),
            'category_id' => $this->integer(),
            'category_tree' => $this->string(),
            'name_ru' => $this->string(),
            'name_en' => $this->string(),
            'name_uz' => $this->string(),
            'description_ru' => $this->text(),
            'description_en' => $this->text(),
            'description_uz' => $this->text(),
            'status' => $this->integer()->notNull()->defaultValue(1),
            'sort' => $this->integer()->notNull()->defaultValue(0),
            'date' => $this->timestamp(),
            'deleted_at' => $this->dateTime()->null()
        ]);

        $this->addForeignKey('category_brand_c_fk', 'category_brand', 'category_id', 'category', 'id');
    }

    public function safeDown()
    {
        $this->dropForeignKey('category_brand_c_fk', 'category_brand');
        $this->dropTable('category_brand');
    }

}
