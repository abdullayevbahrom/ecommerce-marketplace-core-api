<?php
namespace app\modules\admin\controllers;

use Yii;
use yii\web\Controller;
use yii\web\UploadedFile;
use yii\helpers\ArrayHelper;

use app\models\user\User;
use app\models\Category;
use app\models\brand\CategoryBrand;
use app\models\brand\CategoryBrandSearch;
use app\models\moderator\ModerationComment;
use GuzzleHttp\Client;
use yii\web\HttpException;

class BrandController extends Controller
{
    public $user;

    public function beforeAction($action)
    {
        $this->enableCsrfValidation = false;
        if (Yii::$app->user->isGuest) {
            return $this->redirect(['/admin/default']);
        }
        $this->user = User::find()->with('moderatorAccess', 'moderatorAccess.moderator')->where(['id' => Yii::$app->user->identity->id])->one();

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

            if (!in_array('brand', $accesses)) {
                throw new HttpException(403, 'Error access');
            }
        }

        if ($action->id == 'upload') {
            $this->enableCsrfValidation = false;
        }

        return parent::beforeAction($action);
    }

    public function actionIndex()
    {
        $searchModel = new CategoryBrandSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        $query = $dataProvider->query;
        $query->with('image', 'category');

        if ($this->user->role === User::ROLE_MODERATOR) {
            $query->andWhere(['status' => 2])->andWhere(['deleted_at' => null]);
        }

        $categories = ArrayHelper::map(Category::find()->where(['type' => 'product'])->all(), 'id', 'name_ru');

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
            'categories' => $categories
        ]);
    }

    public function actionCreate($id = null)
    {
        $model = new CategoryBrand;

        $current_categories = [];
        $tree = [0 => ''];

        if ($id) {
            $model = CategoryBrand::find()->with('image', 'category')->where(['id' => $id])->one();
            if (!$model) {
                throw new HttpException(404, 'Page not found');
            }

            $tree = explode('/', $model->category_tree);

            foreach ($tree as $key => $id) {
                if (!isset($tree[$key + 1])) {
                    break;
                }
                $current_categories[] = ArrayHelper::map(Category::find()->where(['parent_id' => $id])->all(), 'id', 'name_ru');
            }
        }

        if ($model->load(Yii::$app->request->post()) && $model->validate()) {
            if ($model->saveObject()) {
                Yii::$app->session->setFlash('brand_saved', 'Saved');
                return $this->redirect(['/admin/brand/view', 'id' => $model->id]);
            }
        }

        $categories = ArrayHelper::map(Category::find()->where(['type' => 'product', 'parent_id' => 0])->all(), 'id', 'name_ru');

        return $this->render('create', [
            'model' => $model,
            'categories' => $categories,
            'current_categories' => $current_categories,
            'tree' => $tree
        ]);
    }

    public function actionView($id)
    {
        $user = Yii::$app->user->identity;

        $model = CategoryBrand::find()->with('image', 'category')->where(['id' => $id])->one();
        if (!$model) {
            throw new HttpException(404, 'Page not found');
        }

        if ($user->role === User::ROLE_MODERATOR && $model->status != CategoryBrand::STATUS_INACTIVE) {
            throw new HttpException(403, 'Moderator can only unlock brands');
        }

        return $this->render('view', [
            'model' => $model
        ]);
    }

    public function actionRemove($id)
    {
        $model = CategoryBrand::find()->with('image')->where(['id' => $id])->one();
        if (!$model) {
            throw new HttpException(404, 'Page not found');
        }

        if ($this->user && ($this->user->role != User::ROLE_USER) && $model && $model->removeObject()) {
            Yii::$app->session->setFlash('brand_removed', 'Deleted');
        }

        return $this->redirect(Yii::$app->request->referrer ?: ['/admin/brand']);
    }

    public function actionLock($id)
    {
        $model = CategoryBrand::findOne($id);

        if (!$model) {
            throw new HttpException(404, 'Page not found');
        }

        $user = Yii::$app->user->identity;

        if ($user->role === User::ROLE_MODERATOR && $model->status != CategoryBrand::STATUS_INACTIVE) {
            throw new HttpException(403, 'Moderator can only unlock brands');
        }

        $model->status = ($model->status == CategoryBrand::STATUS_INACTIVE) ? CategoryBrand::STATUS_ACTIVE : CategoryBrand::STATUS_INACTIVE;
        $model->save(false);

        $comment = new ModerationComment();
        $comment->entity_type = 'brand';
        $comment->entity_id = $model->id;
        $comment->action = $model->status == CategoryBrand::STATUS_ACTIVE ? 'approve' : 'reject';
        $comment->comment = 'Ваша бренд разблокирован';
        $comment->moderator_id = $user->id;
        $comment->is_sent_to_warehouse = (bool) (Yii::$app->params['rabbitmq']['enable_moderation_events'] ?? false);
        $comment->save(false);

        $this->sendToWarehouse([
            'id' => $model->id,
            'entity_type' => 'brand',
            'entity_id' => $model->id,
            'action' => $model->status == CategoryBrand::STATUS_ACTIVE ? 'approve' : 'reject',
            'status_after' => $model->status == CategoryBrand::STATUS_ACTIVE ? 'approved' : 'rejected',
            'comment' => 'Ваша бренд разблокирован',
            'moderator_id' => $user->id,
        ]);

        Yii::$app->session->setFlash('brand_locked', $model->status == 1 ? 'brand unlocked' : 'brand blocked');

        if ($user->role === User::ROLE_MODERATOR) {
            return $this->redirect(['admin/brand']);
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

            $apiUrl = rtrim(Yii::$app->params['warehouseApiUrl'] ?? 'https://api.warehouse.example.com', '/') . '/api/moderation/sync';

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
        $model = CategoryBrand::findOne($id);
        if (!$model) {
            throw new HttpException(404, 'brand not found');
        }

        $user = Yii::$app->user->identity;

        if ($user->role !== User::ROLE_MODERATOR) {
            throw new HttpException(403, 'Access denied');
        }

        if ($model->status != 2) {
            throw new HttpException(400, 'Comment allowed only for blocked brands');
        }

        $commentText = trim(Yii::$app->request->post('comment'));
        if (!$commentText) {
            Yii::$app->session->setFlash('error', 'Комментарий обязателен');
            return $this->redirect(Yii::$app->request->referrer);
        }

        $comment = new ModerationComment();
        $comment->entity_type = 'brand';
        $comment->entity_id = $model->id;
        $comment->action = 'reject';   // approve | reject | block
        $comment->comment = $commentText;
        $comment->moderator_id = $user->id;
        $comment->is_sent_to_warehouse = 1;
        $comment->status_after = 'rejected';
        $comment->save(false);

        $this->sendToWarehouse([
            'id' => $model->id,
            'entity_type' => 'brand',
            'entity_id' => $model->id,
            'action' => 'reject',
            'status_after' => 'rejected',
            'comment' => $commentText,
            'moderator_id' => $user->id,
        ]);

        Yii::$app->session->setFlash(
            'info',
            'Комментарий отправлен. бренд остаётся заблокированным.'
        );

        return $this->redirect(['/admin/brand']);
    }
}
