<?php

use yii\db\Migration;

/**
 * Class m251215_000002_create_logs_table
 * 
 * Manual SQL to create table:
 * CREATE TABLE `logs` (
 *   `id` INT(11) NOT NULL AUTO_INCREMENT,
 *   `category` VARCHAR(255) NOT NULL,
 *   `level` VARCHAR(50) DEFAULT 'info',
 *   `message` TEXT,
 *   `data` LONGTEXT,
 *   `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
 *   PRIMARY KEY (`id`),
 *   INDEX `idx-logs-category` (`category`),
 *   INDEX `idx-logs-created_at` (`created_at`)
 * ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
 */
class m251215_000002_create_logs_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%logs}}', [
            'id' => $this->primaryKey(),
            'category' => $this->string()->notNull(),
            'level' => $this->string(50)->defaultValue('info'),
            'message' => $this->text(),
            'data' => $this->text(), // For JSON data
            'created_at' => $this->dateTime()->defaultExpression('CURRENT_TIMESTAMP'),
        ]);

        $this->createIndex(
            'idx-logs-category',
            '{{%logs}}',
            'category'
        );
        
        $this->createIndex(
            'idx-logs-created_at',
            '{{%logs}}',
            'created_at'
        );
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable('{{%logs}}');
    }
}
