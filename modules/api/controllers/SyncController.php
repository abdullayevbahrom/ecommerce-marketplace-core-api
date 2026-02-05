<?php

namespace app\modules\api\controllers;

use app\models\brand\CategoryBrand;
use app\models\Category;
use app\models\color\Color;
use app\models\filter\Filter;
use app\models\Ikpu;
use Yii;
use yii\rest\Controller;
use yii\web\Response;
use app\models\product\Product;
use app\models\product\ProductColor;
use app\models\product\ProductFilter;
use app\models\product\ProductProductType;
use app\models\product\ProductType;
use app\models\product\ProductTypeValue;
use app\models\shop\Shop;
use app\models\stock\Stock;
use app\models\user\User;
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

    public function actionUser()
    {
        $request = Yii::$app->request;

        $shopId = (int)$request->post('shop_id');
        $userId = (int)$request->post('id');
        $name = $request->post('name');
        $phone = $request->post('phone');

        if (!$shopId) {
            Yii::$app->response->statusCode = 422;
            return ['error' => 'shop_id is required'];
        }

        $shop = Shop::findOne($shopId);

        if (!$shop) {
            Yii::$app->response->statusCode = 422;
            return ['error' => 'shop not found'];
        }

        $created = 0;
        $updated = 0;
        $user = null;

        if ($userId) {
            $user = User::find()->where([
                'id' => $userId,
                'shop_id'  => $shop->id
            ])->one();
        }
        
        if (!$user) {
            $user = new User();
            $user->shop_id = $shop->id;
            $user->role = User::ROLE_USER;
            $user->name = $name;
            $user->phone = $phone;
            $user->source = User::SOURCE_SKLAD;
            $user->status = 1;
            $created++;
        } else {
            $updated++;
        }

        $user->name = $name;
        $user->phone = $phone;
        $user->save(false);

        return [
            'success' => true,
            'yii_user_id' => $user->id,
            'created' => $created,
            'updated' => $updated,
        ];
    }

    public function actionUserDelete()
    {
        $request = Yii::$app->request;

        $shopId = (int) $request->post('shop_id');
        $userId = (int) $request->post('id');

        if (!$shopId || !$userId) {
            Yii::$app->response->statusCode = 422;
            return ['error' => 'shop_id and id are required'];
        }

        $user = User::find()->where([
            'id' => $userId,
            'shop_id' => $shopId,
        ])->one();

        if (!$user) {
            Yii::$app->response->statusCode = 404;
            return ['error' => 'User not found'];
        }

        $user->status = User::STATUS_INACTIVE;
        $user->deleted_at = date('Y-m-d H:i:s');

        $user->save(false);
    }

    public function actionStock()
    {
        $request = Yii::$app->request;

        $shopId = (int)$request->post('shop_id');
        $stockId = (int)$request->post('id');
        $name_ru = $request->post('name_ru');
        $address = $request->post('address');

        if (!$shopId) {
            Yii::$app->response->statusCode = 422;
            return ['error' => 'shop_id is required'];
        }

        $shop = Shop::findOne($shopId);

        if (!$shop) {
            Yii::$app->response->statusCode = 422;
            return ['error' => 'shop not found'];
        }

        $created = 0;
        $updated = 0;
        $stock = null;

        if ($stockId) {
            $stock = Stock::find()->where([
                'id' => $stockId,
                'shop_id'  => $shop->id
            ])->one();
        }
        
        if (!$stock) {
            $stock = new Stock();
            $stock->shop_id = Yii::$app->request->post('shop_id');
            $stock->name_ru = Yii::$app->request->post('name_ru');
            $stock->address = Yii::$app->request->post('address');
            $stock->status = 0;
            $created++;
        } else {
            $updated++;
        }

        $stock->name_ru = $name_ru;
        $stock->address = $address;

        $stock->save(false);

        return [
            'id' => $stock->id
        ];  
    }

    public function actionStockDelete()
    {
        $request = Yii::$app->request;

        $shopId = (int) $request->post('shop_id');
        $stockId = (int) $request->post('id');

        if (!$shopId || !$stockId) {
            Yii::$app->response->statusCode = 422;
            return ['error' => 'shop_id and id are required'];
        }

        $stock = Stock::find()->where([
            'id' => $stockId,
            'shop_id' => $shopId,
        ])->one();

        if (!$stock) {
            Yii::$app->response->statusCode = 404;
            return ['error' => 'User not found'];
        }

        $stock->status = 0;
        $stock->deleted_at = date('Y-m-d H:i:s');

        $stock->save(false);
    }

    public function actionProduct()
    {
        $request = Yii::$app->request;

        $shopId = (int)$request->post('shop_id');
        $productId = $request->post('id'); // yii_product_id
        $userId = (int)$request->post('user_id');
        $stockId = (int)$request->post('stock_id');
        $tokenKey = $request->post('token_key');

        $categoryId = $request->post('category_id');
        $brandId = $request->post('brand_id');
        $colorId = $request->post('color_id');


        if (!$shopId && !$userId && !$tokenKey) {
            Yii::$app->response->statusCode = 422;
            return ['error' => 'shop_id and user_id and t_key are required'];
        }

        $shop = Shop::findOne($shopId);
        if (!$shop) {
            Yii::$app->response->statusCode = 404;
            return ['error' => 'Shop not found'];
        }

        $stock = Stock::findOne($stockId);
        if (!$stock) {
            Yii::$app->response->statusCode = 404;
            return ['error' => 'Stock not found'];
        }

        $category = Category::findOne($categoryId);
        if (!$category) {
            Yii::$app->response->statusCode = 404;
            return ['error' => 'category not found'];
        }

        $brand = CategoryBrand::findOne($brandId);
        if (!$brand) {
            Yii::$app->response->statusCode = 404;
            return ['error' => 'brand not found'];
        }

        $color = Color::findOne($colorId);
        if (!$color) {
            Yii::$app->response->statusCode = 404;
            return ['error' => 'color not found'];
        }

        $product = Product::find()->where([
            'id'   => $productId,
            'token_key' => $tokenKey,
        ])
        ->one();

        $created = 0;
        $updated = 0;

        /** @var Product $product */
        if ($product) {
            // UPDATE
            $updated++;
        } else {
            // CREATE
            $product = new Product();
            $product->shop_id = $shop->id;
            $product->user_id = $userId;
            $product->stock_id = $stock->id;
            $product->token_key = $tokenKey;
            $product->status = 2;
            $created++;
        }

        $product->name_ru     = $request->post('name_ru');
        $product->name_en     = $request->post('name_en');
        $product->name_uz     = $request->post('name_uz');

        $product->description_ru = $request->post('description_ru');
        $product->description_en = $request->post('description_en');
        $product->description_uz = $request->post('description_uz');

        $product->price       = (float)$request->post('price', 0);
        $product->barcode     = $request->post('barcode');
        $product->sku         = $request->post('sku');

        $product->category_id = $category->id;
        $product->brand_id    = $brand->id;
        $product->color_id    = $color->id;
        $product->sync_status = 1;

        // $product->status      = (int)$request->post('status', 1);

        if (!$product->save(false)) {
            Yii::$app->response->statusCode = 500;
            return [
                'error' => 'Failed to save product',
                'details' => $product->errors,
            ];
        }

        $colors = (array)$request->post('color', []);
        ProductColor::deleteAll(['product_id' => $product->id]);

        foreach ($colors as $color_id) {
            if ($color_id) {
                
                $color = Color::findOne($color_id);
                if (!$color) {
                    return ['error' => 'color not found on ProductColor'];
                } 

                (new ProductColor([
                    'product_id' => $product->id,
                    'color_id'   => $color->id,
                ]))->save(false);
            }
        }

        $types = (array)$request->post('product_types', []);
        ProductProductType::deleteAll(['product_id' => $product->id]);

        foreach ($types as $pt) {

            $prTypeValue = ProductTypeValue::find()->where(['id' =>$pt['productTypeValue']['id']])->one();

            if (!$prTypeValue) {
                return ['error' => 'product type and pr value not found'];
            }

            (new ProductProductType([
                'product_id' => $product->id,
                'product_type_id' => $prTypeValue->product_type_id,
                'product_type_value_id' => $prTypeValue->id ?? null,
            ]))->save(false);
        }


        $filters = (array)$request->post('filters', []);
        ProductFilter::deleteAll(['product_id' => $product->id]);

        foreach ($filters as $f) {
            $prFilter = Filter::find()->where(['id' => $f['filter']['id']])->one();

            (new ProductFilter([
                'product_id' => $product->id,
                'filter_id'  => $prFilter->id,
                'value_id'   => $f['value_id'] ?? null,
                'value_ru'   => $f['value_ru'] ?? null,
                'value_en'   => $f['value_en'] ?? null,
                'value_uz'   => $f['value_uz'] ?? null,
            ]))->save(false);
        }

        return [
            'success' => true,
            'id' => $product->id,
            'yii_color_id' => $product->color_id,
            'created' => $created,
            'updated' => $updated,
        ];
    }

    public function actionProductDelete()
    {
        $request = Yii::$app->request;

        $shopId = (int)$request->post('shop_id');
        $productId = (int)$request->post('id');

        if (!$shopId || !$productId) {
            Yii::$app->response->statusCode = 422;
            return ['error' => 'shop_id and id are required'];
        }

        $exists = Product::findOne(['id' => $productId, 'shop_id' => $shopId]);

        if (!$exists) {
            Yii::$app->response->statusCode = 404;
            return ['error' => 'Product not found'];
        }
        
        $exists->softDelete();

        return [
            'success' => true,
            'yii_product_id' => $productId,
        ];
    }


    public function actionFilter()
    {
        $request = Yii::$app->request;

        $filterId   = (int) $request->post('id'); // yii_filter_id
        $categoryId = (int) $request->post('category_id');

        if (!$categoryId) {
            Yii::$app->response->statusCode = 422;
            return ['error' => 'category_id is required'];
        }

        /** @var Filter $filter */
        if ($filterId) {
            // UPDATE
            $filter = Filter::findOne($filterId);

            if (!$filter) {
                Yii::$app->response->statusCode = 404;
                return ['error' => 'Filter not found'];
            }

            $created = 0;
            $updated = 1;
        } else {
            // CREATE
            $filter = new Filter();
            $filter->parent_id = 0;
            $filter->status    = 2; // pending
            $created = 1;
            $updated = 0;
        }

        // ===== MAIN FILTER =====
        $filter->category_id = $categoryId;
        $filter->type        = $request->post('type');
        $filter->name_ru     = $request->post('name_ru');
        $filter->name_en     = $request->post('name_en');
        $filter->name_uz     = $request->post('name_uz');
        $filter->status      = (int) $request->post('status', $filter->status);

        if (!$filter->save(false)) {
            Yii::$app->response->statusCode = 500;
            return [
                'error'   => 'Failed to save filter',
                'details' => $filter->errors,
            ];
        }

        // ===== VALUES =====
        $values = (array) $request->post('values', []);

        Filter::deleteAll(['parent_id' => $filter->id]);

        if (!empty($values) && in_array($filter->type, ['select', 'checkbox'])) {
            foreach ($values as $value) {
                if (empty($value['name_ru'])) {
                    continue;
                }

                $child = new Filter();
                $child->parent_id   = $filter->id;
                $child->category_id = $categoryId;
                $child->type        = $filter->type;
                $child->status      = 1;

                $child->name_ru = $value['name_ru'];
                $child->name_en = $value['name_en'] ?? null;
                $child->name_uz = $value['name_uz'] ?? null;

                $child->save(false);
            }
        }

        return [
            'success' => true,
            'id'      => $filter->id,
            'created' => $created,
            'updated' => $updated,
        ];
    }


    public function actionFilterDelete()
    {
        $request = Yii::$app->request;
    
        $filterId = (int) $request->post('id');
    
        if (!$filterId) {
            Yii::$app->response->statusCode = 422;
            return ['error' => 'shop_id and id are required'];
        }
    
        $filter = Filter::find()
            ->where(['id' => $filterId])
            ->one();
    
        if (!$filter) {
            Yii::$app->response->statusCode = 404;
            return ['error' => 'Filter not found'];
        }
    
        // Если используется в товарах — деактивируем
        $inUse = ProductFilter::find()
            ->where(['filter_id' => $filterId])
            ->exists();
    
        $now = date('Y-m-d H:i:s');

        if ($inUse) {
            $filter->status = 0;
            $filter->deleted_at = $now;
            $filter->save(false);
    
            return [
                'success' => true,
                'message' => 'Filter in use, set to inactive',
            ];
        }

        $filter->status = 0;
        $filter->deleted_at = $now;
        $filter->save(false);
    
        // Иначе удаляем полностью
        Filter::updateAll(
        [
            'status' => 0,
            'deleted_at' => $now,
        ],
        ['parent_id' => $filter->id]
        );
    
        return [
            'success' => true,
            'message' => 'Filter deleted',
        ];
    }

    public function actionColor()
    {
        $request = Yii::$app->request;

        $id = $request->post('id'); // yii_color_id

        if ($id) {
            // UPDATE
            $color = Color::find()->where(['id' => $id])->one();
            if (!$color) {
                Yii::$app->response->statusCode = 404;
                return ['error' => 'Color not found'];
            }
        } else {
            // CREATE
            $color = new Color();
        }

        $color->name_ru = $request->post('name_ru');
        $color->name_en = $request->post('name_en');
        $color->name_uz = $request->post('name_uz');
        $color->color   = $request->post('color');

        if (!$color->save(false)) {
            Yii::$app->response->statusCode = 500;
            return [
                'error' => 'Failed to save color',
                'details' => $color->errors,
            ];
        }

        return [
            'success' => true,
            'id' => $color->id,
        ];
    }

    public function actionColorDelete()
    {
        $request = Yii::$app->request;

        $id = (int)$request->post('id');

        if (!$id) {
            Yii::$app->response->statusCode = 422;
            return ['error' => 'id are required'];
        }

        $color = Color::findOne($id);
        if (!$color) {
            Yii::$app->response->statusCode = 404;
            return ['error' => 'Color not found'];
        }

        $color->deleted_at = date('Y-m-d H:i:s');
        $color->save(false);

        return ['success' => true];
    }

    public function actionProductType()
    {
        $r = Yii::$app->request;
        $id = $r->post('id');

        $productType = $id
            ? ProductType::findOne($id)
            : new ProductType();

        if ($id && !$productType) {
            Yii::$app->response->statusCode = 404;
            return ['error' => 'ProductType not found'];
        }

        $productType->category_id = $r->post('category_id');
        $productType->type        = $r->post('type');

        $productType->name_ru = $r->post('name_ru');
        $productType->name_en = $r->post('name_en');
        $productType->name_uz = $r->post('name_uz');

        $productType->description_ru = $r->post('description_ru');
        $productType->description_en = $r->post('description_en');
        $productType->description_uz = $r->post('description_uz');

        $productType->status = (int)$r->post('status', 2);
        $productType->sort   = (int)$r->post('sort', 0);

        $productType->save(false);

        ProductTypeValue::deleteAll(['product_type_id' => $productType->id]);

        foreach ((array)$r->post('values', []) as $value) {
            $v = new ProductTypeValue();
            $v->product_type_id = $productType->id;
            $v->value_ru = $value['value_ru'];
            $v->value_en = $value['value_en'] ?? null;
            $v->value_uz = $value['value_uz'] ?? null;
            $v->display_value = $value['display_value'] ?? null;
            $v->sort = $value['sort'] ?? 0;
            $v->save(false);
        }

        return [
            'success' => true,
            'id' => $productType->id,
        ];
    }

    public function actionProductTypeDelete()
    {
        $id = (int)Yii::$app->request->post('id');

        $productType = ProductType::findOne($id);
        if (!$productType) {
            Yii::$app->response->statusCode = 404;
            return ['error' => 'ProductType not found'];
        }

        $productType->status = 0; // soft delete
        $productType->save(false);

        return ['success' => true];
    }

    public function actionCategory()
    {
        $request = Yii::$app->request;

        $id = (int)$request->post('id'); // yii_category_id (null = create)

        // ===== FIND OR CREATE =====
        if ($id) {
            $category = Category::findOne($id);
            if (!$category) {
                Yii::$app->response->statusCode = 404;
                return ['error' => 'Category not found'];
            }
        } else {
            $category = new Category();
            $category->status = 0; // default
        }

        // ===== ASSIGN FIELDS =====
        $category->parent_id = (int)$request->post('parent_id', 0);
        $category->name_ru   = $request->post('name_ru');
        $category->name_en   = $request->post('name_en');
        $category->name_uz   = $request->post('name_uz');

        $category->description_ru = $request->post('description_ru');
        $category->description_en = $request->post('description_en');
        $category->description_uz = $request->post('description_uz');

        $category->status = (int)$request->post(
            'status',
            $category->status ?? 0
        );

        $category->sort = (int)$request->post('sort', 0);

        // ===== SAVE =====
        if (!$category->save(false)) {
            Yii::$app->response->statusCode = 500;
            return [
                'error'   => 'Failed to save category',
                'details'=> $category->errors,
            ];
        }

        return [
            'success' => true,
            'id'      => $category->id, //  yii_category_id
        ];
    }


    public function actionCategoryDelete()
    {
        $request = Yii::$app->request;

        $id = (int)$request->post('id'); // yii_category_id

        if (!$id) {
            Yii::$app->response->statusCode = 422;
            return ['error' => 'id is required'];
        }

        $category = Category::findOne($id);
        if (!$category) {
            Yii::$app->response->statusCode = 404;
            return ['error' => 'Category not found'];
        }

        // ===== CHECK USAGE =====
        $inUse = Product::find()
            ->where(['category_id' => $id])
            ->exists();

        if ($inUse) {
            // soft deactivate
            $category->status = 0; // 0
            $category->save(false);

            return [
                'success' => true,
                'message' => 'Category in use, set to inactive',
            ];
        }

        // ===== SOFT DELETE =====
        $category->deleted_at = date('Y-m-d H:i:s');
        $category->status = 0;
        $category->save(false);

        return [
            'success' => true,
            'message' => 'Category soft-deleted',
        ];
    }


    public function actionBrand()
    {
        $request = Yii::$app->request;

        $id = (int)$request->post('id'); // yii_brand_id (null = create)

        if ($id) {
            // UPDATE
            $brand = CategoryBrand::findOne($id);
            if (!$brand) {
                Yii::$app->response->statusCode = 404;
                return ['error' => 'Brand not found'];
            }
        } else {
            // CREATE
            $brand = new CategoryBrand();
            $brand->status = 2; // pending by default
        }

        // ===== FIELDS =====
        $brand->category_id = $request->post('category_id');
        $brand->name_ru = $request->post('name_ru');
        $brand->name_en = $request->post('name_en');
        $brand->name_uz = $request->post('name_uz');

        $brand->description_ru = $request->post('description_ru');
        $brand->description_en = $request->post('description_en');
        $brand->description_uz = $request->post('description_uz');

        $brand->sort = (int)$request->post('sort', 0);

        $brand->status = (int)$request->post(
            'status',
            $brand->status
        );

        if (!$brand->save(false)) {
            Yii::$app->response->statusCode = 500;
            return [
                'error'   => 'Failed to save brand',
                'details'=> $brand->errors,
            ];
        }

        return [
            'success' => true,
            'id'      => $brand->id, // 
        ];
    }


    public function actionBrandDelete()
    {
        $request = Yii::$app->request;

        $id = (int)$request->post('id'); // yii_brand_id

        if (!$id) {
            Yii::$app->response->statusCode = 422;
            return ['error' => 'id is required'];
        }

        $brand = CategoryBrand::findOne($id);
        if (!$brand) {
            Yii::$app->response->statusCode = 404;
            return ['error' => 'Brand not found'];
        }

        // ===== CHECK USAGE =====
        $inUse = Product::find()
            ->where(['brand_id' => $id])
            ->exists();

        if ($inUse) {
            // Soft deactivate
            $brand->status = 0; // inactive
            $brand->save(false);

            return [
                'success' => true,
                'message' => 'Brand in use, set to inactive',
            ];
        }

        // ===== SOFT DELETE =====
        $brand->status = 3; // deleted
        $brand->deleted_at = date('Y-m-d H:i:s');
        $brand->save(false);

        return [
            'success' => true,
            'message' => 'Brand soft-deleted',
        ];
    }

    public function actionIkpu()
    {
        $request = Yii::$app->request;
    
        $code = $request->post('code');
        if (!$code) {
            Yii::$app->response->statusCode = 422;
            return ['error' => 'code is required'];
        }
    
        $ikpu = Ikpu::findOne(['code' => $code]);
        $created = false;
    
        if (!$ikpu) {
            $ikpu = new Ikpu();
            $ikpu->code = $code;
            $created = true;
        }
    
        // Prevent self-parent
        if ($request->post('parent_code') === $code) {
            Yii::$app->response->statusCode = 422;
            return ['error' => 'IKPU cannot be its own parent'];
        }
    
        $ikpu->name_ru     = $request->post('name_ru');
        $ikpu->name_en     = $request->post('name_en');
        $ikpu->name_uz     = $request->post('name_uz');
        $ikpu->parent_code = $request->post('parent_code');
        $ikpu->status      = (int) $request->post('status', 1);
    
        if (!$ikpu->save(false)) {
            Yii::$app->response->statusCode = 500;
            return [
                'error' => 'Failed to save IKPU',
                'details' => $ikpu->errors,
            ];
        }
    
        return [
            'success' => true,
            'id'      => $ikpu->id,
            'created' => $created,
        ];
    }

    public function actionIkpuDelete()
    {
        $code = Yii::$app->request->post('code');

        if (!$code) {
            Yii::$app->response->statusCode = 422;
            return ['error' => 'code is required'];
        }

        $ikpu = Ikpu::findOne(['code' => $code]);

        if (!$ikpu) {
            Yii::$app->response->statusCode = 404;
            return ['error' => 'IKPU not found'];
        }

        // If used by products → deactivate
        $inUse = Product::find()
            ->where(['ikpu_code' => $code])
            ->exists();

        if ($inUse) {
            $ikpu->status = 0;
            $ikpu->save(false);

            return [
                'success' => true,
                'message' => 'IKPU in use, set to inactive',
            ];
        }

        $ikpu->delete();

        return [
            'success' => true,
            'message' => 'IKPU deleted',
        ];
    }

    public function actionModeration()
    {
        $request = Yii::$app->request;

        $id           = (int) $request->post('id');
        $entityType   = $request->post('entity_type');
        $action       = $request->post('action');
        $statusAfter  = $request->post('status_after');
        $commentText  = $request->post('comment');
        $moderatorId  = (int) $request->post('moderator_id');

        if (!$id || !$entityType || !$action || !$moderatorId) {
            Yii::$app->response->statusCode = 422;
            return ['error' => 'Invalid payload'];
        }

        $secret = Yii::$app->params['apiSecretKey'];
        $token  = Yii::$app->request->headers->get('X-Api-Token');

        if (md5($id . $secret) !== $token) {
            Yii::$app->response->statusCode = 401;
            return ['error' => 'Unauthorized'];
        }

        switch ($entityType) {
            case 'product':
                $model = Product::findOne($id);
                break;
            
            case 'filter':
                $model = Filter::findOne($id);
                break;
            
            case 'color':
                $model = Color::findOne($id);
                break;
            
            case 'product-type':
                $model = ProductType::findOne($id);
                break;
            
            default:
            $model = null;
        }

        if (!$model) {
            Yii::$app->response->statusCode = 404;
            return ['error' => 'Entity not found'];
        }

        $comment = new \app\models\moderator\ModerationComment();
        $comment->entity_type  = $entityType;
        $comment->entity_id    = $model->id;
        $comment->action       = $action;
        $comment->comment      = $commentText;
        $comment->moderator_id = $moderatorId;
        $comment->save(false);

        if ($statusAfter !== null) {
            $this->applyStatus($model, $statusAfter);
        }

        return ['success' => true];
    }


    public function actionModerationComment()
    {
        $request = Yii::$app->request;

        $id           = (int) $request->post('id');
        $entityType   = $request->post('entity_type');
        $action       = $request->post('action');
        $statusAfter  = $request->post('status_after');
        $commentText  = $request->post('comment');
        $moderatorId  = (int) $request->post('moderator_id');

        if (!$id || !$entityType || !$action || !$moderatorId) {
            Yii::$app->response->statusCode = 422;
            return ['error' => 'Invalid payload'];
        }

        $secret = Yii::$app->params['apiSecretKey'];
        $token  = Yii::$app->request->headers->get('X-Api-Token');

        if (md5($id . $secret) !== $token) {
            Yii::$app->response->statusCode = 401;
            return ['error' => 'Unauthorized'];
        }

        switch ($entityType) {
            case 'product':
                $model = Product::findOne($id);
                break;
            
            case 'filter':
                $model = Filter::findOne($id);
                break;
            
            case 'color':
                $model = Color::findOne($id);
                break;
            
            case 'product_type':
                $model = ProductType::findOne($id);
                break;
            
            default:
            $model = null;
        }

        if (!$model) {
            Yii::$app->response->statusCode = 404;
            return ['error' => 'Entity not found'];
        }

        $comment = new \app\models\moderator\ModerationComment();
        $comment->entity_type  = $entityType;
        $comment->entity_id    = $model->id;
        $comment->action       = $action;
        $comment->comment      = $commentText;
        $comment->moderator_id = $moderatorId;
        $comment->save(false);

        if ($statusAfter) {
            $this->applyStatus($model, $statusAfter);
        }

        return ['success' => true];
    }

    protected function applyStatus($model, string $status)
    {
        if (!$model) {
            return;
        }

        $newStatus = ($status === 'approved') ? 1 : 2;

        if ($model instanceof Product) {
            $model->status = $newStatus;
        } elseif (
            $model instanceof Filter
        ) {
            $model->status = $newStatus;
        }elseif ($model instanceof ProductType){
            $model->status = ($status === 'approved') ? 1 : 0;
        }
        elseif($model instanceof Color) {
            $model->status = ($status === 'approved') ? 1 : 0;
        } else {
            return;
        }

        $model->save(false);
    }
}

