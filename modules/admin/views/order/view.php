<?php
use yii\helpers\Html;
use yii\grid\GridView;

$this->title = 'Заказ #'.$model->id;
$this->params['breadcrubs'][] = $this->title;

$location = $model->map_location ? explode(', ', $model->map_location) : [];
?>

<?php if ($model->map_location) {?>
    <script src="https://api-maps.yandex.ru/2.1/?lang=ru_RU" type="text/javascript"></script>

    <script>
        ymaps.ready(init);

        var myMap, 
            myPlacemark;

        function init(){ 
            myMap = new ymaps.Map("map", {
                center: [<?=$location[0];?>, <?=$location[1];?>],
                zoom: 15
            }); 
            
            myPlacemark = new ymaps.Placemark([<?=$location[0]?>, <?=$location[1];?>], {
                hintContent: 'Москва!',
                balloonContent: 'Столица России'
            });
            
            myMap.geoObjects.add(myPlacemark);
        }
    </script>
<?php }?>

<div class="content-wrapper">
    <section class="content-header">
        <h1><?=$this->title;?></h1>
        
        <ol class="breadcrumb">
            <li><a href="<?=Yii::$app->urlManager->createUrl(['/admin/'])?>"><i class="fa fa-dashboard"></i> Главная</a></li>
            <li class="active"><?=$this->title;?></li>
        </ol>
    </section>
    <section class="content">
        <?php if (Yii::$app->session->hasFlash('order_accepted')) {?>
            <div class="alert alert-success text-center"><?=Yii::$app->session->getFlash('order_accepted');?></div>
        <?php }?>
        <?php if (Yii::$app->session->hasFlash('success')) {?>
            <div class="alert alert-success text-center"><?=Yii::$app->session->getFlash('success');?></div>
        <?php }?>
        <?php if (Yii::$app->session->hasFlash('error')) {?>
            <div class="alert alert-danger text-center"><?=Yii::$app->session->getFlash('error');?></div>
        <?php }?>
        <div class="box box-info color-palette-box">
            <div class="box-header with-border">
                <?php if (($model->delivery && $model->delivery->id != 3) || !$model->delivery) {?>
                    <a href="<?=Yii::$app->urlManager->createUrl(['/admin/order/accept', 'id'=>$model->id, 'status'=>3]);?>" class="btn btn-info"><i class="fa fa-check"></i> На доставке / ожидается оплата</a>
                    <a href="<?=Yii::$app->urlManager->createUrl(['/admin/order/accept', 'id'=>$model->id, 'status'=>4]);?>" class="btn btn-info"><i class="fa fa-check"></i> В пути / Отправлен</a>
                <?php }?>
                <a href="<?=Yii::$app->urlManager->createUrl(['/admin/order/accept', 'id'=>$model->id, 'status'=>5]);?>" class="btn btn-info"><i class="fa fa-check"></i> Доставлен</a>

                <?php if (($model->delivery && $model->delivery->id != 3) || !$model->delivery) {?>
                    <a href="<?=Yii::$app->urlManager->createUrl(['/admin/order/accept', 'id'=>$model->id, 'status'=>10]);?>" class="btn btn-danger"><i class="fa fa-check"></i> Возврат</a>
                <?php }?>
                <?php if ($model->status == 0) {?>
                    <div class="pull-right">
                        <a href="<?=Yii::$app->urlManager->createUrl(['/admin/order/accept', 'id'=>$model->id, 'status'=>1]);?>" class="btn btn-success"><i class="fa fa-check"></i> Принять</a>
                        <a href="<?=Yii::$app->urlManager->createUrl(['/admin/order/accept', 'id'=>$model->id, 'status'=>2]);?>" class="btn btn-danger"><i class="fa fa-remove"></i> Отклонить</a>
                    </div>
                <?php }?>
            </div>
            <div class="box-body">
                <table class="table table-striped">
                    <tr>
                        <td>ID заказа:</td>
                        <td><?=$model->id ? $model->id : '-';?></td>
                    </tr>
                    <tr>
                        <td>Статус:</td>
                        <td>
                            <?php
                                if ($model->status == 0) {
                                    echo '<small class="label bg-yellow">В ожидании</small>';
                                }
                                if ($model->status == 1) {
                                    echo '<small class="label bg-green">Принят</small>';
                                }
                                if ($model->status == 2) {
                                    echo '<small class="label bg-red">Отклонен</small>';
                                }
                                if ($model->status == 3) {
                                    echo '<small class="label bg-green">На доставке</small>';
                                }
                                if ($model->status == 4) {
                                    echo '<small class="label bg-green">В пути</small>';
                                }
                                if ($model->status == 5) {
                                    echo '<small class="label bg-green">Доставлен</small>';
                                }
                                if ($model->status == 10) {
                                    echo '<small class="label bg-red">Возврат</small>';
                                }
                            ?>
                        </td>
                    </tr>
                    <tr>
                        <td>Статус логиста:</td>
                        <td>
                            <?php
                                if ($model->status_logist == 0) {
                                    echo '<small class="label bg-yellow">В ожидании</small>';
                                }
                                if ($model->status_logist == 1) {
                                    echo '<small class="label bg-green">Принят</small>';
                                }
                                if ($model->status_logist == 2) {
                                    echo '<small class="label bg-red">Отклонен</small>';
                                }
                                if ($model->status_logist == 3) {
                                    echo '<small class="label bg-aqua">Отправен на доставку</small>';
                                }
                                if ($model->status_logist == 4) {
                                    echo '<small class="label bg-aqua">В пути</small>';
                                }
                                if ($model->status_logist == 5) {
                                    echo '<small class="label bg-green">Доставлен</small>';
                                }
                            ?>
                        </td>
                    </tr>
                    <tr>
                        <td>Статус оплаты:</td>
                        <td>
                            <?php
                                if ($model->status_payment == 0) {
                                    echo '<small class="label bg-red">Не оплачен</small>';
                                }
                                if ($model->status_payment == 1) {
                                    echo '<small class="label bg-green">Оплачен</small>';
                                }
                            ?>
                        </td>
                    </tr>
                    <tr>
                        <td>BTS Информация:</td>
                        <td id="bts-info-container">
                            <?php if ($model->hasBtsIntegration()): ?>
                                <div class="bts-controls" style="margin-bottom: 15px;">
                                    <button type="button" class="btn btn-info btn-sm" onclick="updateBtsStatus(<?= $model->id ?>)">
                                        <i class="fa fa-refresh"></i> Обновить статусы BTS
                                    </button>
                                    <button type="button" class="btn btn-success btn-sm" onclick="getBtsTracking(<?= $model->id ?>)">
                                        <i class="fa fa-history"></i> Показать историю доставки
                                    </button>
                                </div>
                                <div id="bts-status-list">
                                    <?php foreach ($model->orderProducts as $orderProduct): ?>
                                        <?php if ($orderProduct->bts_id): ?>
                                            <div class="bts-item" style="margin-bottom: 10px; padding: 10px; border: 1px solid #ddd; border-radius: 3px;" data-bts-id="<?= $orderProduct->bts_id ?>">
                                                <div style="display: flex; justify-content: space-between; align-items: center;">
                                                    <div>
                                                        <strong>BTS ID:</strong> <?= $orderProduct->bts_id ?>
                                                        <?php if ($orderProduct->stock): ?>
                                                            <small class="text-muted">(<?= $orderProduct->stock->name_ru ?>)</small>
                                                        <?php endif; ?>
                                                    </div>
                                                    <div>
                                                        <?php 
                                                        $statusLabel = \yii\services\BTS::getBtsStatusLabel($orderProduct->bts_status, 'ru');
                                                        $statusClass = 'label-default';
                                                        
                                                        // Color coding based on status
                                                        switch ($orderProduct->bts_status) {
                                                            case -1: $statusClass = 'label-warning'; break; // Draft
                                                            case 0: $statusClass = 'label-danger'; break; // Refused
                                                            case 1: $statusClass = 'label-info'; break; // At sender
                                                            case 2:
                                                            case 3: $statusClass = 'label-primary'; break; // In transit
                                                            case 4:
                                                            case 5: $statusClass = 'label-warning'; break; // At delivery office / delivering
                                                            case 6: $statusClass = 'label-success'; break; // Delivered
                                                            case 7: $statusClass = 'label-danger'; break; // Return
                                                            case 8:
                                                            case 10:
                                                            case 31:
                                                            case 32:
                                                            case 33:
                                                            case 34: $statusClass = 'label-info'; break; // In processing
                                                        }
                                                        ?>
                                                        <span class="label <?= $statusClass ?> bts-status-label">
                                                            <?= $statusLabel ?: $orderProduct->bts_status_info ?: 'Неизвестный статус' ?>
                                                        </span>
                                                    </div>
                                                </div>
                                                <div style="margin-top: 5px;">
                                                    <small class="text-muted">
                                                        <strong>Статус ID:</strong> <?= $orderProduct->bts_status ?: '-' ?> |
                                                        <strong>Описание:</strong> <?= $orderProduct->bts_status_info ?: '-' ?>
                                                    </small>
                                                </div>
                                                <?php if ($orderProduct->bts_price): ?>
                                                    <div style="margin-top: 5px;">
                                                        <small><strong>Стоимость доставки:</strong> <?= number_format($orderProduct->bts_price, 2) ?> сум</small>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </div>
                                <?php if ($model->delivery_cost): ?>
                                    <div style="margin-top: 10px; font-weight: bold;">
                                        <strong>Общая стоимость доставки:</strong> <?= number_format($model->delivery_cost, 2) ?> сум
                                    </div>
                                <?php endif; ?>
                            <?php else: ?>
                                <span style="color: #999;">BTS интеграция не используется</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <td>Оформитель заказа:</td>
                        <td><?=$model->user->name ? '<a href="'.Yii::$app->urlManager->createUrl(['/admin/user/view', 'id'=>$model->user->id]).'" target="_blank">'.$model->user->name.'</a>' : '-';?></td>
                    </tr>
                    <tr>
                        <td>Адрес:</td>
                        <td><?=$model->address ? $model->address : '-';?></td>
                    </tr>
                    <tr>
                        <td>Тип оплаты:</td>
                        <td><?=$model->payment ? $model->payment->name_ru : '-';?></td>
                    </tr>
                    <tr>
                        <td>Тип доставки:</td>
                        <td><?=$model->delivery ? $model->delivery->name_ru : '-';?></td>
                    </tr>
                    <tr>
                        <td>Кол-во товаров:</td>
                        <td><?=$model->amount ? $model->amount : '-';?></td>
                    </tr>
                    <tr>
                        <td>Сумма товаров(с доставкой):</td>
                        <td><?=$model->price ? number_format($model->price, 0, '.', ' ') : '-';?> UZS</td>
                    </tr>
                    <tr>
                        <td>Примет заказчик:</td>
                        <td>
                            <?php if ($model->receiver == 0) {?>
                                <small class="label bg-red">Нет</small>
                            <?php }?>
                            <?php if ($model->receiver == 1) {?>
                                <small class="label bg-green">Да</small>
                            <?php }?>
                        </td>
                    </tr>
                    <tr>
                        <td>Коментарий:</td>
                        <td><?=$model->comment ? $model->comment : '-';?></td>
                    </tr>
                    <tr>
                        <td>Дата:</td>
                        <td><?=$model->date ? $model->date : '-';?></td>
                    </tr>
                </table>
            </div>
        </div>
        <?php if ($model->receiver == 0) {?>
            <div class="box box-info color-palette-box">
                <div class="box-header with-border">
                    <div class="box-title">
                        Кто примет
                    </div>
                </div>
                <div class="box-body">
                    <table class="table table-striped">
                        <tr>
                            <td>Имя:</td>
                            <td><?=$model->name ? $model->name : '-';?></td>
                        </tr>
                        <tr>
                            <td>Фамилия:</td>
                            <td><?=$model->lastname ? $model->lastname : '-';?></td>
                        </tr>
                        <tr>
                            <td>Телефон:</td>
                            <td><?=$model->phone ? $model->phone : '-';?></td>
                        </tr>
                        <tr>
                            <td>E-mail:</td>
                            <td><?=$model->email ? $model->email : '-';?></td>
                        </tr>
                    </table>
                </div>
            </div>
        <?php }?>
        <?php if ($model->map_location) {?>
            <div class="box box-info color-palette-box">
                <div class="box-header with-border">
                    Местоположение на карте
                </div>
                <div class="box-body">
                    <div id="map" style="width:100%; height:400px"></div>
                </div>
            </div>
        <?php }?>
        <div class="box box-info color-palette-box">
            <div class="box-header with-border">
                <div class="box-title">
                    Товары
                </div>
            </div>
            <div class="box-body">
                <?php if ($products) {?>
                    <?php foreach ($products as $product) {?>
                        <?php if ($product->product) {?>
                            <div class="attachment-block clearfix">
                                <a href="<?=Yii::$app->urlManager->createUrl(['/admin/product/view', 'id'=>$product->product->id]);?>" target="_blank"><img class="attachment-img" src="<?=$product->product->getPhoto();?>" alt="Attachment Image"></a>
                                <div class="attachment-pushed">
                                    <!-- <h4 class="attachment-heading"><?=$product->product->name_ru?></h4> -->
                                    <div class="attachment-text">
                                        <table class="table table-striped">
                                            <tr>
                                                <td>Название:</td>
                                                <td><a href="<?=Yii::$app->urlManager->createUrl(['/admin/product/view', 'id'=>$product->product->id]);?>" target="_blank"><?=$product->product->name_ru;?></a></td>
                                            </tr>
                                            <tr>
                                                <td>Статус:</td>
                                                <td>
                                                    <?php if ($product->status == 1) {?>
                                                        <small class="label bg-green">Активный</small>
                                                    <?php }?>
                                                    <?php if ($product->status == 2) {?>
                                                        <small class="label bg-red">Возврат</small>
                                                    <?php }?>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>Способ доставки</td>
                                                <td><?=$product->delivery ? $product->delivery->name_ru : '<small class="label bg-red">Не указан</small>';?></td>
                                            </tr>
                                            <tr>
                                                <td>Сума за 1 шт.:</td>
                                                <td><?=number_format($product->product_price/$product->amount, 0, '.', ' ')?> UZS</td>
                                            </tr>
                                            <tr>
                                                <td>Количество:</td>
                                                <td><?=$product->amount;?></td>
                                            </tr>
                                            <tr>
                                                <td>Цена доставки:</td>
                                                <td><?=$product->delivery_cost ? : '-';?></td>
                                            </tr>
                                            <tr>
                                                <td>Итоговая сума:</td>
                                                <td><?='('.number_format($product->product_price/$product->amount, 0, '.', ' ').' x '.$product->amount.') + '.number_format($product->delivery_cost, 0, '.', ' ').' = '.number_format($product->price, 0, '.', ' ').' UZS';?></td>
                                            </tr>
                                            <tr>
                                                <td>Отзывы клиентов:</td>
                                                <td>
                                                    <?php if ($product->hasReviews()) {?>
                                                        <span class="label label-success">
                                                            <i class="fa fa-star"></i> <?= count($product->productReviews) ?> отзыв(ов)
                                                        </span>
                                                        
                                                        <?php foreach ($product->productReviews as $index => $review) {?>
                                                            <div style="border-left: 4px solid #ddd; padding-left: 15px; margin: 10px 0; background-color: #fafafa; padding: 10px;">
                                                                <div style="margin-bottom: 5px;">
                                                                    <strong>
                                                                        <i class="fa fa-user"></i> 
                                                                        <?= $review->user ? Html::encode($review->user->name) : 'Неизвестный пользователь' ?>
                                                                    </strong>
                                                                    
                                                                    <small class="text-muted">
                                                                        | <i class="fa fa-calendar"></i> <?= $review->date ?>
                                                                    </small>
                                                                </div>
                                                                
                                                                <div style="margin-bottom: 5px;">
                                                                    <strong>Рейтинг:</strong> 
                                                                    <?php for($i = 1; $i <= 5; $i++): ?>
                                                                        <?php if($i <= $review->rate): ?>
                                                                            <i class="fa fa-star text-yellow"></i>
                                                                        <?php else: ?>
                                                                            <i class="fa fa-star-o text-gray"></i>
                                                                        <?php endif; ?>
                                                                    <?php endfor; ?>
                                                                    (<?= $review->rate ?>/5)
                                                                </div>
                                                                
                                                                <div style="margin-bottom: 5px;">
                                                                    <?php if ($review->status == \app\models\product\review\ProductReview::STATUS_ACCEPTED) {?>
                                                                        <span class="label label-success">
                                                                            <i class="fa fa-check"></i> Принят
                                                                        </span>
                                                                    <?php } elseif ($review->status == \app\models\product\review\ProductReview::STATUS_PENDING) {?>
                                                                        <span class="label label-warning">
                                                                            <i class="fa fa-clock-o"></i> В ожидании
                                                                        </span>
                                                                    <?php } elseif ($review->status == \app\models\product\review\ProductReview::STATUS_REJECTED) {?>
                                                                        <span class="label label-danger">
                                                                            <i class="fa fa-times"></i> Отклонен
                                                                        </span>
                                                                    <?php } elseif ($review->status == \app\models\product\review\ProductReview::STATUS_PROCESSED) {?>
                                                                        <span class="label label-info">
                                                                            <i class="fa fa-cog"></i> Обработан
                                                                        </span>
                                                                    <?php }?>
                                                                    
                                                                    <?php if ($review->status_date) {?>
                                                                        <small class="text-muted">
                                                                            | <strong>Статус изменен:</strong> <?= $review->status_date ?>
                                                                        </small>
                                                                    <?php }?>
                                                                </div>
                                                                
                                                                <?php if ($review->review) {?>
                                                                    <div style="margin-top: 8px;">
                                                                        <div class="well well-sm" style="background-color: #ffffff; margin-bottom: 5px;">
                                                                            <i class="fa fa-quote-left text-muted"></i>
                                                                            <?= nl2br(Html::encode($review->review)) ?>
                                                                            <i class="fa fa-quote-right text-muted"></i>
                                                                        </div>
                                                                    </div>
                                                                <?php }?>
                                                                
                                                                <?php if ($review->status_comment) {?>
                                                                    <div style="margin-top: 5px;">
                                                                        <div class="alert alert-info" style="margin-bottom: 0; padding: 8px;">
                                                                            <strong><i class="fa fa-info-circle"></i> Комментарий модератора:</strong><br>
                                                                            <?= nl2br(Html::encode($review->status_comment)) ?>
                                                                        </div>
                                                                    </div>
                                                                <?php }?>
                                                            </div>
                                                        <?php }?>
                                                        
                                                    <?php } else {?>
                                                        <span class="label label-default">
                                                            <i class="fa fa-minus-circle"></i> Нет отзывов
                                                        </span>
                                                    <?php }?>
                                                </td>
                                            </tr>
                                            <?php if ($product->orderProductFilter) {?>
                                                <?php foreach ($product->orderProductFilter as $filter) {?>
                                                    <?php if ($filter->productFilter && $filter->productFilter->filter) {?>
                                                        <tr>
                                                            <td><?=$filter->productFilter->filter->name_ru;?>:</td>
                                                            <td><?=$filter->productFilter->value_ru;?></td>
                                                        </tr>
                                                    <?php }?>
                                                <?php }?>
                                            <?php }?>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        <?php }?>
                    <?php }?>
                    Общее количество товаров: <?=$model->amount;?>
                    <br/>
                    Итоговая сума123: <?=number_format($model->price);?>
                    <?php if ($model->delivery && $model->delivery->price) {?>
                        <br/>
                        Итоговая сума с доставкой: <?=number_format($model->price + $model->delivery->price);?>
                    <?php }?>
                <?php } else {?>
                    <div class="alert alert-warning text-center">Товары не найдены</div>
                <?php }?>
            </div>
        </div>
        
        <!-- DIDOX Documents Section -->
        <div class="box box-success color-palette-box" style="margin-top: 20px;">
            <div class="box-header with-border">
                <h3 class="box-title"><i class="fa fa-file-text-o"></i> Документы DIDOX</h3>
                <div class="box-tools pull-right">
                    <!-- Auto Creation Buttons -->
                    <div class="btn-group" style="margin-right: 10px;">
                        <a href="<?=Yii::$app->urlManager->createUrl(['/admin/order/create-didox-invoice', 'id' => $model->id]);?>" 
                           class="btn btn-info btn-sm"
                           onclick="return confirm('Создать счет-фактуру автоматически?')">
                            <i class="fa fa-bolt"></i> Авто: Счет-фактура
                        </a>
                        <a href="<?=Yii::$app->urlManager->createUrl(['/admin/order/create-didox-arbitrary', 'id' => $model->id]);?>" 
                           class="btn btn-info btn-sm"
                           onclick="return confirm('Создать произвольный договор автоматически?')">
                            <i class="fa fa-bolt"></i> Авто: Договор
                        </a>
                    </div>
                    <?php if (empty($didoxDocuments)): ?>
                        <a href="<?=Yii::$app->urlManager->createUrl(['/admin/order/create-didox-documents', 'id' => $model->id]);?>" 
                           class="btn btn-warning btn-sm"
                           onclick="return confirm('Вы уверены, что хотите создать оба документа (счет-фактура и договор)?')">
                            <i class="fa fa-files-o"></i> Создать оба документа
                        </a>
                        <a href="<?=Yii::$app->urlManager->createUrl(['/admin/didox/create', 'orderId' => $model->id]);?>" 
                           class="btn btn-primary btn-sm">
                            <i class="fa fa-plus"></i> Создать счет-фактуру
                        </a>
                        <a href="<?=Yii::$app->urlManager->createUrl(['/admin/didox/create-arbitrary', 'orderId' => $model->id]);?>" 
                           class="btn btn-success btn-sm">
                            <i class="fa fa-file-o"></i> Создать договор
                        </a>
                    <?php endif; ?>
                </div>
            </div>
            <div class="box-body">
                <?php if (!empty($didoxDocuments)): ?>
                    <div class="table-responsive">
                        <table class="table table-striped table-bordered">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Название</th>
                                    <th>Тип документа</th>
                                    <th>Статус DIDOX</th>
                                    <th>ID в DIDOX</th>
                                    <th>Создан</th>
                                    <th>Действия</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($didoxDocuments as $doc): ?>
                                <tr>
                                    <td><?= Html::encode($doc->id) ?></td>
                                    <td>
                                        <strong><?= Html::encode($doc->name) ?></strong>
                                        <?php if ($doc->toUser): ?>
                                            <br><small class="text-muted">
                                                <i class="fa fa-user"></i> Назначен: <?= Html::encode($doc->toUser->name) ?>
                                            </small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($doc->isArbitrary()): ?>
                                            <span class="label label-success">
                                                <i class="fa fa-file-text-o"></i> Произвольный договор
                                            </span>
                                        <?php else: ?>
                                            <span class="label label-info">
                                                <i class="fa fa-file-text"></i> Счет-фактура
                                            </span>
                                        <?php endif; ?>
                                        <br><small class="text-muted">DIDOX: <?= Html::encode($doc->didox_doc_type ?: '002') ?></small>
                                    </td>
                                    <td>
                                        <?php
                                        if ($doc->didox_id) {
                                            $statusColor = 'label-default';
                                            switch ($doc->didox_status) {
                                                case 0: $statusColor = 'label-warning'; break; // Draft
                                                case 1: $statusColor = 'label-info'; break; // Waiting partner
                                                case 2: $statusColor = 'label-primary'; break; // Waiting your signature
                                                case 3: $statusColor = 'label-success'; break; // Signed
                                                case 4: $statusColor = 'label-danger'; break; // Rejected
                                                case 120: $statusColor = 'label-default'; break; // Canceled
                                            }
                                            echo '<span class="label ' . $statusColor . '">' . Html::encode($doc->getDidoxStatusLabel()) . '</span>';
                                        } else {
                                            echo '<span class="label label-default">Только локально</span>';
                                        }
                                        ?>
                                    </td>
                                    <td>
                                        <?php if ($doc->didox_id): ?>
                                            <code style="font-size: 10px;"><?= Html::encode($doc->didox_id) ?></code>
                                        <?php else: ?>
                                            <span class="text-muted">—</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <small><?= Yii::$app->formatter->asDatetime($doc->created_at) ?></small>
                                        <?php if ($doc->createdBy): ?>
                                            <br><small class="text-muted">
                                                <i class="fa fa-user"></i> <?= Html::encode($doc->createdBy->name) ?>
                                            </small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <a href="<?= Yii::$app->urlManager->createUrl(['/admin/didox/view', 'id' => $doc->id]) ?>" 
                                               class="btn btn-primary btn-xs" 
                                               title="Просмотреть документ">
                                                <i class="fa fa-eye"></i>
                                            </a>
                                            <?php if (!$doc->didox_id): ?>
                                                <a href="<?= Yii::$app->urlManager->createUrl(['/admin/didox/update', 'id' => $doc->id]) ?>" 
                                                   class="btn btn-warning btn-xs" 
                                                   title="Редактировать">
                                                    <i class="fa fa-edit"></i>
                                                </a>
                                            <?php endif; ?>
                                            <?php if ($doc->canBeSignedInDidox()): ?>
                                                <button type="button" 
                                                        class="btn btn-success btn-xs" 
                                                        title="Подписать"
                                                        onclick="window.open('<?= Yii::$app->urlManager->createUrl(['/admin/didox/view', 'id' => $doc->id]) ?>', '_blank')">
                                                    <i class="fa fa-certificate"></i>
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Summary Info -->
                    <div class="row" style="margin-top: 15px;">
                        <div class="col-md-12">
                            <div class="alert alert-info">
                                <h5><i class="fa fa-info-circle"></i> Информация о связанных документах</h5>
                                <p>Найдено документов DIDOX: <strong><?= count($didoxDocuments) ?></strong></p>
                                <?php 
                                $signedCount = 0;
                                $draftCount = 0;
                                foreach ($didoxDocuments as $doc) {
                                    if ($doc->didox_status == 3) $signedCount++;
                                    if ($doc->didox_status == 0) $draftCount++;
                                }
                                ?>
                                <ul style="margin-bottom: 0;">
                                    <li>Подписанных документов: <strong><?= $signedCount ?></strong></li>
                                    <li>Черновиков: <strong><?= $draftCount ?></strong></li>
                                    <li>Подключенных к DIDOX: <strong><?= count(array_filter($didoxDocuments, function($d) { return $d->didox_id; })) ?></strong></li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    
                <?php else: ?>
                    <div class="alert alert-info text-center">
                        <h4><i class="fa fa-info-circle"></i> Документы DIDOX не найдены</h4>
                        <p>Для этого заказа еще не созданы документы DIDOX.</p>
                        
                        <!-- Auto Creation Section -->
                        <div style="margin-top: 20px; padding: 15px; background: #e7f3ff; border-radius: 5px;">
                            <h5 style="color: black;"><i class="fa fa-bolt"></i> Автоматическое создание</h5>
                            <p class="text-muted small">Документы будут созданы и отправлены в DIDOX автоматически</p>
                            <div style="margin-top: 10px;">
                                <a href="<?=Yii::$app->urlManager->createUrl(['/admin/order/create-didox-invoice', 'id' => $model->id]);?>" 
                                   class="btn btn-info"
                                   onclick="return confirm('Создать счет-фактуру автоматически?')">
                                    <i class="fa fa-bolt"></i> Авто: Счет-фактура
                                </a>
                                <a href="<?=Yii::$app->urlManager->createUrl(['/admin/order/create-didox-arbitrary', 'id' => $model->id]);?>" 
                                   class="btn btn-info"
                                   onclick="return confirm('Создать произвольный договор автоматически?')">
                                    <i class="fa fa-bolt"></i> Авто: Договор
                                </a>
                                <a href="<?=Yii::$app->urlManager->createUrl(['/admin/order/create-didox-documents', 'id' => $model->id]);?>" 
                                   class="btn btn-warning"
                                   onclick="return confirm('Вы уверены, что хотите создать оба документа (счет-фактура и договор)?')">
                                    <i class="fa fa-files-o"></i> Авто: Оба документа
                                </a>
                            </div>
                        </div>
                        
                        <!-- Manual Creation Section -->
                        <div style="margin-top: 20px; padding: 15px; background: #f5f5f5; border-radius: 5px;">
                            <h5 style="color: black;"><i class="fa fa-edit"></i> Ручное создание</h5>
                            <p class="text-muted small">Заполнить форму вручную перед отправкой в DIDOX</p>
                            <div style="margin-top: 10px;">
                                <a href="<?=Yii::$app->urlManager->createUrl(['/admin/didox/create', 'orderId' => $model->id]);?>" 
                                   class="btn btn-primary">
                                    <i class="fa fa-plus"></i> Счет-фактура
                                </a>
                                <a href="<?=Yii::$app->urlManager->createUrl(['/admin/didox/create-arbitrary', 'orderId' => $model->id]);?>" 
                                   class="btn btn-success">
                                    <i class="fa fa-file-o"></i> Произвольный договор
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- DIDOX Logs Section -->
        <?php if (isset($didoxLogs) && !empty($didoxLogs)): ?>
        <div class="box box-default collapsed-box" style="margin-top: 20px;">
            <div class="box-header with-border">
                <h3 class="box-title"><i class="fa fa-history"></i> Логи DIDOX</h3>
                <div class="box-tools pull-right">
                    <button type="button" class="btn btn-box-tool" data-widget="collapse"><i class="fa fa-plus"></i></button>
                </div>
            </div>
            <div class="box-body">
                <div class="table-responsive">
                    <table class="table table-striped table-condensed" style="font-size: 0.9em;">
                        <thead>
                            <tr>
                                <th style="width: 150px;">Дата</th>
                                <th style="width: 80px;">Уровень</th>
                                <th>Сообщение</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($didoxLogs as $log): ?>
                                <?php 
                                    $rowClass = '';
                                    $labelClass = 'label-default';
                                    if ($log->level == 'error') {
                                        $rowClass = 'danger';
                                        $labelClass = 'label-danger';
                                    } elseif ($log->level == 'warning') {
                                        $rowClass = 'warning';
                                        $labelClass = 'label-warning';
                                    } elseif ($log->level == 'info') {
                                        $labelClass = 'label-info';
                                    }
                                ?>
                                <tr class="<?= $rowClass ?>">
                                    <td><?= Yii::$app->formatter->asDatetime($log->created_at) ?></td>
                                    <td><span class="label <?= $labelClass ?>"><?= Html::encode($log->level) ?></span></td>
                                    <td>
                                        <?= Html::encode($log->message) ?>
                                        <?php if ($log->data): ?>
                                            <br><small class="text-muted" style="font-family: monospace;"><?= \yii\helpers\StringHelper::truncate(Html::encode($log->data), 200) ?></small>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php endif; ?>

    </section>
