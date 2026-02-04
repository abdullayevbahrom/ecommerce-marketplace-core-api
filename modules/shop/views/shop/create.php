<?php
use yii\helpers\Html;
use yii\bootstrap4\ActiveForm;
use yii\helpers\ArrayHelper;

use mihaildev\ckeditor\CKEditor;
use app\widgets\admin_language_tab\AdminLanguageTab;

$this->title = 'Добавить магазин';
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
        <?php $form = ActiveForm::begin(['options' => ['enctype' => 'multipart/form-data']]);?>
            <?=AdminLanguageTab::widget();?>
            <br/>
            <div class="box box-info color-palette-box">
                <div class="box-header">
                    Основные данные магазина
                </div>
                <div class="box-body">
                    <div class="row">
                        <div class="col-sm-5">
                            <div class="row">
                                <div class="col-xs-6">
                                    <?=$form->field($model, 'imageFiles[]')->fileInput(['class'=>'file-upload-ajax'])->label('Главное фото'); ?>
                                </div>
                                <div class="col-xs-6">
                                    <img src="<?=$model->getPhoto('200x200')?>" width="200" class="photo-admin-user"/>
                                    <?php if ($model->image) {?>
                                        <br/><br/>
                                        <a href="<?=Yii::$app->urlManager->createUrl(['/shop/default/remove-photo', 'id'=>$model->image->id])?>" class="edit-user remove-object btn btn-sm btn-danger"><i class="fa fa-remove"></i> Удалить фото</a>
                                    <?php }?>
                                </div>
                            </div>
                        </div>
                        <div class="col-sm-7">
                            <?=$form->field($model, 'name_ru')->textInput()->input('text')->label('Название: <span class="error_field">*</span>');?>
                            <?=$form->field($model, 'contact_user')->textInput()->input('text')->label('Контактное лицо:');?>
                            <?=$form->field($model, 'contact_phone')->textInput()->input('text')->label('Контактный номер:');?>
                            <div class="lang-block lang-block-ru">
                                <?=$form->field($model, 'description_ru')->widget(CKEditor::className(),[
                                    'editorOptions' => [
                                        'preset' => 'basic',
                                        'inline' => false,
                                        'height' => 200,
                                        'filebrowserUploadUrl' => '/shop/images'
                                    ]
                                ])->label('Описание (RU):');?>
                            </div>
                            <div class="lang-block lang-block-uz">
                                <?=$form->field($model, 'description_uz')->widget(CKEditor::className(),[
                                    'editorOptions' => [
                                        'preset' => 'basic',
                                        'inline' => false,
                                        'height' => 200,
                                        'filebrowserUploadUrl' => '/shop/images'
                                    ]
                                ])->label('Описание (UZ):');?>
                            </div>
                            <div class="lang-block lang-block-en">
                                <?=$form->field($model, 'description_en')->widget(CKEditor::className(),[
                                    'editorOptions' => [
                                        'preset' => 'basic',
                                        'inline' => false,
                                        'height' => 200,
                                        'filebrowserUploadUrl' => '/shop/images'
                                    ]
                                ])->label('Описание (EN):');?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="box box-info color-palette-box">
                <div class="box-header">
                    Данные продавца
                </div>
                <div class="box-body">
                    <div class="row">
                        <div class="col-sm-4">
                            <?=$form->field($model, 'name')->textInput()->input('text', ['value'=>$model->user ? $model->user->name : ''])->label('Имя: <span class="error_field">*</span>');?>
                        </div>
                        <div class="col-sm-4">
                            <?=$form->field($model, 'phone')->textInput()->input('text', ['class'=>'form-control sms-phone', 'value'=>$model->user ? $model->user->phone : ''])->label('Телефон: <span class="error_field">*</span>');?>
                        </div>
                        <div class="col-sm-4">
                            <?=$form->field($model, 'email')->textInput()->input('text', ['value'=>$model->user ? $model->user->email : ''])->label('E-mail:');?>
                        </div>
                    </div>
                </div>
            </div>
            <div class="box box-info color-palette-box">
                <div class="box-header">
                    Реквезиты продавца
                </div>
                <div class="box-body">
                    <div class="row">
                        <div class="col-sm-6">
                            <?=$form->field($model, 'inn')->textInput()->input('text', ['value'=>$model->shopSeller ? $model->shopSeller->inn : ''])->label('ИНН:');?>
                        </div>
                        <div class="col-sm-6">
                            <?=$form->field($model, 'account')->textInput()->input('text', ['value'=>$model->shopSeller ? $model->shopSeller->account : ''])->label('Расчетный счет:');?>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-sm-6">
                            <?=$form->field($model, 'bank')->textInput()->input('text', ['value'=>$model->shopSeller ? $model->shopSeller->bank : ''])->label('Банк:');?>
                        </div>
                        <div class="col-sm-6">
                            <?=$form->field($model, 'address_legal')->textInput()->input('text', ['value'=>$model->shopSeller ? $model->shopSeller->address_legal : ''])->label('Юридический адрес:');?>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-sm-6">
                            <?=$form->field($model, 'oked')->textInput()->input('text', ['value'=>$model->shopSeller ? $model->shopSeller->oked : ''])->label('ОКЕД:');?>
                        </div>
                        <div class="col-sm-6">
                            <?=$form->field($model, 'okohx')->textInput()->input('text', ['value'=>$model->shopSeller ? $model->shopSeller->okohx : ''])->label('OKOHX:');?>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-sm-6">
                            <?=$form->field($model, 'mfo')->textInput()->input('text', ['value'=>$model->shopSeller ? $model->shopSeller->mfo : ''])->label('MFO:');?>
                        </div>
                    </div>
                </div>
            </div>

            <div class="box box-info color-palette-box">
                <div class="box-header">
                    Доступ
                </div>
                <div class="box-body">
                    <div class="row">
                        <div class="col-sm-6">
                            <?=$form->field($model, 'login')->textInput()->input('text', ['value'=>$model->user ? $model->user->login : ''])->label('Логин: <span class="error_field">*</span>');?>
                        </div>
                        <div class="col-sm-6">
                            <?php $required = !Yii::$app->request->get('id') ? '<span class="error_field">*</span>' : '';?>
                            <?=$form->field($model, 'password')->textInput()->input('password')->label('Пароль: '.$required);?>
                        </div>
                    </div>
                </div>
                <div class="box-footer">
                    <div class="text-right">
                        <?=Html::submitButton('<i class="fa fa-check-square-o"></i> Сохранить', ['class'=>'btn btn-primary'])?>
                    </div>
                </div>
            </div>
            
        <?php ActiveForm::end();?>
    </section>
</div>

<script src="https://api-maps.yandex.ru/2.1/?lang=ru_RU" type="text/javascript"></script>
