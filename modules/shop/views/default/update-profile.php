<?php
use yii\helpers\Html;
use yii\bootstrap4\ActiveForm;

use app\models\user\User;

$this->title = 'Редактирование данных';
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
                                        <br/>
                                        <a href="<?=Yii::$app->urlManager->createUrl(['/shop/default/remove-photo', 'id'=>$model->image->id])?>" class="edit-user remove-object"><i class="fa fa-times"></i> Удалить фото</a>
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
                                    <?=$form->field($model, 'login')->textInput()->input('text')->label('Логин');?>
                                </div>
                            </div>
                        </div>
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