<?php
namespace app\modules\api\controllers;

use Yii;
use yii\web\HttpException;
use yii\rest\Controller;
use yii\filters\auth\HttpBearerAuth;
use yii\services\BTS;
use app\models\product\Product;

class BtsController extends Controller {
    /**
     * Legacy internal region IDs to new BTS region codes.
     * This keeps old frontend builds working while the API expects regionCode.
     */
    private const LEGACY_REGION_ID_TO_CODE = [
        '2' => '60',
        '3' => '50',
        '4' => '90',
        '5' => '10',
        '6' => '01',
        '7' => '80',
        '8' => '30',
        '9' => '25',
        '10' => '85',
        '11' => '95',
        '12' => '20',
        '13' => '75',
        '14' => '70',
        '15' => '40',
    ];
    
    public function beforeAction($action) {
        $this->enableCsrfValidation = false;

        Yii::$app->response->getHeaders()->add('Access-Control-Allow-Origin', '*');
        Yii::$app->response->getHeaders()->add('Access-Control-Allow-Methods', 'GET, POST, PUT, PATCH, DELETE, HEAD, OPTIONS');
        Yii::$app->response->getHeaders()->add('Access-Control-Allow-Headers', 'Content-Type, X-Auth-Token, Origin, Authorization');

        if (Yii::$app->request->headers->has('OPTIONS')) {
            throw new HttpException(200, 'OK');
        }

        // Language detection and setup
        Yii::$app->session->set('language', 'ru');
        $langs = ['ru', 'en', 'uz'];

        $headers = Yii::$app->request->headers;
        if($headers->has('Content-Language')) {
            $lang = $headers->get('Content-Language');
            if(in_array($lang, $langs)) {
                Yii::$app->session->set('language', $lang);
            }
        }

        return parent::beforeAction($action);
    }

    public function behaviors() {
        $behaviors = parent::behaviors();
        $behaviors['authenticator'] = [
            'class' => HttpBearerAuth::className(),
            'optional' => ['*'], // All actions are optional (no authentication required)
        ];

        $auth = $behaviors['authenticator'];
        unset($behaviors['authenticator']);

        $behaviors['corsFilter'] = [
            'class' => \yii\filters\Cors::className(),
            'cors' => [
                'Access-Control-Allow-Origin' => ['*'],
                'Access-Control-Request-Method' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'HEAD', 'OPTIONS'],
                'Access-Control-Request-Headers' => ['*'],
                'Access-Control-Allow-Credentials' => true,
                'Access-Control-Max-Age' => 86400,
                'Access-Control-Expose-Headers' => [],
            ]
        ];

        $behaviors['authenticator'] = $auth;
        $behaviors['authenticator']['except'] = ['options'];
        
