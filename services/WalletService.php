<?php

namespace app\services;

use Yii;
use app\models\user\User;
use yii\base\Component;
use yii\base\Exception;
use yii\helpers\Json;
use GuzzleHttp\Client;

/**
 * WalletService acts as a client for the external AA Wallet Service.
 */
class WalletService extends Component
{
    private $serviceUrl;
    private $client;

    const TOKENS = [
        'USDT' => '0x7b95CaDaf3Fe1154A7B663f3793856F7e9f21d16',
        'USDC' => '0x3f4A04341122360b304C9A896a2Dbfe4cca5B4AE',
    ];

    public function init()
    {
        parent::init();
        $this->serviceUrl = Yii::$app->params['walletServiceUrl'] ?? 'http://localhost:3001';
        $this->client = new Client(['base_uri' => $this->serviceUrl]);
    }

    /**
     * Resolve token symbol to address
     * @param string $token
     * @return string
     */
    public function resolveToken($token)
    {
        $upper = strtoupper($token);
        return self::TOKENS[$upper] ?? $token;
    }

    /**
     * Ensure wallet exists and return EOA address
     * @param int $userId
     * @return string EOA Address
     */
    public function ensureWallet($userId)
    {
        // Call external service to get/generate address
        $data = $this->fetchAddress($userId);
        return $data['eoaAddress'] ?? null;
    }

    /**
     * Get AA Address
     * @param int $userId
     * @return string AA Address
     */
    public function getAAAddress($userId)
    {
        $data = $this->fetchAddress($userId);
        return $data['aaAddress'] ?? null;
    }

    /**
     * Get Wallet Status
     * @param int $userId
     * @return array ['isDeployed' => bool]
     */
    public function getWalletStatus($userId)
    {
        try {
            $userLogin = $this->getUserLogin($userId);
            
            $response = $this->client->get('wallet/status', [
                'query' => [
                    'userId' => $userId,
                    'userLogin' => $userLogin
                ]
            ]);

            return json_decode($response->getBody()->getContents(), true);
        } catch (\Exception $e) {
            Yii::error('Wallet Status Error: ' . $e->getMessage());
        }
        return ['isDeployed' => false];
    }

    /**
     * Predict wallet addresses (same as getAAAddress for the external service)
     * @param int $userId
     * @return array ['eoa_address' => string, 'aa_address' => string]
     */
    public function predictWallet($userId)
    {
        $data = $this->fetchAddress($userId);
        return [
            'eoa_address' => $data['eoaAddress'] ?? '',
            'aa_address' => $data['aaAddress'] ?? ''
        ];
    }

    /**
     * Get Wallet Balance (ETH)
     * @param int|string $userOrAddress User ID or Ethereum Address
     */
    public function getBalance($userOrAddress)
    {
        try {
            $query = [];
            
            if (is_numeric($userOrAddress)) {
                $query['userId'] = $userOrAddress;
                $query['userLogin'] = $this->getUserLogin($userOrAddress);
            } else {
                // If it's an address, we can't use the standard endpoint which expects userId.
                // However, since the goal is to get the balance of a derived address,
                // and the backend derives it from userId, we should prefer passing userId.
                
                // If we absolutely must support address (e.g. for arbitrary checks), 
                // we would need a backend endpoint that accepts an address.
                // Assuming the backend ONLY supports userId as per Postman:
                return 0; 
            }

            $response = $this->client->get('wallet/balance', [
                'query' => $query
            ]);

            $data = json_decode($response->getBody()->getContents(), true);
            // Return array with balance and isDeployed status if available, or just balance for backward compatibility
            // But to support the requirement of showing status from balance response, we should probably return the whole data
            // However, existing calls might expect a scalar. Let's check usages.
            // Only usage is in WalletController::actionBalance and admin/user/view.php
            
            return $data; 
        } catch (\Exception $e) {
            Yii::error('Wallet Balance Error: ' . $e->getMessage());
        }
        return ['balance' => 0, 'isDeployed' => false];
    }

    /**
     * Deploy Wallet via External Service
     * @param int $userId
     * @return string Transaction Hash or Status
     */
    public function deployWallet($userId)
    {
        try {
            $userLogin = $this->getUserLogin($userId);
            
            $response = $this->client->post('wallet/deploy', [
                'json' => [
                    'userId' => $userId,
                    'userLogin' => $userLogin
                ]
            ]);

            $data = json_decode($response->getBody()->getContents(), true);
            return $data['txHash'] ?? 'Deployed';
            
        } catch (\Exception $e) {
            Yii::error('Wallet Deployment Error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Mint Token
     * @param string $to Address to mint to
     * @param string $amount Amount to mint
     * @param string $token Token address
     * @return array Response data
     */
    public function mintToken($to, $amount, $token = '0x7b95CaDaf3Fe1154A7B663f3793856F7e9f21d16')
    {
        try {
            $response = $this->client->post('token/mint', [
                'json' => [
                    'token' => $token,
                    'to' => $to,
                    'amount' => (string)$amount,
                    'requireFactoryAccount' => true
                ]
            ]);

            return json_decode($response->getBody()->getContents(), true);
        } catch (\Exception $e) {
            Yii::error('Token Mint Error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Transfer Token
     * @param int $userId
     * @param string $token Token address or symbol
     * @param string $to Recipient address
     * @param string $amount Amount
     * @return array Response data
     */
    public function transfer($userId, $token, $to, $amount)
    {
        try {
            $userLogin = $this->getUserLogin($userId);
            
            // Resolve token if it's a name
            $tokenAddress = $this->resolveToken($token);

            $response = $this->client->post('wallet/transfer', [
                'json' => [
                    'userId' => $userId,
                    'userLogin' => $userLogin,
                    'token' => $tokenAddress,
                    'to' => $to,
                    'amount' => (string)$amount
                ]
            ]);

            return json_decode($response->getBody()->getContents(), true);
        } catch (\Exception $e) {
            Yii::error('Transfer Error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Execute Batch Payment
     * @param string $aaWalletAddress
     * @param string $token Token address
     * @param string $amount Amount
     * @param string $merchant Merchant address
     * @param int $deadlineSeconds
     * @return array Response data
     */
    public function pay($aaWalletAddress, $token, $amount, $merchant, $deadlineSeconds = 600)
    {
        try {
            $response = $this->client->post('payment/execute-batch', [
                'json' => [
                    'aaWalletAddress' => $aaWalletAddress,
                    'token' => $token,
                    'amount' => (string)$amount,
                    'merchant' => $merchant,
                    'deadlineSeconds' => $deadlineSeconds
                ]
            ]);

            return json_decode($response->getBody()->getContents(), true);
        } catch (\Exception $e) {
            Yii::error('Payment Error: ' . $e->getMessage());
            throw $e;
        }
    }

    // --- Internal Helpers ---

    private function fetchAddress($userId)
    {
        try {
            $userLogin = $this->getUserLogin($userId);
            
            $response = $this->client->get('wallet/address', [
                'query' => [
                    'userId' => $userId,
                    'userLogin' => $userLogin
                ]
            ]);

            return json_decode($response->getBody()->getContents(), true);
        } catch (\Exception $e) {
            Yii::error('Wallet Fetch Error: ' . $e->getMessage());
        }
        return [];
    }

    private function getUserLogin($userId)
    {
        $user = User::findOne($userId);
        // Use phone number as login for Wallet Service as requested
        return $user ? ($user->phone ?: $user->login ?: $user->email ?: 'user_' . $userId) : 'unknown';
    }
}
