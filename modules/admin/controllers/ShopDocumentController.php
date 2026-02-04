<?php

namespace app\modules\admin\controllers;

use Yii;
use yii\web\Controller;
use yii\web\UploadedFile;
use yii\web\HttpException;
use yii\helpers\ArrayHelper;

use app\models\user\User;
use app\models\shop\Shop;
use app\models\shop\document\ShopDocument;
use app\models\shop\document\ShopDocumentSearch;

/**
 * ShopDocumentController implements the CRUD actions for ShopDocument model.
 */
class ShopDocumentController extends Controller
{
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

            if (!in_array('shop-document', $accesses)) {
                throw new HttpException(403, 'Error access');
            }
        }

        if ($action->id == 'upload') {
            $this->enableCsrfValidation = false;
        }

        return parent::beforeAction($action);
    }

    public function actionIndex(){
        $searchModel = new ShopDocumentSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        $shops = ArrayHelper::map(Shop::find()->where(['status'=>1])->all(), 'id', 'name_ru');

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
            'shops' => $shops
        ]);
    }

    public function actionCreate($id = null) {
        $model = new ShopDocument;

        if ($id) {
            $model = ShopDocument::find()->with('file')->where(['id'=>$id])->one();
            if (!$model) {
                throw new HttpException(404, 'Page not found');
            }
        }

        if ($model->load(Yii::$app->request->post()) && $model->validate()) {
            if ($model->saveObject()) {
                Yii::$app->session->setFlash('shop_document_saved', 'Saved');
                return $this->redirect(['/admin/shop-document/view', 'id'=>$model->id]);
            }
        }

        $shops = ArrayHelper::map(Shop::find()->where(['status'=>1])->all(), 'id', 'name_ru');

        return $this->render('create', [
            'model' => $model,
            'shops' => $shops
        ]);
    }

    public function actionView($id) {
        $model = ShopDocument::find()->with('file')->where(['id'=>$id])->one();
        if (!$model) {
            throw new HttpException(404, 'Page not found');
        }

        return $this->render('view', [
            'model' => $model
        ]);
    }

    public function actionRemove($id) {
        $model = ShopDocument::find()->with('file')->where(['id'=>$id])->one();
        if (!$model) {
            throw new HttpException(404, 'Page not found');
        }
        
        if ($this->user && ($this->user->role != User::ROLE_USER) && $model && $model->removeObject()) {
            Yii::$app->session->setFlash('shop_document_removed', 'Deleted');
        }

        return $this->redirect(['/admin/shop-document']);
    }

    public function actionLock($id) {
        $model = ShopDocument::findOne($id);

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
            Yii::$app->session->setFlash('shop_document_locked', $msg);
        }

        return $this->redirect(Yii::$app->request->referrer);
    }
}
