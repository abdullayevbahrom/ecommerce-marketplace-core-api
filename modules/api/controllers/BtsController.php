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
     * Calculate shipping price using BTS API
     * POST /api/bts/calculate
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

        // Calculate total weight for the amount of products
        $totalWeight = $product->weight && $product->weight > 0 
            ? $product->weight * $amount 
            : 2 * $amount; // Default 2kg per product if weight not specified

        // Calculate total volume from product dimensions (length × height × width) × amount
        $volume = null;
        $singleProductVolume = null;
        if ($product->length && $product->height && $product->width) {
            // Calculate single product volume (convert cm to meters)
            $singleProductVolume = ($product->length / 100) * ($product->height / 100) * ($product->width / 100);
            // Multiply by amount to get total volume
            $volume = $singleProductVolume * $amount;
        }

        // Set default date if not provided
        $senderDate = isset($post['senderDate']) ? $post['senderDate'] : null;
        
        // Validate and set sender date
        if (!$senderDate) {
            // Default to tomorrow
            $senderDate = date('Y-m-d', strtotime('+1 day'));
        } else {
            // Validate date format
            $dateObj = \DateTime::createFromFormat('Y-m-d', $senderDate);
            if (!$dateObj || $dateObj->format('Y-m-d') !== $senderDate) {
                Yii::$app->response->statusCode = 422;
                return ['errors' => ['senderDate' => ['Неверный формат даты. Используйте Y-m-d']]];
            }

            // Check if date is not too close (at least 1 day in future)
            $minDate = new \DateTime('+1 day');
            $providedDate = new \DateTime($senderDate);
            
            if ($providedDate < $minDate) {
                Yii::$app->response->statusCode = 422;
                return ['errors' => ['senderDate' => ['Дата отправки должна быть минимум завтра: ' . $minDate->format('Y-m-d')]]];
            }

            // Check if date is not too far (max 30 days in future)
            $maxDate = new \DateTime('+30 days');
            if ($providedDate > $maxDate) {
                Yii::$app->response->statusCode = 422;
                return ['errors' => ['senderDate' => ['Дата отправки не может быть позже чем: ' . $maxDate->format('Y-m-d')]]];
            }
        }

        // Validate city IDs exist
        $regions = BTS::getRegions();
        $allCities = BTS::getCitiesDetailed();
        
        if (!isset($allCities[$senderCityId])) {
            Yii::$app->response->statusCode = 422;
            return ['errors' => ['product_id' => ['Неверный ID города склада продукта']]];
        }
        
        if (!isset($allCities[$post['receiverCityId']])) {
            Yii::$app->response->statusCode = 422;
            return ['errors' => ['receiverCityId' => ['Неверный ID города получателя']]];
        }

        try {
            // Prepare data for BTS API
            $calculationData = [
                'senderCityId' => (int)$senderCityId,
                'receiverCityId' => (int)$post['receiverCityId'],
                'weight' => (float)$totalWeight,
                'volume' => $volume ? (int)$volume : null,
                'senderDate' => $senderDate,
                'senderDelivery' => 1,
                'receiverDelivery' => 1
            ];

            error_log('Calculation data123: ' . json_encode($calculationData));
            // Call BTS API for calculation
            $btsService = new BTS();
            $result = $btsService->calculateDelivery($calculationData);
            error_log('Result2223: ' . json_encode($result));
            if ($result && isset($result['data']['summaryPrice'])) {
                return [
                    'data' => [
                        'summaryPrice' => $result['data']['summaryPrice'],
                        'currency' => 'UZS',
                        'requestData' => $calculationData,
                        'product' => [
                            'id' => $product->id,
                            'name' => $product->name_ru,
                            'amount' => $amount,
                            'single_product' => [
                                'weight' => $product->weight ?: 2, // Show actual or default weight
                                'dimensions' => [
                                    'length' => $product->length,
                                    'height' => $product->height,
                                    'width' => $product->width,
                                    'volume' => $singleProductVolume
                                ]
                            ],
                            'total_calculation' => [
                                'weight' => $totalWeight,
                                'volume' => $volume
                            ],
                            'stock' => [
                                'id' => $product->stock->id,
                                'name' => $product->stock->name_ru,
                                'city_id' => $product->stock->bts_city_id
                            ]
                        ],
                        'senderCity' => $allCities[$senderCityId]['name'],
                        'receiverCity' => $allCities[$post['receiverCityId']]['name']
                    ]
                ];
            } else {
                Yii::$app->response->statusCode = 500;
                return ['errors' => ['general' => ['Ошибка при расчете стоимости доставки']]];
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
