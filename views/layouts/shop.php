<?php

/* @var $this \yii\web\View */
/* @var $content string */

use app\widgets\Alert;
use yii\widgets\Breadcrumbs;
use yii\bootstrap4\Nav;
use yii\bootstrap4\NavBar;
use yii\helpers\Html;
use yii\helpers\ArrayHelper;
use app\assets\AdminAsset;

use app\models\user\User;
use app\models\feedback\Feedback;
use app\models\order\Order;
use app\models\order\product\OrderProduct;
use app\models\Notification;
use app\models\shop\Shop;

AdminAsset::register($this);

$controller = Yii::$app->controller->id;
$action = Yii::$app->controller->action->id;

$user = null;
$shop = null;
$notifications = null;
if (!Yii::$app->user->isGuest) {
    $user = User::find()->with('image', 'moderatorAccess', 'moderatorAccess.moderator')->where(['id'=>Yii::$app->user->identity->id])->one();
    $notifications = Notification::find()->where(['status'=>0])->count();
    if ($notifications == 0) {
        $notifications = '';
    }

    $shop = Shop::find()->with('image')->where(['user_id'=>$user->id])->one();
}

$accesses = array();

if ($user && $user->moderatorAccess) {
    foreach ($user->moderatorAccess as $v) {
        if ($v && $v->moderator) {
            $accesses[] = $v->moderator->url;
        }
    }
}

$feedback_count = Feedback::find()->where(['status'=>0])->count();

$order_products = ArrayHelper::map(OrderProduct::find()->where(['shop_id'=>$shop->id])->all(), 'order_id', 'order_id');
$order_count = Order::find()->where(['status'=>0])->andWhere(['in', 'id', $order_products])->count();
?>
<?php $this->beginPage() ?>
<!DOCTYPE html>
<html lang="<?= Yii::$app->language ?>">
<head>
    <meta charset="<?= Yii::$app->charset ?>">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <script type="text/javascript" src="//www.gstatic.com/firebasejs/3.6.8/firebase.js"></script>
    
    <?= Html::csrfMetaTags() ?>
    <title><?= Html::encode($this->title) ?></title>
    <?php $this->head() ?>
