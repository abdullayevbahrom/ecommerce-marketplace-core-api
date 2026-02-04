<?php

namespace app\controllers\api;

use Yii;
use yii\rest\Controller;
use app\models\order\Order;
use app\models\order\product\OrderProduct;

class WarehouseController extends Controller
{
    public $enableCsrfValidation = false;

    public function beforeAction($action)
    {
        if (!parent::beforeAction($action)) {
            return false;
        }

        if (!in_array($action->id, ['receive-sale', 'receive-inventory', 'health'])) {
            return true;
        }

        $token = Yii::$app->request->headers->get('X-Api-Token');
        $orderId = Yii::$app->request->post('yii_order_id') ?? Yii::$app->request->post('id');
        
        if (!$token || !$orderId) {
            Yii::error('Missing token or order ID in warehouse request', 'warehouse_webhook');
            return $this->asJson(['success' => false, 'message' => 'Invalid request']);
        }

        $expectedToken = md5($orderId . Yii::$app->params['apiSecretKey']);

        if ($token !== $expectedToken) {
            Yii::warning("Invalid token for order $orderId", 'warehouse_webhook');
            return $this->asJson(['success' => false, 'message' => 'Invalid token']);
        }

        return true;
    }

    public function actionReceiveSale()
    {
        $data = Yii::$app->request->post();

        if (!isset($data['sale_id'], $data['yii_order_id'])) {
            return ['success' => false, 'message' => 'Invalid data'];
        }

        $order = Order::findOne($data['yii_order_id']);
        if (!$order) {
            return ['success' => false, 'message' => 'Order not found'];
        }

        $order->warehouse_sale_id = $data['sale_id'];
        $order->warehouse_synced = 1;
        $order->warehouse_sync_date = date('Y-m-d H:i:s');

        if ($order->save()) {
            Yii::info(
                "Order {$order->id} synced with warehouse sale {$data['sale_id']}",
                'warehouse_sync'
            );
            return ['success' => true, 'message' => 'Sale received'];
        }

        return ['success' => false, 'message' => 'Failed to save order'];
    }

    public function actionReceiveInventory()
    {
        $data = Yii::$app->request->post();

        if (!isset($data['inventory_id'])) {
            return ['success' => false, 'message' => 'Invalid data'];
        }

        Yii::info(
            "Inventory received from warehouse: " . json_encode($data),
            'warehouse_webhook'
        );

        return ['success' => true, 'message' => 'Inventory received'];
    }

    public function actionHealth()
    {
        return ['success' => true, 'message' => 'Ecommerce API is healthy'];
    }
}
