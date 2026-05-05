<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%auth_refresh_tokens}}`.
 */
class m260505_033028_create_auth_refresh_tokens_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%auth_refresh_tokens}}', [
            'id' => $this->bigPrimaryKey(),
            'user_id' => $this->bigInteger()->notNull(),
            'token_hash' => $this->char(64)->notNull(),
            'device_token' => $this->string(100)->null(),
            'ip' => $this->string(45)->null(),
            'user_agent' => $this->text()->null(),
            'expires_at' => $this->dateTime()->notNull(),
            'revoked_at' => $this->dateTime()->null(),
            'created_at' => $this->dateTime()->notNull(),
            'updated_at' => $this->dateTime()->null(),
        ]);

        $this->createIndex(
            'idx-auth_refresh_tokens-user_id',
            '{{%auth_refresh_tokens}}',
            'user_id'
        );

        $this->createIndex(
            'idx-auth_refresh_tokens-token_hash',
            '{{%auth_refresh_tokens}}',
            'token_hash'
        );

        $this->createIndex(
            'idx-auth_refresh_tokens-expires_at',
            '{{%auth_refresh_tokens}}',
            'expires_at'
        );
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable('{{%auth_refresh_tokens}}');
    }
}
