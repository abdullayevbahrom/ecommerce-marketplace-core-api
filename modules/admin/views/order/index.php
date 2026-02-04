<?php
use yii\helpers\Html;
use yii\grid\GridView;

$this->title = 'Заказы';
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="content-wrapper">
    <section class="content-header">
        <h1><?=$this->title;?></h1>
        
        <ol class="breadcrumb">
            <li><a href="<?=Yii::$app->urlManager->createUrl(['/admin/'])?>"><i class="fa fa-dashboard"></i> Home</a></li>
            <li class="active"><?=$this->title;?></li>
        </ol>
    </section>
    <section class="content">
        <?php if (Yii::$app->session->hasFlash('order_removed')) {?>
            <div class="callout callout-success text-center">
                <?=Yii::$app->session->getFlash('order_removed');?>
            </div>
        <?php }?>
        <div class="box box-info color-palette-box">
            <div class="box-header with-border">
                <div class="box-title">
                    List orders
                </div>
                <div id="action-links">
                    <a href="javascript:;" class="btn btn-danger" data-value="remove"><i class="fa fa-trash"></i> Delete</a>
                </div>
            </div>
            <div class="box-body" id="item-block">
                <?= GridView::widget([
                    'dataProvider' => $dataProvider,
                    'filterModel' => $searchModel,
                    'summary' => "Page {begin} - {end} of {totalCount} orders<br/><br/>",
                    'emptyText' => 'Заказов нет',
                    'rowOptions' => function ($model, $index, $widget, $grid) {
                        return [
                            'id' => $model['id'],
                            'url' => Yii::$app->urlManager->createUrl('/admin/order/view').'?id='.$model['id']
                        ];
                    },
                    'tableOptions' => [
                        'class'=>'table table-striped table-bordered'
                    ],
                    'columns' => [
                        ['class' => 'yii\grid\SerialColumn'],
                        [
                            'class' => 'yii\grid\CheckboxColumn'
                        ],
                        [
                            'attribute'=>'id',
                            'label'=>'<i class="fa fa-sort"></i> ID',
                            'encodeLabel' => false,
                        ],
                        [
                            'attribute'=>'user_id',
                            'label'=>'<i class="fa fa-sort"></i> User',
                            'encodeLabel' => false,
                            'format' => 'html',
                            'value' => function ($model, $key, $index, $column) {
                                return ($model->user) ? '<a href="'.Yii::$app->urlManager->createUrl(['/admin/user/view', 'id'=>$model->user->id]).'">'.$model->user->name.'</a>' : 'No data';
                            },
                        ],
                        [
                            'attribute'=>'price',
                            'label'=>'<i class="fa fa-sort"></i> Price',
                            'encodeLabel' => false,
                            'value' => function ($model, $key, $index, $column) {
                                return ($model->price) ? number_format($model->price) : 'No data';
                            },
                        ],
                        [
                            'attribute'=>'amount',
                            'label'=>'<i class="fa fa-sort"></i> Amount',
                            'encodeLabel' => false,
                            'value' => function ($model, $key, $index, $column) {
                                return ($model->amount) ? $model->amount : 'No data';
                            },
                        ],
                        [
                            'attribute'=>'status',
                            'label'=>'<i class="fa fa-sort"></i> Status',
                            'encodeLabel' => false,
                            'format' => 'html',
                            'filter' => Html::activeDropDownList($searchModel, 'status', ['0'=>'Pending', '1'=>'Accepted', 'Rejected'], ['class'=>'form-control select2','prompt' => 'Select']),
                            'value' => function ($model, $key, $index, $column) {
                                if ($model->status == 0) {
                                    return '<small class="label bg-yellow">Pending</small>';
                                }
                                if ($model->status == 1) {
                                    return '<small class="label bg-green">Accepted</small>';
                                }
                                if ($model->status == 2) {
                                    return '<small class="label bg-red">Rejected</small>';
                                }
                                if ($model->status == 4) {
                                    return '<small class="label bg-red">Return</small>';
                                }
                            },
                        ],
                        [
                            'label' => '<i class="fa fa-file-text-o"></i> DIDOX',
                            'encodeLabel' => false,
                            'format' => 'html',
                            'contentOptions' => ['style' => 'text-align: center; vertical-align: middle;'],
                            'value' => function ($model, $key, $index, $column) {
                                // Get DIDOX documents for this order
                                $didoxDocuments = \app\models\didox\DidoxDocument::find()
                                    ->where(['order_id' => $model->id])
                                    ->all();
                                
                                if (empty($didoxDocuments)) {
                                    // Check if creation was skipped due to missing info
                                    $shop = $model->shop;
                                    $shopSeller = $shop ? \app\models\shop\seller\ShopSeller::findOne(['shop_id' => $shop->id]) : null;
                                    $sellerMissing = empty($shopSeller) || empty($shopSeller->inn);
                                    
                                    if ($sellerMissing) {
                                        return '
                                            <div style="white-space: nowrap;">
                                                <span class="label label-warning" style="display: block; margin-bottom: 3px;" title="Missing Seller Info (ShopSeller/INN)">
                                                    <i class="fa fa-exclamation-triangle"></i> Нет данных продавца
                                                </span>
                                                <div class="btn-group btn-group-xs">
                                                    <a href="' . Yii::$app->urlManager->createUrl(['/admin/didox/create', 'orderId' => $model->id]) . '" 
                                                       class="btn btn-primary btn-xs" 
                                                       title="Создать счет-фактуру">
                                                        <i class="fa fa-plus"></i>
                                                    </a>
                                                    <a href="' . Yii::$app->urlManager->createUrl(['/admin/didox/create-arbitrary', 'orderId' => $model->id]) . '" 
                                                       class="btn btn-success btn-xs" 
                                                       title="Создать договор">
                                                        <i class="fa fa-file-o"></i>
                                                    </a>
                                                </div>
                                            </div>
                                        ';
                                    }

                                    // No documents - show create buttons
                                    return '
                                        <div style="white-space: nowrap;">
                                            <span class="label label-default" style="display: block; margin-bottom: 3px;">
                                                <i class="fa fa-minus"></i> Нет документов
                                            </span>
                                            <div class="btn-group btn-group-xs">
                                                <a href="' . Yii::$app->urlManager->createUrl(['/admin/didox/create', 'orderId' => $model->id]) . '" 
                                                   class="btn btn-primary btn-xs" 
                                                   title="Создать счет-фактуру">
                                                    <i class="fa fa-plus"></i>
                                                </a>
                                                <a href="' . Yii::$app->urlManager->createUrl(['/admin/didox/create-arbitrary', 'orderId' => $model->id]) . '" 
                                                   class="btn btn-success btn-xs" 
                                                   title="Создать договор">
                                                    <i class="fa fa-file-o"></i>
                                                </a>
                                            </div>
                                        </div>
                                    ';
                                } else {
                                    // Has documents - show status and count
                                    $documentCount = count($didoxDocuments);
                                    $signedCount = 0;
                                    $draftCount = 0;
                                    $connectedCount = 0;
                                    
                                    foreach ($didoxDocuments as $doc) {
                                        if ($doc->didox_status == 3) $signedCount++;
                                        if ($doc->didox_status == 0) $draftCount++;
                                        if ($doc->didox_id) $connectedCount++;
                                    }
                                    
                                    $statusColor = 'label-info';
                                    if ($signedCount > 0) {
                                        $statusColor = 'label-success';
                                    } elseif ($connectedCount > 0) {
                                        $statusColor = 'label-warning';
                                    }
                                    
                                    $statusText = '';
                                    if ($signedCount > 0) {
                                        $statusText = $signedCount . ' подписан';
                                    } elseif ($connectedCount > 0) {
                                        $statusText = $connectedCount . ' в DIDOX';
                                    } else {
                                        $statusText = $draftCount . ' черновик';
                                    }
                                    
                                    return '
                                        <div style="white-space: nowrap;">
                                            <span class="label ' . $statusColor . '" style="display: block; margin-bottom: 3px;">
                                                <i class="fa fa-file-text"></i> ' . $documentCount . ' док.
                                            </span>
                                            <small style="font-size: 10px; color: #666;">' . $statusText . '</small>
                                        </div>
                                    ';
                                }
                            },
                        ],
                        [
                            'attribute'=>'date',
                            'label'=>'<i class="fa fa-sort"></i> Date',
                            'encodeLabel' => false,
                        ],
                        [
                            'class' => 'yii\grid\ActionColumn',
                            'template' => '{delete}',
                            'buttons' => [
                                'delete' => function ($url, $model) {
                                    return Html::a('<span class="glyphicon glyphicon-trash"></span>', Yii::$app->urlManager->createUrl(['/admin/order/remove', 'id'=>$model->id]), ['class'=>'btn btn-danger remove-object']);
                                }
                            ],
                        ]
                    ],
                ]); ?>
            </div>
        </div>
    </section>
</div>  