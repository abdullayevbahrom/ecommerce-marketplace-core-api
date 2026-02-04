<?php

use yii\db\Migration;

/**
 * Class m190809_064141_user
 */
class m190809_064141_user extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('user', [
            'id' => $this->primaryKey(),
            'role' => $this->integer(),
            'token' => $this->string(),
            'facebook_id' => $this->string(),
            'google_id' => $this->string(),
            'vk_id' => $this->string(),
            'device_id' => $this->string(),
            'balance' => $this->double(),
            'name' => $this->string(),
            'lastname' => $this->string(),
            'phone' => $this->string(),
            'email' => $this->string(),
            'login' => $this->string()->unique(),
            'password' => $this->string(),
            'gender' => $this->integer(),
            'birthday' => $this->string(),
            'status' => $this->integer()->notNull()->defaultValue(0),
            'date' => $this->timestamp(),
            'ip' => $this->string()
        ]);

        $this->createIndex('id', 'user', 'id', true);
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m190809_064141_user cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m190809_064141_user cannot be reverted.\n";

        return false;
    }
    */
}
