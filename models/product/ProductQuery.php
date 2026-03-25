<?php

namespace app\models\product;

use app\models\stock\Stock;
use yii\db\ActiveQuery;

class ProductQuery extends ActiveQuery
{
    public function marketplaceVisible(string $productAlias = 'product'): self
    {
        return $this->andWhere([
            'or',
            ["{$productAlias}.stock_id" => null],
            [
                'in',
                "{$productAlias}.stock_id",
                Stock::find()->select('id')->where(['for_marketplace' => 1]),
            ],
        ]);
    }
}
