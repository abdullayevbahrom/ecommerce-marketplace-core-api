<?php

use yii\db\Migration;

class m260403_123500_allow_arbitrary_document_type_in_didox_document extends Migration
{
    public function safeUp()
    {
        $this->execute(
            "ALTER TABLE `didox_document` " .
            "MODIFY COLUMN `document_type` " .
            "ENUM('invoice','arbitrary') NOT NULL DEFAULT 'invoice' " .
            "COMMENT 'Document type (invoice or arbitrary contract)'"
        );
    }

    public function safeDown()
    {
        $this->execute(
            "ALTER TABLE `didox_document` " .
            "MODIFY COLUMN `document_type` " .
            "ENUM('invoice') NOT NULL DEFAULT 'invoice' " .
            "COMMENT 'Document type (invoice for Счёт фактура)'"
        );
    }
}
