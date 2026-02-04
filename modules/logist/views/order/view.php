<?php
use yii\helpers\Html;
use yii\grid\GridView;

$this->title = 'Заказ #'.$model->id;
$this->params['breadcrubs'][] = $this->title;
?>

<div class="content-wrapper">
    <section class="content-header">
        <h1><?=$this->title;?></h1>
        
        <ol class="breadcrumb">
            <li><a href="<?=Yii::$app->urlManager->createUrl(['/logist/'])?>"><i class="fa fa-dashboard"></i> Главная</a></li>
            <li class="active"><?=$this->title;?></li>
        </ol>
    </section>
    <section class="content">
        <?php if (Yii::$app->session->hasFlash('order_set_status')) {?>
            <div class="alert alert-success text-center"><?=Yii::$app->session->getFlash('order_set_status');?></div>
        <?php }?>
        <div class="box box-info color-palette-box">
            <div class="box-header with-border">
                <div class="pull-right">
                    <a href="<?=Yii::$app->urlManager->createUrl(['/logist/order/set-status', 'id'=>$model->id, 'status'=>'1']);?>" class="btn btn-success">Принять</a>
                    <a href="<?=Yii::$app->urlManager->createUrl(['/logist/order/set-status', 'id'=>$model->id, 'status'=>'2']);?>" class="btn btn-danger">Отклонить</a>
                    <a href="<?=Yii::$app->urlManager->createUrl(['/logist/order/set-status', 'id'=>$model->id, 'status'=>'4']);?>" class="btn btn-info">В пути</a>
                    <a href="<?=Yii::$app->urlManager->createUrl(['/logist/order/set-status', 'id'=>$model->id, 'status'=>'5']);?>" class="btn btn-info">Доставлен</a>
                </div>
                <div class="box-title">
                    О заказе
                </div>
            </div>
            <div class="box-body">
                <table class="table table-striped">
                    <tr>
                        <td>ID заказа:</td>
                        <td><?=$model->id ? $model->id : '-';?></td>
                    </tr>
                    <tr>
                        <td>Статус магазина:</td>
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
                        <td>Оформитель заказа:</td>
                        <td><?=$model->user->name ? '<a href="'.Yii::$app->urlManager->createUrl(['/logist/user/view', 'id'=>$model->user->id]).'" target="_blank">'.$model->user->name.'</a>' : '-';?></td>
                    </tr>
                    <tr>
                        <td>Магазин:</td>
                        <td><?=$model->shop ? '<a href="'.Yii::$app->urlManager->createUrl(['/logist/shop/view', 'id'=>$model->shop->id]).'" target="_blank">'.$model->shop->name_ru.'</a>' : '-';?></td>
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
                        <td>Сумма товаров (с доставкой):</td>
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
        <div class="box box-info color-palette-box">
            <div class="box-header with-border">
                <div class="box-title">
                    Товары
                </div>
            </div>
            <div class="box-body">
                <?php if ($products) {?>
                    <?php
                        $amount = 0;
                        $price = 0;
                    ?>
                    <?php foreach ($products as $product) {?>
                        <?php if ($product->product) {?>
                            <?php $amount += $product->amount;?>
                            <?php $price += $product->price;?>
                            <div class="attachment-block clearfix">
                                <a href="<?=Yii::$app->urlManager->createUrl(['/logist/product/view', 'id'=>$product->product->id]);?>" target="_blank"><img class="attachment-img" src="<?=$product->product->getPhoto();?>" alt="Attachment Image"></a>
                                <div class="attachment-pushed">
                                    <!-- <h4 class="attachment-heading"><?=$product->product->name_ru?></h4> -->
                                    <div class="attachment-text">
                                        <table class="table table-striped">
                                            <tr>
                                                <td>Название:</td>
                                                <td><a href="<?=Yii::$app->urlManager->createUrl(['/logist/product/view', 'id'=>$product->product->id]);?>" target="_blank"><?=$product->product->name_ru;?></a></td>
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
                                                <td>Итоговая сума:</td>
                                                <td><?=number_format($product->product_price/$product->amount, 0, '.', ' ').' x '.$product->amount.' = '.number_format($product->price, 0, '.', ' ').' UZS';?></td>
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
                    Общее количество товаров: <?=$amount;?>
                    <br/>
                    Итоговая сума: <?=number_format($price);?>
                <?php } else {?>
                    <div class="alert alert-warning text-center">Товары не найдены</div>
                <?php }?>
            </div>
        </div>
    </section>
</div>