</div>

<script>
// BTS Status Update Functions
function updateBtsStatus(orderId) {
    const button = $('button[onclick="updateBtsStatus(' + orderId + ')"]');
    const originalText = button.html();
    var postData = {
        '<?=Yii::$app->request->csrfParam?>': '<?=Yii::$app->request->getCsrfToken()?>'
    }
    
    button.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Обновление...');
    
    $.ajax({
        url: '<?= Yii::$app->urlManager->createUrl(['/admin/order/update-bts-status', 'id' => $model->id]) ?>',
        type: 'POST',
        data: postData,
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                // Update status displays
                if (response.results && response.results.length > 0) {
                    response.results.forEach(function(result) {
                        if (result.bts_id && result.status_label) {
                            updateBtsItemDisplay(result.bts_id, result.new_status, result.new_status_info, result.status_label);
                        }
                    });
                }
                
                showAlert('success', response.message);
            } else {
                showAlert('error', response.message);
            }
        },
        error: function() {
            showAlert('error', 'Произошла ошибка при обновлении статусов BTS');
        },
        complete: function() {
            button.prop('disabled', false).html(originalText);
        }
    });
}

function getBtsTracking(orderId) {
    const button = $('button[onclick="getBtsTracking(' + orderId + ')"]');
    const originalText = button.html();
    
    var postData = {
        '<?=Yii::$app->request->csrfParam?>': '<?=Yii::$app->request->getCsrfToken()?>'
    }
    
    button.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Загрузка...');
    
    $.ajax({
        url: '<?= Yii::$app->urlManager->createUrl(['/admin/order/get-bts-tracking', 'id' => $model->id]) ?>',
        type: 'POST',
        data: postData,
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                showTrackingModal(response.tracking_data);
            } else {
                showAlert('error', response.message);
            }
        },
        error: function() {
            showAlert('error', 'Произошла ошибка при получении данных трекинга');
        },
        complete: function() {
            button.prop('disabled', false).html(originalText);
        }
    });
}

