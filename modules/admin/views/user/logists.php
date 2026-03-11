<?php
use yii\helpers\Html;
use yii\grid\GridView;

$this->title = 'Логисты';
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
                <h3 class="box-title"><i class="fa fa-truck"></i> Список логистов</h3>
                <div class="pull-right">
                    <a href="<?=Yii::$app->urlManager->createUrl(['/admin/user/create', 'role' => \app\models\user\User::ROLE_LOGIST])?>" class="btn btn-primary">
                        <i class="fa fa-plus"></i> Добавить логиста
                    </a>
                </div>
            </div>
            <div class="box-body" id="item-block">
                <?= GridView::widget([
                    'dataProvider' => $dataProvider,
                    'filterModel' => $searchModel,
                    'summary' => "Показано {begin} - {end} из {totalCount} логистов<br/><br/>",
                    'emptyText' => 'Логисты не найдены',
                    'rowOptions' => function ($model, $index, $widget, $grid) {
                        return [
                            'id' => $model['id'],
                            'url' => Yii::$app->urlManager->createUrl('/admin/user/view').'?id='.$model['id']
                        ];
                    },
                    'pager' => [
                        'options'=>['class'=>'pagination'],
                        'pageCssClass' => 'page-item',
                        'prevPageLabel' => 'Назад',
                        'nextPageLabel' => 'Вперед',
                        'maxButtonCount'=>10,
                        'linkOptions' => [
                            'class' => 'page-link'
                        ]
                    ],
                    'tableOptions' => [
                        'class'=>'table table-striped table-bordered'
                    ],
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
                            'value' => function ($model) {
                                $parts = array_filter([$model->lastname, $model->name, $model->middlename]);
                                return $parts ? implode(' ', $parts) : 'Нет данных';
                            },
                        ],
                        [
                            'attribute'=>'login',
                            'label'=>'<i class="fa fa-sort"></i> Логин',
                            'encodeLabel' => false,
                            'value' => function ($model) {
                                return $model->login ?: 'Нет данных';
                            },
                        ],
                        [
                            'attribute'=>'phone',
                            'label'=>'<i class="fa fa-sort"></i> Телефон',
                            'encodeLabel' => false,
                        ],
                        [
                            'attribute'=>'email',
                            'label'=>'<i class="fa fa-sort"></i> E-mail',
                            'encodeLabel' => false,
                            'value' => function ($model) {
                                return $model->email ?: 'Нет данных';
                            },
                        ],
                        [
                            'attribute'=>'status',
                            'label'=>'<i class="fa fa-sort"></i> Статус',
                            'encodeLabel' => false,
                            'format' => 'html',
                            'value' => function ($model) {
                                if ($model->status == 1) {
                                    return '<small class="label bg-green">Активен</small>';
                                }
                                if ($model->status == 2) {
                                    return '<small class="label bg-red">Заблокирован</small>';
                                }
                                return '<small class="label bg-yellow">Ожидает</small>';
                            },
                        ],
                        [
                            'attribute'=>'date',
                            'label'=>'<i class="fa fa-sort"></i> Дата',
                            'encodeLabel' => false,
                            'value' => function ($model) {
                                return $model->date ?: 'Нет данных';
                            },
                        ],
                    ],
                ]); ?>
            </div>
        </div>
    </section>
</div>
