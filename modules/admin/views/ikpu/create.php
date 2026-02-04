<?php

use yii\helpers\Html;
use yii\bootstrap4\ActiveForm;
use app\widgets\admin_language_tab\AdminLanguageTab;

$this->title = 'Добавить ИКПУ';
$this->params['breadcrumbs'][] = ['label' => 'ИКПУ', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="content-wrapper">
    <section class="content-header">
        <h1><?= Html::encode($this->title) ?></h1>
        
        <ol class="breadcrumb">
            <li><a href="<?=Yii::$app->urlManager->createUrl(['/admin/'])?>"><i class="fa fa-dashboard"></i> Home</a></li>
            <li><a href="<?=Yii::$app->urlManager->createUrl(['/admin/ikpu'])?>">ИКПУ</a></li>
            <li class="active"><?= Html::encode($this->title) ?></li>
        </ol>
    </section>
    
    <section class="content">
        
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
                        <div class="callout callout-info">
                            <h4><i class="fa fa-info"></i> Структура кода ИКПУ</h4>
                            <p><strong>ИКПУ</strong> - 17-значный код классификации продукции Узбекистана (MXIK).</p>
                            <p><strong>Структура кода:</strong></p>
                            <div style="font-family: monospace; font-size: 14px; background: #f4f4f4; padding: 10px; border-radius: 4px; margin: 10px 0;">
                                <span style="color: #d73925;">01</span><span style="color: #00a65a;">90</span><span style="color: #3c8dbc;">200</span><span style="color: #f39c12;">100</span><span style="color: #605ca8;">104</span><span style="color: #dd4b39;">3001</span>
                            </div>
                            <ul style="font-size: 12px;">
                                <li><span style="color: #d73925;">■</span> <strong>Группа</strong> (2 цифры) - основная категория товара</li>
                                <li><span style="color: #00a65a;">■</span> <strong>Класс</strong> (2 цифры) - класс товара в группе</li>
                                <li><span style="color: #3c8dbc;">■</span> <strong>Позиция</strong> (3 цифры) - позиция товара</li>
                                <li><span style="color: #f39c12;">■</span> <strong>Субпозиция</strong> (3 цифры) - детализация</li>
                                <li><span style="color: #605ca8;">■</span> <strong>Бренд</strong> (3 цифры) - код бренда</li>
                                <li><span style="color: #dd4b39;">■</span> <strong>Атрибут</strong> (4 цифры) - характеристики</li>
                            </ul>
                            <p style="font-size: 11px; color: #666;">
                                <strong>Пример:</strong> Макароны упакованные Makfa ракушки 400г
                            </p>
                        </div>
                        
                        <div class="callout callout-warning">
                            <h4><i class="fa fa-exclamation-triangle"></i> Важно</h4>
                            <p>Коды ИКПУ должны соответствовать официальному классификатору MXIK. 
                            Проверить актуальные коды можно на портале 
                            <a href="https://tasnif.soliq.uz" target="_blank">tasnif.soliq.uz</a></p>
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
                    <?= Html::submitButton('<i class="fa fa-save"></i> Сохранить', ['class' => 'btn btn-success']) ?>
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
    
    // IKPU code validation and formatting
    $('#ikpu-code').on('input', function() {
        var code = $(this).val();
        var isValid = /^[0-9]{0,17}$/.test(code);
        
        if (!isValid && code.length > 0) {
            $(this).val(code.replace(/[^0-9]/g, '').substring(0, 17));
            code = $(this).val();
        }
        
        // Visual feedback
        if (code.length === 17) {
            $(this).removeClass('is-invalid').addClass('is-valid');
            showCodeStructure(code);
        } else if (code.length > 0) {
            $(this).removeClass('is-valid').addClass('is-invalid');
        } else {
            $(this).removeClass('is-valid is-invalid');
        }
    });
    
    function showCodeStructure(code) {
        if (code.length === 17) {
            var structure = '<div style="font-family: monospace; font-size: 12px; margin-top: 5px;">' +
                '<strong>Структура:</strong> ' +
                '<span style="color: #d73925; background: #ffeaea; padding: 2px;">' + code.substring(0, 2) + '</span>' +
                '<span style="color: #00a65a; background: #eafaf1; padding: 2px;">' + code.substring(2, 4) + '</span>' +
                '<span style="color: #3c8dbc; background: #eaf4fd; padding: 2px;">' + code.substring(4, 7) + '</span>' +
                '<span style="color: #f39c12; background: #fef9e7; padding: 2px;">' + code.substring(7, 10) + '</span>' +
                '<span style="color: #605ca8; background: #f0eeff; padding: 2px;">' + code.substring(10, 13) + '</span>' +
                '<span style="color: #dd4b39; background: #fdeaea; padding: 2px;">' + code.substring(13, 17) + '</span>' +
                '</div>';
            
            $('#ikpu-code').parent().find('.code-structure').remove();
            $('#ikpu-code').parent().append('<div class="code-structure">' + structure + '</div>');
        }
    }
    
    // Parent code selection handler
    $('#ikpu-parent_code').on('change', function() {
        var parentCode = $(this).val();
        // Additional logic can be added here if needed
    });
});
</script>
