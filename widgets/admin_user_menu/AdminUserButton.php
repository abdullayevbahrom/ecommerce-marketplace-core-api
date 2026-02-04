<?php 
namespace app\widgets\admin_user_menu;

use Yii;
use yii\bootstrap4\Widget;

use app\models\user\User;

class AdminUserButton extends Widget{
	public function init() {}

	public function run() {
		$user = Yii::$app->user->identity;

		$model = User::find()->with('image')->where(['id'=>Yii::$app->request->get('id')])->one();

		return $this->render('admin-user-button', [
			'model' => $model,
			'user' => $user
		]);
	}
}
?>