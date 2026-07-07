<?php
use yii\helpers\Html;
use yii\bootstrap4\ActiveForm;
use mihaildev\ckeditor\CKEditor;
use app\widgets\admin_language_tab\AdminLanguageTab;

/** @var yii\web\View $this */
/** @var app\models\stock\Stock $model */
/** @var yii\bootstrap4\ActiveForm $form */

$this->title = 'Add shop';
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="content-wrapper">
    <section class="content-header">
        <h1><?= $this->title; ?></h1>

        <ol class="breadcrumb">
            <li><a href="<?= Yii::$app->urlManager->createUrl(['/admin/']) ?>"><i class="fa fa-dashboard"></i> Home</a>
            </li>
            <li class="active"><?= $this->title; ?></li>
        </ol>
    </section>
    <section class="content">
        <?php $form = ActiveForm::begin(['options' => ['enctype' => 'multipart/form-data']]); ?>
        <?= $form->errorSummary($model, ['class' => 'alert alert-danger']) ?>
        <?= AdminLanguageTab::widget(); ?>
        <br />
        <div class="box box-info color-palette-box">
            <div class="box-header">
                Main info shop
            </div>
            <div class="box-body">
                <div class="row">
                    <div class="col-sm-5">
                        <div class="row">
                            <div class="col-xs-6">
                                <?= $form->field($model, 'imageFiles[]')->fileInput(['class' => 'file-upload-ajax'])->label('Main photo'); ?>
                            </div>
                            <div class="col-xs-6">
                                <img src="<?= $model->getPhoto('200x200') ?>" width="200" class="photo-admin-user" />
                                <?php if ($model->image) { ?>
                                    <br /><br />
                                    <a href="<?= Yii::$app->urlManager->createUrl(['/admin/default/remove-photo', 'id' => $model->image->id]) ?>"
                                        class="edit-user remove-object btn btn-sm btn-danger"><i class="fa fa-remove"></i>
                                        Delete photo</a>
                                <?php } ?>
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-7">
                        <?= $form->field($model, 'name_ru')->textInput()->input('text')->label('Name: <span class="error_field">*</span>'); ?>
                        <?= $form->field($model, 'contact_user')->textInput()->input('text')->label('Contact person:'); ?>
                        <?= $form->field($model, 'contact_phone')->textInput()->input('text')->label('Contact Phone:'); ?>
                        <div class="lang-block lang-block-ru">
                            <?= $form->field($model, 'description_ru')->widget(CKEditor::class, [
                                'editorOptions' => [
                                    'preset' => 'basic',
                                    'inline' => false,
                                    'height' => 200,
                                    'filebrowserUploadUrl' => '/admin/images'
                                ]
                            ])->label('Description (RU):'); ?>
                        </div>
                        <div class="lang-block lang-block-uz">
                            <?= $form->field($model, 'description_uz')->widget(CKEditor::class, [
                                'editorOptions' => [
                                    'preset' => 'basic',
                                    'inline' => false,
                                    'height' => 200,
                                    'filebrowserUploadUrl' => '/admin/images'
                                ]
                            ])->label('Description (UZ):'); ?>
                        </div>
                        <div class="lang-block lang-block-en">
                            <?= $form->field($model, 'description_en')->widget(CKEditor::class, [
                                'editorOptions' => [
                                    'preset' => 'basic',
                                    'inline' => false,
                                    'height' => 200,
                                    'filebrowserUploadUrl' => '/admin/images'
                                ]
                            ])->label('Description (EN):'); ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="box box-info color-palette-box">
            <div class="box-header">
                Info продавца
            </div>
            <div class="box-body">
                <div class="row">
                    <div class="col-sm-4">
                        <?= $form->field($model, 'name')->textInput()->input('text', ['value' => $model->user ? $model->user->name : ''])->label('Name: <span class="error_field">*</span>'); ?>
                    </div>
                    <div class="col-sm-4">
                        <?= $form->field($model, 'phone')->textInput()->input('text', ['class' => 'form-control sms-phone', 'value' => $model->user ? $model->user->phone : ''])->label('Phone: <span class="error_field">*</span>'); ?>
                    </div>
                    <div class="col-sm-4">
                        <?= $form->field($model, 'email')->textInput()->input('text', ['value' => $model->user ? $model->user->email : ''])->label('E-mail:'); ?>
                    </div>
                </div>
            </div>
        </div>
        <div class="box box-info color-palette-box">
            <div class="box-header">
                Seller details
            </div>
            <div class="box-body">
                <div class="row">
                    <div class="col-sm-6">
                        <?= $form->field($model, 'inn')->textInput()->input('text', ['value' => $model->shopSeller ? $model->shopSeller->inn : ''])->label('TIN:'); ?>
                    </div>
                    <div class="col-sm-6">
                        <?= $form->field($model, 'account')->textInput()->input('text', ['value' => $model->shopSeller ? $model->shopSeller->account : ''])->label('Checking account:'); ?>
                    </div>
                </div>
                <div class="row">
                    <div class="col-sm-6">
                        <?= $form->field($model, 'bank')->textInput()->input('text', ['value' => $model->shopSeller ? $model->shopSeller->bank : ''])->label('Bank:'); ?>
                    </div>
                    <div class="col-sm-6">
                        <?= $form->field($model, 'address_legal')->textInput()->input('text', ['value' => $model->shopSeller ? $model->shopSeller->address_legal : ''])->label('Legal addres:'); ?>
                    </div>
                </div>
                <div class="row">
                    <div class="col-sm-6">
                        <?= $form->field($model, 'oked')->textInput()->input('text', ['value' => $model->shopSeller ? $model->shopSeller->oked : ''])->label('OKED:'); ?>
                    </div>
                    <div class="col-sm-6">
                        <?= $form->field($model, 'okohx')->textInput()->input('text', ['value' => $model->shopSeller ? $model->shopSeller->okohx : ''])->label('OKOHX:'); ?>
                    </div>
                </div>
                <div class="row">
                    <div class="col-sm-6">
                        <?= $form->field($model, 'mfo')->textInput()->input('text', ['value' => $model->shopSeller ? $model->shopSeller->mfo : ''])->label('MFO:'); ?>
                    </div>
                    <div class="col-sm-6">
                        <?= $form->field($model, 'organization')->textInput()->input('text', ['value' => $model->shopSeller ? $model->shopSeller->organization : ''])->label('Name organization:'); ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="box box-info color-palette-box">
            <div class="box-header">
                Access
            </div>
            <div class="box-body">
                <div class="row">
                    <div class="col-sm-6">
                        <?= $form->field($model, 'login')->textInput()->input('text', ['value' => $model->user ? $model->user->login : ''])->label('Login: <span class="error_field">*</span>'); ?>
                    </div>
                    <div class="col-sm-6">
                        <?php $required = !Yii::$app->request->get('id') ? '<span class="error_field">*</span>' : ''; ?>
                        <?= $form->field($model, 'password')->textInput()->input('password')->label('Password: ' . $required); ?>
                    </div>
                </div>
            </div>
            <div class="box-footer">
                <div class="text-right">
                    <?= Html::submitButton('<i class="fa fa-check-square-o"></i> Save', ['class' => 'btn btn-primary']) ?>
                </div>
            </div>
        </div>

        <?php ActiveForm::end(); ?>
    </section>
</div>

<script src="https://api-maps.yandex.ru/2.1/?lang=ru_RU" type="text/javascript"></script>