<?php

use yii\db\Migration;

class m220119_130050_delivery extends Migration
{
    public function safeUp()
    {
        $this->createTable('delivery', [
            'id' => $this->primaryKey(),
            'name_ru' => $this->string(),
            'name_uz' => $this->string(),
            'name_en' => $this->string(),
            'description_ru' => $this->text(),
            'description_uz' => $this->text(),
            'description_en' => $this->text(),
            'price' => $this->double(),
            'status' => $this->integer()->notNull()->defaultValue(0),
            'sort' => $this->integer()->notNull()->defaultValue(0),
            'date' => $this->timestamp()
        ]);

        $this->createIndex('id', 'delivery', 'id', true);
    }

    public function safeDown()
    {
       $this->dropTable('delivery');
    }
}
