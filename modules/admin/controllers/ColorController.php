<?php
namespace app\modules\admin\controllers;

use Yii;
use yii\web\Controller;
use yii\web\HttpException;

use app\models\user\User;
use app\models\color\Color;
use app\models\color\ColorSearch;
use app\models\moderator\ModerationComment;
use GuzzleHttp\Client;

class ColorController extends Controller{
	public $user;

    public function beforeAction($action) {
        $this->enableCsrfValidation = false;
        if (Yii::$app->user->isGuest) {
            return $this->redirect(['/admin/default']);
        }
        $this->user = User::find()->with('moderatorAccess', 'moderatorAccess.moderator')->where(['id'=>Yii::$app->user->identity->id])->one();

        if (!$this->user) {
            Yii::$app->user->logout(false);
            return $this->redirect(['/admin/default']);
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

            if (!in_array('color', $accesses)) {
                throw new HttpException(403, 'Error access');
            }
        }

        return parent::beforeAction($action);
    }

    public function actionIndex() {
        $searchModel = new ColorSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

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
            'dataProvider' => $dataProvider,
        ]);
    }

    public function actionCreate() {

        if ($this->user->role == User::ROLE_MODERATOR) {
            return $this->redirect(['/admin/default/profile']);
        }

        $model = ($id = Yii::$app->request->get('id')) ? Color::find()->where(['id'=>$id])->one() : new Color;

        if ($model->load(Yii::$app->request->post()) && $model->validate()) {
            if ($model->save()) {
                Yii::$app->session->setFlash('color_saved', 'Saved');
                return $this->redirect(['/admin/color/view', 'id'=>$model->id]);
            }
        }

        return $this->render('create', [
            'model' => $model
        ]);
    }

    public function actionView($id) {
        $model = Color::find()->where(['id'=>$id])->one();

        return $this->render('view', [
            'model' => $model
        ]);
    }

    public function actionRemove($id) {
        $model = Color::findOne(['id'=>$id]);
        
        if ($this->user->role == User::ROLE_MODERATOR) {
            return $this->redirect(['/admin/default/profile']);
        }

        if ($this->user && ($this->user->role != User::ROLE_USER) && $model && $model->delete()) {
            Yii::$app->session->setFlash('color_removed', 'Deleted');
        }
        return $this->redirect(['/admin/color']);
    }

    public function actionLock($id) {
        $model = Color::findOne($id);
        
        if (!$model) {
            throw new HttpException(404, 'Page not found');
        }

        $user = Yii::$app->user->identity;

        if ($user->role === User::ROLE_MODERATOR && $model->status != 2) {
            throw new HttpException(403, 'Moderator can only unlock colors');
        }

        $model->status = ($model->status == Color::STATUS_ACTIVE) ? Color::STATUS_INACTIVE : Color::STATUS_ACTIVE;
        $model->save(false);

        $comment = new ModerationComment();
        $comment->entity_type  = 'color';
        $comment->entity_id    = $model->id;
        $comment->action       = $model->status == Color::STATUS_ACTIVE ? 'approve' : 'block';
        $comment->comment      = 'Ваш цвет разблокирован';
        $comment->moderator_id = $user->id;
        $comment->is_sent_to_warehouse = (bool) (Yii::$app->params['rabbitmq']['enable_moderation_events'] ?? false);
        $comment->save(false);

        $this->sendToWarehouse([
            'id' => $model->id,
            'entity_type'  => 'color',
            'entity_id'    => $model->id,
            'action'       => $model->status == Color::STATUS_ACTIVE  ? 'approve' : 'block',
            'status_after' => $model->status == Color::STATUS_ACTIVE  ? 'approved' : 'pending',
            'comment'      => 'Ваш цвет разблокирован',
            'moderator_id' => $user->id,
        ]);

        Yii::$app->session->setFlash('color_locked', $model->status == 1 ? 'color unlocked' : 'color blocked');

        return $this->redirect(['/admin/color']);
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
        $model = Color::findOne($id);
        if (!$model) {
            throw new HttpException(404, 'color not found');
        }

        $user = Yii::$app->user->identity;

        if ($user->role !== User::ROLE_MODERATOR) {
            throw new HttpException(403, 'Access denied');
        }

        if ($model->status != 2) {
            throw new HttpException(400, 'Comment allowed only for blocked color');
        }

        $commentText = trim(Yii::$app->request->post('comment'));
        if (!$commentText) {
            Yii::$app->session->setFlash('error', 'Комментарий обязателен');
            return $this->redirect(Yii::$app->request->referrer);
        }

        $comment = new ModerationComment();
        $comment->entity_type  = 'color';
        $comment->entity_id    = $model->id;
        $comment->action       = 'reject';   // approve | reject | block
        $comment->comment      = $commentText;
        $comment->moderator_id = $user->id;
        $comment->is_sent_to_warehouse = 1;
        $comment->status_after = 'rejected';
        $comment->save(false);

        $this->sendToWarehouse([
            'id' => $model->id,
            'entity_type'  => 'color',
            'entity_id'    => $model->id,
            'action'       => 'reject',
            'status_after' => 'rejected',
            'comment'      => $commentText,
            'moderator_id' => $user->id,
        ]);

        Yii::$app->session->setFlash(
            'info',
            'Комментарий отправлен. цвет остаётся заблокированным.'
        );

        return $this->redirect(['/admin/color']);
    }
}
