<?php
use yii\helpers\ArrayHelper;

use app\widgets\admin_logist_menu\AdminLogistMenu;

$this->title = 'Логистическая компания';
$this->params['breadcrumbs'][] = $this->title;

$type = Yii::$app->request->get('type');
?>

<div class="content-wrapper">
    <section class="content-header">
        <h1><?=$this->title;?></h1>

        <ol class="breadcrumb">
            <li><a href="<?=Yii::$app->urlManager->createUrl(['/logist/'])?>"><i class="fa fa-dashboard"></i> Главная</a></li>
            <li class="active"><?=$this->title;?></li>
        </ol>
    </section>
    <section class="content">
        <?php if (Yii::$app->session->hasFlash('logist_saved')) {?>
            <div class="callout callout-success text-center">
                <?=Yii::$app->session->getFlash('logist_saved');?>
            </div>
        <?php }?>
        <?php if (Yii::$app->session->hasFlash('logist_locked')) {?>
            <div class="callout callout-success text-center">
                <?=Yii::$app->session->getFlash('logist_locked');?>
            </div>
        <?php }?>
        <div class="row">
            <div class="col-sm-3">
                <?=AdminLogistMenu::widget();?>
            </div>
            <div class="col-sm-9">
                <div class="box box-info color-palette-box">
                    <div class="box-body">
                        <table class="table table-striped">
                            <tr>
                                <td>ID</td>
                                <td><?=$model->id;?></td>
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
                                <td>Название</td>
                                <td><?=$model->name_ru ? $model->name_ru : '-';?></td>
                            </tr>
                            <tr>
                                <td>Контактное лицо</td>
                                <td><?=$model->contact_user ? $model->contact_user : '-';?></td>
                            </tr>
                            <tr>
                                <td>Контактный номер</td>
                                <td><?=$model->contact_phone ? $model->contact_phone : '-';?></td>
                            </tr>
                            <tr>
                                <td>Логин</td>
                                <td><?=$model->user->login ? $model->user->login : '-';?></td>
                            </tr>
                            <tr>
                                <td>Дата</td>
                                <td><?=$model->date ? $model->date : '-';?></td>
                            </tr>
                        </table>
                    </div>
                </div>
                <?php if ($model->description_ru) {?>
                    <div class="box box-info color-palette-box">
                        <div class="box-header">Описание</div>
                        <div class="box-body">
                            <?=$model->description_ru;?>
                        </div>
                    </div>
                <?php }?>
            </div>
        </div>
    </section>
</div>