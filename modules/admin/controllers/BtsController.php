<?php
namespace app\modules\admin\controllers;

use Yii;
use yii\web\Controller;

class BtsController extends Controller {

    public function actionCities($region_id) {
        if (Yii::$app->request->isAjax) {
            return $this->asJson([
                'success' => true,
                'cities' => Yii::$app->bts->getCities($region_id)
            ]);
        }
        
        throw new \yii\web\NotFoundHttpException();
    }
}
