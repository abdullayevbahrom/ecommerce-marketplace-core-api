<?php

namespace app\modules\admin\controllers;

use Yii;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use yii\helpers\ArrayHelper;
use yii\web\HttpException;

use app\models\user\User;
use app\models\product\ProductType;
use app\models\product\ProductTypeSearch;
use app\models\product\ProductTypeValue;
use app\models\Category;
use app\models\moderator\ModerationComment;
use app\models\product\Product;
use GuzzleHttp\Client;

class ProductTypeController extends Controller {
    public $user;

    public function beforeAction($action) {
        $this->enableCsrfValidation = false;
        if (Yii::$app->user->isGuest) {
            return $this->redirect(['/admin/default']);
        }
        $this->user = User::find()->with('moderatorAccess', 'moderatorAccess.moderator')->where(['id'=>Yii::$app->user->identity->id])->one();

        if (!$this->user) {
            Yii::$app->user->logout(false);
            return $this->redirect(["/admin/default"]);
        }

        if (($this->user->role == User::ROLE_MODERATOR)) {
            $accesses = array();

            if ($this->user && $this->user->moderatorAccess) {
                foreach ($this->user->moderatorAccess as $v) {
                    if ($v && $v->moderator) {
                        $accesses[] = $v->moderator->url;
                    }
                }
            }

            if (!in_array('product-type', $accesses)) {
                return $this->redirect(['/admin/default/profile']);
            }
        }

        return parent::beforeAction($action);
    }

    public function actionIndex() {
        $searchModel = new ProductTypeSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);
        $dataProvider->query->with('category');

        if ($this->user->role == User::ROLE_MODERATOR) {
            $dataProvider->query->andWhere(['status' => 2]);
        }

        $dataProvider->setSort([
            'defaultOrder' => [
                'sort' => SORT_ASC,
                'id' => SORT_DESC
            ]
        ]);

