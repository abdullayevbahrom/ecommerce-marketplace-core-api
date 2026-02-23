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
     */
    public function actionPay()
    {
        $request = Yii::$app->request;
        $aaWalletAddress = $request->post('aaWalletAddress');
        $token = $request->post('token');
        $amount = $request->post('amount');
        $merchant = $request->post('merchant');

        if (!$aaWalletAddress || !$token || !$amount || !$merchant) {
            return ['error' => 'Missing required parameters'];
        }

        try {
            $result = $this->walletService->pay($aaWalletAddress, $token, $amount, $merchant);
            return $result;
        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * Transfer Tokens (P2P)
     * POST /api/wallet/transfer
     * Accepts 'token' as address or symbol (USDT, USDC)
     */
    public function actionTransfer()
    {
        $id = Yii::$app->user->id;
        if (!$id) {
            return ['error' => 'User not found'];
        }

        $request = Yii::$app->request;
        $token = $request->post('token');
        $to = $request->post('to');
        $amount = $request->post('amount');

        if (!$token || !$to || !$amount) {
            return ['error' => 'Missing required parameters'];
        }

        try {
            // Service handles token resolution (symbol -> address)
            $result = $this->walletService->transfer($id, $token, $to, $amount);
            return $result;
        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * Transfer Tokens by Name (Explicit Endpoint)
     * POST /api/wallet/transfer-by-name
     */
    public function actionTransferByName()
    {
        return $this->actionTransfer();
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
