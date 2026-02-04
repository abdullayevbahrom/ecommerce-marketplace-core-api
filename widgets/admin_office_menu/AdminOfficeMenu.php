<?php 
namespace app\widgets\admin_office_menu;

use Yii;
use yii\bootstrap4\Widget;

use app\models\office\Office;

class AdminOfficeMenu extends Widget{
	public function init() {}

	public function run() {
		$model = Office::find()->where(['id'=>Yii::$app->request->get('id')])->one();

		return $this->render('admin_office_menu', [
			'model' => $model,
			'user' => Yii::$app->user->identity
		]);
	}
}
?>