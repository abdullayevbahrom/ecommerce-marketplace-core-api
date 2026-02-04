<?php
use yii\helpers\Html;
use yii\bootstrap4\ActiveForm;

use mihaildev\ckeditor\CKEditor;

use app\widgets\admin_language_tab\AdminLanguageTab;

$this->title = 'Maintain your advantage';
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
        <?php $form = ActiveForm::begin(['options' => ['enctype' => 'multipart/form-data'], 'id' => 'form-profile']);?>
            <div class="box box-info color-palette-box">
                <div class="box-body">
                    <div class="row">
                        <div class="col-sm-5">
                            <div class="row">
                                <div class="col-xs-6"> 
                                    <?=$form->field($model, 'imageFiles[]')->fileInput(['class'=>'file-upload-ajax'])->label('Main photo'); ?>
                                </div>
                                <div class="col-xs-6">
                                    <img src="<?=$model->getPhoto('200x200')?>" width="200" class="photo-admin-user"/>
                                    <?php if ($model->image) {?>
                                        <br/><br/>
                                        <a href="<?=Yii::$app->urlManager->createUrl(['/admin/default/remove-photo', 'id'=>$model->image->id])?>" class="edit-user remove-object btn btn-sm btn-danger"><i class="fa fa-remove"></i> Delete photo</a>
                                    <?php }?>
                                </div>
                            </div>
                        </div>
                        <div class="col-sm-7">
                            <?=AdminLanguageTab::widget();?>
                            <br/>
                            <div class="lang-block lang-block-ru">
                                <?=$form->field($model, 'name_ru')->textInput()->input('text')->label('Name (RU): <span class="error_field">*</span>');?>
                                <?=$form->field($model, 'description_ru')->widget(CKEditor::className(),[
                                    'editorOptions' => [
                                        'preset' => 'advanced',
                                        'inline' => false,
                                        'height' => 400,
                                        'filebrowserUploadUrl' => '/admin/advantage/upload'
                                    ]
                                ])->label('Description  (RU) <span class="error_field">*</span>');?>
                            </div>
                            <div class="lang-block lang-block-uz" style="display: none">
                                <?=$form->field($model, 'name_uz')->textInput()->input('text')->label('Name (UZ):');?>
                                <?=$form->field($model, 'description_uz')->widget(CKEditor::className(),[
                                    'editorOptions' => [
                                        'preset' => 'advanced',
                                        'inline' => false,
                                        'height' => 400,
                                        'filebrowserUploadUrl' => '/admin/advantage/upload'
                                    ]
                                ])->label('Description (UZ)');?>
                            </div>
                            <div class="lang-block lang-block-en" style="display: none">
                                <?=$form->field($model, 'name_en')->textInput()->input('text')->label('Name (EN):');?>
                                <?=$form->field($model, 'description_en')->widget(CKEditor::className(),[
                                    'editorOptions' => [
                                        'preset' => 'advanced',
                                        'inline' => false,
                                        'height' => 400,
                                        'filebrowserUploadUrl' => '/admin/advantage/upload'
                                    ]
                                ])->label('Description (EN)');?>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="box-footer">
                    <div class="text-right">
                        <?=Html::submitButton('<i class="glyphicon glyphicon-ok"></i> Save', ['class'=>'btn btn-primary']);?>
                    </div>
                </div>
            </div>
        <?php ActiveForm::end();?>
    </section>
</div>  