<?php
use yii\helpers\Html;
use yii\bootstrap4\ActiveForm;
use yii\helpers\ArrayHelper;
use mihaildev\ckeditor\CKEditor;

use app\models\product\ProductFilter;

use app\models\filter\Filter;
use app\widgets\admin_language_tab\AdminLanguageTab;

$this->title = $model->isNewRecord ? 'Добавить товар' : 'Редактировать товар: ' . $model->name_ru;
$this->params['breadcrumbs'][] = ['label' => 'Товары', 'url' => ['/shop/product/']];
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
            <li><a href="<?=Yii::$app->urlManager->createUrl(['/shop/'])?>"><i class="fa fa-dashboard"></i> Главная</a></li>
            <li><a href="<?=Yii::$app->urlManager->createUrl(['/shop/product/'])?>"><i class="fa fa-cube"></i> Товары</a></li>
            <li class="active"><?= $model->isNewRecord ? 'Добавить' : 'Редактировать' ?></li>
        </ol>
    </section>
    <section class="content">
        <?php if (Yii::$app->session->hasFlash('error')) {?>
            <div class="callout callout-danger text-center">
                <?=Yii::$app->session->getFlash('error');?>
            </div>
        <?php }?>
        <?php if (Yii::$app->session->hasFlash('product_saved')) {?>
            <div class="callout callout-success text-center">
                <?=Yii::$app->session->getFlash('product_saved');?>
            </div>
        <?php }?>
        
        <!-- Display validation errors -->
        <?php if ($model->hasErrors()) {?>
            <div class="callout callout-danger">
                <h4><i class="icon fa fa-ban"></i> Ошибки валидации!</h4>
                <ul style="margin-bottom: 0;">
                    <?php foreach ($model->getErrors() as $field => $errors) {?>
                        <?php foreach ($errors as $error) {?>
                            <li><strong><?= $model->getAttributeLabel($field) ?>:</strong> <?= Html::encode($error) ?></li>
                        <?php }?>
                    <?php }?>
                </ul>
            </div>
        <?php }?>

        <?php $form = ActiveForm::begin(['options' => ['enctype' => 'multipart/form-data']]);?>
            <?=$form->field($model, 'token_key')->textInput()->input('hidden', ['value'=>Yii::$app->security->generateRandomString()])->label(false);?>
            <?php
                // Get current shop ID for the logged-in user
                $userId = Yii::$app->user->identity ? Yii::$app->user->identity->id : null;
                $currentUser = \app\models\user\User::findOne(['id', $userId]);
                $shopId = $currentUser ? $currentUser->shop_id : null;
            ?>
            <?=$form->field($model, 'shop_id')->hiddenInput(['value' => $shopId])->label(false);?>

            <!-- Main Product Information -->
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title">
                        <i class="fa fa-info-circle"></i> Основная информация о товаре
                    </h3>
                    <div class="box-tools pull-right">
                        <?php if (!$model->isNewRecord) { ?>
                            <a href="<?=Yii::$app->urlManager->createUrl(['/shop/product/view', 'id'=>$model->id])?>" class="btn btn-info btn-sm">
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
                                            <a href="<?=Yii::$app->urlManager->createUrl(['/shop/default/remove-photo', 'id'=>$model->image->id])?>" class="edit-user remove-object btn btn-sm btn-danger">
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
                                <div class="col-sm-6">
                                    <?=$form->field($model, 'brand_id')->dropDownList(
                                        $brands,
                                        [
                                            'class'=>'form-control select2',
                                            'prompt'=>'Выберите бренд'
                                        ]
                                    )->label('Бренд');?>
                                </div>
                                <div class="col-sm-6">
                                    <?=$form->field($model, 'stock_id')->dropDownList(
                                        $stocks,
                                        [
                                            'class'=>'form-control select2',
                                            'prompt'=>'Выберите склад'
                                        ]
                                    )->label('Склад');?>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-sm-6">
                                    <?=$form->field($model, 'delivery_id')->dropDownList(
                                        $deliveries,
                                        [
                                            'class'=>'form-control select2',
                                            'prompt'=>'Выберите тип доставки'
                                        ]
                                    )->label('Тип доставки');?>
                                </div>
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
                        </div>
                    </div>
                </div>
            </div>

            <!-- Photo Gallery -->
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title">
                        <i class="fa fa-camera"></i> Галерея фото товара
                    </h3>
                    <?php if ($model->gallery) {?>
                        <span class="label label-primary pull-right"><?=count($model->gallery);?> фото</span>
                    <?php }?>
                </div>
                <div class="box-body">
                    <?=$form->field($model, 'imageGallery[]', ['template' => '{input}{error}'])->fileInput([
                        'multiple' => true, 
                        'accept' => 'image/*', 
                        'class'=>'file-upload-ajax-gallery'
                    ])->label(false)?>
                    <div class="help-block">
                        <small><i class="fa fa-info-circle"></i> Выберите несколько изображений для галереи товара</small>
                    </div>

                    <?php if ($model->gallery) {?>
                        <div class="row" style="margin-top: 15px;">
                            <?php foreach ($model->gallery as $photo) {?>
                                <div class="col-md-2 col-sm-3 col-xs-4" style="margin-bottom: 15px;">
                                    <div class="thumbnail">
                                        <img src="<?=$photo->getPhoto('product', '250x250');?>" style="height:120px; object-fit: cover;" alt="" class="img-responsive"/>
                                        <div class="caption text-center">
                                            <a href="<?=Yii::$app->urlManager->createUrl(['/shop/default/remove-photo', 'id'=>$photo->id, 'type'=>'product'])?>" class="remove-object btn btn-xs btn-danger">
                                                <i class="fa fa-trash"></i>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            <?php }?>
                        </div>
                    <?php }?>
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
                        <div class="col-sm-3">
                            <?=$form->field($model, 'weight')->textInput([
                                'type' => 'number',
                                'step' => '0.01',
                                'min' => '0',
                                'placeholder' => '0.00'
                            ])->label('Вес (грамм)');?>
                        </div>
                        <div class="col-sm-3">
                            <?=$form->field($model, 'height')->textInput([
                                'type' => 'number',
                                'step' => '0.01',
                                'min' => '0',
                                'placeholder' => '0.00'
                            ])->label('Высота (см)');?>
                        </div>
                        <div class="col-sm-3">
                            <?=$form->field($model, 'width')->textInput([
                                'type' => 'number',
                                'step' => '0.01',
                                'min' => '0',
                                'placeholder' => '0.00'
                            ])->label('Ширина (см)');?>
                        </div>
                        <div class="col-sm-3">
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
                                        'filebrowserUploadUrl' => '/shop/images'
                                    ]
                                ]);?>
                            </div>
                            <div class="tab-pane" id="desc-uz">
                                <?=$form->field($model, 'description_uz', ['template' => '{input}{error}'])->widget(CKEditor::className(),[
                                    'editorOptions' => [
                                        'preset' => 'advanced',
                                        'inline' => false,
                                        'height' => 200,
                                        'filebrowserUploadUrl' => '/shop/images'
                                    ]
                                ]);?>
                            </div>
                            <div class="tab-pane" id="desc-en">
                                <?=$form->field($model, 'description_en', ['template' => '{input}{error}'])->widget(CKEditor::className(),[
                                    'editorOptions' => [
                                        'preset' => 'advanced',
                                        'inline' => false,
                                        'height' => 200,
                                        'filebrowserUploadUrl' => '/shop/images'
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
                                            <a href="javascript:;" class="btn btn-danger remove-block" title="Удалить">
                                                <i class="fa fa-trash"></i>
                                            </a>
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
