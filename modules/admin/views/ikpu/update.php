<?php

use yii\helpers\Html;
use yii\bootstrap4\ActiveForm;
use app\widgets\admin_language_tab\AdminLanguageTab;

$this->title = 'Редактировать ИКПУ: ' . $model->code;
$this->params['breadcrumbs'][] = ['label' => 'ИКПУ', 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $model->code, 'url' => ['view', 'id' => $model->id]];
$this->params['breadcrumbs'][] = 'Редактировать';
?>

<div class="content-wrapper">
    <section class="content-header">
        <h1><?= Html::encode($this->title) ?></h1>
        
        <ol class="breadcrumb">
            <li><a href="<?=Yii::$app->urlManager->createUrl(['/admin/'])?>"><i class="fa fa-dashboard"></i> Home</a></li>
            <li><a href="<?=Yii::$app->urlManager->createUrl(['/admin/ikpu'])?>">ИКПУ</a></li>
            <li><a href="<?=Yii::$app->urlManager->createUrl(['/admin/ikpu/view', 'id' => $model->id])?>"><?= Html::encode($model->code) ?></a></li>
            <li class="active">Редактировать</li>
        </ol>
    </section>
    
    <section class="content">
        <?= AdminLanguageTab::widget(); ?>
        <br/>
        
        <?php $form = ActiveForm::begin(['options' => ['enctype' => 'multipart/form-data']]); ?>
        
        <div class="box box-info color-palette-box">
            <div class="box-header with-border">
                <h3 class="box-title">Основная информация</h3>
            </div>
            <div class="box-body">
                <div class="row">
                    <div class="col-md-6">
                        <?= $form->field($model, 'code')->textInput([
                            'maxlength' => true,
                            'placeholder' => 'Например: 01902001001043001',
                            'pattern' => '[0-9]{17}',
                            'title' => 'Код ИКПУ должен содержать ровно 17 цифр'
                        ])->label('Код ИКПУ <span class="error_field">*</span>') ?>
                        
                        <?= $form->field($model, 'parent_code')->dropDownList($parentOptions, [
                            'class' => 'form-control select2',
                            'prompt' => 'Выберите родительский элемент'
                        ])->label('Родительский элемент') ?>
                        

                        
                        <?= $form->field($model, 'status')->dropDownList(\app\models\Ikpu::getStatusOptions(), [
                            'class' => 'form-control select2'
                        ])->label('Статус') ?>
                    </div>
                    <div class="col-md-6">
                        <div class="callout callout-warning">
                            <h4><i class="fa fa-warning"></i> Внимание при изменении</h4>
                            <p><strong>Изменение кода ИКПУ</strong> может повлиять на связанные товары и документы DIDOX.</p>
                            <?php if ($model->getChildren()->count() > 0) { ?>
                                <p><strong>Дочерние элементы:</strong> <?= $model->getChildren()->count() ?></p>
                            <?php } ?>
                            <?php if ($model->getProducts()->count() > 0) { ?>
                                <p><strong>Связанные товары:</strong> <?= $model->getProducts()->count() ?></p>
                            <?php } ?>
                        </div>
                        
                        <div class="callout callout-info">
                            <h4><i class="fa fa-info"></i> Текущий путь</h4>
                            <p><?= Html::encode($model->getFullPath()) ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="box box-success color-palette-box">
            <div class="box-header with-border">
                <h3 class="box-title">Названия на разных языках</h3>
            </div>
            <div class="box-body">
                <div class="row">
                    <div class="col-md-12">
                        <?= $form->field($model, 'name_ru')->textInput([
                            'maxlength' => true,
                            'placeholder' => 'Название на русском языке'
                        ])->label('Название (Русский) <span class="error_field">*</span>') ?>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <?= $form->field($model, 'name_uz')->textInput([
                            'maxlength' => true,
                            'placeholder' => 'Название на узбекском языке'
                        ])->label('Название (Узбекский)') ?>
                    </div>
                    <div class="col-md-6">
                        <?= $form->field($model, 'name_en')->textInput([
                            'maxlength' => true,
                            'placeholder' => 'Название на английском языке'
                        ])->label('Название (Английский)') ?>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="box box-default">
            <div class="box-body">
                <div class="form-group">
                    <?= Html::submitButton('<i class="fa fa-save"></i> Сохранить изменения', ['class' => 'btn btn-success']) ?>
                    <?= Html::a('<i class="fa fa-eye"></i> Просмотр', ['view', 'id' => $model->id], ['class' => 'btn btn-info']) ?>
                    <?= Html::a('<i class="fa fa-times"></i> Отмена', ['index'], ['class' => 'btn btn-default']) ?>
                </div>
            </div>
        </div>
        
        <?php ActiveForm::end(); ?>
    </section>
</div>

<script>
$(document).ready(function() {
    // Initialize Select2
    $('.select2').select2({
        theme: 'bootstrap4',
        width: '100%'
    });
});
</script>
