<?php

use yii\db\Migration;

/**
 * Class m221220_125721_banner
 */
class m221220_125721_banner extends Migration
{
    /**
     * {@inheritdoc}
     */
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
            'date' => $this->timestamp()
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m221220_125721_banner cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m221220_125721_banner cannot be reverted.\n";

        return false;
    }
    */
}
