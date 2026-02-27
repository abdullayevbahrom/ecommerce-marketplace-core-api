<?php

declare(strict_types=1);

namespace app\commands;

use yii\console\Controller;
use yii\console\ExitCode;
use yii\elasticsearch\Connection;
use yii\elasticsearch\Command;

class EsLabController extends Controller
{
    private Command $cmd;
    private Connection $es;

    public function __construct()
    {
        parent::__construct(...func_get_args());
        $this->es = \Yii::$app->elasticsearch;
        $this->cmd = $this->es->createCommand();
    }

    public function actionEnsureAll()
    {
        $registries = require \Yii::getAlias('@app/config/es-indexes.php');

        foreach ($registries as $index => $actions) {
            $index = (string)$actions['index'];
            $reIndex = (string)$actions['reindex'];
            \Yii::$app->runAction($index);
            $this->stdout("Ensured index '{$index}'\n");
            \Yii::$app->runAction($reIndex);
            $this->stdout("Reindexed '{$index}'\n");
        }

        $this->stdout("All done!\n");

        return ExitCode::OK;
    }

    public function actionSeedDemo()
    {
        $docId = 999999;

        $doc = [
            'id' => $docId,
            'shop_id' => 1,
            'category_id' => 10,
            'brand_id' => 7,
            'status' => 2,
            'price' => 123.45,
            'price_small' => 119.99,
            'price_opt' => 110.00,
            'discount' => 5.0,

            'name_uz' => 'iPhone 15 Pro Max',
            'name_ru' => 'Айфон 15 Про Макс',
            'name_en' => 'iPhone 15 Pro Max',

            'description_uz' => 'Original telefon, kafolat bor.',
            'description_ru' => 'Оригинальный телефон, гарантия есть.',
            'description_en' => 'Original phone with warranty.',

            'search_name' => 'iPhone 15 Pro Max Айфон 15 Про Макс iPhone 15 Pro Max',
            'search_desc' => 'Original telefon kafolat original phone warranty оригинальный телефон гарантия',

            'sku' => 'IP15PM-256-BLACK',
            'barcode' => '0123456789012',

            'updated_at' => date('c'),
        ];

        $this->cmd->insert('products', '_doc', $doc, $docId);

        $this->stdout("Seeded demo doc with id={$docId}\n");
        return 0;
    }

    /**
     * 2) MATCH query: faqat bitta field’da qidiradi
     */
    public function actionSearchMatch($q = 'iphone')
    {
        $body = [
            'size' => 5,
            'query' => [
                'match' => [
                    'search_name' => $q,
                ],
            ],
        ];

        $res = $this->es->post('products/_search', [], json_encode($body));

        $this->printHits($res);

        return 0;
    }

    /**
     * 3) MULTI_MATCH: bir nechta field’da qidiradi (+fuzziness)
     */
    public function actionSearchMulti($q = 'iphone')
    {
        $body = [
            'size' => 5,
            'query' => [
                'multi_match' => [
                    'query' => $q,
                    'fields' => [
                        'search_name^5',
                        'name_uz^4',
                        'name_ru^4',
                        'name_en^4',
                        'search_desc',
                        'sku^10',
                        'barcode^10',
                    ],
                    'type' => 'best_fields',
                    'fuzziness' => 'AUTO',
                ],
            ],
        ];

        $res = $this->es->post(['products', '_search'], [], json_encode($body));

        $this->printHits($res);
        return 0;
    }

    /**
     * 4) BOOL + FILTER: qidiruv (must) + filtrlash (filter)
     */
    public function actionSearchBool($q = 'iphone', $minPrice = 100, $maxPrice = 200)
    {
        $body = [
            'size' => 10,
            'query' => [
                'bool' => [
                    'must' => [
                        [
                            'multi_match' => [
                                'query' => $q,
                                'fields' => ['search_name^5', 'search_desc'],
                                'fuzziness' => 'AUTO',
                            ],
                        ],
                    ],
                    'filter' => [
                        ['term' => ['status' => 2]],
                        ['range' => ['price' => ['gte' => (float)$minPrice, 'lte' => (float)$maxPrice]]],
                    ],
                ],
            ],
            'sort' => [
                ['price' => 'asc'],
            ],
        ];

        $res = $this->es->post(['products', '_search'], [], json_encode($body));

        $this->printHits($res);
        return 0;
    }

    /**
     * 5) EXACT qidiruv: sku yoki barcode (keyword bo‘lishi kerak)
     */
    public function actionSearchExactSku($sku = 'IP15PM-256-BLACK')
    {
        $body = [
            'size' => 5,
            'query' => [
                'term' => [
                    'sku' => $sku,
                ],
            ],
        ];

        $res = $this->es->post(['products', '_search'], [], json_encode($body));

        $this->printHits($res);
        return 0;
    }

    /**
     * 6) Demo doc’ni o‘chirish
     */
    public function actionDeleteDemo()
    {
        $docId = 999999;

        $this->cmd->delete('products', '_doc', $docId);

        $this->stdout("Deleted demo doc id={$docId}\n");
        return 0;
    }

    private function printHits(array $res): void
    {
        $total = $res['hits']['total']['value'] ?? null;
        $hits = $res['hits']['hits'] ?? [];

        $this->stdout("Total: " . ($total === null ? 'unknown' : $total) . "\n");

        foreach ($hits as $h) {
            $src = $h['_source'] ?? [];
            $score = $h['_score'] ?? null;
            $this->stdout(
                "- id=" . ($src['id'] ?? '?') .
                    " | search_name=" . ($src['search_name'] ?? '') .
                    " | search_desc=" . ($src['search_desc'] ?? '') .
                    " | price=" . ($src['price'] ?? '') .
                    " | sku=" . ($src['sku'] ?? '') .
                    " | score=" . ($score !== null ? round($score, 2) : '?') .
                    "\n"
            );
        }
    }
}
