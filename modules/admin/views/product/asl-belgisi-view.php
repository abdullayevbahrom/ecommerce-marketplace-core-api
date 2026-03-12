<?php
use yii\helpers\Html;

$this->title = 'ASL Belgisi — ' . Html::encode($entry->gtin);
$this->params['breadcrumbs'][] = ['label' => 'Товары', 'url' => ['/admin/product/']];
$this->params['breadcrumbs'][] = ['label' => 'ASL Belgisi', 'url' => ['/admin/product/asl-belgisi']];
$this->params['breadcrumbs'][] = $entry->gtin;
?>

<div class="content-wrapper">
    <section class="content-header">
        <h1>
            <i class="fa fa-check-circle"></i> ASL Belgisi
            <small>GTIN: <?= Html::encode($entry->gtin) ?></small>
        </h1>
        <ol class="breadcrumb">
            <li><a href="<?=Yii::$app->urlManager->createUrl(['/admin/'])?>"><i class="fa fa-dashboard"></i> Главная</a></li>
            <li><a href="<?=Yii::$app->urlManager->createUrl(['/admin/product/'])?>"><i class="fa fa-cube"></i> Товары</a></li>
            <li><a href="<?=Yii::$app->urlManager->createUrl(['/admin/product/asl-belgisi'])?>">ASL Belgisi</a></li>
            <li class="active"><?= Html::encode($entry->gtin) ?></li>
        </ol>
    </section>
    <section class="content">
        <div class="row">
            <!-- ASL Belgisi Info -->
            <div class="col-md-5">
                <div class="box box-<?= $entry->status === 'PUBLISHED' ? 'success' : 'warning' ?>">
                    <div class="box-header with-border">
                        <h3 class="box-title"><i class="fa fa-info-circle"></i> Информация ASL Belgisi</h3>
                    </div>
                    <div class="box-body">
                        <table class="table table-striped">
                            <tr>
                                <td width="180"><strong>GTIN:</strong></td>
                                <td><code style="font-size: 14px;"><?= Html::encode($entry->gtin) ?></code></td>
                            </tr>
                            <tr>
                                <td><strong>Статус:</strong></td>
                                <td>
                                    <?php if ($entry->status === 'PUBLISHED') {?>
                                        <span class="label label-success"><i class="fa fa-check"></i> <?= Html::encode($entry->status) ?></span>
                                    <?php } elseif ($entry->status) {?>
                                        <span class="label label-warning"><?= Html::encode($entry->status) ?></span>
                                    <?php } else {?>
                                        <span class="label label-default">N/A</span>
                                    <?php }?>
                                </td>
                            </tr>
                            <?php if ($entry->asl_product_id) {?>
                            <tr>
                                <td><strong>ASL Product ID:</strong></td>
                                <td><code><?= Html::encode($entry->asl_product_id) ?></code></td>
                            </tr>
                            <?php }?>
                            <tr>
                                <td><strong>Название (RU):</strong></td>
                                <td><?= Html::encode($entry->product_name_ru ?: '-') ?></td>
                            </tr>
                            <tr>
                                <td><strong>Название (UZ):</strong></td>
                                <td><?= Html::encode($entry->product_name_uz ?: '-') ?></td>
                            </tr>
                            <tr>
                                <td><strong>ИНН:</strong></td>
                                <td><?= Html::encode($entry->inn ?: '-') ?></td>
                            </tr>
                            <tr>
                                <td><strong>Группа товара:</strong></td>
                                <td>
                                    <?php if ($entry->product_group) {?>
                                        <span class="label label-default"><?= Html::encode($entry->product_group) ?></span>
                                    <?php } else {?>
                                        -
                                    <?php }?>
                                </td>
                            </tr>
                            <tr>
                                <td><strong>Проверено:</strong></td>
                                <td><?= $entry->checked_at ?></td>
                            </tr>
                            <tr>
                                <td><strong>Создано:</strong></td>
                                <td><?= $entry->created_at ?></td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Matched Products (auto-connected via barcode = gtin) -->
            <div class="col-md-7">
                <div class="box box-primary">
                    <div class="box-header with-border">
                        <h3 class="box-title"><i class="fa fa-cube"></i> Товары с этим штрихкодом</h3>
                        <span class="label label-primary pull-right"><?= count($matchedProducts) ?></span>
                    </div>
                    <div class="box-body table-responsive no-padding">
                        <?php if (empty($matchedProducts)) {?>
                            <div class="text-center" style="padding: 30px;">
                                <i class="fa fa-inbox fa-2x text-muted"></i>
                                <p class="text-muted" style="margin-top: 10px;">Нет товаров с штрихкодом <?= Html::encode($entry->gtin) ?></p>
                            </div>
                        <?php } else {?>
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th width="50">ID</th>
                                        <th>Название</th>
                                        <th>Магазин</th>
                                        <th>Цена</th>
                                        <th width="80">Статус</th>
                                        <th width="50"></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($matchedProducts as $product) {?>
                                        <tr>
                                            <td><?= $product->id ?></td>
                                            <td><?= Html::encode($product->name_ru ?: '-') ?></td>
                                            <td>
                                                <?php if ($product->shop_id) {?>
                                                    <small class="text-muted">ID: <?= $product->shop_id ?></small>
                                                <?php } else {?>
                                                    -
                                                <?php }?>
                                            </td>
                                            <td><?= number_format($product->price ?: 0, 0, '', ' ') ?></td>
                                            <td>
                                                <?php if ($product->status == 1) {?>
                                                    <span class="label label-success">Активен</span>
                                                <?php } elseif ($product->status == 2) {?>
                                                    <span class="label label-danger">Заблокирован</span>
                                                <?php } else {?>
                                                    <span class="label label-default"><?= $product->status ?></span>
                                                <?php }?>
                                            </td>
                                            <td>
                                                <a href="<?=Yii::$app->urlManager->createUrl(['/admin/product/view', 'id' => $product->id])?>" class="btn btn-xs btn-info" title="Просмотр">
                                                    <i class="fa fa-eye"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php }?>
                                </tbody>
                            </table>
                        <?php }?>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>
