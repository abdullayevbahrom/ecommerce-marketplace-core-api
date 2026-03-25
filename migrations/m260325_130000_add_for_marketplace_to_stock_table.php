<?php

use yii\db\Migration;

class m260325_130000_add_for_marketplace_to_stock_table extends Migration
{
    public function safeUp()
    {
        $this->addColumn(
            'stock',
            'for_marketplace',
            $this->tinyInteger(1)->notNull()->defaultValue(0)->after('address')
        );
    }

    public function safeDown()
    {
        $this->dropColumn('stock', 'for_marketplace');
    }
}
