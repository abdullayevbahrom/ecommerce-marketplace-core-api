<?php 
namespace app\widgets\admin_product_menu;

use Yii;
use yii\bootstrap4\Widget;

use app\models\product\Product;
use app\models\user\User;

class AdminProductMenu extends Widget{
	public function init() {}

	public function run() {
		$model = Product::find()->with('image')->where(['id'=>Yii::$app->request->get('id')])->one();

		return $this->render('admin_product_menu', [
			'model' => $model,
			'user' => Yii::$app->user->identity
		]);
	}
}
?>