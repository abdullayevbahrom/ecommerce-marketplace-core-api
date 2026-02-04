<?php 
namespace app\widgets\admin_shop_menu;

use Yii;
use yii\bootstrap4\Widget;

use app\models\shop\Shop;
use app\models\user\User;

class AdminShopMenu extends Widget{
	public function init() {}

	public function run() {
		$user = Yii::$app->user->identity;

		if ($user->role == User::ROLE_SHOP) {
			$model = Shop::find()->with('image', 'products', 'shopAdvertisings')->where(['user_id'=>$user->id])->one();
		}

		if (($user->role == User::ROLE_ADMIN) || ($user->role == User::ROLE_MODERATOR) ||  ($user->role == User::ROLE_LOGIST)) {
			$model = Shop::find()->with('image', 'products', 'shopAdvertisings')->where(['id'=>Yii::$app->request->get('id')])->one();
		}

		return $this->render('admin_shop_menu', [
			'model' => $model,
			'user' => $user
		]);
	}
}
?>