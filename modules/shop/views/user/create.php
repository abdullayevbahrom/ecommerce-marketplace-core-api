<?php
use yii\helpers\Html;
use yii\bootstrap4\ActiveForm;

use app\models\user\User;

$this->title = 'Сохранить пользователя';
$this->params['breadcrumbs'][] = ['label' => 'Users', 'url' => ['index']];
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
        <?php $form = ActiveForm::begin(); ?>
            <div class="box box-info color-palette-box">
                <div class="box-header">Основные данные</div>
                <div class="box-body">
                    <div class="row">
                        <div class="col-sm-5">
                            <div class="row">
                                <div class="col-xs-6"> 
                                    <?=$form->field($model, 'imageFiles[]')->fileInput(['class'=>'file-upload-ajax'])->label('Фото'); ?>
                                </div>
                                <div class="col-xs-6">
                                    <img src="<?=$model->getPhoto('200x200/')?>" width="200" class="photo-admin-user"/>
                                    <?php if ($model->image) {?>
                                        <br/><br/>
                                        <a href="<?=Yii::$app->urlManager->createUrl(['/shop/default/remove-photo', 'id'=>$model->image->id])?>" class="edit-user remove-object btn btn-sm btn-danger"><i class="fa fa-remove"></i> Удалить фото</a>
                                    <?php }?>
                                </div>
                            </div>
                        </div>
                        <div class="col-sm-7">
                            <div class="row">
                                <div class="col-sm-6">
                                    <?=$form->field($model, 'name')->textInput()->input('text')->label('Имя');?>
                                </div>
                                <div class="col-sm-6">
                                    <?=$form->field($model, 'phone')->textInput()->input('text', ['class'=>'form-control sms-phone'])->label('Телефон');?>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-sm-6">
                                    <?=$form->field($model, 'email')->textInput()->input('text')->label('E-mail');?>
                                </div>
                                <div class="col-sm-6">
                                    <?=$form->field($model, 'password')->textInput()->input('password', ['value'=>''])->label('Пароль');?>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-sm-6">
                                    <?=$form->field($model, 'birthday')->textInput()->input('text', ['class'=>'form-control datepicker'])->label('Дата рождения');?>
                                </div>
                            </div>
                            <?= $form->field($model, 'gender')->radioList(['1' => 'Мужской', '2' => 'Женский'])->label('Пол'); ?>
                        </div>
                    </div>
                </div>
            </div>
            <div class="box box-info color-palette-box">
                <div class="box-header">Адреса</div>
                <div class="box-body">
                    <div id="item-form">
                        <?php if ($model->addresses) {?>
                            <?php foreach ($model->addresses as $v) {?>
                                <div class="item-block">
                                    <div class="row">
                                        <div class="col-sm-10">
                                            <?=$form->field($model, 'address[]')->textInput()->input('text', ['value'=>$v->address, 'placeholder'=>'Введите адрес'])->label(false);?>
                                        </div>
                                        <div class="col-sm-2">
                                            <a href="javascript:;" class="btn btn-danger remove-block" title="Удалить"><i class="fa fa-remove"></i> </a>
                                        </div>
                                    </div>
                                </div>
                            <?php }?>
                        <?php } else {?>
                            <div class="item-block">
                                <div class="row">
                                    <div class="col-sm-10">
                                        <?=$form->field($model, 'address[]')->textInput()->input('text', ['placeholder'=>'Введите адрес'])->label(false);?>
                                    </div>
                                    <div class="col-sm-2">
                                        <a href="javascript:;" class="btn btn-danger remove-block"><i class="fa fa-remove"></i> </a>
                                    </div>
                                </div>
                            </div>
                        <?php }?>
                    </div>

                    <div class="text-center">
                        <a href="javascript:;" class="add-variant-item btn btn-success btn-sm">
                            <i class="glyphicon glyphicon-plus"></i> Добавить еще
                        </a>
                    </div>
                </div>
                <div class="box-footer">
                    <div class="text-right">
                        <?=Html::submitButton('<i class="fa fa-check-square-o"></i> Сохранить', ['class'=>'btn btn-primary']);?>
                    </div>
                </div>
            </div>
        <?php ActiveForm::end()?>
    </section>
</div>