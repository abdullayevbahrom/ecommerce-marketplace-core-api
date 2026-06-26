<?php
use yii\helpers\Html;
use yii\bootstrap4\ActiveForm;
use mihaildev\ckeditor\CKEditor;
use app\widgets\admin_language_tab\AdminLanguageTab;

/** @var yii\web\View $this */
/** @var app\models\stock\Stock $model */
/** @var yii\bootstrap4\ActiveForm $form */

$this->title = 'Добавить склад';

$regions = Yii::$app->bts->getRegions('ru');
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="content-wrapper">
    <section class="content-header">
        <h1><?= $this->title; ?></h1>

        <ol class="breadcrumb">
            <li><a href="<?= Yii::$app->urlManager->createUrl(['/shop/']) ?>"><i class="fa fa-dashboard"></i> Главная</a>
            </li>
            <li class="active"><?= $this->title; ?></li>
        </ol>
    </section>
    <section class="content">
        <?= AdminLanguageTab::widget(); ?>
        <br />
        <?php $form = ActiveForm::begin(['options' => ['enctype' => 'multipart/form-data']]); ?>
        <div class="box box-info color-palette-box">
            <div class="box-header">
                Данные склада
            </div>
            <div class="box-body">
                <div class="row">
                    <div class="col-sm-5">
                        <div class="row">
                            <div class="col-xs-6">
                                <?= $form->field($model, 'imageFiles[]')->fileInput(['class' => 'file-upload-ajax'])->label('Главное фото'); ?>
                            </div>
                            <div class="col-xs-6">
                                <img src="<?= $model->getPhoto('200x200') ?>" width="200" class="photo-admin-user" />
                                <?php if ($model->image) { ?>
                                    <br /><br />
                                    <a href="<?= Yii::$app->urlManager->createUrl(['/shop/default/remove-photo', 'id' => $model->image->id]) ?>"
                                        class="edit-user remove-object btn btn-sm btn-danger"><i class="fa fa-remove"></i>
                                        Удалить фото</a>
                                <?php } ?>
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-7">
                        <div class="lang-block lang-block-ru">
                            <?= $form->field($model, 'name_ru')->textInput()->input('text')->label('Название (RU): <span class="error_field">*</span>'); ?>
                        </div>
                        <div class="lang-block lang-block-en">
                            <?= $form->field($model, 'name_en')->textInput()->input('text')->label('Название (EN):'); ?>
                        </div>
                        <div class="lang-block lang-block-uz">
                            <?= $form->field($model, 'name_uz')->textInput()->input('text')->label('Название (UZ):'); ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="box box-info color-palette-box">
            <div class="box-header">
                Описание склада
            </div>
            <div class="box-body">
                <div class="lang-block lang-block-ru">
                    <?= $form->field($model, 'description_ru')->widget(CKEditor::className(), [
                        'editorOptions' => [
                            'preset' => 'advanced',
                            'inline' => false,
                            'height' => 200,
                            'filebrowserUploadUrl' => '/shop/images'
                        ]
                    ])->label('Описание (RU): <span class="error_field">*</span>'); ?>
                </div>
                <div class="lang-block lang-block-uz">
                    <?= $form->field($model, 'description_uz')->widget(CKEditor::className(), [
                        'editorOptions' => [
                            'preset' => 'advanced',
                            'inline' => false,
                            'height' => 200,
                            'filebrowserUploadUrl' => '/shop/images'
                        ]
                    ])->label('Описание (UZ):'); ?>
                </div>
                <div class="lang-block lang-block-en">
                    <?= $form->field($model, 'description_en')->widget(CKEditor::className(), [
                        'editorOptions' => [
                            'preset' => 'advanced',
                            'inline' => false,
                            'height' => 200,
                            'filebrowserUploadUrl' => '/shop/images'
                        ]
                    ])->label('Описание (EN):'); ?>
                </div>
            </div>
        </div>
        <div class="box box-info color-palette-box">
            <div class="box-header">
                Местоположение
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
                            [],
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
            </div>

            <div class="box-footer">
                <div class="text-right">
                    <?= Html::submitButton('<i class="fa fa-check-square-o"></i> Сохранить', ['class' => 'btn btn-primary']) ?>
                </div>
            </div>
        </div>
        <?php ActiveForm::end(); ?>
    </section>
</div>

<script>
    async function updateCitiesDropdown(regionId, selectedCityId) {
        let citySelect = document.getElementById('stock-city');

        citySelect.innerHTML = '<option value="">Загрузка городов...</option>';
        citySelect.disabled = true;

        if (regionId) {
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

                citySelect.innerHTML = '<option value="">Выберите город</option>';

                if (data.success && data.cities) {
                    Object.values(data.cities).forEach(function (city) {
                        if (city && city.name) {
                            let option = document.createElement('option');
                            option.value = city.code;
                            option.textContent = city.name.ru;
                            if (selectedCityId && city.code == selectedCityId) {
                                option.selected = true;
                            }
                            citySelect.appendChild(option);
                        }
                    });

                    citySelect.disabled = false;
                } else {
                    citySelect.innerHTML = '<option value="">Города nе найдены</option>';
                }

            } catch (error) {
                console.error('Xatolik:', error);
                citySelect.innerHTML = '<option value="">Ошибка загрузки</option>';
                citySelect.disabled = true;
            }
        } else {
            citySelect.innerHTML = '<option value="">Выберите город</option>';
            citySelect.disabled = true;
        }
    }

    // Initialize when page loads
    document.addEventListener('DOMContentLoaded', async function () {
        var regionSelect = document.getElementById('stock-region');
        var currentCityId = regionSelect.getAttribute('data-current-city');

        // Handle region change
        regionSelect.addEventListener('change', async function () {
            await updateCitiesDropdown(this.value, null);
        });

        // Load cities on page load if region is already selected
        if (regionSelect.value) {
            await updateCitiesDropdown(regionSelect.value, currentCityId);
        }

        // Form validation
        document.querySelector('form').addEventListener('submit', function (e) {
            var regionId = regionSelect.value;
            var cityId = document.getElementById('stock-city').value;

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

    .form-group label i {
        margin-right: 5px;
        color: #666;
    }

    #stock-city:disabled {
        background-color: #f5f5f5;
        cursor: not-allowed;
    }
</style>