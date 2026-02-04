<?php

use yii\db\Migration;

/**
 * Create core DIDOX document table (parent table for all document types)
 * Contains only essential fields common to all document types.
 * Specific document type fields will be in separate tables.
 */
class m250106_000000_create_core_didox_document_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        // Create core DIDOX documents table
        $this->createTable('{{%didox_document}}', [
            'id' => $this->primaryKey(),
            'name' => $this->string(255)->notNull()->comment('Document name'),
            'document_type' => "ENUM('invoice') NOT NULL DEFAULT 'invoice' COMMENT 'Document type (invoice for Счёт фактура)'",
            
            // DIDOX platform integration fields
            'didox_id' => $this->string(100)->null()->comment('DIDOX document ID (_id from API response)'),
            'didox_status' => $this->integer()->null()->comment('DIDOX document status (0-190)'),
            'didox_data' => $this->text()->null()->comment('JSON encoded DIDOX API response data'),
            'didox_error_data' => $this->text()->null()->comment('JSON encoded DIDOX API error responses'),
            'didox_last_attempt' => $this->dateTime()->null()->comment('Last DIDOX API submission attempt'),
            'didox_created_at' => $this->dateTime()->null()->comment('DIDOX document creation timestamp'),
            'didox_signed_at' => $this->dateTime()->null()->comment('DIDOX document signing timestamp'),
            
            // Local system fields
            'created_by' => $this->integer()->null()->comment('User ID who created the document'),
            'to_user_id' => $this->integer()->null()->comment('User ID assigned to sign the document'),
            'status' => $this->integer()->defaultValue(1)->comment('Local document status (0=inactive, 1=active, 2=blocked)'),
            'created_at' => $this->timestamp()->defaultExpression('CURRENT_TIMESTAMP'),
            'updated_at' => $this->timestamp()->defaultExpression('CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'),
        ]);
        
        // Create indexes for better performance
        $this->createIndex('idx-didox_document-didox_id', '{{%didox_document}}', 'didox_id');
        $this->createIndex('idx-didox_document-didox_status', '{{%didox_document}}', 'didox_status');
        $this->createIndex('idx-didox_document-document_type', '{{%didox_document}}', 'document_type');
        $this->createIndex('idx-didox_document-created_by', '{{%didox_document}}', 'created_by');
        $this->createIndex('idx-didox_document-to_user_id', '{{%didox_document}}', 'to_user_id');
        $this->createIndex('idx-didox_document-status', '{{%didox_document}}', 'status');
        $this->createIndex('idx-didox_document-didox_created_at', '{{%didox_document}}', 'didox_created_at');
        $this->createIndex('idx-didox_document-created_at', '{{%didox_document}}', 'created_at');
        
        // Add foreign key constraints
        $this->addForeignKey(
            'fk-didox_document-created_by',
            '{{%didox_document}}',
            'created_by',
            '{{%user}}',
            'id',
            'SET NULL',
            'CASCADE'
        );
        
        $this->addForeignKey(
            'fk-didox_document-to_user_id',
            '{{%didox_document}}',
            'to_user_id',
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
        // Drop foreign keys
        $this->dropForeignKey('fk-didox_document-to_user_id', '{{%didox_document}}');
        $this->dropForeignKey('fk-didox_document-created_by', '{{%didox_document}}');
        
        // Drop indexes
        $this->dropIndex('idx-didox_document-created_at', '{{%didox_document}}');
        $this->dropIndex('idx-didox_document-didox_created_at', '{{%didox_document}}');
        $this->dropIndex('idx-didox_document-status', '{{%didox_document}}');
        $this->dropIndex('idx-didox_document-to_user_id', '{{%didox_document}}');
        $this->dropIndex('idx-didox_document-created_by', '{{%didox_document}}');
        $this->dropIndex('idx-didox_document-document_type', '{{%didox_document}}');
        $this->dropIndex('idx-didox_document-didox_status', '{{%didox_document}}');
        $this->dropIndex('idx-didox_document-didox_id', '{{%didox_document}}');
        
        // Drop table
        $this->dropTable('{{%didox_document}}');
    }
} 