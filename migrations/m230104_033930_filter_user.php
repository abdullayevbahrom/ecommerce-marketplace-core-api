<?php

use yii\db\Migration;

/**
 * Class m230104_033930_filter_user
 */
class m230104_033930_filter_user extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('filter_user', [
            'id' => $this->primaryKey(),
            'user_id' => $this->integer(),
            'filter_id' => $this->integer(),
            'enabled' => $this->integer(),
            'date' => $this->timestamp()
        ]);

        $this->addForeignKey('filter_user_u_fk', 'filter_user', 'user_id', 'user', 'id', 'CASCADE');
        $this->addForeignKey('filter_user_f_fk', 'filter_user', 'filter_id', 'filter', 'id', 'CASCADE');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m230104_033930_filter_user cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m230104_033930_filter_user cannot be reverted.\n";

        return false;
    }
    */
}
