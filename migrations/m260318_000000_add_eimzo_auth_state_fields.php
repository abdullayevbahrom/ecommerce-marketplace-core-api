<?php

use yii\db\Migration;

/**
 * Add eimzo_auth_completed and didox_auth_completed fields to user table.
 *
 * These fields track the two-step authentication flow:
 * Step 1: E-IMZO authentication (eimzo_auth_completed) — required for login
 * Step 2: Didox connection (didox_auth_completed) — optional, enriches profile
 *
 * Manual SQL (run in phpMyAdmin or MySQL client):
 *
 * -- UP:
 * ALTER TABLE `user` ADD COLUMN `eimzo_auth_completed` TINYINT(1) NOT NULL DEFAULT 0 AFTER `eimzo_last_login`;
 * ALTER TABLE `user` ADD COLUMN `didox_auth_completed` TINYINT(1) NOT NULL DEFAULT 0 AFTER `eimzo_auth_completed`;
 *
 * -- Backfill: mark users with didox token as both steps completed
 * UPDATE `user` SET `eimzo_auth_completed` = 1, `didox_auth_completed` = 1 WHERE `eimzo_didox_token` IS NOT NULL;
 *
 * -- Backfill: mark users with eimzo tax_id but no didox token as eimzo-only
 * UPDATE `user` SET `eimzo_auth_completed` = 1 WHERE `eimzo_tax_id` IS NOT NULL AND `eimzo_didox_token` IS NULL;
 *
 * -- DOWN:
 * ALTER TABLE `user` DROP COLUMN `didox_auth_completed`;
 * ALTER TABLE `user` DROP COLUMN `eimzo_auth_completed`;
 *
 * -- After running UP, mark as applied:
 * INSERT INTO `migration` (`version`, `apply_time`) VALUES ('m260318_000000_add_eimzo_auth_state_fields', UNIX_TIMESTAMP());
 */
class m260318_000000_add_eimzo_auth_state_fields extends Migration
{
    public function safeUp()
    {
        $this->addColumn('user', 'eimzo_auth_completed', $this->tinyInteger(1)->defaultValue(0)->after('eimzo_last_login'));
        $this->addColumn('user', 'didox_auth_completed', $this->tinyInteger(1)->defaultValue(0)->after('eimzo_auth_completed'));

        // Mark existing users who have eimzo_didox_token as having completed both steps
        $this->update('user', [
            'eimzo_auth_completed' => 1,
            'didox_auth_completed' => 1,
        ], ['IS NOT', 'eimzo_didox_token', null]);

        // Mark existing users who have eimzo_tax_id but no didox token as eimzo-only
        $this->update('user', [
            'eimzo_auth_completed' => 1,
        ], [
            'AND',
            ['IS NOT', 'eimzo_tax_id', null],
            ['IS', 'eimzo_didox_token', null],
        ]);
    }

    public function safeDown()
    {
        $this->dropColumn('user', 'didox_auth_completed');
        $this->dropColumn('user', 'eimzo_auth_completed');
    }
}
