<?php

use yii\db\Migration;

/**
 * Class m220117_113530_news_view
 */
class m220117_113530_news_view extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('news_view', [
            'id' => $this->primaryKey(),
            'news_id' => $this->integer(),
            'ip' => $this->string(),
            'date' => $this->timestamp()
        ]);

        $this->addForeignKey('news_view_news_fk', 'news_view', 'news_id', 'news', 'id', 'CASCADE');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m220117_113530_news_view cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m220117_113530_news_view cannot be reverted.\n";

        return false;
    }
    */
}
