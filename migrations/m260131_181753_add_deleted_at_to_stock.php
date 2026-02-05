<?php

use yii\db\Migration;

class m260131_181753_add_deleted_at_to_stock extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('{{%stock}}', 'deleted_at', $this->dateTime()->null()->after('status'));
        $this->createIndex('idx-stock-deleted_at', '{{%stock}}', 'deleted_at');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropIndex('idx-stock-deleted_at', '{{%stock}}');
        $this->dropColumn('{{%stock}}', 'deleted_at');
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m260131_181753_add_deleted_at_to_stock cannot be reverted.\n";

        return false;
    }
    */
}
