<?php
use yii\helpers\Html;
use yii\grid\GridView;
use yii\widgets\Pjax;
use yii\widgets\LinkPager;
use yii\bootstrap4\ActiveForm;

$this->title = 'Аналитика';
$this->params['breadcrumbs'][] = $this->title;

$type = Yii::$app->request->get('type');
?>
<script src="/admin_files/bower_components/jquery/dist/jquery.min.js"></script>

<div class="content-wrapper">
    <section class="content-header">
        <h1><?=$this->title;?></h1>

        <ol class="breadcrumb">
            <li><a href="<?=Yii::$app->urlManager->createUrl(['/shop/'])?>"><i class="fa fa-dashboard"></i> Главная</a></li>
            <li class="active"><?=$this->title;?></li>
        </ol>
    </section>
    <section class="content">
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
                        <li><a href="<?=Yii::$app->urlManager->createUrl(['/shop/analytics', 'type'=>'week'])?>">За неделю</a></li>
                        <li><a href="<?=Yii::$app->urlManager->createUrl(['/shop/analytics', 'type'=>'month'])?>">За месяц</a></li>
                        <li><a href="<?=Yii::$app->urlManager->createUrl(['/shop/analytics', 'type'=>'hyear'])?>">За пол года</a></li>
                        <li><a href="<?=Yii::$app->urlManager->createUrl(['/shop/analytics', 'type'=>'year'])?>">За год</a></li>
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