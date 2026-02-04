<?php
use yii\helpers\Html;
use yii\bootstrap4\ActiveForm;

$this->title = 'Upload logo';
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
        <?php if (Yii::$app->session->hasFlash('logo_saved')) {?>
            <div class="callout callout-success text-center">
                <?=Yii::$app->session->getFlash('logo_saved');?>
            </div>
        <?php }?>
        <div class="box box-info color-palette-box">
            <?php $form = ActiveForm::begin(['options' => ['enctype' => 'multipart/form-data'], 'id' => 'form-profile']);?>
                <div class="box-body">
                    <div class="row">
                        <div class="col-sm-6">
                            <?=$form->field($model, 'imageFiles[]')->fileInput(['multiple' => true, 'accept' => 'image/*', 'class'=>'file-upload-ajax'])->label(false)?>
                        </div>
                        <div class="col-sm-6">
                            <?php if ($model->image) {?>
                                <img src="<?=$model->getPhoto();?>" width="100%"/>
                                <br/>
                                <a href="<?=Yii::$app->urlManager->createUrl(['/admin/default/remove-photo', 'id'=>$model->image->id])?>"><i class="glyphicon glyphicon-trash" class="remove-object"></i> Delete photo</a>
                                <br/><br/>
                            <?php }?>
                        </div>
                    </div>  
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