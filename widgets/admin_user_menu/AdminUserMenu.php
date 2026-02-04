<?php 
namespace app\widgets\admin_user_menu;

use Yii;
use yii\bootstrap4\Widget;

use app\models\user\User;
use app\models\order\Order;
use app\models\transaction\Transaction;
use app\models\user\cart\UserCart;
use app\models\user\favorite\UserFavorite;
use app\models\user\compare\UserCompare;
use app\models\user\card\UserCard;
use app\models\product\ProductRequest;

class AdminUserMenu extends Widget{
	public function init() {}

	public function run() {
		$user = Yii::$app->user->identity;

		$model = User::find()->with('image')->where(['id'=>Yii::$app->request->get('id')])->one();
		$orders_count = Order::find()->where(['user_id'=>Yii::$app->request->get('id'), 'status'=>0])->count();

        // Additional stats for the enhanced view
        $orderCount = Order::find()->where(['user_id' => $model->id])->count();
        $totalSpent = Order::find()->where(['user_id' => $model->id, 'status' => 1])->sum('price') ?: 0;
        $transactionCount = Transaction::find()->where(['user_id' => $model->id])->count();
        $cartItemCount = UserCart::find()->where(['user_id' => $model->id])->count();
        $favoriteCount = UserFavorite::find()->where(['user_id' => $model->id])->count();
        $compareCount = UserCompare::find()->where(['user_id' => $model->id])->count();
        $cardCount = UserCard::find()->where(['user_id' => $model->id])->count();

		return $this->render('admin-user-menu', [
			'model' => $model,
			'user' => $user,
			'orders_count' => $orders_count,
            'orderCount' => $orderCount,
            'totalSpent' => $totalSpent,
            'transactionCount' => $transactionCount,
            'cartItemCount' => $cartItemCount,
            'favoriteCount' => $favoriteCount,
            'compareCount' => $compareCount,
            'cardCount' => $cardCount
		]);
	}
}
?>