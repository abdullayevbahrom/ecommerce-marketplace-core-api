<?php 
namespace app\widgets\admin_advantage_menu;

use Yii;
use yii\bootstrap4\Widget;

use app\models\advantages\Advantages;

class AdminAdvantageMenu extends Widget{
	public function init() {}

	public function run() {
		$model = Advantages::find()->with('image')->where(['id'=>Yii::$app->request->get('id')])->one();

		return $this->render('admin_advantage_menu', [
			'model' => $model
		]);
	}
}
?>