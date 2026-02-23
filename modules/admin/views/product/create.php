<?php
use yii\helpers\Html;
use yii\bootstrap4\ActiveForm;
use yii\helpers\ArrayHelper;
use mihaildev\ckeditor\CKEditor;

use app\models\product\ProductFilter;

use app\models\filter\Filter;
use app\widgets\admin_language_tab\AdminLanguageTab;

$this->title = $model->isNewRecord ? 'Добавить товар' : 'Редактировать товар: ' . $model->name_ru;
$this->params['breadcrumbs'][] = ['label' => 'Товары', 'url' => ['/admin/product/']];
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="content-wrapper">
    <section class="content-header">
        <h1>
            <i class="fa fa-<?= $model->isNewRecord ? 'plus' : 'edit' ?>"></i> 
            <?= $model->isNewRecord ? 'Добавить товар' : 'Редактировать товар' ?>
            <?php if (!$model->isNewRecord) { ?>
                <small>ID: <?= $model->id ?></small>
            <?php } ?>
        </h1>

        <ol class="breadcrumb">
            <li><a href="<?=Yii::$app->urlManager->createUrl(['/admin/'])?>"><i class="fa fa-dashboard"></i> Главная</a></li>
            <li><a href="<?=Yii::$app->urlManager->createUrl(['/admin/product/'])?>"><i class="fa fa-cube"></i> Товары</a></li>
            <li class="active"><?= $model->isNewRecord ? 'Добавить' : 'Редактировать' ?></li>
        </ol>
    </section>
    <section class="content">
        <?php $form = ActiveForm::begin(['options' => ['enctype' => 'multipart/form-data']]);?>
            <?=$form->field($model, 'token_key')->textInput()->input('hidden', ['value'=>Yii::$app->security->generateRandomString()])->label(false);?>
            <!-- Main Product Information -->
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title">
                        <i class="fa fa-info-circle"></i> Основная информация о товаре
                    </h3>
                    <div class="box-tools pull-right">
                        <?php if (!$model->isNewRecord) { ?>
                            <a href="<?=Yii::$app->urlManager->createUrl(['/admin/product/view', 'id'=>$model->id])?>" class="btn btn-info btn-sm">
                                <i class="fa fa-eye"></i> Просмотр
                            </a>
                        <?php } ?>
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
                        <div class="col-sm-3">
                            <?=$form->field($model, 'unit_id')->dropDownList(
                                $units,
                                [
                                    'class'=>'form-control select2',
                                    'prompt'=>'Выберите ед. измерения'
                                ]
                            )->label('Единица измерения');?>
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
                        <div class="col-sm-6">
                            <?=$form->field($model, 'sku')->textInput([
                                'placeholder' => 'Введите SKU товара'
                            ])->label('SKU');?>
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
                        <div class="col-sm-3">
                            <?=$form->field($model, 'currency_id')->dropDownList(
                                $currencies,
                                [
                                    'class'=>'form-control select2',
                                    'prompt'=>'Выберите валюту'
                                ]
                            )->label('Тип валюты');?>
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
                        <!-- Main Category -->
                        <?=$form->field($model, 'category_id')->dropDownList(
                            $categories,
                            [
                                'class'=>'category-item form-control select2',
                                'prompt'=>'Выберите категорию',
                                'options' => [$tree[0] => ['selected'=>'selected']]
                            ]
                        )->label('Основная категория <span class="text-danger">*</span>');?>
                        
                        <!-- Subcategories -->
                        <?php if ($current_categories) {?>
                            <?php foreach ($current_categories as $key => $category) {?>
                                <?php if ($category) {?>
                                    <?=$form->field($model, 'sub_category_id[]', ['options'=>['class'=>['category-group form-group']]])->dropDownList(
                                        $category,
                                        ['class'=>'category-item form-control select2', 'prompt'=>'Выберите подкатегорию', 'options' => [$tree[$key+1] => ['selected'=>'selected']]]
                                    )->label('Подкатегория ' . ($key + 1));?>
                                <?php }?>
                            <?php }?>
                        <?php }?>
                    </div>
                </div>
            </div>
            
            <!-- IKPU Section -->
            <div class="box box-default">
                <div class="box-header with-border">
                    <h3 class="box-title">
                        <i class="fa fa-barcode"></i> ИКПУ (Классификатор продукции)
                    </h3>
                </div>
                <div class="box-body">
                    <!-- Toggle buttons -->
                    <div class="btn-group btn-group-sm" style="margin-bottom: 15px;">
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
                            'autocomplete' => 'off'
                        ])->label('Код ИКПУ') ?>
                        <div id="ikpu-suggestions" class="list-group" style="display: none; position: absolute; z-index: 1000; max-height: 200px; overflow-y: auto; width: 100%;"></div>
                        <div class="help-block">
                            <i class="fa fa-info-circle"></i> Начните печатать для поиска в справочнике ИКПУ
                        </div>
                    </div>
                    
                    <!-- Custom mode -->
                    <div id="ikpu-custom-mode" style="display: none;">
                        <div class="row">
                            <div class="col-sm-6">
                                <?= $form->field($model, 'ikpu_code', ['template' => '{input}{error}'])->textInput([
                                    'placeholder' => 'Введите 17-значный код ИКПУ',
                                    'id' => 'ikpu-custom-input',
                                    'class' => 'form-control',
                                    'pattern' => '[0-9]{17}',
                                    'title' => 'Код ИКПУ должен содержать ровно 17 цифр'
                                ])->label('Код ИКПУ') ?>
                            </div>
                            <div class="col-sm-6">
                                <?= $form->field($model, 'ikpu_name', ['template' => '{input}{error}'])->textInput([
                                    'placeholder' => 'Введите название для этого кода ИКПУ',
                                    'id' => 'ikpu-custom-name',
                                    'class' => 'form-control'
                                ])->label('Название ИКПУ') ?>
                            </div>
                        </div>
                        <div class="help-block">
                            <i class="fa fa-warning text-orange"></i> Пользовательский код ИКПУ (не из официального справочника)
                        </div>
                    </div>
                </div>
            </div>

                    <!-- Product Filters -->
                    <?php if ($model->productFilters) {?>
                        <div class="box box-success">
                            <div class="box-header with-border">
                                <h3 class="box-title">
                                    <i class="fa fa-filter"></i> Фильтры товара
                                </h3>
                            </div>
                            <div class="box-body">
                                <div class="row">
                                    <?php foreach ($model->productFilters as $key => $value) {?>
                                        <?php if ($value->filter) {?>
                                            <div class="col-md-6" style="margin-bottom: 15px;">
                                                <?php if ($value->filter->type == 'input') {?>
                                                    <?=$form->field($model, 'filters['.$value->filter->id.']')->textInput([
                                                        'value'=>$value->value_ru,
                                                        'placeholder' => 'Введите значение'
                                                    ])->label($value->filter->name_ru);?>
                                                <?php }?>
                                                <?php if ($value->filter->type == 'select') {?>
                                                    <?php $filter_childs = ArrayHelper::map(Filter::find()->where(['parent_id'=>$value->filter->id])->all(), 'value_ru', 'value_ru');?>
                                                    <?php if ($filter_childs) {?>
                                                        <?=$form->field($model, 'filters['.$value->filter->id.']')->dropDownList(
                                                            $filter_childs,
                                                            [
                                                                'class'=>'form-control select2',
                                                                'prompt'=>'Выберите значение',
                                                                'options' => [$value->value_ru => ['selected'=>'selected']]
                                                            ]
                                                        )->label($value->filter->name_ru);?>
                                                    <?php }?>
                                                <?php }?>
                                                <?php if ($value->filter->type == 'checkbox') {?>
                                                    <div class="form-group">
                                                        <label class="control-label"><?= Html::encode($value->filter->name_ru) ?></label>
                                                        <div>
                                                            <?php $filter_childs_ids = ArrayHelper::map(ProductFilter::find()->where(['product_id'=>$value->product_id, 'filter_id'=>$value->filter_id])->all(), 'value_ru', 'value_ru');?>
                                                            <?php $filter_childs = Filter::find()->where(['parent_id'=>$value->filter->id])->all();?>
                                                            <?php foreach ($filter_childs as $k => $v) {?>
                                                                <label class="checkbox-inline" style="margin-right: 15px;">
                                                                    <input type="checkbox" name="Product[filters][<?=$value->filter->id?>][<?=$k?>]" value="<?=$v->value_ru?>" <?= array_key_exists($v->value_ru, $filter_childs_ids) ? 'checked' : '' ?>>
                                                                    <?= Html::encode($v->value_ru) ?>
                                                                </label>
                                                            <?php }?>
                                                        </div>
                                                    </div>
                                                <?php }?>
                                            </div>
                                        <?php }?>
                                    <?php }?>
                                </div>
                            </div>
                        </div>
                    <?php }?>

            <!-- Dimensions Section -->
            <div class="box box-default">
                <div class="box-header with-border">
                    <h3 class="box-title">
                        <i class="fa fa-cube"></i> Габариты и физические свойства
                    </h3>
                </div>
                <div class="box-body">
                    <div class="row">
                        <div class="col-sm-6">
                            <?=$form->field($model, 'weight')->textInput([
                                'type' => 'number',
                                'step' => '0.01',
                                'min' => '0',
                                'placeholder' => '0.00'
                            ])->label('Вес (грамм)');?>
                        </div>
                        <div class="col-sm-6">
                            <?=$form->field($model, 'height')->textInput([
                                'type' => 'number',
                                'step' => '0.01',
                                'min' => '0',
                                'placeholder' => '0.00'
                            ])->label('Высота (см)');?>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-sm-6">
                            <?=$form->field($model, 'width')->textInput([
                                'type' => 'number',
                                'step' => '0.01',
                                'min' => '0',
                                'placeholder' => '0.00'
                            ])->label('Ширина (см)');?>
                        </div>
                        <div class="col-sm-6">
                            <?=$form->field($model, 'length')->textInput([
                                'type' => 'number',
                                'step' => '0.01',
                                'min' => '0',
                                'placeholder' => '0.00'
                            ])->label('Длина (см)');?>
                        </div>
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
                                ]);?>
                            </div>
                            <div class="tab-pane" id="desc-uz">
                                <?=$form->field($model, 'description_uz', ['template' => '{input}{error}'])->widget(CKEditor::className(),[
                                    'editorOptions' => [
                                        'preset' => 'advanced',
                                        'inline' => false,
                                        'height' => 200,
                                        'filebrowserUploadUrl' => '/admin/images'
                                    ]
                                ]);?>
                            </div>
                            <div class="tab-pane" id="desc-en">
                                <?=$form->field($model, 'description_en', ['template' => '{input}{error}'])->widget(CKEditor::className(),[
                                    'editorOptions' => [
                                        'preset' => 'advanced',
                                        'inline' => false,
                                        'height' => 200,
                                        'filebrowserUploadUrl' => '/admin/images'
                                    ]
                                ]);?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Product Types Container (for dynamic loading) -->
            <div id="product-types-container">
                <!-- Product types will be loaded here dynamically when category is selected -->
            </div>

            <!-- Product Characteristics -->
            <div class="box box-info">
                <div class="box-header with-border">
                    <h3 class="box-title">
                        <i class="fa fa-cogs"></i> Характеристики товара
                    </h3>
                </div>
                <div class="box-body">
                    <div id="item-form">
                        <?php if ($model->productProperties) {?>
                            <?php foreach ($model->productProperties as $v) {?>
                                <div class="item-block">
                                    <div class="row">
                                        <div class="col-sm-5">
                                            <?=$form->field($model, 'properties_data[key_name][]', ['template' => '{input}{error}'])->textInput([
                                                'value'=>$v->key_name, 
                                                'placeholder'=>'Введите название характеристики'
                                            ]);?>
                                        </div>
                                        <div class="col-sm-5">
                                            <?=$form->field($model, 'properties_data[value_name][]', ['template' => '{input}{error}'])->textInput([
                                                'value'=>$v->value_name, 
                                                'placeholder'=>'Введите значение характеристики'
                                            ]);?>
                                        </div>
                                        <div class="col-sm-2">
                                            <label class="control-label">&nbsp;</label>
                                            <div>
                                                <a href="javascript:;" class="btn btn-danger remove-block" title="Удалить">
                                                    <i class="fa fa-trash"></i>
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php }?>
                        <?php } else {?>
                            <div class="item-block">
                                <div class="row">
                                    <div class="col-sm-5">
                                        <?=$form->field($model, 'properties_data[key_name][]', ['template' => '{input}{error}'])->textInput([
                                            'placeholder'=>'Введите название характеристики'
                                        ])->label('Название');?>
                                    </div>
                                    <div class="col-sm-5">
                                        <?=$form->field($model, 'properties_data[value_name][]', ['template' => '{input}{error}'])->textInput([
                                            'placeholder'=>'Введите значение характеристики'
                                        ])->label('Значение');?>
                                    </div>
                                    <div class="col-sm-2">
                                        <label class="control-label">&nbsp;</label>
                                        <div>
                                            <a href="javascript:;" class="btn btn-danger remove-block" title="Удалить">
                                                <i class="fa fa-trash"></i>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php }?>
                    </div>

                    <div class="text-center" style="margin-top: 15px;">
                        <a href="javascript:;" class="add-variant-item btn btn-success btn-sm">
                            <i class="fa fa-plus"></i> Добавить характеристику
                        </a>
                    </div>
                </div>
                <?php if (!$colors_object) {?>
                    <div class="box-footer">
                        <div class="text-right">
                            <?=Html::submitButton('<i class="fa fa-check-square-o"></i> Сохранить', ['class'=>'btn btn-primary btn-lg'])?>
                        </div>
                    </div>
                <?php }?>
            </div>

            <!-- Variants Section -->
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title">
                        <i class="fa fa-list"></i> Варианты товара
                    </h3>
                </div>
                <div class="box-body">
                    <div id="variants-container">
                        <p class="text-muted">Выберите цвета и типы товаров для генерации вариантов.</p>
                    </div>
                </div>
            </div>

            <!-- Product Colors -->
            <?php if ($colors_object) {?>
                <div class="box box-warning">
                    <div class="box-header with-border">
                        <h3 class="box-title">
                            <i class="fa fa-paint-brush"></i> Цвета товара
                        </h3>
                    </div>
                    <div class="box-body">
                        <div class="row">
                            <?php foreach ($colors_object as $key => $color) {?>
                                <?php if ($color) {?>
                                    <div class="col-md-3 col-sm-4 col-xs-6" style="margin-bottom: 15px;">
                                        <label style="display: flex; align-items: center; padding: 12px; border: 1px solid #ddd; border-radius: 4px; margin: 0; cursor: pointer; transition: background-color 0.2s;">
                                            <input type="checkbox" name="Product[colors][]" value="<?=$color->id;?>" style="margin: 0; margin-right: 10px; transform: scale(1.2);"/>
                                            <span style="font-weight: 500; color: #333;"><?=Html::encode($color->name_ru);?></span>
                                        </label>
                                    </div>
                                <?php }?>
                            <?php }?>
                        </div>
                    </div>
                    <div class="box-footer">
                        <div class="text-right">
                            <?=Html::submitButton('<i class="fa fa-check-square-o"></i> Сохранить', ['class'=>'btn btn-primary btn-lg'])?>
                        </div>
                    </div>
                </div>
            <?php }?>

 
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

