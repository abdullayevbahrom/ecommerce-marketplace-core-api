<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model app\models\didox\DidoxDocument */
/* @var $users array */

$this->title = 'Создать счет-фактуру DIDOX';
$this->params['breadcrumbs'][] = ['label' => 'Документы DIDOX', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="content-wrapper">
    <section class="content-header">
        <h1><?= Html::encode($this->title) ?></h1>
        <ol class="breadcrumb">
            <li><a href="<?= Yii::$app->urlManager->createUrl(['/admin/']) ?>"><i class="fa fa-dashboard"></i> Главная</a></li>
            <li><a href="<?= Yii::$app->urlManager->createUrl(['/admin/didox']) ?>">Документы DIDOX</a></li>
            <li class="active"><?= Html::encode($this->title) ?></li>
        </ol>
    </section>
    <section class="content">
        <!-- Flash Messages -->
        <?php if (Yii::$app->session->hasFlash('error')): ?>
            <div class="callout callout-danger">
                <h4><i class="fa fa-ban"></i> Ошибка!</h4>
                <p><?= Yii::$app->session->getFlash('error') ?></p>
            </div>
        <?php endif; ?>
        
        <?php if (Yii::$app->session->hasFlash('warning')): ?>
            <div class="callout callout-warning">
                <h4><i class="fa fa-warning"></i> Предупреждение!</h4>
                <p><?= Yii::$app->session->getFlash('warning') ?></p>
            </div>
        <?php endif; ?>
        
        <?php if (Yii::$app->session->hasFlash('success')): ?>
            <div class="callout callout-success">
                <h4><i class="fa fa-check"></i> Успешно!</h4>
                <p><?= Yii::$app->session->getFlash('success') ?></p>
            </div>
        <?php endif; ?>
        
        <?php if (Yii::$app->session->hasFlash('info')): ?>
            <div class="callout callout-info">
                <h4><i class="fa fa-info"></i> Информация</h4>
                <p><?= Yii::$app->session->getFlash('info') ?></p>
            </div>
        <?php endif; ?>

        <div class="callout callout-info">
            <h4><i class="fa fa-info-circle"></i> Создание счет-фактуры</h4>
            <p>Создание счет-фактуры автоматически создаст документ типа 002 на платформе DIDOX и сохранит локально. Убедитесь, что вы аутентифицированы с E-IMZO.</p>
        </div>

        <?= $this->render('_form', [
            'model' => $model,
            'users' => $users,
            'currentAdminTin' => $currentAdminTin,
        ]) ?>
    </section>
</div> 