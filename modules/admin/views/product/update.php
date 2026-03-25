<?php
use yii\helpers\Html;
use yii\bootstrap4\ActiveForm;
use yii\helpers\ArrayHelper;
use mihaildev\ckeditor\CKEditor;

use app\models\product\ProductFilter;

use app\models\filter\Filter;
use app\widgets\admin_language_tab\AdminLanguageTab;

$this->title = 'Редактировать товар: ' . $model->name_ru;
$this->params['breadcrumbs'][] = ['label' => 'Товары', 'url' => ['/admin/product/']];
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="content-wrapper">
    <section class="content-header">
        <h1>
            <i class="fa fa-edit"></i> Редактировать товар
            <small>ID: <?= $model->id ?></small>
        </h1>

        <ol class="breadcrumb">
            <li><a href="<?=Yii::$app->urlManager->createUrl(['/admin/'])?>"><i class="fa fa-dashboard"></i> Главная</a></li>
            <li><a href="<?=Yii::$app->urlManager->createUrl(['/admin/product/'])?>"><i class="fa fa-cube"></i> Товары</a></li>
            <li class="active">Редактировать</li>
        </ol>
    </section>
    <section class="content">
        <?php $form = ActiveForm::begin(['options' => ['enctype' => 'multipart/form-data']]);?>
            <?=$form->field($model, 'token_key')->hiddenInput()->label(false);?>
            
            <div class="row">
                <div class="col-md-12">
                    <!-- Main Product Information -->
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title">
                        <i class="fa fa-info-circle"></i> Основная информация о товаре
                    </h3>
                    <div class="box-tools pull-right">
                        <a href="<?=Yii::$app->urlManager->createUrl(['/admin/product/view', 'id'=>$model->id])?>" class="btn btn-info btn-sm">
                            <i class="fa fa-eye"></i> Просмотр
                        </a>
                    </div>
                </div>
                <div class="box-body">
                    <div class="row">
                        <!-- Product Photo Section -->
                        <div class="col-md-4">
                            <div class="form-group">
                                <label class="control-label">Основное фото товара</label>
                                <div class="row">
                                    <div class="col-xs-12">
                                        <?=$form->field($model, 'imageFiles[]', ['template' => '{input}{error}'])->fileInput([
                                            'class' => 'file-upload-ajax',   
                                            'accept' => 'image/jpeg, image/jpg, image/png, image/gif, image/svg+xml, image/webp'
                                        ]); ?>
                                    </div>
                                    <div class="col-xs-12 text-center" style="margin-top: 10px;">
                                        <img src="<?=$model->getPhoto('200x200')?>" width="200" class="photo-admin-user img-thumbnail"/>
                                        <?php if ($model->image) {?>
                                            <br/><br/>
                                            <a href="<?=Yii::$app->urlManager->createUrl(['/admin/default/remove-photo', 'id'=>$model->image->id])?>" class="edit-user remove-object btn btn-sm btn-danger">
                                                <i class="fa fa-trash"></i> Удалить фото
                                            </a>
                                        <?php }?>
                                        <div class="help-block">
                                            <small>Рекомендуемый размер: 500x500px</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Basic Information -->
                        <div class="col-md-8">
                            <!-- Product Names -->
                            <div class="form-group">
                                <label class="control-label">Название товара <span class="text-danger">*</span></label>
                                <div class="nav-tabs-custom" style="margin-bottom: 0;">
                                    <ul class="nav nav-tabs">
                                        <li class="active"><a href="#name-ru" data-toggle="tab">RU</a></li>
                                        <li><a href="#name-uz" data-toggle="tab">UZ</a></li>
                                        <li><a href="#name-en" data-toggle="tab">EN</a></li>
                                    </ul>
                                    <div class="tab-content" style="padding: 10px;">
                                        <div class="active tab-pane" id="name-ru">
                                            <?=$form->field($model, 'name_ru', ['template' => '{input}{error}'])->textInput([
                                                'placeholder' => 'Введите название товара на русском языке'
                                            ]);?>
                                        </div>
                                        <div class="tab-pane" id="name-uz">
                                            <?=$form->field($model, 'name_uz', ['template' => '{input}{error}'])->textInput([
                                                'placeholder' => 'Mahsulot nomini o\'zbek tilida kiriting'
                                            ]);?>
                                        </div>
                                        <div class="tab-pane" id="name-en">
                                            <?=$form->field($model, 'name_en', ['template' => '{input}{error}'])->textInput([
                                                'placeholder' => 'Enter product name in English'
                                            ]);?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Basic Settings -->
                            <div class="row">
                                <div class="col-sm-4">
                                    <?=$form->field($model, 'shop_id')->dropDownList(
                                        $shops,
                                        [
                                            'class'=>'form-control',
                                            'prompt'=>'Выберите магазин',
                                            'id'=>'product-shop_id'
                                        ]
                                    )->label('Магазин <span class="text-danger">*</span>');?>
                                </div>
                                <div class="col-sm-4">
                                    <?=$form->field($model, 'brand_id')->dropDownList(
                                        $brands,
                                        [
                                            'class'=>'form-control select2',
                                            'prompt'=>'Выберите бренд'
                                        ]
                                    )->label('Бренд');?>
                                </div>
                            </div>
                            
                            <!-- Warehouse Selection -->
                            <div class="row">
                                <div class="col-sm-4">
                                    <?=$form->field($model, 'stock_id')->dropDownList(
                                        [],
                                        [
                                            'class'=>'form-control',
                                            'prompt'=>'Выберите склад',
                                            'id'=>'product-stock_id',
                                            'disabled'=>true
                                        ]
                                    )->label('Склад');?>
                                </div>
                                <div class="col-sm-4">
                                    <?=$form->field($model, 'delivery_id')->dropDownList(
                                        $deliveries,
                                        [
                                            'class'=>'form-control select2',
                                            'prompt'=>'Выберите тип доставки'
                                        ]
                                    )->label('Тип доставки');?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Pricing Information -->
            <div class="box box-success">
                <div class="box-header with-border">
                    <h3 class="box-title">
                        <i class="fa fa-rub"></i> Ценовая информация
                    </h3>
                </div>
                <div class="box-body">
                    <div class="row">
                        <div class="col-sm-3">
                            <?=$form->field($model, 'price')->textInput([
                                'type' => 'number',
                                'step' => '0.01',
                                'min' => '0',
                                'placeholder' => '0.00'
                            ])->label('Цена <span class="text-danger">*</span>');?>
                        </div>
                        <div class="col-sm-3">
                            <?=$form->field($model, 'price_small')->textInput([
                                'type' => 'number',
                                'step' => '0.01',
                                'min' => '0',
                                'placeholder' => '0.00'
                            ])->label('Цена мелкий опт');?>
                        </div>
                        <div class="col-sm-3">
                            <?=$form->field($model, 'price_opt')->textInput([
                                'type' => 'number',
                                'step' => '0.01',
                                'min' => '0',
                                'placeholder' => '0.00'
                            ])->label('Цена оптом');?>
                        </div>
                        <div class="col-sm-3">
                            <?=$form->field($model, 'min_order')->textInput([
                                'type' => 'number',
                                'min' => '1',
                                'placeholder' => '1'
                            ])->label('Мин. заказ');?>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-sm-3">
                            <?=$form->field($model, 'qty_small_wholesale')->textInput([
                                'type' => 'number',
                                'min' => '1',
                                'placeholder' => '1'
                            ])->label('Кол-во малого опта');?>
                        </div>
                        <div class="col-sm-3">
                            <?=$form->field($model, 'qty_big_wholesale')->textInput([
                                'type' => 'number',
                                'min' => '1',
                                'placeholder' => '1'
                            ])->label('Кол-во крупного опта');?>
                        </div>
                        <div class="col-sm-3">
                            <?=$form->field($model, 'discount')->textInput([
                                'type' => 'number',
                                'min' => '0',
                                'max' => '100',
                                'step' => '0.01',
                                'placeholder' => '0'
                            ])->label('Скидка %');?>
                        </div>
                        <div class="col-sm-3">
                            <?=$form->field($model, 'amount')->textInput([
                                'type' => 'number',
                                'min' => '0',
                                'placeholder' => '0'
                            ])->label('Количество');?>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Additional Information -->
            <div class="box box-info">
                <div class="box-header with-border">
                    <h3 class="box-title">
                        <i class="fa fa-tags"></i> Дополнительная информация
                    </h3>
                </div>
                <div class="box-body">
                    <div class="row">
                        <div class="col-sm-6">
                            <?=$form->field($model, 'tag_id')->dropDownList(
                                $tags,
                                [
                                    'class'=>'form-control select2',
                                    'prompt'=>'Выберите тег',
                                    'options' => [$tree[0] => ['selected'=>'selected']]
                                ]
                            )->label('Тег');?>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-sm-6">
                            <?=$form->field($model, 'barcode')->textInput([
                                'placeholder' => 'Введите штрихкод'
                            ])->label('Штрихкод');?>
                        </div>
                        <div class="col-sm-6">
                            <?=$form->field($model, 'status')->dropDownList([
                                1 => 'Активен',
                                2 => 'Заблокирован'
                            ], [
                                'class'=>'form-control'
                            ])->label('Статус');?>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Categories Section -->
            <div class="box box-warning">
                <div class="box-header with-border">
                    <h3 class="box-title">
                        <i class="fa fa-sitemap"></i> Категории товара
                    </h3>
                </div>
                <div class="box-body">

                            <div id="category-block">
                                <!-- categories -->
                                <?=$form->field($model, 'category_id')->dropDownList(
                                    $categories,
                                    [
                                        'class'=>'category-item form-control select2',
                                        'prompt'=>'Select category',
                                        'options' => [$tree[0] => ['selected'=>'selected']]
                                    ]
                                )->label('Category: <span class="error_field">*</span>');?>
                                <?php if ($current_categories) {
                                    foreach ($current_categories as $key => $category) {
                                        if ($category) {
                                                $selectedOption = isset($tree[$key+1]) && is_scalar($tree[$key+1]) ? $tree[$key+1] : null;
                                                echo $form->field($model, 'sub_category_id[]', ['options'=>['class'=>['category-group form-group']]])->dropDownList(
                                                    $category,
                                                    [
                                                        'class'=>'category-item form-control select2',
                                                        'prompt'=>'Select subcategory',
                                                        'options' => $selectedOption ? [$selectedOption => ['selected'=>'selected']] : []
                                                    ]
                                                )->label('Subcategory');
                                        }
                                    }
                                }?>
                                <!-- end categories -->

                                <!-- IKPU -->
                                <div class="form-group">
                                    <label class="control-label">ИКПУ (Классификатор продукции):</label>
                                    
                                    <?php if ($model->ikpu_code) { ?>
                                        <div class="alert alert-info" style="margin-bottom: 10px;">
                                            <strong>Текущий ИКПУ:</strong> 
                                            <code><?= Html::encode($model->ikpu_code) ?></code>
                                            <?php if ($model->ikpu_name) { ?>
                                                - <?= Html::encode($model->ikpu_name) ?>
                                            <?php } ?>
                                            <?php if ($model->ikpu) { ?>
                                                <span class="label label-success">Из справочника</span>
                                            <?php } else { ?>
                                                <span class="label label-warning">Пользовательский</span>
                                            <?php } ?>
                                        </div>
                                    <?php } ?>
                                    
                                    <!-- Toggle buttons -->
                                    <div class="btn-group btn-group-sm" style="margin-bottom: 10px;">
                                        <button type="button" class="btn btn-primary" id="ikpu-mode-search" onclick="switchIkpuMode('search')">
                                            <i class="fa fa-search"></i> Выбрать из справочника
                                        </button>
                                        <button type="button" class="btn btn-default" id="ikpu-mode-custom" onclick="switchIkpuMode('custom')">
                                            <i class="fa fa-edit"></i> Ввести свой код
                                        </button>
                                    </div>
                                    
                                    <!-- Search mode -->
                                    <div id="ikpu-search-mode">
                                        <?= $form->field($model, 'ikpu_code', ['template' => '{input}{error}'])->textInput([
                                            'placeholder' => 'Начните печатать код или название ИКПУ для поиска...',
                                            'id' => 'ikpu-code-input',
                                            'class' => 'form-control',
                                            'autocomplete' => 'off',
                                            'value' => $model->ikpu && $model->ikpu_code ? $model->ikpu_code : ''
                                        ]) ?>
                                        <div id="ikpu-suggestions" class="list-group" style="display: none; position: absolute; z-index: 1000; max-height: 200px; overflow-y: auto; width: 100%;"></div>
                                        <div class="help-block">
                                            <i class="fa fa-info-circle"></i> Начните печатать для поиска в справочнике ИКПУ
                                        </div>
                                    </div>
                                    
                                    <!-- Custom mode -->
                                    <div id="ikpu-custom-mode" style="display: none;">
                                        <?= $form->field($model, 'ikpu_code', ['template' => '{input}{error}'])->textInput([
                                            'placeholder' => 'Введите 17-значный код ИКПУ',
                                            'id' => 'ikpu-custom-input',
                                            'class' => 'form-control',
                                            'pattern' => '[0-9]{17}',
                                            'title' => 'Код ИКПУ должен содержать ровно 17 цифр',
                                            'value' => !$model->ikpu && $model->ikpu_code ? $model->ikpu_code : ''
                                        ]) ?>
                                        <?= $form->field($model, 'ikpu_name', ['template' => '{input}{error}'])->textInput([
                                            'placeholder' => 'Введите название для этого кода ИКПУ',
                                            'id' => 'ikpu-custom-name',
                                            'class' => 'form-control',
                                            'value' => !$model->ikpu && $model->ikpu_name ? $model->ikpu_name : ''
                                        ]) ?>
                                        <div class="help-block">
                                            <i class="fa fa-warning text-orange"></i> Пользовательский код ИКПУ (не из официального справочника)
                                        </div>
                                    </div>
                                </div>
                                <!-- end IKPU -->

                                <!-- filters -->
                                <?php if ($model->productFilters) {?>
                                    <?php foreach ($model->productFilters as $key => $value) {?>
                                        <?php if ($value->filter) {?>
                                            <?php if ($value->filter->type == 'input') {?>
                                                <?=$form->field($model, 'filters['.$value->filter->id.']', ['options'=>['class'=>'category-group form-group']])->textInput()->input('text', ['value'=>$value->value_ru])->label($value->filter->name_ru);?>
                                            <?php }?>
                                            <?php if ($value->filter->type == 'select') {?>
                                                <?php $filter_childs = ArrayHelper::map(Filter::find()->where(['parent_id'=>$value->filter->id])->all(), 'value_ru', 'value_ru');?>
                                                <?php if ($filter_childs) {?>
                                                    <?=$form->field($model, 'filters['.$value->filter->id.']', ['options'=>['class'=>'category-group form-group']])->dropDownList(
                                                        $filter_childs,
                                                        [
                                                            'class'=>'form-control select2',
                                                            'prompt'=>'Select subcategory',
                                                            'options' => [$value->value_ru => ['selected'=>'selected']]
                                                        ]
                                                    )->label($value->filter->name_ru);?>
                                                <?php }?>
                                            <?php }?>
                                            <?php if ($value->filter->type == 'checkbox') {?>
                                                <div class="category-group form-group">
                                                    <?php $filter_childs_ids = ArrayHelper::map(ProductFilter::find()->where(['product_id'=>$value->product_id, 'filter_id'=>$value->filter_id])->all(), 'value_ru', 'value_ru');?>
                                                    <?php $filter_childs = Filter::find()->where(['parent_id'=>$value->filter->id])->all();?>
                                                    <?php foreach ($filter_childs as $k => $v) {?>
                                                        <?=$form->field($model, 'filters['.$value->filter->id.']['.$k.']')->checkbox(['value' => $v->value_ru, 'checked'=>array_key_exists($v->value_ru, $filter_childs_ids) ? true : false])->label($v->value_ru);?>
                                                    <?php }?>
                                                </div>
                                            <?php }?>
                                        <?php }?>
                                    <?php }?>
                                <?php }?>
                                <!-- end filters -->
                            </div>
                            
                        </div>
                    </div>
                </div>
            </div>
            <!-- Dimensions Section -->
            <div class="box box-default">
                <div class="box-header with-border">
                    <h3 class="box-title">
                        <i class="fa fa-cube"></i> Габариты товара
                    </h3>
                </div>
                <div class="box-body">
                    <div class="row">
                        <div class="col-sm-3">
                            <?=$form->field($model, 'weight')->textInput([
                                'type' => 'number',
                                'min' => '0',
                                'step' => '0.01',
                                'placeholder' => '0'
                            ])->label('Вес (грамм)');?>
                        </div>
                        <div class="col-sm-3">
                            <?=$form->field($model, 'height')->textInput([
                                'type' => 'number',
                                'min' => '0',
                                'step' => '0.1',
                                'placeholder' => '0'
                            ])->label('Высота (см)');?>
                        </div>
                        <div class="col-sm-3">
                            <?=$form->field($model, 'width')->textInput([
                                'type' => 'number',
                                'min' => '0',
                                'step' => '0.1',
                                'placeholder' => '0'
                            ])->label('Ширина (см)');?>
                        </div>
                        <div class="col-sm-3">
                            <?=$form->field($model, 'length')->textInput([
                                'type' => 'number',
                                'min' => '0',
                                'step' => '0.1',
                                'placeholder' => '0'
                            ])->label('Длина (см)');?>
                        </div>
                    </div>
                    <div class="help-block">
                        <i class="fa fa-info-circle"></i> Укажите точные размеры товара для расчета доставки
                    </div>
                </div>
            </div>
            <!-- Product Description -->
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title">
                        <i class="fa fa-file-text-o"></i> Описание товара
                    </h3>
                </div>
                <div class="box-body">
                    <div class="nav-tabs-custom">
                        <ul class="nav nav-tabs">
                            <li class="active"><a href="#desc-ru" data-toggle="tab">RU</a></li>
                            <li><a href="#desc-uz" data-toggle="tab">UZ</a></li>
                            <li><a href="#desc-en" data-toggle="tab">EN</a></li>
                        </ul>
                        <div class="tab-content">
                            <div class="active tab-pane" id="desc-ru">
                                <?=$form->field($model, 'description_ru', ['template' => '{input}{error}'])->widget(CKEditor::className(),[
                                    'editorOptions' => [
                                        'preset' => 'advanced',
                                        'inline' => false,
                                        'height' => 200,
                                        'filebrowserUploadUrl' => '/admin/images'
                                    ]
                                ])->label('Описание на русском языке');?>
                            </div>
                            <div class="tab-pane" id="desc-uz">
                                <?=$form->field($model, 'description_uz', ['template' => '{input}{error}'])->widget(CKEditor::className(),[
                                    'editorOptions' => [
                                        'preset' => 'advanced',
                                        'inline' => false,
                                        'height' => 200,
                                        'filebrowserUploadUrl' => '/admin/images'
                                    ]
                                ])->label('Описание на узбекском языке');?>
                            </div>
                            <div class="tab-pane" id="desc-en">
                                <?=$form->field($model, 'description_en', ['template' => '{input}{error}'])->widget(CKEditor::className(),[
                                    'editorOptions' => [
                                        'preset' => 'advanced',
                                        'inline' => false,
                                        'height' => 200,
                                        'filebrowserUploadUrl' => '/admin/images'
                                    ]
                                ])->label('Описание на английском языке');?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Product Types (Read-only display of existing connections) -->
            <?php if ($current_product_types) {?>
                <div class="box box-info">
                    <div class="box-header with-border">
                        <h3 class="box-title">
                            <i class="fa fa-tags"></i> Типы товара
                        </h3>
                    </div>
                    <div class="box-body">
                        <div class="alert alert-info">
                            <i class="fa fa-info-circle"></i> У этого товара есть следующие типовые вариации. Типы товаров устанавливаются при создании и не могут быть изменены здесь.
                        </div>
                        
                        <?php 
                        // Group product types by type
                        $grouped_types = [];
                        foreach ($current_product_types as $connection) {
                            if ($connection->productType) {
                                $type_name = $connection->productType->name_ru;
                                if (!isset($grouped_types[$type_name])) {
                                    $grouped_types[$type_name] = [];
                                }
                                if ($connection->productTypeValue) {
                                    $grouped_types[$type_name][] = $connection->productTypeValue->value_ru;
                                } else {
                                    $grouped_types[$type_name][] = $connection->custom_value;
                                }
                            }
                        }
                        ?>
                        
                        <?php if ($grouped_types) {?>
                            <?php foreach ($grouped_types as $type_name => $values) {?>
                                <div class="form-group">
                                    <label class="control-label"><?=$type_name;?></label>
                                    <div style="margin-top: 10px;">
                                        <?php foreach ($values as $value) {?>
                                            <span class="badge badge-info" style="margin-right: 10px; padding: 8px 12px; font-size: 12px;">
                                                <?=$value;?>
                                            </span>
                                        <?php }?>
                                    </div>
                                </div>
                                <hr>
                            <?php }?>
                        <?php } else {?>
                            <p class="text-muted">No product type variations found for this product.</p>
                        <?php }?>
                    </div>
                </div>
            <?php }?>

            <!-- Product Characteristics -->
            <div class="box box-warning">
                <div class="box-header with-border">
                    <h3 class="box-title">
                        <i class="fa fa-list"></i> Характеристики товара
                    </h3>
                </div>
                <div class="box-body">
                    <div id="item-form">
                        <?php if ($model->productProperties) {?>
                            <?php foreach ($model->productProperties as $v) {?>
                                <div class="item-block">
                                    <div class="row">
                                        <div class="col-sm-5">
                                            <?=$form->field($model, 'properties_data[key_name][]')->textInput()->input('text', ['value'=>$v->key_name, 'placeholder'=>'Введите название характеристики'])->label(false);?>
                                        </div>
                                        <div class="col-sm-5">
                                            <?=$form->field($model, 'properties_data[value_name][]')->textInput()->input('text', ['value'=>$v->value_name, 'placeholder'=>'Введите значение характеристики'])->label(false);?>
                                        </div>
                                        <div class="col-sm-2">
                                            <a href="javascript:;" class="btn btn-danger remove-block" title="Удалить"><i class="fa fa-trash"></i></a>
                                        </div>
                                    </div>
                                </div>
                            <?php }?>
                        <?php } else {?>
                            <div class="item-block">
                                <div class="row">
                                    <div class="col-sm-5">
                                        <?=$form->field($model, 'properties_data[key_name][]')->textInput()->input('text', ['placeholder'=>'Введите название характеристики'])->label(false);?>
                                    </div>
                                    <div class="col-sm-5">
                                        <?=$form->field($model, 'properties_data[value_name][]')->textInput()->input('text', ['placeholder'=>'Введите значение характеристики'])->label(false);?>
                                    </div>
                                    <div class="col-sm-2">
                                        <a href="javascript:;" class="btn btn-danger remove-block"><i class="fa fa-trash"></i></a>
                                    </div>
                                </div>
                            </div>
                        <?php }?>
                    </div>

                    <div class="text-center">
                        <a href="javascript:;" class="add-variant-item btn btn-success btn-sm">
                            <i class="fa fa-plus"></i> Добавить характеристику
                        </a>
                    </div>
                </div>
            </div>

            <!-- Photo Gallery -->
            <div class="box box-success">
                <div class="box-header with-border">
                    <h3 class="box-title">
                        <i class="fa fa-camera"></i> Галерея фотографий
                    </h3>
                </div>
                <div class="box-body">
                    <?=$form->field($model, 'imageGallery[]')->fileInput([
                        'multiple' => true, 
                        'accept' => 'image/jpeg, image/jpg, image/png, image/gif, image/svg+xml, image/webp', 
                        'class'=>'file-upload-ajax-gallery'
                    ])->label('Дополнительные фотографии товара')?>

                    <?php if ($model->gallery) {?>
                        <div class="row mix-grid">
                            <?php foreach ($model->gallery as $photo) {?>
                                <div class="col-md-4 col-sm-6" style="margin-bottom: 15px;">
                                    <div class="thumbnail">
                                        <img src="<?=$photo->getPhoto('product', '250x250');?>" style="height:200px; width: 100%; object-fit: cover;" alt="" class="img-responsive"/>
                                        <div class="caption text-center">
                                            <a href="<?=Yii::$app->urlManager->createUrl(['/admin/default/remove-photo', 'id'=>$photo->id, 'type'=>'product'])?>" class="remove-object btn btn-sm btn-danger">
                                                <i class="fa fa-trash"></i> Удалить
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            <?php }?>
                        </div>
                    <?php }?>
                    
                    <div class="help-block">
                        <i class="fa fa-info-circle"></i> Загрузите дополнительные фотографии товара. Рекомендуемый размер: 800x800px
                    </div>
                </div>
                <div class="box-footer">
                    <div class="text-right">
                        <?=Html::submitButton('<i class="fa fa-save"></i> Сохранить изменения', ['class'=>'btn btn-primary btn-lg'])?>
                        <a href="<?=Yii::$app->urlManager->createUrl(['/admin/product/view', 'id'=>$model->id])?>" class="btn btn-default btn-lg">
                            <i class="fa fa-eye"></i> Просмотр
                        </a>
                    </div>
                </div>
            </div>
                </div>
            </div>

        <?php ActiveForm::end();?>
    </section>
