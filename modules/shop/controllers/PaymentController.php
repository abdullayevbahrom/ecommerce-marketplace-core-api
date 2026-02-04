<?php
namespace app\modules\shop\controllers;

use Yii;
use yii\web\Controller;
use yii\rest\Controller;

class PaymentController extends Controller
{
    public function actionCreatePayment()
    {
        $amount = 100; // Сумма платежа в рублях
        $description = 'Описание платежа';
        
        $paymentUrl = Yii::$app->yookassa->createPayment($amount, $description);

        return $this->redirect($paymentUrl);
    }

    public function actionSuccess()
    {
        echo 'Платеж успешно выполнен!';
    }
}