function updateBtsItemDisplay(btsId, newStatus, newStatusInfo, statusLabel) {
    const item = $('.bts-item[data-bts-id="' + btsId + '"]');
    if (item.length) {
        // Update status label
        const statusLabelElement = item.find('.bts-status-label');
        statusLabelElement.text(statusLabel);
        
        // Update status class based on new status
        let statusClass = 'label-default';
        switch (parseInt(newStatus)) {
            case -1: statusClass = 'label-warning'; break;
            case 0: statusClass = 'label-danger'; break;
            case 1: statusClass = 'label-info'; break;
            case 2:
            case 3: statusClass = 'label-primary'; break;
            case 4:
            case 5: statusClass = 'label-warning'; break;
            case 6: statusClass = 'label-success'; break;
            case 7: statusClass = 'label-danger'; break;
            default: statusClass = 'label-info'; break;
        }
        
        statusLabelElement.removeClass('label-default label-warning label-danger label-info label-primary label-success')
                          .addClass(statusClass);
        
        // Update status details
        const statusDetails = item.find('small.text-muted');
        statusDetails.html('<strong>Статус ID:</strong> ' + (newStatus || '-') + ' | <strong>Описание:</strong> ' + (newStatusInfo || '-'));
        
        // Add animation effect
        item.addClass('alert-success').delay(2000).queue(function() {
            $(this).removeClass('alert-success').dequeue();
        });
    }
}

