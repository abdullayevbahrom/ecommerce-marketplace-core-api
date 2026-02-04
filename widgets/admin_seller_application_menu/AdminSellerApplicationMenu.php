<?php 
namespace app\widgets\admin_seller_application_menu;

use Yii;
use yii\bootstrap4\Widget;

use app\models\seller\SellerApplication;

class AdminSellerApplicationMenu extends Widget{
	public function init() {}

	public function run() {
		$model = SellerApplication::findOne(Yii::$app->request->get('id'));

		return $this->render('admin_seller_application_menu', [
			'model' => $model
		]);
	}
}
?> 