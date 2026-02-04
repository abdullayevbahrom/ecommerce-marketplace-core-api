<?php
namespace app\modules\shop\controllers;

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

            if (!in_array('shop-advertising', $accesses)) {
                throw new HttpException(403, 'В доступе отказано');
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
        $dataProvider->query->with('image')->andWhere(['shop_id'=>$this->shop->id]);

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
            $model = ShopAdvertising::find()->with('image')->where(['id'=>$id, 'shop_id'=>$this->shop->id])->one();
            if (!$model) {
                throw new HttpException(404, 'Page not found');
            }
        }

        $model->scenario = ShopAdvertising::SHOP;

        if ($model->load(Yii::$app->request->post()) && $model->validate()) {
            if ($model->saveObject()) {
                Yii::$app->session->setFlash('shop_advertising_saved', 'Реклама успешно сохранена');
                return $this->redirect(['/shop/shop-advertising/view', 'id'=>$model->id]);
            }
        }

        return $this->render('create', [
            'model' => $model
        ]);
    }

    public function actionView($id) {
        $model = ShopAdvertising::find()->with('image')->where(['id'=>$id, 'shop_id'=>$this->shop->id])->one();
        if (!$model) {
            throw new HttpException(404, 'Page not found');
        }

        return $this->render('view', [
            'model' => $model
        ]);
    }

    public function actionRemove($id) {
        $model = ShopAdvertising::find()->with('image')->where(['id'=>$id, 'shop_id'=>$this->shop->id])->one();
        if (!$model) {
            throw new HttpException(404, 'Page not found');
        }
        
        if ($this->user && ($this->user->role != User::ROLE_USER) && $model && $model->removeObject()) {
            Yii::$app->session->setFlash('shop_advertising_removed', 'Реклама успешно удалена');
        }

        return $this->redirect(['/shop/shop-advertising']);
    }

    public function actionLock($id) {
        $model = ShopAdvertising::findOne(['id'=>$id, 'shop_id'=>$this->shop->id]);

        if (!$model) {
            throw new HttpException(404, 'Page not found');
        }

        if ($model->status == 1) {
            $model->status = 2;
            $msg = 'Реклама успешно заблокирована';
        } else {
            $model->status = 1;
            $msg = 'Реклама успешно разблокирована';
        }

        if ($model->save(false)) {
            Yii::$app->session->setFlash('shop_advertising_locked', $msg);
        }

        return $this->redirect(Yii::$app->request->referrer);
    }
}