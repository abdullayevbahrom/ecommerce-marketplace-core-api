<?php
use yii\helpers\Html;
use yii\bootstrap4\ActiveForm;

use app\widgets\admin_language_tab\AdminLanguageTab;

$this->title = 'Add color';
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
        <?php if (Yii::$app->session->hasFlash('color_removed')) {?>
            <div class="callout callout-success text-center">
                <?=Yii::$app->session->getFlash('color_removed');?>
            </div>
        <?php }?>
        <?php $form = ActiveForm::begin(['options' => ['enctype' => 'multipart/form-data']]);?>
            <div class="box box-info color-palette-box">
                <div class="box-header with-border">
                    Main info
                </div>
            
                <div class="box-body">
                    
                    <?=AdminLanguageTab::widget();?>
                    <br/>
                    <div class="row">
                        <div class="col-sm-6">
                            <div class="lang-block lang-block-ru">
                                <?=$form->field($model, 'name_ru', ['template'=>'{label}<div class="input-group"><span class="input-group-addon"><i class="fa fa-pencil"></i></span>{input}</div>{error}'])->textInput()->input('text', ['placeholder'=>'Enter color name in ru', 'class'=>'form-control'])->label('Name (RU) <span class="error_field">*</span>');?>
                            </div>
                            <div class="lang-block lang-block-en">
                                <?=$form->field($model, 'name_en', ['template'=>'{label}<div class="input-group"><span class="input-group-addon"><i class="fa fa-pencil"></i></span>{input}</div>{error}'])->textInput()->input('text', ['placeholder'=>'Enter color name in eng', 'class'=>'form-control'])->label('Name (EN)');?>
                            </div>
                            <div class="lang-block lang-block-uz">
                                <?=$form->field($model, 'name_uz', ['template'=>'{label}<div class="input-group"><span class="input-group-addon"><i class="fa fa-pencil"></i></span>{input}</div>{error}'])->textInput()->input('text', ['placeholder'=>'Enter color name in uz', 'class'=>'form-control'])->label('Name (UZ)');?>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <?=$form->field($model, 'color')->textInput()->input('color')->label('Color');?>
                        </div>
                    </div>
                </div>
                <div class="box-footer">
                    <div class="text-right">
                        <?=Html::submitButton('<i class="fa fa-check-square-o"></i> Save', ['class'=>'btn btn-primary']);?>
                    </div>
                </div>
            </div>
        <?php ActiveForm::end();?>
    </section>
</div>  