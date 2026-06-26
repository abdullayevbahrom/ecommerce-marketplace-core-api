<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%regions}}`.
 */
class m250126_130000_create_regions_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%regions}}', [
            'id' => $this->primaryKey(),
            'bts_id' => $this->string(255)->notNull()->unique()->comment('BTS system region ID'),
            'name_ru' => $this->string(255)->notNull()->comment('Russian name'),
            'name_uz' => $this->string(255)->notNull()->comment('Uzbek name'),
            'name_en' => $this->string(255)->notNull()->comment('English name'),
            'status' => $this->integer()->defaultValue(1)->comment('Status: 1=active, 0=inactive'),
            'created_at' => $this->timestamp()->defaultExpression('CURRENT_TIMESTAMP'),
            'updated_at' => $this->timestamp()->defaultExpression('CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'),
        ]);

        // Create indexes
        $this->createIndex('idx-regions-bts_id', '{{%regions}}', 'bts_id');
        $this->createIndex('idx-regions-status', '{{%regions}}', 'status');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable('{{%regions}}');
    }

    /*
    // Manual SQL for adjustments if needed:
    CREATE TABLE `regions` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `bts_id` int(11) NOT NULL COMMENT 'BTS system region ID',
      `name_ru` varchar(255) NOT NULL COMMENT 'Russian name',
      `name_uz` varchar(255) NOT NULL COMMENT 'Uzbek name', 
      `name_en` varchar(255) NOT NULL COMMENT 'English name',
      `status` int(11) DEFAULT 1 COMMENT 'Status: 1=active, 0=inactive',
      `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
      `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      PRIMARY KEY (`id`),
      UNIQUE KEY `bts_id` (`bts_id`),
      KEY `idx-regions-bts_id` (`bts_id`),
      KEY `idx-regions-status` (`status`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

    // To rollback:
    DROP TABLE `regions`;
    */
}