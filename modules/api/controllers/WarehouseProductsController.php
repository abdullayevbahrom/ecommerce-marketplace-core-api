<?php

namespace app\modules\api\controllers;

use Yii;
use yii\rest\Controller;
use yii\web\Response;
use yii\web\BadRequestHttpException;
use yii\web\UnauthorizedHttpException;
use app\models\product\PendingProduct;

/**
 * DEACTIVATED: This controller and the "Pending Product" logic are no longer used.
 * We are moving to a direct sync approach where auxiliary data (colors, types, filters)
 * are managed via ProductAttributeController.
 * 
 * WarehouseProductsController handles product submissions from Sklad (Warehouse)
 * 
 * Endpoint: POST /api/warehouse-products/submit
 * Spec: SHOP_API_SPECIFICATION.md
 */
class WarehouseProductsController extends Controller
{
    public $enableCsrfValidation = false;

    public function init()
    {
        parent::init();
        // Logic deactivated per user request
        throw new \yii\web\GoneHttpException('This endpoint is deactivated. Please use the new direct attribute sync endpoints.');
    }

    public function behaviors()
    {
        $behaviors = parent::behaviors();
        $behaviors['contentNegotiator']['formats']['application/json'] = Response::FORMAT_JSON;
        return $behaviors;
    }

    /**
     * Check authentication token
     * Headers: X-Api-Token (md5 hash of branch_id + apiSecretKey)
     * Same pattern as Order.php sendOrderToWarehouse()
     */
    protected function checkAuth()
    {
        $headers = Yii::$app->request->headers;
        $token = $headers->get('X-Api-Token');
        
        if (!$token) {
            throw new UnauthorizedHttpException('Missing X-Api-Token header');
        }
        
        // Get branch_id from header or body
        $branchId = $headers->get('X-Branch-ID') ?? Yii::$app->request->post('branch_id');
        
        if (!$branchId) {
            throw new UnauthorizedHttpException('Missing X-Branch-ID header');
        }
        
        // Validate token: md5(branch_id + apiSecretKey)
        $expectedToken = md5($branchId . Yii::$app->params['apiSecretKey']);
        
        if ($token !== $expectedToken) {
            throw new UnauthorizedHttpException('Invalid API Token');
        }
    }

    /**
     * Submit Product from Sklad (Warehouse)
     * 
     * Method: POST /api/warehouse-products/submit
     * 
     * Headers:
     *   - Authorization: Bearer {ECOMMERCE_API_KEY}
     *   - X-Branch-ID: {branch_id}
     *   - X-Branch-Name: {branch_name} (optional)
     * 
     * Body: See SHOP_API_SPECIFICATION.md
     */
    public function actionSubmit()
    {
        $this->checkAuth();
        
        $request = Yii::$app->request;
        $headers = $request->headers;
        $data = $request->post();
        
        // Get branch info from headers or body
        $branchId = $headers->get('X-Branch-ID') ?? $data['branch_id'] ?? null;
        $branchName = $headers->get('X-Branch-Name') ?? $data['branch_name'] ?? null;
        
        // Validate required fields per spec
        if (empty($data['warehouse_product_id'])) {
            throw new BadRequestHttpException('warehouse_product_id is required');
        }
        
        if (empty($branchId)) {
            throw new BadRequestHttpException('branch_id (or X-Branch-ID header) is required');
        }
        
        if (empty($data['product']['name_ru']) && empty($data['product']['name'])) {
            throw new BadRequestHttpException('product.name_ru is required');
        }
        
        if (!isset($data['product']['price'])) {
            throw new BadRequestHttpException('product.price is required');
        }

        $warehouseProductId = $data['warehouse_product_id'];

        // Find existing submission or create new
        $model = PendingProduct::findOne([
            'branch_id' => $branchId, 
            'warehouse_product_id' => $warehouseProductId
        ]);
        
        $isNew = false;
        if (!$model) {
            $model = new PendingProduct();
            $model->branch_id = $branchId;
            $model->warehouse_product_id = $warehouseProductId;
            $isNew = true;
        }

        // Reset status if re-submitted after rejection
        if ($model->status == PendingProduct::STATUS_REJECTED) {
            $model->status = PendingProduct::STATUS_PENDING;
            $model->moderator_comment = null;
        }

        // Map fields from spec
        $model->branch_name = $branchName;
        $model->branch_yii_stock_id = $data['branch_yii_stock_id'] ?? null;
        $model->merchant_id = $data['merchant_id'] ?? null;
        $model->merchant_name = $data['merchant_name'] ?? null;
        $model->callback_url = $data['callback_url'] ?? null;
        
        // Store product data
        $product = $data['product'] ?? [];
        $model->name = $product['name'] ?? $product['name_ru'] ?? null;
        $model->name_ru = $product['name_ru'] ?? null;
        $model->name_en = $product['name_en'] ?? null;
        $model->name_uz = $product['name_uz'] ?? null;
        $model->description_ru = $product['description_ru'] ?? null;
        $model->description_en = $product['description_en'] ?? null;
        $model->description_uz = $product['description_uz'] ?? null;
        $model->price = $product['price'] ?? null;
        $model->price_small = $product['price_small'] ?? null;
        $model->price_opt = $product['price_opt'] ?? null;
        $model->amount = $product['amount'] ?? null;
        $model->barcode = $product['barcode'] ?? null;
        $model->sku = $product['sku'] ?? null;
        $model->weight = $product['weight'] ?? null;
        $model->discount = $product['discount'] ?? null;
        
        // Store full JSON payload for reference
        $model->data = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        
        if ($model->save()) {
            $submissionId = 'shop_sub_' . $model->id;
            
            Yii::info("Product submission received: {$submissionId} from branch {$branchId}", 'warehouse-sync');
            
            return [
                'success' => true,
                'submission_id' => $submissionId,
                'message' => 'Product received and queued for moderation',
                'data' => [
                    'warehouse_product_id' => $warehouseProductId,
                    'submission_id' => $submissionId,
                    'status' => 'pending_moderation',
                    'estimated_review_time' => '24-48 hours',
                    'created_at' => date('c'),
                ]
            ];
        } else {
            Yii::$app->response->statusCode = 422;
            return [
                'success' => false,
                'error' => 'Validation failed',
                'message' => 'The given data was invalid',
                'errors' => $model->errors
            ];
        }
    }

    /**
     * Check Submission Status
     * 
     * Method: GET /api/warehouse-products/status
     * Query params: warehouse_product_id, branch_id
     */
    public function actionStatus()
    {
        $this->checkAuth();
        
        $warehouseProductId = Yii::$app->request->get('warehouse_product_id');
        $branchId = Yii::$app->request->get('branch_id');

        if (!$warehouseProductId || !$branchId) {
            throw new BadRequestHttpException('warehouse_product_id and branch_id are required');
        }
        
        $model = PendingProduct::findOne([
            'branch_id' => $branchId,
            'warehouse_product_id' => $warehouseProductId
        ]);
        
        if (!$model) {
            throw new \yii\web\NotFoundHttpException('Submission not found');
        }

        $response = [
            'success' => true,
            'warehouse_product_id' => $model->warehouse_product_id,
            'submission_id' => 'shop_sub_' . $model->id,
            'status' => $model->status,
            'updated_at' => $model->updated_at,
        ];

        if ($model->status == PendingProduct::STATUS_REJECTED) {
            $response['comment'] = $model->moderator_comment;
        }
        
        if ($model->status == PendingProduct::STATUS_APPROVED && $model->approved_product_id) {
            $response['marketplace_product_id'] = $model->approved_product_id;
        }

        return $response;
    }
}
