<?php
/* @var $this yii\web\View */
/* @var $model app\models\didox\DidoxDocument */

use yii\helpers\Html;
use yii\widgets\DetailView;
?>

<!-- Invoice Specific Details -->
<div class="invoice-view-section">
    <?= DetailView::widget([
        'model' => $model,
        'options' => ['class' => 'table table-striped table-bordered detail-view'],
        'attributes' => [
            // === BASIC INVOICE INFORMATION ===
            [
                'label' => false,
                'format' => 'raw',
                'value' => '<h4 style="margin-top: 20px; margin-bottom: 10px; color: #337ab7; border-bottom: 2px solid #337ab7; padding-bottom: 5px;"><i class="fa fa-info-circle"></i> Основная информация о счет-фактуре</h4>'
            ],
            [
                'attribute' => 'factura_type',
                'label' => 'Тип счет-фактуры',
                'value' => function($model) {
                    $types = [0 => 'Стандартная', 1 => 'Кредитная', 2 => 'Дебетовая', 3 => 'Смешанная', 4 => 'Агрегатная', 5 => 'Корректировочная', 6 => 'Импорт', 7 => 'Экспорт', 8 => 'Транзит', 9 => 'Инвестиционная'];
                    return $types[$model->invoice->factura_type ?? 0] . ' (' . ($model->invoice->factura_type ?? 0) . ')';
                },
            ],
            [
                'label' => 'Номер счет-фактуры',
                'value' => $model->invoice->invoice_number ?? '',
            ],
            [
                'label' => 'Дата счет-фактуры',
                'value' => $model->invoice->invoice_date ?? '',
                'format' => 'date',
            ],
            [
                'label' => 'Номер договора',
                'value' => $model->invoice->contract_number ?? '',
            ],
            [
                'label' => 'Дата договора',
                'value' => $model->invoice->contract_date ?? '',
                'format' => 'date',
            ],
            [
                'label' => 'ID договора (my.soliq.uz)',
                'value' => $model->invoice->contract_id ?? '',
            ],
            [
                'label' => 'ID лота',
                'value' => $model->invoice->lot_id ?? '',
            ],
            [
                'attribute' => 'has_marking',
                'label' => 'Есть маркированные товары',
                'value' => $model->invoice->has_marking ? 'Да' : 'Нет',
            ],
            [
                'attribute' => 'has_rent',
                'label' => 'Есть арендные услуги',
                'value' => $model->invoice->has_rent ? 'Да' : 'Нет',
            ],
            
            // === SELLER INFORMATION ===
            [
                'label' => false,
                'format' => 'raw',
                'value' => '<h4 style="margin-top: 20px; margin-bottom: 10px; color: #337ab7; border-bottom: 2px solid #337ab7; padding-bottom: 5px;"><i class="fa fa-building"></i> Информация о продавце</h4>'
            ],
            [
                'label' => 'ИНН продавца',
                'value' => $model->invoice->seller_tin ?? '',
            ],
            [
                'label' => 'Наименование продавца',
                'value' => $model->invoice->seller_name ?? '',
            ],
            [
                'label' => 'Адрес продавца',
                'value' => $model->invoice->seller_address ?? '',
            ],
            [
                'label' => 'Код филиала продавца',
                'value' => $model->invoice->seller_branch_code ?? '',
            ],
            [
                'label' => 'Название филиала продавца',
                'value' => $model->invoice->seller_branch_name ?? '',
            ],
            [
                'label' => 'Банковский счет продавца',
                'value' => $model->invoice->seller_bank_account ?? '',
            ],
            [
                'label' => 'Код банка продавца (МФО)',
                'value' => $model->invoice->seller_bank_code ?? '',
            ],
            [
                'label' => 'Название банка продавца',
                'value' => $model->invoice->seller_bank_name ?? '',
            ],
            
            // === BUYER INFORMATION ===
            [
                'label' => false,
                'format' => 'raw',
                'value' => '<h4 style="margin-top: 20px; margin-bottom: 10px; color: #337ab7; border-bottom: 2px solid #337ab7; padding-bottom: 5px;"><i class="fa fa-user"></i> Информация о покупателе</h4>'
            ],
            [
                'label' => 'ИНН/ПИНФЛ покупателя',
                'value' => $model->invoice->buyer_tin ?? '',
            ],
            [
                'label' => 'Наименование покупателя',
                'value' => $model->invoice->buyer_name ?? '',
            ],
            [
                'label' => 'Адрес покупателя',
                'value' => $model->invoice->buyer_address ?? '',
            ],
            [
                'label' => 'Код филиала покупателя',
                'value' => $model->invoice->buyer_branch_code ?? '',
            ],
            [
                'label' => 'Название филиала покупателя',
                'value' => $model->invoice->buyer_branch_name ?? '',
            ],
            
            // === FINANCIAL INFORMATION ===
            [
                'label' => false,
                'format' => 'raw',
                'value' => '<h4 style="margin-top: 20px; margin-bottom: 10px; color: #337ab7; border-bottom: 2px solid #337ab7; padding-bottom: 5px;"><i class="fa fa-money"></i> Финансовая информация</h4>'
            ],
            [
                'label' => 'Общая сумма без НДС',
                'value' => $model->invoice->total_sum ?? 0,
                'format' => ['currency', 'UZS'],
            ],
            [
                'label' => 'Общая сумма НДС',
                'value' => $model->invoice->total_vat_sum ?? 0,
                'format' => ['currency', 'UZS'],
            ],
            [
                'label' => 'Общая сумма с НДС',
                'value' => $model->invoice->total_with_vat_sum ?? 0,
                'format' => ['currency', 'UZS'],
            ],
            [
                'attribute' => 'has_vat',
                'label' => 'Включает НДС',
                'value' => $model->invoice->has_vat ? 'Да' : 'Нет',
            ],
            [
                'attribute' => 'has_lgota',
                'label' => 'Есть льготы',
                'value' => $model->invoice->has_lgota ? 'Да' : 'Нет',
            ],
        ],
    ]) ?>
