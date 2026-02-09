<?php
namespace app\modules\api\controllers;

use Yii;
use yii\web\Response;
use yii\web\HttpException;
use yii\rest\Controller;
use yii\filters\auth\HttpBearerAuth;
use yii\services\BTS;
use app\models\product\Product;
use app\models\stock\Stock;

class BtsController extends Controller {
    
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

        $behaviors['authenticator']['except'] = ['options'];
        $behaviors['authenticator'] = $auth;
        
        return $behaviors;
    }

    public $serializer = [
        'class' => 'yii\rest\Serializer',
        'collectionEnvelope' => 'data',
    ];

    /**
     * Calculate shipping price using BTS API (order-calculator endpoint)
     * POST /api/bts/calculate
     * 
     * Required params:
     * - product_id: Product ID
     * - receiverCityId: Receiver BTS city ID
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
        $requiredFields = ['product_id', 'receiverCityId'];
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
        $product = Product::find()->with('stock')->where(['id' => $post['product_id'], 'status' => 1])->one();
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

        // Get sender city from product stock
        $senderCityId = $product->stock->bts_city_id;

        // Calculate total weight for the amount of products (in kg)
        $unitWeight = $product->weight && $product->weight > 0 ? $product->weight : 1.0;
        $totalWeight = $unitWeight * $amount;
        $totalWeight = max(1.0, $totalWeight); // Minimum 1kg

        // Calculate total volume from product dimensions (in cm³) × amount
        // Then calculate equivalent cubic dimensions
        $unitLength = $product->length ?: 10;
        $unitWidth = $product->width ?: 10;
        $unitHeight = $product->height ?: 10;
        
        $singleProductVolumeCm3 = $unitLength * $unitWidth * $unitHeight;
        $totalVolumeCm3 = $singleProductVolumeCm3 * $amount;
        
        // Smart stacking: keep base dimensions (length × width), stack by height
        // This avoids inflating dimensions with a cube approximation
        $volumeX = max(10, (int)$unitLength);
        $volumeY = max(10, (int)$unitWidth);
        $volumeZ = max(10, (int)($unitHeight * $amount)); // Stack products vertically

        // Validate city IDs exist
        $allCities = BTS::getCitiesDetailed();
        
        if (!isset($allCities[$senderCityId])) {
            Yii::$app->response->statusCode = 422;
            return ['errors' => ['product_id' => ['Неверный ID города склада продукта']]];
        }
        
        if (!isset($allCities[$post['receiverCityId']])) {
            Yii::$app->response->statusCode = 422;
            return ['errors' => ['receiverCityId' => ['Неверный ID города получателя']]];
        }

        // Get delivery type options from request or use defaults
        $pickupType = isset($post['pickup_type']) && in_array($post['pickup_type'], ['courier', 'branch', 'self']) 
            ? $post['pickup_type'] 
            : 'branch';
        $dropoffType = isset($post['dropoff_type']) && in_array($post['dropoff_type'], ['courier', 'branch', 'self']) 
            ? $post['dropoff_type'] 
            : 'courier';
        $isMultipleCost = isset($post['is_multiple_cost']) ? (int)$post['is_multiple_cost'] : 0;

        try {
            // Prepare data for BTS order-calculator API
            $calculationData = [
                'senderCityCode' => (string)$senderCityId,
                'receiverCityCode' => (string)$post['receiverCityId'],
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

            // Call BTS API for calculation
            $btsService = new BTS();
            $result = $btsService->calculateOrder($calculationData);
            
            if ($result && $result['success'] && isset($result['data'])) {
                // Extract price based on pickup_type and dropoff_type combination
                $priceKey = $pickupType . '_to_' . $dropoffType;
                $price = null;
                $allPrices = [];
                
                // Build all prices array and extract selected price
                $priceKeys = ['branch_to_branch', 'branch_to_courier', 'courier_to_branch', 'courier_to_courier'];
                foreach ($priceKeys as $key) {
                    if (isset($result['data'][$key])) {
                        $allPrices[$key] = $result['data'][$key];
                        if ($key === $priceKey && isset($result['data'][$key]['price'])) {
                            $price = $result['data'][$key]['price'];
                        }
                    }
                }
                
                // Fallback: try direct price field (for single cost response)
                if ($price === null && isset($result['data']['price'])) {
                    $price = $result['data']['price'];
                }
                
                // Fallback: get first available price
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
                        'requestData' => $calculationData,
                        'product' => [
                            'id' => $product->id,
                            'name' => $product->name_ru,
                            'amount' => $amount,
                            'single_product' => [
                                'weight' => $unitWeight,
                                'dimensions' => [
                                    'length' => $unitLength,
                                    'width' => $unitWidth,
                                    'height' => $unitHeight,
                                    'volume_cm3' => $singleProductVolumeCm3
                                ]
                            ],
                                'total_calculation' => [
                                    'weight' => $totalWeight,
                                    'volume_cm3' => $totalVolumeCm3,
                                    'packed_dimensions' => [
                                        'x' => $volumeX,
                                        'y' => $volumeY,
                                        'z' => $volumeZ
                                    ]
                                ],
                            'stock' => [
                                'id' => $product->stock->id,
                                'name' => $product->stock->name_ru,
                                'city_id' => $product->stock->bts_city_id
                            ]
                        ],
                        'senderCity' => $allCities[$senderCityId]['name'] ?? $senderCityId,
                        'receiverCity' => $allCities[$post['receiverCityId']]['name'] ?? $post['receiverCityId']
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
     * Get all regions
     * GET /api/bts/regions
     */
    public function actionRegions() {
        try {
            $language = Yii::$app->request->get('language', 'ru');
            
            if (!in_array($language, ['ru', 'uz', 'en'])) {
                $language = 'ru';
            }

            $regions = BTS::getRegions($language);
            
            $formattedRegions = [];
            foreach ($regions as $id => $name) {
                $formattedRegions[] = [
                    'id' => $id,
                    'name' => $name,
                    'name_ru' => BTS::getRegions('ru')[$id] ?? $name,
                    'name_uz' => BTS::getRegions('uz')[$id] ?? $name,
                    'name_en' => BTS::getRegions('en')[$id] ?? $name,
                ];
            }

            return ['data' => $formattedRegions];
            
        } catch (\Exception $e) {
            Yii::error('BTS regions error: ' . $e->getMessage(), __METHOD__);
            Yii::$app->response->statusCode = 500;
            return ['errors' => ['general' => ['Ошибка при получении списка регионов']]];
        }
    }

    /**
     * Get cities by region
     * GET /api/bts/cities?regionId=X
     */
    public function actionCities() {
        try {
            $regionId = Yii::$app->request->get('regionId');
            $language = Yii::$app->request->get('language', 'ru');
            
            if (!in_array($language, ['ru', 'uz', 'en'])) {
                $language = 'ru';
            }

            if ($regionId) {
                // Get cities for specific region
                if (!is_numeric($regionId)) {
                    Yii::$app->response->statusCode = 422;
                    return ['errors' => ['regionId' => ['ID региона должен быть числом']]];
                }

                $regions = BTS::getRegions();
                if (!isset($regions[$regionId])) {
                    Yii::$app->response->statusCode = 422;
                    return ['errors' => ['regionId' => ['Неверный ID региона']]];
                }

                $cities = BTS::getCities($regionId, $language);
                
                $formattedCities = [];
                foreach ($cities as $id => $city) {
                    $formattedCities[] = [
                        'id' => $id,
                        'name' => BTS::getCityName($id, $language),
                        'region_id' => $regionId
                    ];
                }

                return ['data' => $formattedCities];
                
            } else {
                // Get all cities
                $allCities = BTS::getCitiesDetailed();
                
                $formattedCities = [];
                foreach ($allCities as $id => $city) {
                    $formattedCities[] = [
                        'id' => $id,
                        'name' => BTS::getCityName($id, $language),
                        'region_id' => $city['region_id']
                    ];
                }

                return ['data' => $formattedCities];
            }
            
        } catch (\Exception $e) {
            Yii::error('BTS cities error: ' . $e->getMessage(), __METHOD__);
            Yii::$app->response->statusCode = 500;
            return ['errors' => ['general' => ['Ошибка при получении списка городов']]];
        }
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
