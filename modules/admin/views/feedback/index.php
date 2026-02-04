<?php

use yii\helpers\Html;
use yii\helpers\Url;
use yii\grid\GridView;

/* @var $this yii\web\View */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = 'Поддержка';
$this->params['breadcrumbs'][] = $this->title;

// Подключаем CSS и JS для Swiper Slider через CDN
$this->registerCssFile('https://unpkg.com/swiper/swiper-bundle.min.css');
$this->registerJsFile('https://unpkg.com/swiper/swiper-bundle.min.js', ['depends' => [\yii\web\JqueryAsset::class]]);

?>

<style>
    .feedback-slider-container {
        position: relative;
        padding: 0 40px;
        margin-bottom: 10px;
    }
    .swiper-slide {
        height: auto;
        display: flex;
        flex-direction: column;
    }
    .box.box-solid {
        flex-grow: 1;
        display: flex;
        flex-direction: column;
    }
    .box-body {
        flex-grow: 1;
    }
    .swiper-button-next, .swiper-button-prev {
        color: #333;
    }
    .swiper-pagination-bullet-active {
        background: #333;
    }
</style>

<!-- <div class="content-wrapper">
    <section class="content-header">
        <h1><?= Html::encode($this->title) ?></h1>
        
        <ol class="breadcrumb">
            <li><a href="<?= Url::to(['/admin/']) ?>"><i class="fa fa-dashboard"></i> Главная</a></li>
            <li class="active"><?= Html::encode($this->title) ?></li>
        </ol>
    </section>
    <section class="content">
        
        <div class="feedback-slider-container">
            <div class="swiper-container">
                <div class="swiper-wrapper">
                    <?php foreach ($dataProvider->getModels() as $key => $model): ?>
                        <div class="swiper-slide">
                            <div class="box box-solid box-primary">
                                <div class="box-header with-border">
                                    <h3 class="box-title"><?= Html::encode($model->type) ?></h3>
                                </div>
                                <div class="box-body">
                                    <p><strong>Дата обращения:</strong></p>
                                    <p><?= Yii::$app->formatter->asDatetime($model->date, 'medium') ?></p>
                                </div>
                                <div class="box-footer text-right">
                                    <?= Html::a('<span class="glyphicon glyphicon-eye-open"></span> Просмотр', Url::to(['view', 'id' => $model->id]), [
                                        'class' => 'btn btn-sm btn-default',
                                        'title' => Yii::t('yii', 'View'),
                                    ]) ?>
                                    <?= Html::a('<span class="glyphicon glyphicon-trash"></span> Удалить', Url::to(['delete', 'id' => $model->id]), [
                                        'class' => 'btn btn-sm btn-danger',
                                        'title' => Yii::t('yii', 'Delete'),
                                        'data-confirm' => Yii::t('yii', 'Are you sure you want to delete this item?'),
                                        'data-method' => 'post',
                                    ]) ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <div class="swiper-pagination"></div>

            <div class="swiper-button-next"></div>
            <div class="swiper-button-prev"></div>
        </div>
    </section>
</div>

<?php
$this->registerJs(<<<JS
    var swiper = new Swiper('.swiper-container', {
        slidesPerView: 3,
        spaceBetween: 30,
        loop: false,
        pagination: {
            el: '.swiper-pagination',
            clickable: true,
        },
        navigation: {
            nextEl: '.swiper-button-next',
            prevEl: '.swiper-button-prev',
        },
        breakpoints: {
            1200: {
                slidesPerView: 3,
                spaceBetween: 30
            },
            992: {
                slidesPerView: 2,
                spaceBetween: 20
            },
            768: {
                slidesPerView: 1,
                spaceBetween: 10
            }
        }
    });
JS
, \yii\web\View::POS_READY);
?> -->
<div class="content-wrapper">
    <section class="content-header">
        <h1><?=$this->title;?></h1>
        
        <ol class="breadcrumb">
            <li><a href="<?=Yii::$app->urlManager->createUrl(['/admin/'])?>"><i class="fa fa-dashboard"></i> Home</a></li>
            <li class="active"><?=$this->title;?></li>
        </ol>
    </section>
    <section class="content">


    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'columns' => [
            ['class' => 'yii\grid\SerialColumn'],

            // 'id',
            'type',
            'date',
            [
                'class' => 'yii\grid\ActionColumn',
                'template' => '{view} {delete}',
                'buttons' => [
                    'view' => function ($url, $model, $key) {
                        return Html::a('<span class="glyphicon glyphicon-eye-open"></span>', $url, [
                            'title' => Yii::t('yii', 'View'),
                        ]);
                    },
                    'delete' => function ($url, $model, $key) {
                        return Html::a('<span class="glyphicon glyphicon-trash"></span>', $url, [
                            'title' => Yii::t('yii', 'Delete'),
                            'data-confirm' => Yii::t('yii', 'Are you sure you want to delete this item?'),
                            'data-method' => 'post',
                        ]);
                    },
                ],
            ],
        ],
    ]); ?>
</section>

</div>
