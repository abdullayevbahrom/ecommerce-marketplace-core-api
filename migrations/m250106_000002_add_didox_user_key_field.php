<?php

use yii\db\Migration;

/**
 * Add didox_user_key field to core didox_document table
 * This field stores the user key for DIDOX authentication
 */
class m250106_000002_add_didox_user_key_field extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        // Add didox_user_key field to didox_document table
        $this->addColumn('{{%didox_document}}', 'didox_user_key', $this->string(255)->null()->comment('User key for DIDOX authentication')->after('didox_signed_at'));
        
        // Create index for better performance
        $this->createIndex('idx-didox_document-didox_user_key', '{{%didox_document}}', 'didox_user_key');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        // Drop index
        $this->dropIndex('idx-didox_document-didox_user_key', '{{%didox_document}}');
        
        // Drop column
        $this->dropColumn('{{%didox_document}}', 'didox_user_key');
    }
} 