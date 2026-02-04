<?php 
namespace app\widgets\admin_shop_advertising_menu;

use Yii;
use yii\bootstrap4\Widget;

use app\models\shop\advertising\ShopAdvertising;

class AdminShopAdvertisingMenu extends Widget{
	public function init() {}

	public function run() {
		$model = ShopAdvertising::find()->with('image')->where(['id'=>Yii::$app->request->get('id')])->one();

		return $this->render('admin_shop_advertising_menu', [
			'model' => $model,
			'user' => Yii::$app->user->identity
		]);
	}
}
?>