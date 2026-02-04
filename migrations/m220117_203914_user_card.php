<?php

use yii\db\Migration;

/**
 * Class m220117_203914_user_card
 */
class m220117_203914_user_card extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('user_card', [
            'id' => $this->primaryKey(),
            'user_id' => $this->integer(),
            'card_token' => $this->string(),
            'card_number' => $this->string(),
            'card_expire' => $this->string(),
            'card_phone_number' => $this->string(),
            'status' => $this->integer()->notNull()->defaultValue(1),
            'date' => $this->timestamp()
        ]);

        $this->addForeignKey('user_card_u_fk', 'user_card', 'user_id', 'user', 'id', 'CASCADE');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m220117_203914_user_card cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m220117_203914_user_card cannot be reverted.\n";

        return false;
    }
    */
}
