<?php

use yii\db\Migration;

/**
 * Handles the creation of table `seller_application`.
 */
class m250107_000001_create_seller_application_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('seller_application', [
            'id' => $this->primaryKey(),
            'name' => $this->string()->notNull()->comment('Applicant name'),
            'phone' => $this->string()->notNull()->comment('Applicant phone number'),
            'status' => $this->integer()->defaultValue(1)->comment('Application status: 1=pending, 2=approved, 3=rejected'),
            'admin_notes' => $this->text()->comment('Admin notes about the application'),
            'date' => $this->dateTime()->defaultExpression('CURRENT_TIMESTAMP')->comment('Application submission date'),
        ]);

        // Add indexes for better performance
        $this->createIndex('idx-seller_application-status', 'seller_application', 'status');
        $this->createIndex('idx-seller_application-phone', 'seller_application', 'phone');
        $this->createIndex('idx-seller_application-date', 'seller_application', 'date');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable('seller_application');
    }
} 