</div>

<!-- Products Section -->
<?php 
// Check if products table exists and relation works
$products = [];
try {
    $products = $model->includedProducts;
} catch (\Exception $e) {
    // Table doesn't exist yet - migration not run
    $products = [];
}
?>
<?php if ($products): ?>
<div class="row" style="margin-top: 20px;">
    <div class="col-md-12">
        <div class="box box-info">
            <div class="box-header with-border">
                <h3 class="box-title"><i class="fa fa-list"></i> Товары в счет-фактуре</h3>
            </div>
            <div class="box-body">
                <?php foreach ($products as $index => $product): ?>
                <div class="panel panel-default" style="margin-bottom: 15px;">
                    <div class="panel-heading">
                        <h4 class="panel-title">
                            <i class="fa fa-cube"></i> Товар #<?= $product->ord_no ?: ($index + 1) ?>: <?= Html::encode($product->name ?: 'Без названия') ?>
                        </h4>
                    </div>
                    <div class="panel-body">
                        <div class="row">
                            <div class="col-md-6">
                                <table class="table table-condensed">
                                    <tr><th>Код ИКПУ:</th><td><?= Html::encode($product->catalog_code) ?></td></tr>
                                    <tr><th>Название ИКПУ:</th><td><?= Html::encode($product->catalog_name) ?></td></tr>
                                    <tr><th>Код упаковки:</th><td><?= Html::encode($product->package_code) ?></td></tr>
                                    <tr><th>Единица измерения:</th><td><?= Html::encode($product->package_name) ?></td></tr>
                                    <tr><th>Происхождение:</th><td><?= Html::encode($product->getOriginLabel()) ?></td></tr>
                                    <tr><th>Штрих-код:</th><td><?= Html::encode($product->barcode ?: 'Нет') ?></td></tr>
                                    <?php if ($product->marks): ?>
                                    <tr><th>Маркировки:</th><td><?= Html::encode($product->marks) ?></td></tr>
                                    <?php endif; ?>
                                </table>
                            </div>
                            <div class="col-md-6">
                                <table class="table table-condensed">
                                    <tr><th>Количество:</th><td><?= Yii::$app->formatter->asDecimal($product->count, 3) ?></td></tr>
                                    <tr><th>Цена за единицу:</th><td><?= Yii::$app->formatter->asCurrency($product->summa) ?></td></tr>
                                    <tr><th>Стоимость поставки:</th><td><?= Yii::$app->formatter->asCurrency($product->delivery_sum) ?></td></tr>
                                    <tr><th>Ставка НДС:</th><td><?= $product->vat_rate ?>%</td></tr>
                                    <tr><th>Сумма НДС:</th><td><?= Yii::$app->formatter->asCurrency($product->vat_sum) ?></td></tr>
                                    <tr><th>Стоимость с НДС:</th><td><strong><?= Yii::$app->formatter->asCurrency($product->delivery_sum_with_vat) ?></strong></td></tr>
                                    <tr><th>Статус НДС:</th><td>
                                        <?php if ($product->without_vat): ?>
                                            <span class="label label-warning">Без НДС</span>
                                        <?php else: ?>
                                            <span class="label label-success">С НДС</span>
                                        <?php endif; ?>
                                    </td></tr>
                                </table>
                            </div>
                        </div>

                        <?php if ($product->committent_name || $product->committent_tin): ?>
                        <div class="alert alert-info" style="margin-top: 10px;">
                            <strong><i class="fa fa-handshake-o"></i> Информация о комитенте:</strong><br>
                            <strong>Наименование:</strong> <?= Html::encode($product->committent_name) ?><br>
                            <strong>ИНН:</strong> <?= Html::encode($product->committent_tin) ?><br>
                            <?php if ($product->committent_vat_reg_code): ?>
                            <strong>Рег. код НДС:</strong> <?= Html::encode($product->committent_vat_reg_code) ?>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>

                        <?php if ($product->lgota_id || $product->lgota_name): ?>
                        <div class="alert alert-warning" style="margin-top: 10px;">
                            <strong><i class="fa fa-percent"></i> Информация о льготе:</strong><br>
                            <strong>Тип:</strong> <?= Html::encode($product->getLgotaTypeLabel()) ?><br>
                            <?php if ($product->lgota_id): ?>
                            <strong>Код льготы:</strong> <?= Html::encode($product->lgota_id) ?><br>
                            <?php endif; ?>
                            <?php if ($product->lgota_name): ?>
                            <strong>Название:</strong> <?= Html::encode($product->lgota_name) ?><br>
                            <?php endif; ?>
                            <?php if ($product->lgota_vat_sum > 0): ?>
                            <strong>Льготная сумма НДС:</strong> <?= Yii::$app->formatter->asCurrency($product->lgota_vat_sum) ?>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>
