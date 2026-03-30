<?php
namespace app\modules\api\controllers;

use app\models\order\product\OrderProduct;
use app\services\PayKeeperService;
use app\services\WalletService;
use YooKassa\Client;
use Yii;
use yii\helpers\ArrayHelper;
use yii\web\HttpException;
use yii\rest\Controller;
use yii\filters\auth\HttpBearerAuth;
use app\models\order\Order;

class PaymentController extends Controller {

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
            'optional' => ['notify']
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

    public function actionPay() {
        $orderId = Yii::$app->request->post('order_id');
        $userId  = Yii::$app->user->identity->id;

        if (!$orderId) {
            Yii::$app->response->statusCode = 422;
            return ['errors' => ['order_id' => 'order_id is required']];
        }

        $order = Order::find()
            ->where(['id' => $orderId, 'user_id' => $userId])
            ->one();

        if (!$order) {
            Yii::$app->response->statusCode = 404;
            return ['errors' => ['order' => 'Order not found']];
        }

        if ($order->status_payment == 1) {
            Yii::$app->response->statusCode = 422;
            return ['errors' => ['order' => 'Order is already paid']];
        }

        $walletPaymentId = Yii::$app->params['walletPaymentId'] ?? null;

        if ($walletPaymentId && (int)$order->payment_id === (int)$walletPaymentId) {
            return $this->handleCryptoPayment($order, $userId);
        }

        return $this->handleTraditionalPayment($order);
    }

    /**
     * Handle crypto/wallet payment (payment_id == walletPaymentId).
     * Returns the current balance, payment amount, and a redirect URL to the
     * currency backend that will execute (or reject) the on-chain transfer.
     */
    private function handleCryptoPayment(Order $order, int $userId): array
    {
        $token              = Yii::$app->params['walletDefaultToken'] ?? 'USDT';
        $currencyBackendUrl = rtrim(Yii::$app->params['currencyBackendUrl'] ?? Yii::$app->params['walletServiceUrl'] ?? '', '/');
        // TODO: Add real UZS→USDT exchange rate API. Currently 1:1.
        $exchangeRate       = (float)(Yii::$app->params['uzsToUsdtRate'] ?? 1.0);
        $paymentAmount      = (float)$order->price * $exchangeRate;

        // Retrieve current wallet balance
        $walletService = new WalletService();
        $walletService->init();

        try {
            $balanceData = $walletService->getBalance($userId);
        } catch (\Exception $e) {
            Yii::error('Wallet balance check failed for user ' . $userId . ': ' . $e->getMessage(), __METHOD__);
            Yii::$app->response->statusCode = 503;
            return ['errors' => ['wallet' => 'Could not retrieve wallet balance. Please try again later.']];
        }

        // Extract the balance for the configured token from the tokens array.
        // The external service returns { balance: <ETH>, tokens: [{ symbol, balance, ... }], ... }.
        $balance = $this->extractTokenBalance($balanceData, $token);

        if ($balance < $paymentAmount) {
            Yii::$app->response->statusCode = 422;
            return [
                'errors' => [
                    'wallet' => sprintf(
                        'Insufficient balance. Your %s balance is %s, but the payment requires %s %s.',
                        $token,
                        number_format($balance, 6, '.', ''),
                        number_format($paymentAmount, 6, '.', ''),
                        $token
                    ),
                ],
                'data' => [
                    'balance' => $balance,
                    'amount'  => $paymentAmount,
                    'token'   => $token,
                ],
            ];
        }

        $baseUrl = rtrim(Yii::$app->params['baseUrl'] ?? Yii::$app->request->hostInfo, '/');
        $payUrl = $baseUrl . '/api/app/pay/order/' . $order->id;

        return [
            'data' => [
                'order_id' => $order->id,
                'balance' => $balance,
                'amount'  => $paymentAmount,
                'token'   => $token,
                'pay_url' => $payUrl,
            ],
        ];
    }

