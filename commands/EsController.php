<?php

declare(strict_types=1);

namespace app\commands;

use yii\console\Controller;
use yii\elasticsearch\Command;
use yii\elasticsearch\Connection;
use yii\console\ExitCode;

class EsController extends Controller
{
    private Command $cmd;
    private Connection $es;

    public function __construct()
    {
        parent::__construct(...func_get_args());
        $this->es = \Yii::$app->elasticsearch;
        $this->cmd = $this->es->createCommand();
    }

    public function actionCreateProductsIndex(): int
    {
        try {
            $this->cmd->deleteIndex('products');
        } catch (\Throwable $e) {
        }

        $this->cmd->createIndex('products', [
            'settings' => [
                'number_of_shards' => 1,
                'number_of_replicas' => 0,
                'analysis' => [
                    'analyzer' => [
                        'default' => [
                            'type' => 'standard',
                        ],
                    ],
                ],
            ],
            'mappings' => [
                'properties' => [
                    'id' => ['type' => 'integer'],

                    'shop_id' => ['type' => 'integer'],
                    'category_id' => ['type' => 'integer'],
                    'brand_id' => ['type' => 'integer'],
                    'region_id' => ['type' => 'integer'],
                    'currency_id' => ['type' => 'integer'],
                    'tag_id' => ['type' => 'integer'],
                    'views' => ['type' => 'integer'],

                    'status' => ['type' => 'integer'],
                    'deleted_at' => ['type' => 'date', 'ignore_malformed' => true],

                    'price' => ['type' => 'double'],
                    'price_small' => ['type' => 'double'],
                    'price_opt' => ['type' => 'double'],
                    'discount' => ['type' => 'double'],

                    'name_uz' => ['type' => 'text'],
                    'name_ru' => ['type' => 'text'],
                    'name_en' => ['type' => 'text'],

                    'description_uz' => ['type' => 'text'],
                    'description_ru' => ['type' => 'text'],
                    'description_en' => ['type' => 'text'],

                    'search_name' => ['type' => 'text'],
                    'search_desc' => ['type' => 'text'],

                    'sku' => ['type' => 'keyword'],
                    'barcode' => ['type' => 'keyword'],

                    'updated_at' => ['type' => 'date'],
                    'filters' => [
                        'type' => 'nested',
                        'properties' => [
                            'filter_id' => ['type' => 'integer'],
                            'pf_id' => ['type' => 'integer'],
                            'value_ru' => ['type' => 'keyword'],
                            'value_en' => ['type' => 'keyword'],
                            'value_uz' => ['type' => 'keyword'],
                        ],
                    ],
                ],
            ],
        ]);

        $this->stdout("products index created\n");

        return ExitCode::OK;
    }
}
