<?php

use yii\db\Migration;

/**
 * Class m220222_123602_shop
 */
class m220222_123602_shop extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('shop', [
            'id' => $this->primaryKey(),
            'user_id' => $this->integer(),
            'name_ru' => $this->string(),
            'name_uz' => $this->string(),
            'name_en' => $this->string(),
            'description_ru' => $this->text(),
            'description_uz' => $this->text(),
            'description_en' => $this->text(),
            'status' => $this->integer()->notNull()->defaultValue(0),
            'date' => $this->timestamp()
        ]);

        $this->createIndex('id', 'shop', 'id', true);
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m220222_123602_shop cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m220222_123602_shop cannot be reverted.\n";

        return false;
    }
    */
}
