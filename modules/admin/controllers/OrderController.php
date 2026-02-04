<?php
namespace app\modules\admin\controllers;

use Yii;
use yii\web\Controller;
use yii\web\UploadedFile;
use yii\web\HttpException;

use app\models\user\User;
use app\models\order\Order;
use app\models\order\OrderSearch;
use app\models\order\product\OrderProduct;
use app\models\order\product\OrderProductSearch;
use yii\services\BTS;

class OrderController extends Controller{
	public $user;
    
    public function beforeAction($action) {
        // Enable CSRF validation for BTS actions for security
        if (in_array($action->id, ['update-bts-status', 'get-bts-tracking'])) {
            $this->enableCsrfValidation = true;
        } else {
            $this->enableCsrfValidation = false;
        }
        if (Yii::$app->user->isGuest) {
            return $this->redirect(['/admin/default']);
        }
        $this->user = User::find()->with('moderatorAccess', 'moderatorAccess.moderator')->where(['id'=>Yii::$app->user->identity->id])->one();

        if (($this->user->role == User::ROLE_MODERATOR)) {
            $accesses = array();

            if ($this->user && $this->user->moderatorAccess) {
                foreach ($this->user->moderatorAccess as $v) {
                    if ($v && $v->moderator) {
                        $accesses[] = $v->moderator->url;
                    }
                }
            }

            if (!in_array('order', $accesses)) {
                throw new HttpException(403, 'Error access');
            }
        }

        return parent::beforeAction($action);
    }

