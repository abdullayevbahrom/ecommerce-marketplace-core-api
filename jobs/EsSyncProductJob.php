<?php

declare(strict_types=1);

namespace app\jobs;

use app\models\elasticsearch\ProductEs;
use yii\base\BaseObject;
use yii\queue\JobInterface;
use yii\helpers\Json;
use app\models\filter\Filter;
use app\models\product\Product;
use app\models\product\ProductFilter;

class EsSyncProductJob extends BaseObject implements JobInterface
{
    public int $productId;
    public string $action = 'upsert'; // upsert | delete

    public function execute($queue): void
    {
        $index = ProductEs::index();

        if ($this->action === 'delete') {
            \Yii::$app->elasticsearch->delete($index . '/_doc/' . $this->productId);
            return;
        }

        $p = Product::find()->where(['id' => $this->productId])->one();
        if (!$p) {
            \Yii::$app->elasticsearch->delete($index . '/_doc/' . $this->productId);
            return;
        }

        if ((int)$p->status !== 1 || $p->deleted_at !== null) {
            \Yii::$app->elasticsearch->delete($index . '/_doc/' . $this->productId);
            return;
        }

        $pfRows = ProductFilter::find()
            ->alias('pf')
            ->select(['pf.id', 'pf.filter_id', 'pf.value_ru', 'pf.value_en', 'pf.value_uz', 'f.code AS filter_code'])
            ->leftJoin(['f' => Filter::tableName()], 'f.id = pf.filter_id')
            ->where(['product_id' => (int)$p->id])
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

        $doc = [
            'id' => (int)$p->id,
            'shop_id' => (int)$p->shop_id,
            'category_id' => (int)$p->category_id,
            'brand_id' => (int)$p->brand_id,
            'region_id' => (int)$p->region_id,
            'currency_id' => (int)$p->currency_id,
            'tag_id' => (int)$p->tag_id,
            'views' => (int)$p->views,

            'status' => (int)$p->status,
            'deleted_at' => $p->deleted_at ? date('c', strtotime($p->deleted_at)) : null,

            'price' => (float)$p->price,
            'price_small' => (float)$p->price_small,
            'price_opt' => (float)$p->price_opt,
            'discount' => (float)$p->discount,
            'amount' => (float)$p->amount,

            'name_uz' => (string)$p->name_uz,
            'name_ru' => (string)$p->name_ru,
            'name_en' => (string)$p->name_en,

            'description_uz' => (string)$p->description_uz,
            'description_ru' => (string)$p->description_ru,
            'description_en' => (string)$p->description_en,

            'search_name' => trim(implode(' ', array_filter([
                $p->name_uz,
                $p->name_ru,
                $p->name_en,
                $p->name_trans_uz ?? null,
                $p->name_trans_ru ?? null,
                $p->name_trans_en ?? null
            ]))),
            'search_desc' => trim(implode(' ', array_filter([
                strip_tags((string)$p->description_uz),
                strip_tags((string)$p->description_ru),
                strip_tags((string)$p->description_en),
            ]))),

            'sku' => (string)$p->sku,
            'barcode' => (string)$p->barcode,

            'updated_at' => date('c'),

            'filters' => $nestedFilters,
        ];

        \Yii::$app->elasticsearch->put(
            $index . '/_doc/' . $this->productId,
            [],
            Json::encode($doc)
        );
    }
}
