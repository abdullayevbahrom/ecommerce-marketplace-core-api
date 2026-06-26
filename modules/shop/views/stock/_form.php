<?php
use yii\helpers\Html;
use mihaildev\ckeditor\CKEditor;
use yii\helpers\ArrayHelper;

/** @var yii\web\View $this */
/** @var app\models\stock\Stock $model */
/** @var yii\bootstrap4\ActiveForm $form */

// Get current cities if model has region selected
$regions = Yii::$app->bts->getRegions('ru');
$currentCities = [];
if ($model->bts_region_id) {
    $cities = Yii::$app->bts->getCities($model->bts_region_id);
    $currentCities = ArrayHelper::map($cities, 'code', 'name.ru');
}
?>

<div class="box box-info color-palette-box">
    <div class="box-header">
        Основная информация
    </div>
    <div class="box-body">
        <div class="row">
            <div class="col-sm-6">
                <?= $form->field($model, 'name_ru')->textInput()->label('Название (Русский): <span class="error_field">*</span>'); ?>
            </div>
            <div class="col-sm-6">
                <?= $form->field($model, 'name_en')->textInput()->label('Название (Английский):'); ?>
            </div>
        </div>
        <div class="row">
            <div class="col-sm-6">
                <?= $form->field($model, 'name_uz')->textInput()->label('Название (Узбекский):'); ?>
            </div>
            <div class="col-sm-6">
                <?= $form->field($model, 'sort')->textInput()->label('Порядок сортировки:'); ?>
            </div>
        </div>
    </div>
</div>

<div class="box box-info color-palette-box">
    <div class="box-header">
        Описание
    </div>
    <div class="box-body">
        <div class="row">
            <div class="col-sm-4">
                <?= $form->field($model, 'description_ru')->widget(CKEditor::class, [
                    'editorOptions' => [
                        'preset' => 'full',
                        'inline' => false,
                    ],
                ])->label('Описание (Русский):'); ?>
            </div>
            <div class="col-sm-4">
                <?= $form->field($model, 'description_en')->widget(CKEditor::class, [
                    'editorOptions' => [
                        'preset' => 'full',
                        'inline' => false,
                    ],
                ])->label('Описание (Английский):'); ?>
            </div>
            <div class="col-sm-4">
                <?= $form->field($model, 'description_uz')->widget(CKEditor::class, [
                    'editorOptions' => [
                        'preset' => 'full',
                        'inline' => false,
                    ],
                ])->label('Описание (Узбекский):'); ?>
            </div>
        </div>
    </div>
</div>

<div class="box box-info color-palette-box">
    <div class="box-header">
        Изображения
    </div>
    <div class="box-body">
        <div class="row">
            <div class="col-sm-12">
                <?php if ($model->image) { ?>
                    <p>Текущее изображение:</p>
                    <img src="<?= $model->getPhoto('sm') ?>" class="img-thumbnail" style="max-width: 200px;">
                    <br><br>
                <?php } ?>
                <?= $form->field($model, 'imageFiles[]')->fileInput(['multiple' => true, 'accept' => 'image/*'])->label('Изображения:'); ?>
            </div>
        </div>
    </div>
</div>

