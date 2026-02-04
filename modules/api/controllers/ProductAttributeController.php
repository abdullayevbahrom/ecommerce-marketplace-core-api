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

/**
 * ProductAttributeController handles READ-ONLY access for auxiliary product data
 * (Colors, Product Types, Filters, IKPU, Categories, Brands) for Sklad integration.
 */
class ProductAttributeController extends Controller
{
    public $enableCsrfValidation = false;

    public function behaviors()
    {
        $behaviors = parent::behaviors();
        $behaviors['contentNegotiator']['formats']['application/json'] = Response::FORMAT_JSON;
        return $behaviors;
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
        if ($updatedSince) {
            // Getting the model class from the query
            $modelClass = $query->modelClass;
            $tableName = $modelClass::tableName();
            
            $query->andWhere([
                'or',
                ['>=', "$tableName.updated_at", $updatedSince],
                ['>=', "$tableName.deactivated_at", $updatedSince]
            ]);
        }
    }

    // =========================================================================
    // COLOR ACTIONS
    // =========================================================================

    /**
     * Get list of Colors
     * GET /api/product-attribute/color
     */
    public function actionColorList()
    {
        $this->checkAuth();
        
        $query = Color::find()->asArray();
        $this->applySyncFilter($query);
        
        return new \yii\data\ActiveDataProvider([
            'query' => $query,
            'pagination' => false,
            'sort' => ['defaultOrder' => ['id' => SORT_DESC]],
        ]);
    }

    // =========================================================================
    // CATEGORY ACTIONS
    // =========================================================================

    /**
     * Get list of Categories
     * GET /api/product-attribute/category
     */
    public function actionCategoryList()
    {
        $this->checkAuth();
        
        $query = Category::find()->where(['type' => 'product'])->asArray();
        $this->applySyncFilter($query);
        
        return new \yii\data\ActiveDataProvider([
            'query' => $query,
            'pagination' => false,
            'sort' => ['defaultOrder' => ['sort' => SORT_ASC, 'id' => SORT_ASC]],
        ]);
    }

    /**
     * Get list of Tags
     * GET /api/product-attribute/tag
     */
    public function actionTagList()
    {
        $this->checkAuth();
        
        $query = Category::find()->where(['type' => 'tag'])->asArray();
        $this->applySyncFilter($query);
        
        return new \yii\data\ActiveDataProvider([
            'query' => $query,
            'pagination' => true,
            'pageSize' => 100,
            'sort' => ['defaultOrder' => ['sort' => SORT_ASC, 'id' => SORT_ASC]],
        ]);
    }

    // =========================================================================
    // BRAND ACTIONS
    // =========================================================================

    /**
     * Get list of Brands
     * GET /api/product-attribute/brand
     */
    public function actionBrandList()
    {
        $this->checkAuth();
        
        $query = CategoryBrand::find()->where(['status' => 1])->asArray();
        $this->applySyncFilter($query);
        
        if ($catId = Yii::$app->request->get('category_id')) {
            $query->andWhere(['category_id' => $catId]);
        }

        return new \yii\data\ActiveDataProvider([
            'query' => $query,
            'pagination' => ['pageSize' => 100],
            'sort' => ['defaultOrder' => ['sort' => SORT_ASC, 'name_ru' => SORT_ASC]],
        ]);
    }

    // =========================================================================
    // PRODUCT TYPE ACTIONS
    // =========================================================================

    /**
     * Get list of Product Types
     * GET /api/product-attribute/product-type
     */
    public function actionProductTypeList()
    {
        $this->checkAuth();
        $query = ProductType::find()->with('productTypeValues')->asArray();
        $this->applySyncFilter($query);
        
        if ($catId = Yii::$app->request->get('category_id')) {
            $query->andWhere(['category_id' => $catId]);
        }
        
        return new \yii\data\ActiveDataProvider([
            'query' => $query,
            'pagination' => ['pageSize' => 50],
            'sort' => ['defaultOrder' => ['sort' => SORT_ASC]],
        ]);
    }

    // =========================================================================
    // FILTER ACTIONS
    // =========================================================================

    /**
     * Get list of Filters
     * GET /api/product-attribute/filter
     */
    public function actionFilterList()
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
        
