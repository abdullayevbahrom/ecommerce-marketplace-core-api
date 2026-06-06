<?php

namespace app\models\product;

use app\models\stock\Stock;
use yii\db\ActiveQuery;

class ProductQuery extends ActiveQuery
{
    public function marketplaceVisible(string $productAlias = 'product'): self
    {
        return $this->andWhere([
            '>',
            "{$productAlias}.amount",
            0,
        ])->andWhere([
            'or',
            ["{$productAlias}.stock_id" => null],
            [
                'in',
                "{$productAlias}.stock_id",
                Stock::find()->select('id')->where(['for_marketplace' => 1]),
            ],
        ]);
    }

    public function publicVisible(string $productAlias = 'product'): self
    {
        return $this->andWhere([
            "{$productAlias}.status" => 1,
            "{$productAlias}.deleted_at" => null,
        ])->marketplaceVisible($productAlias);
    }

    public function activeMarketplaceVisible(string $productAlias = 'product'): self
    {
        return $this->publicVisible($productAlias);
    }
}
