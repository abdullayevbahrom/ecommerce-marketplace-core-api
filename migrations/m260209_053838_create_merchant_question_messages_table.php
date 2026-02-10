<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%merchant_question_messages}}`.
 */
class m260209_053838_create_merchant_question_messages_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%merchant_question_messages}}', [
            'id' => $this->primaryKey(),

            'question_id' => $this->integer()->notNull(),

            'sender_id' => $this->integer()->notNull(),
            'sender_role' => $this->string(20)->notNull(), // client | merchant | moderator

            'message' => $this->text()->notNull(),

            'created_at' => $this->integer()->notNull(),
        ]);

        $this->createIndex(
            'idx-mqm-question_id',
            '{{%merchant_question_messages}}',
            'question_id'
        );
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropForeignKey('fk-mqm-question', '{{%merchant_question_messages}}');
        $this->dropTable('{{%merchant_question_messages}}');
    }
}
