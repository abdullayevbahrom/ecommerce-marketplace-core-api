<?php
$this->title = 'Сменить пароль';

use yii\helpers\Html;
use yii\bootstrap4\ActiveForm;
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
        <?php if (Yii::$app->session->hasFlash('password_changed')) {?>
            <div class="callout callout-success text-center">
                <?=Yii::$app->session->getFlash('password_changed');?>
            </div>
        <?php }?>
        <div class="box box-info color-palette-box">
            <div class="box-body">
                <?php $form = ActiveForm::begin([
                    'id' => 'login-form',
                    'options' => ['class' => 'form-horizontal'],
                    'fieldConfig' => [
                        'template' => "{label}\n<div class=\"col-sm-6\">{input}{error}</div>",
                        'labelOptions' => ['class' => 'col-sm-3 control-label'],
                        'inputOptions' => ['class' => 'form-control'],
                        'errorOptions' => ['class' => 'help-block text-danger'],
                    ],]); ?>
                    <?=$form->field($model, 'password')->passwordInput()->input('password', ['value'=>''])->label('Новый пароль') ?>
                    
                    <div class="row">
                        <div class="col-sm-6 col-sm-offset-3">
                            <?php echo Html::submitButton('Сохранить', ['class'=>'btn btn-primary', 'style'=>'width:100%'])?>
                        </div>
                    </div>
                <?php ActiveForm::end();?>
			</div>
		</div>
	</section>
</div>