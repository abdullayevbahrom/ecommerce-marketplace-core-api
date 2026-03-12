<?php

namespace app\modules\api\controllers;

use Yii;
use yii\rest\Controller;
use yii\filters\auth\HttpBearerAuth;
use app\models\Promocode;
use app\models\user\cart\UserCart;
use app\modules\api\components\ApiResponseTrait;

class PromocodeController extends Controller
{
    use ApiResponseTrait;

    public function beforeAction($action) {
        $this->enableCsrfValidation = false;

        Yii::$app->response->getHeaders()->add('Access-Control-Allow-Origin', '*');
        Yii::$app->response->getHeaders()->add('Access-Control-Allow-Methods', 'GET, POST, PUT, PATCH, DELETE, HEAD, OPTIONS');
        Yii::$app->response->getHeaders()->add('Access-Control-Allow-Headers', 'Content-Type, X-Auth-Token, Origin, Authorization');

        if (Yii::$app->request->headers->has('OPTIONS')) {
            throw new \yii\web\HttpException(200, 'OK');
        }

        return parent::beforeAction($action);
    }

    public function behaviors()
    {
        $behaviors = parent::behaviors();

        $auth = [
            'class' => HttpBearerAuth::class,
        ];

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

        $auth['except'] = ['options'];
        $behaviors['authenticator'] = $auth;

        return $behaviors;
    }

    /**
     * List authenticated user's available promocodes (personal + universal)
     * GET /api/promocode/my
     */
    public function actionMy()
    {
        $user = Yii::$app->user->identity;
        $now = date('Y-m-d H:i:s');

        $query = Promocode::find()
            ->where(['status' => Promocode::STATUS_ACTIVE])
            ->andWhere(['or',
                ['start_date' => null],
                ['<=', 'start_date', $now]
            ])
            ->andWhere(['or',
                ['end_date' => null],
                ['>=', 'end_date', $now]
            ])
            ->andWhere(['or',
                ['user_id' => $user->id],
                ['user_id' => null]
            ]);

        $promos = $query->all();
        $availablePromos = [];

        foreach ($promos as $promo) {
            // Check global usage limit
            if ($promo->usage_limit !== null) {
                $usedCount = \app\models\order\Order::find()->where(['promocode_id' => $promo->id])->count();
                if ($usedCount >= $promo->usage_limit) {
                    continue;
                }
            }

            // Check usage limit per user
            if ($promo->usage_limit_per_user > 0) {
                $userUsedCount = \app\models\order\Order::find()
                    ->where(['promocode_id' => $promo->id, 'user_id' => $user->id])
                    ->count();

                if ($userUsedCount >= $promo->usage_limit_per_user) {
                    continue;
                }
            }

            // Check First Order Requirement
            if ($promo->is_first_order) {
                $hasOrders = \app\models\order\Order::find()
                    ->where(['user_id' => $user->id])
                    ->exists();
                if ($hasOrders) {
                    continue;
                }
            }

            $availablePromos[] = [
                'id' => $promo->id,
                'title' => $promo->getTitle(),
                'description' => $promo->getDescription(),
                'code' => $promo->code,
                'end_date' => $promo->end_date ? date('d.m.Y, H:i', strtotime($promo->end_date)) : null,
                'min_order_amount' => $promo->min_order_amount,
                'max_discount_amount' => $promo->max_discount_amount,
                'value' => $promo->value,
                'type' => $promo->type == Promocode::TYPE_FIXED ? 'fixed' : 'percent',
                'is_personal' => !empty($promo->user_id)
            ];
        }

        return $this->sendSuccess($availablePromos);
    }

    /**
     * Check/validate a promocode (lightweight, no cart required)
     * POST /api/promocode/check
     * Body: { "code": "PROMO123" }
     */
    public function actionCheck()
    {
        $user = Yii::$app->user->identity;
        $code = Yii::$app->request->post('code');

        if (!$code) {
            return $this->sendError(400, 'Promocode code is required');
        }

        $promocode = Promocode::findOne(['code' => $code]);

        if (!$promocode) {
            return $this->sendError(404, 'Promocode not found');
        }

        // Basic validity check (without cart-dependent checks)
        if ($promocode->status !== Promocode::STATUS_ACTIVE) {
            return $this->sendError(422, 'Promocode is inactive');
        }

        $now = date('Y-m-d H:i:s');
        if ($promocode->start_date && $promocode->start_date > $now) {
            return $this->sendError(422, 'Promocode is not active yet');
        }
        if ($promocode->end_date && $promocode->end_date < $now) {
            return $this->sendError(422, 'Promocode has expired');
        }

        // Check personal ownership
        if ($promocode->user_id !== null && $promocode->user_id != $user->id) {
            return $this->sendError(422, 'This promocode is not valid for your account');
        }

        // Check global usage limit
        if ($promocode->usage_limit !== null) {
            $usedCount = \app\models\order\Order::find()->where(['promocode_id' => $promocode->id])->count();
            if ($usedCount >= $promocode->usage_limit) {
                return $this->sendError(422, 'Promocode usage limit reached');
            }
        }

        // Check per-user usage limit
        $userUsedCount = 0;
        if ($promocode->usage_limit_per_user > 0) {
            $userUsedCount = \app\models\order\Order::find()
                ->where(['promocode_id' => $promocode->id, 'user_id' => $user->id])
                ->count();
            if ($userUsedCount >= $promocode->usage_limit_per_user) {
                return $this->sendError(422, 'You have already used this promocode');
            }
        }

        // Check first order requirement
        if ($promocode->is_first_order) {
            $hasOrders = \app\models\order\Order::find()
                ->where(['user_id' => $user->id])
                ->exists();
            if ($hasOrders) {
                return $this->sendError(422, 'This promocode is only for the first order');
            }
        }

        return $this->sendSuccess([
            'valid' => true,
            'promocode' => [
                'id' => $promocode->id,
                'code' => $promocode->code,
                'title' => $promocode->getTitle(),
                'description' => $promocode->getDescription(),
                'type' => $promocode->type == Promocode::TYPE_FIXED ? 'fixed' : 'percent',
                'value' => $promocode->value,
                'min_order_amount' => $promocode->min_order_amount,
                'max_discount_amount' => $promocode->max_discount_amount,
                'end_date' => $promocode->end_date ? date('d.m.Y, H:i', strtotime($promocode->end_date)) : null,
                'used_count' => (int)$userUsedCount,
                'usage_limit' => $promocode->usage_limit_per_user,
                'is_first_order' => (bool)$promocode->is_first_order,
                'category_id' => $promocode->category_id,
                'product_id' => $promocode->product_id,
            ],
        ]);
    }

