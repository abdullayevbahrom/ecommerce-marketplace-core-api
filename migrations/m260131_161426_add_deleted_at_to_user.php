<?php

use yii\db\Migration;

class m260131_161426_add_deleted_at_to_user extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('{{%user}}', 'deleted_at', $this->dateTime()->null()->after('status'));
        $this->createIndex('idx-user-deleted_at', '{{%user}}', 'deleted_at');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropIndex('idx-user-deleted_at', '{{%user}}');
        $this->dropColumn('{{%user}}', 'deleted_at');
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m260131_161426_add_deleted_at_to_user cannot be reverted.\n";

        return false;
    }
    */
}
