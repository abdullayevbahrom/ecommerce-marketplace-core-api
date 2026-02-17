<?php

use yii\db\Migration;

class m220629_043815_color extends Migration
{
    public function safeUp()
    {
        $this->createTable('color', [
            'id' => $this->primaryKey(),
            'name_ru' => $this->string(),
            'name_en' => $this->string(),
            'name_uz' => $this->string(),
            'color' => $this->string(),
            'date' => $this->timestamp(),
            'deleted_at' => $this->dateTime()->null(),
            'status' => $this->integer()->notNull()->defaultValue(1)->comment('1=active, 0=inactive')
        ]);

        $this->createIndex('id', 'color', 'id', true);
        $this->createIndex('idx-color-status', '{{%color}}', 'status');
    }

    public function safeDown()
    {
        $this->dropIndex('idx-color-status', '{{%color}}');
        $this->dropTable('color');
    }
}
