<?php

namespace app\modules\api\controllers;

use Yii;
use yii\rest\Controller;
use yii\web\Response;
use yii\web\UnauthorizedHttpException;
use app\models\color\Color;
use app\models\product\ProductType;
use app\models\filter\Filter;
use app\models\Ikpu;
use app\models\Category;
use app\models\brand\CategoryBrand;
use app\models\user\User;
use app\models\shop\Shop;
use app\models\stock\Stock;
use app\models\Region;
use app\models\delivery\Delivery;
use app\models\office\Office;
use yii\filters\ContentNegotiator;

/**
 * ProductAttributeController handles READ-ONLY access for auxiliary product data
 * (Colors, Product Types, Filters, IKPU, Categories, Brands) for Sklad integration.
 */
class ProductAttributeController extends Controller
{
    public $enableCsrfValidation = false;

    public function behaviors()
    {
        // $behaviors = parent::behaviors();
        // $behaviors['contentNegotiator']['formats']['application/json'] = Response::FORMAT_JSON;
        // return $behaviors;
        return [
            'contentNegotiator' => [
                'class' => ContentNegotiator::class,
                'formats' => [
                    'application/json' => Response::FORMAT_JSON,
                ],
            ],
        ];
    }

    /**
     * Check authentication token
     * Headers: X-Api-Token (md5 hash of branch_id + apiSecretKey)
     */
    protected function checkAuth()
    {
        // Authentication temporarily disabled for testing
        return true;

        /*
        $headers = Yii::$app->request->headers;
        $token = $headers->get('X-Api-Token');
        
        if (!$token) {
            throw new UnauthorizedHttpException('Missing X-Api-Token header');
        }
        
        $branchId = $headers->get('X-Branch-ID');
        if (!$branchId) {
            throw new UnauthorizedHttpException('Missing X-Branch-ID header');
        }
        
        $expectedToken = md5($branchId . Yii::$app->params['apiSecretKey']);
        if ($token !== $expectedToken) {
            throw new UnauthorizedHttpException('Invalid API Token');
        }
        */
    }

    /**
     * Apply updated_since filter to query
     * @param \yii\db\ActiveQuery $query
     */
    protected function applySyncFilter($query)
    {
        $updatedSince = Yii::$app->request->get('updated_since');
        if (!$updatedSince) {
            return;
        }
        $modelClass = $query->modelClass;
        $tableName = $modelClass::tableName();
        $schema     = Yii::$app->db->schema->getTableSchema($tableName);

        if ($schema->getColumn('updated_at')) {
            $query->andWhere(['>=', "$tableName.updated_at", $updatedSince]);
        }
    }

    // =========================================================================
    // COLOR ACTIONS
    // =========================================================================

    /**
     * Get list of Colors
     * GET /api/product-attribute/color
     */
    public function actionColorList($page = 1, $pageSize = 50)
    {
        $this->checkAuth();

        $query = Color::find()->asArray();
        $this->applySyncFilter($query);

        return $this->paginate($query, $page, $pageSize);
    }

    // =========================================================================
    // CATEGORY ACTIONS
    // =========================================================================

    /**
     * Get list of Categories
     * GET /api/product-attribute/category
     */
    public function actionCategoryList($page = 1, $pageSize = 50)
    {
        $this->checkAuth();

        $query = Category::find()->where(['type' => 'product'])->asArray();
        $this->applySyncFilter($query);

        return $this->paginate($query, $page, $pageSize);
    }

    /**
     * Get list of Units
     * GET /api/product-attribute/unit
     */
    public function actionUnitList($page = 1, $pageSize = 50)
    {
        $this->checkAuth();

        $query = Category::find()->where(['type' => 'unit'])->asArray();
        $this->applySyncFilter($query);

        return $this->paginate($query, $page, $pageSize);
    }

    /**
     * Get list of Units
     * GET /api/product-attribute/unit
     */
    public function actionCurrencyList($page = 1, $pageSize = 50)
    {
        $this->checkAuth();

        $query = Category::find()->where(['type' => 'currency'])->asArray();
        $this->applySyncFilter($query);

        return $this->paginate($query, $page, $pageSize);
    }

    /**
     * Get list of Tags
     * GET /api/product-attribute/tag
     */
    public function actionTagList($page = 1, $pageSize = 50)
    {
        $this->checkAuth();

        $query = Category::find()->where(['type' => 'tag'])->asArray();
        $this->applySyncFilter($query);

        return $this->paginate($query, $page, $pageSize);
    }

    // =========================================================================
    // BRAND ACTIONS
    // =========================================================================

    /**
     * Get list of Brands
     * GET /api/product-attribute/brand
     */
    public function actionBrandList($page = 1, $pageSize = 50)
    {
        $this->checkAuth();

        $query = CategoryBrand::find()->where(['status' => 1])->asArray();
        $this->applySyncFilter($query);

        if ($catId = Yii::$app->request->get('category_id')) {
            $query->andWhere(['category_id' => $catId]);
        }

        return $this->paginate($query, $page, $pageSize);
    }

    // =========================================================================
    // PRODUCT TYPE ACTIONS
    // =========================================================================

    /**
     * Get list of Product Types
     * GET /api/product-attribute/product-type
     */
    public function actionProductTypeList($page = 1, $pageSize = 50)
    {
        $query = ProductType::find()->with('productTypeValues');
        //$this->applySyncFilter($query);
        $count = $query->count();

        $items = $query
            ->orderBy(['id' => SORT_ASC])
            ->offset(($page - 1) * $pageSize)
            ->limit($pageSize)
            ->asArray()
            ->all();

        return [
            'success' => true,
            'page' => $page,
            'pageSize' => $pageSize,
            'total' => $count,
            'hasMore' => ($page * $pageSize) < $count,
            'items' => $items,
        ];
    }

