<?php
use yii\helpers\Html;
use yii\grid\GridView;
use yii\widgets\Pjax;
use yii\widgets\LinkPager;
use yii\bootstrap4\ActiveForm;

$this->title = 'Admin panel';
$this->params['breadcrumbs'][] = $this->title;

$type = Yii::$app->request->get('type');
?>
<script src="/admin_files/bower_components/jquery/dist/jquery.min.js"></script>

<div class="content-wrapper">
    <section class="content-header">
        <h1><?=$this->title;?></h1>

        <ol class="breadcrumb">
            <li><a href="<?=Yii::$app->urlManager->createUrl(['/admin/'])?>"><i class="fa fa-dashboard"></i> Home</a></li>
            <li class="active"><?=$this->title;?></li>
        </ol>
    </section>
    <section class="content">
        <div class="row">
            <!-- <div class="col-lg-3 col-xs-6">
                <div class="small-box bg-red">
                    <div class="inner">
                        <h3><?=($shop_count && ($shop_count > 0)) ? $shop_count : 0;?></h3>
                        <p>Shopsы</p>
                    </div>
                    <div class="icon">
                        <i class="fa fa-building"></i>
                    </div>
                    <a href="<?=Yii::$app->urlManager->createUrl(['/admin/shop'])?>" class="small-box-footer">View <i class="fa fa-arrow-circle-right"></i></a>
                </div>
            </div> -->
            <div class="col-lg-4 col-xs-6">
                <div class="small-box bg-green">
                    <div class="inner">
                        <h3><?=($product_count && ($product_count > 0)) ? $product_count : 0;?></h3>
                        <p>Products</p>
                    </div>
                    <div class="icon">
                        <i class="fa fa-shopping-cart"></i>
                    </div>
                    <a href="<?=Yii::$app->urlManager->createUrl(['/admin/product'])?>" class="small-box-footer">View <i class="fa fa-arrow-circle-right"></i></a>
                </div>
            </div>
            <div class="col-lg-4 col-xs-6">
                <div class="small-box bg-red">
                    <div class="inner">
                        <h3><?=($user_count && ($user_count > 0)) ? $user_count : 0;?></h3>
                        <p>Users</p>
                    </div>
                    <div class="icon">
                        <i class="fa fa-group"></i>
                    </div>
                    <a href="<?=Yii::$app->urlManager->createUrl(['/admin/user'])?>" class="small-box-footer">View <i class="fa fa-arrow-circle-right"></i></a>
                </div>
            </div>
            <div class="col-lg-4 col-xs-6">
                <div class="small-box bg-aqua">
                    <div class="inner">
                        <h3><?=($order_count && ($order_count > 0)) ? $order_count : 0;?></h3>
                        <p>Orders</p>
                    </div>
                    <div class="icon">
                        <i class="fa fa-bell"></i>
                    </div>
                    <a href="<?=Yii::$app->urlManager->createUrl(['/admin/order'])?>" class="small-box-footer">View <i class="fa fa-arrow-circle-right"></i></a>
                </div>
            </div>
        </div>
        <div class="box box-info">
            <div class="box-header with-border">
                <h3 class="box-title">
                    <?php if (!$type || ($type == 'year')) {?>
                        Order schedule for yerar
                    <?php } else {?>
                        <?php if ($type == 'week') {?>
                            Order schedule for week
                        <?php }?>
                        <?php if ($type == 'month') {?>
                            Order schedule for month
                        <?php }?>
                        <?php if ($type == 'hyear') {?>
                            Order schedule for half a year
                        <?php }?>
                    <?php }?>
                </h3>

                <div class="box-tools pull-right">
                    <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown" aria-expanded="false">
                        <span class="caret"></span>
                    </button>
                    <ul class="dropdown-menu">
                        <li><a href="<?=Yii::$app->urlManager->createUrl(['/admin/default/dashboard', 'type'=>'week'])?>">Week</a></li>
                        <li><a href="<?=Yii::$app->urlManager->createUrl(['/admin/default/dashboard', 'type'=>'month'])?>">Month</a></li>
                        <li><a href="<?=Yii::$app->urlManager->createUrl(['/admin/default/dashboard', 'type'=>'hyear'])?>">Half of year</a></li>
                        <li><a href="<?=Yii::$app->urlManager->createUrl(['/admin/default/dashboard', 'type'=>'year'])?>">Year</a></li>
                    </ul>
                </div>
            </div>
            <div class="box-body chart-responsive">
                <?php if ($data) {?>
                    <div class="chart" id="bar-chart" style="height: 300px;"></div>
                <?php } else {?>
                    <div class="callout callout-warning text-center">No orders</div>
                <?php }?>
            </div>
        </div>
        <div class="box box-info color-palette-box">
            <div class="box-header with-border">
                <div class="box-title">
                    Last Orders
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
                    'emptyText' => 'No orders',
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
                                return ($model->user) ? '<a href="'.Yii::$app->urlManager->createUrl(['/admin/user/view', 'id'=>$model->user->id]).'">'.$model->user->name.'</a>' : 'No date';
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
                                if ($model->status == 1) {
                                    return '<small class="label bg-red">Rejected</small>';
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
        <div class="box box-info color-palette-box">
            <div class="box-header with-border">
                <div id="action-links">
                    <a href="javascript:;" class="btn btn-danger" data-value="remove"><i class="fa fa-trash"></i> Delete</a>
                </div>
            </div>
            <div class="box-body" id="item-block">
                <?= GridView::widget([
                    'dataProvider' => $dataProviderReview,
                    'filterModel' => $searchModelReview,
                    'summary' => "Page {begin} - {end} from {totalCount} reviews<br/><br/>",
                    'emptyText' => 'No review',
                    'rowOptions' => function ($model, $index, $widget, $grid) {
                        return [
                            'id' => $model['id'],
                            'url' => Yii::$app->urlManager->createUrl('/admin/review/view').'?id='.$model['id']
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
                            'attribute'=>'product_id',
                            'label'=>'<i class="fa fa-sort"></i> Product',
                            'encodeLabel' => false,
                            'format' => 'html',
                            'filter' => Html::activeDropDownList($searchModelReview, 'product_id', $products, ['class'=>'form-control select2','prompt' => 'Select']),
                            'value' => function ($model, $key, $index, $column) {
                                return ($model->product && $model->product->name_ru) ? '<a href="'.Yii::$app->urlManager->createUrl(['/admin/product/view', 'id'=>$model->product->id]).'">'.$model->product->name_ru.'</a>' : '<small class="label bg-red">No</small>';
                            },
                        ],
                        [
                            'attribute'=>'user_id',
                            'label'=>'<i class="fa fa-sort"></i> User',
                            'encodeLabel' => false,
                            'format' => 'html',
                            'filter' => Html::activeDropDownList($searchModelReview, 'user_id', $users, ['class'=>'form-control select2','prompt' => 'Select']),
                            'value' => function ($model, $key, $index, $column) {
                                return ($model->user && $model->user->name) ? '<a href="'.Yii::$app->urlManager->createUrl(['/admin/user/view', 'id'=>$model->user->id]).'">'.$model->user->name.'</a>' : '<small class="label bg-red">No</small>';
                            },
                        ],
                        [
                            'attribute'=>'rate',
                            'label'=>'<i class="fa fa-sort"></i> Rate',
                            'encodeLabel' => false,
                        ],
                        [
                            'attribute'=>'date',
                            'label'=>'<i class="fa fa-sort"></i> Date',
                            'encodeLabel' => false,
                        ],
                        [
                            'class' => 'yii\grid\ActionColumn',
                            'template' => '{update} {delete}',
                            'buttons' => [
                                'update' => function ($url, $model) {
                                    return Html::a('<span class="glyphicon glyphicon-pencil"></span>', Yii::$app->urlManager->createUrl(['/admin/review/create', 'id'=>$model->id]), ['class'=>'btn btn-warning']);
                                },
                                'delete' => function ($url, $model) {
                                    return Html::a('<span class="glyphicon glyphicon-trash"></span>', Yii::$app->urlManager->createUrl(['/admin/review/remove', 'id'=>$model->id]), ['class'=>'btn btn-danger remove-object']);
                                }
                            ],
                        ]
                    ],
                ]); ?>
            </div>
        </div>
    </section>
</div>

<?php if ($data) {?>
    <script>
        $(function() {
            var bar = new Morris.Bar({
                element: 'bar-chart',
                resize: true,
                data: [
                    <?php foreach ($data as $k => $v) {?>
                        {y: '<?=$k;?>', a: <?=$v['amount'];?>, b: <?=$v['price'];?>},
                    <?php }?>
                ],
                barColors: ['#00a65a', '#f56954'],
                xkey: 'y',
                ykeys: ['a', 'b'],
                labels: ['Amount', 'Sum'],
                hideHover: 'auto'
            });
        });
    </script>
<?php }?>