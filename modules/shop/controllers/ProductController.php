<?php

namespace app\modules\shop\controllers;

use Yii;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use yii\helpers\ArrayHelper;
use yii\web\HttpException;
use yii\web\Response;

use app\models\user\User;
use app\models\product\Product;
use app\models\product\ProductSearch;
use app\models\product\ProductFilter;
use app\models\product\ProductColor;
use app\models\color\Color;
use app\models\filter\Filter;
use app\models\shop\Shop;
use app\models\Category;
use app\models\brand\CategoryBrand;
use app\models\Notification;
use app\models\stock\Stock;
use app\models\delivery\Delivery;
use app\models\office\Office;
use app\models\office\ProductOffice;
use app\models\Images;
use app\models\Settings;
use app\models\product\ProductType;
use app\models\product\ProductTypeValue;
use app\models\product\ProductProductType;

class ProductController extends Controller {
    public $user;
    public $shop;

    public function beforeAction($action) {
        $this->enableCsrfValidation = false;
        if (Yii::$app->user->isGuest) {
            return $this->redirect(['/admin/default']);
        }
        $this->user = User::find()->with('moderatorAccess', 'moderatorAccess.moderator')->where(['id'=>Yii::$app->user->identity->id])->one();
        $this->shop = Shop::findOne(['user_id'=>$this->user->id]);

        if (($this->user->role == User::ROLE_MODERATOR)) {
            $accesses = array();

            if ($this->user && $this->user->moderatorAccess) {
                foreach ($this->user->moderatorAccess as $v) {
                    if ($v && $v->moderator) {
                        $accesses[] = $v->moderator->url;
                    }
                }
            }

            if (!in_array('product', $accesses)) {
                return $this->redirect(['/shop/default/profile']);
            }
        }

        return parent::beforeAction($action);
    }

    public function actionIndex($status = null) {
        $searchModel = new ProductSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);
        $dataProvider->query->andWhere(['shop_id'=>$this->shop->id]);

        $dataProvider->setSort([
            'defaultOrder' => [
                'id' => 'desc'
            ]
        ]);

