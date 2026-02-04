<?php

use yii\db\Migration;

/**
 * Class m220319_095221_messages
 */
class m220319_095221_messages extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('messages', [
            'id' => $this->primaryKey(),
            'message_room_id' => $this->integer(),
            'user_id' => $this->integer(),
            'message' => $this->text(),
            'status' => $this->integer()->notNull()->defaultValue(0),
            'date' => $this->timestamp()
        ]);

        $this->addForeignKey('messages_m_fk', 'messages', 'message_room_id', 'message_room', 'id', 'CASCADE');
        $this->addForeignKey('messages_u_fk', 'messages', 'user_id', 'user', 'id', 'CASCADE');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m220319_095221_messages cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m220319_095221_messages cannot be reverted.\n";

        return false;
    }
    */
}
