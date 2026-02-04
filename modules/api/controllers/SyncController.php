<?php

namespace app\modules\api\controllers;

use Yii;
use yii\rest\Controller;
use yii\web\Response;
use app\models\product\Product;
use yii\helpers\ArrayHelper;

class SyncController extends Controller
{
    public function behaviors()
    {
        $behaviors = parent::behaviors();
        $behaviors['contentNegotiator']['formats']['application/json'] = Response::FORMAT_JSON;
        return $behaviors;
    }

    /**
     * Get pending products for synchronization
     * GET /api/sync/products/pending
     */

    public function actionPending($page = 1, $pageSize = 50)
    {
         $query = Product::find()
             ->orderBy(['id' => SORT_ASC]);

        $count = $query->count();

        $products = $query
            ->offset(($page - 1) * $pageSize)
            ->limit($pageSize)
            ->all();

         $data = [];
         foreach ($products as $product) {
             $item = $product->toArray();
             $item['sku'] = $product->sku;
             $item['barcode'] = $product->barcode;
             $item['ikpu_code'] = $product->ikpu_code;
             // Add related table IDs
             $item['user_id'] = $product->user_id;
             $item['category_id'] = $product->category_id;
             $item['brand_id'] = $product->brand_id;
             $item['shop_id'] = $product->shop_id;
             $item['stock_id'] = $product->stock_id;
             $item['region_id'] = $product->region_id;
             $item['currency_id'] = $product->currency_id;
             $item['unit_id'] = $product->unit_id;
             $item['color_id'] = $product->color_id;
             $item['delivery_id'] = $product->delivery_id;
             $item['product_relation_id'] = $product->product_relation_id;
             $item['office_id'] = $product->office_id;
             $item['tag_id'] = $product->tag_id;
             $data[] = $item;
        }

        return [
                'success' => true,
                'page' => $page,
                'pageSize' => $pageSize,
                'total' => $count,
                'hasMore' => ($page * $pageSize) < $count,
                'products' => $data,
            ];
    }

    /**
     * Confirm synchronization and update sklad_product_id
     * POST /api/sync/products/confirm
     */
    public function actionConfirm()
    {
        $request = Yii::$app->request;
        $mappings = $request->post('mappings', []); // Expecting [{'shop_id': 1, 'sklad_id': 100}, ...]

        $updatedCount = 0;
        $errors = [];

        foreach ($mappings as $mapping) {
            if (empty($mapping['shop_id']) || empty($mapping['sklad_id'])) {
                continue;
            }

            $product = Product::findOne($mapping['shop_id']);
            if ($product) {
                $product->sklad_product_id = $mapping['sklad_id'];
                $product->sync_status = 1; // Synced
                if ($product->save(false)) { // Skip validation to just update status fields
                    $updatedCount++;
                } else {
                    $errors[] = ['id' => $mapping['shop_id'], 'error' => $product->errors];
                }
            }
        }

        return [
            'success' => true,
            'updated' => $updatedCount,
            'errors' => $errors
        ];
    }
}

