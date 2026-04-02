<?php

declare(strict_types=1);

namespace app\commands;

use yii\console\Controller;
use app\models\filter\Filter;
use app\models\product\Product;
use app\models\product\ProductFilter;
use app\models\elasticsearch\ProductEs;
use app\models\order\product\OrderProduct;
use app\models\product\review\ProductReview;
use yii\elasticsearch\Command;
use yii\console\ExitCode;

class ReindexController extends Controller
{
    public function actionProducts($batch = 300)
    {
        /** @var Command $cmd */
        $cmd = \Yii::$app->elasticsearch->createCommand();
        $q = Product::find()->where(['deleted_at' => null]);

        $count = 0;

        foreach ($q->batch($batch) as $rows) {
            foreach ($rows as $p) {
                $docId = (int)$p->id;

                $nameUz = (string)($p->name_uz ?? '');
                $nameRu = (string)($p->name_ru ?? '');
                $nameEn = (string)($p->name_en ?? '');

                $descUz = (string)($p->description_uz ?? '');
                $descRu = (string)($p->description_ru ?? '');
                $descEn = (string)($p->description_en ?? '');

                $pfRows = ProductFilter::find()
                    ->alias('pf')
                    ->select(['pf.id', 'pf.filter_id', 'pf.value_ru', 'pf.value_en', 'pf.value_uz', 'f.code AS filter_code'])
                    ->leftJoin(['f' => Filter::tableName()], 'f.id = pf.filter_id')
                    ->where(['product_id' => $docId])
                    ->asArray()
                    ->all();

                $nestedFilters = [];
                foreach ($pfRows as $r) {
                    $nestedFilters[] = [
                        'filter_id' => (int)$r['filter_id'],
                        'filter_code' => (string)($r['filter_code'] ?? ''),
                        'pf_id'     => (int)$r['id'],
                        'value_ru'  => (string)($r['value_ru'] ?? ''),
                        'value_en'  => (string)($r['value_en'] ?? ''),
                        'value_uz'  => (string)($r['value_uz'] ?? ''),
                    ];
                }
                $ordersCount = (int)OrderProduct::find()->where(['product_id' => $docId])->count('DISTINCT id') ?? 0;
                $reviewsCount = (int)ProductReview::find()->where(['product_id' => $docId])->count('DISTINCT id') ?? 0;
                $avgRate = (float)ProductReview::find()->where(['product_id' => $docId])->average('rate') ?? 0.0;
                $goodReviewsCount = (int)ProductReview::find()->where(['product_id' => $docId])->andWhere(['>=', 'rate', 4])->count('DISTINCT id') ?? 0;

                $doc = [
                    'id' => $docId,

                    'shop_id' => $p->shop_id ? (int)$p->shop_id : null,
                    'category_id' => $p->category_id ? (int)$p->category_id : null,
                    'brand_id' => $p->brand_id ? (int)$p->brand_id : null,
                    'region_id' => $p->region_id ? (int)$p->region_id : null,
                    'currency_id' => $p->currency_id ? (int)$p->currency_id : null,
                    'tag_id' => $p->tag_id ? (int)$p->tag_id : null,
                    'views' => $p->views ? (int)$p->views : null,
                    'orders_count' => $ordersCount,
                    'reviews_count' => $reviewsCount,
                    'avg_rate' => $avgRate,
                    'good_reviews_count' => $goodReviewsCount,

                    'status' => (int)$p->status,
                    'deleted_at' => $p->deleted_at ? date('c', strtotime($p->deleted_at)) : null,

                    'price' => $p->price !== null ? (float)$p->price : null,
                    'price_small' => $p->price_small !== null ? (float)$p->price_small : null,
                    'price_opt' => $p->price_opt !== null ? (float)$p->price_opt : null,
                    'discount' => $p->discount !== null ? (float)$p->discount : null,
                    'amount' => $p->amount !== null ? (float)$p->amount : null,

                    'name_uz' => $nameUz,
                    'name_ru' => $nameRu,
                    'name_en' => $nameEn,

                    'description_uz' => $descUz,
                    'description_ru' => $descRu,
                    'description_en' => $descEn,

                    'search_name' => trim($nameUz . ' ' . $nameRu . ' ' . $nameEn),
                    'search_desc' => trim($descUz . ' ' . $descRu . ' ' . $descEn),

                    'sku' => $p->sku ? (string)$p->sku : null,
                    'barcode' => $p->barcode ? (string)$p->barcode : null,

                    'updated_at' => date('c', strtotime($p->date)),
                    'filters' => $nestedFilters,
                ];

                $cmd->insert(ProductEs::index(), '_doc', $doc, (string)$docId);
                $count++;
            }
            $this->stdout("Indexed: {$count}\n");
        }

        $this->stdout("DONE. Total indexed: {$count}\n");

        return ExitCode::OK;
    }
}
