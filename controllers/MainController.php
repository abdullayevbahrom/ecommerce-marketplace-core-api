<?php
namespace app\controllers;

use Yii;
use yii\web\Controller;

class MainController extends Controller {
    public function actionIndex() {
        return $this->redirect('/admin');
        return $this->render('index');
    }

    public function actionLogOut() {
        Yii::$app->user->logout();
        return $this->goHome();
    }

    public function actionError() {
        $this->layout = 'error';
        return $this->render('error');
    }
}
