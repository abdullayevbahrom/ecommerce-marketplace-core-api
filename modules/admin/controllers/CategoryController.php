<?php
namespace app\modules\admin\controllers;

use Yii;
use yii\web\Controller;
use yii\web\Response;
use yii\web\HttpException;

use app\models\user\User;
use app\models\filter\Filter;
use app\models\Category;
use app\models\moderator\ModerationComment;
use GuzzleHttp\Client;

class CategoryController extends Controller{
    public $user;

    public function beforeAction($action) {
        $this->enableCsrfValidation = false;
        if (Yii::$app->user->isGuest) {
            return $this->redirect(['/admin/default']);
        }
        $this->user = User::find()->with('moderatorAccess', 'moderatorAccess.moderator')->where(['id'=>Yii::$app->user->identity->getId()])->one();

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

            if (!in_array('category', $accesses)) {
                throw new HttpException(403, 'Error access');
            }
        }

        return parent::beforeAction($action);
    }

    public function actionIndex($type = null) {
        $model = new Category;

        $query = Category::find()->where(['type' => $type])
            ->orderBy('sort');

        if ($this->user->role === User::ROLE_MODERATOR) {
            $query->andWhere(['status' => Category::STATUS_INACTIVE]);
        }

        if ($model->load(Yii::$app->request->post()) && $model->validate()) {
            $model->type = Yii::$app->request->get('type');
            if ($model->saveCategory()) {
                Yii::$app->session->setFlash('category_saved', 'Saved');
            }
            return $this->redirect(Yii::$app->request->referrer);
        }

        $categories = $model->getCategories($query->asArray()->all());
        // $categories = $model->getCategories($model->find()->orderBy('sort')->asArray()->where(['type'=>$type])->all());

        $filters = Filter::find()->with('childs')->where(['parent_id'=>0])->all();

        return $this->render('index', [
            'model' => $model,
            'categories' => $categories,
            'filters' => $filters
        ]);
    }

    public function actionRemove($id) {
        $model = Category::findOne($id);

        if (!$model) {
            throw new HttpException(404, 'Category not found');
        }

        if ($model && $model->delete()) {
            Yii::$app->session->setFlash('category_deleted', 'Deleted');
        }

        return $this->redirect(Yii::$app->request->referrer);
    }

    public function actionSaveSort() {
        if (!Yii::$app->request->isAjax) {
            throw new HttpException(400, 'Bad request');
        }

        $data = Yii::$app->request->post();
        $levels = ['top', 'second', 'third'];
        $updated = 0;
        $errors = [];

        foreach ($levels as $level) {
            if (empty($data[$level])) {
                continue;
            }

            $items = explode(',', (string) $data[$level]);
            foreach ($items as $item) {
                $item = trim($item);
                if ($item === '') {
                    continue;
                }

                $parts = explode('-', $item, 2);
                if (count($parts) < 2 || !is_numeric($parts[0]) || !is_numeric($parts[1])) {
                    $errors[] = "Invalid payload item: {$item}";
                    continue;
                }

                $sort = (int) $parts[0];
                $id = (int) $parts[1];
                $cat = Category::findOne($id);

                if (!$cat) {
                    $errors[] = "Category not found: {$id}";
                    continue;
                }

                $cat->sort = $sort;
                if ($cat->save(false)) {
                    $updated++;
                } else {
                    $errors[] = "Failed to save category: {$id}";
                }
            }
        }

        Yii::$app->response->format = Response::FORMAT_JSON;
        return [
            'save' => empty($errors),
            'updated' => $updated,
            'errors' => $errors,
        ];
    }

    public function actionGetCategory() {
        if (Yii::$app->request->isAjax && ($data = Yii::$app->request->post())) {
            $category = $data['id'] ? Category::find()->with('image')->where(['id'=>$data['id']])->one() : null;

            $data = [
                'id' => $category->id,
                'name_ru' => $category->name_ru,
                'name_uz' => $category->name_uz,
                'name_en' => $category->name_en,
                'description_ru' => $category->description_ru,
                'description_uz' => $category->description_uz,
                'description_en' => $category->description_en,
                'option_ru' => $category->option_ru,
                'option_uz' => $category->option_uz,
                'option_en' => $category->option_en,
                'popular' => $category->popular,
                'photo' => $category->getPhoto(),
                'photo_id' => $category->image ? $category->image->id : ''
            ];

            Yii::$app->response->format = Response::FORMAT_JSON;
            return ['category'=>$data];
        }
    }

    public function actionGetCategories() {
        if (Yii::$app->request->isAjax && ($data = Yii::$app->request->post())) {

            $cat = null;
            $categories = [];
            $filters = [];
            $product_types = [];
            $parent_id = 0;
            
            if ($data['id']) {
                $cat = Category::find()->with('parent')->where(['id'=>$data['id']])->one();
                $categories = Category::find()->with('image')->where(['parent_id'=>$data['id']])->all();

                if ($cat && $cat->parent) {
                    $filters = Filter::find()->with('childs', 'categoryFilter')->where(['parent_id'=>0])->andWhere(['category_id'=>$data['id']])->orWhere(['category_id'=>$cat->parent->id])->all();
                    $parent_id = $cat->parent->id;
                } else {
                    $filters = Filter::find()->with('childs', 'categoryFilter')->where(['category_id'=>$data['id'], 'parent_id'=>0])->all();
                }
                
                // Get product types for this category or global types
                $product_types = \app\models\product\ProductType::find()
                    ->with('productTypeValues')
                    ->where(['status' => 1])
                    ->andWhere([
                        'or', 
                        ['category_id' => $data['id']]
                    ])
                    ->orderBy('sort ASC')
                    ->all();
                    
                // Debug logging
                \Yii::info("Category ID: " . $data['id'], 'application');
                \Yii::info("Found " . count($product_types) . " product types", 'application');
                foreach ($product_types as $type) {
                    \Yii::info("Type: " . $type->name_ru . ", Category ID: " . $type->category_id, 'application');
                }
            }
            

            Yii::$app->response->format = Response::FORMAT_JSON;
            
            // Debug logging
            \Yii::info("Found " . count($filters) . " filters for category ID: " . $data['id'], 'application');
            
            // Serialize filters to include computed fields like is_filter
            $serializedFilters = [];
            foreach ($filters as $filter) {
                $filterData = $filter->toArray();
                // Add computed fields
                $filterData['is_filter'] = ($filter->is_filter == 1) ? true : false;
                $filterData['name'] = $filter->name_ru ?: $filter->name_en ?: $filter->name_uz;
                $filterData['value'] = $filter->value_ru ?: $filter->value_en ?: $filter->value_uz;
                
                \Yii::info("Filter: {$filterData['name']}, Type: {$filter->type}, is_filter: {$filterData['is_filter']}", 'application');
                
                // Add childs if they exist
                if ($filter->childs) {
                    $filterData['childs'] = [];
                    foreach ($filter->childs as $child) {
                        $childData = $child->toArray();
                        $childData['value_ru'] = $child->value_ru;
                        $childData['value_en'] = $child->value_en;
                        $childData['value_uz'] = $child->value_uz;
                        $filterData['childs'][] = $childData;
                    }
                }
                
                $serializedFilters[] = $filterData;
            }
            
            return [
                'categories' => $categories, 
                'filters' => $serializedFilters, 
                'product_types' => $product_types,
                'parent_id' => $parent_id
            ];
        }
    }

    public function actionLock($id) {
        $model = Category::findOne($id);
        
        if (!$model) {
            throw new HttpException(404, 'Page not found');
        }

        $user = Yii::$app->user->identity;

        if ($user->role === User::ROLE_MODERATOR && $model->status != 0) {
            throw new HttpException(403, 'Moderator can only unlock categories');
        }

        $oldStatus = $model->status;
        $model->status = ($model->status == Category::STATUS_INACTIVE) ? Category::STATUS_ACTIVE : Category::STATUS_INACTIVE;
        $model->save(false);

        $comment = new ModerationComment();
        $comment->entity_type  = 'category';
        $comment->entity_id    = $model->id;
        $comment->action       = $model->status == Category::STATUS_ACTIVE ? 'approve' : 'reject';
        $comment->comment      = 'Ваша категория разблокирована';
        $comment->moderator_id = $user->id;
        $comment->is_sent_to_warehouse = (bool) (Yii::$app->params['rabbitmq']['enable_moderation_events'] ?? false);
        $comment->save(false);

        $this->sendToWarehouse([
            'id' => $model->id,
            'entity_type'  => 'category',
            'entity_id'    => $model->id,
            'action'       => $model->status == Category::STATUS_ACTIVE  ? 'approve' : 'reject',
            'status_after' => $model->status == Category::STATUS_ACTIVE  ? 'approved' : 'rejected',
            'comment'      => 'Ваша категория разблокирована',
            'moderator_id' => $user->id,
        ]);

        Yii::$app->session->setFlash('category_locked', $model->status == 1 ? 'category unlocked' : 'category blocked');

        if ($user->role === User::ROLE_MODERATOR) {
            return $this->redirect(['admin/category?type=product']);
        }

        return $this->redirect(Yii::$app->request->referrer);
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
        $model = Category::findOne($id);
        if (!$model) {
            throw new HttpException(404, 'category not found');
        }

        $user = Yii::$app->user->identity;

        if ($user->role !== User::ROLE_MODERATOR) {
            throw new HttpException(403, 'Access denied');
        }

        if ($model->status != 0) {
            throw new HttpException(400, 'Comment allowed only for blocked categories');
        }

        $commentText = trim(Yii::$app->request->post('comment'));
        if (!$commentText) {
            Yii::$app->session->setFlash('error', 'Комментарий обязателен');
            return $this->redirect(Yii::$app->request->referrer);
        }

        $comment = new ModerationComment();
        $comment->entity_type  = 'category';
        $comment->entity_id    = $model->id;
        $comment->action       = 'reject';   // approve | reject | block
        $comment->comment      = $commentText;
        $comment->moderator_id = $user->id;
        $comment->is_sent_to_warehouse = 1;
        $comment->status_after = 'rejected';
        $comment->save(false);

        $this->sendToWarehouse([
            'id' => $model->id,
            'entity_type'  => 'category',
            'entity_id'    => $model->id,
            'action'       => 'reject',
            'status_after' => 'rejected',
            'comment'      => $commentText,
            'moderator_id' => $user->id,
        ]);

        Yii::$app->session->setFlash(
            'info',
            'Комментарий отправлен. категория остаётся заблокированным.'
        );

        return $this->redirect(['/admin/category?type=product']);
    }
}
?>
