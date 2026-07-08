<?php

use yii\db\Migration;

/**
 * Handles adding `files` to table `{{%image}}`.
 */
class m260602_100003_add_color_id_to_image extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('{{%image}}', 'color_id', $this->integer()->null());
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropColumn('{{%image}}', 'color_id');
    }
}
