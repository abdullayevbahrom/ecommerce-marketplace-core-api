<?php

use yii\db\Migration;

/**
 * Create separate DIDOX documents table
 */
class m240101_000001_create_didox_document_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        // Create separate DIDOX documents table
        $this->createTable('{{%didox_document}}', [
            'id' => $this->primaryKey(),
            'name' => $this->string(255)->notNull()->comment('Document name'),
            'didox_id' => $this->string(100)->null()->comment('DIDOX document ID'),
            'didox_status' => $this->integer()->null()->comment('DIDOX document status (0-190)'),
            'didox_type' => $this->string(10)->null()->comment('DIDOX document type (001, 002, 008, etc.)'),
            'buyer_tin' => $this->string(20)->null()->comment('Buyer TIN (INN)'),
            'seller_tin' => $this->string(20)->null()->comment('Seller TIN (INN)'),
            'total_sum' => $this->decimal(15, 2)->null()->comment('Total document sum'),
            'total_delivery_sum' => $this->decimal(15, 2)->null()->comment('Total delivery sum'),
            'total_vat_sum' => $this->decimal(15, 2)->null()->comment('Total VAT sum'),
            'contract_number' => $this->string(100)->null()->comment('Contract number'),
            'contract_date' => $this->date()->null()->comment('Contract date'),
            'has_vat' => $this->boolean()->defaultValue(false)->comment('Has VAT'),
            'has_lgota' => $this->boolean()->defaultValue(false)->comment('Has benefits'),
            'has_marks' => $this->boolean()->defaultValue(false)->comment('Has markings'),
            'oneside' => $this->boolean()->defaultValue(false)->comment('One-sided document'),
            'didox_created_at' => $this->dateTime()->null()->comment('DIDOX document creation timestamp'),
            'didox_signed_at' => $this->dateTime()->null()->comment('DIDOX document signing timestamp'),
            'didox_data' => $this->text()->null()->comment('JSON encoded DIDOX document data'),
            'didox_user_key' => $this->string(255)->null()->comment('User key for DIDOX authentication'),
            'created_by' => $this->integer()->null()->comment('User ID who created the document'),
            'status' => $this->integer()->defaultValue(1)->comment('Local document status'),
            'created_at' => $this->timestamp()->defaultExpression('CURRENT_TIMESTAMP'),
            'updated_at' => $this->timestamp()->defaultExpression('CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'),
        ]);
        
        // Create indexes for better performance
        $this->createIndex('idx-didox_document-didox_id', '{{%didox_document}}', 'didox_id');
        $this->createIndex('idx-didox_document-didox_status', '{{%didox_document}}', 'didox_status');
        $this->createIndex('idx-didox_document-didox_type', '{{%didox_document}}', 'didox_type');
        $this->createIndex('idx-didox_document-buyer_tin', '{{%didox_document}}', 'buyer_tin');
        $this->createIndex('idx-didox_document-seller_tin', '{{%didox_document}}', 'seller_tin');
        $this->createIndex('idx-didox_document-created_by', '{{%didox_document}}', 'created_by');
        $this->createIndex('idx-didox_document-status', '{{%didox_document}}', 'status');
        $this->createIndex('idx-didox_document-didox_created_at', '{{%didox_document}}', 'didox_created_at');
        
        // Add foreign key constraint for created_by
        $this->addForeignKey(
            'fk-didox_document-created_by',
            '{{%didox_document}}',
            'created_by',
            '{{%user}}',
            'id',
            'SET NULL',
            'CASCADE'
        );
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        // Drop foreign key
        $this->dropForeignKey('fk-didox_document-created_by', '{{%didox_document}}');
        
        // Drop indexes
        $this->dropIndex('idx-didox_document-didox_created_at', '{{%didox_document}}');
        $this->dropIndex('idx-didox_document-status', '{{%didox_document}}');
        $this->dropIndex('idx-didox_document-created_by', '{{%didox_document}}');
        $this->dropIndex('idx-didox_document-seller_tin', '{{%didox_document}}');
        $this->dropIndex('idx-didox_document-buyer_tin', '{{%didox_document}}');
        $this->dropIndex('idx-didox_document-didox_type', '{{%didox_document}}');
        $this->dropIndex('idx-didox_document-didox_status', '{{%didox_document}}');
        $this->dropIndex('idx-didox_document-didox_id', '{{%didox_document}}');
        
        // Drop table
        $this->dropTable('{{%didox_document}}');
    }
} 