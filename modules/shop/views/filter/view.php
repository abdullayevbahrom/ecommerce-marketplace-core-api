<?php
use app\widgets\admin_language_tab\AdminLanguageTab;

$this->title = 'Фильтр';
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="content-wrapper">
    <section class="content-header">
        <h1><?=$this->title;?></h1>
        
        <ol class="breadcrumb">
            <li><a href="<?=Yii::$app->urlManager->createUrl(['/shop/'])?>"><i class="fa fa-dashboard"></i> Главная</a></li>
            <li class="active"><?=$this->title;?></li>
        </ol>
    </section>
    <section class="content">
        <?php if (Yii::$app->session->hasFlash('filter_locked')) {?>
            <div class="callout callout-success text-center">
                <?=Yii::$app->session->getFlash('filter_locked');?>
            </div>
        <?php }?>
        <?php if (Yii::$app->session->hasFlash('filter_saved')) {?>
            <div class="callout callout-success text-center">
                <?=Yii::$app->session->getFlash('filter_saved');?>
            </div>
        <?php }?>
        <div class="box box-info color-palette-box">
            <div class="box-header">
                <div class="pull-right">
                    <div class="btn-group">
                        <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown">
                            <span class="fa fa-cog"></span>
                        </button>
                        <ul class="dropdown-menu pull-right">
                            <li><a href="<?=Yii::$app->urlManager->createUrl(['/shop/filter/create']);?>">Добавить фильтр</a></li>
                            <li><a href="<?=Yii::$app->urlManager->createUrl(['/shop/filter/create', 'id'=>$model->id]);?>">Редактировать</a></li>
                            <li><a href="<?=Yii::$app->urlManager->createUrl(['/shop/filter/lock', 'id'=>$model->id]);?>"><?=($model->status == 1) ? 'Заблокировать' : 'Разблокировать';?></a></li>
                            <li><a href="<?=Yii::$app->urlManager->createUrl(['/shop/filter/remove', 'id'=>$model->id]);?>" class="remove-object">Удалить</a></li>
                        </ul>
                    </div>
                </div>
                <strong>Данные фильтра</strong>
            </div>
            <div class="box-body">
                <?=AdminLanguageTab::widget();?>
                <br/>
                <table class="table table-striped">
                    <tr>
                        <td>ID</td>
                        <td><?=$model->id ? $model->id : 'Не указано';?></td>
                    </tr>
                    <tr>
                        <td>Тип</td>
                        <td>
                            <?php if ($model->type == 'select') {?>
                                <small class="label bg-black">Выбор одной опции</small>
                            <?php }?>
                            <?php if ($model->type == 'checkbox') {?>
                                <small class="label bg-blue">Выбор нескольких опций</small>
                            <?php }?>
                            <?php if ($model->type == 'input') {?>
                                <small class="label bg-aqua">Ввод текста</small>
                            <?php }?>
                        </td>
                    </tr>
                    <tr>
                        <td>Название</td>
                        <td>
                            <div class="lang-block lang-block-ru">
                                <?=$model->name_ru ? $model->name_ru : 'Не указано';?>
                            </div>
                            <div class="lang-block lang-block-uz">
                                <?=$model->name_uz ? $model->name_uz : 'Не указано';?>
                            </div>
                            <div class="lang-block lang-block-en">
                                <?=$model->name_en ? $model->name_en : 'Не указано';?>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td>Категория</td>
                        <td><?=$model->category ? $model->category->name_ru : 'Не указано';?></td>
                    </tr>
                    <tr>
                        <td>Статус</td>
                        <td>
                            <?php if ($model->status == 0) {?>
                                <small class="label bg-red">Заблокирован</small>
                            <?php }?>
                            <?php if ($model->status == 1) {?>
                                <small class="label bg-green">Активный</small>
                            <?php }?>
                        </td>
                    </tr>
                    <tr>
                        <td>Дата создания</td>
                        <td><?=$model->date ? $model->date : 'Не указано';?></td>
                    </tr>
                </table>
                <?php if ($model->childs) {?>
                    <hr/>
                    <strong>Пункты</strong>
                    <div class="row" style="margin-top:20px">
                        <?php foreach ($model->childs as $child) {?>
                            <div class="col-sm-2">
                                <div class="lang-block lang-block-ru">
                                    <?=$child->value_ru ? $child->value_ru : 'Не указано';?>
                                </div>
                                <div class="lang-block lang-block-uz">
                                    <?=$child->value_uz ? $child->value_uz : 'Не указано';?>
                                </div>
                                <div class="lang-block lang-block-en">
                                    <?=$child->value_en ? $child->value_en : 'Не указано';?>
                                </div>
                            </div>
                        <?php }?>
                    </div>
                <?php }?>
            </div>
        </div>
    </section>
</div>