    // =========================================================================
    // FILTER ACTIONS
    // =========================================================================

    /**
     * Get list of Filters
     * GET /api/product-attribute/filter
     */
    public function actionFilterList($page = 1, $pageSize = 50)
    {
        $this->checkAuth();
        // Only fetch parents (admin panel uses parent_id=0 for root)
        $query = Filter::find()->where(['or', ['parent_id' => null], ['parent_id' => 0]])->with('childs')->asArray();
        $this->applySyncFilter($query);

        if ($catId = Yii::$app->request->get('category_id')) {
            // Include filters from parent category as well (inheritance)
            $categoryIds = [$catId];
            $category = Category::findOne($catId);

            if ($category && $category->parent_id) {
                $categoryIds[] = $category->parent_id;
            }

            $query->andWhere(['category_id' => $categoryIds]);
        }

        return $this->paginate($query, $page, $pageSize);
    }

    // =========================================================================
    // IKPU ACTIONS
    // =========================================================================

    /**
     * Get list of IKPU codes
     * GET /api/product-attribute/ikpu
     */
    public function actionIkpuList($page = 1, $pageSize = 50)
    {
        $this->checkAuth();

        $query = Ikpu::find()->asArray();
        $this->applySyncFilter($query);

        // Optional search by code or name
        if ($q = Yii::$app->request->get('q')) {
            $query->andWhere([
                'or',
                ['like', 'code', $q],
                ['like', 'name_ru', $q],
                ['like', 'name_uz', $q],
                ['like', 'name_en', $q]
            ]);
        }

        return $this->paginate($query, $page, $pageSize);
    }

    // =========================================================================
    // USER ACTIONS
    // =========================================================================

    /**
     * Get list of Users
     * GET /api/product-attribute/user
     */
    public function actionUserList($page = 1, $pageSize = 50)
    {
        $this->checkAuth();

        $query = User::find()->andWhere([
            'role' => [
                User::ROLE_ADMIN,
                User::ROLE_MODERATOR,
                User::ROLE_SHOP,
            ]
        ]);

        $this->applySyncFilter($query);

        return $this->paginate($query, $page, $pageSize);
    }

    // =========================================================================
    // SHOP ACTIONS
    // =========================================================================

    /**
     * Get list of Shops
     * GET /api/product-attribute/shop
     */
    public function actionShopList($page = 1, $pageSize = 50)
    {
        $this->checkAuth();

        $query = Shop::find()->asArray();
        $this->applySyncFilter($query);

        return $this->paginate($query, $page, $pageSize);
    }

    // =========================================================================
    // STOCK ACTIONS
    // =========================================================================

    /**
     * Get list of Stocks
     * GET /api/product-attribute/stock
     */
    public function actionStockList($page = 1, $pageSize = 50)
    {
        $shopId = (int) Yii::$app->request->get('shop_id');
        if (!$shopId) {
            Yii::$app->response->statusCode = 422;
            return ['error' => 'shop_id required'];
        }

        $this->checkAuth();

        // $query = Stock::find()->where(['shop_id' => $shopId])->andWhere(['deleted_at' => null])->asArray();
        $query = Stock::find()
            ->select([
                'stock.*',
                'user.name AS username',
            ])
            ->leftJoin('shop', 'shop.id = stock.shop_id')
            ->leftJoin('user', 'user.id = shop.user_id')
            ->where(['stock.shop_id' => $shopId])
            ->andWhere(['stock.deleted_at' => null])
            ->asArray();

        $this->applySyncFilter($query);

        return $this->paginate($query, $page, $pageSize);
    }

    // =========================================================================
    // REGION ACTIONS
    // =========================================================================

    /**
     * Get list of Regions
     * GET /api/product-attribute/region
     */
    public function actionRegionList($page = 1, $pageSize = 50)
    {
        $this->checkAuth();

        $query = Region::find()->asArray();
        $this->applySyncFilter($query);

        return $this->paginate($query, $page, $pageSize);
    }

    // =========================================================================
    // DELIVERY ACTIONS
    // =========================================================================

    /**
     * Get list of Deliveries
     * GET /api/product-attribute/delivery
     */
    public function actionDeliveryList($page = 1, $pageSize = 50)
    {
        $this->checkAuth();

        $query = Delivery::find()->asArray();
        $this->applySyncFilter($query);

        return $this->paginate($query, $page, $pageSize);
    }

    // =========================================================================
    // OFFICE ACTIONS
    // =========================================================================

    /**
     * Get list of Offices
     * GET /api/product-attribute/office
     */
    public function actionOfficeList($page = 1, $pageSize = 50)
    {
        $this->checkAuth();

        $query = Office::find()->asArray();
        $this->applySyncFilter($query);

        return $this->paginate($query, $page, $pageSize);
    }

    protected function paginate(\yii\db\ActiveQuery $query, int $page, int $pageSize)
    {
        $count = (clone $query)->count();

        $items = $query
            ->orderBy(['id' => SORT_ASC])
            ->offset(($page - 1) * $pageSize)
            ->limit($pageSize)
            ->asArray()
            ->all();

        return [
            'success'  => true,
            'page'     => $page,
            'pageSize' => $pageSize,
            'total'    => $count,
            'hasMore'  => ($page * $pageSize) < $count,
            'items'    => $items,
        ];
    }
}
