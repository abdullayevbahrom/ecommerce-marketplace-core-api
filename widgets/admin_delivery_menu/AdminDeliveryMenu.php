<?php 
namespace app\widgets\admin_delivery_menu;

use Yii;
use yii\bootstrap4\Widget;

use app\models\delivery\Delivery;

class AdminDeliveryMenu extends Widget{
	public function init() {}

	public function run() {
		$model = Delivery::find()->with('image')->where(['id'=>Yii::$app->request->get('id')])->one();

		return $this->render('admin_delivery_menu', [
			'model' => $model
		]);
	}
}
?>