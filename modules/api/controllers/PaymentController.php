<?php
namespace app\modules\api\controllers;


use app\services\PayKeeperService;
use app\services\WalletService;
use YooKassa\Client;
use Yii;
use yii\helpers\ArrayHelper;
use yii\web\HttpException;
use yii\rest\Controller;
use yii\filters\auth\HttpBearerAuth;
use yii\filters\Cors;
use yii\filters\VerbFilter;
use yii\rest\OptionsAction;
use app\models\order\Order;
use app\models\order\product\OrderProduct;
use yii\helpers\Json;

class PaymentController extends Controller {

    public $user;

    public function actions()
    {
        $actions = parent::actions();
        $actions['options'] = [
            'class' => OptionsAction::class,
        ];
        return $actions;
    }

    public function beforeAction($action) {
        $this->enableCsrfValidation = false;
        return parent::beforeAction($action);
    }

    public function behaviors() {
        $behaviors = parent::behaviors();

        unset($behaviors['authenticator']);

        $behaviors['corsFilter'] = [
            'class' => Cors::class,
            'cors' => [
                'Origin' => ['*'],
                'Access-Control-Request-Method' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'HEAD', 'OPTIONS'],
                'Access-Control-Request-Headers' => ['*'],
                'Access-Control-Allow-Credentials' => false,
                'Access-Control-Max-Age' => 86400,
                'Access-Control-Expose-Headers' => [],
            ]
        ];

        $behaviors['authenticator'] = [
            'class' => HttpBearerAuth::class,
            'optional' => ['notify', 'pos-branch', 'pos-session'],
            'except' => ['options'],
        ];

        $behaviors['verbs'] = [
            'class' => VerbFilter::class,
            'actions' => [
                'pay' => ['POST'],
                'pay-order' => ['POST'],
                'pos-pay' => ['POST'],
                'options' => ['OPTIONS'],
            ],
        ];

