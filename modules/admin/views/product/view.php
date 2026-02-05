<?php
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use app\models\product\ProductFilter;

use app\widgets\admin_product_menu\AdminProductMenu;
use app\widgets\admin_language_tab\AdminLanguageTab;

$this->title = 'Просмотр товара: ' . $model->name_ru;
$this->params['breadcrumbs'][] = ['label' => 'Товары', 'url' => ['/admin/product/']];
$this->params['breadcrumbs'][] = $this->title;

$type = Yii::$app->request->get('type');
?>

<div class="content-wrapper">
    <section class="content-header">
        <h1>
            <i class="fa fa-cube"></i> Просмотр товара
            <small>ID: <?= $model->id ?></small>
        </h1>

        <ol class="breadcrumb">
            <li><a href="<?=Yii::$app->urlManager->createUrl(['/admin/'])?>"><i class="fa fa-dashboard"></i> Главная</a></li>
            <li><a href="<?=Yii::$app->urlManager->createUrl(['/admin/product/'])?>"><i class="fa fa-cube"></i> Товары</a></li>
            <li class="active">Просмотр товара</li>
        </ol>
    </section>
    <section class="content">
        <?php if (Yii::$app->session->hasFlash('product_saved')) {?>
            <div class="callout callout-success text-center">
                <?=Yii::$app->session->getFlash('product_saved');?>
            </div>
        <?php }?>
        <?php if (Yii::$app->session->hasFlash('product_locked')) {?>
            <div class="callout callout-success text-center">
                <?=Yii::$app->session->getFlash('product_locked');?>
            </div>
        <?php }?>
        <div class="row">
            <div class="col-sm-3">
                <?=AdminProductMenu::widget();?>
            </div>
            <div class="col-sm-9">
                <?=AdminLanguageTab::widget();?>
                <br/>
                
                <!-- Product Header Info -->
                <div class="box box-primary">
                    <div class="box-header with-border">
                        <h3 class="box-title">
                            <i class="fa fa-info-circle"></i> Основная информация о товаре
                        </h3>
                        <div class="box-tools pull-right">
                            <?php if (Yii::$app->user->identity->role !== \app\models\user\User::ROLE_MODERATOR): ?>
                            <a href="<?=Yii::$app->urlManager->createUrl(['/admin/product/create', 'id'=>$model->id]);?>" class="btn btn-warning btn-sm">
                                <i class="fa fa-edit"></i> Редактировать
                            </a>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="box-body">
                        <div class="row">
                            <div class="col-md-3">
                                <div class="text-center">
                                    <img src="<?=$model->getPhoto('300x300');?>" class="img-responsive img-thumbnail" alt="Фото товара" style="max-height: 200px;">
                                    <p class="text-muted" style="margin-top: 10px;">
                                        <small><i class="fa fa-eye"></i> Просмотров: <?=$model->views;?></small>
                                    </p>
                                </div>
                            </div>
                            <div class="col-md-9">
                                <table class="table table-bordered">
                                    <tr>
                                        <td width="150"><strong>Название:</strong></td>
                                        <td><?= Html::encode($model->name_ru ?: 'Не указано') ?></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Статус:</strong></td>
                                        <td>
                                            <?php if ($model->status == 1) {?>
                                                <span class="label label-success"><i class="fa fa-check"></i> Активен</span>
                                            <?php } elseif ($model->status == 2) {?>
                                                <span class="label label-danger"><i class="fa fa-ban"></i> Заблокирован</span>
                                            <?php } else {?>
                                                <span class="label label-default">Неизвестно</span>
                                            <?php }?>
                                        </td>
                                    </tr>
                                    <?php if (Yii::$app->user->identity->role === \app\models\user\User::ROLE_MODERATOR && $model->status == 2): ?>
                                        <hr>
                                        <h4><i class="fa fa-comment"></i> Комментарий модератора</h4>

                                        <form method="post" action="<?=Yii::$app->urlManager->createUrl(['/admin/product/comment', 'id'=>$model->id])?>">

                                            <?= Html::csrfMetaTags() ?>

                                            <textarea
                                                name="comment"
                                                class="form-control"
                                                rows="4"
                                                required
                                                placeholder="Укажите причину, почему товар остаётся заблокированным"
                                            ></textarea>

                                            <br>

                                            <button type="submit" class="btn btn-warning btn-sm">
                                                <i class="fa fa-paper-plane"></i> Отправить комментарий
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                    <tr>
                                        <td><strong>Категория:</strong></td>
                                        <td><?= $model->category ? Html::encode($model->category->name_ru) : '<span class="text-muted">Не указана</span>' ?></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Бренд:</strong></td>
                                        <td><?= $model->brand ? Html::encode($model->brand->name_ru) : '<span class="text-muted">Не указан</span>' ?></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Цвет:</strong></td>
                                        <td>
                                            <?php if ($model->color) {?>
                                                <span class="badge" style="background-color: <?=$model->color->color;?>; color: white; padding: 5px 10px;">
                                                    <?= Html::encode($model->color->name_ru) ?>
                                                </span>
                                            <?php } else {?>
                                                <span class="text-muted">Не указан</span>
                                            <?php }?>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td><strong>Комментарий модератора:</strong></td>
                                        <td>
                                            <?php if (!empty($model->moderationComments)): ?>
                                                <?php $lastComment = end($model->moderationComments); ?>
                                                <?= Html::encode($lastComment->comment) ?>
                                                <br>
                                                <small class="text-muted">
                                                    <?= Yii::$app->formatter->asDatetime($lastComment->created_at) ?>
                                                </small>
                                            <?php else: ?>
                                                <span class="text-muted">Не указан</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>

                                    <tr>
                                        <td><strong>Рейтинг:</strong></td>
                                        <td>
                                            <?php for($i = 1; $i <= 5; $i++) {?>
                                                <i class="fa fa-star<?= $i <= $model->rating ? '' : '-o' ?>" style="color: #f39c12;"></i>
                                            <?php }?>
                                            <span class="text-muted">(<?= $model->rating ?>/5)</span>
                                        </td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                                
                <!-- Pricing and Commercial Info -->
                <div class="row">
                    <div class="col-md-6">
                        <div class="box box-success">
                            <div class="box-header with-border">
                                <h3 class="box-title"><i class="fa fa-rub"></i> Ценовая информация</h3>
                            </div>
                            <div class="box-body">
                                <table class="table table-striped">
                                    <tr>
                                        <td><strong>Цена:</strong></td>
                                        <td class="text-right">
                                            <?php if ($model->price) {?>
                                                <span class="text-success" style="font-size: 18px; font-weight: bold;">
                                                    <?= number_format($model->price, 0, '.', ' ') ?> сум
                                                </span>
                                            <?php } else {?>
                                                <span class="text-muted">Не указана</span>
                                            <?php }?>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td><strong>Цена мелкий опт:</strong></td>
                                        <td class="text-right"><?= $model->price_small ? number_format($model->price_small, 0, '.', ' ') . ' сум' : '<span class="text-muted">Не указана</span>' ?></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Цена оптом:</strong></td>
                                        <td class="text-right"><?= $model->price_opt ? number_format($model->price_opt, 0, '.', ' ') . ' сум' : '<span class="text-muted">Не указана</span>' ?></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Скидка:</strong></td>
                                        <td class="text-right">
                                            <?php if ($model->discount) {?>
                                                <span class="label label-warning"><?= $model->discount ?>%</span>
                                            <?php } else {?>
                                                <span class="text-muted">Нет</span>
                                            <?php }?>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td><strong>Мин. заказ:</strong></td>
                                        <td class="text-right"><?= $model->min_order ? number_format($model->min_order) . ' шт.' : '<span class="text-muted">Не указан</span>' ?></td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="box box-info">
                            <div class="box-header with-border">
                                <h3 class="box-title"><i class="fa fa-barcode"></i> Складская информация</h3>
                            </div>
                            <div class="box-body">
                                <table class="table table-striped">
                                    <tr>
                                        <td><strong>SKU:</strong></td>
                                        <td><?= Html::encode($model->sku ?: 'Не указан') ?></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Штрихкод:</strong></td>
                                        <td><?= Html::encode($model->barcode ?: 'Не указан') ?></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Количество:</strong></td>
                                        <td>
                                            <?php if ($model->amount) {?>
                                                <span class="label label-primary"><?= number_format($model->amount) ?> шт.</span>
                                            <?php } else {?>
                                                <span class="text-muted">Не указано</span>
                                            <?php }?>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td><strong>Магазин:</strong></td>
                                        <td><?= $model->shop_id ? 'ID: ' . $model->shop_id : '<span class="text-muted">Не указан</span>' ?></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Тип доставки:</strong></td>
                                        <td><?= $model->delivery ? Html::encode($model->delivery->name_ru) : '<span class="text-muted">Не указан</span>' ?></td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Product Variations -->
                <?php if ($model->products) {?>
                    <div class="box box-warning">
                        <div class="box-header with-border">
                            <h3 class="box-title"><i class="fa fa-copy"></i> Варианты товара</h3>
                            <span class="label label-primary pull-right"><?=count($model->products);?> вариантов</span>
                        </div>
                        <div class="box-body">
                            <div class="alert alert-info">
                                <i class="fa fa-info-circle"></i> Это все варианты товара, созданные с различными комбинациями типов/цветов на основе данного базового товара.
                            </div>
                            
                            <div class="table-responsive">
                                <table class="table table-striped table-bordered">
                                    <thead>
                                        <tr>
                                            <th width="60">ID</th>
                                            <th width="80">Фото</th>
                                            <th>Название товара</th>
                                            <th>Цвет</th>
                                            <th>Типы товара</th>
                                            <th>Цена</th>
                                            <th width="100">Действия</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <!-- Current Product (highlighted) -->
                                        <tr style="background-color: #f0f8ff; border-left: 4px solid #3c8dbc;">
                                            <td><strong><?=$model->id;?></strong></td>
                                            <td>
                                                <img src="<?=$model->getPhoto('100x100');?>" width="50" height="50" class="img-thumbnail" alt="Фото товара">
                                            </td>
                                            <td>
                                                <strong><?=$model->name_ru;?></strong>
                                                <br><small class="text-muted">Текущий товар</small>
                                            </td>
                                            <td>
                                                <?php if ($model->color) {?>
                                                    <span class="badge" style="background-color: <?=$model->color->color;?>; color: white; padding: 5px 10px;">
                                                        <?=$model->color->name_ru;?>
                                                    </span>
                                                <?php } else { ?>
                                                    <span class="text-muted">Нет цвета</span>
                                                <?php }?>
                                            </td>
                                            <td>
                                                <?php if ($model->productProductTypes) {?>
                                                    <?php 
                                                    $types = [];
                                                    foreach ($model->productProductTypes as $ppt) {
                                                        if ($ppt->productType) {
                                                            $value = $ppt->productTypeValue ? 
                                                                ($ppt->productTypeValue->display_value ?: $ppt->productTypeValue->value_ru) : 
                                                                $ppt->custom_value;
                                                            $types[] = $ppt->productType->name_ru . ': ' . $value;
                                                        }
                                                    }
                                                    ?>
                                                    <?php foreach ($types as $type) {?>
                                                        <span class="label label-success" style="margin-right: 5px; margin-bottom: 2px; display: inline-block;"><?=$type;?></span>
                                                    <?php }?>
                                                <?php } else { ?>
                                                    <span class="text-muted">Нет типов</span>
                                                <?php }?>
                                            </td>
                                            <td><strong><?=number_format($model->price, 0, '.', ' ');?> сум</strong></td>
                                            <td>
                                                <span class="label label-info">Текущий</span>
                                            </td>
                                        </tr>
                                        
                                        <!-- Other Variations -->
                                        <?php foreach ($model->products as $product) {?>
                                            <tr>
                                                <td><?=$product->id;?></td>
                                                <td>
                                                    <img src="<?=$product->getPhoto('100x100');?>" width="50" height="50" class="img-thumbnail" alt="Фото товара">
                                                </td>
                                                <td>
                                                    <a href="<?=Yii::$app->urlManager->createUrl(['/admin/product/view', 'id'=>$product->id]);?>" target="_blank">
                                                        <?=$product->name_ru;?>
                                                    </a>
                                                </td>
                                                <td>
                                                    <?php if ($product->color) {?>
                                                        <span class="badge" style="background-color: <?=$product->color->color;?>; color: white; padding: 5px 10px;">
                                                            <?=$product->color->name_ru;?>
                                                        </span>
                                                    <?php } else { ?>
                                                        <span class="text-muted">Нет цвета</span>
                                                    <?php }?>
                                                </td>
                                                <td>
                                                    <?php if ($product->productProductTypes) {?>
                                                        <?php 
                                                        $types = [];
                                                        foreach ($product->productProductTypes as $ppt) {
                                                            if ($ppt->productType) {
                                                                $value = $ppt->productTypeValue ? 
                                                                    ($ppt->productTypeValue->display_value ?: $ppt->productTypeValue->value_ru) : 
                                                                    $ppt->custom_value;
                                                                $types[] = $ppt->productType->name_ru . ': ' . $value;
                                                            }
                                                        }
                                                        ?>
                                                        <?php foreach ($types as $type) {?>
                                                            <span class="label label-default" style="margin-right: 5px; margin-bottom: 2px; display: inline-block;"><?=$type;?></span>
                                                        <?php }?>
                                                    <?php } else { ?>
                                                        <span class="text-muted">Нет типов</span>
                                                    <?php }?>
                                                </td>
                                                <td><?=number_format($product->price, 0, '.', ' ');?> сум</td>
                                                <td>
                                                    <div class="btn-group btn-group-xs">
                                                        <a href="<?=Yii::$app->urlManager->createUrl(['/admin/product/view', 'id'=>$product->id]);?>" class="btn btn-info btn-xs" title="Просмотр">
                                                            <i class="fa fa-eye"></i>
                                                        </a>
                                                        <?php if (Yii::$app->user->identity->role !== \app\models\user\User::ROLE_MODERATOR): ?>
                                                        <a href="<?=Yii::$app->urlManager->createUrl(['/admin/product/create', 'id'=>$product->id]);?>" class="btn btn-warning btn-xs" title="Редактировать">
                                                            <i class="fa fa-edit"></i>
                                                        </a>
                                                        <?php endif ?>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php }?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                <?php }?>

                <!-- IKPU Information -->
                <?php if ($model->ikpu_code || $model->ikpu_name) {?>
                    <div class="box box-info color-palette-box">
                        <div class="box-header with-border">
                            <h3 class="box-title"><i class="fa fa-barcode"></i> ИКПУ (Классификатор продукции)</h3>
                        </div>
                        <div class="box-body">
                            <table class="table table-striped">
                                <tr>
                                    <td width="150"><strong>Код ИКПУ:</strong></td>
                                    <td>
                                        <?php if ($model->ikpu_code) {?>
                                            <code style="font-size: 14px; background-color: #f4f4f4; padding: 5px 8px; border-radius: 3px;">
                                                <?=\yii\helpers\Html::encode($model->ikpu_code);?>
                                            </code>
                                            <?php if ($model->ikpu) {?>
                                                <a href="<?=Yii::$app->urlManager->createUrl(['/admin/ikpu/view', 'id'=>$model->ikpu->id]);?>" 
                                                   class="btn btn-xs btn-info" style="margin-left: 10px;" title="Просмотреть ИКПУ">
                                                    <i class="fa fa-external-link"></i> Подробнее
                                                </a>
                                            <?php }?>
                                        <?php } else {?>
                                            <span class="text-muted">Не указан</span>
                                        <?php }?>
                                    </td>
                                </tr>
                                <tr>
                                    <td><strong>Название ИКПУ:</strong></td>
                                    <td>
                                        <?php if ($model->ikpu_name) {?>
                                            <?=\yii\helpers\Html::encode($model->ikpu_name);?>
                                        <?php } elseif ($model->ikpu && $model->ikpu->name_ru) {?>
                                            <?=\yii\helpers\Html::encode($model->ikpu->name_ru);?>
                                        <?php } else {?>
                                            <span class="text-muted">Не указано</span>
                                        <?php }?>
                                    </td>
                                </tr>
                                <?php if ($model->ikpu && $model->ikpu->parent_code) {?>
                                    <tr>
                                        <td><strong>Родительский код:</strong></td>
                                        <td>
                                            <code style="font-size: 12px; background-color: #f9f9f9; padding: 3px 6px; border-radius: 3px;">
                                                <?=\yii\helpers\Html::encode($model->ikpu->parent_code);?>
                                            </code>
                                        </td>
                                    </tr>
                                <?php }?>
                                <?php if ($model->ikpu_code && !$model->ikpu) {?>
                                    <tr>
                                        <td><strong>Тип:</strong></td>
                                        <td>
                                            <span class="label label-warning">Пользовательский код</span>
                                            <small class="text-muted">(не найден в справочнике ИКПУ)</small>
                                        </td>
                                    </tr>
                                <?php } elseif ($model->ikpu) {?>
                                    <tr>
                                        <td><strong>Тип:</strong></td>
                                        <td>
                                            <span class="label label-success">Из справочника ИКПУ</span>
                                        </td>
                                    </tr>
                                <?php }?>
                            </table>
                        </div>
                    </div>
                <?php }?>

                <!-- Dimensions and Physical Properties -->
                <?php if ($model->weight || $model->height || $model->width || $model->length) {?>
                    <div class="box box-default">
                        <div class="box-header with-border">
                            <h3 class="box-title"><i class="fa fa-cube"></i> Габариты и физические свойства</h3>
                        </div>
                        <div class="box-body">
                            <div class="row">
                                <div class="col-md-3">
                                    <div class="info-box bg-yellow">
                                        <span class="info-box-icon"><i class="fa fa-balance-scale"></i></span>
                                        <div class="info-box-content">
                                            <span class="info-box-text">Вес</span>
                                            <span class="info-box-number"><?= $model->weight ? $model->weight . ' г' : 'Не указан' ?></span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="info-box bg-green">
                                        <span class="info-box-icon"><i class="fa fa-arrows-v"></i></span>
                                        <div class="info-box-content">
                                            <span class="info-box-text">Высота</span>
                                            <span class="info-box-number"><?= $model->height ? $model->height . ' см' : 'Не указана' ?></span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="info-box bg-blue">
                                        <span class="info-box-icon"><i class="fa fa-arrows-h"></i></span>
                                        <div class="info-box-content">
                                            <span class="info-box-text">Ширина</span>
                                            <span class="info-box-number"><?= $model->width ? $model->width . ' см' : 'Не указана' ?></span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="info-box bg-red">
                                        <span class="info-box-icon"><i class="fa fa-long-arrow-right"></i></span>
                                        <div class="info-box-content">
                                            <span class="info-box-text">Длина</span>
                                            <span class="info-box-number"><?= $model->length ? $model->length . ' см' : 'Не указана' ?></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php }?>
                
                <!-- Filters -->
                <?php if ($model->productFilters) {?>
                    <div class="box box-info">
                        <div class="box-header with-border">
                            <h3 class="box-title"><i class="fa fa-filter"></i> Фильтры товара</h3>
                        </div>
                        <div class="box-body">
                            <div class="row">
                                <?php foreach ($model->productFilters as $filter) {?>
                                    <?php if ($filter && $filter->filter) {?>
                                        <div class="col-md-6" style="margin-bottom: 15px;">
                                            <div class="form-group">
                                                <label class="control-label" style="font-weight: bold; color: #3c8dbc;">
                                                    <i class="fa fa-tag"></i> <?= Html::encode($filter->filter->name_ru) ?>
                                                </label>
                                                <div style="margin-top: 5px;">
                                                    <?php if ($filter->filter->type == 'checkbox') {?>
                                                        <?php $items = ArrayHelper::map(ProductFilter::find()->where(['product_id'=>$filter->product_id, 'filter_id'=>$filter->filter_id])->all(), 'value_ru', 'value_ru');?>
                                                        <?php if ($items) {?>
                                                            <?php foreach ($items as $item) {?>
                                                                <span class="label label-primary" style="margin-right: 5px; padding: 5px 8px;">
                                                                    <?= Html::encode($item) ?>
                                                                </span>
                                                            <?php }?>
                                                        <?php }?>
                                                    <?php } else {?>
                                                        <span class="label label-success" style="padding: 5px 8px;">
                                                            <?= Html::encode($filter->value_ru) ?>
                                                        </span>
                                                    <?php }?>
                                                </div>
                                            </div>
                                        </div>
                                    <?php }?>
                                <?php }?>
                            </div>
                        </div>
                    </div>
                <?php }?>
                <!-- Product Content -->
                <div class="row">
                    <?php if ($model->description_ru || $model->description_uz || $model->description_en) {?>
                        <div class="col-md-4">
                            <div class="box box-primary">
                                <div class="box-header with-border">
                                    <h3 class="box-title"><i class="fa fa-file-text-o"></i> Описание товара</h3>
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
                                                <?= $model->description_ru ?: '<span class="text-muted">Описание не указано</span>' ?>
                                            </div>
                                            <div class="tab-pane" id="desc-uz">
                                                <?= $model->description_uz ?: '<span class="text-muted">Tavsif ko\'rsatilmagan</span>' ?>
                                            </div>
                                            <div class="tab-pane" id="desc-en">
                                                <?= $model->description_en ?: '<span class="text-muted">Description not provided</span>' ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php }?>
                    
                    <?php if ($model->composition_ru || $model->composition_uz || $model->composition_en) {?>
                        <div class="col-md-4">
                            <div class="box box-success">
                                <div class="box-header with-border">
                                    <h3 class="box-title"><i class="fa fa-list-ul"></i> Состав товара</h3>
                                </div>
                                <div class="box-body">
                                    <div class="nav-tabs-custom">
                                        <ul class="nav nav-tabs">
                                            <li class="active"><a href="#comp-ru" data-toggle="tab">RU</a></li>
                                            <li><a href="#comp-uz" data-toggle="tab">UZ</a></li>
                                            <li><a href="#comp-en" data-toggle="tab">EN</a></li>
                                        </ul>
                                        <div class="tab-content">
                                            <div class="active tab-pane" id="comp-ru">
                                                <?= $model->composition_ru ?: '<span class="text-muted">Состав не указан</span>' ?>
                                            </div>
                                            <div class="tab-pane" id="comp-uz">
                                                <?= $model->composition_uz ?: '<span class="text-muted">Tarkib ko\'rsatilmagan</span>' ?>
                                            </div>
                                            <div class="tab-pane" id="comp-en">
                                                <?= $model->composition_en ?: '<span class="text-muted">Composition not provided</span>' ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php }?>
                    
                    <?php if ($model->recommendation_ru || $model->recommendation_uz || $model->recommendation_en) {?>
                        <div class="col-md-4">
                            <div class="box box-warning">
                                <div class="box-header with-border">
                                    <h3 class="box-title"><i class="fa fa-lightbulb-o"></i> Рекомендации</h3>
                                </div>
                                <div class="box-body">
                                    <div class="nav-tabs-custom">
                                        <ul class="nav nav-tabs">
                                            <li class="active"><a href="#rec-ru" data-toggle="tab">RU</a></li>
                                            <li><a href="#rec-uz" data-toggle="tab">UZ</a></li>
                                            <li><a href="#rec-en" data-toggle="tab">EN</a></li>
                                        </ul>
                                        <div class="tab-content">
                                            <div class="active tab-pane" id="rec-ru">
                                                <?= $model->recommendation_ru ?: '<span class="text-muted">Рекомендации не указаны</span>' ?>
                                            </div>
                                            <div class="tab-pane" id="rec-uz">
                                                <?= $model->recommendation_uz ?: '<span class="text-muted">Tavsiyalar ko\'rsatilmagan</span>' ?>
                                            </div>
                                            <div class="tab-pane" id="rec-en">
                                                <?= $model->recommendation_en ?: '<span class="text-muted">Recommendations not provided</span>' ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php }?>
                </div>
                <!-- Product Characteristics -->
                <?php if ($model->productProperties) {?>
                    <div class="box box-info">
                        <div class="box-header with-border">
                            <h3 class="box-title"><i class="fa fa-cogs"></i> Характеристики товара</h3>
                        </div>
                        <div class="box-body">
                            <div class="row">
                                <?php foreach ($model->productProperties as $property) {?>
                                    <div class="col-md-6" style="margin-bottom: 10px;">
                                        <div class="form-group">
                                            <label class="control-label" style="font-weight: bold;">
                                                <?= Html::encode($property->key_name) ?>:
                                            </label>
                                            <span class="form-control-static">
                                                <?= Html::encode($property->value_name) ?>
                                            </span>
                                        </div>
                                    </div>
                                <?php }?>
                            </div>
                        </div>
                    </div>
                <?php }?>
                <!-- Photo Gallery -->
                <?php if ($model->gallery) {?>
                    <div class="box box-primary">
                        <div class="box-header with-border">
                            <h3 class="box-title"><i class="fa fa-camera"></i> Фотогалерея</h3>
                            <span class="label label-primary pull-right"><?=count($model->gallery);?> фото</span>
                        </div>
                        <div class="box-body">
                            <div class="row">
                                <?php foreach ($model->gallery as $photo) {?>
                                    <div class="col-md-3 col-sm-4 col-xs-6" style="margin-bottom: 15px;">
                                        <div class="thumbnail">
                                            <img src="<?=$photo->getPhoto('product');?>" alt="Фото товара" class="img-responsive" style="height: 200px; object-fit: cover;">
                                            <div class="caption text-center">
                                                <a href="<?=$photo->getPhoto('product');?>" target="_blank" class="btn btn-primary btn-xs">
                                                    <i class="fa fa-search-plus"></i> Увеличить
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                <?php }?>
                            </div>
                        </div>
                    </div>
                <?php }?>
                <!-- Product Types -->
                <?php if ($model->productProductTypes) {?>
                    <div class="box box-success">
                        <div class="box-header with-border">
                            <h3 class="box-title"><i class="fa fa-tags"></i> Типы товара</h3>
                        </div>
                        <div class="box-body">
                            <?php 
                            // Group product types by type
                            $grouped_types = [];
                            foreach ($model->productProductTypes as $connection) {
                                if ($connection->productType) {
                                    $type_name = $connection->productType->name_ru;
                                    if (!isset($grouped_types[$type_name])) {
                                        $grouped_types[$type_name] = [];
                                    }
                                    if ($connection->productTypeValue) {
                                        $grouped_types[$type_name][] = $connection->productTypeValue->value_ru ?: $connection->productTypeValue->display_value ?: $connection->productTypeValue->value;
                                    } else {
                                        $grouped_types[$type_name][] = $connection->custom_value;
                                    }
                                }
                            }
                            ?>
                            
                            <?php if ($grouped_types) {?>
                                <div class="row">
                                    <?php foreach ($grouped_types as $type_name => $values) {?>
                                        <div class="col-md-6" style="margin-bottom: 20px;">
                                            <div class="form-group">
                                                <label class="control-label" style="font-weight: bold; color: #3c8dbc; font-size: 16px;">
                                                    <i class="fa fa-tag"></i> <?= Html::encode($type_name) ?>
                                                </label>
                                                <div style="margin-top: 10px;">
                                                    <?php foreach ($values as $value) {?>
                                                        <span class="label label-success" style="margin-right: 8px; margin-bottom: 5px; padding: 8px 12px; font-size: 12px; display: inline-block;">
                                                            <?= Html::encode($value) ?>
                                                        </span>
                                                    <?php }?>
                                                </div>
                                            </div>
                                        </div>
                                    <?php }?>
                                </div>
                            <?php } else {?>
                                <p class="text-muted">
                                    <i class="fa fa-info-circle"></i> Типы товара не найдены для данного продукта.
                                </p>
                            <?php }?>
                        </div>
                    </div>
                <?php }?>
                
                <!-- Product Colors -->
                <?php if ($model->productColors) {?>
                    <div class="box box-warning">
                        <div class="box-header with-border">
                            <h3 class="box-title"><i class="fa fa-paint-brush"></i> Цвета товара</h3>
                            <span class="label label-warning pull-right"><?=count($model->productColors);?> цветов</span>
                        </div>
                        <div class="box-body">
                            <div class="row">
                                <?php foreach ($model->productColors as $color) {?>
                                    <div class="col-md-3 col-sm-4 col-xs-6" style="margin-bottom: 15px;">
                                        <div class="text-center">
                                            <?php if ($color->color) {?>
                                                <div style="width: 80px; height: 80px; background-color: <?=$color->color->color;?>; border: 2px solid #ddd; border-radius: 50%; margin: 0 auto 10px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);"></div>
                                                <h5 style="margin: 0; font-weight: bold;">
                                                    <?= Html::encode($color->color->name_ru) ?>
                                                </h5>
                                                <small class="text-muted">
                                                    <?= Html::encode($color->color->color) ?>
                                                </small>
                                            <?php } else {?>
                                                <div style="width: 80px; height: 80px; background-color: #f5f5f5; border: 2px solid #ddd; border-radius: 50%; margin: 0 auto 10px; display: flex; align-items: center; justify-content: center;">
                                                    <i class="fa fa-question text-muted"></i>
                                                </div>
                                                <h5 style="margin: 0;">
                                                    <span class="text-muted">Цвет не определен</span>
                                                </h5>
                                            <?php }?>
                                        </div>
                                    </div>
                                <?php }?>
                            </div>
                        </div>
                    </div>
                <?php }?>
            </div>
        </div>
    </section>
</div>