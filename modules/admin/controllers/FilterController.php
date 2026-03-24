<?php
namespace app\modules\admin\controllers;

use Yii;
use yii\web\Controller;
use yii\web\HttpException;
use yii\data\Pagination;
use yii\helpers\ArrayHelper;

use app\models\filter\Filter;
use app\models\filter\FilterSearch;
use app\models\user\User;
use app\models\Category;
use app\models\moderator\ModerationComment;
use GuzzleHttp\Client;

class FilterController extends Controller{
	public $user;

    public function beforeAction($action) {
        $this->enableCsrfValidation = false;
        if (Yii::$app->user->isGuest) {
            return $this->redirect(['/admin/default']);
        }
        $this->user = User::find()->with('moderatorAccess', 'moderatorAccess.moderator')->where(['id'=>Yii::$app->user->identity->getId()])->one();

        if (($this->user->role == User::ROLE_MODERATOR)) {
            $accesses = array();

            if ($this->user && $this->user->moderatorAccess) {
                foreach ($this->user->moderatorAccess as $v) {
                    if ($v && $v->moderator) {
                        $accesses[] = $v->moderator->url;
                    }
                }
            }

            if (!in_array('filter', $accesses)) {
                throw new HttpException(403, 'Error access');
            }
        }

        return parent::beforeAction($action);
    }

    public function actionIndex() {
        $searchModel = new FilterSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);
        $dataProvider->query->with('childs', 'category')->andWhere(['parent_id'=>0]);

        if ($this->user->role == User::ROLE_MODERATOR) {
            $dataProvider->query->andWhere(['status' => 2])->andWhere(['deleted_at' => null]);
        }

        $dataProvider->setSort([
            'defaultOrder' => [
                'id' => 'desc'
            ]
        ]);

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider
        ]);
    }

    public function actionCreate() {
        $model = new Filter;
        $current_categories = [];
        $tree = [0 => ''];

        if ($this->user->role == User::ROLE_MODERATOR) {
            return $this->redirect(['/admin/default/profile']);
        }

        // edit
        if ($id = Yii::$app->request->get('id')) {
            $model = Filter::find()->with('category', 'category.parent', 'category.parent.childs', 'childs')->where(['id'=>$id])->one();
            if (!$model) {
                throw new HttpException(404, 'Page not found');
            }

            $tree = explode('/', $model->category_tree);
            $current_categories = [];

            foreach ($tree as $key => $id) {
                $current_categories[] = ArrayHelper::map(Category::find()->where(['parent_id'=>$id])->all(), 'id', 'name_ru');
            }
        }
        // end edit

        if ($model->load(Yii::$app->request->post()) && $model->validate()) {
            if ($model->saveObject()) {
                Yii::$app->session->setFlash('filter_saved', 'Saved');
                return $this->redirect(['/admin/filter/view', 'id'=>$model->id]);
            }
        }

        $categories = ArrayHelper::map(Category::find()->where(['parent_id'=>0, 'type'=>'product'])->all(), 'id', 'name_ru');

        return $this->render('create', [
            'model' => $model,
            'categories' => $categories,
            'current_categories' => $current_categories,
            'tree' => $tree
        ]);
    }

    public function actionView($id) {
        $model = Filter::find()->with('childs', 'category')->where(['id'=>$id])->one();
        if (!$model) {
            throw new HttpException(404, 'Page not found');
        }

        return $this->render('view', [
            'model' => $model
        ]);
    }

    public function actionLock($id) {
        $model = Filter::findOne($id);
        
        if (!$model) {
            throw new HttpException(404, 'Page not found');
        }

        $user = Yii::$app->user->identity;

        if ($user->role === User::ROLE_MODERATOR && $model->status != 2) {
            throw new HttpException(403, 'Moderator can only unlock products');
        }

        $oldStatus = $model->status;
        $model->status = ($model->status == 2) ? 1 : 2;
        $model->save(false);

        $comment = new ModerationComment();
        $comment->entity_type  = 'filter';
        $comment->entity_id    = $model->id;
        $comment->action       = $model->status == 1 ? 'approve' : 'reject';
        $comment->comment      = 'Ваш филтр разблокирован';
        $comment->moderator_id = $user->id;
        $comment->is_sent_to_warehouse = (bool) (Yii::$app->params['rabbitmq']['enable_moderation_events'] ?? false);
        $comment->save(false);

        $this->sendToWarehouse([
            'id' => $model->id,
            'entity_type'  => 'filter',
            'entity_id'    => $model->id,
            'action'       => $model->status == 1 ? 'approve' : 'reject',
            'status_after' => $model->status == 1 ? 'approved' : 'rejected',
            'comment'      => 'Ваш филтр разблокирован',
            'moderator_id' => $user->id,
        ]);

        Yii::$app->session->setFlash('filter_locked', $model->status == 1 ? 'filter unlocked' : 'filter blocked');

        if ($user->role === User::ROLE_MODERATOR) {
            return $this->redirect(['/admin/filter']);
        }

        return $this->redirect(Yii::$app->request->referrer);
    }

    public function actionRemove($id) {

        if ($this->user->role == User::ROLE_MODERATOR) {
            return $this->redirect(['/admin/default/profile']);
        }

        $model = Filter::findOne($id);
        if (!$model) {
            throw new HttpException(404, 'Page not found');
        }

        if ($this->user && ($this->user->role != User::ROLE_USER) && $model && $model->delete()) {
            Filter::deleteAll(['parent_id'=>$id]);
            Yii::$app->session->setFlash('filter_removed', 'Deleted');
        }

        return $this->redirect(['/admin/filter']);
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
        $model = Filter::findOne($id);
        if (!$model) {
            throw new HttpException(404, 'filter not found');
        }

        $user = Yii::$app->user->identity;

        if ($user->role !== User::ROLE_MODERATOR) {
            throw new HttpException(403, 'Access denied');
        }

        if ($model->status != 2) {
            throw new HttpException(400, 'Comment allowed only for blocked filter');
        }

        $commentText = trim(Yii::$app->request->post('comment'));
        if (!$commentText) {
            Yii::$app->session->setFlash('error', 'Комментарий обязателен');
            return $this->redirect(Yii::$app->request->referrer);
        }

        $comment = new ModerationComment();
        $comment->entity_type  = 'filter';
        $comment->entity_id    = $model->id;
        $comment->action       = 'reject';   // approve | reject | block
        $comment->comment      = $commentText;
        $comment->moderator_id = $user->id;
        $comment->is_sent_to_warehouse = 0;
        $comment->status_after = 'rejected';
        $comment->save(false);

        $this->sendToWarehouse([
            'id' => $model->id,
            'entity_type'  => 'filter',
            'entity_id'    => $model->id,
            'action'       => 'reject',
            'status_after' => 'rejected',
            'comment'      => $commentText,
            'moderator_id' => $user->id,
        ]);

        Yii::$app->session->setFlash(
            'info',
            'Комментарий отправлен. филтр остаётся заблокированным.'
        );

        return $this->redirect(['/admin/filter']);
    }
}
