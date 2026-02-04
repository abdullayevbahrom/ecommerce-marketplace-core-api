<?php 
namespace app\widgets\admin_stock_menu;

use Yii;
use yii\bootstrap4\Widget;

use app\models\stock\Stock;

class AdminStockMenu extends Widget{
	public function init() {}

	public function run() {
		$model = Stock::find()->with('image')->where(['id'=>Yii::$app->request->get('id')])->one();

		return $this->render('admin_stock_menu', [
			'model' => $model,
			'user' => Yii::$app->user->identity
		]);
	}
}
?>