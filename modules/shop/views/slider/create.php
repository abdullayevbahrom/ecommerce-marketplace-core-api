<?php
use yii\helpers\Html;
use yii\bootstrap4\ActiveForm;

use mihaildev\ckeditor\CKEditor;

use app\widgets\admin_language_tab\AdminLanguageTab;

$this->title = 'Добавить слайд';
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
        <div class="box box-info color-palette-box">
            <?php $form = ActiveForm::begin(['options' => ['enctype' => 'multipart/form-data'], 'id' => 'form-profile']);?>
                <div class="box-body">
                    <?=AdminLanguageTab::widget();?>
                    <br/>
                    <div class="lang-block lang-block-ru">
                        <?=$form->field($model, 'name_ru', ['template'=>'{label}<div class="input-group"><span class="input-group-addon"><i class="fa fa-pencil"></i></span>{input}</div>{error}'])->textInput()->input('text')->label('Название (RU)');?>
                        <?=$form->field($model, 'content_ru')->widget(CKEditor::className(),[
                            'editorOptions' => [
                                'preset' => 'advanced',
                                'inline' => false
                            ],
                        ])->label('Описание (RU)');?>
                    </div>
                    <div class="lang-block lang-block-uz">
                        <?=$form->field($model, 'name_uz', ['template'=>'{label}<div class="input-group"><span class="input-group-addon"><i class="fa fa-pencil"></i></span>{input}</div>{error}'])->textInput()->input('text')->label('Название (UZ)');?>
                        <?=$form->field($model, 'content_uz')->widget(CKEditor::className(),[
                            'editorOptions' => [
                                'preset' => 'advanced',
                                'inline' => false
                            ],
                        ])->label('Описание (UZ)');?>
                    </div>
                    <div class="lang-block lang-block-en">
                        <?=$form->field($model, 'name_en', ['template'=>'{label}<div class="input-group"><span class="input-group-addon"><i class="fa fa-pencil"></i></span>{input}</div>{error}'])->textInput()->input('text')->label('Название (EN)');?>
                        <?=$form->field($model, 'content_en')->widget(CKEditor::className(),[
                            'editorOptions' => [
                                'preset' => 'advanced',
                                'inline' => false
                            ],
                        ])->label('Описание (EN)');?>
                    </div>
                    <div class="row">
                        <div class="col-sm-6">
                            <?=$form->field($model, 'link', ['template'=>'{label}<div class="input-group"><span class="input-group-addon"><i class="fa fa-pencil"></i></span>{input}</div>{error}'])->textInput()->input('text')->label('Ссылка');?>
                        </div>
                        <div class="col-sm-6">
                            <?=$form->field($model, 'type')->dropDownList(
                                ['site' => 'Сайт', 'mobile' => 'Мобильный'],
                                [
                                    'class'=>'form-control select2',
                                    'prompt'=>'Выбрать тип'
                                ],
                            )->label('Тип');?>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-sm-6">
                            <?=$form->field($model, 'imageFiles[]')->fileInput(['multiple' => true, 'accept' => 'image/*', 'class'=>'file-upload-ajax'])->label(false)?>
                        </div>
                        <div class="col-sm-6">
                            <?php if ($model->image) {?>
                                <img src="<?=$model->getPhoto();?>" width="200"/>
                                <br/><br/>
                                <a href="<?=Yii::$app->urlManager->createUrl(['/shop/default/remove-photo', 'id'=>$model->image->id])?>" class="remove-object btn btn-sm btn-danger"><i class="fa fa-remove"></i> Удалить фото</a>
                                <br/><br/>
                            <?php }?>
                        </div>
                    </div>    
                </div>
                <div class="box-footer">
                    <div class="text-right">
                        <?=Html::submitButton('<i class="fa fa-check-square-o"></i> Сохранить', ['class'=>'btn btn-primary']);?>
                    </div>
                </div>
            <?php ActiveForm::end();?>
        </div>
    </section>
</div>  