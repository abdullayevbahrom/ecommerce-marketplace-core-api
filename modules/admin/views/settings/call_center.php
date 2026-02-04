<?php
use yii\helpers\Html;
use yii\bootstrap4\ActiveForm;

$this->title = 'Phone (Call Center)';
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="content-wrapper">
    <section class="content-header">
        <h1><?=$this->title;?></h1>
        
        <ol class="breadcrumb">
            <li><a href="<?=Yii::$app->urlManager->createUrl(['/admin/'])?>"><i class="fa fa-dashboard"></i> Home</a></li>
            <li class="active"><?=$this->title;?></li>
        </ol>
    </section>
    <section class="content">
        <?php if (Yii::$app->session->hasFlash('call_center_saved')) {?>
            <div class="callout callout-success text-center">
                <?=Yii::$app->session->getFlash('call_center_saved');?>
            </div>
        <?php }?>
        <div class="box box-info color-palette-box">
            <?php $form = ActiveForm::begin(['id' => 'form-profile']);?>
                <div class="box-body">
                    <?=$form->field($model, 'content')->textInput()->input('text', ['value'=>$model->content, 'placeholder'=>'Enter phone number'])->label(false);?>
                </div>
                <div class="box-footer">
                    <div class="text-right">
                        <?=Html::submitButton('<i class="fa fa-check-square-o"></i> Save', ['class'=>'btn btn-primary']);?>
                    </div>
                </div>
            <?php ActiveForm::end();?>
        </div>
    </section>
</div>  