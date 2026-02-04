<?php

use yii\db\Migration;

/**
 * Class m220117_203834_question
 */
class m220117_203834_question extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('question', [
            'id' => $this->primaryKey(),
            'question_ru' => $this->text(),
            'question_en' => $this->text(),
            'question_uz' => $this->text(),
            'answer_ru' => $this->text(),
            'answer_en' => $this->text(),
            'answer_uz' => $this->text(),
            'status' => $this->integer()->notNull()->defaultValue(0),
            'date' => $this->timestamp()
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m220117_203834_question cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m220117_203834_question cannot be reverted.\n";

        return false;
    }
    */
}
