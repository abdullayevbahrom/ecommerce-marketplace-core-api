<?php
namespace app\modules\api\controllers;


use app\services\PayKeeperService;
use YooKassa\Client;
use Yii;
use yii\helpers\ArrayHelper;
use yii\web\HttpException;
use yii\rest\Controller;
use yii\filters\auth\HttpBearerAuth;
use app\models\order\Order;
use app\models\order\product\OrderProduct;

class PaymentController extends Controller {

    public $user;

    public function beforeAction($action) {
        $this->enableCsrfValidation = false;
        Yii::$app->response->getHeaders()->add('Access-Control-Allow-Origin', '*');
        Yii::$app->response->getHeaders()->add('Access-Control-Allow-Methods', 'GET, POST, PUT, PATCH, DELETE, HEAD, OPTIONS');
        Yii::$app->response->getHeaders()->add('Access-Control-Allow-Headers', 'Content-Type, X-Auth-Token, Origin, Authorization');

        if (Yii::$app->request->headers->has('OPTIONS')) {
            throw new HttpException(200, 'OK');
        }

        return parent::beforeAction($action);
    }

    public function behaviors() {
        $behaviors = parent::behaviors();
        $behaviors['authenticator'] = [
            'class' => HttpBearerAuth::className(),
            'optional' => ['notify']
        ];

        $auth = $behaviors['authenticator'];
        unset($behaviors['authenticator']);

        $behaviors['corsFilter'] = [
            'class' => \yii\filters\Cors::className(),
            'cors' => [
                'Access-Control-Allow-Origin' => ['*'],
                'Access-Control-Request-Method' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'HEAD', 'OPTIONS'],
                'Access-Control-Request-Headers' => ['*'],
                'Access-Control-Allow-Credentials' => true,
                'Access-Control-Max-Age' => 86400,
                'Access-Control-Expose-Headers' => [],
            ]
        ];

        $behaviors['authenticator']['except'] = ['options'];

        $behaviors['authenticator'] = $auth;

        return $behaviors;
    }

    public $serializer = [
        'class' => 'yii\rest\Serializer',
        'collectionEnvelope' => 'data',
    ];


    public function actionPay() {

        $id = Yii::$app->request->post('order_id');
        $user_id = Yii::$app->user->identity->id;
        $order  = Order::find()
            ->where(['id'=> $id, 'user_id' =>  $user_id ])->one();

        $order_products  = OrderProduct::find()
            ->where(['order_id'=> $id])->all();

        
        $amount = 0;
        foreach($order_products as $order_product){
            $amount  += $order_product->price;
        }

        $service =  new PayKeeperService();
        
        $url = $service->get_invoice_url($order->id, $amount);
        $band_card = $this->createPayment($amount,$order->id,'bank_card');
        $yoo_money = $this->createPayment($amount,$order->id,'yoo_money');    
        // $sberbank = $this->createPayment($amount, $order->id,'sberbank');  
        // $b2b_sberbank = $this->createPayment($amount, $order->id,'b2b_sberbank');    
        // $qiwi = $this->createPayment($amount, $order->id,'qiwi');    
        // $alfabank = $this->createPayment($amount, $order->id,'alfabank');    
        // $tinkoff_bank = $this->createPayment($amount, $order->id,'tinkoff_bank');

        return [
            'data'=> [
                'pay_url' => $url ,
                'band_card' => $band_card,
                'yoo_money' => $yoo_money,
                // 'sberbank' => $sberbank,
                // 'qiwi' => $qiwi,
                // 'alfabank' => $alfabank,
                // 'tinkoff_bank' => $tinkoff_bank,
                // 'b2b_sberbank' => $b2b_sberbank,
            ]
        ];
    }

    public function actionNotify() {
        $post = Yii::$app->request->post();
        $service =  new PayKeeperService();
        $id = ArrayHelper::getValue($post, 'id', 0);
        $sum = ArrayHelper::getValue($post, 'sum', 0);
        $clientid = ArrayHelper::getValue($post, 'clientid', 0);
        $orderid = ArrayHelper::getValue($post, 'orderid', 0);
        $key = ArrayHelper::getValue($post, 'key', 0);
        return [
            'success' => $service->notify($id, $sum, $clientid, $orderid, $key)
        ];
    }
    public function createPayment($amount = 1,$desc = ' ',$type = 'yoo_money'){
        $amount = !$amount ? 1 : $amount;
        $desc = empty($desc) ? 'Оплата' : $desc;
        $client = new Client();
        $client->setAuth('239537', 'test_oEKmFN2MHOIGsGv0ubYpO70jPToj94bv3xTNDvPVi9U');
        $resp = $client->createPayment(
            array(
                'amount' => array(
                    'value' => $amount,
                    'currency' => 'RUB',
                ),
                'description' => $desc,
                'confirmation' => array(
                    'type' => 'redirect',
                    'return_url' => Yii::$app->getUrlManager()->createAbsoluteUrl('/payment/success')
                ),
                'payment_method_data' => array(
                    'type' =>$type,
                ),
            ),
            uniqid('', true)
        );
        return $resp->getConfirmation()->getConfirmationUrl();
    }
}
?>