</div>

<script>
// Warehouse data grouped by shop
var warehousesByShop = <?= json_encode($warehousesByShop, JSON_UNESCAPED_UNICODE) ?>;

// IKPU functionality
var ikpuSearchTimeout;
var ikpuSuggestions = document.getElementById('ikpu-suggestions');
var ikpuSearchInput = document.getElementById('ikpu-code-input');
var ikpuCustomInput = document.getElementById('ikpu-custom-input');
var ikpuCustomName = document.getElementById('ikpu-custom-name');
var currentIkpuMode = 'search';

// Initialize IKPU mode switching
function switchIkpuMode(mode) {
    currentIkpuMode = mode;
    
    var searchMode = document.getElementById('ikpu-search-mode');
    var customMode = document.getElementById('ikpu-custom-mode');
    var searchBtn = document.getElementById('ikpu-mode-search');
    var customBtn = document.getElementById('ikpu-mode-custom');
    
    if (mode === 'search') {
        searchMode.style.display = 'block';
        customMode.style.display = 'none';
        searchBtn.className = 'btn btn-primary';
        customBtn.className = 'btn btn-default';
        
        // Clear custom inputs
        if (ikpuCustomInput) ikpuCustomInput.value = '';
        if (ikpuCustomName) ikpuCustomName.value = '';
        
        // Enable search input
        if (ikpuSearchInput) ikpuSearchInput.disabled = false;
    } else {
        searchMode.style.display = 'none';
        customMode.style.display = 'block';
        searchBtn.className = 'btn btn-default';
        customBtn.className = 'btn btn-primary';
        
        // Clear search input and hide suggestions
        if (ikpuSearchInput) ikpuSearchInput.value = '';
        if (ikpuSuggestions) ikpuSuggestions.style.display = 'none';
        
        // Enable custom inputs
        if (ikpuCustomInput) ikpuCustomInput.disabled = false;
        if (ikpuCustomName) ikpuCustomName.disabled = false;
    }
}

