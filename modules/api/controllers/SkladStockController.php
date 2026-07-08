<?php

namespace app\modules\api\controllers;

use Yii;
use yii\rest\Controller;
use yii\web\BadRequestHttpException;
use yii\web\UnauthorizedHttpException;
use app\models\product\Product;

class SkladStockController extends Controller
{
    public $enableCsrfValidation = false;

    /**
     * POST /api/sklad/stock/update
     * Body: { "id": <yii_product_id>, "amount": <float> }
     * Header: X-Api-Token: md5(yii_product_id + apiSecretKey)
     */
    public function actionUpdate()
    {
        $request = Yii::$app->request;
        $token = $request->headers->get('X-Api-Token');
        $productId = (int) $request->post('id');
        $amount = $request->post('amount');

        if (!$token || !$productId || $amount === null) {
            throw new BadRequestHttpException('Missing required fields: id, amount, X-Api-Token');
        }

        $expectedToken = md5($productId . Yii::$app->params['apiSecretKey']);
        if ($token !== $expectedToken) {
            throw new UnauthorizedHttpException('Invalid X-Api-Token');
        }

        $product = Product::findOne($productId);
        if (!$product) {
            return ['success' => false, 'message' => 'Product not found'];
        }

        $product->amount = max(0, (float) $amount);
        $product->qty = (string) $product->amount;
        $product->save(false, ['amount', 'qty']);

        return ['success' => true, 'id' => $productId, 'amount' => $product->amount];
    }
}
