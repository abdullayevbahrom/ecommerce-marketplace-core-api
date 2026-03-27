<?php

use yii\db\Migration;

/**
 * Class m251214_000002_add_didox_eimzo_settings
 * 
 * SQL to execute manually:
 * 
 * INSERT INTO `settings` (`type`, `content`, `date`) VALUES
 * ('didox_eimzo_tax_id', '', NOW()),
 * ('didox_eimzo_token', '', NOW()),
 * ('didox_eimzo_last_login', '', NOW()),
 * ('didox_eimzo_certificate', '', NOW()),
 * ('didox_pfx_path', '', NOW()),
 * ('didox_pfx_password', '', NOW()),
 * ('didox_signer_url', 'http://eimzo-signer:8080/generate', NOW());
 */
class m251214_000002_add_didox_eimzo_settings extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $settings = [
            ['didox_eimzo_tax_id', ''],
            ['didox_eimzo_token', ''],
            ['didox_eimzo_last_login', ''],
            ['didox_eimzo_certificate', ''], // Will store JSON if needed
            ['didox_pfx_path', ''],
            ['didox_pfx_password', ''],
            ['didox_signer_url', 'http://eimzo-signer:8080/generate']
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
            'didox_eimzo_tax_id',
            'didox_eimzo_token',
            'didox_eimzo_last_login',
            'didox_eimzo_certificate',
            'didox_pfx_path',
            'didox_pfx_password',
            'didox_signer_url'
        ];

        $this->delete('settings', ['type' => $types]);
    }
}