// Initialize mode based on existing data
document.addEventListener('DOMContentLoaded', function() {
    // Check if current IKPU is from database or custom
    var hasIkpuRelation = <?= $model->ikpu ? 'true' : 'false' ?>;
    var hasIkpuCode = <?= $model->ikpu_code ? 'true' : 'false' ?>;
    
    if (hasIkpuCode && !hasIkpuRelation) {
        // Custom IKPU
        switchIkpuMode('custom');
    } else {
        // Search mode (default)
        switchIkpuMode('search');
    }
});

// Search functionality for search mode
if (ikpuSearchInput) {
    ikpuSearchInput.addEventListener('input', function() {
        if (currentIkpuMode !== 'search') return;
        
        var query = this.value.trim();
        clearTimeout(ikpuSearchTimeout);
        
        if (query.length < 3) {
            ikpuSuggestions.style.display = 'none';
            return;
        }
        
        ikpuSearchTimeout = setTimeout(function() {
            searchIkpu(query);
        }, 300);
    });
}

// Custom code validation
if (ikpuCustomInput) {
    ikpuCustomInput.addEventListener('input', function() {
        var value = this.value;
        
        // Only allow digits
        this.value = value.replace(/[^0-9]/g, '');
        
        // Limit to 17 digits
        if (this.value.length > 17) {
            this.value = this.value.substring(0, 17);
        }
        
        // Visual feedback
        if (this.value.length === 17) {
            this.style.borderColor = '#5cb85c';
        } else {
            this.style.borderColor = '#ccc';
        }
    });
}

