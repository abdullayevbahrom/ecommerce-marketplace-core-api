<?php

namespace app\modules\admin\controllers;

use Yii;
use yii\web\Controller;
use yii\web\UploadedFile;
use yii\helpers\ArrayHelper;

use app\models\user\User;
use app\models\news\News;
use app\models\news\NewsSearch;
use app\models\shop\Shop;

class NewsController extends Controller
{
    public $user;

    public function beforeAction($action)
    {
        $this->enableCsrfValidation = false;
        if (Yii::$app->user->isGuest) {
            return $this->redirect(['/admin/default']);
        }
        $this->user = User::find()->with('moderatorAccess', 'moderatorAccess.moderator')->where(['id' => Yii::$app->user->identity->id])->one();

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
        $searchModel = new NewsSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);
        $dataProvider->query->with('image', 'shop');

        $shops = ArrayHelper::map(Shop::find()->where(['status' => 1])->all(), 'id', 'name_ru');

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
            'shops' => $shops
        ]);
    }

    public function actionCreate($id = null)
    {
        $model = new News;

        if ($id) {
            $model = News::find()->with('image', 'shop')->where(['id' => $id])->one();
            if (!$model) {
                throw new HttpException(404, 'Page not found');
            }
        }

        if ($model->load(Yii::$app->request->post()) && $model->validate()) {
            if ($model->saveObject()) {
                Yii::$app->session->setFlash('news_saved', 'Saved');
                return $this->redirect(['/admin/news/view', 'id' => $model->id]);
            }
        }

        $shops = ArrayHelper::map(Shop::find()->where(['status' => 1])->all(), 'id', 'name_ru');

        return $this->render('create', [
            'model' => $model,
            'shops' => $shops
        ]);
    }

    public function actionView($id)
    {
        $model = News::find()->with('image', 'shop')->where(['id' => $id])->one();
        if (!$model) {
            throw new HttpException(404, 'Page not found');
        }

        return $this->render('view', [
            'model' => $model
        ]);
    }

    public function actionRemove($id)
    {
        $model = News::find()->with('image')->where(['id' => $id])->one();
        if (!$model) {
            throw new HttpException(404, 'Page not found');
        }

        if ($this->user && ($this->user->role != User::ROLE_USER) && $model && $model->removeObject()) {
            Yii::$app->session->setFlash('news_removed', 'Deleted');
        }

        return $this->redirect(['/admin/news']);
    }

    public function actionLock($id)
    {
        $model = News::findOne($id);

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
            Yii::$app->session->setFlash('news_locked', $msg);
        }

        return $this->redirect(Yii::$app->request->referrer);
    }

    public function actionUpload($CKEditorFuncNum)
    {
        $file = UploadedFile::getInstanceByName('upload');
        if (!$file) {
            return "File not uploaded\n";
        }

        $model = new News();
        $name = $model->generateFileName() . '.' . $file->extension;

        $tmp = Yii::getAlias('@runtime') . '/ck_' . uniqid() . '_' . $name;
        if (!$file->saveAs($tmp)) {
            return "Error in upload file\n";
        }

        try {
            $key = "uploads/news/gallery/{$name}";
            $contentType = @mime_content_type($tmp) ?: 'application/octet-stream';

            Yii::$app->s3->putFile($key, $tmp, $contentType);

            $url = Yii::$app->s3->url($key);

            return '<script type="text/javascript">window.parent.CKEDITOR.tools.callFunction("'
                . $CKEditorFuncNum . '", "'
                . $url . '", "");</script>';
        } catch (\Throwable $e) {
            Yii::error("CKEditor upload error: " . $e->getMessage(), __METHOD__);
            return "Upload failed\n";
        } finally {
            @unlink($tmp);
        }
    }
}