    /**
     * Apply/validate a promocode against user's cart
     * POST /api/promocode/apply
     * Body: { "promocode": "PROMO123" }
     */
    public function actionApply()
    {
        $user = Yii::$app->user->identity;
        $code = Yii::$app->request->post('promocode');

        if (!$code) {
            return $this->sendError(400, 'Promocode is required');
        }

        $promocode = Promocode::findOne(['code' => $code]);

        if (!$promocode) {
            return $this->sendError(404, 'Promocode not found');
        }

        // Load cart with all relations needed for response
        $cartItems = UserCart::find()
            ->with([
                'product', 'product.image', 'product.color',
                'product.productProductTypes', 'product.productProductTypes.productType',
                'product.productProductTypes.productTypeValue',
                'cartFilter', 'cartFilter.productFilter'
            ])
            ->where(['user_id' => $user->id])
            ->all();

        if (empty($cartItems)) {
            return $this->sendError(400, 'Cart is empty');
        }

        // Calculate cart total and build grouped cart response
        $cartTotal = 0;
        $groups = [];

        foreach ($cartItems as $item) {
            if (!$item->product) continue;

            $unitPrice = $item->product->getPriceByQuantity($item->amount);
            $lineTotal = $unitPrice * $item->amount;
            $cartTotal += $lineTotal;

            $key = !empty($item->product->token_key) ? 'token_' . $item->product->token_key : 'prod_' . $item->product->id;

            if (!isset($groups[$key])) {
                $groups[$key] = [
                    'group_id' => $key,
                    'product_id' => $item->product->id,
                    'name_ru' => $item->product->name_ru,
                    'name_uz' => $item->product->name_uz,
                    'name_en' => $item->product->name_en,
                    'image' => $item->product->getPhoto(),
                    'total_amount' => 0,
                    'total_price' => 0,
                    'total_delivery_cost' => 0,
                    'total_with_delivery' => 0,
                    'items' => [],
                ];
            }

            $groups[$key]['total_amount'] += $item->amount;
            $groups[$key]['total_price'] += $lineTotal;
            $groups[$key]['total_delivery_cost'] += $item->delivery_cost;
            $groups[$key]['total_with_delivery'] += ($lineTotal + $item->delivery_cost);

            $variantData = [
                'id' => $item->id,
                'product_id' => $item->product->id,
                'name_ru' => $item->product->name_ru,
                'name_uz' => $item->product->name_uz,
                'name_en' => $item->product->name_en,
                'amount' => $item->amount,
                'price' => $lineTotal,
                'unit_price' => $unitPrice,
                'delivery_cost' => $item->delivery_cost,
                'stock_amount' => $item->product->amount,
                'color' => $item->product->color ? [
                    'id' => $item->product->color->id,
                    'name' => $item->product->color->name_ru,
                    'code' => $item->product->color->color
                ] : null,
                'product_types' => [],
                'filters' => $item->getProductFilter(),
            ];

            if ($item->product->productProductTypes) {
                foreach ($item->product->productProductTypes as $ppt) {
                    if ($ppt->productType && ($ppt->productTypeValue || $ppt->custom_value)) {
                        $variantData['product_types'][] = [
                            'type_id' => $ppt->productType->id,
                            'type_name' => $ppt->productType->name_ru,
                            'value_id' => $ppt->productTypeValue ? $ppt->productTypeValue->id : null,
                            'value' => $ppt->productTypeValue ? $ppt->productTypeValue->value_ru : $ppt->custom_value
                        ];
                    }
                }
            }

            $groups[$key]['items'][] = $variantData;
        }

        // Validate against model (includes all checks: status, dates, limits, ownership, first order, category/product)
        list($isValid, $error) = $promocode->checkValidity($user, $cartTotal, $cartItems);

        if (!$isValid) {
            return $this->sendError(422, $error);
        }

        // Calculate discount
        $discountAmount = $promocode->calculateDiscount($cartTotal);

        $userUsedCount = \app\models\order\Order::find()
            ->where(['promocode_id' => $promocode->id, 'user_id' => $user->id])
            ->count();

        return $this->sendSuccess([
            'valid' => true,
            'message' => 'Promocode applied successfully',
            'promocode' => [
                'code' => $promocode->code,
                'title' => $promocode->getTitle(),
                'description' => $promocode->getDescription(),
                'type' => $promocode->type == Promocode::TYPE_FIXED ? 'fixed' : 'percent',
                'value' => $promocode->value,
                'used_count' => (int)$userUsedCount,
                'usage_limit' => $promocode->usage_limit_per_user,
                'discount_amount' => $discountAmount,
            ],
            'cart_total' => $cartTotal,
            'total_after_discount' => $cartTotal - $discountAmount,
            'cart' => array_values($groups),
        ]);
    }
}