        $users = ArrayHelper::map(User::find()->where(['status'=>1])->all(), 'id', 'name');
        $stocks = ArrayHelper::map(Stock::find()->where(['status'=>1, 'shop_id'=>$this->shop->id])->all(), 'id', 'name_ru');
        $categories = ArrayHelper::map(Category::find()->where(['type'=>'product'])->all(), 'id', 'name_ru');

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
            'stocks' => $stocks,
            'users' => $users,
            'categories' => $categories,
            'user' => Yii::$app->user->identity
        ]);
    }

    public function actionView($id) {
        $model = Product::find()->with('delivery', 'category', 'image', 'stock', 'brand', 'tag', 'productProperties', 'productColors', 'productColors.image', 'productColors.color', 'productOffices', 'productProductTypes', 'productProductTypes.productType', 'productProductTypes.productTypeValue', 'products', 'products.color', 'products.image', 'products.productProductTypes', 'products.productProductTypes.productType', 'products.productProductTypes.productTypeValue', 'gallery')->where(['id'=>$id, 'shop_id'=>$this->shop->id])->one();

        if (!$model) {
            throw new HttpException(404, 'Page not found');
        }

        $notification = Notification::findOne(['type'=>'product_new', 'object_id'=>$id]);
        if ($notification) {
            $notification->status = 1;
            $notification->save(false);
        }

        return $this->render('view', [
            'model' => $model
        ]);
    }

    public function actionCreate($id = null) {
        $model = new Product;
        $current_categories = []; 
        $current_colors = [];
        $current_product_types = [];
        $tree = [0 => ''];
        
        // edit
        if ($id) {
            $model = Product::find()->with('image', 'gallery', 'category', 'productColors', 'productColors.image', 'productColors.color', 'productFilters', 'productFilters.filter', 'productProperties', 'productOffices')->where(['id'=>$id])->one();

            if (!$model) {
                throw new HttpException(404, 'Page not found');
            }
            
            $tree = explode('/', $model->category_tree);

            foreach ($tree as $key => $v_id) {
                $current_categories[] = ArrayHelper::map(Category::find()->where(['parent_id'=>$v_id])->all(), 'id', 'name_ru');
            }

            $current_colors = ArrayHelper::map(ProductColor::find()->where(['in', 'product_id', $v_id])->all(), 'color_id', 'color_id');
            
            // Load existing product type connections
            $current_product_types = ProductProductType::find()
                ->with('productType', 'productTypeValue')
                ->where(['product_id' => $id])
                ->all();
        }
        // end edit

        if ($model->load(Yii::$app->request->post()) && $model->validate()) {
            // Set shop_id for shop products before validation            
            if (!$id) {
                // Generate a shared token_key for all variants
                $shared_token_key = $model->token_key ?: Yii::$app->security->generateRandomString();
                
                // Get explicit variants from request (new logic)
                $post = Yii::$app->request->post();
                $variants = isset($post['Product']['variants']) ? $post['Product']['variants'] : [];

                if (!empty($variants)) {
                    // New logic: Iterate through explicitly defined variants
                    foreach ($variants as $variant) {
                        $color = isset($variant['color_id']) && $variant['color_id'] !== '' ? $variant['color_id'] : null;
                        $types = isset($variant['types']) ? $variant['types'] : [];
                        
                        $price_data = [
                            'price' => isset($variant['price']) ? $variant['price'] : null,
                            'price_small' => isset($variant['price_small']) ? $variant['price_small'] : null,
                            'price_opt' => isset($variant['price_opt']) ? $variant['price_opt'] : null,
                            'amount' => isset($variant['amount']) ? $variant['amount'] : null,
                        ];

                        // saveObject expects product_types as [type_id => value_id] or [type_id => [value_id]]
                        // Our $types is [type_id => value_id]
                        // We need to pass it correctly. saveObject handles it.
                        
                        $product = $model->saveObject(false, $color, $shared_token_key, null, $types, $price_data);
                        if ($product) {
                            $product->shop_id = $model->shop_id;
                            $product->save(false);
                        }
                    }
                } else {
                    // Fallback to old logic (if no variants generated or JS disabled/failed)
                    
                    // Get product type prices from request
                    $product_type_prices = isset($post['Product']['product_type_prices']) ? $post['Product']['product_type_prices'] : [];
                    
                    // Create mode: Handle product type variations
                    if ($model->product_types) {
                        $type_combinations = $this->generateTypeCombinations($model->product_types);
                        
                        foreach ($type_combinations as $combination) {
                            // Determine price overrides for this combination
                            $price_data = null;
                            foreach ($combination as $type_id => $value_id) {
                                if (isset($product_type_prices[$value_id])) {
                                    $p_data = $product_type_prices[$value_id];
                                    if (!empty($p_data['price']) || !empty($p_data['price_small']) || !empty($p_data['price_opt'])) {
                                        $price_data = $p_data;
                                    }
                                }
                            }

                            if ($model->colors) {
                                foreach ($model->colors as $color) {
                                    $product = $model->saveObject(false, $color, $shared_token_key, null, $combination, $price_data);
                                }
                            } else {
                                $product = $model->saveObject(false, null, $shared_token_key, null, $combination, $price_data);
                            }
                        }
                    } else {
                        // Original color handling for create
                        if ($model->colors) {
                            foreach ($model->colors as $color) {
                                $product = $model->saveObject(false, $color, $shared_token_key);
                                $product->shop_id = $model->shop_id;
                                $product->save(false);
                            }
                        } else {
                            $product = $model->saveObject(false);
                            $product->shop_id = $model->shop_id;
                            $product->save(false);
                        }
                    }
                }
            } else {
                // Update mode: Don't modify product types, just update the main product
                $product = $model->updateObject(false);
            }
            
            Yii::$app->session->setFlash('product_saved', 'Товар успешно сохранен');
            return $this->redirect(['/shop/product/view', 'id' => $product->id]);
        }

        $categories = ArrayHelper::map(Category::find()->with('childs')->where(['parent_id'=>0, 'type'=>'product'])->all(), 'id', 'name_ru');
        $brands = ArrayHelper::map(CategoryBrand::find()->where(['status'=>1])->all(), 'id', 'name_ru');        
        $offices = ArrayHelper::map(Office::find()->all(), 'id', 'name');
        $deliveries = ArrayHelper::map(Delivery::find()->all(), 'id', 'name_ru');
        $stocks = ArrayHelper::map(Stock::find()->where(['status'=>1, 'shop_id'=>$this->shop->id])->all(), 'id', 'name_ru');
        $tags = ArrayHelper::map(Category::find()->where(['type'=>'tag'])->all(), 'id', 'name_ru');
        $colors = ArrayHelper::map(Color::find()->all(), 'id', 'name_ru');
        $colors_object = Color::find()->all();

        // Product types will be loaded dynamically via AJAX
        $product_types = [];
        $product_type_values = [];

        $view = $id ? 'update' : 'create';

        return $this->render($view, [
            'model' => $model,
            'categories' => $categories,
            'brands' => $brands,
            'current_categories' => $current_categories,
            'current_colors' => $current_colors,
            'current_product_types' => $current_product_types,
            'tree' => $tree,
            'offices' => $offices,
            'deliveries' => $deliveries,
            'colors' => $colors,
            'colors_object' => $colors_object,
            'stocks' => $stocks,
            'tags' => $tags,
            'product_types' => $product_types,
            'product_type_values' => $product_type_values
        ]);
    }

    public function actionLock($id) {
        $model = Product::findOne(['id'=>$id, 'shop_id'=>$this->shop->id]);

        if (!$model) {
            throw new HttpException(404, 'Page not found');
        }

        if ($model->status == 1) {
            $model->status = 2;
            $msg = 'Товар успешно заблокирован';
        } else {
            $model->status = 1;
            $msg = 'Товар успешно разблокирован';
        }

        if ($model->save(false)) {
            Yii::$app->session->setFlash('product_locked', $msg);
        }

        return $this->redirect(Yii::$app->request->referrer);
    }

    public function actionRemove($id) {
        $get = file_get_contents('log.txt');
        file_put_contents('log.txt',$get.'
        '.date("Y-m-s H:i:s").' - shoprem '.$id.' - '.Yii::$app->user->identity->id);
    if(Yii::$app->user->identity->role == User::ROLE_ADMIN){
        $model = Product::find()->with('image')->where(['id'=>$id, 'shop_id'=>$this->shop->id])->one();

        if (!$model) {
            throw new HttpException(404, 'Page not found');
        }

        // if ($this->user && ($this->user->role != User::ROLE_USER) && $model && $model->removeObject()) {
        //     Yii::$app->session->setFlash('product_removed', 'Товар успешно удален');
        // }
    }
        return $this->redirect(['/shop/product']);
    }

    /**
     * Generate combinations of product types for creating product variants
     * @param array $product_types
     * @return array
     */
    private function generateTypeCombinations($product_types) {
        $combinations = [];

        // If only one type is selected, return simple combinations
        if (count($product_types) == 1) {
            foreach ($product_types as $type_id => $values) {
                if (is_array($values)) {
                    foreach ($values as $value_id) {
                        $combinations[] = [$type_id => $value_id];
                    }
                } else {
                    $combinations[] = [$type_id => $values];
                }
            }
            return $combinations;
        }
        
        // For multiple types, generate all combinations
        $type_arrays = [];
        foreach ($product_types as $type_id => $values) {
            if (is_array($values)) {
                foreach ($values as $value_id) {
                    $type_arrays[$type_id][] = $value_id;
                }
            } else {
                $type_arrays[$type_id][] = $values;
            }
        }
        
        // Generate cartesian product of all type combinations
        $keys = array_keys($type_arrays);
        $values = array_values($type_arrays);
        $total = array_product(array_map('count', $values));
        
        for ($i = 0; $i < $total; $i++) {
            $combination = [];
            $temp = $i;
            for ($j = count($values) - 1; $j >= 0; $j--) {
                $combination[$keys[$j]] = $values[$j][$temp % count($values[$j])];
                $temp = intval($temp / count($values[$j]));
            }
            $combinations[] = array_reverse($combination, true);
        }
        
        return $combinations;
    }
}
