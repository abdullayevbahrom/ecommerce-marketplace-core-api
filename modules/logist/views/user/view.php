<?php
use app\widgets\admin_user_menu\AdminUserMenu;

$this->title = 'Профиль пользователя';
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
        <?php if (Yii::$app->session->hasFlash('user_saved')) {?>
            <div class="callout callout-success text-center">
                <?=Yii::$app->session->getFlash('user_saved');?>
            </div>
        <?php }?>
        <?php if (Yii::$app->session->hasFlash('user_locked')) {?>
            <div class="callout callout-success text-center">
                <?=Yii::$app->session->getFlash('user_locked');?>
            </div>
        <?php }?>
        <?php if ($model) {?>
            <div class="row">
                <div class="col-sm-3">
                    <?=AdminUserMenu::widget();?>
                </div>
                <div class="col-sm-9">
                    <div class="box box-info color-palette-box">
                        <div class="box-header">
                            <!-- <div class="pull-right">
                                <div class="btn-group">
                                    <button type="button" class="btn btn-primary dropdown-toggle" data-toggle="dropdown">
                                        <span class="fa fa-cog"></span>
                                    </button>
                                    <ul class="dropdown-menu pull-right">
                                        <li><a href="<?=Yii::$app->urlManager->createUrl(['/shop/user/view', 'id'=>$model->id]);?>">Пользователь</a></li>
                                        <li><a href="<?=Yii::$app->urlManager->createUrl(['/shop/user/cards', 'id'=>$model->id]);?>">Карты</a></li>
                                    </ul>
                                </div>
                            </div> -->
                            Данные пользователя
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
                                        <?php if ($model->status == 0) {?>
                                            <small class="label bg-yellow">В ожидании</small>
                                        <?php }?>
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
                                    <td>E-mail</td>
                                    <td><?=$model->email ? $model->email : 'Не указано';?></td>
                                </tr>
                                <tr>
                                    <td>Дата рождения</td>
                                    <td><?=$model->birthday ? $model->birthday : 'Не указано';?></td>
                                </tr>
                                <tr>
                                    <td>Пол</td>
                                    <td>
                                        <?php
                                            $genders = ['1'=>'Мужской', '2'=>'Женский'];
                                            echo $model->gender ? $genders[$model->gender] : 'Не указано';
                                        ?>
                                    </td>
                                </tr>
                                <tr>
                                    <td>Токен</td>
                                    <td><?=$model->token ? $model->token : 'Не указано';?></td>
                                </tr>
                                <tr>
                                    <td>Дата регистрации</td>
                                    <td><?=$model->date ? $model->date : 'Не указано';?></td>
                                </tr>
                            </table>
                        </div>
                    </div>
                    <?php if ($model->addresses) {?>
                        <div class="box box-info color-palette-box">
                            <div class="box-header">
                                Адреса
                            </div>
                            <div class="box-body">
                                <ul>
                                    <?php foreach ($model->addresses as $value) {?>
                                        <li><?=$value->address;?></li>
                                    <?php }?>
                                </ul>
                            </div>
                        </div>
                    <?php }?>
                </div>
            </div>
        <?php } else {?>
            <div class="alert alert-warning text-center">Пользователя не существует</div>
        <?php }?>
    </section>
</div>