console.log('🚀 SCRIPT LOADED: Warehouse filtering script started');
console.log('📊 DATA CHECK: warehousesByShop =', warehousesByShop);

// Simple test function
function testElements() {
    var shopSelect = document.getElementById('product-shop_id');
    var warehouseSelect = document.getElementById('product-stock_id');
    
    console.log('🔍 ELEMENT CHECK: Shop select =', shopSelect);
    console.log('🔍 ELEMENT CHECK: Warehouse select =', warehouseSelect);
    
    if (shopSelect) {
        console.log('✅ Shop select found, current value =', shopSelect.value);
    } else {
        console.error('❌ Shop select NOT FOUND');
    }
    
    if (warehouseSelect) {
        console.log('✅ Warehouse select found, current value =', warehouseSelect.value);
    } else {
        console.error('❌ Warehouse select NOT FOUND');
    }
    
    return shopSelect && warehouseSelect;
}

// Initialize warehouse filtering when page loads
document.addEventListener('DOMContentLoaded', function() {
    console.log('🔧 DOM LOADED: Starting warehouse filtering initialization');
    
    // Test elements first
    if (!testElements()) {
        console.error('❌ FAILED: Required elements not found');
        return;
    }
    
    var shopSelect = document.getElementById('product-shop_id');
    var warehouseSelect = document.getElementById('product-stock_id');
    
    // Function to update warehouses based on selected shop
    function updateWarehouses(shopId) {
        console.log('🔄 UPDATE: Called with shopId =', shopId, 'type =', typeof shopId);
        
        // Clear existing options
        warehouseSelect.innerHTML = '<option value="">Select warehouse</option>';
        console.log('🧹 CLEARED: Warehouse options cleared');
        
        if (shopId && shopId !== '' && warehousesByShop[shopId]) {
            var warehouses = warehousesByShop[shopId];
            console.log('✅ FOUND: Warehouses for shop ' + shopId + ' =', warehouses);
            
            // Add warehouses for selected shop
            warehouses.forEach(function(warehouse, index) {
                var option = document.createElement('option');
                option.value = warehouse.id;
                option.textContent = warehouse.name;
                warehouseSelect.appendChild(option);
                console.log('➕ ADDED: Option #' + index + ' - ' + warehouse.name + ' (ID: ' + warehouse.id + ')');
            });
            
            // Enable warehouse dropdown
            warehouseSelect.disabled = false;
            console.log('✅ ENABLED: Warehouse dropdown with ' + warehouses.length + ' options');
        } else {
            // No shop selected or no warehouses for this shop
            warehouseSelect.disabled = true;
            console.log('⚠️ NO MATCH: No warehouses for shop ID "' + shopId + '"');
            console.log('📋 AVAILABLE: Shop IDs in data =', Object.keys(warehousesByShop));
        }
    }
    
    // Handle shop change with detailed logging
    shopSelect.addEventListener('change', function(event) {
        console.log('🎯 CHANGE EVENT: Shop changed!');
        console.log('   - Event target =', event.target);
        console.log('   - Selected value =', this.value);
        console.log('   - Selected text =', this.options[this.selectedIndex].text);
        
        updateWarehouses(this.value);
    });
    
    console.log('🎧 LISTENER: Shop change event listener added');
    
    // Load warehouses on page load if shop is already selected
    if (shopSelect.value && shopSelect.value !== '') {
        console.log('🔄 INIT LOAD: Shop already selected =', shopSelect.value);
        updateWarehouses(shopSelect.value);
    } else {
        console.log('⭕ INIT LOAD: No shop selected on page load');
    }
    
    console.log('✅ COMPLETE: Warehouse filtering initialized successfully');
});

// Test function you can call manually from console
window.testWarehouseFiltering = function() {
    console.log('🧪 MANUAL TEST: Running warehouse filtering test');
    testElements();
    
    var shopSelect = document.getElementById('product-shop_id');
    if (shopSelect && shopSelect.value) {
        console.log('🔄 MANUAL TEST: Testing with current shop value =', shopSelect.value);
        updateWarehouses(shopSelect.value);
    } else {
        console.log('⚠️ MANUAL TEST: No shop selected to test with');
    }
};
</script>