<div class="box box-info color-palette-box">
    <div class="box-header">
        <i class="fa fa-map-marker"></i> Местоположение
    </div>
    <div class="box-body">
        <div class="row">
            <div class="col-sm-4">
                <?= $form->field($model, 'bts_region_id')->dropDownList(
                    $regions,
                    [
                        'prompt' => 'Выберите регион',
                        'id' => 'stock-region',
                        'class' => 'form-control',
                        'data-current-city' => $model->bts_city_id
                    ]
                )->label('<i class="fa fa-globe"></i> Регион: <span class="error_field">*</span>'); ?>
            </div>
            <div class="col-sm-4">
                <?= $form->field($model, 'bts_city_id')->dropDownList(
                    $currentCities,
                    [
                        'prompt' => 'Выберите город',
                        'id' => 'stock-city',
                        'class' => 'form-control'
                    ]
                )->label('<i class="fa fa-building"></i> Город: <span class="error_field">*</span>'); ?>
            </div>
            <div class="col-sm-4">
                <?= $form->field($model, 'address')->textInput([
                    'class' => 'form-control',
                    'placeholder' => 'Введите адрес склада'
                ])->label('<i class="fa fa-home"></i> Адрес:'); ?>
            </div>
        </div>

        <?php if ($model->bts_region_id && $model->bts_city_id): ?>
            <div class="row">
                <div class="col-sm-12">
                    <div class="alert alert-info">
                        <i class="fa fa-info-circle"></i>
                        <strong>Текущее местоположение:</strong>
                        <?= $model->getFullAddress('ru') ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<div class="box-footer">
    <div class="text-right">
        <?= Html::submitButton('<i class="fa fa-check-square-o"></i> ' . ($model->isNewRecord ? 'Создать' : 'Обновить'), ['class' => 'btn btn-primary']) ?>
        <?= Html::a('<i class="fa fa-times"></i> Отмена', ['index'], ['class' => 'btn btn-default']) ?>
    </div>
</div>

<script>
    $(document).ready(async function () {
        // Initialize region-city functionality
        initializeRegionCityDropdowns();

        async function initializeRegionCityDropdowns() {
            var $regionSelect = $('#stock-region');
            var $citySelect = $('#stock-city');
            var currentCityId = $regionSelect.data('current-city');

            // Handle region change
            $regionSelect.on('change', function () {
                var regionId = $(this).val();
                loadCitiesByRegion(regionId, null);
            });

            // Load cities for selected region
            async function loadCitiesByRegion(regionId, selectedCityId) {
                if (regionId) {
                    citySelect.prop('disabled', true).html('<option value="">Загрузка городов...</option>');
                    let params = new URLSearchParams({ region_id: regionId }).toString();
                    try {
                        const response = await fetch('/admin/bts/cities?' + params, {
                            method: 'GET',
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest'
                            }
                        });

                        if (!response.ok) {
                            throw new Error('Tarmoq xatosi yuz berdi');
                        }

                        const data = await response.json();

                        citySelect.html('<option value="">Выберите город</option>');

                        if (data.success && data.cities) {
                            Object.values(data.cities).forEach(function (city) {
                                if (city && city.name) {
                                    let code = city.code;
                                    let title = city.name.ru;
                                    let selected = (selectedCityId && code == selectedCityId) ? 'selected' : '';
                                    citySelect.append('<option value="' + code + '" ' + selected + '>' + title + '</option>');
                                }
                            });
                        } else {
                            $citySelect.html('<option value="">Нет городов в данном регионе</option>').prop('disabled', false);
                        }

                    } catch (error) {
                        console.error('Xatolik:', error);
                        citySelect.html('<option value="">Ошибка загрузки</option>');
                    } finally {
                        citySelect.prop('disabled', false);
                    }
                } else {
                    $citySelect.html('<option value="">Выберите город</option>').prop('disabled', false);
                }
            }

            // Load cities on page load if region is already selected
            if ($regionSelect.val() && currentCityId) {
                loadCitiesByRegion($regionSelect.val(), currentCityId);
            }
        }

        // Form validation enhancement
        $('form').on('submit', function (e) {
            var regionId = $('#stock-region').val();
            var cityId = $('#stock-city').val();

            if (regionId && !cityId) {
                e.preventDefault();
                alert('Пожалуйста, выберите город для выбранного региона.');
                return false;
            }
        });
    });
</script>

<style>
    .error_field {
        color: red;
    }

    .box-header i {
        margin-right: 5px;
    }

    .form-group label i {
        margin-right: 5px;
        color: #666;
    }

    #stock-city:disabled {
        background-color: #f5f5f5;
        cursor: not-allowed;
    }

    .alert-info {
        border-left: 4px solid #5bc0de;
    }
</style>