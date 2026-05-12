<?php

use yii\db\Migration;

class m260512_010000_create_missing_didox_document_signatures_table extends Migration
{
    public function safeUp()
    {
        $db = $this->db;
        $tableSchema = $db->getTableSchema('didox_document_signatures', true);
        if ($tableSchema !== null) {
            return;
        }

        $this->createTable('didox_document_signatures', [
            'id' => $this->primaryKey(),
            'didox_document_id' => $this->integer()->notNull(),
            'user_id' => $this->integer()->notNull(),
            'signer_tax_id' => $this->string(20)->notNull(),
            'signer_name' => $this->string(255)->null(),
            'signature_data' => 'LONGTEXT NOT NULL',
            'signature_type' => "ENUM('sign','accept','reject') DEFAULT 'sign'",
            'comment' => $this->text()->null(),
            'signed_at' => $this->dateTime()->notNull(),
            'certificate_serial' => $this->string(100)->null(),
            'certificate_valid_from' => $this->dateTime()->null(),
            'certificate_valid_to' => $this->dateTime()->null(),
            'didox_response' => $this->text()->null(),
            'created_at' => $this->timestamp()->defaultExpression('CURRENT_TIMESTAMP'),
        ], 'ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');

        $this->createIndex('idx_didox_sig_doc', 'didox_document_signatures', 'didox_document_id');
        $this->createIndex('idx_didox_sig_user', 'didox_document_signatures', 'user_id');

        $this->addForeignKey(
            'fk_didox_sig_doc',
            'didox_document_signatures',
            'didox_document_id',
            'didox_document',
            'id',
            'CASCADE',
            'CASCADE'
        );
        $this->addForeignKey(
            'fk_didox_sig_user',
            'didox_document_signatures',
            'user_id',
            'user',
            'id',
            'CASCADE',
            'CASCADE'
        );
    }

    public function safeDown()
    {
        $db = $this->db;
        $tableSchema = $db->getTableSchema('didox_document_signatures', true);
        if ($tableSchema === null) {
            return;
        }

        $this->dropForeignKey('fk_didox_sig_user', 'didox_document_signatures');
        $this->dropForeignKey('fk_didox_sig_doc', 'didox_document_signatures');
        $this->dropTable('didox_document_signatures');
    }
}

