<?php 
namespace app\widgets\admin_logist_menu;

use Yii;
use yii\bootstrap4\Widget;

use app\models\logist\Logist;
use app\models\user\User;

class AdminLogistMenu extends Widget{
	public function init() {}

	public function run() {
		$user = Yii::$app->user->identity;

		if ($user->role == User::ROLE_LOGIST) {
			$model = Logist::find()->with('image')->where(['user_id'=>$user->id])->one();
		} else {
			$model = Logist::find()->with('image')->where(['id'=>Yii::$app->request->get('id')])->one();
		}

		return $this->render('admin_logist_menu', [
			'model' => $model,
			'user' => $user
		]);
	}
}
?>