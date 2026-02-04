<?php
namespace app\modules\billz\controllers;

use Yii;
use yii\rest\Controller;
use yii\web\HttpException;
use yii\data\ActiveDataProvider;
use yii\filters\auth\HttpBearerAuth;
use yii\helpers\ArrayHelper;

use yii\services\Billz;

class ApiController extends Controller {
    public function actionRequest() {
        $billz = new Billz();
        return $billz->request(json_encode(Yii::$app->request->post()));
    }
}
?>