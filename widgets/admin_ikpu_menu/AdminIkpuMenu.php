<?php 

namespace app\widgets\admin_ikpu_menu;

use Yii;
use yii\bootstrap4\Widget;

use app\models\Ikpu;
use app\models\user\User;

class AdminIkpuMenu extends Widget
{
    public function init() {}

    public function run() 
    {
        $model = Ikpu::find()->where(['id' => Yii::$app->request->get('id')])->one();

        return $this->render('admin_ikpu_menu', [
            'model' => $model,
            'user' => Yii::$app->user->identity
        ]);
    }
}
