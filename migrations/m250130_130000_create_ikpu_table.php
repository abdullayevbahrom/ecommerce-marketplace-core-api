<?php

use yii\db\Migration;

/**
 * Class m250130_130000_create_ikpu_table
 * Creates IKPU (Единый классификатор продукции Узбекистана) reference table
 */
class m250130_130000_create_ikpu_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        // Create IKPU reference table
        $this->createTable('ikpu', [
            'id' => $this->primaryKey(),
            'code' => $this->string(17)->notNull()->unique()->comment('Код ИКПУ'),
            'name_ru' => $this->string(500)->notNull()->comment('Название ИКПУ на русском'),
            'name_uz' => $this->string(500)->null()->comment('Название ИКПУ на узбекском'),
            'name_en' => $this->string(500)->null()->comment('Название ИКПУ на английском'),
            'parent_code' => $this->string(17)->null()->comment('Родительский код ИКПУ'),
            'status' => $this->integer()->defaultValue(1)->comment('Статус (1=активный, 0=неактивный)'),
            'created_at' => $this->timestamp()->defaultExpression('CURRENT_TIMESTAMP'),
            'updated_at' => $this->timestamp()->defaultExpression('CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'),
        ]);

        // Create indexes
        $this->createIndex('idx_ikpu_code', 'ikpu', 'code');
        $this->createIndex('idx_ikpu_parent_code', 'ikpu', 'parent_code');
        $this->createIndex('idx_ikpu_status', 'ikpu', 'status');

        // Add foreign key for parent relationship
        $this->addForeignKey(
            'fk_ikpu_parent',
            'ikpu',
            'parent_code',
            'ikpu',
            'code',
            'SET NULL',
            'CASCADE'
        );
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropForeignKey('fk_ikpu_parent', 'ikpu');
        $this->dropTable('ikpu');
    }

    /*
    // Manual SQL for creating IKPU table:
    CREATE TABLE `ikpu` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `code` varchar(17) NOT NULL COMMENT 'Код ИКПУ',
        `name_ru` varchar(500) NOT NULL COMMENT 'Название ИКПУ на русском',
        `name_uz` varchar(500) DEFAULT NULL COMMENT 'Название ИКПУ на узбекском',
        `name_en` varchar(500) DEFAULT NULL COMMENT 'Название ИКПУ на английском',
        `parent_code` varchar(17) DEFAULT NULL COMMENT 'Родительский код ИКПУ',
        `status` int(11) DEFAULT 1 COMMENT 'Статус (1=активный, 0=неактивный)',
        `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
        `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        UNIQUE KEY `code` (`code`),
        KEY `idx_ikpu_code` (`code`),
        KEY `idx_ikpu_parent_code` (`parent_code`),
        KEY `idx_ikpu_status` (`status`),
        CONSTRAINT `fk_ikpu_parent` FOREIGN KEY (`parent_code`) REFERENCES `ikpu` (`code`) ON DELETE SET NULL ON UPDATE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

    // To rollback (drop table):
    DROP TABLE IF EXISTS `ikpu`;
    */
}