<?php else: ?>
<div class="row" style="margin-top: 20px;">
    <div class="col-md-12">
        <div class="box box-warning">
            <div class="box-header with-border">
                <h3 class="box-title"><i class="fa fa-exclamation-triangle"></i> Товары</h3>
            </div>
            <div class="box-body">
                <?php if (empty($products) && isset($e)): ?>
                <div class="alert alert-info">
                    <h4><i class="fa fa-info-circle"></i> Требуется миграция базы данных</h4>
                    <p>Для отображения товаров необходимо выполнить миграцию базы данных:</p>
                    <code>php yii migrate</code>
                </div>
                <?php else: ?>
                <p class="text-muted">В этом документе нет добавленных товаров.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Associated Order Section -->
<?php if ($model->order_id): ?>
<div class="row" style="margin-top: 20px;">
    <div class="col-md-12">
        <div class="box box-info">
            <div class="box-header with-border">
                <h3 class="box-title"><i class="fa fa-shopping-cart"></i> Связанный заказ</h3>
                <div class="box-tools pull-right">
                    <?= Html::a('<i class="fa fa-external-link"></i> Просмотреть заказ', 
                        ['/admin/order/view', 'id' => $model->order_id], 
                        ['class' => 'btn btn-primary btn-sm', 'target' => '_blank']) ?>
                </div>
            </div>
            <div class="box-body">
                <?php if ($model->order): ?>
                    <div class="row">
                        <div class="col-md-6">
                            <table class="table table-condensed">
                                <tr><th>Номер заказа:</th><td>#<?= Html::encode($model->order->id) ?></td></tr>
                                <tr><th>Дата заказа:</th><td><?= Yii::$app->formatter->asDate($model->order->date) ?></td></tr>
                                <tr><th>Сумма заказа:</th><td><?= Yii::$app->formatter->asCurrency($model->order->total_sum ?? 0) ?></td></tr>
                                <tr><th>Статус заказа:</th><td>
                                    <?php 
                                    $statusLabels = [
                                        0 => '<span class="label label-default">Новый</span>',
                                        1 => '<span class="label label-info">В обработке</span>', 
                                        2 => '<span class="label label-warning">Отправлен</span>',
                                        3 => '<span class="label label-success">Доставлен</span>',
                                        4 => '<span class="label label-danger">Отменен</span>',
                                    ];
                                    echo $statusLabels[$model->order->status] ?? '<span class="label label-default">Неизвестно</span>';
                                    ?>
                                </td></tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <table class="table table-condensed">
                                <?php if ($model->order->user): ?>
                                <tr><th>Клиент:</th><td><?= Html::encode($model->order->user->name . ' ' . $model->order->user->lastname) ?></td></tr>
                                <tr><th>Телефон:</th><td><?= Html::encode($model->order->user->phone) ?></td></tr>
                                <tr><th>Email:</th><td><?= Html::encode($model->order->user->email) ?></td></tr>
                                <?php else: ?>
                                <tr><th>Клиент:</th><td><?= Html::encode($model->order->name . ' ' . $model->order->lastname) ?></td></tr>
                                <tr><th>Телефон:</th><td><?= Html::encode($model->order->phone) ?></td></tr>
                                <?php endif; ?>
                                <tr><th>Адрес доставки:</th><td><?= Html::encode($model->order->address ?: 'Не указан') ?></td></tr>
                            </table>
                        </div>
                    </div>
                    
                    <div class="alert alert-info">
                        <h5><i class="fa fa-link"></i> Связь документа с заказом</h5>
                        <p>Этот счет-фактура создан на основе заказа <strong>#<?= $model->order_id ?></strong>.</p>
                        <p>Данные покупателя и условия договора были автоматически заполнены из информации заказа.</p>
                    </div>
                <?php else: ?>
                    <div class="alert alert-warning">
                        <h5><i class="fa fa-warning"></i> Заказ не найден</h5>
                        <p>Документ связан с заказом #<?= $model->order_id ?>, но заказ не найден в базе данных. Возможно, заказ был удален.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?php endif; ?> 