    /**
     * Execute crypto payment for an order.
     * POST /api/app/pay/order/{orderId}
     * Checks ownership, balance, then calls the AA backend to transfer tokens.
     */
    public function actionPayOrder($orderId)
    {
        $userId = Yii::$app->user->identity->id;

        $order = Order::find()
            ->where(['id' => $orderId, 'user_id' => $userId])
            ->one();

        if (!$order) {
            Yii::$app->response->statusCode = 404;
            return ['success' => false, 'error' => 'Order not found or does not belong to you'];
        }

        if ($order->status_payment == 1) {
            Yii::$app->response->statusCode = 422;
            return ['success' => false, 'error' => 'Order is already paid'];
        }

        $walletPaymentId = Yii::$app->params['walletPaymentId'] ?? null;
        if (!$walletPaymentId || (int)$order->payment_id !== (int)$walletPaymentId) {
            Yii::$app->response->statusCode = 422;
            return ['success' => false, 'error' => 'This order does not use crypto payment'];
        }

        $token = Yii::$app->params['walletDefaultToken'] ?? 'USDT';
        $exchangeRate = (float)(Yii::$app->params['uzsToUsdtRate'] ?? 1.0);
        $paymentAmount = (float)$order->price * $exchangeRate;

        // Find the merchant (shop owner) to pay
        $shop = $order->shop;
        $merchantUserId = $shop ? $shop->user_id : null;

        if (!$merchantUserId) {
            Yii::$app->response->statusCode = 422;
            return ['success' => false, 'error' => 'Shop owner not found for this order'];
        }

        $walletService = new WalletService();
        $walletService->init();

        // Check balance first
        try {
            $balanceData = $walletService->getBalance($userId);
            $balance = $this->extractTokenBalance($balanceData, $token);

            if ($balance < $paymentAmount) {
                Yii::$app->response->statusCode = 422;
                return [
                    'success' => false,
                    'error' => sprintf('Insufficient %s balance: %s available, %s required', $token, $balance, $paymentAmount),
                    'data' => ['balance' => $balance, 'amount' => $paymentAmount, 'token' => $token],
                ];
            }
        } catch (\Exception $e) {
            Yii::$app->response->statusCode = 503;
            return ['success' => false, 'error' => 'Could not check wallet balance: ' . $e->getMessage()];
        }

        // Execute payment via AA backend
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

            return [
                'success' => true,
                'message' => 'Payment successful',
                'data' => [
                    'order_id' => $order->id,
                    'amount_paid' => $paymentAmount,
                    'token' => $token,
                    'tx_result' => $result,
                ],
            ];
        } catch (\Exception $e) {
            $dbTransaction->rollBack();
            Yii::$app->response->statusCode = 422;
            return ['success' => false, 'error' => 'Payment failed: ' . $e->getMessage()];
        }
    }

    /**
     * Extract the balance for a specific token symbol from the wallet service response.
     * Falls back to the top-level `balance` field (ETH) when `tokens` is absent.
     */
    private function extractTokenBalance(array $balanceData, string $symbol): float
    {
        $tokens = $balanceData['tokens'] ?? [];

        foreach ($tokens as $tokenEntry) {
            if (isset($tokenEntry['symbol']) && strtoupper($tokenEntry['symbol']) === strtoupper($symbol)) {
                return (float)($tokenEntry['balance'] ?? 0);
            }
        }

        // Fallback: use the top-level balance value (e.g. ETH or a single-token setup)
        return (float)($balanceData['balance'] ?? 0);
    }

    /**
     * Handle traditional payment methods (PayKeeper + YooKassa).
     * Generates all available payment URLs for the given order.
     */
    private function handleTraditionalPayment(Order $order): array
    {
        $orderProducts = OrderProduct::find()
            ->where(['order_id' => $order->id])
            ->all();

        $amount = 0;
        foreach ($orderProducts as $orderProduct) {
            $amount += $orderProduct->price;
        }

        $service  = new PayKeeperService();
        $payUrl   = $service->get_invoice_url($order->id, $amount);
        $bandCard = $this->createPayment($amount, $order->id, 'bank_card');
        $yooMoney = $this->createPayment($amount, $order->id, 'yoo_money');

        return [
            'data' => [
                'pay_url'   => $payUrl,
                'band_card' => $bandCard,
                'yoo_money' => $yooMoney,
            ],
        ];
    }

    public function actionNotify() {
        $post    = Yii::$app->request->post();
        $service = new PayKeeperService();
        $id      = ArrayHelper::getValue($post, 'id', 0);
        $sum     = ArrayHelper::getValue($post, 'sum', 0);
        $clientid = ArrayHelper::getValue($post, 'clientid', 0);
        $orderid  = ArrayHelper::getValue($post, 'orderid', 0);
        $key      = ArrayHelper::getValue($post, 'key', 0);
        return [
            'success' => $service->notify($id, $sum, $clientid, $orderid, $key)
        ];
    }

    public function createPayment($amount = 1, $desc = ' ', $type = 'yoo_money') {
        $amount = !$amount ? 1 : $amount;
        $desc   = empty($desc) ? 'Оплата' : $desc;
        $client = new Client();
        $client->setAuth('239537', 'test_oEKmFN2MHOIGsGv0ubYpO70jPToj94bv3xTNDvPVi9U');
        $resp = $client->createPayment(
            [
                'amount' => [
                    'value'    => $amount,
                    'currency' => 'RUB',
                ],
                'description'  => $desc,
                'confirmation' => [
                    'type'       => 'redirect',
                    'return_url' => Yii::$app->getUrlManager()->createAbsoluteUrl('/payment/success'),
                ],
                'payment_method_data' => [
                    'type' => $type,
                ],
            ],
            uniqid('', true)
        );
        return $resp->getConfirmation()->getConfirmationUrl();
    }
}
