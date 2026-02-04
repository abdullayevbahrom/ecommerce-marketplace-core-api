<?php
use yii\helpers\Html;
use yii\bootstrap4\ActiveForm;
use yii\helpers\ArrayHelper;
use mihaildev\ckeditor\CKEditor;

use app\models\filter\Filter;
use app\widgets\admin_language_tab\AdminLanguageTab;
use yii\services\BTS;

$this->title = 'Добавить склад';

// Prepare all BTS data for JavaScript
$allRegions = BTS::getRegions('ru');
$allCitiesData = [];
foreach ($allRegions as $regionId => $regionName) {
    $cities = BTS::getCities($regionId, 'ru');
    $citiesForRegion = [];
    foreach ($cities as $cityId => $city) {
        $citiesForRegion[] = [
            'id' => $cityId,
            'bts_id' => $cityId,
            'name_ru' => $city['name'],
            'name_uz' => BTS::getCityName($cityId, 'uz'),
            'name_en' => BTS::getCityName($cityId, 'en'),
        ];
    }
    $allCitiesData[$regionId] = $citiesForRegion;
}
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
        <?=AdminLanguageTab::widget();?>
        <br/>
        <?php $form = ActiveForm::begin(['options' => ['enctype' => 'multipart/form-data']]);?>
            <div class="box box-info color-palette-box">
                <div class="box-header">
                    Данные склада
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
                            <div class="lang-block lang-block-ru">
                                <?=$form->field($model, 'name_ru')->textInput()->input('text')->label('Название (RU): <span class="error_field">*</span>');?>
                            </div>
                            <div class="lang-block lang-block-en">
                                <?=$form->field($model, 'name_en')->textInput()->input('text')->label('Название (EN):');?>
                            </div>
                            <div class="lang-block lang-block-uz">
                                <?=$form->field($model, 'name_uz')->textInput()->input('text')->label('Название (UZ):');?>
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
                        <?=$form->field($model, 'description_ru')->widget(CKEditor::className(),[
                            'editorOptions' => [
                                'preset' => 'advanced',
                                'inline' => false,
                                'height' => 200,
                                'filebrowserUploadUrl' => '/shop/images'
                            ]
                        ])->label('Описание (RU): <span class="error_field">*</span>');?>
                    </div>
                    <div class="lang-block lang-block-uz">
                        <?=$form->field($model, 'description_uz')->widget(CKEditor::className(),[
                            'editorOptions' => [
                                'preset' => 'advanced',
                                'inline' => false,
                                'height' => 200,
                                'filebrowserUploadUrl' => '/shop/images'
                            ]
                        ])->label('Описание (UZ):');?>
                    </div>
                    <div class="lang-block lang-block-en">
                        <?=$form->field($model, 'description_en')->widget(CKEditor::className(),[
                            'editorOptions' => [
                                'preset' => 'advanced',
                                'inline' => false,
                                'height' => 200,
                                'filebrowserUploadUrl' => '/shop/images'
                            ]
                        ])->label('Описание (EN):');?>
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
                            <?=$form->field($model, 'bts_region_id')->dropDownList(
                                $allRegions,
                                [
                                    'prompt' => 'Выберите регион',
                                    'id' => 'stock-region',
                                    'class' => 'form-control',
                                    'data-current-city' => $model->bts_city_id
                                ]
                            )->label('<i class="fa fa-globe"></i> Регион: <span class="error_field">*</span>');?>
                        </div>
                        <div class="col-sm-4">
                            <?=$form->field($model, 'bts_city_id')->dropDownList(
                                [],
                                [
                                    'prompt' => 'Выберите город',
                                    'id' => 'stock-city',
                                    'class' => 'form-control'
                                ]
                            )->label('<i class="fa fa-building"></i> Город: <span class="error_field">*</span>');?>
                        </div>
                        <div class="col-sm-4">
                            <?=$form->field($model, 'address')->textInput([
                                'class' => 'form-control',
                                'placeholder' => 'Введите адрес склада'
                            ])->label('<i class="fa fa-home"></i> Адрес:');?>
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

<script>
// Embed PHP data into JavaScript
var BTS_CITIES_DATA = <?= json_encode($allCitiesData, JSON_UNESCAPED_UNICODE) ?>;

// Simple region-city dropdown handler
function updateCitiesDropdown(regionId, selectedCityId) {
    var citySelect = document.getElementById('stock-city');
    
    // Clear existing options
    citySelect.innerHTML = '<option value="">Выберите город</option>';
    
    if (regionId && BTS_CITIES_DATA[regionId]) {
        var cities = BTS_CITIES_DATA[regionId];
        
        // Add cities to dropdown
        cities.forEach(function(city) {
            var option = document.createElement('option');
            option.value = city.bts_id;
            option.textContent = city.name_ru;
            
            // Select current city if specified
            if (selectedCityId && city.bts_id == selectedCityId) {
                option.selected = true;
            }
            
            citySelect.appendChild(option);
        });
        
        citySelect.disabled = false;
    } else {
        citySelect.disabled = true;
    }
}

// Initialize when page loads
document.addEventListener('DOMContentLoaded', function() {
    var regionSelect = document.getElementById('stock-region');
    var currentCityId = regionSelect.getAttribute('data-current-city');

    // Handle region change
    regionSelect.addEventListener('change', function() {
        updateCitiesDropdown(this.value, null);
    });

    // Load cities on page load if region is already selected
    if (regionSelect.value && currentCityId) {
        updateCitiesDropdown(regionSelect.value, currentCityId);
    }

    // Form validation
    document.querySelector('form').addEventListener('submit', function(e) {
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

