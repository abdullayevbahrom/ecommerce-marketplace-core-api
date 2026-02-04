<?php

namespace app\controllers\payment;

use Yii;
use yii\rest\Controller;

use yii\services\Payme;

class PaymeController extends Controller
{
    public function beforeAction($action) {
        $this->enableCsrfValidation = false;
        return parent::beforeAction($action);
    }

    public function actionIndex() {
        $data = file_get_contents("php://input");
        $response = (new Payme($data))->response();
        return $response;
    }
}