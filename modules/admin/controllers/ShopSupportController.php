<?php

namespace app\modules\admin\controllers;

use Yii;
use yii\web\Controller;
use yii\web\UploadedFile;
use yii\web\HttpException;
use yii\helpers\ArrayHelper;

use app\models\user\User;
use app\models\shop\Shop;
use app\models\shop\support\ShopSupport;
use app\models\shop\support\ShopSupportSearch;

/**
 * ShopSupportController implements the CRUD actions for ShopSupport model.
 */
class ShopSupportController extends Controller
{
    public $user;
    public $shop;
    
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

            if (!in_array('shop-support', $accesses)) {
                throw new HttpException(403, 'Error access');
            }
        }

        if ($action->id == 'upload') {
            $this->enableCsrfValidation = false;
        }

        return parent::beforeAction($action);
    }

    public function actionIndex() {
        $searchModel = new ShopSupportSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);
        $dataProvider->query->with('shop');

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider
        ]);
    }

    public function actionView($id) {
        $model = ShopSupport::find()->with('shop')->where(['id'=>$id])->one();
        if (!$model) {
            throw new HttpException(404, 'Page not found');
        }

        $model->status = 1;
        $model->save(false);

        return $this->render('view', [
            'model' => $model
        ]);
    }

    public function actionRemove($id) {
        $model = ShopSupport::find()->where(['id'=>$id])->one();
        if (!$model) {
            throw new HttpException(404, 'Page not found');
        }
        
        if ($this->user && ($this->user->role != User::ROLE_USER) && $model && $model->removeObject()) {
            Yii::$app->session->setFlash('shop_support_removed', 'Deleted');
        }

        return $this->redirect(['/admin/shop-support']);
    }
}
