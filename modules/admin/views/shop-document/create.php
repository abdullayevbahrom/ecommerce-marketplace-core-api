<?php
use yii\helpers\Html;
use yii\bootstrap4\ActiveForm;

use mihaildev\ckeditor\CKEditor;

use app\widgets\admin_language_tab\AdminLanguageTab;
use app\models\File;

$this->title = 'Add document';
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
        <div class="box box-info color-palette-box">
            <?php $form = ActiveForm::begin(['options' => ['enctype' => 'multipart/form-data'], 'id' => 'form-profile']);?>
                <div class="box-body">
                    <?=AdminLanguageTab::widget();?>
                    <br/>
                    <div class="lang-block lang-block-ru">
                        <?=$form->field($model, 'name_ru', ['template'=>'{label}<div class="input-group"><span class="input-group-addon"><i class="fa fa-pencil"></i></span>{input}</div>{error}'])->textInput()->input('text')->label('Name (RU) <span class="error_field">*</span>');?>
                        <?=$form->field($model, 'content_ru')->widget(CKEditor::className(),[
                            'editorOptions' => [
                                'preset' => 'advanced',
                                'inline' => false
                            ],
                        ])->label('Description (RU)');?>
                    </div>
                    <div class="lang-block lang-block-uz">
                        <?=$form->field($model, 'name_uz', ['template'=>'{label}<div class="input-group"><span class="input-group-addon"><i class="fa fa-pencil"></i></span>{input}</div>{error}'])->textInput()->input('text')->label('Name (UZ)');?>
                        <?=$form->field($model, 'content_uz')->widget(CKEditor::className(),[
                            'editorOptions' => [
                                'preset' => 'advanced',
                                'inline' => false
                            ],
                        ])->label('Description (UZ)');?>
                    </div>
                    <div class="lang-block lang-block-en">
                        <?=$form->field($model, 'name_en', ['template'=>'{label}<div class="input-group"><span class="input-group-addon"><i class="fa fa-pencil"></i></span>{input}</div>{error}'])->textInput()->input('text')->label('Name (EN)');?>
                        <?=$form->field($model, 'content_en')->widget(CKEditor::className(),[
                            'editorOptions' => [
                                'preset' => 'advanced',
                                'inline' => false
                            ],
                        ])->label('Description (EN)');?>
                    </div>
                    <?=$form->field($model, 'shop_id')->dropDownList(
                        $shops,
                        [
                            'class'=>'form-control select2',
                            'prompt'=>'Select shop'
                        ],
                    )->label('Shops:');?>
                    <div class="row">
                        <div class="col-sm-6">
                            <?=$form->field($model, 'files[]')->fileInput(['multiple' => true, 'accept' => 'file/*', 'class'=>'file-upload-ajax-file'])->label(false)?>
                        </div>
                        <div class="col-sm-6">
                            <?php if ($model->file) {?>
                                <img src="<?=File::FILE_DEFAULT;?>" width="200"/>
                                <br/><br/>
                                <a href="<?=Yii::$app->urlManager->createUrl(['/admin/default/remove-file', 'id'=>$model->file->id])?>" class="remove-object btn btn-sm btn-danger"><i class="fa fa-remove"></i> Delete file</a>
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