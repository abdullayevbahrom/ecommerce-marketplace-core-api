<?php

use yii\db\Migration;

/**
 * Add status management field to product_review table
 */
class m240115_000000_add_status_to_product_review extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        // Add status field to product_review table
        $this->addColumn('{{%product_review}}', 'status', $this->integer()->notNull()->defaultValue(0)->comment('Review status: 0=pending, 1=accepted, 2=rejected, 3=processed'));
        
        // Add status date tracking
        $this->addColumn('{{%product_review}}', 'status_date', $this->dateTime()->null()->comment('When status was last changed'));
        
        // Add admin user who changed status
        $this->addColumn('{{%product_review}}', 'status_user_id', $this->integer()->null()->comment('Admin user who changed status'));
        
        // Add status comment for rejection reasons etc
        $this->addColumn('{{%product_review}}', 'status_comment', $this->text()->null()->comment('Admin comment for status change'));
        
        // Create indexes for better performance
        $this->createIndex('idx-product_review-status', '{{%product_review}}', 'status');
        $this->createIndex('idx-product_review-status_date', '{{%product_review}}', 'status_date');
        $this->createIndex('idx-product_review-status_user_id', '{{%product_review}}', 'status_user_id');
        
        // Add foreign key for status_user_id
        $this->addForeignKey(
            'fk-product_review-status_user_id',
            '{{%product_review}}',
            'status_user_id',
            '{{%user}}',
            'id',
            'SET NULL'
        );
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        // Drop foreign key
        $this->dropForeignKey('fk-product_review-status_user_id', '{{%product_review}}');
        
        // Drop indexes
        $this->dropIndex('idx-product_review-status_user_id', '{{%product_review}}');
        $this->dropIndex('idx-product_review-status_date', '{{%product_review}}');
        $this->dropIndex('idx-product_review-status', '{{%product_review}}');
        
        // Drop columns
        $this->dropColumn('{{%product_review}}', 'status_comment');
        $this->dropColumn('{{%product_review}}', 'status_user_id');
        $this->dropColumn('{{%product_review}}', 'status_date');
        $this->dropColumn('{{%product_review}}', 'status');
    }
} 