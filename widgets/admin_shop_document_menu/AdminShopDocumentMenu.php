<?php 
namespace app\widgets\admin_shop_document_menu;

use Yii;
use yii\bootstrap4\Widget;

use app\models\shop\document\ShopDocument;

class AdminShopDocumentMenu extends Widget{
	public function init() {}

	public function run() {
		$model = ShopDocument::find()->with('file')->where(['id'=>Yii::$app->request->get('id')])->one();

		return $this->render('admin_shop_document_menu', [
			'model' => $model,
			'user' => Yii::$app->user->identity
		]);
	}
}
?>