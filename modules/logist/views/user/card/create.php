<?php
use yii\helpers\Html;
use yii\bootstrap4\ActiveForm;

use app\models\user\User; 

use app\widgets\admin_user_menu\AdminUserMenu;
use app\widgets\admin_user_menu\AdminUserButton;

$this->title = 'Сохранить карту';
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
        <div class="row">
            <div class="col-sm-3">
                <?=AdminUserMenu::widget();?>
            </div>
            <div class="col-sm-9">
                <?php $form = ActiveForm::begin(); ?>
                    <div class="box box-info color-palette-box">
                        <div class="box-header with-border">
                            <div class="box-title pull-right">
                                <?=AdminUserButton::widget();?>
                            </div>
                        </div>
                        <div class="box-body">
                            <div class="row">
                                <div class="col-sm-6">
                                    <?=$form->field($model, 'card_type_id')->dropDownList(
                                        $card_types,
                                        [
                                            'class'=>'form-control select2',
                                            'prompt'=>'Выбрать тип карты'
                                        ],
                                    )->label('Тип карты <span class="error_field">*</span>');?>
                                </div>
                                <div class="col-sm-6">
                                    <?=$form->field($model, 'card_number')->textInput()->input('text', ['class'=>'form-control card-number', 'value'=>''])->label('Номер карты <span class="error_field">*</span>');?>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-sm-6">
                                    <?=$form->field($model, 'card_expire')->textInput()->input('text', ['class'=>'form-control card-expire'])->label('Срок карты <span class="error_field">*</span>');?>
                                </div>
                                <div class="col-sm-6">
                                    <?=$form->field($model, 'card_phone_number')->textInput()->input('text', ['class'=>'form-control sms-phone'])->label('Номер телефона <span class="error_field">*</span>');?>
                                </div>
                            </div>
                        </div>
                        <div class="box-footer">
                            <div class="text-right">
                                <?=Html::submitButton('<i class="fa fa-check-square-o"></i> Сохранить', ['class'=>'btn btn-primary']);?>
                            </div>
                        </div>
                    </div>
                <?php ActiveForm::end();?>
            </div>
        </div>
    </section>
</div>