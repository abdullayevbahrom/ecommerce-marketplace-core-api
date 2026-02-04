<?php 
namespace app\widgets\admin_banner_menu;

use Yii;
use yii\bootstrap4\Widget;

use app\models\banner\Banner;

class AdminBannerMenu extends Widget{
	public function init() {}

	public function run() {
		$model = Banner::find()->with('image')->where(['id'=>Yii::$app->request->get('id')])->one();

		return $this->render('admin_banner_menu', [
			'model' => $model
		]);
	}
}
?>