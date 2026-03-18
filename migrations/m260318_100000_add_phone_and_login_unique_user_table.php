<?php

use yii\db\Migration;

/**
 * Manual SQL (run in phpMyAdmin or MySQL client):
 *
 * -- UP:
 * ALTER TABLE `user` ADD UNIQUE INDEX `idx_unique_phone` (`phone`) ADD UNIQUE INDEX `idx_unique_login` (`login`);
 *
 * -- DOWN:
 * ALTER TABLE `user` DROP INDEX `idx_unique_phone`;
 * ALTER TABLE `user` DROP INDEX `idx_unique_login`;
 *
 * -- After running UP, mark as applied:
 * INSERT INTO `migration` (`version`, `apply_time`) VALUES ('m260318_100000_add_phone_and_login_unique_user_table', UNIX_TIMESTAMP());
 */
class m260318_100000_add_phone_and_login_unique_user_table extends Migration
{
    public function safeUp()
    {
        $this->createIndex('idx_unique_phone', '{{%user}}', 'phone', true);
        $this->createIndex('idx_unique_login', '{{%user}}', 'login', true);
    }

    public function safeDown()
    {
        $this->dropIndex('idx_unique_phone', '{{%user}}');
        $this->dropIndex('idx_unique_login', '{{%user}}');
    }
}
