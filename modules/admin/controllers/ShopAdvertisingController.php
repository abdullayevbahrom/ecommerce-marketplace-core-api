<?php
namespace app\modules\admin\controllers;

use Yii;
use yii\web\Controller;
use yii\web\UploadedFile;
use yii\helpers\ArrayHelper;

use app\models\user\User;
use app\models\shop\Shop;
use app\models\shop\advertising\ShopAdvertising;
use app\models\shop\advertising\ShopAdvertisingSearch;

class ShopAdvertisingController extends Controller{
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

            if (!in_array('shop-advertising', $accesses)) {
                throw new HttpException(403, 'Error access');
            }
        }

        if ($action->id == 'upload') {
            $this->enableCsrfValidation = false;
        }

        return parent::beforeAction($action);
    }

    public function actionIndex(){
        $searchModel = new ShopAdvertisingSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);
        $dataProvider->query->with('image');

        $shops = ArrayHelper::map(Shop::find()->where(['status'=>1])->all(), 'id', 'name_ru');

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
            'shops' => $shops
        ]);
    }

    public function actionCreate($id = null) {
        $model = new ShopAdvertising;

        if ($id) {
            $model = ShopAdvertising::find()->with('image')->where(['id'=>$id])->one();
            if (!$model) {
                throw new HttpException(404, 'Page not found');
            }
        }

        $model->scenario = ShopAdvertising::ADMIN;

        if ($model->load(Yii::$app->request->post()) && $model->validate()) {
            if ($model->saveObject()) {
                Yii::$app->session->setFlash('shop_advertising_saved', 'Saved');
                return $this->redirect(['/admin/shop-advertising/view', 'id'=>$model->id]);
            }
        }

        $shops = ArrayHelper::map(Shop::find()->where(['status'=>1])->all(), 'id', 'name_ru');

        return $this->render('create', [
            'model' => $model,
            'shops' => $shops
        ]);
    }

    public function actionView($id) {
        $model = ShopAdvertising::find()->with('image')->where(['id'=>$id])->one();
        if (!$model) {
            throw new HttpException(404, 'Page not found');
        }

        return $this->render('view', [
            'model' => $model
        ]);
    }

    public function actionRemove($id) {
        $model = ShopAdvertising::find()->with('image')->where(['id'=>$id])->one();
        if (!$model) {
            throw new HttpException(404, 'Page not found');
        }
        
        if ($this->user && ($this->user->role != User::ROLE_USER) && $model && $model->removeObject()) {
            Yii::$app->session->setFlash('shop_advertising_removed', 'Deleted');
        }

        return $this->redirect(['/admin/shop-advertising']);
    }

    public function actionLock($id) {
        $model = ShopAdvertising::findOne($id);

        if (!$model) {
            throw new HttpException(404, 'Page not found');
        }

        if ($model->status == 1) {
            $model->status = 2;
            $msg = 'Blocked';
        } else {
            $model->status = 1;
            $msg = 'Unblocked';
        }

        if ($model->save(false)) {
            Yii::$app->session->setFlash('shop_advertising_locked', $msg);
        }

        return $this->redirect(Yii::$app->request->referrer);
    }
}