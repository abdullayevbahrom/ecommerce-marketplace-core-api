<?php
namespace app\modules\admin\controllers;

use Yii;
use yii\web\Controller;
use yii\web\UploadedFile;
use yii\helpers\ArrayHelper;

use app\models\user\User;
use app\models\advantages\Advantages;
use app\models\advantages\AdvantagesSearch;

class AdvantageController extends Controller{
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

            if (!in_array('advantage', $accesses)) {
                throw new HttpException(403, 'Doesnt access');
            }
        }

        if ($action->id == 'upload') {
            $this->enableCsrfValidation = false;
        }

        return parent::beforeAction($action);
    }

    public function actionIndex(){
        $searchModel = new AdvantagesSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);
        $dataProvider->query->with('image');

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider
        ]);
    }

    public function actionCreate($id = null) {
        $model = new Advantages;

        if ($id) {
            $model = Advantages::find()->with('image')->where(['id'=>$id])->one();
            if (!$model) {
                throw new HttpException(404, 'Page not found');
            }
        }

        if ($model->load(Yii::$app->request->post()) && $model->validate()) {
            if ($model->saveObject()) {
                Yii::$app->session->setFlash('advantage_saved', 'Saved');
                return $this->redirect(['/admin/advantage/view', 'id'=>$model->id]);
            }
        }

        return $this->render('create', [
            'model' => $model
        ]);
    }

    public function actionView($id) {
        $model = Advantages::find()->with('image')->where(['id'=>$id])->one();
        if (!$model) {
            throw new HttpException(404, 'Page not found');
        }

        return $this->render('view', [
            'model' => $model
        ]);
    }

    public function actionRemove($id) {
        $model = Advantages::find()->with('image')->where(['id'=>$id])->one();
        if (!$model) {
            throw new HttpException(404, 'Page not found');
        }
        
        if ($this->user && ($this->user->role != User::ROLE_USER) && $model && $model->removeObject()) {
            Yii::$app->session->setFlash('advantage_removed', 'Deleted');
        }

        return $this->redirect(['/admin/advantage']);
    }

    public function actionLock($id) {
        $model = Advantages::findOne($id);

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
            Yii::$app->session->setFlash('advantage_locked', $msg);
        }

        return $this->redirect(Yii::$app->request->referrer);
    }

    public function actionUpload($CKEditorFuncNum) {
        $file = UploadedFile::getInstanceByName('upload');
        if ($file) {
            $path = 'uploads/advantages/gallery/';

            $model = new Advantages;

            $advantages = $model->generateFileName().'.'.$file->extension;

            if ($file->saveAs($path.$advantages)) {
                return '<script type="text/javascript">window.parent.CKEDITOR.tools.callFunction("'.$CKEditorFuncNum.'", "/'.$path.$advantages.'", "");</script>';
            } else {
                return "Error in upload file\n";
            }
        } else {
            return "File not uploaded\n";
        }
    }
}