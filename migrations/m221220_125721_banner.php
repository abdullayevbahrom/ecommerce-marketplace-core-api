<?php

use yii\db\Migration;

class m221220_125721_banner extends Migration
{
    public function safeUp()
    {
        $this->createTable('banner', [
            'id' => $this->primaryKey(),
            'type' => $this->string(),
            'description_ru' => $this->text(),
            'description_en' => $this->text(),
            'description_uz' => $this->text(),
            'sort' => $this->integer()->notNull()->defaultValue(0),
            'status' => $this->integer()->notNull()->defaultValue(1),
            'date' => $this->timestamp(),
            'hash' => $this->text(),
        ]);
    }

     public function safeDown()
    {
        $this->dropTable('banner');
    }
}
