<?php
use yii\helpers\Html;
use app\models\product\Product;

$this->title = 'ASL Belgisi — Реестр';
$this->params['breadcrumbs'][] = ['label' => 'Товары', 'url' => ['/admin/product/']];
$this->params['breadcrumbs'][] = 'ASL Belgisi';

$totalPages = $pageSize > 0 ? ceil($total / $pageSize) : 0;
?>

<div class="content-wrapper">
    <section class="content-header">
        <h1>
            <i class="fa fa-check-circle"></i> ASL Belgisi
            <small>Реестр проверок товаров</small>
        </h1>
        <ol class="breadcrumb">
            <li><a href="<?=Yii::$app->urlManager->createUrl(['/admin/'])?>"><i class="fa fa-dashboard"></i> Главная</a></li>
            <li><a href="<?=Yii::$app->urlManager->createUrl(['/admin/product/'])?>"><i class="fa fa-cube"></i> Товары</a></li>
            <li class="active">ASL Belgisi</li>
        </ol>
    </section>
    <section class="content">
        <!-- Search & Filter -->
        <div class="box box-default">
            <div class="box-body">
                <form method="get" action="<?=Yii::$app->urlManager->createUrl(['/admin/product/asl-belgisi'])?>">
                    <div class="row">
                        <div class="col-md-5">
                            <div class="input-group">
                                <span class="input-group-addon"><i class="fa fa-search"></i></span>
                                <input type="text" name="search" class="form-control" placeholder="Поиск по GTIN, названию, ИНН..." value="<?= Html::encode($search) ?>">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <select name="status" class="form-control">
                                <option value="">Все статусы</option>
                                <option value="PUBLISHED" <?= $statusFilter === 'PUBLISHED' ? 'selected' : '' ?>>PUBLISHED</option>
                                <option value="DRAFT" <?= $statusFilter === 'DRAFT' ? 'selected' : '' ?>>DRAFT</option>
                                <option value="BLOCKED" <?= $statusFilter === 'BLOCKED' ? 'selected' : '' ?>>BLOCKED</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-primary btn-block"><i class="fa fa-filter"></i> Фильтр</button>
                        </div>
                        <div class="col-md-2">
                            <a href="<?=Yii::$app->urlManager->createUrl(['/admin/product/asl-belgisi'])?>" class="btn btn-default btn-block"><i class="fa fa-times"></i> Сбросить</a>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Results -->
        <div class="row">
            <div class="col-md-12">
                <div class="box box-primary">
                    <div class="box-header with-border">
                        <h3 class="box-title"><i class="fa fa-list"></i> Реестр ASL Belgisi</h3>
                        <span class="label label-primary pull-right">Всего: <?= number_format($total) ?></span>
                    </div>
                    <div class="box-body table-responsive no-padding">
                        <?php if (empty($records)) {?>
                            <div class="text-center" style="padding: 40px;">
                                <i class="fa fa-inbox fa-3x text-muted"></i>
                                <p class="text-muted" style="margin-top: 10px;">Нет записей ASL Belgisi</p>
                            </div>
                        <?php } else {?>
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th width="50">ID</th>
                                        <th>GTIN</th>
                                        <th>Название в ASL</th>
                                        <th>ИНН</th>
                                        <th>Группа</th>
                                        <th width="100">Статус</th>
                                        <th width="80">Товаров</th>
                                        <th width="140">Проверено</th>
                                        <th width="50"></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($records as $record) {?>
                                        <?php $productCount = Product::find()->where(['barcode' => $record->gtin])->count(); ?>
                                        <tr>
                                            <td><?= $record->id ?></td>
                                            <td><code><?= Html::encode($record->gtin) ?></code></td>
                                            <td><?= Html::encode($record->product_name_ru ?: '-') ?></td>
                                            <td><?= Html::encode($record->inn ?: '-') ?></td>
                                            <td>
                                                <?php if ($record->product_group) {?>
                                                    <span class="label label-default"><?= Html::encode($record->product_group) ?></span>
                                                <?php } else {?>
                                                    -
                                                <?php }?>
                                            </td>
                                            <td>
                                                <?php if ($record->status === 'PUBLISHED') {?>
                                                    <span class="label label-success"><i class="fa fa-check"></i> <?= Html::encode($record->status) ?></span>
                                                <?php } elseif ($record->status) {?>
                                                    <span class="label label-warning"><?= Html::encode($record->status) ?></span>
                                                <?php } else {?>
                                                    <span class="label label-default">N/A</span>
                                                <?php }?>
                                            </td>
                                            <td>
                                                <?php if ($productCount > 0) {?>
                                                    <span class="badge bg-blue"><?= $productCount ?></span>
                                                <?php } else {?>
                                                    <span class="badge bg-gray">0</span>
                                                <?php }?>
                                            </td>
                                            <td><small><?= $record->checked_at ?></small></td>
                                            <td>
                                                <a href="<?=Yii::$app->urlManager->createUrl(['/admin/product/asl-belgisi-view', 'id' => $record->id])?>" class="btn btn-xs btn-info" title="Подробнее">
                                                    <i class="fa fa-eye"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php }?>
                                </tbody>
                            </table>
                        <?php }?>
                    </div>
                    <?php if ($totalPages > 1) {?>
                    <div class="box-footer text-center">
                        <ul class="pagination" style="margin: 0;">
                            <?php if ($page > 1) {?>
                                <li><a href="<?=Yii::$app->urlManager->createUrl(array_merge(['/admin/product/asl-belgisi'], ['page' => $page - 1], $search ? ['search' => $search] : [], $statusFilter ? ['status' => $statusFilter] : []))?>">&laquo;</a></li>
                            <?php }?>
                            <?php
                            $start = max(1, $page - 3);
                            $end = min($totalPages, $page + 3);
                            for ($i = $start; $i <= $end; $i++) {?>
                                <li class="<?= $i == $page ? 'active' : '' ?>">
                                    <a href="<?=Yii::$app->urlManager->createUrl(array_merge(['/admin/product/asl-belgisi'], ['page' => $i], $search ? ['search' => $search] : [], $statusFilter ? ['status' => $statusFilter] : []))?>"><?= $i ?></a>
                                </li>
                            <?php }?>
                            <?php if ($page < $totalPages) {?>
                                <li><a href="<?=Yii::$app->urlManager->createUrl(array_merge(['/admin/product/asl-belgisi'], ['page' => $page + 1], $search ? ['search' => $search] : [], $statusFilter ? ['status' => $statusFilter] : []))?>">&raquo;</a></li>
                            <?php }?>
                        </ul>
                    </div>
                    <?php }?>
                </div>
            </div>
        </div>
    </section>
</div>
