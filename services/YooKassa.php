<?php

namespace app\services;

use Yii;
use YooKassa\Client;

class Yookassa 
{

    private $yookassa;

    public function createPayment($amount, $description, $type)
    {
        $yookassa = new Client();
        $yookassa->setAuth('sample_shop_id', 'sample_secret_key');
        $payment = $yookassa->createPayment(
            array(
                'amount' => array(
                    'value' => $amount,
                    'currency' => 'RUB',
                ),
                'description' => $description,
                'confirmation' => array(
                    'type' => 'redirect',
                    'return_url' => Yii::$app->getUrlManager()->createAbsoluteUrl('/payment/success')
                ),
                'payment_method_data' => array(
                    'type' => $type,
                ),
            ),
            uniqid('', true)
        );

        return $payment->getConfirmation()->getConfirmationUrl();
    }
}