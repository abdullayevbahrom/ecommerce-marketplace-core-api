<?php

namespace app\modules\api\controllers;

use app\models\order\Order;
use app\models\product\Product;
use app\models\user\activity\UserActivity;
use app\models\user\cart\UserCart;
use app\models\user\favorite\UserFavorite;
use app\models\session\WebSession;
use app\models\user\User;
use Yii;
use yii\helpers\ArrayHelper;
use yii\filters\auth\HttpBearerAuth;
use yii\filters\Cors;
use yii\filters\VerbFilter;
use yii\rest\Controller;
use yii\web\HttpException;
use yii\web\Response;
use yii\web\UnauthorizedHttpException;

class SessionController extends Controller
{
    public function beforeAction($action)
    {
        $this->enableCsrfValidation = false;

        return parent::beforeAction($action);
    }

    public function behaviors()
    {
        $behaviors = parent::behaviors();

        unset($behaviors['authenticator']);

        $behaviors['corsFilter'] = [
            'class' => Cors::class,
            'cors' => [
                'Origin' => ['*'],
                'Access-Control-Request-Method' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'],
                'Access-Control-Request-Headers' => ['Authorization', 'Content-Type'],
                'Access-Control-Allow-Credentials' => false,
                'Access-Control-Max-Age' => 86400,
            ],
        ];

        $behaviors['authenticator'] = [
            'class' => HttpBearerAuth::class,
            'except' => ['options', 'create-operator', 'user-overview', 'user-overview-by-phone'],
        ];

        $behaviors['verbs'] = [
            'class' => VerbFilter::class,
            'actions' => [
                'warehouse' => ['POST'],
                'operator' => ['POST'],
                'create-operator' => ['POST'],
                'user-overview' => ['POST'],
                'user-overview-by-phone' => ['POST'],
                'options' => ['OPTIONS'],
            ],
        ];

        return $behaviors;
    }

    public function actions()
    {
        $actions = parent::actions();
        $actions['options'] = [
            'class' => \yii\rest\OptionsAction::class,
        ];

        return $actions;
    }

    public function actionSessions()
    {
        $user = Yii::$app->user->identity;

        $sessions = WebSession::find()
            ->where([
                'user_id' => $user->id,
                'is_revoked' => 0
            ])
            ->all();

        return [
            'success' => true,
            'sessions' => $sessions
        ];
    }

