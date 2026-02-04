<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model app\models\Promocode */

$this->title = 'Создать промокод';
$this->params['breadcrumbs'][] = ['label' => 'Промокоды', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="content-wrapper">
    <section class="content-header">
        <h1><?= Html::encode($this->title) ?></h1>
    </section>

    <section class="content">
        <div class="box box-primary">
            <div class="box-body">
                <?= $this->render('_form', [
                    'model' => $model,
                ]) ?>
            </div>
        </div>
    </section>
</div>
