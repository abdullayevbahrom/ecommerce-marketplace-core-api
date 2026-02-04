<?php

use yii\db\Migration;

/**
 * Handles adding delivery_cost to table `{{%user_cart}}`.
 */
class m250129_000000_add_delivery_cost_to_user_cart extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('{{%user_cart}}', 'delivery_cost', $this->decimal(10,2)->defaultValue(0)->comment('Delivery cost for this cart item'));
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropColumn('{{%user_cart}}', 'delivery_cost');
    }
}

