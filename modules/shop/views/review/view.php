<?php

$this->title = 'Отзыв';
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="content-wrapper">
    <section class="content-header">
        <h1><?=mb_substr($this->title, 0, 50, 'utf-8');?></h1>
        
        <ol class="breadcrumb">
            <li><a href="<?=Yii::$app->urlManager->createUrl(['/shop/'])?>"><i class="fa fa-dashboard"></i> Главная</a></li>
            <li class="active"><?=mb_substr($this->title, 0, 50, 'utf-8');?></li>
        </ol>
    </section>
    <section class="content">
        <div class="box box-info color-palette-box">
            <div class="box-header">
                Данные отзыва
            </div>
            <div class="box-body">
                <table class="table table-striped">
                    <tr>
                        <td>ID</td>
                        <td><?=$model->id ? $model->id : '-';?></td>
                    </tr>
                    <tr>
                        <td>Товар</td>
                        <td>
                            <?=($model->product && $model->product->name_ru) ? '<a href="'.Yii::$app->urlManager->createUrl(['/shop/product/view', 'id'=>$model->product->id]).'">'.$model->product->name_ru.'</a>' : '<small class="label bg-red">Нет</small>';?>
                        </td>
                    </tr>
                    <tr>
                        <td>Пользователь</td>
                        <td>
                            <?=($model->user && $model->user->name) ? '<a href="'.Yii::$app->urlManager->createUrl(['/shop/user/view', 'id'=>$model->user->id]).'">'.$model->user->name.'</a>' : '<small class="label bg-red">Нет</small>';?>
                        </td>
                    </tr>
                    <tr>
                        <td>Оценка</td>
                        <td>
                            <?=$model->rate ? $model->rate : '-';?>
                        </td>
                    </tr>
                    <tr>
                        <td>Дата</td>
                        <td>
                            <?=$model->date ? $model->date : '-';?>
                        </td>
                    </tr>
                </table>
            </div>
        </div> 
        <div class="box box-info color-palette-box">
            <div class="box-header">
                Отзыв
            </div>
            <div class="box-body">
                <?php if ($model->review) {?>
                    <?=$model->review;?>
                <?php } else {?>
                    <div class="alert alert-warning text-center">Отзыв не найден</div>
                <?php }?>
            </div>
        </div>
    </section>
</div>