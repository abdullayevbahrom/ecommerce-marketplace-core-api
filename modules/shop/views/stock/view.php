<?php
use yii\helpers\ArrayHelper;
use app\models\product\ProductFilter;

use app\widgets\admin_stock_menu\AdminStockMenu;
use app\widgets\admin_language_tab\AdminLanguageTab;

$this->title = 'Склад';
$this->params['breadcrumbs'][] = $this->title;

$type = Yii::$app->request->get('type');
?>

<div class="content-wrapper">
    <section class="content-header">
        <h1><?=$this->title;?></h1>

        <ol class="breadcrumb">
            <li><a href="<?=Yii::$app->urlManager->createUrl(['shop'])?>"><i class="fa fa-dashboard"></i> Главная</a></li>
            <li class="active"><?=$this->title;?></li>
        </ol>
    </section>
    <section class="content">
        <?php if (Yii::$app->session->hasFlash('stock_saved')) {?>
            <div class="callout callout-success text-center">
                <?=Yii::$app->session->getFlash('stock_saved');?>
            </div>
        <?php }?>
        <?php if (Yii::$app->session->hasFlash('stock_locked')) {?>
            <div class="callout callout-success text-center">
                <?=Yii::$app->session->getFlash('stock_locked');?>
            </div>
        <?php }?>
        <div class="row">
            <div class="col-sm-3">
                <?=AdminStockMenu::widget();?>
            </div>
            <div class="col-sm-9">
                <?=AdminLanguageTab::widget();?>
                <br/>
                <div class="box box-info color-palette-box">
                    <div class="box-body">
                        <table class="table table-striped">
                            <tr>
                                <td>ID</td>
                                <td><?=$model->id;?></td>
                            </tr>
                            <tr>
                                <td>Статус</td>
                                <td>
                                    <?php if ($model->status == 1) {?>
                                        <small class="label bg-green">Активный</small>
                                    <?php }?>
                                    <?php if ($model->status == 2) {?>
                                        <small class="label bg-red">Заблокирован</small>
                                    <?php }?>
                                </td>
                            </tr>
                            <tr>
                                <td>Название</td>
                                <td>
                                    <div class="lang-block lang-block-ru">
                                        <?=$model->name_ru ? $model->name_ru : '-';?>
                                    </div>
                                    <div class="lang-block lang-block-en">
                                        <?=$model->name_en ? $model->name_en : '-';?>
                                    </div>
                                    <div class="lang-block lang-block-uz">
                                        <?=$model->name_uz ? $model->name_uz : '-';?>
                                    </div>
                                </td>
                            </tr>
                            
                            <tr>
                                <td>Кол-во товаров</td>
                                <td><?=$model->products ? count($model->products) : 0;?></td>
                            </tr>
                            <tr>
                                <td>Дата</td>
                                <td><?=$model->date ? $model->date : '-';?></td>
                            </tr>
                        </table>
                    </div>
                </div>
                <?php if ($model->description_ru || $model->description_uz || $model->description_en) {?>
                    <div class="box box-info color-palette-box">
                        <div class="box-header">Описание</div>
                        <div class="box-body">
                            <div class="lang-block lang-block-ru">
                                <?=$model->description_ru;?>
                            </div>
                            <div class="lang-block lang-block-uz">
                                <?=$model->description_uz;?>
                            </div>
                            <div class="lang-block lang-block-en">
                                <?=$model->description_en;?>
                            </div>
                        </div>
                    </div>
                <?php }?>
                
                <?php if ($model->bts_region_id || $model->bts_city_id || $model->address) {?>
                    <div class="box box-info color-palette-box">
                        <div class="box-header">
                            <i class="fa fa-map-marker"></i> Информация о местоположении
                        </div>
                        <div class="box-body">
                            <div class="row">
                                <div class="col-sm-6">
                                    <table class="table table-condensed">
                                        <?php if ($model->bts_region_id) {?>
                                            <tr>
                                                <td><i class="fa fa-globe text-muted"></i> <strong>Регион:</strong></td>
                                                <td>
                                                    <div class="lang-block lang-block-ru">
                                                        <?= $model->getRegionName('ru') ?: '-' ?>
                                                    </div>
                                                    <div class="lang-block lang-block-en">
                                                        <?= $model->getRegionName('en') ?: '-' ?>
                                                    </div>
                                                    <div class="lang-block lang-block-uz">
                                                        <?= $model->getRegionName('uz') ?: '-' ?>
                                                    </div>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td><strong>ID региона BTS:</strong></td>
                                                <td><code><?= $model->bts_region_id ?></code></td>
                                            </tr>
                                        <?php }?>
                                        
                                        <?php if ($model->bts_city_id) {?>
                                            <tr>
                                                <td><i class="fa fa-building text-muted"></i> <strong>Город:</strong></td>
                                                <td>
                                                    <div class="lang-block lang-block-ru">
                                                        <?= $model->getCityName('ru') ?: '-' ?>
                                                    </div>
                                                    <div class="lang-block lang-block-en">
                                                        <?= $model->getCityName('en') ?: '-' ?>
                                                    </div>
                                                    <div class="lang-block lang-block-uz">
                                                        <?= $model->getCityName('uz') ?: '-' ?>
                                                    </div>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td><strong>ID города BTS:</strong></td>
                                                <td><code><?= $model->bts_city_id ?></code></td>
                                            </tr>
                                        <?php }?>
                                    </table>
                                </div>
                                <div class="col-sm-6">
                                    <?php if ($model->address) {?>
                                        <div class="form-group">
                                            <label><i class="fa fa-home text-muted"></i> <strong>Адрес:</strong></label>
                                            <div class="well well-sm">
                                                <?= nl2br(\yii\helpers\Html::encode($model->address)) ?>
                                            </div>
                                        </div>
                                    <?php }?>
                                    
                                    <div class="form-group">
                                        <label><i class="fa fa-map-o text-muted"></i> <strong>Полный адрес:</strong></label>
                                        <div class="well well-sm">
                                            <div class="lang-block lang-block-ru">
                                                <?= $model->getFullAddress('ru') ?: 'Не указано' ?>
                                            </div>
                                            <div class="lang-block lang-block-en">
                                                <?= $model->getFullAddress('en') ?: 'Не указано' ?>
                                            </div>
                                            <div class="lang-block lang-block-uz">
                                                <?= $model->getFullAddress('uz') ?: 'Не указано' ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php }?>
            </div>
        </div>
    </section>
</div>