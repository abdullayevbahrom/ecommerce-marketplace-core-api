<?php
namespace app\modules\shop\controllers;

use Yii;
use yii\web\Controller;
use yii\web\UploadedFile;

use app\models\user\User;
use app\models\news\News;
use app\models\news\NewsSearch;
use app\models\shop\Shop;

class NewsController extends Controller{
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

            if (!in_array('news', $accesses)) {
                throw new HttpException(403, 'В доступе отказано');
            }
        }

        if ($action->id == 'upload') {
            $this->enableCsrfValidation = false;
        }

        return parent::beforeAction($action);
    }

    public function actionIndex(){
        $searchModel = new NewsSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);
        $dataProvider->query->with('image')->andWhere(['shop_id'=>$this->shop->id]);

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
            'shops' => $shops
        ]);
    }

    public function actionCreate($id = null) {
        $model = new News;

        if ($id) {
            $model = News::find()->with('image')->where(['id'=>$id, 'shop_id'=>$this->shop->id])->one();
            if (!$model) {
                throw new HttpException(404, 'Page not found');
            }
        }

        if ($model->load(Yii::$app->request->post()) && $model->validate()) {
            if ($model->saveObject()) {
                Yii::$app->session->setFlash('news_saved', 'Новость успешно сохранена');
                return $this->redirect(['/shop/news/view', 'id'=>$model->id]);
            }
        }

        return $this->render('create', [
            'model' => $model,
            'shops' => $shops
        ]);
    }

    public function actionView($id) {
        $model = News::find()->with('image')->where(['id'=>$id, 'shop_id'=>$this->shop->id])->one();
        if (!$model) {
            throw new HttpException(404, 'Page not found');
        }

        return $this->render('view', [
            'model' => $model
        ]);
    }

    public function actionRemove($id) {
        $model = News::find()->with('image')->where(['id'=>$id, 'shop_id'=>$this->shop->id])->one();
        if (!$model) {
            throw new HttpException(404, 'Page not found');
        }
        
        if ($this->user && ($this->user->role != User::ROLE_USER) && $model && $model->removeObject()) {
            Yii::$app->session->setFlash('news_removed', 'Новость успешно удалена');
        }

        return $this->redirect(['/shop/news']);
    }

    public function actionLock($id) {
        $model = News::findOne($id);

        if (!$model) {
            throw new HttpException(404, 'Page not found');
        }

        if ($model->status == 1) {
            $model->status = 2;
            $msg = 'Новость успешно заблокирована';
        } else {
            $model->status = 1;
            $msg = 'Новость успешно разблокирована';
        }

        if ($model->save(false)) {
            Yii::$app->session->setFlash('news_locked', $msg);
        }

        return $this->redirect(Yii::$app->request->referrer);
    }

    public function actionUpload($CKEditorFuncNum) {
        $file = UploadedFile::getInstanceByName('upload');
        if ($file) {
            $path = 'uploads/news/gallery/';

            $model = new News;

            $news = $model->generateFileName().'.'.$file->extension;

            if ($file->saveAs($path.$news)) {
                return '<script type="text/javascript">window.parent.CKEDITOR.tools.callFunction("'.$CKEditorFuncNum.'", "/'.$path.$news.'", "");</script>';
            } else {
                return "Возникла ошибка при загрузке файла\n";
            }
        } else {
            return "Файл не загружен\n";
        }
    }
}