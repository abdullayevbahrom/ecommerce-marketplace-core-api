<?php

use yii\db\Migration;

/**
 * Handles the creation of table `product_request`.
 */
class m250107_000002_create_product_request_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('product_request', [
            'id' => $this->primaryKey(),
            'product_name' => $this->string()->notNull()->comment('Product name'),
            'product_photo' => $this->string()->comment('Product photo filename'),
            'quantity' => $this->integer()->notNull()->comment('Requested quantity'),
            'product_link' => $this->text()->comment('Link to product'),
            'phone' => $this->string()->notNull()->comment('Applicant phone number'),
            'email' => $this->string()->comment('Applicant email'),
            'status' => $this->integer()->defaultValue(1)->comment('Request status: 1=pending, 2=approved, 3=rejected'),
            'admin_notes' => $this->text()->comment('Admin notes about the request'),
            'date' => $this->dateTime()->defaultExpression('CURRENT_TIMESTAMP')->comment('Request submission date'),
        ]);

        // Add indexes for better performance
        $this->createIndex('idx-product_request-status', 'product_request', 'status');
        $this->createIndex('idx-product_request-phone', 'product_request', 'phone');
        $this->createIndex('idx-product_request-date', 'product_request', 'date');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable('product_request');
    }
} 