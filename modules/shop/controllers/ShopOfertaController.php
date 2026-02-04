<?php

namespace app\modules\shop\controllers;

use Yii;
use yii\web\Controller;
use yii\web\UploadedFile;
use yii\web\HttpException;
use yii\helpers\ArrayHelper;

use app\models\user\User;
use app\models\shop\Shop;
use app\models\shop\oferta\ShopOferta;
use app\models\shop\oferta\ShopOfertaSearch;

/**
 * ShopOfertaController implements the CRUD actions for ShopOferta model.
 */
class ShopOfertaController extends Controller
{
    public $user;
    public $shop;
    
    public function beforeAction($action) {
        $this->enableCsrfValidation = false;
        if (Yii::$app->user->isGuest) {
            return $this->redirect(['/shop/default']);
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

            if (!in_array('shop-oferta', $accesses)) {
                throw new HttpException(403, 'В доступе отказано');
            }
        }

        if ($action->id == 'upload') {
            $this->enableCsrfValidation = false;
        }

        return parent::beforeAction($action);
    }

    public function actionIndex() {
        $searchModel = new ShopOfertaSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);
        $dataProvider->query->andWhere(['shop_id'=>$this->shop->id]);

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider
        ]);
    }

    public function actionCreate($id = null) {
        $model = new ShopOferta;

        if ($id) {
            $model = ShopOferta::find()->with('file')->where(['id'=>$id, 'shop_id'=>$this->shop->id])->one();
            if (!$model) {
                throw new HttpException(404, 'Page not found');
            }
        }

        if ($model->load(Yii::$app->request->post()) && $model->validate()) {
            if ($model->saveObject()) {
                Yii::$app->session->setFlash('shop_oferta_saved', 'Оферта успешно сохранена');
                return $this->redirect(['/shop/shop-oferta/view', 'id'=>$model->id]);
            }
        }

        return $this->render('create', [
            'model' => $model
        ]);
    }

    public function actionView($id) {
        $model = ShopOferta::find()->with('file')->where(['id'=>$id, 'shop_id'=>$this->shop->id])->one();
        if (!$model) {
            throw new HttpException(404, 'Page not found');
        }

        return $this->render('view', [
            'model' => $model
        ]);
    }

    public function actionRemove($id) {
        $model = ShopOferta::find()->with('file')->where(['id'=>$id, 'shop_id'=>$this->shop->id])->one();
        if (!$model) {
            throw new HttpException(404, 'Page not found');
        }
        
        if ($this->user && ($this->user->role != User::ROLE_USER) && $model && $model->removeObject()) {
            Yii::$app->session->setFlash('shop_oferta_removed', 'Оферта успешно удалена');
        }

        return $this->redirect(['/shop/shop-oferta']);
    }

    public function actionLock($id) {
        $model = ShopOferta::findOne(['id'=>$id, 'shop_id'=>$this->shop->id]);

        if (!$model) {
            throw new HttpException(404, 'Page not found');
        }

        if ($model->status == 1) {
            $model->status = 2;
            $msg = 'Оферта успешно заблокирована';
        } else {
            $model->status = 1;
            $msg = 'Оферта успешно разблокирована';
        }

        if ($model->save(false)) {
            Yii::$app->session->setFlash('shop_oferta_locked', $msg);
        }

        return $this->redirect(Yii::$app->request->referrer);
    }
}
