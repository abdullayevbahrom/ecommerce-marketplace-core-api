<?php
namespace app\modules\admin\controllers;

use Yii;
use yii\web\Controller;
use yii\web\HttpException;

use app\models\user\User;
use app\models\color\Color;
use app\models\color\ColorSearch;

class ColorController extends Controller{
	public $user;

    public function beforeAction($action) {
        $this->enableCsrfValidation = false;
        if (Yii::$app->user->isGuest) {
            return $this->redirect(['/admin']);
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

            if (!in_array('color', $accesses)) {
                throw new HttpException(403, 'Error access');
            }
        }

        return parent::beforeAction($action);
    }

    public function actionIndex() {
        $searchModel = new ColorSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        $dataProvider->setSort([
            'defaultOrder' => [
                'id' => 'desc'
            ]
        ]);

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    public function actionCreate() {
        $model = ($id = Yii::$app->request->get('id')) ? Color::find()->where(['id'=>$id])->one() : new Color;

        if ($model->load(Yii::$app->request->post()) && $model->validate()) {
            if ($model->save()) {
                Yii::$app->session->setFlash('color_saved', 'Saved');
                return $this->redirect(['/admin/color/view', 'id'=>$model->id]);
            }
        }

        return $this->render('create', [
            'model' => $model
        ]);
    }

    public function actionView($id) {
        $model = Color::find()->where(['id'=>$id])->one();

        return $this->render('view', [
            'model' => $model
        ]);
    }

    public function actionRemove($id) {
        $model = Color::findOne(['id'=>$id]);

        if ($this->user && ($this->user->role != User::ROLE_USER) && $model && $model->delete()) {
            Yii::$app->session->setFlash('color_removed', 'Deleted');
        }
        return $this->redirect(['/admin/color']);
    }
}