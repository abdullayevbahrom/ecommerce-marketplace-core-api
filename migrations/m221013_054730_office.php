<?php

use yii\db\Migration;

/**
 * Class m221013_054730_office
 */
class m221013_054730_office extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('office', [
            'id' => $this->primaryKey(),
            'office_id' => $this->string(),
            'name' => $this->string(),
            'date' => $this->timestamp()
        ]);

        $this->createIndex('id', 'office', 'id', true);
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m221013_054730_office cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m221013_054730_office cannot be reverted.\n";

        return false;
    }
    */
}
