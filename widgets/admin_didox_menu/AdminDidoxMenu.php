<?php
namespace app\widgets\admin_didox_menu;

use yii\base\Widget;
use yii\helpers\Html;
use Yii;
use app\models\didox\DidoxDocument;

class AdminDidoxMenu extends Widget
{
    public function init() {}

    public function run()
    {
        $model = DidoxDocument::find()->where(['id'=>Yii::$app->request->get('id')])->one();

        return $this->render('admin_didox_menu', [
            'model' => $model,
            'user' => Yii::$app->user->identity
        ]);
    }
}
?> 