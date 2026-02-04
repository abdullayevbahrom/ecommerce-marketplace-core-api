<?php

use yii\db\Migration;

/**
 * Class m221020_092905_partners
 */
class m221020_092905_partners extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('partners', [
            'id' => $this->primaryKey(),
            'name_ru' => $this->string(),
            'name_uz' => $this->string(),
            'name_en' => $this->string(),
            'description_mini_ru' => $this->text(),
            'description_mini_uz' => $this->text(),
            'description_mini_en' => $this->text(),
            'description_ru' => $this->text(),
            'description_uz' => $this->text(),
            'description_en' => $this->text(),
            'status' => $this->integer(),
            'sort' => $this->integer(),
            'date' => $this->timestamp()
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m221020_092905_partners cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m221020_092905_partners cannot be reverted.\n";

        return false;
    }
    */
}