// Hide suggestions when clicking outside
document.addEventListener('click', function(e) {
    if (ikpuSearchInput && ikpuSuggestions && 
        !ikpuSearchInput.contains(e.target) && 
        !ikpuSuggestions.contains(e.target)) {
        ikpuSuggestions.style.display = 'none';
    }
});

function searchIkpu(query) {
    fetch('<?= \yii\helpers\Url::to(['/admin/ikpu/search']) ?>?q=' + encodeURIComponent(query))
        .then(response => response.json())
        .then(data => {
            ikpuSuggestions.innerHTML = '';
            
            if (data.length > 0) {
                data.forEach(function(item) {
                    var suggestion = document.createElement('a');
                    suggestion.className = 'list-group-item list-group-item-action';
                    suggestion.style.cursor = 'pointer';
                    suggestion.innerHTML = '<strong>' + item.id + '</strong><br><small>' + item.text.replace(item.id + ' - ', '') + '</small>';
                    
                    suggestion.addEventListener('click', function() {
                        ikpuSearchInput.value = item.id;
                        ikpuSuggestions.style.display = 'none';
                        
                        // Clear custom name field when selecting from search
                        if (ikpuCustomName) ikpuCustomName.value = '';
                    });
                    
                    ikpuSuggestions.appendChild(suggestion);
                });
                
                ikpuSuggestions.style.display = 'block';
            } else {
                ikpuSuggestions.style.display = 'none';
            }
        })
        .catch(error => {
            console.error('IKPU search error:', error);
            ikpuSuggestions.style.display = 'none';
        });
}

