<?php

use yii\db\Migration;

/**
 * Class m251214_000001_add_didox_seller_settings
 * 
 * SQL to execute manually:
 * 
 * INSERT INTO `settings` (`type`, `content`, `date`) VALUES
 * ('didox_seller_inn', '123456789', NOW()),
 * ('didox_seller_name', '"MAIN TRADING HOLDING" MAS''ULIYATI CHEKLANGAN JAMIYAT', NOW()),
 * ('didox_seller_address', '', NOW()),
 * ('didox_seller_account', '', NOW()),
 * ('didox_seller_mfo', '', NOW());
 */
class m251214_000001_add_didox_seller_settings extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $settings = [
            ['didox_seller_inn', '123456789'],
            ['didox_seller_name', '"MAIN TRADING HOLDING" MAS\'ULIYATI CHEKLANGAN JAMIYAT'],
            ['didox_seller_address', ''],
            ['didox_seller_account', ''],
            ['didox_seller_mfo', '']
        ];

        foreach ($settings as $setting) {
            $this->insert('settings', [
                'type' => $setting[0],
                'content' => $setting[1],
                'date' => date('Y-m-d H:i:s')
            ]);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $types = [
            'didox_seller_inn',
            'didox_seller_name',
            'didox_seller_address',
            'didox_seller_account',
            'didox_seller_mfo'
        ];

        $this->delete('settings', ['type' => $types]);
    }
}
