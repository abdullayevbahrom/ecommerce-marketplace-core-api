<?php 
namespace app\widgets\admin_partner_menu;

use Yii;
use yii\bootstrap4\Widget;

use app\models\partners\Partners;

class AdminPartnerMenu extends Widget{
	public function init() {}

	public function run() {
		$model = Partners::find()->with('image')->where(['id'=>Yii::$app->request->get('id')])->one();

		return $this->render('admin_partner_menu', [
			'model' => $model
		]);
	}
}
?>