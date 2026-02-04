<?php

use yii\db\Migration;

/**
 * Add to_user_id field to didox_document table for user assignment
 */
class m240101_000002_add_to_user_id_to_didox_document extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        // Add to_user_id field for document assignment to users for signing
        $this->addColumn('{{%didox_document}}', 'to_user_id', $this->integer()->null()->comment('User ID assigned to sign the document'));
        
        // Create index for better performance
        $this->createIndex('idx-didox_document-to_user_id', '{{%didox_document}}', 'to_user_id');
        
        // Add foreign key constraint
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
        // Drop foreign key
        $this->dropForeignKey('fk-didox_document-to_user_id', '{{%didox_document}}');
        
        // Drop index
        $this->dropIndex('idx-didox_document-to_user_id', '{{%didox_document}}');
        
        // Drop column
        $this->dropColumn('{{%didox_document}}', 'to_user_id');
    }
} 