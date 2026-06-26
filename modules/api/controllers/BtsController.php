<?php
namespace app\modules\api\controllers;

use app\components\Bts\BtsComponent;
use Yii;
use yii\web\HttpException;
use yii\rest\Controller;
use yii\filters\auth\HttpBearerAuth;
use app\models\product\Product;

class BtsController extends Controller
{
    public function beforeAction($action)
    {
        $this->enableCsrfValidation = false;

        Yii::$app->response->getHeaders()->add('Access-Control-Allow-Origin', '*');
        Yii::$app->response->getHeaders()->add('Access-Control-Allow-Methods', 'GET, POST, PUT, PATCH, DELETE, HEAD, OPTIONS');
        Yii::$app->response->getHeaders()->add('Access-Control-Allow-Headers', 'Content-Type, X-Auth-Token, Origin, Authorization');

        if (Yii::$app->request->headers->has('OPTIONS')) {
            throw new HttpException(200, 'OK');
        }

        $langs = ['ru', 'en', 'uz'];
        $headers = Yii::$app->request->headers;
        if ($headers->has('Content-Language')) {
            $lang = $headers->get('Content-Language');
            if (\in_array($lang, $langs)) {
                Yii::$app->session->set('language', $lang);
            }
        } else {
            Yii::$app->session->set('language', 'ru');
        }

        return parent::beforeAction($action);
    }

    public function behaviors()
    {
        $behaviors = parent::behaviors();
        $behaviors['authenticator'] = [
            'class' => HttpBearerAuth::class,
            'optional' => ['*'],
        ];

        $auth = $behaviors['authenticator'];
        unset($behaviors['authenticator']);

        $behaviors['corsFilter'] = [
            'class' => \yii\filters\Cors::class,
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
    public function actionCalculate()
    {
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
            $amount = (int) $post['amount'];
        }

        // Validate product exists and has stock
        $product = Product::find()
            ->with('stock')
            ->where(['product.id' => $post['product_id'], 'product.status' => 1])
            ->publicVisible()
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
        $senderCityCode = (string) $product->stock->bts_city_id;

        // Product weight is stored in grams in admin/shop forms.
        $unitWeight = $this->normalizeProductWeightToKg($product->weight ?? null);
        $totalWeight = $unitWeight * $amount;
        $totalWeight = max(1.0, $totalWeight); // Minimum 1kg

        // Calculate total volume from product dimensions (in cm)
        $unitLength = $product->length ?: 10;
        $unitWidth = $product->width ?: 10;
        $unitHeight = $product->height ?: 10;

        // Smart stacking: keep base dimensions (length x width), stack by height
        $volumeX = max(10, (int) $unitLength);
        $volumeY = max(10, (int) $unitWidth);
        $volumeZ = max(10, (int) ($unitHeight * $amount));

        // Get delivery type options from request or use defaults
        $pickupType = isset($post['pickup_type']) && in_array($post['pickup_type'], ['courier', 'branch', 'self'])
            ? $post['pickup_type']
            : 'branch';
        $dropoffType = isset($post['dropoff_type']) && in_array($post['dropoff_type'], ['courier', 'branch', 'self'])
            ? $post['dropoff_type']
            : 'courier';

        try {
            $calculationData = [
                'senderCityCode' => $senderCityCode,
                'receiverCityCode' => (string) $post['receiverCityCode'],
                'pickup_type' => $pickupType,
                'dropoff_type' => $dropoffType,
                'weight' => (float) $totalWeight,
                'volume' => [
                    'x' => $volumeX,
                    'y' => $volumeY,
                    'z' => $volumeZ
                ]
            ];
            /** @var BtsComponent $bts */
            $bts = Yii::$app->bts;
            $price = $bts->calculateOrder($calculationData);
            $priceKey = 'courier_to_courier';

            return [
                'data' => [
                    'price' => $price,
                    'price_key' => $priceKey,
                    'all_prices' => $price,
                    'currency' => 'UZS',
                    'bts_response' => $price,
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

        } catch (\Exception $e) {
            Yii::error('BTS calculation error: ' . $e->getMessage(), __METHOD__);
            Yii::$app->response->statusCode = 500;
            return ['errors' => ['general' => ['Ошибка сервиса расчета доставки']]];
        }
    }

    /**
     * GET /api/bts/regions
     */
    public function actionRegions()
    {
        return ['data' => Yii::$app->bts->fetchRegions()];
    }

    public function actionSearchCities()
    {
        try {
            $searchTerm = Yii::$app->request->get('q');
            $language = Yii::$app->request->get('lang', 'ru');
            $regionId = Yii::$app->request->get('region_code');

            if (empty($searchTerm)) {
                return ['success' => false, 'data' => null, 'error' => 'Search term is required'];
            }

            if (!\in_array($language, ['ru', 'uz', 'en'])) {
                $language = 'ru';
            }

            if ($regionId && !is_numeric($regionId)) {
                return ['success' => false, 'data' => null, 'error' => 'Invalid region ID format'];
            }

            $cities = Yii::$app->bts->searchCities($regionId, $searchTerm, $language);
            return ['success' => true, 'data' => $cities, 'message' => 'Cities search completed successfully'];
        } catch (\Throwable $e) {
            return ['success' => false, 'data' => null, 'error' => $e->getMessage()];
        }
    }

    public function actionAddressInfo()
    {
        try {
            $cityId = Yii::$app->request->get('city_code');
            $language = Yii::$app->request->get('lang', 'ru');

            if (empty($cityId) || !is_numeric($cityId)) {
                return ['success' => false, 'data' => null, 'error' => 'Valid city ID is required'];
            }

            if (!\in_array($language, ['ru', 'uz', 'en'])) {
                $language = 'ru';
            }

            $addressInfo = Yii::$app->bts::getAddressInfo($cityId, $language);

            if (!$addressInfo) {
                return ['success' => false, 'data' => null, 'error' => 'City not found'];
            }

            return ['success' => true, 'data' => $addressInfo, 'message' => 'Address information retrieved successfully'];
        } catch (\Throwable $e) {
            return ['success' => false, 'data' => null, 'error' => $e->getMessage()];
        }
    }

    public function actionGetPackageTypes()
    {
        try {
            return ['success' => true, 'data' => Yii::$app->bts->getPackageTypes(), 'message' => 'Package types retrieved successfully'];
        } catch (\Throwable $e) {
            return ['success' => false, 'data' => null, 'error' => $e->getMessage()];
        }
    }

    public function actionGetPostTypes()
    {
        try {
            return ['success' => true, 'data' => Yii::$app->bts->getPostTypes(), 'message' => 'Post types retrieved successfully'];
        } catch (\Throwable $e) {
            return ['success' => false, 'data' => null, 'error' => $e->getMessage()];
        }
    }

    public function actionStatuses()
    {
        try {
            return ['success' => true, 'data' => Yii::$app->bts->getStatuses(), 'message' => 'Order statuses retrieved successfully'];
        } catch (\Throwable $e) {
            return ['success' => false, 'data' => null, 'error' => $e->getMessage()];
        }
    }

    /**
     * Get delivery types
     * GET /api/bts/delivery-types
     */
    public function actionDeliveryTypes()
    {
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

    private function normalizeProductWeightToKg(?float $rawWeight): float
    {
        $weightInGrams = (float) $rawWeight;
        if ($weightInGrams <= 0) {
            return 1.0;
        }

        return $weightInGrams / 1000;
    }
}