</head>
<body class="hold-transition <?=(($controller == 'default') && ($action == 'index')) ? 'login-page' : 'skin-blue sidebar-mini'?>">
<?php $this->beginBody() ?>
<div class="wrapper">
    <?php if (($controller != 'default') || (($controller == 'default') && ($action != 'index'))) {?>
        <header class="main-header">
            <a href="<?=Yii::$app->urlManager->createUrl(['/shop/user'])?>" class="logo">
                <span class="logo-mini"><b>M</b>C</span>
                <span class="logo-lg"><b>app</b></span>
            </a>
            <nav class="navbar navbar-static-top">
                <div class="navbar-custom-menu">
                    <ul class="nav navbar-nav">
                        <!-- <li class="dropdown notifications-menu">
                            <a href="<?=Yii::$app->urlManager->createUrl(['/shop/notification'])?>" id="subscribe">
                                <i class="fa fa-bell-o"></i>
                                <span class="label label-warning"><?=($notifications > 0) ? $notifications : '';?></span>
                            </a>
                        </li> -->
                        <li class="dropdown user user-menu">
                            <a href="#" class="dropdown-toggle" data-toggle="dropdown">
                                <img src="<?=$user ? $user->getPhoto('100x100') : '';?>" class="user-image" alt="User Image">
                                <span class="hidden-xs"><?=$user ? $user->name : '';?></span>
                            </a>
                            <ul class="dropdown-menu">
                                <li class="user-header">
                                    <img src="<?=$user ? $user->getPhoto('100x100') : '';?>" class="img-circle" alt="User Image">
                                    <p><?=$user ? $user->name : '';?></p>
                                </li>
                                <li class="user-footer">
                                    <div class="pull-left">
                                        <a href="<?=Yii::$app->urlManager->createUrl(['/shop/default/profile'])?>" class="btn btn-default btn-flat">Профиль</a>
                                    </div>
                                    <div class="pull-right">
                                        <a href="<?=Yii::$app->urlManager->createUrl(['/main/log-out'])?>" class="btn btn-default btn-flat">Выйти</a>
                                    </div>
                                </li>
                            </ul>
                        </li>
                    </ul>
                </div>
            </nav>
        </header>

        <aside class="main-sidebar">
            <section class="sidebar">
                <div class="user-panel">
                    <div class="pull-left image">
                        <!-- <img src="<?=$shop ? $shop->getPhoto('100x100') : '';?>" class="img-circle" alt="User Image"> -->
                    </div>
                    <div class="pull-left info">
                        <p><?=$shop ? $shop->name_ru : '';?></p>
                        <a href="javascript:;"><i class="fa fa-circle text-success"></i> Магазин</a>
                    </div>
                </div>

                <ul class="sidebar-menu" data-widget="tree">
                    <li class="header">Меню</li>
                    <?php if($user && ($user->role == User::ROLE_SHOP)) {?>
                        <li <?=(($controller == 'default') && ($action == 'dashboard')) ? 'class="active"' : '';?>>
                            <a href="<?=Yii::$app->urlManager->createUrl(['/shop/default/dashboard'])?>">
                                <i class="fa fa-home"></i> <span>Главная</span>
                            </a>
                        </li>
                    <?php }?>

                    <?php if($user && ($user->role == User::ROLE_SHOP)) {?>
                        <li <?=(($controller == 'default') && ($action == 'shop')) ? 'class="active"' : '';?>>
                            <a href="<?=Yii::$app->urlManager->createUrl(['/shop/default/shop'])?>">
                                <i class="fa fa-briefcase"></i> <span>Магазин</span>
                            </a>
                        </li>
                    <?php }?>

                    <?php if($user && (($user->role == User::ROLE_SHOP) || in_array('shop-advertising', $accesses))) {?>
                        <li <?=($controller == 'shop-advertising') ? 'class="active"' : '';?>>
                            <a href="<?=Yii::$app->urlManager->createUrl(['/shop/shop-advertising'])?>">
                                <i class="fa fa-bullhorn"></i> <span>Реклама</span>
                            </a>
                        </li>
                    <?php }?>

                    <?php if($user && (($user->role == User::ROLE_SHOP) || in_array('product', $accesses))) {?>
                        <li <?=($controller == 'product') ? 'class="active"' : '';?>>
                            <a href="<?=Yii::$app->urlManager->createUrl(['/shop/product'])?>">
                                <i class="fa fa-shopping-cart"></i> <span>Товары</span>
                            </a>
                        </li>
                    <?php }?>

                    <?php if($user && (($user->role == User::ROLE_SHOP) || in_array('logist', $accesses))) {?>
                        <li <?=($controller == 'logist') ? 'class="active"' : '';?>>
                            <a href="<?=Yii::$app->urlManager->createUrl(['/shop/logist'])?>">
                                <i class="fa fa-truck"></i> <span>Компании (логистика)</span>
                            </a>
                        </li>
                    <?php }?>

                    <?php if($user && (($user->role == User::ROLE_SHOP) || in_array('stock', $accesses))) {?>
                        <li <?=($controller == 'stock') ? 'class="active"' : '';?>>
                            <a href="<?=Yii::$app->urlManager->createUrl(['/shop/stock'])?>">
                                <i class="fa fa-archive"></i> <span>Склады</span>
                            </a>
                        </li>
                    <?php }?>

                    <?php if($user && (($user->role == User::ROLE_SHOP) || in_array('review', $accesses))) {?>
                        <li <?=($controller == 'review') ? 'class="active"' : '';?>>
                            <a href="<?=Yii::$app->urlManager->createUrl(['/shop/review'])?>">
                                <i class="fa fa-commenting"></i> <span>Отзывы</span>
                            </a>
                        </li>
                    <?php }?>

                    <?php if($user && (($user->role == User::ROLE_SHOP))) {?>
                        <li <?=($controller == 'order') ? 'class="active"' : '';?>>
                            <a href="<?=Yii::$app->urlManager->createUrl(['/shop/order'])?>">
                                <i class="fa fa-star"></i> <span>Заказы</span>
                                <?php if ($order_count > 0) {?>
                                    <span class="pull-right-container">
                                        <small class="label pull-right bg-red"><?=$order_count;?></small>
                                    </span>
                                <?php }?>
                            </a>
                        </li>
                    <?php }?>

                    <?php if($user && (($user->role == User::ROLE_SHOP))) {?>
                        <li <?=($controller == 'analytics') ? 'class="active"' : '';?>>
                            <a href="<?=Yii::$app->urlManager->createUrl(['/shop/analytics'])?>">
                                <i class="fa fa-line-chart"></i> <span>Аналитика</span>
                            </a>
                        </li>
                    <?php }?>

                    <?php if($user && (($user->role == User::ROLE_SHOP))) {?>
                        <li <?=($controller == 'news') ? 'class="active"' : '';?>>
                            <a href="<?=Yii::$app->urlManager->createUrl(['/shop/news'])?>">
                                <i class="fa fa-newspaper-o"></i> <span>Новости</span>
                            </a>
                        </li>
                    <?php }?>

                    <?php if($user && (($user->role == User::ROLE_SHOP))) {?>
                        <li <?=($controller == 'shop-oferta') ? 'class="active"' : '';?>>
                            <a href="<?=Yii::$app->urlManager->createUrl(['/shop/shop-oferta'])?>">
                                <i class="fa fa-file"></i> <span>Оферта</span>
                            </a>
                        </li>
                    <?php }?>

                    <?php if($user && (($user->role == User::ROLE_SHOP))) {?>
                        <li <?=($controller == 'shop-document') ? 'class="active"' : '';?>>
                            <a href="<?=Yii::$app->urlManager->createUrl(['/shop/shop-document'])?>">
                                <i class="fa fa-pencil"></i> <span>Документы</span>
                            </a>
                        </li>
                    <?php }?>

                    <?php if($user && (($user->role == User::ROLE_SHOP))) {?>
                        <li <?=($controller == 'shop-support') ? 'class="active"' : '';?>>
                            <a href="<?=Yii::$app->urlManager->createUrl(['/shop/shop-support'])?>">
                                <i class="fa fa-warning"></i> <span>Поддержка</span>
                            </a>
                        </li>
                    <?php }?>

                    <li class="treeview<?=(($controller == 'default') && (($action == 'change-password') || ($action == 'profile'))) ? ' active' : '';?>">
                        <a href="#">
                            <i class="fa fa-cog"></i> <span>Настройки</span>
                            <span class="pull-right-container">
                                <i class="fa fa-angle-left pull-right"></i>
                            </span>
                        </a>
                        <ul class="treeview-menu">
                            <li><a href="<?=Yii::$app->urlManager->createUrl(['/shop/default/profile'])?>"><i class="fa fa-circle-o"></i> Профиль</a></li>
                            <li><a href="<?=Yii::$app->urlManager->createUrl(['/shop/default/change-password'])?>"><i class="fa fa-circle-o"></i> Сменить пароль</a></li>
                        </ul>
                    </li>
                    <li>
                        <a href="<?=Yii::$app->urlManager->createUrl(['/main/log-out'])?>">
                            <i class="fa fa-sign-out"></i>
                            <span>Выйти</span>
                        </a>
                    </li>
                </ul>
            </section>
        </aside>
    <?php }?>

    <?=$content;?>

    <?php if (($controller != 'default') || (($controller == 'default') && ($action != 'index'))) {?>
        <footer class="main-footer">
            <strong>app</strong>
        </footer>

        <!-- Control Sidebar -->
        <aside class="control-sidebar control-sidebar-dark">
            <!-- Create the tabs -->
            <ul class="nav nav-tabs nav-justified control-sidebar-tabs">
                <li><a href="#control-sidebar-home-tab" data-toggle="tab"><i class="fa fa-home"></i></a></li>

                <li><a href="#control-sidebar-settings-tab" data-toggle="tab"><i class="fa fa-gears"></i></a></li>
            </ul>
            <!-- Tab panes -->
        </aside>

        <div class="control-sidebar-bg"></div>
    <?php }?>
</div>
<?php $this->endBody() ?>
</body>
</html>
<?php $this->endPage() ?>
