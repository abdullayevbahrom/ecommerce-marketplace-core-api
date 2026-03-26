<?php
use yii\helpers\Html;
use yii\grid\GridView;
use app\models\shop\Shop;

$this->title = 'Менеджеры';
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="content-wrapper">
    <section class="content-header">
        <h1><?=$this->title;?></h1>
        <ol class="breadcrumb">
            <li><a href="<?=Yii::$app->urlManager->createUrl(['/admin/'])?>"><i class="fa fa-dashboard"></i> Главная</a></li>
            <li class="active"><?=$this->title;?></li>
        </ol>
    </section>
    <section class="content">
        <div class="box box-info color-palette-box">
            <div class="box-header with-border">
                <h3 class="box-title"><i class="fa fa-user-secret"></i> Пользователи с ролью «Менеджер»</h3>
            </div>
            <div class="box-body" id="item-block">
                <?= GridView::widget([
                    'dataProvider' => $dataProvider,
                    'filterModel' => $searchModel,
                    'summary' => "Показано {begin} - {end} из {totalCount} менеджеров<br/><br/>",
                    'emptyText' => 'Менеджеры не найдены',
                    'rowOptions' => function ($model) {
                        return [
                            'id' => $model['id'],
                            'url' => Yii::$app->urlManager->createUrl('/admin/user/view').'?id='.$model['id']
                        ];
                    },
                    'pager' => [
                        'options'=>['class'=>'pagination'],
                        'prevPageLabel' => 'Назад',
                        'nextPageLabel' => 'Вперед',
                        'maxButtonCount'=>10,
                        'linkOptions' => ['class' => 'page-link']
                    ],
                    'tableOptions' => ['class'=>'table table-striped table-bordered'],
                    'columns' => [
                        ['class' => 'yii\grid\SerialColumn'],
                        [
                            'label' => 'Фото',
                            'format' => 'html',
                            'value' => function($data) { return Html::img($data->getPhoto('50x50'), ['width'=>'30']); },
                        ],
                        [
                            'attribute'=>'id',
                            'label'=>'<i class="fa fa-sort"></i> ID',
                            'encodeLabel' => false,
                        ],
                        [
                            'attribute'=>'name',
                            'label'=>'<i class="fa fa-sort"></i> Имя',
                            'encodeLabel' => false,
                            'format' => 'html',
                            'value' => function ($model) {
                                $parts = array_filter([$model->lastname, $model->name, $model->middlename]);
                                $name = $parts ? implode(' ', $parts) : 'Нет данных';
                                $url = Yii::$app->urlManager->createUrl(['/admin/user/view', 'id' => $model->id]);
                                return Html::a($name, $url, ['class' => 'text-primary']);
                            },
                        ],
                        [
                            'attribute'=>'phone',
                            'label'=>'<i class="fa fa-sort"></i> Телефон',
                            'encodeLabel' => false,
                        ],
                        [
                            'label' => '<i class="fa fa-shopping-bag"></i> Магазин',
                            'encodeLabel' => false,
                            'format' => 'html',
                            'value' => function ($model) {
                                if (!$model->shop_id) {
                                    return '<span class="text-muted">Не привязан</span>';
                                }
                                $shop = Shop::findOne($model->shop_id);
                                if (!$shop) {
                                    return '<span class="text-muted">Магазин #' . $model->shop_id . '</span>';
                                }
                                $shopUrl = Yii::$app->urlManager->createUrl(['/admin/shop/view', 'id' => $shop->id]);
                                return Html::a(
                                    '<i class="fa fa-external-link"></i> ' . Html::encode($shop->name_ru ?: "Магазин #{$shop->id}"),
                                    $shopUrl,
                                    ['class' => 'text-primary']
                                );
                            },
                        ],
                        [
                            'attribute'=>'status',
                            'label'=>'<i class="fa fa-sort"></i> Статус',
                            'encodeLabel' => false,
                            'format' => 'html',
                            'filter' => Html::activeDropDownList($searchModel, 'status', ['1'=>'Активен', '2'=>'Заблокирован'], ['class'=>'form-control select2','prompt' => 'Все']),
                            'value' => function ($model) {
                                if ($model->status == 1) return '<small class="label bg-green">Активен</small>';
                                if ($model->status == 2) return '<small class="label bg-red">Заблокирован</small>';
                                return '<small class="label bg-yellow">Ожидает</small>';
                            },
                        ],
                        [
                            'attribute'=>'date',
                            'label'=>'<i class="fa fa-sort"></i> Дата',
                            'encodeLabel' => false,
                        ],
                    ],
                ]); ?>
            </div>
        </div>
    </section>
</div>
