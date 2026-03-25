<?php

/* @var $this \yii\web\View */
/* @var $content string */

use app\widgets\Alert;
use yii\widgets\Breadcrumbs;
use yii\bootstrap4\Nav;
use yii\bootstrap4\NavBar;
use yii\helpers\Html;
use app\assets\AdminAsset;

use app\models\user\User;
use app\models\feedback\Feedback;
use app\models\order\Order;
use app\models\Notification;
use app\models\shop\support\ShopSupport;
use app\models\seller\SellerApplication;
use app\models\product\ProductRequest;

AdminAsset::register($this);

$controller = Yii::$app->controller->id;
$action = Yii::$app->controller->action->id;

$user = null;
$notifications = null;
if (!Yii::$app->user->isGuest) {
    $user = User::find()->with('image', 'moderatorAccess', 'moderatorAccess.moderator')->where(['id'=>Yii::$app->user->identity->id])->one();
    $notifications = Notification::find()->where(['status'=>0])->count();
    if ($notifications == 0) {
        $notifications = '';
    }
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
$order_count = Order::find()->where(['status'=>0])->count();
$seller_application_count = SellerApplication::find()->where(['status'=>SellerApplication::STATUS_PENDING])->count();
$product_request_count = ProductRequest::find()->where(['status'=>ProductRequest::STATUS_PENDING])->count();
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
            <a href="<?=Yii::$app->urlManager->createUrl(['/admin/user'])?>" class="logo">
                <span class="logo-mini"><b>M</b>C</span>
                <span class="logo-lg"><b>app</b></span>
            </a>
            <nav class="navbar navbar-static-top">
                <div class="navbar-custom-menu">
                    <ul class="nav navbar-nav">
                        <li class="dropdown notifications-menu">
                            <a href="<?=Yii::$app->urlManager->createUrl(['/admin/notification'])?>" id="subscribe">
                                <i class="fa fa-bell-o"></i>
                                <span class="label label-warning"><?=($notifications > 0) ? $notifications : '';?></span>
                            </a>
                        </li>
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
                                        <a href="<?=Yii::$app->urlManager->createUrl(['/admin/default/profile'])?>" class="btn btn-default btn-flat">Профиль</a>
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
                        <img src="<?=$user ? $user->getPhoto('100x100') : '';?>" class="img-circle" alt="User Image">
                    </div>
                    <div class="pull-left info">
                        <p><?=$user ? $user->name : '';?></p>
                        <a href="javascript:;"><i class="fa fa-circle text-success"></i> Администратор</a>
                    </div>
                </div>

                <ul class="sidebar-menu" data-widget="tree">
                    <li class="header">Меню</li>
                    <?php if($user && ($user->role == User::ROLE_ADMIN)) {?>
                        <li <?=(($controller == 'default') && ($action == 'dashboard')) ? 'class="active"' : '';?>>
                            <a href="<?=Yii::$app->urlManager->createUrl(['/admin/default/dashboard'])?>">
                                <i class="fa fa-home"></i> <span>Главная</span>
                            </a>
                        </li>
                    <?php }?>

                    <?php if($user && (($user->role == User::ROLE_ADMIN) || in_array('user', $accesses))) {?>
                        <li class="treeview<?=($controller == 'user' || $controller == 'moderator') ? ' active' : '';?>">
                            <a href="#">
                                <i class="fa fa-group"></i> <span>Пользователи</span>
                                <span class="pull-right-container">
                                    <i class="fa fa-angle-left pull-right"></i>
                                </span>
                            </a>
                            <ul class="treeview-menu">
                                <li <?=($controller == 'user' && $action == 'all') ? 'class="active"' : '';?>>
                                    <a href="<?=Yii::$app->urlManager->createUrl(['/admin/user/all'])?>"><i class="fa fa-circle-o"></i> Все пользователи</a>
                                </li>
                                <li <?=($controller == 'user' && $action == 'index') ? 'class="active"' : '';?>>
                                    <a href="<?=Yii::$app->urlManager->createUrl(['/admin/user'])?>"><i class="fa fa-circle-o"></i> Клиенты</a>
                                </li>
                                <?php if ($user->role == User::ROLE_ADMIN) {?>
                                    <li <?=($controller == 'user' && $action == 'admins') ? 'class="active"' : '';?>>
                                        <a href="<?=Yii::$app->urlManager->createUrl(['/admin/user/admins'])?>"><i class="fa fa-circle-o"></i> Администраторы</a>
                                    </li>
                                <?php }?>
                                <li <?=($controller == 'moderator') ? 'class="active"' : '';?>>
                                    <a href="<?=Yii::$app->urlManager->createUrl(['/admin/moderator'])?>"><i class="fa fa-circle-o"></i> Модераторы</a>
                                </li>
                                <li <?=($controller == 'user' && $action == 'logists') ? 'class="active"' : '';?>>
                                    <a href="<?=Yii::$app->urlManager->createUrl(['/admin/user/logists'])?>"><i class="fa fa-circle-o"></i> Логисты</a>
                                </li>
                                <li <?=($controller == 'user' && $action == 'operators') ? 'class="active"' : '';?>>
                                    <a href="<?=Yii::$app->urlManager->createUrl(['/admin/user/operators'])?>"><i class="fa fa-circle-o"></i> Операторы</a>
                                </li>
                                <li <?=($controller == 'user' && $action == 'shops') ? 'class="active"' : '';?>>
                                    <a href="<?=Yii::$app->urlManager->createUrl(['/admin/user/shops'])?>"><i class="fa fa-circle-o"></i> Магазины</a>
                                </li>
                            </ul>
                        </li>
                    <?php }?>

                    <?php if($user && (($user->role == User::ROLE_ADMIN) || in_array('shop', $accesses) || in_array('shop-advertising', $accesses) || in_array('shop-oferta', $accesses) || in_array('shop-document', $accesses) || in_array('didox', $accesses))) {?>
                        <li  class="treeview<?=(($controller == 'shop') || ($controller == 'shop-advertising') || ($controller == 'shop-oferta') || ($controller == 'shop-document') || ($controller == 'didox')) ? ' active' : '';?>">
                            <a href="#">
                                <i class="fa fa-briefcase"></i> <span>Магазины</span>
                                <span class="pull-right-container">
                                    <i class="fa fa-angle-left pull-right"></i>
                                </span>
                            </a>
                            <ul class="treeview-menu">
                                <?php if ($user->role == User::ROLE_ADMIN || in_array('shop', $accesses)) {?>
                                    <li><a href="<?=Yii::$app->urlManager->createUrl(['/admin/shop'])?>"><i class="fa fa-circle-o"></i> Магазины</a></li>
                                <?php }?>
                                <?php if ($user->role == User::ROLE_ADMIN || in_array('shop-advertising', $accesses)) {?>
                                    <li><a href="<?=Yii::$app->urlManager->createUrl(['/admin/shop-advertising'])?>"><i class="fa fa-circle-o"></i> Реклама</a></li>
                                <?php }?>
                                <?php if ($user->role == User::ROLE_ADMIN || in_array('shop-oferta', $accesses)) {?>
                                    <li><a href="<?=Yii::$app->urlManager->createUrl(['/admin/shop-oferta'])?>"><i class="fa fa-circle-o"></i> Оферта</a></li>
                                <?php }?>
                                <?php if ($user->role == User::ROLE_ADMIN || in_array('shop-document', $accesses)) {?>
                                    <li class="treeview<?=($controller == 'shop-document' || $controller == 'didox') ? ' active' : '';?>">
                                        <a href="#">
                                            <i class="fa fa-circle-o"></i> <span>Documentation</span>
                                            <span class="pull-right-container">
                                                <i class="fa fa-angle-left pull-right"></i>
                                            </span>
                                        </a>
                                        <ul class="treeview-menu">
                                            <li><a href="<?=Yii::$app->urlManager->createUrl(['/admin/shop-document'])?>"><i class="fa fa-file-o"></i> Regular Documents</a></li>
                                            <?php if ($user->role == User::ROLE_ADMIN || in_array('didox', $accesses)) {?>
                                                <li class="treeview<?=(($controller == 'didox')) ? ' active' : '';?>">
                                                    <a href="#">
                                                        <i class="fa fa-cloud"></i> <span>DIDOX Documents</span>
                                                        <span class="pull-right-container">
                                                            <i class="fa fa-angle-left pull-right"></i>
                                                        </span>
                                                    </a>
                                                    <ul class="treeview-menu">
                                                        <li<?=($controller == 'didox' && $action == 'index') ? ' class="active"' : '';?>><a href="<?=Yii::$app->urlManager->createUrl(['/admin/didox/index'])?>"><i class="fa fa-list"></i> All Documents</a></li>
                                                        <li<?=($controller == 'didox' && $action == 'create') ? ' class="active"' : '';?>><a href="<?=Yii::$app->urlManager->createUrl(['/admin/didox/create'])?>"><i class="fa fa-plus"></i> Create Document</a></li>
                                                        <li<?=($controller == 'didox' && $action == 'ikpu') ? ' class="active"' : '';?>><a href="<?=Yii::$app->urlManager->createUrl(['/admin/didox/ikpu'])?>"><i class="fa fa-tags"></i> ИКПУ Management</a></li>
                                                        <li><a href="<?=Yii::$app->urlManager->createUrl(['/admin/didox/login'])?>"><i class="fa fa-sign-in"></i> E-IMZO Login</a></li>
                                                    </ul>
                                                </li>
                                            <?php }?>
                                        </ul>
                                    </li>
                                <?php }?>
                            </ul>
                        </li>
                    <?php }?>

                    <?php if($user && (($user->role == User::ROLE_ADMIN) || in_array('product', $accesses) || in_array('filter', $accesses) || in_array('color', $accesses) || in_array('product-type', $accesses) || in_array('ikpu', $accesses))) {?>
                    <li  class="treeview<?=(($controller == 'product') || ($controller == 'filter') || ($controller == 'color') || ($controller == 'product-type') || ($controller == 'ikpu')) ? ' active' : '';?>">
                            <a href="#">
                                <i class="fa fa-shopping-cart"></i> <span>Товары</span>
                                <span class="pull-right-container">
                                    <i class="fa fa-angle-left pull-right"></i>
                                </span>
                            </a>
                            <ul class="treeview-menu">
                                <?php if ($user->role == User::ROLE_ADMIN || in_array('product', $accesses)) {?>
                                    <li><a href="<?=Yii::$app->urlManager->createUrl(['/admin/product'])?>"><i class="fa fa-circle-o"></i> Товары</a></li>
                                <?php }?>
                                <?php if ($user->role == User::ROLE_ADMIN || in_array('ikpu', $accesses)) {?>
                                    <li><a href="<?=Yii::$app->urlManager->createUrl(['/admin/ikpu'])?>"><i class="fa fa-tags"></i> ИКПУ</a></li>
                                <?php }?>
                                <?php if ($user->role == User::ROLE_ADMIN || in_array('filter', $accesses)) {?>
                                    <li><a href="<?=Yii::$app->urlManager->createUrl(['/admin/filter'])?>"><i class="fa fa-circle-o"></i> Фильтр</a></li>
                                <?php }?>
                                <?php if ($user->role == User::ROLE_ADMIN || in_array('color', $accesses)) {?>
                                    <li><a href="<?=Yii::$app->urlManager->createUrl(['/admin/color'])?>"><i class="fa fa-circle-o"></i> Цвета</a></li>
                                <?php }?>
                                <?php if ($user->role == User::ROLE_ADMIN || in_array('product-type', $accesses)) {?>
                                    <li><a href="<?=Yii::$app->urlManager->createUrl(['/admin/product-type'])?>"><i class="fa fa-circle-o"></i> Тип товаров</a></li>
                                <?php }?>
                                <?php if ($user->role == User::ROLE_ADMIN || in_array('product', $accesses)) {?>
                                    <li><a href="<?=Yii::$app->urlManager->createUrl(['/admin/product/asl-belgisi'])?>"><i class="fa fa-check-circle"></i> ASL Belgisi</a></li>
                                <?php }?>
                            </ul>
                        </li>
                    <?php }?>

                    <?php if($user && (($user->role == User::ROLE_ADMIN) || in_array('logist', $accesses))) {?>
                        <li <?=($controller == 'logist') ? 'class="active"' : '';?>>
                            <a href="<?=Yii::$app->urlManager->createUrl(['/admin/logist'])?>">
                                <i class="fa fa-truck"></i> <span>Компании (логистика)</span>
                            </a>
                        </li>
                    <?php }?> 

                    <?php if($user && (($user->role == User::ROLE_ADMIN) || in_array('stock', $accesses))) {?>
                        <li <?=($controller == 'stock') ? 'class="active"' : '';?>>
                            <a href="<?=Yii::$app->urlManager->createUrl(['/admin/stock'])?>">
                                <i class="fa fa-archive"></i> <span>Склады</span>
                            </a>
                        </li>
                    <?php }?>

                    <?php if($user && (($user->role == User::ROLE_ADMIN) || in_array('banner', $accesses))) {?>
                        <li <?=($controller == 'banner') ? 'class="active"' : '';?>>
                            <a href="<?=Yii::$app->urlManager->createUrl(['/admin/banner'])?>">
                                <i class="fa fa-image"></i> <span>Банеры</span>
                            </a>
                        </li>
                    <?php }?>

                    <?php if($user && (($user->role == User::ROLE_ADMIN) || in_array('review', $accesses))) {?>
                        <li <?=($controller == 'review') ? 'class="active"' : '';?>>
                            <a href="<?=Yii::$app->urlManager->createUrl(['/admin/review'])?>">
                                <i class="fa fa-commenting"></i> <span>Отзывы</span>
                            </a>
                        </li>
                    <?php }?>

                    <?php if($user && ($user->role == User::ROLE_ADMIN)) {?>
                        <li <?=($controller == 'promocode') ? 'class="active"' : '';?>>
                            <a href="<?=Yii::$app->urlManager->createUrl(['/admin/promocode'])?>">
                                <i class="fa fa-ticket"></i> <span>Промокоды</span>
                            </a>
                        </li>
                    <?php }?>

                    <?php if($user && (($user->role == User::ROLE_ADMIN)|| in_array('order', $accesses))) {?>
                        <li <?=($controller == 'order') ? 'class="active"' : '';?>>
                            <a href="<?=Yii::$app->urlManager->createUrl(['/admin/order'])?>">
                                <i class="fa fa-star"></i> <span>Заказы</span>
                                <?php if ($order_count > 0) {?>
                                    <span class="pull-right-container">
                                        <small class="label pull-right bg-red"><?=$order_count;?></small>
                                    </span>
                                <?php }?>
                            </a>
                        </li>
                    <?php }?>

                    <?php if($user && (($user->role == User::ROLE_ADMIN)|| in_array('news', $accesses))) {?>
                        <li <?=($controller == 'news') ? 'class="active"' : '';?>>
                            <a href="<?=Yii::$app->urlManager->createUrl(['/admin/news'])?>">
                                <i class="fa fa-newspaper-o"></i> <span>Новости</span>
                            </a>
                        </li>
                    <?php }?>

                    <?php if($user && (($user->role == User::ROLE_ADMIN)|| in_array('question', $accesses))) {?>
                        <li <?=($controller == 'question') ? 'class="active"' : '';?>>
                            <a href="<?=Yii::$app->urlManager->createUrl(['/admin/question'])?>">
                                <i class="fa fa-question"></i> <span>Частые вопросы</span>
                            </a>
                        </li>
                    <?php }?>

                    <?php if($user && (($user->role == User::ROLE_ADMIN) || in_array('slider', $accesses))) {?>
                        <li <?=($controller == 'slider') ? 'class="active"' : '';?>>
                            <a href="<?=Yii::$app->urlManager->createUrl(['/admin/slider'])?>">
                                <i class="fa fa-image"></i> <span>Слайдер</span>
                            </a>
                        </li>
                    <?php }?>

                    <?php if($user && (($user->role == User::ROLE_ADMIN) || in_array('feedback', $accesses))) {?>
                        <li <?=($controller == 'feedback') ? 'class="active"' : '';?>>
                            <a href="<?=Yii::$app->urlManager->createUrl(['/admin/feedback'])?>">
                                <i class="fa fa-phone"></i> <span>Поддержка</span>
                                <?php if ($feedback_count > 0) {?>
                                    <span class="pull-right-container">
                                        <small class="label pull-right bg-red"><?=$feedback_count;?></small>
                                    </span>
                                <?php }?>
                            </a>
                        </li>
                    <?php }?>

                    <?php if($user && (($user->role == User::ROLE_ADMIN) || in_array('seller-application', $accesses))) {?>
                        <li <?=($controller == 'seller-application') ? 'class="active"' : '';?>>
                            <a href="<?=Yii::$app->urlManager->createUrl(['/admin/seller-application'])?>">
                                <i class="fa fa-users"></i> <span>Заявки продавцов</span>
                                <?php if ($seller_application_count > 0) {?>
                                    <span class="pull-right-container">
                                        <small class="label pull-right bg-red"><?=$seller_application_count;?></small>
                                    </span>
                                <?php }?>
                            </a>
                        </li>
                    <?php }?>

                    <?php if($user && (($user->role == User::ROLE_ADMIN) || in_array('product-request', $accesses))) {?>
                        <li <?=($controller == 'product-request') ? 'class="active"' : '';?>>
                            <a href="<?=Yii::$app->urlManager->createUrl(['/admin/product-request'])?>">
                                <i class="fa fa-shopping-cart"></i> <span>Запросы на товар</span>
                                <?php if ($product_request_count > 0) {?>
                                    <span class="pull-right-container">
                                        <small class="label pull-right bg-red"><?=$product_request_count;?></small>
                                    </span>
                                <?php }?>
                            </a>
                        </li>
                    <?php }?>

                    <?php /* Модераторы moved inside Пользователи dropdown */ ?>

                    <?php if ($user && (($user->role == User::ROLE_ADMIN) || in_array('category', $accesses) || in_array('delivery', $accesses) || in_array('brand', $accesses) || in_array('category?type=product', $accesses) || in_array('category?type=region', $accesses) || in_array('category?type=card', $accesses) || in_array('category?type=payment', $accesses) || in_array('category?type=unit', $accesses) || in_array('category?type=currency', $accesses))) {?>
                        <li  class="treeview<?=(($controller == 'category') || ($controller == 'delivery') || ($controller == 'brand')) ? ' active' : '';?>">
                            <a href="#">
                                <i class="fa fa-list"></i> <span>Справочник</span>
                                <span class="pull-right-container">
                                    <i class="fa fa-angle-left pull-right"></i>
                                </span>
                            </a>
                            <ul class="treeview-menu">
                                <?php if ($user->role == User::ROLE_ADMIN || in_array('category?type=product', $accesses)) {?>    
                                    <li><a href="<?=Yii::$app->urlManager->createUrl(['/admin/category', 'type'=>'product'])?>"><i class="fa fa-circle-o"></i> Категории товаров</a></li>
                                <?php }?>
                                <?php if ($user->role == User::ROLE_ADMIN || in_array('brand', $accesses)) {?>
                                    <li><a href="<?=Yii::$app->urlManager->createUrl(['/admin/brand'])?>"><i class="fa fa-circle-o"></i> Бренды</a></li>
                                <?php }?>
                                <?php if ($user->role == User::ROLE_ADMIN || in_array('category?type=region', $accesses)) {?>
                                    <li><a href="<?=Yii::$app->urlManager->createUrl(['/admin/category', 'type'=>'region'])?>"><i class="fa fa-circle-o"></i> Регионы</a></li>
                                <?php }?>
                                <?php if ($user->role == User::ROLE_ADMIN || in_array('category?type=card', $accesses)) {?>
                                    <li><a href="<?=Yii::$app->urlManager->createUrl(['/admin/category', 'type'=>'card'])?>"><i class="fa fa-circle-o"></i> Типы карт</a></li>
                                <?php }?>
                                <?php if ($user->role == User::ROLE_ADMIN || in_array('delivery', $accesses)) {?>
                                    <li><a href="<?=Yii::$app->urlManager->createUrl(['/admin/delivery'])?>"><i class="fa fa-circle-o"></i> Способы доставки</a></li>
                                <?php }?>
                                <?php if ($user->role == User::ROLE_ADMIN || in_array('category?type=payment', $accesses)) {?>
                                    <li><a href="<?=Yii::$app->urlManager->createUrl(['/admin/category', 'type'=>'payment'])?>"><i class="fa fa-circle-o"></i> Способы оплаты</a></li>
                                <?php }?>
                                <?php if ($user->role == User::ROLE_ADMIN || in_array('category?type=unit', $accesses)) {?>
                                    <li><a href="<?=Yii::$app->urlManager->createUrl(['/admin/category', 'type'=>'unit'])?>"><i class="fa fa-circle-o"></i> Ед. измерения</a></li>
                                <?php }?>
                                <?php if ($user->role == User::ROLE_ADMIN || in_array('category?type=currency', $accesses)) {?>
                                    <li><a href="<?=Yii::$app->urlManager->createUrl(['/admin/category', 'type'=>'currency'])?>"><i class="fa fa-circle-o"></i> Валюта</a></li>
                                <?php }?>
                                <?php if ($user->role == User::ROLE_ADMIN || in_array('category?type=tag', $accesses)) {?>
                                    <li><a href="<?=Yii::$app->urlManager->createUrl(['/admin/category', 'type'=>'tag'])?>"><i class="fa fa-circle-o"></i> Теги</a></li>
                                <?php }?>
                                <!-- <li><a href="<?=Yii::$app->urlManager->createUrl(['/admin/category', 'type'=>'refund'])?>"><i class="fa fa-circle-o"></i> Возвраты</a></li> -->
                            </ul>
                        </li>
                    <?php }?>

                    <?php if ($user && (($user->role == User::ROLE_ADMIN) || in_array('settings/call-center', $accesses) || in_array('settings/logo', $accesses) || in_array('settings/didox', $accesses))) {?>
                        <?php $admin_settings = ['settings'];?>
                        <li class="treeview<?=in_array($controller, $admin_settings) ? ' active' : '';?>">
                            <a href="#">
                                <i class="fa fa-cogs"></i> <span>Управление</span>
                                <span class="pull-right-container">
                                    <i class="fa fa-angle-left pull-right"></i>
                                </span>
                            </a>
                            <ul class="treeview-menu">
                                <?php if ($user && (($user->role == User::ROLE_ADMIN) || in_array('settings/call-center', $accesses))) {?>
                                    <li><a href="<?=Yii::$app->urlManager->createUrl(['/admin/settings/call-center'])?>"><i class="fa fa-circle-o"></i> Телефон (Call Center)</a></li>
                                <?php }?>
                                <?php if ($user && (($user->role == User::ROLE_ADMIN) || in_array('settings/logo', $accesses))) {?>
                                    <li><a href="<?=Yii::$app->urlManager->createUrl(['/admin/settings/logo'])?>"><i class="fa fa-circle-o"></i> Логотип</a></li>
                                <?php }?>
                                <?php if ($user && (($user->role == User::ROLE_ADMIN) || in_array('settings/didox', $accesses))) {?>
                                    <li><a href="<?=Yii::$app->urlManager->createUrl(['/admin/settings/didox'])?>"><i class="fa fa-circle-o"></i> E-IMZO</a></li>
                                <?php }?>
                            </ul>
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
                            <li><a href="<?=Yii::$app->urlManager->createUrl(['/admin/default/profile'])?>"><i class="fa fa-circle-o"></i> Профиль</a></li>
                            <li><a href="<?=Yii::$app->urlManager->createUrl(['/admin/default/change-password'])?>"><i class="fa fa-circle-o"></i> Сменить пароль</a></li>
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

<script>
    $(document).on('click', '.view_category', function () {
        const categoryId = $(this).data('value');
    
        if (!categoryId) {
            console.error('Category ID not found');
            return;
        }
    
        // подставляем action в форму
        $('#moderator-comment-form').attr(
            'action',
            '/admin/category/comment?id=' + categoryId
        );
    });
</script>
</body>
</html>
<?php $this->endPage() ?>
