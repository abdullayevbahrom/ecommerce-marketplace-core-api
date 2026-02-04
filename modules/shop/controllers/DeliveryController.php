<?php
namespace app\modules\shop\controllers;

use Yii;
use yii\web\Controller;
use yii\web\UploadedFile;

use app\models\user\User;
use app\models\delivery\Delivery;
use app\models\delivery\DeliverySearch;

class DeliveryController extends Controller{
	public $user;
    
    public function beforeAction($action) {
        $this->enableCsrfValidation = false;
        if (Yii::$app->user->isGuest) {
            return $this->redirect(['/admin/default']);
        }
        $this->user = User::find()->with('moderatorAccess', 'moderatorAccess.moderator')->where(['id'=>Yii::$app->user->identity->id])->one();

        if (($this->user->role == User::ROLE_MODERATOR)) {
            $accesses = array();

            if ($this->user && $this->user->moderatorAccess) {
                foreach ($this->user->moderatorAccess as $v) {
                    if ($v && $v->moderator) {
                        $accesses[] = $v->moderator->url;
                    }
                }
            }

            if (!in_array('delivery', $accesses)) {
                throw new HttpException(403, 'В доступе отказано');
            }
        }

        if ($action->id == 'upload') {
            $this->enableCsrfValidation = false;
        }

        return parent::beforeAction($action);
    }

    public function actionIndex(){
        $searchModel = new DeliverySearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);
        $dataProvider->query->with('image');

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider
        ]);
    }

    public function actionCreate($id = null) {
        $model = new Delivery;

        if ($id) {
            $model = Delivery::find()->with('image')->where(['id'=>$id])->one();
            if (!$model) {
                throw new HttpException(404, 'Page not found');
            }
        }

        if ($model->load(Yii::$app->request->post()) && $model->validate()) {
            if ($model->saveObject()) {
                Yii::$app->session->setFlash('delivery_saved', 'Способо доставки успешно сохранен');
                return $this->redirect(['/shop/delivery/view', 'id'=>$model->id]);
            }
        }

        return $this->render('create', [
            'model' => $model
        ]);
    }

    public function actionView($id) {
        $model = Delivery::find()->with('image')->where(['id'=>$id])->one();
        if (!$model) {
            throw new HttpException(404, 'Page not found');
        }

        return $this->render('view', [
            'model' => $model
        ]);
    }

    public function actionRemove($id) {
        $model = Delivery::find()->with('image')->where(['id'=>$id])->one();
        if (!$model) {
            throw new HttpException(404, 'Page not found');
        }
        
        if ($this->user && ($this->user->role != User::ROLE_USER) && $model && $model->removeObject()) {
            Yii::$app->session->setFlash('delivery_removed', 'Способо доставки успешно удален');
        }

        return $this->redirect(['/shop/delivery']);
    }

    public function actionLock($id) {
        $model = Delivery::findOne($id);

        if (!$model) {
            throw new HttpException(404, 'Page not found');
        }

        if ($model->status == 1) {
            $model->status = 2;
            $msg = 'Способо доставки успешно заблокирован';
        } else {
            $model->status = 1;
            $msg = 'Способо доставки успешно разблокирован';
        }

        if ($model->save(false)) {
            Yii::$app->session->setFlash('delivery_locked', $msg);
        }

        return $this->redirect(Yii::$app->request->referrer);
    }
}