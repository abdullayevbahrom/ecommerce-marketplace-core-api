<?php

use yii\db\Migration;

/**
 * Class m220319_095211_message_room
 */
class m220319_095211_message_room extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('message_room', [
            'id' => $this->primaryKey(),
            'sender_id' => $this->integer(),
            'getter_id' => $this->integer(),
            'status' => $this->integer()->notNull()->defaultValue(0),
            'status_archive' => $this->integer()->notNull()->defaultValue(0),
            'status_important' => $this->integer()->notNull()->defaultValue(0),
            'date' => $this->timestamp()
        ]);

        $this->createIndex('id', 'message_room', 'id', true);
        $this->addForeignKey('message_room_s_fk', 'message_room', 'sender_id', 'user', 'id', 'CASCADE');
        $this->addForeignKey('message_room_g_fk', 'message_room', 'getter_id', 'user', 'id', 'CASCADE');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m220319_095211_message_room cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m220319_095211_message_room cannot be reverted.\n";

        return false;
    }
    */
}
