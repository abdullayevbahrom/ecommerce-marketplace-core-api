<?php
namespace app\modules\shop\controllers;

use Yii;
use yii\web\Controller;
use yii\web\UploadedFile;

use app\models\user\User;
use app\models\feedback\Feedback;
use app\models\feedback\FeedbackSearch;

class FeedbackController extends Controller{
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

            if (!in_array('feedback', $accesses)) {
                throw new HttpException(403, 'В доступе отказано');
            }
        }

        return parent::beforeAction($action);
    }

    public function actionIndex(){
        $searchModel = new FeedbackSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider
        ]);
    }

    public function actionView($id) {
        $model = Feedback::findOne($id);
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
        $model = Feedback::findOne($id);
        if (!$model) {
            throw new HttpException(404, 'Page not found');
        }
        
        if ($this->user && ($this->user->role != User::ROLE_USER) && $model && $model->delete()) {
            Yii::$app->session->setFlash('feedback_removed', 'Сообщение успешно удалено');
        }

        return $this->redirect(['/shop/feedback']);
    }
}