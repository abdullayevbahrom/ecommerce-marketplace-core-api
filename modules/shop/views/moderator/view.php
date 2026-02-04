<?php

use app\widgets\admin_moderator_menu\AdminModeratorMenu;

$this->title = 'Профиль модератора';
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="content-wrapper">
    <section class="content-header">
        <h1><?=$this->title;?></h1>

        <ol class="breadcrumb">
            <li><a href="<?=Yii::$app->urlManager->createUrl(['/shop/'])?>"><i class="fa fa-dashboard"></i> Home</a></li>
            <li class="active"><?=$this->title;?></li>
        </ol>
    </section>
    <section class="content">
        <?php if (Yii::$app->session->hasFlash('moderator_saved')) {?>
            <div class="alert alert-success text-center">
                <?=Yii::$app->session->getFlash('moderator_saved');?>
            </div>
        <?php }?>
        <?php if ($model) {?>
            <div class="row">
                <div class="col-sm-3">
                    <?=AdminModeratorMenu::widget();?>
                </div>
                <div class="col-sm-9">
                    <div class="box box-info color-palette-box">
                        <div class="box-header">
                            Данные модератора
                        </div>
                        <div class="box-body">
                            <table class="table table-striped">
                                <tr>
                                    <td>ID</td>
                                    <td><?=$model->id ? $model->id : 'Не указано';?></td>
                                </tr>
                                <tr>
                                    <td>Статус</td>
                                    <td>
                                        <?php if ($model->status == 1) {?>
                                            <small class="label bg-green">Активный</small>
                                        <?php }?>
                                        <?php if ($model->status == 2) {?>
                                            <small class="label bg-red">Заблокирован</small>
                                        <?php }?>
                                    </td>
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
                                    <td>Логин</td>
                                    <td><?=$model->login ? $model->login : 'Не указано';?></td>
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
            <div class="box">
                <div class="box-body">
                    <div class="alert alert-warning text-center">Модератора не существует</div>
                </div>
            </div>
        <?php }?>
    </section>
</div>