<?php

namespace app\controllers;

use Yii;
use yii\web\Controller;

class MainController extends Controller
{
    public function actionIndex()
    {
        return $this->redirect('/admin');
        return $this->render('index');
    }

    public function actionEimzoMobileCheck()
    {
        $this->layout = false;

        return $this->render('eimzo-mobile-check');
    }

    public function actionEimzoMobileLogin()
    {
        $this->layout = false;

        return $this->render('eimzo-mobile-login');
    }

    public function actionEimzoLoginCheck()
    {
        $this->layout = false;

        return $this->render('eimzo-login-check');
    }

    public function actionLogOut()
    {
        Yii::$app->user->logout();
        return $this->goHome();
    }

    public function actionError()
    {
        $this->layout = 'error';
        $exception = Yii::$app->errorHandler->exception;

        if ($exception !== null) {
            return $this->render('error', ['exception' => $exception]);
        }
        
        return $this->render('error');
    }
}
