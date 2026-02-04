<?php
namespace yii\services;

use Yii;
use yii\rest\Controller;

use app\models\Order;

require __DIR__ . '/payme/AbstractPayme.php';
require __DIR__ . '/payme/DbTransactionProvider.php';

class Payme extends payme\AbstractPayme
{
    protected $accounts = ["id"];
    protected $tableName = "transaction_payme";
    protected $minSum = 1000;
    protected $maxSum = 100000;
    protected $timeout = 6000 * 1000;
    protected $canCancelSuccessTransaction = false;
    protected $userKey = "user_id";

    private $config = [
        'merchant' => '',
        'login' => 'Paycom',
        'key' => '',
        'test_key' => '',
    ];

    private $pdo;

    public function __construct($request)
    {
        $host = 'localhost';
        $db   = '';
        $user = '';
        $pass = '';
        $charset = 'utf8';
        $dsn = "mysql:host=$host;dbname=$db;charset=$charset";
        $opt = [
            \PDO::ATTR_ERRMODE            => \PDO::ERRMODE_EXCEPTION,
            \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
            \PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        $pdo = new \PDO($dsn, $user, $pass, $opt);
        parent::__construct($request, new payme\DbTransactionProvider($this->tableName, $pdo));
        $this->pdo = $pdo;
    }

    public function auth()
    {
        $headers = getallheaders();

        if (!$headers ||
            !isset($headers['Authorization']) ||
            !preg_match('/^\s*Basic\s+(\S+)\s*$/i', $headers['Authorization'], $matches) ||
            base64_decode($matches[1]) != $this->config['login'] . ":" . $this->password()
        ) {
            return false;
        }

        return true;
    }

    public function password() {
        $pass = $this->pdo->query("SELECT password FROM `payme_password` WHERE id=1")->fetch();

        return $pass['password'];
    }

    protected function checkPerformTransaction()
    {
        if (!$this->auth()) {
            return $this->response->error(payme\PaymeResponse::AUTH_ERROR);
        }

        if (!$this->request->hasAccounts($this->accounts) || !$this->request->hasParam(["amount"])) {
            return $this->response->error(payme\PaymeResponse::JSON_RPC_ERROR);
        }

        $accounts = $this->request->getParam('account');
        $amount = $this->getAmount($this->request->getParam("amount"));

        $invoice = $this->getInvoice($accounts['id']);
        if (!$invoice) {
            return $this->response->error(payme\PaymeResponse::USER_NOT_FOUND);
        }

        if (!$this->isValidAmount($invoice['price'], $amount)) {
            return $this->response->error(payme\PaymeResponse::WRONG_AMOUNT);
        }

        return $this->response->successCheckPerformTransaction();
    }

    protected function createTransaction()
    {
        if (!$this->auth()) {
            return $this->response->error(payme\PaymeResponse::AUTH_ERROR);
        }
        if (!$this->request->hasAccounts($this->accounts) || !$this->request->hasParam(["amount", "time", "id"])) {
            return $this->response->error(payme\PaymeResponse::JSON_RPC_ERROR);
        }
        $accounts = $this->request->getParam('account');
        $amount = $this->getAmount($this->request->getParam("amount"));
        $transId = $this->request->getParam('id');
        $time = $this->request->getParam("time");

        $trans = $this->provider->getByTransId($transId);

        if ($trans) {
            if ($trans['state'] != 1) {
                return $this->response->error(payme\PaymeResponse::CANT_PERFORM_TRANS);
            }

            if (!$this->checkTimeout($trans['create_time'])) {
                $this->provider->update($transId, [
                    "state" => -1,
                    "reason" => 4
                ]);
                return $this->response->error(payme\PaymeResponse::CANT_PERFORM_TRANS, [
                    "uz" => "Vaqt tugashi o'tdi",
                    "ru" => "Тайм-аут прошел",
                    "en" => "Timeout passed"
                ]);
            }
            return $this->response->successCreateTransaction(
                $trans['create_time'],
                $trans['id'],
                $trans['state']
            );
        }

        $invoice = $this->getInvoice($accounts['id']);
        if (!$invoice) {
            return $this->response->error(payme\PaymeResponse::USER_NOT_FOUND);
        }

        if (!$this->isValidAmount($invoice['price'], $amount)) {
            return $this->response->error(payme\PaymeResponse::WRONG_AMOUNT);
        }

        $trans = $this->provider->getByOwnerId($invoice['user_id'], $invoice['id']);
        if ($trans && $trans['state'] == 1) {
            return $this->response->error(payme\PaymeResponse::PENDING_PAYMENT);
        }
        if ($trans && $trans['state'] == 2) {
            return $this->response->error(payme\PaymeResponse::CANT_PERFORM_TRANS);
        }
        if ($trans && $trans['state'] == -2) {
            return $this->response->error(payme\PaymeResponse::CANT_PERFORM_TRANS);
        }

        try {
            $this->provider->insert([
                'transaction' => $transId,
                'payme_time' => $time,
                'amount' => $amount,
                'state' => 1,
                'create_time' => $this->microtime(),
                'user_id' => $invoice[$this->userKey],
                'id' => $invoice['id'],
            ]);
            $trans = $this->provider->getByTransId($transId);
            
            return $this->response->successCreateTransaction($trans['create_time'], $trans['id'], $trans['state']);
        } catch (\Exception $e) {
            return $this->response->error(payme\PaymeResponse::SYSTEM_ERROR);
        }
    }

    protected function performTransaction()
    {
        if (!$this->auth()) {
            return $this->response->error(payme\PaymeResponse::AUTH_ERROR);
        }

        if (!$this->request->hasParam(["id"])) {
            return $this->response->error(payme\PaymeResponse::JSON_RPC_ERROR);
        }

        $transId = $this->request->getParam('id');
        $trans = $this->provider->getByTransId($transId);

        if (!$trans) {
            return $this->response->error(payme\PaymeResponse::TRANS_NOT_FOUND);
        }
        
        if ($trans['state'] != 1) {
            if ($trans['state'] == 2) {
                return $this->response->successPerformTransaction($trans['state'], $trans['perform_time'], $trans['id']);
            } else {
                return $this->response->error(payme\PaymeResponse::CANT_PERFORM_TRANS);
            }
        }

        if (!$this->checkTimeout($trans['create_time'])) {
            $this->provider->update($transId, [
                "state" => -1,
                "reason" => 4
            ]);
            return $this->response->error(payme\PaymeResponse::CANT_PERFORM_TRANS, [
                "uz" => "Vaqt tugashi o'tdi",
                "ru" => "Тайм-аут прошел",
                "en" => "Timeout passed"
            ]);
        }

        
        try {
            $this->fillUpBalance($trans['order_id'], $trans['amount']);
            
            $performTime = $this->microtime();
            $this->provider->update($transId, [
                "state" => 2,
                "perform_time" => $performTime
            ]);
            
            return $this->response->successPerformTransaction(2, $performTime, $trans['id']);
        } catch (\Exception $e) {
            return $this->response->error(payme\PaymeResponse::CANT_PERFORM_TRANS);
        }
    }

    protected function checkTransaction()
    {
        if (!$this->auth()) {
            return $this->response->error(payme\PaymeResponse::AUTH_ERROR);
        }

        if (!$this->request->hasParam(["id"])) {
            return $this->response->error(payme\PaymeResponse::JSON_RPC_ERROR);
        }
        $transId = $this->request->getParam("id");
        $trans = $this->provider->getByTransId($transId);
        if ($trans) {
            return $this->response->successCheckTransaction(
                $trans['create_time'],
                $trans['perform_time'],
                $trans['cancel_time'],
                $trans['id'],
                $trans['state'],
                $trans['reason']
            );
        } else {
            return $this->response->error(payme\PaymeResponse::TRANS_NOT_FOUND);
        }
    }

    protected function cancelTransaction()
    {
        if (!$this->auth()) {
            return $this->response->error(payme\PaymeResponse::AUTH_ERROR);
        }

        if (!$this->request->hasParam(["id", "reason"])) {
            return $this->response->error(payme\PaymeResponse::JSON_RPC_ERROR);
        }
        $transId = $this->request->getParam("id");
        $reason = $this->request->getParam("reason");
        $trans = $this->provider->getByTransId($transId);
        if (!$trans) {
            $this->response->error(payme\PaymeResponse::TRANS_NOT_FOUND);
        }
        if ($trans['state'] == 1) {
            $cancelTime = $this->microtime();
            $this->provider->update($transId, [
                "state" => -1,
                "cancel_time" => $cancelTime,
                "reason" => $reason
            ]);
            return $this->response->successCancelTransaction(-1, $cancelTime, $trans['id']);
        }
        if ($trans['state'] != 2) {
            return $this->response->successCancelTransaction($trans['state'], $trans['cancel_time'], $trans['id']);
        }
        try {
            $this->withdrawBalance($trans['order_id'], $trans['amount']);
            $cancelTime = $this->microtime();
            $this->provider->update($transId, [
                "state" => -2,
                "cancel_time" => $cancelTime,
                "reason" => $reason
            ]);
            return $this->response->successCancelTransaction(-2, $cancelTime, $trans['id']);
        } catch (\Exception $e) {
            return $this->response->error(payme\PaymeResponse::CANT_CANCEL_TRANSACTION);
        }
    }

    public function getStatement()
    {
        if (!$this->auth()) {
            return $this->response->error(payme\PaymeResponse::AUTH_ERROR);
        }
        if (!$this->request->hasParam(["id", "reason"])) {
            return $this->response->error(payme\PaymeResponse::JSON_RPC_ERROR);
        }

        return $this->pdo->query("SELECT * FROM `payme_uz` WHERE create_time < ".time()." AND state=1");
    }

    protected function changePassword()
    {
        if (!$this->auth()) {
            return $this->response->error(payme\PaymeResponse::AUTH_ERROR);
        }

        $pass = $this->password();

        $payme_password = $this->request->getParam("password");

        //$change_password = $this->pdo->query("UPDATE `payme_password` SET `password`='{$payme_password}' WHERE `id`=1");

        return $this->response->success([
            'success' => true
        ]);
    }

    private function fillUpBalance($id, $amount)
    {
        $invoice = $this->getInvoice($id);

        $user_id = $invoice['user_id'];
        $date = date('Y-m-d H:i:s');

        if (!$invoice) {
            throw new \Exception("Can't find order");
        }

        $time = time();
        $this->pdo->query("UPDATE `order` SET `status_payment` = 1 WHERE id = {$user_id}");

        $this->pdo->query("INSERT INTO `transaction_payme` (user_id, order_id, amount, status, date) VALUES ({$user_id}, {$id}, {$amount}, 1, '{$date}')");
    }

    private function withdrawBalance($id, $amount)
    {
        $invoice = $this->getInvoice($id);
        if (!$invoice) {
            throw new \Exception("Can't find order");
        }

        $this->pdo->query("UPDATE `order` SET `status_payment` = 2 WHERE id = {$id}");
    }

    private function checkTimeout($created_time)
    {
        return $this->microtime() <= ($created_time + $this->timeout);
    }

    private function getInvoice($id) {
        return $this->pdo->query("SELECT * FROM `order` WHERE `id` = '{$id}'")->fetch();
    }

    private function getUser($id) {
        return $this->pdo->query("SELECT * FROM `user` WHERE `id` = '{$id}'")->fetch();
    }

    private function isValidAmount($clientAmount, $paymeAmount) {
        return $clientAmount == $paymeAmount;
    }

    private function getAmount($amount) {
        return $amount / 100;
    }
}