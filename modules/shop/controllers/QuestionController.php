<?php

namespace app\modules\shop\controllers;

use Yii;
use yii\web\Controller;
use yii\web\UploadedFile;

use app\models\user\User;
use app\models\question\Question;
use app\models\question\QuestionSearch;

class QuestionController extends Controller
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

            if (!in_array('question', $accesses)) {
                throw new HttpException(403, 'В доступе отказано');
            }
        }

        if ($action->id == 'upload') {
            $this->enableCsrfValidation = false;
        }

        return parent::beforeAction($action);
    }

    public function actionIndex()
    {
        $searchModel = new QuestionSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider
        ]);
    }

    public function actionCreate($id = null)
    {
        $model = new Question;

        if ($id) {
            $model = Question::findOne($id);
            if (!$model) {
                throw new HttpException(404, 'Page not found');
            }
        }

        $model->status = 1;

        if ($model->load(Yii::$app->request->post()) && $model->validate()) {
            if ($model->save()) {
                Yii::$app->session->setFlash('question_saved', 'Вопрос с ответом успешно сохранены');
                return $this->redirect(['/shop/question/view', 'id' => $model->id]);
            }
        }

        return $this->render('create', [
            'model' => $model
        ]);
    }

    public function actionView($id)
    {
        $model = Question::findOne($id);
        if (!$model) {
            throw new HttpException(404, 'Page not found');
        }

        return $this->render('view', [
            'model' => $model
        ]);
    }

    public function actionRemove($id)
    {
        $model = Question::findOne($id);
        if (!$model) {
            throw new HttpException(404, 'Page not found');
        }

        if ($this->user && ($this->user->role != User::ROLE_USER) && $model && $model->delete()) {
            Yii::$app->session->setFlash('question_removed', 'Вопрос с ответом успешно удалены');
        }

        return $this->redirect(['/shop/question']);
    }

    public function actionLock($id)
    {
        $model = Question::findOne($id);

        if (!$model) {
            throw new HttpException(404, 'Page not found');
        }

        if ($model->status == 1) {
            $model->status = 2;
            $msg = 'Вопрос с ответом успешно заблокированы';
        } else {
            $model->status = 1;
            $msg = 'Вопрос с ответом успешно разблокированы';
        }

        if ($model->save(false)) {
            Yii::$app->session->setFlash('question_locked', $msg);
        }

        return $this->redirect(Yii::$app->request->referrer);
    }

    public function actionUpload($CKEditorFuncNum)
    {
        $file = UploadedFile::getInstanceByName('upload');
        if (!$file) {
            return "File not uploaded\n";
        }

        $model = new Question();
        $name = $model->generateFileName() . '.' . $file->extension;

        $tmp = Yii::getAlias('@runtime') . '/question_' . uniqid() . '_' . $name;
        if (!$file->saveAs($tmp)) {
            return "Error in upload file\n";
        }

        try {
            $key = "uploads/question/gallery/{$name}";
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
