<?php

namespace app\modules\api\controllers;

use Yii;
use yii\rest\Controller;
use app\services\WalletService;

use yii\filters\auth\HttpBearerAuth;

class WalletController extends Controller
{
    private $walletService;

    public function init()
    {
        parent::init();
        // Initialize the service manually or via DI if configured
        $this->walletService = new WalletService();
    }

    public function behaviors()
    {
        $behaviors = parent::behaviors();

        $behaviors['authenticator'] = [
            'class' => HttpBearerAuth::className(),
        ];

        return $behaviors;
    }

    /**
     * Get wallet address for the current user
     * GET /api/wallet/address
     */
    public function actionAddress()
    {
        $id = Yii::$app->user->id;

        if (!$id) {
            return ['error' => 'User not found'];
        }

        try {
            $eoa = $this->walletService->ensureWallet($id);
            $aa = $this->walletService->getAAAddress($id);

            return [
                'user_id' => $id,
                'eoa_address' => $eoa,
                'aa_address' => $aa
            ];
        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * Get wallet balance
     * GET /api/wallet/balance
     */
    public function actionBalance()
    {
        $id = Yii::$app->user->id;

        if (!$id) {
            return ['error' => 'User not found'];
        }

        $cacheKey = 'wallet_balance_' . $id;
        $cachedResponse = Yii::$app->cache->get($cacheKey);

        if ($cachedResponse !== false) {
            return $cachedResponse;
        }

        try {
            // Get balance data (includes balance, isDeployed, tokens if available)
            $balanceData = $this->walletService->getBalance($id);

            // Get address data
            $addresses = $this->walletService->predictWallet($id);

            $tokens = $balanceData['tokens'] ?? [];

            // Define images
            $images = [
                'ETH' => 'https://raw.githubusercontent.com/trustwallet/assets/master/blockchains/ethereum/info/logo.png',
                'USDT' => 'https://raw.githubusercontent.com/trustwallet/assets/master/blockchains/ethereum/assets/0xdAC17F958D2ee523a2206206994597C13D831ec7/logo.png',
                'USDC' => 'https://raw.githubusercontent.com/trustwallet/assets/master/blockchains/ethereum/assets/0xA0b86991c6218b36c1d19D4a2e9Eb0cE3606eB48/logo.png',
                'HUMO' => 'https://cdn.example.com/assets/cards/humo.png',
                'app' => 'https://cdn-icons-png.flaticon.com/512/555/555526.png',
            ];

            // Enhance existing tokens
            foreach ($tokens as &$token) {
                $sym = strtoupper($token['symbol']);
                $token['image'] = $images[$sym] ?? 'https://cdn-icons-png.flaticon.com/512/121/121799.png';
            }
            unset($token);

            // Construct response
            $response = [
                'aaAddress' => $addresses['aa_address'],
                'balance' => (string)($balanceData['balance'] ?? '0.0'),
                'unit' => 'ETH',
                'image' => $images['ETH'],
                'isDeployed' => (bool)($balanceData['isDeployed'] ?? false),
                'tokens' => $tokens,
                'recentTransactions' => $balanceData['recentTransactions'] ?? []
            ];

            Yii::$app->cache->set($cacheKey, $response, 60); // Cache for 1 minute

            return $response;
        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * Mint tokens (Test/Admin only)
     * POST /api/wallet/mint
     */
    public function actionMint()
    {
        $request = Yii::$app->request;
        $to = $request->post('to');
        $amount = $request->post('amount');
        $token = $request->post('token', '0x7b95CaDaf3Fe1154A7B663f3793856F7e9f21d16'); // Default USDT

        if (!$to || !$amount) {
            return ['error' => 'Missing required parameters: to, amount'];
        }

        try {
            $result = $this->walletService->mintToken($to, $amount, $token);
            return $result;
        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * Execute Payment
     * POST /api/wallet/pay
     * Accepts: merchantId (int), amount (string), symbol (string e.g. 'USDT')
     * payerId is the authenticated user.
     */
    public function actionPay()
    {

        $payerId = Yii::$app->user->id;
        if (!$payerId) {
            return ['error' => 'User not found'];
        }

        $request = Yii::$app->request;
        $merchantId = $request->post('merchantId');
        $amount = $request->post('amount');
        $symbol = $request->post('symbol');

        if (!$merchantId || !$amount || !$symbol) {
            return ['error' => 'Missing required parameters: merchantId, amount, symbol'];
        }

        try {
            $result = $this->walletService->pay($payerId, $merchantId, $amount, $symbol);
            return $result;
        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * @deprecated Transfer endpoint has been removed from the wallet backend. Use /payment/* instead.
     * POST /api/wallet/transfer
     */
    public function actionTransfer()
    {
        Yii::$app->response->statusCode = 410;
        return ['error' => 'Transfer functionality is no longer available. Use /payment/* endpoints instead.'];
    }

    /**
     * @deprecated Transfer endpoint has been removed from the wallet backend.
     * POST /api/wallet/transfer-by-name
     */
    public function actionTransferByName()
    {
        Yii::$app->response->statusCode = 410;
        return ['error' => 'Transfer functionality is no longer available. Use /payment/* endpoints instead.'];
    }

    /**
     * Approve Payment (pre-approval step)
     * POST /api/wallet/approve-payment
     */
    public function actionApprovePayment()
    {
        $payerId = Yii::$app->user->id;
        if (!$payerId) {
            return ['error' => 'User not found'];
        }

        $request = Yii::$app->request;
        $merchantId = $request->post('merchantId');
        $amount = $request->post('amount');
        $symbol = $request->post('symbol');

        if (!$merchantId || !$amount || !$symbol) {
            return ['error' => 'Missing required parameters: merchantId, amount, symbol'];
        }

        try {
            $result = $this->walletService->approvePayment($payerId, $merchantId, $amount, $symbol);
            return $result;
        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * Build payment batch (preview, no execution)
     * POST /api/wallet/build-batch
     */
    public function actionBuildBatch()
    {
        $payerId = Yii::$app->user->id;
        if (!$payerId) {
            return ['error' => 'User not found'];
        }

        $request = Yii::$app->request;
        $merchantId = $request->post('merchantId');
        $amount = $request->post('amount');
        $symbol = $request->post('symbol');

        if (!$merchantId || !$amount || !$symbol) {
            return ['error' => 'Missing required parameters: merchantId, amount, symbol'];
        }

        try {
            $result = $this->walletService->buildBatch($payerId, $merchantId, $amount, $symbol);
            return $result;
        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * Get supported tokens
     * GET /api/wallet/supported-tokens
     */
    public function actionSupportedTokens()
    {
        try {
            return $this->walletService->getSupportedTokens();
        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * Predict wallet addresses without creating them
     * GET /api/wallet/predict
     */
    public function actionPredict()
    {
        $id = Yii::$app->user->id;

        if (!$id) {
            return ['error' => 'User not found'];
        }

        try {
            $addresses = $this->walletService->predictWallet($id);
            return [
                'user_id' => $id,
                'eoa_address' => $addresses['eoa_address'],
                'aa_address' => $addresses['aa_address'],
                'predicted' => true
            ];
        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * Deploy AA Wallet
     * POST /api/wallet/deploy
     */
    public function actionDeploy()
    {
        $user_id = Yii::$app->user->id;

        if (!$user_id) {
            return ['error' => 'User not found'];
        }

        try {
            $txHash = $this->walletService->deployWallet($user_id);

            // Invalidate balance cache
            Yii::$app->cache->delete('wallet_balance_' . $user_id);

            return ['status' => 'Deployment initiated', 'txHash' => $txHash];
        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }
}
