<?php
use yii\helpers\Html;
use yii\bootstrap4\ActiveForm;

use mihaildev\ckeditor\CKEditor;

use app\widgets\admin_language_tab\AdminLanguageTab;

$this->title = 'Save question';
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
                    <?=AdminLanguageTab::widget();?>
                    <br/>
                    <div class="lang-block lang-block-ru">
                        <?=$form->field($model, 'question_ru')->widget(CKEditor::className(),[
                            'editorOptions' => [
                                'preset' => 'advanced',
                                'inline' => false,
                                'height' => 400,
                                'filebrowserUploadUrl' => '/admin/question/upload'
                            ]
                        ])->label('Question (RU) <span class="error_field">*</span>');?>
                        <?=$form->field($model, 'answer_ru')->widget(CKEditor::className(),[
                            'editorOptions' => [
                                'preset' => 'advanced',
                                'inline' => false,
                                'height' => 400,
                                'filebrowserUploadUrl' => '/admin/question/upload'
                            ]
                        ])->label('Answer (RU) <span class="error_field">*</span>');?>
                    </div>
                    <div class="lang-block lang-block-uz" style="display: none">
                        <?=$form->field($model, 'question_uz')->widget(CKEditor::className(),[
                            'editorOptions' => [
                                'preset' => 'advanced',
                                'inline' => false,
                                'height' => 400,
                                'filebrowserUploadUrl' => '/admin/question/upload'
                            ]
                        ])->label('Question (UZ)');?>
                        <?=$form->field($model, 'answer_uz')->widget(CKEditor::className(),[
                            'editorOptions' => [
                                'preset' => 'advanced',
                                'inline' => false,
                                'height' => 400,
                                'filebrowserUploadUrl' => '/admin/question/upload'
                            ]
                        ])->label('Answer (UZ)');?>
                    </div>
                    <div class="lang-block lang-block-en" style="display: none">
                        <?=$form->field($model, 'question_en')->widget(CKEditor::className(),[
                            'editorOptions' => [
                                'preset' => 'advanced',
                                'inline' => false,
                                'height' => 400,
                                'filebrowserUploadUrl' => '/admin/question/upload'
                            ]
                        ])->label('Question (EN)');?>
                        <?=$form->field($model, 'answer_en')->widget(CKEditor::className(),[
                            'editorOptions' => [
                                'preset' => 'advanced',
                                'inline' => false,
                                'height' => 400,
                                'filebrowserUploadUrl' => '/admin/question/upload'
                            ]
                        ])->label('Answer (EN)');?>
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