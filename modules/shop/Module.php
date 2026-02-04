<?php

namespace app\modules\shop;

use Yii;
use app\models\user\User;
use app\models\shop\Shop;

/**
 * shop module definition class
 */
class Module extends \yii\base\Module
{
    /**
     * @inheritdoc
     */
    public $controllerNamespace = 'app\modules\shop\controllers';
    
    /**
     * @inheritdoc
     */
    public function init()
    {
        $this->layout = '/shop';
        parent::init();

        // custom initialization code goes here
    }
    
    /**
     * Prevent access to shop module for users without actual shop records
     */
    public function beforeAction($action)
    {
        // Check if user is logged in
        if (Yii::$app->user->isGuest) {
            Yii::$app->response->redirect(['/admin/default'])->send();
            return false;
        }

        // Get current user
        $user = User::findOne(Yii::$app->user->identity->id);
        
        // Check if user has an actual shop record
        $shop = Shop::findOne(['user_id' => $user->id]);
        
        if (!$shop) {
            // User doesn't have a shop - prevent access to entire shop module
            Yii::$app->user->logout();
            Yii::$app->session->setFlash('error', 'У вас нет магазина. Обратитесь к администратору для создания магазина.');
            Yii::$app->response->redirect(['/admin/default'])->send();
            return false;
        }
        
        // Check if user has proper role for shop access
        if (!in_array($user->role, [User::ROLE_SHOP, User::ROLE_ADMIN, User::ROLE_MODERATOR])) {
            Yii::$app->user->logout();
            Yii::$app->session->setFlash('error', 'У вас нет прав доступа к магазину.');
            Yii::$app->response->redirect(['/admin/default'])->send();
            return false;
        }
        
        return parent::beforeAction($action);
    }
}