// Initialize warehouse filtering when page loads
document.addEventListener('DOMContentLoaded', function() {
    var shopSelect = document.getElementById('product-shop_id');
    var warehouseSelect = document.getElementById('product-stock_id');
    var currentWarehouseId = warehouseSelect.getAttribute('data-current-value');
    
    // Handle shop change
    shopSelect.addEventListener('change', function() {
        updateWarehouses(this.value, null);
    });
    
    // Function to update warehouses based on selected shop
    function updateWarehouses(shopId, selectedWarehouseId) {
        // Clear existing options
        warehouseSelect.innerHTML = '<option value="">Select warehouse</option>';
        
        if (shopId && warehousesByShop[shopId]) {
            var warehouses = warehousesByShop[shopId];
            
            // Add warehouses for selected shop
            warehouses.forEach(function(warehouse) {
                var option = document.createElement('option');
                option.value = warehouse.id;
                option.textContent = warehouse.name;
                
                // Select the warehouse if it matches current or selected value
                if (selectedWarehouseId && warehouse.id == selectedWarehouseId) {
                    option.selected = true;
                }
                
                warehouseSelect.appendChild(option);
            });
            
            // Enable warehouse dropdown
            warehouseSelect.disabled = false;
        } else {
            // No shop selected or no warehouses for this shop
            warehouseSelect.disabled = true;
        }
    }
    
    // Load warehouses on page load if shop is already selected
    if (shopSelect.value) {
        updateWarehouses(shopSelect.value, currentWarehouseId);
    }
});
</script>
