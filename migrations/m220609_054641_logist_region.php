<?php

use yii\db\Migration;

/**
 * Class m220609_054641_logist_region
 */
class m220609_054641_logist_region extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('logist_region', [
            'id' => $this->primaryKey(),
            'logist_id' => $this->integer(),
            'region_id' => $this->integer(),
            'region_tree' => $this->string(),
            'date' => $this->timestamp()
        ]);

        $this->createIndex('id', 'logist_region', 'id', true);
        $this->addForeignKey('logist_region_l_fk', 'logist_region', 'logist_id', 'logist', 'id', 'CASCADE');
        $this->addForeignKey('logist_region_r_fk', 'logist_region', 'region_id', 'category', 'id', 'CASCADE');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m220609_054641_logist_region cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m220609_054641_logist_region cannot be reverted.\n";

        return false;
    }
    */
}
