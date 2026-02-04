<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%cities}}`.
 */
class m250126_130100_create_cities_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%cities}}', [
            'id' => $this->primaryKey(),
            'bts_id' => $this->integer()->notNull()->unique()->comment('BTS system city ID'),
            'region_id' => $this->integer()->notNull()->comment('Reference to regions table'),
            'bts_region_id' => $this->integer()->notNull()->comment('BTS system region ID'),
            'name_ru' => $this->string(255)->notNull()->comment('Russian name'),
            'name_uz' => $this->string(255)->notNull()->comment('Uzbek name'),
            'name_en' => $this->string(255)->notNull()->comment('English name'),
            'status' => $this->integer()->defaultValue(1)->comment('Status: 1=active, 0=inactive'),
            'created_at' => $this->timestamp()->defaultExpression('CURRENT_TIMESTAMP'),
            'updated_at' => $this->timestamp()->defaultExpression('CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'),
        ]);

        // Create indexes
        $this->createIndex('idx-cities-bts_id', '{{%cities}}', 'bts_id');
        $this->createIndex('idx-cities-region_id', '{{%cities}}', 'region_id');
        $this->createIndex('idx-cities-bts_region_id', '{{%cities}}', 'bts_region_id');
        $this->createIndex('idx-cities-status', '{{%cities}}', 'status');

        // Add foreign key for region_id
        $this->addForeignKey(
            'fk-cities-region_id',
            '{{%cities}}',
            'region_id',
            '{{%regions}}',
            'id',
            'CASCADE'
        );
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        // Drop foreign key first
        $this->dropForeignKey('fk-cities-region_id', '{{%cities}}');
        $this->dropTable('{{%cities}}');
    }

    /*
    // Manual SQL for adjustments if needed:
    CREATE TABLE `cities` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `bts_id` int(11) NOT NULL COMMENT 'BTS system city ID',
      `region_id` int(11) NOT NULL COMMENT 'Reference to regions table',
      `bts_region_id` int(11) NOT NULL COMMENT 'BTS system region ID',
      `name_ru` varchar(255) NOT NULL COMMENT 'Russian name',
      `name_uz` varchar(255) NOT NULL COMMENT 'Uzbek name',
      `name_en` varchar(255) NOT NULL COMMENT 'English name',
      `status` int(11) DEFAULT 1 COMMENT 'Status: 1=active, 0=inactive',
      `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
      `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      PRIMARY KEY (`id`),
      UNIQUE KEY `bts_id` (`bts_id`),
      KEY `idx-cities-bts_id` (`bts_id`),
      KEY `idx-cities-region_id` (`region_id`),
      KEY `idx-cities-bts_region_id` (`bts_region_id`),
      KEY `idx-cities-status` (`status`),
      CONSTRAINT `fk-cities-region_id` FOREIGN KEY (`region_id`) REFERENCES `regions` (`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

    // To rollback:
    DROP TABLE `cities`;
    */
}