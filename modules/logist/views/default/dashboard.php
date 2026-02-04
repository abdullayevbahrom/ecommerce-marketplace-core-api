<?php
use yii\helpers\Html;
use yii\grid\GridView;
use yii\widgets\Pjax;
use yii\widgets\LinkPager;
use yii\bootstrap4\ActiveForm;

$this->title = 'Админ панель';
$this->params['breadcrumbs'][] = $this->title;

$type = Yii::$app->request->get('type');
?>
<script src="/admin_files/bower_components/jquery/dist/jquery.min.js"></script>

<div class="content-wrapper">
    <section class="content-header">
        <h1><?=$this->title;?></h1>

        <ol class="breadcrumb">
            <li><a href="<?=Yii::$app->urlManager->createUrl(['/logist/'])?>"><i class="fa fa-dashboard"></i> Главная</a></li>
            <li class="active"><?=$this->title;?></li>
        </ol>
    </section>
    <section class="content">
        <div class="row">
            <div class="col-lg-4 col-xs-6">
                <div class="small-box bg-aqua">
                    <div class="inner">
                        <h3><?=($order_count && ($order_count > 0)) ? $order_count : 0;?></h3>
                        <p>Заказы</p>
                    </div>
                    <div class="icon">
                        <i class="fa fa-bell"></i>
                    </div>
                    <a href="<?=Yii::$app->urlManager->createUrl(['/logist/order'])?>" class="small-box-footer">Посмотреть <i class="fa fa-arrow-circle-right"></i></a>
                </div>
            </div>
        </div>
        <div class="box box-info">
            <div class="box-header with-border">
                <h3 class="box-title">
                    <?php if (!$type || ($type == 'year')) {?>
                        График заказов за год
                    <?php } else {?>
                        <?php if ($type == 'week') {?>
                            График заказов за неделю
                        <?php }?>
                        <?php if ($type == 'month') {?>
                            График заказов за месяц
                        <?php }?>
                        <?php if ($type == 'hyear') {?>
                            График заказов за пол года
                        <?php }?>
                    <?php }?>
                </h3>

                <div class="box-tools pull-right">
                    <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown" aria-expanded="false">
                        <span class="caret"></span>
                    </button>
                    <ul class="dropdown-menu">
                        <li><a href="<?=Yii::$app->urlManager->createUrl(['/logist/default/dashboard', 'type'=>'week'])?>">За неделю</a></li>
                        <li><a href="<?=Yii::$app->urlManager->createUrl(['/logist/default/dashboard', 'type'=>'month'])?>">За месяц</a></li>
                        <li><a href="<?=Yii::$app->urlManager->createUrl(['/logist/default/dashboard', 'type'=>'hyear'])?>">За пол года</a></li>
                        <li><a href="<?=Yii::$app->urlManager->createUrl(['/logist/default/dashboard', 'type'=>'year'])?>">За год</a></li>
                    </ul>
                </div>
            </div>
            <div class="box-body chart-responsive">
                <?php if ($data) {?>
                    <div class="chart" id="bar-chart" style="height: 300px;"></div>
                <?php } else {?>
                    <div class="callout callout-warning text-center">Заказов нет</div>
                <?php }?>
            </div>
        </div>
        <div class="box box-info color-palette-box">
            <div class="box-header with-border">
                <div class="box-title">
                    Последние заказы
                </div>
                <div id="action-links">
                    <a href="javascript:;" class="btn btn-danger" data-value="remove"><i class="fa fa-trash"></i> Удалить</a>
                </div>
            </div>
            <div class="box-body" id="item-block">
                <?= GridView::widget([
                    'dataProvider' => $dataProvider,
                    'filterModel' => $searchModel,
                    'summary' => "Страница {begin} - {end} из {totalCount} заказов<br/><br/>",
                    'emptyText' => 'Заказов нет',
                    'rowOptions' => function ($model, $index, $widget, $grid) {
                        return [
                            'id' => $model['id'],
                            'url' => Yii::$app->urlManager->createUrl('/logist/order/view').'?id='.$model['id']
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
                            'label'=>'<i class="fa fa-sort"></i> Пользователь',
                            'encodeLabel' => false,
                            'format' => 'html',
                            'value' => function ($model, $key, $index, $column) {
                                return ($model->user) ? '<a href="'.Yii::$app->urlManager->createUrl(['/logist/user/view', 'id'=>$model->user->id]).'">'.$model->user->name.'</a>' : 'Не указано';
                            },
                        ],
                        [
                            'attribute'=>'price',
                            'label'=>'<i class="fa fa-sort"></i> Стоимость',
                            'encodeLabel' => false,
                            'value' => function ($model, $key, $index, $column) {
                                return ($model->price) ? number_format($model->price) : 'Не указано';
                            },
                        ],
                        [
                            'attribute'=>'amount',
                            'label'=>'<i class="fa fa-sort"></i> Кол-во',
                            'encodeLabel' => false,
                            'value' => function ($model, $key, $index, $column) {
                                return ($model->amount) ? $model->amount : 'Не указано';
                            },
                        ],
                        [
                            'attribute'=>'status',
                            'label'=>'<i class="fa fa-sort"></i> Статус',
                            'encodeLabel' => false,
                            'format' => 'html',
                            'filter' => Html::activeDropDownList($searchModel, 'status', ['0'=>'В ожидании', '1'=>'Принят', 'Отклонен'], ['class'=>'form-control select2','prompt' => 'Выбрать']),
                            'value' => function ($model, $key, $index, $column) {
                                if ($model->status == 0) {
                                    return '<small class="label bg-yellow">В ожидании</small>';
                                }
                                if ($model->status == 1) {
                                    return '<small class="label bg-green">Принят</small>';
                                }
                                if ($model->status == 1) {
                                    return '<small class="label bg-red">Отклонен</small>';
                                }
                            },
                        ],
                        [
                            'attribute'=>'date',
                            'label'=>'<i class="fa fa-sort"></i> Дата',
                            'encodeLabel' => false,
                        ],
                        [
                            'class' => 'yii\grid\ActionColumn',
                            'template' => '{delete}',
                            'buttons' => [
                                'delete' => function ($url, $model) {
                                    return Html::a('<span class="glyphicon glyphicon-trash"></span>', Yii::$app->urlManager->createUrl(['/logist/order/remove', 'id'=>$model->id]), ['class'=>'btn btn-danger remove-object']);
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
                labels: ['Кол-во', 'Сумма'],
                hideHover: 'auto'
            });
        });
    </script>
<?php }?>