        return $behaviors;
    }

    public $serializer = [
        'class' => 'yii\rest\Serializer',
        'collectionEnvelope' => 'data',
    ];


    /**
     * Initiate payment for an order (dispatches to crypto or traditional).
     * POST /api/order/pay-order
     *
     * Error codes (returned as {message, error_code}):
     *   1 — order_id is required (422)
     *   2 — Order not found or does not belong to user (404)
     *   3 — Order is already paid (422)
     *   4 — Could not retrieve wallet balance (503, crypto only)
     *   5 — Insufficient wallet balance (422, crypto only)
     */
    public function actionPay() {
        $orderId = Yii::$app->request->post('order_id');
        $userId  = Yii::$app->user->identity->id;

        if (!$orderId) {
            Yii::$app->response->statusCode = 422;
            return ['message' => 'order_id is required', 'error_code' => 1];
        }

        $order = Order::find()->where(['id' => $orderId, 'user_id' => $userId])->one();

        if (!$order) {
            Yii::$app->response->statusCode = 404;
            return ['message' => 'Order not found', 'error_code' => 2];
        }

        if ($order->status_payment == 1) {
            Yii::$app->response->statusCode = 422;
            return ['message' => 'Order is already paid', 'error_code' => 3];
        }

        $walletPaymentId = Yii::$app->params['walletPaymentId'] ?? null;

        if ($walletPaymentId && (int)$order->payment_id === (int)$walletPaymentId) {
            return $this->handleCryptoPayment($order, $userId);
        }

        return $this->handleTraditionalPayment($order);
    }

    /**
     * Crypto-payment branch of actionPay. Error codes continue the actionPay space:
     *   4 — Could not retrieve wallet balance (503)
     *   5 — Insufficient wallet balance (422)
     */
    private function handleCryptoPayment(Order $order, int $userId): array
    {
        $token = Yii::$app->params['walletDefaultToken'] ?? 'USDT';
        // TODO: Add real UZS→USDT exchange rate API. Currently 1:1.
        $exchangeRate = (float)(Yii::$app->params['uzsToUsdtRate'] ?? 1.0);
        $paymentAmount = (float)$order->price * $exchangeRate;

        $walletService = new WalletService();
        $walletService->init();

        try {
            $balanceData = $walletService->getBalance($userId);
        } catch (\Exception $e) {
            Yii::$app->response->statusCode = 503;
            return ['message' => 'Could not retrieve wallet balance.', 'error_code' => 4];
        }

        $balance = $this->extractTokenBalance($balanceData, $token);

        if ($balance < $paymentAmount) {
            Yii::$app->response->statusCode = 422;
            return [
                'message' => sprintf('Insufficient %s balance: %s available, %s required', $token, $balance, $paymentAmount),
                'error_code' => 5,
                'data' => ['balance' => $balance, 'amount' => $paymentAmount, 'token' => $token],
            ];
        }

        $baseUrl = rtrim(Yii::$app->params['baseUrl'] ?? Yii::$app->request->hostInfo, '/');
        $payUrl = $baseUrl . '/api/app/pay/order/' . $order->id;

        return [
            'data' => [
                'order_id' => $order->id,
                'balance' => $balance,
                'amount' => $paymentAmount,
                'token' => $token,
                'pay_url' => $payUrl,
            ],
        ];
    }

    private function extractTokenBalance(array $balanceData, string $symbol): float
    {
        foreach ($balanceData['tokens'] ?? [] as $tokenEntry) {
            if (isset($tokenEntry['symbol']) && strtoupper($tokenEntry['symbol']) === strtoupper($symbol)) {
                return (float)($tokenEntry['balance'] ?? 0);
            }
        }
        return (float)($balanceData['balance'] ?? 0);
    }

    private function handleTraditionalPayment(Order $order): array
    {
        $orderProducts = OrderProduct::find()->where(['order_id' => $order->id])->all();
        $amount = 0;
        foreach ($orderProducts as $op) { $amount += $op->price; }

        $service = new PayKeeperService();
        $payUrl = $service->get_invoice_url($order->id, $amount);
        $bandCard = $this->createPayment($amount, $order->id, 'bank_card');
        $yooMoney = $this->createPayment($amount, $order->id, 'yoo_money');

        return ['data' => ['pay_url' => $payUrl, 'band_card' => $bandCard, 'yoo_money' => $yooMoney]];
    }

    /**
     * Execute crypto payment for an order.
     * POST /api/app/pay/order/{orderId}
     *
     * Error codes (returned as {message, error_code}):
     *   1 — Order not found or does not belong to user (404)
     *   2 — Order is already paid (422)
     *   3 — Order does not use crypto payment (422)
     *   4 — Shop owner missing for order (422)
     *   5 — Insufficient wallet balance (422)
     *   6 — Wallet balance check failed (503)
     *   7 — On-chain payment execution failed (422)
     */
    public function actionPayOrder($orderId)
    {
        $userId = Yii::$app->user->identity->id;
        $order = Order::find()->where(['id' => $orderId, 'user_id' => $userId])->one();

        if (!$order) {
            Yii::$app->response->statusCode = 404;
            return ['message' => 'Order not found or does not belong to you', 'error_code' => 1];
        }
        if ($order->status_payment == 1) {
            Yii::$app->response->statusCode = 422;
            return ['message' => 'Order is already paid', 'error_code' => 2];
        }

        $walletPaymentId = Yii::$app->params['walletPaymentId'] ?? null;
        if (!$walletPaymentId || (int)$order->payment_id !== (int)$walletPaymentId) {
            Yii::$app->response->statusCode = 422;
            return ['message' => 'This order does not use crypto payment', 'error_code' => 3];
        }

        $token = Yii::$app->params['walletDefaultToken'] ?? 'USDT';
        $exchangeRate = (float)(Yii::$app->params['uzsToUsdtRate'] ?? 1.0);
        $paymentAmount = (float)$order->price * $exchangeRate;

        $shop = $order->shop;
        $merchantUserId = $shop ? $shop->user_id : null;
        if (!$merchantUserId) {
            Yii::$app->response->statusCode = 422;
            return ['message' => 'Shop owner not found', 'error_code' => 4];
        }

        $walletService = new WalletService();
        $walletService->init();

        try {
            $balanceData = $walletService->getBalance($userId);
            $balance = $this->extractTokenBalance($balanceData, $token);
            if ($balance < $paymentAmount) {
                Yii::$app->response->statusCode = 422;
                return ['message' => "Insufficient $token balance: $balance < $paymentAmount", 'error_code' => 5];
            }
        } catch (\Exception $e) {
            Yii::$app->response->statusCode = 503;
            return ['message' => 'Balance check failed: ' . $e->getMessage(), 'error_code' => 6];
        }

        $dbTransaction = Yii::$app->db->beginTransaction();
        try {
            $result = $walletService->pay($userId, $merchantUserId, (string)$paymentAmount, $token);

            $order->status_payment = 1;
            $order->save(false);

            $transaction = new \app\models\transaction\Transaction();
            $transaction->user_id = $userId;
            $transaction->order_id = $order->id;
            $transaction->shop_id = $order->shop_id;
            $transaction->type_transaction = 'payment';
            $transaction->type_payment = 'wallet';
            $transaction->amount = $order->price;
            $transaction->status = 1;
            $transaction->save(false);

            $dbTransaction->commit();
            return ['success' => true, 'message' => 'Payment successful', 'data' => ['order_id' => $order->id, 'amount_paid' => $paymentAmount, 'token' => $token]];
        } catch (\Exception $e) {
            $dbTransaction->rollBack();
            Yii::$app->response->statusCode = 422;
            return ['message' => 'Payment failed: ' . $e->getMessage(), 'error_code' => 7];
        }
    }

    /**
     * POS QR crypto payment — mobile scans QR at kassa, pays via wallet.
     * POST /api/payment/pos-pay
     *
     * Body: { session_token, token? }
     *
     * Flow:
     * 1. Fetch session from sklad API (order items, total, merchant)
     * 2. Check user wallet balance
     * 3. Execute payment (user → merchant)
     * 4. Confirm payment back to sklad
     */
    public function actionPosPay()
    {
        $user = Yii::$app->user->identity;
        $post = Yii::$app->request->post();
        $sessionToken = $post['session_token'] ?? null;
        $token = $post['token'] ?? Yii::$app->params['walletDefaultToken'] ?? 'USDT';

        if (!$sessionToken) {
            Yii::$app->response->statusCode = 422;
            return ['success' => false, 'error' => 'session_token is required'];
        }

        $skladUrl = Yii::$app->params['skladApiUrl'] ?? 'https://api.warehouse.example.com';

        // Step 1: Fetch session from sklad
        try {
            $sessionResponse = file_get_contents("{$skladUrl}/api/pay/{$sessionToken}");
            $sessionData = json_decode($sessionResponse, true);
        } catch (\Exception $e) {
            Yii::$app->response->statusCode = 503;
            return ['success' => false, 'error' => 'Could not reach sklad API'];
        }

        if (!($sessionData['success'] ?? false)) {
            Yii::$app->response->statusCode = 404;
            return ['success' => false, 'error' => $sessionData['message'] ?? 'Session not found'];
        }

        $session = $sessionData['data'];

        if ($session['status'] !== 'pending') {
            Yii::$app->response->statusCode = 422;
            return ['success' => false, 'error' => "Session is {$session['status']}"];
        }

        if ($session['is_expired'] ?? false) {
            Yii::$app->response->statusCode = 422;
            return ['success' => false, 'error' => 'Session expired'];
        }

        // Step 2: Resolve merchant
        try {
            $merchantResponse = file_get_contents("{$skladUrl}/api/pay/{$sessionToken}/merchant");
            $merchantData = json_decode($merchantResponse, true);
        } catch (\Exception $e) {
            Yii::$app->response->statusCode = 503;
            return ['success' => false, 'error' => 'Could not resolve merchant'];
        }

        $merchantUserId = $merchantData['data']['merchant_user_id'] ?? null;
        if (!$merchantUserId) {
            Yii::$app->response->statusCode = 422;
            return ['success' => false, 'error' => 'Merchant not found for this branch'];
        }

        $paymentAmount = (float)$session['total'];
        $exchangeRate = (float)(Yii::$app->params['uzsToUsdtRate'] ?? 1.0);
        $cryptoAmount = $paymentAmount * $exchangeRate;

        // Step 3: Check balance and execute payment
        $walletService = new WalletService();
        $walletService->init();

        try {
            $balanceData = $walletService->getBalance($user->id);
            $balance = $this->extractTokenBalance($balanceData, $token);

            if ($balance < $cryptoAmount) {
                Yii::$app->response->statusCode = 422;
                return [
                    'success' => false,
                    'error' => "Insufficient {$token} balance",
                    'data' => [
                        'balance' => $balance,
                        'required' => $cryptoAmount,
                        'token' => $token,
                    ],
                ];
            }
        } catch (\Exception $e) {
            Yii::$app->response->statusCode = 503;
            return ['success' => false, 'error' => 'Balance check failed: ' . $e->getMessage()];
        }

        try {
            $result = $walletService->pay($user->id, $merchantUserId, (string)$cryptoAmount, $token);

            // Extract tx data — WalletService returns {success, data: {txHash, ...}}
            $payData = $result['data'] ?? $result;
            $txHash = $payData['txHash'] ?? $payData['tx_hash'] ?? $result['txHash'] ?? 'unknown';
            $payerAddr = $payData['payerAddress'] ?? $payData['payer'] ?? '';

            // Step 4: Confirm payment to sklad
            $confirmContext = stream_context_create([
                'http' => [
                    'method' => 'POST',
                    'header' => "Content-Type: application/json\r\n",
                    'content' => json_encode([
                        'tx_hash' => $txHash,
                        'customer_wallet' => $payerAddr,
                        'payment_type' => 'crypto',
                        'token' => $token,
                    ]),
                    'ignore_errors' => true,
                ],
            ]);

            @file_get_contents("{$skladUrl}/api/pay/{$sessionToken}/confirm", false, $confirmContext);

            // Create transaction record
            $transaction = new \app\models\transaction\Transaction();
            $transaction->user_id = $user->id;
            $transaction->shop_id = null; // POS payment, no marketplace order
            $transaction->type_transaction = 'pos_payment';
            $transaction->type_payment = 'wallet';
            $transaction->amount = $paymentAmount;
            $transaction->status = 1;
            $transaction->save(false);

            return [
                'success' => true,
                'message' => 'Payment successful',
                'data' => [
                    'session_token' => $sessionToken,
                    'tx_hash' => $txHash,
                    'amount_uzs' => $paymentAmount,
                    'amount_crypto' => $cryptoAmount,
                    'token' => $token,
                    'merchant_name' => $session['merchant_name'] ?? null,
                    'branch_name' => $session['branch_name'] ?? null,
                    'items' => $session['items'] ?? [],
                ],
            ];
        } catch (\Exception $e) {
            Yii::$app->response->statusCode = 422;
            return ['success' => false, 'error' => 'Payment failed: ' . $e->getMessage()];
        }
    }

    /**
     * Get session details for QR payment preview (proxy to sklad).
     * GET /api/payment/pos-session?token=xxx
     */
    public function actionPosSession()
    {
        $token = Yii::$app->request->get('token');

        if (!$token) {
            Yii::$app->response->statusCode = 422;
            return ['success' => false, 'error' => 'token is required'];
        }

        $skladUrl = Yii::$app->params['skladApiUrl'] ?? 'https://api.warehouse.example.com';

        try {
            $response = @file_get_contents("{$skladUrl}/api/pay/{$token}");
            if ($response === false) {
                Yii::$app->response->statusCode = 404;
                return ['success' => false, 'error' => 'Session not found'];
            }
            $payload = Json::decode($response);
            if (!is_array($payload)) {
                Yii::$app->response->statusCode = 503;
                return ['success' => false, 'error' => 'Invalid response from sklad'];
            }

            if (($payload['success'] ?? false) !== true || !isset($payload['data']) || !is_array($payload['data'])) {
                Yii::$app->response->statusCode = 404;
                return $payload;
            }

            return [
                'success' => true,
                'data' => $this->mapPosSessionToOrderDetail($payload['data']),
            ];
        } catch (\Exception $e) {
            Yii::$app->response->statusCode = 503;
            return ['success' => false, 'error' => 'Could not fetch session'];
        }
    }

    /**
     * Get latest pending session for a branch (proxy to sklad).
     * GET /api/payment/pos-branch?branch_id=61
     * Mobile scans static QR → calls this → gets current order.
     */
    public function actionPosBranch()
    {
        $branchId = Yii::$app->request->get('branch_id');

        if (!$branchId) {
            Yii::$app->response->statusCode = 422;
            return ['success' => false, 'error' => 'branch_id is required'];
        }

        $skladUrl = Yii::$app->params['skladApiUrl'] ?? 'https://api.warehouse.example.com';

        try {
            $response = @file_get_contents("{$skladUrl}/api/pay/branch/{$branchId}");
            if ($response === false) {
                Yii::$app->response->statusCode = 404;
                return ['success' => false, 'error' => 'No active order for this branch'];
            }
            $payload = Json::decode($response);
            if (!is_array($payload)) {
                Yii::$app->response->statusCode = 503;
                return ['success' => false, 'error' => 'Invalid response from sklad'];
            }

            if (($payload['success'] ?? false) !== true || !isset($payload['data']) || !is_array($payload['data'])) {
                Yii::$app->response->statusCode = 404;
                return $payload;
            }

            return [
                'success' => true,
                'data' => $this->mapPosSessionToOrderDetail($payload['data'], (int)$branchId),
            ];
        } catch (\Exception $e) {
            Yii::$app->response->statusCode = 503;
            return ['success' => false, 'error' => 'Could not reach sklad'];
        }
    }

    /**
     * Convert sklad POS session payload to order/detail-like structure.
     */
    private function mapPosSessionToOrderDetail(array $session, ?int $branchId = null): array
    {
        $items = isset($session['items']) && is_array($session['items']) ? $session['items'] : [];
        $orderProducts = [];

        foreach ($items as $idx => $item) {
            $quantity = (float)($item['quantity'] ?? 0);
            $unitPrice = (float)($item['price'] ?? 0);
            $lineTotal = $quantity * $unitPrice;
            $productId = isset($item['product_id']) ? (int)$item['product_id'] : null;
            $name = (string)($item['name'] ?? 'POS item');

            $orderProducts[] = [
                'id' => $idx + 1,
                'delivery' => null,
                'price' => $lineTotal,
                'product_price' => $lineTotal,
                'unit_price' => $unitPrice,
                'total_cost' => $lineTotal,
                'delivery_cost' => 0,
                'amount' => $quantity,
                'status' => 1,
                'product' => [
                    'id' => $productId,
                    'name_ru' => $name,
                    'name_uz' => $name,
                    'name_en' => $name,
                    'image' => null,
                ],
                'refund' => null,
                'stock' => [
                    'id' => $branchId,
                    'name' => $session['branch_name'] ?? null,
                ],
                'bts_id' => null,
                'bts_status' => null,
                'bts_status_info' => null,
                'bts_price' => null,
                'address' => null,
            ];
        }

        return [
            'id' => null,
            'user' => Yii::$app->user->identity ?? null,
            'payment' => [
                'name_ru' => 'Crypto POS',
                'name_uz' => 'Crypto POS',
                'name_en' => 'Crypto POS',
            ],
            'delivery' => null,
            'price' => (float)($session['total'] ?? 0),
            'amount' => array_sum(array_map(static function ($i) {
                return (float)($i['quantity'] ?? 0);
            }, $items)),
            'delivery_cost' => 0,
            'discount_amount' => (float)($session['discount'] ?? 0),
            'promocode' => null,
            'name' => $session['merchant_name'] ?? null,
            'phone' => null,
            'address' => $session['branch_name'] ?? null,
            'status' => 0,
            'status_payment' => (($session['status'] ?? 'pending') === 'paid') ? 1 : 0,
            'date' => date('Y-m-d H:i:s'),
            'orderReceipt' => null,
            'orderProducts' => $orderProducts,
            'didox_documents' => [],
            'pos_meta' => [
                'session_token' => $session['session_token'] ?? null,
                'session_status' => $session['status'] ?? null,
                'subtotal' => (float)($session['subtotal'] ?? 0),
                'expires_at' => $session['expires_at'] ?? null,
                'is_expired' => (bool)($session['is_expired'] ?? false),
                'branch_name' => $session['branch_name'] ?? null,
                'merchant_name' => $session['merchant_name'] ?? null,
                'branch_id' => $branchId,
            ],
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
