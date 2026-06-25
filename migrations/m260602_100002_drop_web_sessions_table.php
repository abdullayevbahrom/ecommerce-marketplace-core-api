<?php

use yii\db\Migration;

class m260602_100002_drop_web_sessions_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->dropTable('{{%web_sessions}}');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->createTable('{{%web_sessions}}', [
            'id' => $this->primaryKey(),
            'user_id' => $this->integer()->notNull(),
            'access_token' => $this->string(64)->notNull()->unique(),
            'refresh_token' => $this->string(64)->notNull()->unique(),
            'device_name' => $this->string(255)->null(),
            'platform' => $this->string(100)->null(),
            'browser' => $this->string(100)->null(),

            'ip' => $this->string(45)->null(),
            'user_agent' => $this->string()->null(),

            'is_revoked' => $this->tinyInteger(1)->notNull()->defaultValue(0),

            'expires_at' => $this->integer()->notNull(),
            'created_at' => $this->integer()->notNull(),
            'updated_at' => $this->integer()->null(),
        ]);

        $this->createIndex('idx-web_sessions-user_id', '{{%web_sessions}}', 'user_id');
    }
}