        return $behaviors;
    }

    public $serializer = [
        'class' => 'yii\rest\Serializer',
        'collectionEnvelope' => 'data',
    ];

    /**
     * Calculate shipping price using BTS API (order-calculate endpoint)
     * POST /api/bts/calculate
     * 
     * Required params:
     * - product_id: Product ID
     * - receiverCityCode: Receiver BTS city code (e.g. "0101")
     * 
     * Optional params:
     * - amount: Quantity of products (default: 1)
     * - pickup_type: 'courier', 'branch', 'self' (default: 'branch')
     * - dropoff_type: 'courier', 'branch', 'self' (default: 'courier')
     * - is_multiple_cost: 0 or 1 (default: 0)
     */
    public function actionCalculate() {
        $post = Yii::$app->request->post();

        // Validate required fields
        $requiredFields = ['product_id', 'receiverCityCode'];
        foreach ($requiredFields as $field) {
            if (!isset($post[$field]) || $post[$field] === '') {
                Yii::$app->response->statusCode = 422;
                return ['errors' => [$field => ['Поле обязательно для заполнения']]];
            }
        }

        // Validate and set amount (quantity)
        $amount = 1;
        if (isset($post['amount'])) {
            if (!is_numeric($post['amount']) || $post['amount'] <= 0) {
                Yii::$app->response->statusCode = 422;
                return ['errors' => ['amount' => ['Количество должно быть положительным числом']]];
            }
            $amount = (int)$post['amount'];
        }

        // Validate product exists and has stock
        $product = Product::find()
            ->with('stock')
            ->where(['product.id' => $post['product_id'], 'product.status' => 1])
            ->marketplaceVisible()
            ->one();
        if (!$product) {
            Yii::$app->response->statusCode = 422;
            return ['errors' => ['product_id' => ['Продукт не найден или неактивен']]];
        }

        if (!$product->stock_id || !$product->stock) {
            Yii::$app->response->statusCode = 422;
            return ['errors' => ['product_id' => ['Продукт не привязан к складу']]];
        }

        if (!$product->stock->bts_city_id) {
            Yii::$app->response->statusCode = 422;
            return ['errors' => ['product_id' => ['У склада продукта не указан город для доставки']]];
        }

        // Get sender city code from product stock (stored as BTS city code string, e.g. "0101")
        $senderCityCode = (string)$product->stock->bts_city_id;

        // Product weight is stored in grams in admin/shop forms.
        $unitWeight = $this->normalizeProductWeightToKg($product->weight ?? null);
        $totalWeight = $unitWeight * $amount;
        $totalWeight = max(1.0, $totalWeight); // Minimum 1kg

        // Calculate total volume from product dimensions (in cm)
        $unitLength = $product->length ?: 10;
        $unitWidth = $product->width ?: 10;
        $unitHeight = $product->height ?: 10;

        // Smart stacking: keep base dimensions (length x width), stack by height
        $volumeX = max(10, (int)$unitLength);
        $volumeY = max(10, (int)$unitWidth);
        $volumeZ = max(10, (int)($unitHeight * $amount));

        // Get delivery type options from request or use defaults
        $pickupType = isset($post['pickup_type']) && in_array($post['pickup_type'], ['courier', 'branch', 'self'])
            ? $post['pickup_type']
            : 'branch';
        $dropoffType = isset($post['dropoff_type']) && in_array($post['dropoff_type'], ['courier', 'branch', 'self'])
            ? $post['dropoff_type']
            : 'courier';
        $isMultipleCost = isset($post['is_multiple_cost']) ? (int)$post['is_multiple_cost'] : 0;

        try {
            $calculationData = [
                'senderCityCode' => $senderCityCode,
                'receiverCityCode' => (string)$post['receiverCityCode'],
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

            $btsService = new BTS();
            $result = $btsService->calculateOrder($calculationData);

            if ($result && $result['success'] && isset($result['data'])) {
                $priceKey = $pickupType . '_to_' . $dropoffType;
                $price = null;
                $allPrices = [];

                $priceKeys = ['branch_to_branch', 'branch_to_courier', 'courier_to_branch', 'courier_to_courier'];
                foreach ($priceKeys as $key) {
                    if (isset($result['data'][$key])) {
                        $allPrices[$key] = $result['data'][$key];
                        if ($key === $priceKey && isset($result['data'][$key]['price'])) {
                            $price = $result['data'][$key]['price'];
                        }
                    }
                }

                if ($price === null && isset($result['data']['price'])) {
                    $price = $result['data']['price'];
                }

                if ($price === null && !empty($allPrices)) {
                    foreach ($allPrices as $priceData) {
                        if (isset($priceData['available']) && $priceData['available'] && isset($priceData['price'])) {
                            $price = $priceData['price'];
                            break;
                        }
                    }
                }

                return [
                    'data' => [
                        'price' => $price,
                        'price_key' => $priceKey,
                        'all_prices' => $isMultipleCost ? $allPrices : null,
                        'currency' => 'UZS',
                        'bts_response' => $result['data'],
                        'product' => [
                            'id' => $product->id,
                            'name' => $product->name_ru,
                            'amount' => $amount,
                            'stock' => [
                                'id' => $product->stock->id,
                                'name' => $product->stock->name_ru,
                                'city_code' => $senderCityCode
                            ]
                        ],
                    ]
                ];
            } else {
                Yii::$app->response->statusCode = 500;
                return [
                    'errors' => ['general' => ['Ошибка при расчете стоимости доставки']],
                    'bts_response' => $result
                ];
            }

        } catch (\Exception $e) {
            Yii::error('BTS calculation error: ' . $e->getMessage(), __METHOD__);
            Yii::$app->response->statusCode = 500;
            return ['errors' => ['general' => ['Ошибка сервиса расчета доставки']]];
        }
    }

    /**
     * Get all regions from BTS API
     * GET /api/bts/regions
     */
    public function actionRegions() {
        try {
            $bts = new BTS();
            $result = $bts->fetchRegions();

            if ($result['success'] && isset($result['data']['items'])) {
                return ['data' => $result['data']['items']];
            }

            // Fallback to hardcoded regions
            $regions = BTS::getRegions('ru');
            $formattedRegions = [];
            foreach ($regions as $id => $name) {
                $formattedRegions[] = ['code' => (string)$id, 'name' => $name];
            }
            return ['data' => $formattedRegions];

        } catch (\Exception $e) {
            Yii::error('BTS regions error: ' . $e->getMessage(), __METHOD__);
            Yii::$app->response->statusCode = 500;
            return ['errors' => ['general' => ['Ошибка при получении списка регионов']]];
        }
    }

    /**
     * Get cities by region code from BTS API
     * GET /api/bts/cities?regionCode=01
     */
    public function actionCities() {
        try {
            $regionCode = Yii::$app->request->get('regionCode');
            $regionId = Yii::$app->request->get('regionId');

            if (!$regionCode && $regionId !== null && $regionId !== '') {
                $regionCode = $this->resolveRegionCode($regionId);
            }

            if (!$regionCode) {
                Yii::$app->response->statusCode = 422;
                return [
                    'errors' => [
                        'regionCode' => ['Код региона обязателен (например: 01, 10, 60)'],
                        'regionId' => ['Дополнительно поддерживается legacy regionId, если frontend еще не перешел на regionCode'],
                    ]
                ];
            }

            $bts = new BTS();
            $result = $bts->fetchCities($regionCode);

            if ($result['success'] && isset($result['data']['items'])) {
                return [
                    'data' => $result['data']['items'],
                    '_meta' => array_filter([
                        'bts_meta' => $result['data']['_meta'] ?? null,
                        'resolved_region_code' => $regionCode,
                        'requested_region_id' => $regionId,
                        'requested_region_code' => Yii::$app->request->get('regionCode'),
                    ], static fn($value) => $value !== null && $value !== ''),
                ];
            }

            Yii::$app->response->statusCode = $result['httpCode'] ?? 500;
            return ['errors' => ['general' => [$result['error'] ?? 'Ошибка при получении списка городов']]];

        } catch (\Exception $e) {
            Yii::error('BTS cities error: ' . $e->getMessage(), __METHOD__);
            Yii::$app->response->statusCode = 500;
            return ['errors' => ['general' => ['Ошибка при получении списка городов']]];
        }
    }

    private function resolveRegionCode($regionId): ?string
    {
        $regionId = trim((string)$regionId);

        if ($regionId === '') {
            return null;
        }

        // Already in new BTS code format.
        if (preg_match('/^\d{2}$/', $regionId)) {
            return $regionId;
        }

        // Common fallback if someone sends "1" instead of "01".
        if ($regionId === '1') {
            return '01';
        }

        return self::LEGACY_REGION_ID_TO_CODE[$regionId] ?? null;
    }

    private function normalizeProductWeightToKg($rawWeight): float
    {
        $weightInGrams = (float)$rawWeight;
        if ($weightInGrams <= 0) {
            return 1.0;
        }

        return $weightInGrams / 1000;
    }

    /**
     * Get delivery types
     * GET /api/bts/delivery-types
     */
    public function actionDeliveryTypes() {
        return [
            'data' => [
                [
                    'id' => 0,
                    'name' => 'Самовывоз',
                    'name_ru' => 'Самовывоз',
                    'name_uz' => 'O\'zi olib ketish',
                    'name_en' => 'Pickup',
                    'description' => 'Получение/отправка из/в отделение BTS'
                ],
                [
                    'id' => 1,
                    'name' => 'Доставка',
                    'name_ru' => 'Доставка',
                    'name_uz' => 'Yetkazib berish',
                    'name_en' => 'Door-to-door',
                    'description' => 'Доставка курьером до адреса'
                ]
            ]
        ];
    }
}
