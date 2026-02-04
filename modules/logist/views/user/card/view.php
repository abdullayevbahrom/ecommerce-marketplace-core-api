<?php
use app\widgets\admin_user_menu\AdminUserMenu;
use app\widgets\admin_user_menu\AdminUserButton;

$this->title = 'Карта пользователя';
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="content-wrapper">
    <section class="content-header">
        <h1><?=$this->title;?></h1>
        
        <ol class="breadcrumb">
            <li><a href="<?=Yii::$app->urlManager->createUrl(['/shop/'])?>"><i class="fa fa-dashboard"></i> Главная</a></li>
            <li class="active"><?=$this->title;?></li>
        </ol>
    </section>
    <section class="content">
        <?php if (Yii::$app->session->hasFlash('card_saved')) {?>
            <div class="callout callout-success text-center">
                <?=Yii::$app->session->getFlash('card_saved');?>
            </div>
        <?php }?>
        <?php if (Yii::$app->session->hasFlash('card_locked')) {?>
            <div class="callout callout-success text-center">
                <?=Yii::$app->session->getFlash('card_locked');?>
            </div>
        <?php }?>
        <div class="row">
            <div class="col-sm-3">
                <?=AdminUserMenu::widget();?>
            </div>
            <div class="col-sm-9">
                <div class="box box-info color-palette-box">
                    <div class="box-header">
                        <div class="pull-right">
                            <a href="<?=Yii::$app->urlManager->createUrl(['/shop/user/card-create', 'id'=>Yii::$app->request->get('id'), 'card_id'=>$model->id]);?>" class="btn btn-info"><i class="fa fa-pencil"></i> Редактировать</a>
                            <a href="<?=Yii::$app->urlManager->createUrl(['/shop/user/card-lock', 'id'=>Yii::$app->request->get('id'), 'card_id'=>$model->id]);?>" class="btn btn-warning"><?php if ($model->status == 1) {?><i class="fa fa-lock"></i> Заблокировать<?php } else {?><i class="fa fa-unlock"></i> Разблокировать<?php }?></a>
                            <a href="<?=Yii::$app->urlManager->createUrl(['/shop/user/card-remove', 'id'=>Yii::$app->request->get('id'), 'card_id'=>$model->id]);?>" class="btn btn-danger remove-object"><i class="fa fa-remove"></i> Удалить</a>
                            <?=AdminUserButton::widget();?>
                        </div>
                        Данные карты
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
                                <td>Номер карты</td>
                                <td><?=$model->card_number ? $model->card_number : 'Не указано';?></td>
                            </tr>
                            <tr>
                                <td>Срок карты</td>
                                <td><?=$model->card_expire ? $model->card_expire : 'Не указано';?></td>
                            </tr>
                            <tr>
                                <td>Номер телефона</td>
                                <td><?=$model->card_phone_number ? $model->card_phone_number : 'Не указано';?></td>
                            </tr>
                            <tr>
                                <td>Дата</td>
                                <td><?=$model->date ? $model->date : 'Не указано';?></td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>