    public function actionWarehouse()
    {
        $user = Yii::$app->user->identity;

        if (!$user) {
            return ['success' => false, 'message' => 'Unauthorized'];
        }

        if (!\in_array($user->role, [User::ROLE_ADMIN, User::ROLE_MODERATOR, User::ROLE_SHOP])) {
            Yii::$app->response->statusCode = 200;

            return ['success' => false, 'message' => 'Forbidden'];
        }

        // Find shop_id for shop/merchant users
        $shopId = null;
        if ($user->role === User::ROLE_SHOP) {
            $shop = \app\models\shop\Shop::findOne(['user_id' => $user->id]);
            $shopId = $shop ? (int) $shop->id : null;
        }

        $payload = [
            'id' => (int) $user->id,
            'yii_id' => (int) $user->id,
            'phone' => (string) $user->phone,
            'name' => trim($user->name . ' ' . $user->lastname . ' ' . $user->middlename),
            'role' => (int) $user->role,
            'is_active' => $user->status === User::STATUS_ACTIVE,
            'shop_id' => $shopId,
        ];

        $token = md5($user->id . Yii::$app->params['apiSecretKey']);
        $warehouseApiUrl = rtrim(Yii::$app->params['warehouseApiUrl'], '/');

        try {
            /** @var \GuzzleHttp\Client $client */
            $client = Yii::$app->httpClient;
            $response = $client->post(
                $warehouseApiUrl . '/api/shopLogin',
                [
                    'json' => $payload,
                    'headers' => [
                        'X-Api-Token' => $token,
                        'Content-Type' => 'application/json',
                    ],
                ]
            );

            $status = $response->getStatusCode();
            $body = (string) $response->getBody();
            $data = json_decode($body, true);

            if ($status >= 400) {
                Yii::warning("Warehouse sync failed: HTTP {$status} Body: {$body}", __METHOD__);
            }

            return [
                'success' => $status < 400,
                'status' => $status,
                'data' => $data ?? $body,
            ];
        } catch (\Throwable $e) {
            Yii::error("Warehouse sync fatal: {$e->getMessage()}", __METHOD__);
            Yii::$app->response->statusCode = 500;

            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function actionOperator()
    {
        $user = Yii::$app->user->identity;

        if (!$user) {
            return ['success' => false, 'message' => 'Unauthorized'];
        }

        if (!\in_array($user->role, [User::ROLE_ADMIN, User::ROLE_MODERATOR, User::ROLE_USER, User::ROLE_OPERATOR])) {
            Yii::$app->response->statusCode = 200;

            return ['success' => false, 'message' => 'Forbidden'];
        }

        $payload = [
            'id' => (int) $user->id,
            'yii_id' => (int) $user->id,
            'phone' => (string) $user->phone,
            'name' => trim($user->name . ' ' . $user->lastname . ' ' . $user->middlename),
            'role' => (int) $user->role,
            'email' => (string) $user->email,
            'is_active' => $user->status === User::STATUS_ACTIVE,
        ];

        $operatorApiUrl = rtrim(Yii::$app->params['operatorApiUrl'], '/');
        $token = md5($user->id . Yii::$app->params['apiSecretKey']);

        try {
            /** @var \GuzzleHttp\Client $client */
            $client = Yii::$app->httpClient;
            $response = $client->post(
                $operatorApiUrl . '/api/auth/shopLogin',
                [
                    'json' => $payload,
                    'headers' => [
                        'X-Api-Token' => $token,
                        'Content-Type' => 'application/json',
                    ],
                ]
            );

            $status = $response->getStatusCode();
            $body = (string) $response->getBody();
            $res = json_decode($body, true);

            if ($status >= 400) {
                Yii::warning("Operator sync failed: HTTP {$status} Body: {$body}", __METHOD__);
            }

            return [
                'success' => $status < 400,
                'status' => $status,
                'data' => $res ?? $body,
            ];
        } catch (\Throwable $e) {
            Yii::error("Operator sync fatal: {$e->getMessage()}", __METHOD__);
            Yii::$app->response->statusCode = 500;

            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function actionCreateOperator()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $payload = Yii::$app->request->post();
        $phone = (string) ArrayHelper::getValue($payload, 'phone', '');
        $secret = Yii::$app->params['apiSecretKey'] ?? null;

        if (!$secret) {
            throw new HttpException(500, 'Missing apiSecretKey');
        }

        $expectedToken = md5($phone . $secret);
        $providedToken = Yii::$app->request->headers->get('X-Api-Token');

        if (!$providedToken || $providedToken !== $expectedToken) {
            throw new UnauthorizedHttpException('Invalid X-Api-Token');
        }

        $email = trim((string) ArrayHelper::getValue($payload, 'email', ''));
        $name = trim((string) ArrayHelper::getValue($payload, 'name', ''));
        $password = (string) ArrayHelper::getValue($payload, 'password', '');
        $isActive = (bool) ArrayHelper::getValue($payload, 'is_active', true);

        if ($phone === '' || $name === '' || $password === '') {
            Yii::$app->response->statusCode = 422;

            return [
                'success' => false,
                'message' => 'name, phone and password are required',
            ];
        }

        $user = User::find()
            ->where(['role' => User::ROLE_OPERATOR])
            ->andWhere(['or', ['phone' => $phone], ['email' => $email]])
            ->one();

        if (!$user) {
            $conflict = User::find()
                ->andWhere(['or', ['phone' => $phone], ['email' => $email]])
                ->one();

            if ($conflict) {
                Yii::$app->response->statusCode = 409;

                return [
                    'success' => false,
                    'message' => 'Phone or email already used by another user',
                ];
            }

            $user = new User();
            $user->scenario = User::SIGNUP_ADMIN_USER;
        } else {
            $user->scenario = User::UPDATE_ADMIN_USER;
        }

        $nameParts = preg_split('/\s+/', $name, 3, PREG_SPLIT_NO_EMPTY) ?: [];
        $user->name = $nameParts[0] ?? $name;
        $user->lastname = $nameParts[1] ?? null;
        $user->middlename = $nameParts[2] ?? null;
        $user->phone = $phone;
        $user->email = $email;
        $user->role = User::ROLE_OPERATOR;
        $user->status = $isActive ? User::STATUS_ACTIVE : User::STATUS_BLOCKED;
        $user->password = $user->generatePassword($password);

        if (!$user->validate()) {
            Yii::$app->response->statusCode = 422;

            return [
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $user->errors,
            ];
        }

        $savedUser = $user->saveObject(User::ROLE_OPERATOR);

        if (!$savedUser) {
            Yii::$app->response->statusCode = 500;

            return [
                'success' => false,
                'message' => 'Failed to create operator user',
            ];
        }

        return [
            'success' => true,
            'data' => [
                'yii_user_id' => (int) $savedUser->id,
                'phone' => $savedUser->phone,
                'email' => $savedUser->email,
                'role' => (int) $savedUser->role,
                'status' => (int) $savedUser->status,
            ],
        ];
    }

    public function actionUserOverview()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $payload = Yii::$app->request->post();
        $userId = ArrayHelper::getValue($payload, 'user_id', 0);
        $secret = Yii::$app->params['apiSecretKey'] ?? null;

        if (!$secret) {
            throw new HttpException(500, 'Missing apiSecretKey');
        }

        if ($userId <= 0) {
            Yii::$app->response->statusCode = 422;

            return [
                'success' => false,
                'message' => 'user_id is required',
            ];
        }

        $expectedToken = md5($userId . $secret);
        $providedToken = Yii::$app->request->headers->get('X-Api-Token');

        if (!$providedToken || $providedToken !== $expectedToken) {
            throw new UnauthorizedHttpException('Invalid X-Api-Token');
        }

        $user = is_numeric($userId) ? User::findOne((int) $userId) : null;

        if (!$user && User::hasColumn('global_user_id')) {
            $user = User::findOne(['global_user_id' => (string) $userId]);
        }

        if (!$user) {
            Yii::$app->response->statusCode = 404;

            return [
                'success' => false,
                'message' => 'User not found',
            ];
        }

        return [
            'success' => true,
            'data' => [
                'user_id' => (int) $user->id,
                'global_user_id' => $user->hasAttribute('global_user_id') ? (string) ($user->global_user_id ?? '') : null,
                'last_orders' => $this->buildLastOrders($user),
                'favorite_products' => $this->buildFavoriteProducts($user),
                'recently_viewed_products' => $this->buildRecentlyViewedProducts($user),
                'cart' => $this->buildCartData($user),
            ],
        ];
    }

    public function actionUserOverviewByPhone()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $providedToken = (string) Yii::$app->request->headers->get('X-Internal-Token', '');
        $expectedToken = (string) (Yii::$app->params['auth']['gateway']['internalToken'] ?? '');
        if ($providedToken === '' || $expectedToken === '' || !hash_equals($expectedToken, $providedToken)) {
            throw new UnauthorizedHttpException('Invalid internal token');
        }

        $payload = Yii::$app->request->post();
        $phone = preg_replace('/[^\d]/', '', (string) ArrayHelper::getValue($payload, 'phone', ''));
        if ($phone === '') {
            Yii::$app->response->statusCode = 422;
            return [
                'success' => false,
                'message' => 'phone is required',
            ];
        }

        $user = User::find()
            ->where(new \yii\db\Expression("REPLACE(phone, '+', '') = :phone", [':phone' => $phone]))
            ->orderBy([
                'shop_id' => SORT_DESC,
                'role' => SORT_DESC,
                'id' => SORT_ASC,
            ])
            ->one();

        if (!$user) {
            Yii::$app->response->statusCode = 404;
            return [
                'success' => false,
                'message' => 'User not found',
            ];
        }

        return [
            'success' => true,
            'data' => [
                'user_id' => (int) $user->id,
                'phone' => (string) $user->phone,
                'name' => trim((string) ($user->name ?? '')),
                'role' => (int) $user->role,
                'status' => (int) $user->status,
                'global_user_id' => $user->hasAttribute('global_user_id') ? (string) ($user->global_user_id ?? '') : null,
            ],
        ];
    }

    private function buildLastOrders(User $user, int $limit = 10): array
    {
        $orders = Order::find()
            ->with(['orderProducts.product.image'])
            ->where(['user_id' => $user->id])
            ->orderBy(['id' => SORT_DESC])
            ->limit($limit)
            ->all();

        return array_map(function (Order $order) {
            return [
                'id' => (int) $order->id,
                'price' => (float) $order->price,
                'delivery_cost' => (float) $order->delivery_cost,
                'amount' => (float) $order->amount,
                'status' => (int) $order->status,
                'status_payment' => (int) $order->status_payment,
                'status_delivery' => (int) $order->status_delivery,
                'date' => $order->date,
                'products' => array_map(function ($orderProduct) {
                    return $this->serializeProductSummary($orderProduct->product, [
                        'amount' => $orderProduct->amount !== null ? (float) $orderProduct->amount : null,
                        'price' => $orderProduct->product_price !== null ? (float) $orderProduct->product_price : null,
                    ]);
                }, $order->orderProducts ?: []),
            ];
        }, $orders);
    }

    private function buildFavoriteProducts(User $user, int $limit = 20): array
    {
        $favorites = UserFavorite::find()
            ->with(['product.image'])
            ->where(['user_id' => $user->id])
            ->orderBy(['id' => SORT_DESC])
            ->limit($limit)
            ->all();

        return array_values(array_filter(array_map(function (UserFavorite $favorite) {
            return $this->serializeProductSummary($favorite->product, [
                'favorite_id' => (int) $favorite->id,
                'date' => $favorite->date,
            ]);
        }, $favorites)));
    }

    private function buildRecentlyViewedProducts(User $user, int $limit = 20): array
    {
        $activities = UserActivity::find()
            ->where([
                'user_id' => $user->id,
                'activity_type' => UserActivity::TYPE_VIEW,
            ])
            ->andWhere(['not', ['product_id' => null]])
            ->orderBy(['created_at' => SORT_DESC, 'id' => SORT_DESC])
            ->limit(100)
            ->all();

        $items = [];
        $productIds = [];

        foreach ($activities as $activity) {
            $productId = (int) $activity->product_id;
            if ($productId <= 0 || isset($productIds[$productId])) {
                continue;
            }

            $productIds[$productId] = $activity->created_at;

            if (count($productIds) >= $limit) {
                break;
            }
        }

        if (empty($productIds)) {
            return [];
        }

        $products = Product::find()
            ->with('image')
            ->where(['id' => array_keys($productIds)])
            ->indexBy('id')
            ->all();

        foreach ($productIds as $productId => $viewedAt) {
            $summary = $this->serializeProductSummary($products[$productId] ?? null, [
                'viewed_at' => $viewedAt,
            ]);

            if ($summary !== null) {
                $items[] = $summary;
            }
        }

        return $items;
    }

    private function buildCartData(User $user): array
    {
        $cartItems = UserCart::find()
            ->with(['product.image', 'cartFilter.productFilter'])
            ->where(['user_id' => $user->id])
            ->orderBy(['id' => SORT_DESC])
            ->all();

        $items = [];
        $totalAmount = 0.0;
        $totalPrice = 0.0;
        $totalDeliveryCost = 0.0;

        foreach ($cartItems as $cartItem) {
            $productSummary = $this->serializeProductSummary($cartItem->product, [
                'cart_id' => (int) $cartItem->id,
                'amount' => $cartItem->amount !== null ? (float) $cartItem->amount : null,
                'price' => $cartItem->price !== null ? (float) $cartItem->price : null,
                'delivery_cost' => $cartItem->delivery_cost !== null ? (float) $cartItem->delivery_cost : 0.0,
                'date' => $cartItem->date,
            ]);

            if ($productSummary === null) {
                continue;
            }

            $items[] = $productSummary;
            $totalAmount += (float) ($cartItem->amount ?? 0);
            $totalPrice += (float) ($cartItem->price ?? 0);
            $totalDeliveryCost += (float) ($cartItem->delivery_cost ?? 0);
        }

        return [
            'count' => count($items),
            'total_amount' => $totalAmount,
            'total_price' => $totalPrice,
            'total_delivery_cost' => $totalDeliveryCost,
            'items' => $items,
        ];
    }

    private function serializeProductSummary(?Product $product, array $extra = []): ?array
    {
        if ($product === null) {
            return null;
        }

        return array_merge([
            'id' => (int) $product->id,
            'name' => (string) ($product->name_uz ?: $product->name_ru ?: $product->name_en),
            'price' => $product->price !== null ? (float) $product->price : null,
            'image' => $product->getPhoto('original'),
        ], $extra);
    }


    public function actionProfile()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $token = Yii::$app->request->headers->get('Web-Session');

        if (!$token) {
            return ['success' => false, 'message' => 'Session token missing'];
        }

        $session = WebSession::find()
            ->where([
                'access_token' => $token,
                'is_revoked' => 0
            ])
            ->andWhere(['>', 'expires_at', time()])
            ->one();

        if (!$session) {
            return ['success' => false, 'message' => 'Invalid session'];
        }

        $user = User::findOne($session->user_id);

        return [
            'success' => true,
            'data' => $user
        ];
    }

    public function actionRefresh()
    {
        $refreshToken = Yii::$app->request->post('refresh_token');

        $session = WebSession::find()
            ->where(['refresh_token' => $refreshToken])
            ->andWhere(['is_revoked' => 0])
            ->one();

        if (!$session) {
            throw new HttpException(401, 'Invalid refresh token');
        }

        $session->access_token = Yii::$app->security->generateRandomString(64);
        $session->expires_at = time() + (60 * 60 * 24 * 7);
        $session->updated_at = time();
        $session->save(false);

        return [
            'access_token' => $session->access_token
        ];
    }

    public function actionLogout()
    {
        $token = Yii::$app->request->headers->get('Web-Session');

        WebSession::updateAll(
            ['is_revoked' => 1],
            ['access_token' => $token]
        );

        return ['success' => true];
    }

    public function actionLogoutAll()
    {
        $currentToken = Yii::$app->request->headers->get('Authorization');
        $currentToken = str_replace('Bearer ', '', $currentToken);

        $user = Yii::$app->user->identity;

        WebSession::updateAll(
            ['is_revoked' => 1],
            ['user_id' => $user->id]
        );

        return ['success' => true];
    }
}