function showTrackingModal(trackingData) {
    let modalContent = '<div class="modal fade" id="btsTrackingModal" tabindex="-1" role="dialog">' +
                       '<div class="modal-dialog modal-lg" role="document">' +
                       '<div class="modal-content">' +
                       '<div class="modal-header">' +
                       '<button type="button" class="close" data-dismiss="modal">&times;</button>' +
                       '<h4 class="modal-title"><i class="fa fa-history"></i> История доставки BTS</h4>' +
                       '</div>' +
                       '<div class="modal-body">';
    
    if (trackingData && trackingData.length > 0) {
        trackingData.forEach(function(item) {
            modalContent += '<div class="panel panel-default">' +
                           '<div class="panel-heading">' +
                           '<strong>BTS ID:</strong> ' + item.bts_id +
                           ' <span class="label label-info pull-right">' + (item.current_status_label || 'Неизвестен') + '</span>' +
                           '</div>' +
                           '<div class="panel-body">';
            
            if (item.error) {
                modalContent += '<div class="alert alert-danger"><i class="fa fa-exclamation-triangle"></i> ' + item.error + '</div>';
            } else if (item.history && item.history.length > 0) {
                modalContent += '<div class="timeline-container">';
                
                item.history.forEach(function(entry, index) {
                    let statusColor = 'default';
                    switch (entry.status_id) {
                        case 1: statusColor = 'info'; break;
                        case 2:
                        case 3: statusColor = 'primary'; break;
                        case 4:
                        case 5: statusColor = 'warning'; break;
                        case 6: statusColor = 'success'; break;
                        case 7: statusColor = 'danger'; break;
                        default: statusColor = 'default'; break;
                    }
                    
                    modalContent += '<div class="timeline-item" style="border-left: 3px solid #' + getStatusColor(statusColor) + '; padding-left: 15px; margin-bottom: 15px; position: relative;">';
                    
                    // Timeline dot
                    modalContent += '<div style="position: absolute; left: -8px; top: 5px; width: 12px; height: 12px; border-radius: 50%; background-color: #' + getStatusColor(statusColor) + '; border: 2px solid white;"></div>';
                    
                    // Content
                    modalContent += '<div class="timeline-content">';
                    modalContent += '<div class="timeline-header" style="margin-bottom: 5px;">';
                    modalContent += '<span class="label label-' + statusColor + '">' + (entry.status_label || 'ID: ' + entry.status_id) + '</span>';
                    if (entry.formatted_date) {
                        modalContent += ' <small class="text-muted pull-right"><i class="fa fa-clock-o"></i> ' + entry.formatted_date + '</small>';
                    }
                    modalContent += '</div>';
                    
                    if (entry.message) {
                        modalContent += '<div class="timeline-message" style="margin-bottom: 5px;"><strong>' + entry.message + '</strong></div>';
                    }
                    
                    if (entry.location) {
                        modalContent += '<div class="timeline-location"><i class="fa fa-map-marker"></i> ' + entry.location + '</div>';
                    }
                    
                    if (entry.tracking_link) {
                        modalContent += '<div style="margin-top: 5px;"><a href="' + entry.tracking_link + '" target="_blank" class="btn btn-xs btn-primary"><i class="fa fa-external-link"></i> Открыть ссылку отслеживания</a></div>';
                    }
                    
                    modalContent += '</div></div>';
                });
                
                modalContent += '</div>';
            } else {
                modalContent += '<div class="alert alert-info"><i class="fa fa-info-circle"></i> История доставки пуста</div>';
            }
            
            modalContent += '</div></div>';
        });
    } else {
        modalContent += '<div class="alert alert-info"><i class="fa fa-info-circle"></i> Данные истории доставки не найдены</div>';
    }
    
    modalContent += '</div>' +
                   '<div class="modal-footer">' +
                   '<button type="button" class="btn btn-default" data-dismiss="modal"><i class="fa fa-close"></i> Закрыть</button>' +
                   '</div>' +
                   '</div></div></div>';
    
    // Remove existing modal
    $('#btsTrackingModal').remove();
    
    // Add new modal
    $('body').append(modalContent);
    $('#btsTrackingModal').modal('show');
}

function getStatusColor(statusColor) {
    const colors = {
        'default': 'cccccc',
        'info': '5bc0de',
        'primary': '337ab7',
        'warning': 'f0ad4e',
        'success': '5cb85c',
        'danger': 'd9534f'
    };
    return colors[statusColor] || colors['default'];
}

function showAlert(type, message) {
    const alertClass = type === 'success' ? 'alert-success' : 'alert-danger';
    const icon = type === 'success' ? 'fa-check' : 'fa-exclamation-triangle';
    
    const alert = '<div class="alert ' + alertClass + ' alert-dismissible" style="margin-top: 10px;">' +
                  '<button type="button" class="close" data-dismiss="alert">&times;</button>' +
                  '<i class="fa ' + icon + '"></i> ' + message +
                  '</div>';
    
    $('#bts-info-container').prepend(alert);
    
    // Auto-hide after 5 seconds
    setTimeout(function() {
        $('.alert').fadeOut();
    }, 5000);
}

// Flash message handling
<?php if (Yii::$app->session->hasFlash('bts_status_updated')): ?>
$(document).ready(function() {
    showAlert('success', '<?= Yii::$app->session->getFlash('bts_status_updated') ?>');
});
<?php endif; ?>
</script>