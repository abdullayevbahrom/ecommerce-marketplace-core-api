<?php 
namespace app\widgets\admin_shop_oferta_menu;

use Yii;
use yii\bootstrap4\Widget;

use app\models\shop\oferta\ShopOferta;

class AdminShopOfertaMenu extends Widget{
	public function init() {}

	public function run() {
		$model = ShopOferta::find()->with('file')->where(['id'=>Yii::$app->request->get('id')])->one();

		return $this->render('admin_shop_oferta_menu', [
			'model' => $model,
			'user' => Yii::$app->user->identity
		]);
	}
}
?>