    public function actionIndex() {

        $searchModel = new OrderSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);
        $dataProvider->query->with('user', 'payment', 'delivery');
        $dataProvider->query->orderBy('id desc');

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider
        ]);
    }

    public function actionView($id) {
        $model = Order::find()->with('user', 'orderProducts')->where(['id'=>$id])->one();
        if (!$model) {
            throw new HttpException(404, 'Page not found');
        }

        $products = OrderProduct::find()->with([
            'product', 
            'product.image', 
            'orderProductFilter', 
            'orderProductFilter.productFilter', 
            'orderProductFilter.productFilter.filter',
            'productReview', // Single review from order user
            'productReviews', // All reviews for the product
            'productReviews.user', // Users who wrote reviews
            'delivery' // Add delivery relationship
        ])->where(['order_id'=>$model->id])->all();

        // Get connected DIDOX documents
        $didoxDocuments = \app\models\didox\DidoxDocument::find()
            ->where(['order_id' => $model->id])
            ->with(['createdBy', 'toUser'])
            ->orderBy(['created_at' => SORT_DESC])
            ->all();

        // Get Didox logs for this order
        $didoxLogs = \app\models\Log::find()
            ->where(['category' => 'didox_order'])
            ->andWhere(['like', 'message', "Order #{$model->id}"])
            ->orderBy(['created_at' => SORT_DESC])
            ->all();

        return $this->render('view', [
            'model' => $model,
            'products' => $products,
            'didoxDocuments' => $didoxDocuments,
            'didoxLogs' => $didoxLogs
        ]);
    }

    /**
     * Create Didox documents (Invoice and Contract) for an order
     * @param int $id Order ID
     * @return mixed
     */
    public function actionCreateDidoxDocuments($id)
    {
        $model = Order::findOne($id);
        if (!$model) {
            throw new HttpException(404, 'Page not found');
        }

        // Use the service to create documents
        $result = \app\services\DidoxOrderService::createDocuments($model);
        
        if ($result['success'] && empty($result['messages'])) {
            // If success is true but no messages, it might be weird, but let's handle it
             Yii::$app->session->setFlash('success', 'Didox documents process completed successfully.');
        } elseif ($result['success']) {
            $message = implode('<br>', $result['messages']);
            Yii::$app->session->setFlash('success', $message);
        } else {
            $message = !empty($result['messages']) ? implode('<br>', $result['messages']) : 'Failed to create Didox documents.';
            Yii::$app->session->setFlash('error', $message);
        }
        
        return $this->redirect(['view', 'id' => $id]);
    }

    /**
     * Create only Didox Invoice for an order (Auto)
     * @param int $id Order ID
     * @return mixed
     */
    public function actionCreateDidoxInvoice($id)
    {
        $model = Order::findOne($id);
        if (!$model) {
            throw new HttpException(404, 'Page not found');
        }

        $result = \app\services\DidoxOrderService::createInvoice($model);
        
        if ($result['success']) {
            $message = implode('<br>', $result['messages']);
            Yii::$app->session->setFlash('success', $message ?: 'Invoice created successfully.');
        } else {
            $message = !empty($result['messages']) ? implode('<br>', $result['messages']) : 'Failed to create invoice.';
            Yii::$app->session->setFlash('error', $message);
        }
        
        return $this->redirect(['view', 'id' => $id]);
    }

    /**
     * Create only Didox Arbitrary Contract for an order (Auto)
     * @param int $id Order ID
     * @return mixed
     */
    public function actionCreateDidoxArbitrary($id)
    {
        $model = Order::findOne($id);
        if (!$model) {
            throw new HttpException(404, 'Page not found');
        }

        $result = \app\services\DidoxOrderService::createArbitrary($model);
        
        if ($result['success']) {
            $message = implode('<br>', $result['messages']);
            Yii::$app->session->setFlash('success', $message ?: 'Arbitrary contract created successfully.');
        } else {
            $message = !empty($result['messages']) ? implode('<br>', $result['messages']) : 'Failed to create arbitrary contract.';
            Yii::$app->session->setFlash('error', $message);
        }
        
        return $this->redirect(['view', 'id' => $id]);
    }

    /**
     * Get order data for AJAX requests (used by DIDOX forms)
     * @param int $id Order ID
     * @return array JSON response
     */
    public function actionGetOrderData($id) {
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        
        $order = Order::find()
            ->with(['user', 'user.addresses', 'orderProducts', 'orderProducts.product', 'orderProducts.product.ikpu', 'delivery'])
            ->where(['id' => $id])
            ->one();
            
        if (!$order) {
            return [
                'success' => false,
                'error' => 'Заказ не найден'
            ];
        }
        
        // Check if order is already connected to DIDOX document
        $existingConnection = \app\models\didox\DidoxDocument::isOrderConnectedToDidox($id);
        if ($existingConnection) {
            return [
                'success' => false,
                'error' => "Заказ уже связан с документом \"{$existingConnection['name']}\" ({$existingConnection['document_type_label']})"
            ];
        }
        
        return [
            'success' => true,
            'data' => [
                'id' => $order->id,
                'date' => $order->date,
                'address' => $order->address,
                'phone' => $order->phone,
                'name' => $order->name,
                'lastname' => $order->lastname,
                'total_sum' => $order->price,
                'amount' => $order->amount,
                'status' => $order->status,
                'user' => $order->user ? [
                    'id' => $order->user->id,
                    'name' => $order->user->name,
                    'lastname' => $order->user->lastname,
                    'middlename' => $order->user->middlename,
                    'phone' => $order->user->phone,
                    'email' => $order->user->email,
                    'inn' => $order->user->inn,
                    'last_address' => $order->user->last_address,
                    'addresses' => $order->user->addresses ? array_map(function($addr) {
                        return ['address' => $addr->address];
                    }, $order->user->addresses) : []
                ] : null,
                'delivery' => $order->delivery ? [
                    'id' => $order->delivery->id,
                    'name' => $order->delivery->name_ru,
                    'price' => $order->delivery->price
                ] : null,
                'products' => array_map(function($orderProduct) {
                    $product = $orderProduct->product;
                    $unitPrice = $orderProduct->amount > 0 ? $orderProduct->product_price / $orderProduct->amount : 0;
                    
                    return [
                        'id' => $orderProduct->id,
                        'product_id' => $orderProduct->product_id,
                        'name' => $product ? $product->name_ru : 'Продукт не найден',
                        'unit_price' => $unitPrice,
                        'amount' => $orderProduct->amount,
                        'total_price' => $orderProduct->product_price,
                        'delivery_cost' => $orderProduct->delivery_cost,
                        // IKPU data for DIDOX
                        'ikpu_code' => $product && $product->ikpu_code ? $product->ikpu_code : '08471001001000000', // Default service code
                        'ikpu_name' => $product && $product->ikpu ? $product->ikpu->name_ru : ($product && $product->ikpu_name ? $product->ikpu_name : 'Услуги'),
                        // Package/unit data
                        'package_code' => '1501886', // Default unit code for "шт."
                        'package_name' => 'шт.',
                        // VAT and origin defaults
                        'vat_rate' => 0, // Default 0% VAT
                        'origin' => 4, // Default "Местное"
                        // Additional product data
                        'description' => $product ? $product->description_ru : '',
                        'barcode' => '', // Products don't have barcodes by default
                        'marks' => '' // No marking by default
                    ];
                }, $order->orderProducts)
            ]
        ];
    }

    public function actionRemove($id) {
        $model = Order::find()->with('user', 'orderProducts')->where(['id'=>$id])->one();
        if (!$model) {
            throw new HttpException(404, 'Page not found');
        }
        
        if ($this->user && ($this->user->role != User::ROLE_USER) && $model && $model->delete()) {
            Yii::$app->session->setFlash('order_removed', 'Deleted');
        }

        return $this->redirect(['/admin/order']);
    }

    public function actionAccept($id, $status) {
        $model = Order::findOne($id);

        if (!$model) {
            throw new HttpException(404, 'Page not found');
        }

        if ($this->user && ($this->user->role != User::ROLE_USER) && $model) {
            if ($status == 1) {
                $message = 'Order received successfully';
            } else if ($status == 2) {
                $message = 'Order successfully rejected';
            } else if ($status == 3) {
                $message = 'Delivery in order';
            } else if ($status == 4) {
                $message = 'Order on the way';
            } else if ($status == 5) {
                $message = 'Order delivered';
            } else if ($status == 10) {
                $message = 'Order return';
            } else {
                throw new HttpException(404, 'Page not found');
            }

            $model->status = $status;
            if ($model->save(false)) {
                Yii::$app->session->setFlash('order_accepted', $message);
                return $this->redirect(Yii::$app->request->referrer);
            }
        }

        return $this->redirect(['/admin/order']);
    }

    /**
     * Update BTS status for order
     * @param int $id Order ID
     * @return mixed
     */
    public function actionUpdateBtsStatus($id)
    {
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;

        // Additional security check - ensure request is POST
        if (!Yii::$app->request->isPost) {
            return [
                'success' => false,
                'message' => 'Недопустимый метод запроса'
            ];
        }

        $order = Order::findOne($id);
        if (!$order) {
            return [
                'success' => false,
                'message' => 'Заказ не найден'
            ];
        }

        $updated = false;
        $results = [];
        $bts = new BTS();

        foreach ($order->orderProducts as $orderProduct) {
            if ($orderProduct->bts_id) {
                $response = $bts->trackOrder($orderProduct->bts_id);
                error_log("responsebts: " . json_encode($response));
                if ($response['success'] && isset($response['data'])) {
                    $trackingData = $response['data'];
                    $oldStatus = $orderProduct->bts_status;
                    $oldStatusInfo = $orderProduct->bts_status_info;
                    
                    // Update status from tracking data - API returns direct structure
                    if (isset($trackingData['status']['id'])) {
                        $orderProduct->bts_status = $trackingData['status']['id'];
                    }
                    if (isset($trackingData['status']['name'])) {
                        $orderProduct->bts_status_info = $trackingData['status']['name'];
                    }
                    
                    if ($orderProduct->save(false)) {
                        $updated = true;
                        $results[] = [
                            'bts_id' => $orderProduct->bts_id,
                            'old_status' => $oldStatus,
                            'new_status' => $orderProduct->bts_status,
                            'old_status_info' => $oldStatusInfo,
                            'new_status_info' => $orderProduct->bts_status_info,
                            'status_label' => BTS::getBtsStatusLabel($orderProduct->bts_status, 'ru')
                        ];
                    }
                } else {
                    $results[] = [
                        'bts_id' => $orderProduct->bts_id,
                        'error' => $response['error'] ?? 'Ошибка при обновлении статуса'
                    ];
                }
            }
        }

        if ($updated) {
            Yii::$app->session->setFlash('bts_status_updated', 'Статусы BTS успешно обновлены');
            return [
                'success' => true,
                'message' => 'Статусы BTS успешно обновлены',
                'results' => $results
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Не удалось обновить статусы BTS',
                'results' => $results
            ];
        }
    }

    /**
     * Get BTS tracking data for order
     * @param int $id Order ID
     * @return mixed
     */
    public function actionGetBtsTracking($id)
    {
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;

        // Additional security check - ensure request is POST
        if (!Yii::$app->request->isPost) {
            return [
                'success' => false,
                'message' => 'Недопустимый метод запроса'
            ];
        }

        $order = Order::findOne($id);
        if (!$order) {
            return [
                'success' => false,
                'message' => 'Заказ не найден'
            ];
        }

        $trackingData = [];
        $bts = new BTS();

        foreach ($order->orderProducts as $orderProduct) {
            if ($orderProduct->bts_id) {
                // Get order history instead of just tracking
                $historyResponse = $bts->getOrderHistory($orderProduct->bts_id);
                
                if ($historyResponse['success']) {
                    $historyData = $historyResponse['data'];
                    
                    // Process history entries
                    $processedHistory = [];
                    if (is_array($historyData)) {
                        foreach ($historyData as $entry) {
                            $processedHistory[] = [
                                'message' => $entry['message'] ?? '',
                                'timestamp' => $entry['timestamp'] ?? null,
                                'formatted_date' => isset($entry['timestamp']) ? date('d.m.Y H:i:s', $entry['timestamp']) : '',
                                'status_id' => $entry['status_id'] ?? null,
                                'status_label' => BTS::getBtsStatusLabel($entry['status_id'] ?? null, 'ru'),
                                'location' => $entry['location'] ?? '',
                                'tracking_link' => $entry['trackingLink'] ?? null
                            ];
                        }
                        
                        // Sort by timestamp descending (newest first)
                        usort($processedHistory, function($a, $b) {
                            return ($b['timestamp'] ?? 0) - ($a['timestamp'] ?? 0);
                        });
                    }
                    
                    $trackingData[] = [
                        'order_product_id' => $orderProduct->id,
                        'bts_id' => $orderProduct->bts_id,
                        'current_status' => $orderProduct->bts_status,
                        'current_status_info' => $orderProduct->bts_status_info,
                        'current_status_label' => BTS::getBtsStatusLabel($orderProduct->bts_status, 'ru'),
                        'history' => $processedHistory,
                        'raw_history_data' => $historyData
                    ];
                } else {
                    $trackingData[] = [
                        'order_product_id' => $orderProduct->id,
                        'bts_id' => $orderProduct->bts_id,
                        'error' => $historyResponse['error'] ?? 'Ошибка при получении истории заказа'
                    ];
                }
            }
        }

        return [
            'success' => true,
            'tracking_data' => $trackingData
        ];
    }
}
