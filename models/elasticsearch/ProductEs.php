<?php

declare(strict_types=1);

namespace app\models\elasticsearch;

use yii\elasticsearch\ActiveRecord;

class ProductEs extends ActiveRecord
{
    public static function index()
    {
        return 'products';
    }

    public function attributes()
    {
        return [
            'id',
            'shop_id',
            'stock_id',
            'category_id',
            'brand_id',
            'region_id',
            'currency_id',
            'status',
            'deleted_at',
            'price',
            'price_small',
            'price_opt',
            'discount',
            'amount',
            'name_uz',
            'name_ru',
            'name_en',
            'description_uz',
            'description_ru',
            'description_en',
            'search_name',
            'search_desc',
            'sku',
            'barcode',
            'updated_at',
            'tag_id',
            'views',
            'filters',
        ];
    }
}
