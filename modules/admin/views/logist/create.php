<?php
use yii\helpers\Html;
use yii\bootstrap4\ActiveForm;
use yii\helpers\ArrayHelper;
use mihaildev\ckeditor\CKEditor;

$this->title = 'Add логистическую company';
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
        <?php $form = ActiveForm::begin(['options' => ['enctype' => 'multipart/form-data']]);?>
            <div class="box box-info color-palette-box">
                <div class="box-header">
                    Company basic data
                </div>
                <div class="box-body">
                    <div class="row">
                        <div class="col-sm-5">
                            <div class="row">
                                <div class="col-xs-6"> 
                                    <?=$form->field($model, 'imageFiles[]')->fileInput(['class'=>'file-upload-ajax'])->label('Logo'); ?>
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
                            <?=$form->field($model, 'name_ru')->textInput()->input('text')->label('Name: <span class="error_field">*</span>');?>
                            <?=$form->field($model, 'contact_user')->textInput()->input('text')->label('Contact person');?>
                            <?=$form->field($model, 'contact_phone')->textInput()->input('text', ['class'=>'form-control sms-phone'])->label('Contact person phone');?>
                        </div>
                    </div>
                </div>
            </div>
            <div class="box box-info color-palette-box">
                <div class="box-header">
                    Description company
                </div>
                <div class="box-body">
                    <?=$form->field($model, 'description_ru')->widget(CKEditor::className(),[
                        'editorOptions' => [
                            'preset' => 'advanced',
                            'inline' => false,
                            'height' => 200,
                            'filebrowserUploadUrl' => '/admin/images'
                        ]
                    ])->label('Description');?>
                </div>
            </div>
            <div class="box box-info color-palette-box">
                <div class="box-header">
                    Access
                </div>
                <div class="box-body">
                    <div class="row">
                        <div class="col-sm-6">
                            <?=$form->field($model, 'login')->textInput()->input('text', ['value'=>$model->user ? $model->user->login : ''])->label('Login: <span class="error_field">*</span>');?>
                        </div>
                        <div class="col-sm-6">
                            <?php $required = !Yii::$app->request->get('id') ? '<span class="error_field">*</span>' : '';?>
                            <?=$form->field($model, 'password')->textInput()->input('password')->label('Password: '.$required);?>
                        </div>
                    </div>
                </div>
                <div class="box-footer">
                    <div class="text-right">
                        <?=Html::submitButton('<i class="fa fa-check-square-o"></i> Save', ['class'=>'btn btn-primary'])?>
                    </div>
                </div>
            </div>
        <?php ActiveForm::end();?>
    </section>
</div>