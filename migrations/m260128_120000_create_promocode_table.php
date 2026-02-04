<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%promocode}}`.
 */
class m260128_120000_create_promocode_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%promocode}}', [
            'id' => $this->primaryKey(),
            'code' => $this->string(50)->notNull()->unique(),
            'type' => $this->integer()->notNull()->defaultValue(1)->comment('1: Fixed Amount, 2: Percentage'),
            'value' => $this->decimal(10, 2)->notNull(),
            'min_order_amount' => $this->decimal(10, 2)->defaultValue(0),
            'max_discount_amount' => $this->decimal(10, 2)->null()->comment('Max discount for percentage type'),
            'start_date' => $this->dateTime()->null(),
            'end_date' => $this->dateTime()->null(),
            'usage_limit' => $this->integer()->null()->comment('Total times code can be used'),
            'usage_limit_per_user' => $this->integer()->defaultValue(1)->comment('Times a single user can use'),
            'status' => $this->integer()->defaultValue(1)->comment('0: Inactive, 1: Active'),
            'is_first_order' => $this->boolean()->defaultValue(false),
            'category_id' => $this->integer()->null(),
            'product_id' => $this->integer()->null(),
            'user_id' => $this->integer()->null()->comment('Specific user ID for personal promocodes'),
            
            // Translatable fields
            'title_ru' => $this->string(255),
            'title_uz' => $this->string(255),
            'title_en' => $this->string(255),
            'description_ru' => $this->text(),
            'description_uz' => $this->text(),
            'description_en' => $this->text(),
            
            'created_at' => $this->dateTime()->defaultExpression('CURRENT_TIMESTAMP'),
            'updated_at' => $this->dateTime()->defaultExpression('CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'),
        ]);

        // Add index for code lookup
        $this->createIndex(
            '{{%idx-promocode-code}}',
            '{{%promocode}}',
            'code'
        );

        // Add columns to order table
        $this->addColumn('{{%order}}', 'promocode_id', $this->integer()->null());
        $this->addColumn('{{%order}}', 'discount_amount', $this->decimal(10, 2)->defaultValue(0));

        // Add foreign key for order -> promocode
        $this->addForeignKey(
            '{{%fk-order-promocode_id}}',
            '{{%order}}',
            'promocode_id',
            '{{%promocode}}',
            'id',
            'SET NULL',
            'CASCADE'
        );

        // Add foreign key for promocode -> user (if user_id is set)
        $this->addForeignKey(
            '{{%fk-promocode-user_id}}',
            '{{%promocode}}',
            'user_id',
            '{{%user}}',
            'id',
            'CASCADE',
            'CASCADE'
        );
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropForeignKey('{{%fk-promocode-user_id}}', '{{%promocode}}');
        $this->dropForeignKey('{{%fk-order-promocode_id}}', '{{%order}}');
        $this->dropColumn('{{%order}}', 'promocode_id');
        $this->dropColumn('{{%order}}', 'discount_amount');
        $this->dropTable('{{%promocode}}');
    }
}

/*
-- SQL for manual execution:

CREATE TABLE `promocode` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `code` varchar(50) NOT NULL,
    `type` int(11) NOT NULL DEFAULT '1' COMMENT '1: Fixed Amount, 2: Percentage',
    `value` decimal(10,2) NOT NULL,
    `min_order_amount` decimal(10,2) DEFAULT '0.00',
    `max_discount_amount` decimal(10,2) DEFAULT NULL COMMENT 'Max discount for percentage type',
    `start_date` datetime DEFAULT NULL,
    `end_date` datetime DEFAULT NULL,
    `usage_limit` int(11) DEFAULT NULL COMMENT 'Total times code can be used',
    `usage_limit_per_user` int(11) DEFAULT '1' COMMENT 'Times a single user can use',
    `status` int(11) DEFAULT '1' COMMENT '0: Inactive, 1: Active',
    `is_first_order` tinyint(1) DEFAULT '0',
    `category_id` int(11) DEFAULT NULL,
    `product_id` int(11) DEFAULT NULL,
    `user_id` int(11) DEFAULT NULL COMMENT 'Specific user ID for personal promocodes',
    `title_ru` varchar(255) DEFAULT NULL,
    `title_uz` varchar(255) DEFAULT NULL,
    `title_en` varchar(255) DEFAULT NULL,
    `description_ru` text,
    `description_uz` text,
    `description_en` text,
    `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
    `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx-promocode-code` (`code`)
) ENGINE=InnoDB;

ALTER TABLE `order` ADD COLUMN `promocode_id` int(11) DEFAULT NULL;
ALTER TABLE `order` ADD COLUMN `discount_amount` decimal(10,2) DEFAULT '0.00';

ALTER TABLE `order`
ADD CONSTRAINT `fk-order-promocode_id`
FOREIGN KEY (`promocode_id`) REFERENCES `promocode` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

ALTER TABLE `promocode`
ADD CONSTRAINT `fk-promocode-user_id`
FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
*/
