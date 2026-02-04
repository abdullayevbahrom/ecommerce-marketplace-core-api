<?php

use yii\db\Migration;

/**
 * Add didox_doc_type column to didox_document table
 * This column stores the document type code sent to DIDOX API
 */
class m250106_000002_add_didox_doc_type_to_didox_document extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        // Add didox_doc_type column
        $this->addColumn('{{%didox_document}}', 'didox_doc_type', $this->string(25)->defaultValue('002')->comment('DIDOX document type code (002=invoice, 001=waybill, etc.)'));
        
        // Create index for better performance
        $this->createIndex('idx-didox_document-didox_doc_type', '{{%didox_document}}', 'didox_doc_type');
        
        // Set default value for existing records
        $this->update('{{%didox_document}}', ['didox_doc_type' => '002'], ['document_type' => 'invoice']);
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        // Drop index first
        $this->dropIndex('idx-didox_document-didox_doc_type', '{{%didox_document}}');
        
        // Drop column
        $this->dropColumn('{{%didox_document}}', 'didox_doc_type');
    }
} 