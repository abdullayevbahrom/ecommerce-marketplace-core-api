<?php
use app\models\user\User;

use app\widgets\admin_menu\AdminMenu;

$this->title = 'Профиль';
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="content-wrapper">
    <?php if (Yii::$app->session->hasFlash('moderator_deleted')) {?>
        <div class="alert alert-success text-center"><?=Yii::$app->session->getFlash('moderator_deleted');?></div>
    <?php }?>
    <?php if (Yii::$app->session->hasFlash('moderators_deleted')) {?>
        <div class="alert alert-success text-center"><?=Yii::$app->session->getFlash('moderators_deleted');?></div>
    <?php }?>
    <section class="content-header">
        <h1><?=$this->title;?></h1>

        <ol class="breadcrumb">
            <li><a href="<?=Yii::$app->urlManager->createUrl(['/logist/'])?>"><i class="fa fa-dashboard"></i> Home</a></li>
            <li class="active"><?=$this->title;?></li>
        </ol>
    </section>
    <section class="content">
        <?php if ($model) {?>
            <div class="row">
                <div class="col-sm-3">
                    <?=AdminMenu::widget();?>
                </div>
                <div class="col-sm-9">
                    <div class="box box-info color-palette-box">
                        <div class="box-header">
                            Данные пользователя
                        </div>
                        <div class="box-body">
                            <table class="table table-striped">
                                <tr>
                                    <td>ID</td>
                                    <td><?=$model->id ? $model->id : 'Не указано';?></td>
                                </tr>
                                <tr>
                                    <td>Роль</td>
                                    <td>
                                        <?php if ($model->role == User::ROLE_ADMIN) {?>
                                            <small class="label bg-green">Администратор</small>
                                        <?php }?>
                                        <?php if ($model->role == User::ROLE_MODERATOR) {?>
                                            <small class="label bg-green">Модератор</small>
                                        <?php }?>
                                        <?php if ($model->role == User::ROLE_SHOP) {?>
                                            <small class="label bg-green">Магазин</small>
                                        <?php }?>
                                        <?php if ($model->role == User::ROLE_LOGIST) {?>
                                            <small class="label bg-green">Логистика</small>
                                        <?php }?>
                                    </td>
                                </tr> 
                                <tr>
                                    <td>Логин</td>
                                    <td><?=$model->login ? $model->login : 'Не указано';?></td>
                                </tr>    
                                <tr>
                                    <td>Имя</td>
                                    <td><?=$model->name ? $model->name : 'Не указано';?></td>
                                </tr>
                                <tr>
                                    <td>Телефон</td>
                                    <td><?=$model->phone ? $model->phone : 'Не указано';?></td>
                                </tr>
                                <tr>
                                    <td>E-mail</td>
                                    <td><?=$model->email ? $model->email : 'Не указано';?></td>
                                </tr>
                                <tr>
                                    <td>Дата регистрации</td>
                                    <td><?=$model->date ? $model->date : 'Не указано';?></td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        <?php } else {?>
            <div class="card">
                <div class="card-body">
                    <div class="alert alert-warning text-center">Профиля не существует</div>
                </div>
            </div>
        <?php }?>
    </section>
</div>