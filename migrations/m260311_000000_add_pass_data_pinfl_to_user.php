<?php

use yii\db\Migration;

/**
 * Adds pass_data and pinfl columns to user table for MyID create-session flow.
 *
 * Manual SQL (run in phpMyAdmin or MySQL client):
 *
 * -- UP:
 * ALTER TABLE `user`
 *     ADD COLUMN `pinfl` VARCHAR(14) NULL COMMENT 'Personal ID number (14 digits)' AFTER `myid_verified`,
 *     ADD COLUMN `pass_data` VARCHAR(9) NULL COMMENT 'Passport series+number (e.g. AA1234567)' AFTER `pinfl`;
 *
 * CREATE INDEX `idx_user_pinfl` ON `user` (`pinfl`);
 *
 * -- DOWN:
 * DROP INDEX `idx_user_pinfl` ON `user`;
 * ALTER TABLE `user`
 *     DROP COLUMN `pass_data`,
 *     DROP COLUMN `pinfl`;
 *
 * -- After running UP, mark as applied:
 * INSERT INTO `migration` (`version`, `apply_time`) VALUES ('m260311_000000_add_pass_data_pinfl_to_user', UNIX_TIMESTAMP());
 */
class m260311_000000_add_pass_data_pinfl_to_user extends Migration
{
    public function safeUp()
    {
        $this->addColumn('{{%user}}', 'pinfl', $this->string(14)->null()->comment('Personal ID number (14 digits)')->after('myid_verified'));
        $this->addColumn('{{%user}}', 'pass_data', $this->string(9)->null()->comment('Passport series+number (e.g. AA1234567)')->after('pinfl'));

        $this->createIndex('idx_user_pinfl', '{{%user}}', 'pinfl');
    }

    public function safeDown()
    {
        $this->dropIndex('idx_user_pinfl', '{{%user}}');
        $this->dropColumn('{{%user}}', 'pass_data');
        $this->dropColumn('{{%user}}', 'pinfl');
    }
}