        $categories = ArrayHelper::map(Category::find()->where(['type'=>'product'])->all(), 'id', 'name_ru');

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
            'categories' => $categories
        ]);
    }

    public function actionView($id) {
        $model = ProductType::find()->with('category', 'productTypeValues')->where(['id'=>$id])->one();

        if (!$model) {
            throw new HttpException(404, 'Page not found');
        }

        return $this->render('view', [
            'model' => $model
        ]);
    }

    public function actionCreate($id = null) {
        $model = new ProductType;

        if ($this->user->role == User::ROLE_MODERATOR) {
            return $this->redirect(['/admin/default/profile']);
        }
        // edit
        if ($id) {
            $model = ProductType::find()->with('productTypeValues')->where(['id'=>$id])->one();
            if (!$model) {
                throw new HttpException(404, 'Page not found');
            }
        }

        if ($model->load(Yii::$app->request->post()) && $model->validate()) {
            // Additional validation for select/checkbox types
            if ($model->type == 'select' || $model->type == 'checkbox') {
                $hasValidValues = false;
                if (isset($_POST['values']) && is_array($_POST['values'])) {
                    foreach ($_POST['values'] as $valueData) {
                        if (!empty($valueData['value_ru'])) {
                            $hasValidValues = true;
                            break;
                        }
                    }
                }
                
                if (!$hasValidValues) {
                    $model->addError('type', 'At least one predefined value is required for Select and Checkbox types.');
                    Yii::$app->session->setFlash('product_type_error', 'Please add at least one predefined value for ' . $model->type . ' type.');
                } else {
                    $model->status = 1;
                    if ($model->save()) {
                        $this->handleProductTypeValues($model, $id);
                        Yii::$app->session->setFlash('product_type_saved', 'Product type saved successfully with ' . count($_POST['values']) . ' predefined values.');
                        return $this->redirect(['/admin/product-type/view', 'id' => $model->id]);
                    }
                }
            } else {
                $model->status = 1;
                if ($model->save()) {
                    // If type changed to non-select/checkbox, remove all values
                    ProductTypeValue::deleteAll(['product_type_id' => $model->id]);
                    Yii::$app->session->setFlash('product_type_saved', 'Product type saved successfully.');
                    return $this->redirect(['/admin/product-type/view', 'id' => $model->id]);
                }
            }
        }

        $categories = ArrayHelper::map(Category::find()->where(['type'=>'product'])->all(), 'id', 'name_ru');

        $view = $id ? 'update' : 'create';

        return $this->render($view, [
            'model' => $model,
            'categories' => $categories
        ]);
    }

    public function actionLock($id) {
        $model = ProductType::findOne($id);

        if (!$model) {
            throw new HttpException(404, 'Page not found');
        }

        $user = Yii::$app->user->identity;

        if ($user->role === User::ROLE_MODERATOR && $model->status != 2) {
            throw new HttpException(403, 'Moderator can only unlock products type');
        }

        $oldStatus = $model->status;
        $model->status = ($model->status == ProductType::STATUS_ACTIVE) ? ProductType::STATUS_INACTIVE : ProductType::STATUS_ACTIVE;
        $model->save(false);

        $comment = new ModerationComment();
        $comment->entity_type  = 'product-type';
        $comment->entity_id    = $model->id;
        $comment->action       = $model->status == 1 ? 'approve' : 'block';
        $comment->comment      = 'Ваш тип товара разблокирован';
        $comment->moderator_id = $user->id;
        $comment->is_sent_to_warehouse = (bool) (Yii::$app->params['rabbitmq']['enable_moderation_events'] ?? false);
        $comment->save(false);

        $this->sendToWarehouse([
            'id' => $model->id,
            'entity_type'  => 'product-type',
            'entity_id'    => $model->id,
            'action'       => $model->status == 1 ? 'approve' : 'block',
            'status_after' => $model->status == 1 ? 'approved' : 'pending',
            'comment'      => 'Ваш тип товара разблокирован',
            'moderator_id' => $user->id,
        ]);


        Yii::$app->session->setFlash(
            'product_locked',
            $model->status == 1 ? 'Product type unlocked' : 'Product type blocked'
        );

        return $user->role === User::ROLE_MODERATOR
            ? $this->redirect(['/admin/product-type'])
            : $this->redirect(Yii::$app->request->referrer);
    }
    

    protected function sendToWarehouse(array $payload)
    {
        if ((bool) (Yii::$app->params['rabbitmq']['enable_moderation_events'] ?? false)) {
            return;
        }

        try {
            $client = new Client(['timeout' => 5.0]);

            $apiUrl = rtrim(Yii::$app->params['warehouseApiUrl'] ?? 'http://warehouse.example.com', '/') . '/api/moderation/sync';

            $secretKey = Yii::$app->params['apiSecretKey'] ?? null;
            if (!$secretKey) {
                return;
            }

            $token = md5($payload['id'] . $secretKey);

            $client->post($apiUrl, [
                'json' => array_merge($payload, [
                    'metadata' => [
                        'source' => 'yii2',
                    ],
                ]),
                'headers' => [
                    'X-Api-Token' => $token,
                ],
            ]);

        } catch (\Throwable $e) {
            \Yii::error($e->getMessage(), 'warehouse');
        }
    }

    public function actionComment($id)
    {
        $model = ProductType::findOne($id);
        if (!$model) {
            throw new HttpException(404, 'Product type not found');
        }

        $user = Yii::$app->user->identity;

        if ($user->role !== User::ROLE_MODERATOR) {
            throw new HttpException(403, 'Access denied');
        }

        if ($model->status != 2) {
            throw new HttpException(400, 'Comment allowed only for blocked products type');
        }

        $commentText = trim(Yii::$app->request->post('comment'));
        if (!$commentText) {
            Yii::$app->session->setFlash('error', 'Комментарий обязателен');
            return $this->redirect(Yii::$app->request->referrer);
        }

        $comment = new ModerationComment();
        $comment->entity_type  = 'product-type';
        $comment->entity_id    = $model->id;
        $comment->action       = 'reject';   // approve | reject | block
        $comment->comment      = $commentText;
        $comment->moderator_id = $user->id;
        $comment->is_sent_to_warehouse = 0;
        $comment->status_after = 'rejected';
        $comment->save(false);

        $this->sendToWarehouse([
            'id' => $model->id,
            'entity_type'  => 'product-type',
            'entity_id'    => $model->id,
            'action'       => 'reject',
            'status_after' => 'rejected',
            'comment'      => $commentText,
            'moderator_id' => $user->id,
        ]);

        Yii::$app->session->setFlash(
            'info',
            'Комментарий отправлен. Тип Товара остаётся заблокированным.'
        );

        return $this->redirect(['/admin/product-type']);
    }
        

    public function actionRemove($id) {
        $model = ProductType::findOne($id);

        if (!$model) {
            throw new HttpException(404, 'Page not found');
        }

        if ($model->delete()) {
            Yii::$app->session->setFlash('product_type_removed', 'Removed');
        }

        return $this->redirect(['/admin/product-type']);
    }

    /**
     * AJAX endpoint to get product type values
     */
    public function actionGetValues($product_type_id) {
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        
        $values = ProductTypeValue::find()
            ->where(['product_type_id' => $product_type_id, 'status' => 1])
            ->orderBy(['sort' => SORT_ASC])
            ->asArray()
            ->all();
        
        return $values;
    }

    /**
     * Handle creating, updating, and deleting product type values
     */
    private function handleProductTypeValues($model, $editingExisting = false) {
        // Handle deleted values first
        if (isset($_POST['deleted_values']) && is_array($_POST['deleted_values'])) {
            foreach ($_POST['deleted_values'] as $deletedId) {
                $valueToDelete = ProductTypeValue::findOne($deletedId);
                if ($valueToDelete && $valueToDelete->product_type_id == $model->id) {
                    $valueToDelete->delete();
                }
            }
        }
        
        // Handle existing and new values
        if (isset($_POST['values']) && is_array($_POST['values'])) {
            foreach ($_POST['values'] as $valueData) {
                // Skip empty values
                if (empty($valueData['value_ru'])) {
                    continue;
                }
                
                $value = null;
                
                // Check if this is an existing value (has ID)
                if (!empty($valueData['id'])) {
                    $value = ProductTypeValue::findOne([
                        'id' => $valueData['id'],
                        'product_type_id' => $model->id
                    ]);
                }
                
                // If no existing value found, create new one
                if (!$value) {
                    $value = new ProductTypeValue();
                    $value->product_type_id = $model->id;
                    $value->status = 1;
                }
                
                // Set/update value data
                $value->value_ru = trim($valueData['value_ru']);
                $value->value_en = trim($valueData['value_en'] ?? '');
                $value->value_uz = trim($valueData['value_uz'] ?? '');
                $value->sort = (int)($valueData['sort'] ?? 0);
                
                // Validate and save
                if ($value->validate()) {
                    $value->save();
                } else {
                    // Log validation errors for debugging
                    Yii::error('ProductTypeValue validation failed: ' . json_encode($value->errors), __METHOD__);
                }
            }
        }
    }

    function log($type){
        $get = file_get_contents('log.txt');
        file_put_contents('log.txt',$get.'
        '.date("Y-m-s H:i:s").' - '.$type.' - '.Yii::$app->user->identity->id);
    }
} 
