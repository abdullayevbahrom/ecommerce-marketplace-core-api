<?php 
namespace app\widgets\admin_brand_menu;

use Yii;
use yii\bootstrap4\Widget;

use app\models\brand\CategoryBrand;

class AdminBrandMenu extends Widget{
	public function init() {}

	public function run() {
		$model = CategoryBrand::find()->with('image')->where(['id'=>Yii::$app->request->get('id')])->one();

		return $this->render('admin_brand_menu', [
			'model' => $model
		]);
	}
}
?>