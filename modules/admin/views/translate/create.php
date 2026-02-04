<?php
use yii\helpers\Html;
use yii\bootstrap4\ActiveForm;

$this->title = 'Add translate';
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
        <?php if (Yii::$app->session->hasFlash('moderator_removed')) {?>
            <div class="alert alert-success text-center"><?=Yii::$app->session->getFlash('moderator_removed');?></div>
        <?php }?>
        <div class="box box-info color-palette-box">            
            <?php $form = ActiveForm::begin(['options' => ['enctype' => 'multipart/form-data'], 'id' => 'form-profile']);?>
                <div class="box-body">
                    <div id="block-form">
                        <div class="block-block">
                            <div class="row">
                                <div class="col-sm-4">
                                    <?=$form->field($model, 'names_ru[]')->textInput()->input('text')->label('<img src="/assets_files/images/languages/ru.png" width="25"> Russian');?>
                                </div>
                                <div class="col-sm-4">
                                    <?=$form->field($model, 'names_uz[]')->textInput()->input('text')->label('<img src="/assets_files/images/languages/uz.png" width="25"> Uzbek');?>
                                </div>
                                <div class="col-sm-4">
                                    <?=$form->field($model, 'names_en[]')->textInput()->input('text')->label('<img src="/assets_files/images/languages/en.png" width="25"> English');?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="text-center">
                        <a href="javascript:;" class="add-block btn btn-success btn-sm">
                            <i class="fa fa-plus"></i> Add more
                        </a>
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