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

    public function behaviors()
    {
        $behaviors = parent::behaviors();
        $behaviors['authenticator'] = [
            'class' => HttpBearerAuth::className(),
            'optional' => ['index', 'check'], // Check is active for all, but really should be auth'd if user specific checks needed.
            // Wait, check uses Yii::$app->user->identity. So it must be authenticated or handle guest.
            // Let's keep check authenticated? No, index is public. My is auth. Check is auth.
            'optional' => ['index'], 
        ];
        
        // CORS filter
        $behaviors['corsFilter'] = [
            'class' => \yii\filters\Cors::className(),
            'cors' => [
                'Access-Control-Allow-Origin' => ['*'],
                'Access-Control-Request-Method' => ['GET', 'POST', 'OPTIONS'],
                'Access-Control-Request-Headers' => ['*'],
            ],
        ];

        return $behaviors;
    }

    /**
     * List active promocodes
     * GET /api/promocode
     */
    public function actionIndex()
    {
        $query = Promocode::find()
            ->where(['status' => Promocode::STATUS_ACTIVE])
            ->andWhere(['or', 
                ['end_date' => null], 
                ['>=', 'end_date', date('Y-m-d H:i:s')]
            ]);

        // If user is logged in, we might want to filter out ones they've already used max times
        // But for general listing, showing all active ones is usually fine.
        
        $promos = $query->all();
        
        $data = [];
        foreach ($promos as $promo) {
            $data[] = [
                'id' => $promo->id,
                'title' => $promo->getTitle(),
                'description' => $promo->getDescription(),
                'code' => $promo->code, // Maybe hide code if it's a "secret" one? Assuming public for now based on UI
                'end_date' => $promo->end_date ? date('d.m.Y, H:i', strtotime($promo->end_date)) : null,
                'min_order_amount' => $promo->min_order_amount,
                'value' => $promo->value,
                'type' => $promo->type == Promocode::TYPE_FIXED ? 'fixed' : 'percent',
            ];
        }

        return $this->sendSuccess($data);
    }

    /**
     * List my active promocodes (personal and universal)
     * GET /api/promocode/my
     */
    public function actionMy()
    {
        $user = Yii::$app->user->identity;
        $now = date('Y-m-d H:i:s');

        // Find active promocodes:
        // 1. Status is Active
        // 2. Not expired (end_date >= now OR null)
        // 3. Assigned to this user OR universal (user_id is NULL)
        $query = Promocode::find()
            ->where(['status' => Promocode::STATUS_ACTIVE])
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
            // Check usage limits manually since checkValidity requires order amount
            
            // Check global usage limit
            if ($promo->usage_limit !== null) {
                $usedCount = \app\models\order\Order::find()->where(['promocode_id' => $promo->id])->count();
                if ($usedCount >= $promo->usage_limit) {
                    continue; // Skip if global limit reached
                }
            }

            // Check usage limit per user
            if ($promo->usage_limit_per_user > 0) {
                $userUsedCount = \app\models\order\Order::find()
                    ->where(['promocode_id' => $promo->id, 'user_id' => $user->id])
                    ->count();
                
                if ($userUsedCount >= $promo->usage_limit_per_user) {
                    continue; // Skip if user limit reached
                }
            }

            // Check First Order Requirement (pre-check)
            if ($promo->is_first_order) {
                $hasOrders = \app\models\order\Order::find()
                    ->where(['user_id' => $user->id])
                    ->exists();
                if ($hasOrders) {
                    continue; // Skip if not first order
                }
            }

            $availablePromos[] = [
                'id' => $promo->id,
                'title' => $promo->getTitle(),
                'description' => $promo->getDescription(),
                'code' => $promo->code,
                'end_date' => $promo->end_date ? date('d.m.Y, H:i', strtotime($promo->end_date)) : null,
                'min_order_amount' => $promo->min_order_amount,
                'value' => $promo->value,
                'type' => $promo->type == Promocode::TYPE_FIXED ? 'fixed' : 'percent',
                'is_personal' => !empty($promo->user_id)
            ];
        }

        return $this->sendSuccess($availablePromos);
    }

    /**
     * Check/Apply a promocode
     * POST /api/promocode/check
     * Body: { "code": "1DAY221" }
     */
    public function actionCheck()
    {
        $user = Yii::$app->user->identity;
        $code = Yii::$app->request->post('code');

        if (!$code) {
            return $this->sendError(400, 'Code is required');
        }

        $promocode = Promocode::findOne(['code' => $code]);

        if (!$promocode) {
            return $this->sendError(404, 'Promocode not found');
        }

        // Calculate current cart total
        $cartItems = UserCart::find()->where(['user_id' => $user->id])->with('product')->all();
        
        if (empty($cartItems)) {
            return $this->sendError(400, 'Cart is empty');
        }

        $cartTotal = 0;
        foreach ($cartItems as $item) {
            if ($item->product) {
                // Use the price calculation logic from UserCart/Product
                $unitPrice = $item->product->getPriceByQuantity($item->amount);
                $cartTotal += $unitPrice * $item->amount;
            }
        }

        // Validate
        list($isValid, $error) = $promocode->checkValidity($user, $cartTotal, $cartItems);

        if (!$isValid) {
            return $this->sendError(422, $error);
        }

        // Calculate discount
        $discountAmount = $promocode->calculateDiscount($cartTotal);
        $finalPrice = max(0, $cartTotal - $discountAmount);

        return $this->sendSuccess([
            'promocode' => [
                'id' => $promocode->id,
                'code' => $promocode->code,
                'title' => $promocode->getTitle(),
                'description' => $promocode->getDescription(),
                'type' => $promocode->type,
                'value' => $promocode->value,
            ],
            'calculation' => [
                'original_total' => $cartTotal,
                'discount_amount' => $discountAmount,
                'final_total' => $finalPrice,
            ],
            'valid' => true,
            'message' => 'Promocode applied successfully'
        ]);
    }
}
