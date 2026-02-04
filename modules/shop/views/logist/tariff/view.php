<?php
use yii\helpers\ArrayHelper;

use app\widgets\admin_logist_menu\AdminLogistMenu;

$this->title = 'Логистическая компания';
$this->params['breadcrumbs'][] = $this->title;

$type = Yii::$app->request->get('type');
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
        <?php if (Yii::$app->session->hasFlash('logist_tariff_saved')) {?>
            <div class="callout callout-success text-center">
                <?=Yii::$app->session->getFlash('logist_tariff_saved');?>
            </div>
        <?php }?>
        <div class="row">
            <div class="col-sm-3">
                <?=AdminLogistMenu::widget();?>
            </div>
            <div class="col-sm-9">
                <div class="box box-info color-palette-box">
                    <div class="box-header">
                        <div class="pull-right">
                            <div class="btn-group">
                                <button type="button" class="btn btn-info dropdown-toggle" data-toggle="dropdown">
                                    <span class="fa fa-cog"></span>
                                </button>
                                <ul class="dropdown-menu pull-right">
                                    <li><a href="<?=Yii::$app->urlManager->createUrl(['/shop/logist/tariff', 'id'=>Yii::$app->request->get('id')]);?>">Список регионов</a></li>
                                    <li><a href="<?=Yii::$app->urlManager->createUrl(['/shop/logist/tariff-create', 'id'=>Yii::$app->request->get('id')]);?>">Добавить тариф</a></li>
                                    <li><a href="<?=Yii::$app->urlManager->createUrl(['/shop/logist/tariff-create', 'id'=>Yii::$app->request->get('id'), 'tariff_id'=>$model->id]);?>">Редактировать</a></li>
                                    <li><a href="<?=Yii::$app->urlManager->createUrl(['/shop/logist/tariff-remove', 'id'=>Yii::$app->request->get('id'), 'tariff_id'=>$model->id]);?>" class="remove-object">Удалить</a></li>
                                </ul>
                            </div>
                        </div>
                        Регион
                    </div>
                    <div class="box-body">
                        <table class="table table-striped">
                            <tr>
                                <td>ID</td>
                                <td><?=$model->id;?></td>
                            </tr>
                            <tr>
                                <td>Регион</td>
                                <td>
                                    <?=$model->region ? $model->region->name_ru : '-';?>
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>
                <div class="box box-info color-palette-box">
                    <div class="box-header">
                        Тарифы
                    </div>
                    <div class="box-body">
                        <?php if ($model->logistRegionPrices) {?>
                            <table class="table table-striped">
                                <tr>
                                    <th>ID</th>
                                    <th>Еденица измерения</th>
                                    <th>Количество</th>
                                    <th>Стоимость</th>
                                </tr>
                                <?php foreach ($model->logistRegionPrices as $item) {?>
                                    <tr>
                                        <td><?=$item->id ? $item->id : '-';?></td>
                                        <td><?=$item->unit ? $item->unit->name_ru : '-';?></td>
                                        <td><?=$item->unit_amount ? $item->unit_amount : '-';?></td>
                                        <td><?=$item->price ? $item->price : '-';?></td>
                                    </tr>
                                <?php }?>
                            </table>
                        <?php } else {?>
                            <div class="alert alert-warning text-center">Тарифы не указаны</div>
                        <?php }?>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>