        return new \yii\data\ActiveDataProvider([
            'query' => $query,
            'pagination' => ['pageSize' => 50],
            'sort' => ['defaultOrder' => ['id' => SORT_DESC]],
        ]);
    }

    // =========================================================================
    // IKPU ACTIONS
    // =========================================================================

    /**
     * Get list of IKPU codes
     * GET /api/product-attribute/ikpu
     */
    public function actionIkpuList()
    {
        $this->checkAuth();
        
        $query = Ikpu::find()->asArray();
        $this->applySyncFilter($query);
        
        // Optional search by code or name
        if ($q = Yii::$app->request->get('q')) {
             $query->andWhere(['or', 
                 ['like', 'code', $q],
                 ['like', 'name_ru', $q],
                 ['like', 'name_uz', $q],
                 ['like', 'name_en', $q]
             ]);
        }
        
        return new \yii\data\ActiveDataProvider([
            'query' => $query,
            'pagination' => ['pageSize' => 50],
            'sort' => ['defaultOrder' => ['code' => SORT_ASC]],
        ]);
    }

    // =========================================================================
    // USER ACTIONS
    // =========================================================================

    /**
     * Get list of Users
     * GET /api/product-attribute/user
     */
    public function actionUserList()
    {
        $this->checkAuth();
        
        $query = User::find();
        $this->applySyncFilter($query);

        return new \yii\data\ActiveDataProvider([
            'query' => $query,
            'pagination' => ['pageSize' => 50],
            'sort' => ['defaultOrder' => ['id' => SORT_DESC]],
        ]);
    }

    // =========================================================================
    // SHOP ACTIONS
    // =========================================================================

    /**
     * Get list of Shops
     * GET /api/product-attribute/shop
     */
    public function actionShopList()
    {
        $this->checkAuth();
        
        $query = Shop::find()->asArray();
        $this->applySyncFilter($query);

        return new \yii\data\ActiveDataProvider([
            'query' => $query,
            'pagination' => ['pageSize' => 50],
            'sort' => ['defaultOrder' => ['id' => SORT_DESC]],
        ]);
    }

    // =========================================================================
    // STOCK ACTIONS
    // =========================================================================

    /**
     * Get list of Stocks
     * GET /api/product-attribute/stock
     */
    public function actionStockList()
    {
        $this->checkAuth();
        
        $query = Stock::find()->asArray();
        $this->applySyncFilter($query);

        return new \yii\data\ActiveDataProvider([
            'query' => $query,
            'pagination' => ['pageSize' => 50],
            'sort' => ['defaultOrder' => ['id' => SORT_DESC]],
        ]);
    }

    // =========================================================================
    // REGION ACTIONS
    // =========================================================================

    /**
     * Get list of Regions
     * GET /api/product-attribute/region
     */
    public function actionRegionList()
    {
        $this->checkAuth();
        
        $query = Region::find()->asArray();
        $this->applySyncFilter($query);

        return new \yii\data\ActiveDataProvider([
            'query' => $query,
            'pagination' => false,
            'sort' => ['defaultOrder' => ['id' => SORT_ASC]],
        ]);
    }

    // =========================================================================
    // DELIVERY ACTIONS
    // =========================================================================

    /**
     * Get list of Deliveries
     * GET /api/product-attribute/delivery
     */
    public function actionDeliveryList()
    {
        $this->checkAuth();
        
        $query = Delivery::find()->asArray();
        $this->applySyncFilter($query);

        return new \yii\data\ActiveDataProvider([
            'query' => $query,
            'pagination' => false,
            'sort' => ['defaultOrder' => ['id' => SORT_ASC]],
        ]);
    }

    // =========================================================================
    // OFFICE ACTIONS
    // =========================================================================

    /**
     * Get list of Offices
     * GET /api/product-attribute/office
     */
    public function actionOfficeList()
    {
        $this->checkAuth();
        
        $query = Office::find()->asArray();
        $this->applySyncFilter($query);

        return new \yii\data\ActiveDataProvider([
            'query' => $query,
            'pagination' => false,
            'sort' => ['defaultOrder' => ['id' => SORT_ASC]],
        ]);
    }
}
