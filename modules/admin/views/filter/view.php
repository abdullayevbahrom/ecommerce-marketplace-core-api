<?php
use app\widgets\admin_language_tab\AdminLanguageTab;

$this->title = 'Фильтр';
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
                            <li><a href="<?=Yii::$app->urlManager->createUrl(['/admin/filter/create']);?>">Добавить фильтр</a></li>
                            <li><a href="<?=Yii::$app->urlManager->createUrl(['/admin/filter/create', 'id'=>$model->id]);?>">Редактировать</a></li>
                            <li><a href="<?=Yii::$app->urlManager->createUrl(['/admin/filter/lock', 'id'=>$model->id]);?>"><?=($model->status == 1) ? 'Заблокировать' : 'Разблокировать';?></a></li>
                            <li><a href="<?=Yii::$app->urlManager->createUrl(['/admin/filter/remove', 'id'=>$model->id]);?>" class="remove-object">Удалить</a></li>
                        </ul>
                    </div>
                </div>
                <strong>Информация о фильтре</strong>
            </div>
            <div class="box-body">
                <?=AdminLanguageTab::widget();?>
                <br/>
                <table class="table table-striped">
                    <tr>
                        <td>ID</td>
                        <td><?=$model->id ? $model->id : 'Нет данных';?></td>
                    </tr>
                    <tr>
                        <td>Тип</td>
                        <td>
                            <?php if ($model->type == 'select') {?>
                                <small class="label bg-black">Выбор одного варианта</small>
                            <?php }?>
                            <?php if ($model->type == 'checkbox') {?>
                                <small class="label bg-blue">Чекбокс</small>
                            <?php }?>
                            <?php if ($model->type == 'input') {?>
                                <small class="label bg-aqua">Ввод текста</small>
                            <?php }?>
                            <?php if ($model->type == 'file') {?>
                                <small class="label bg-yellow">File</small>
                            <?php }?>
                        </td>
                    </tr>
                    <tr>
                        <td>Название</td>
                        <td>
                            <div class="lang-block lang-block-ru">
                                <?=$model->name_ru ? $model->name_ru : 'Нет данных';?>
                            </div>
                            <div class="lang-block lang-block-uz">
                                <?=$model->name_uz ? $model->name_uz : 'Нет данных';?>
                            </div>
                            <div class="lang-block lang-block-en">
                                <?=$model->name_en ? $model->name_en : 'Нет данных';?>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td>Категория</td>
                        <td><?=$model->category ? $model->category->name_ru : 'Нет данных';?></td>
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
                        <td><?=$model->date ? $model->date : 'Нет данных';?></td>
                    </tr>
                </table>
                <?php if ($model->childs) {?>
                    <hr/>
                    <strong>Варианты фильтра</strong>
                    <div class="table-responsive" style="margin-top:20px">
                        <table class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Название варианта</th>
                                    <th>Значение варианта</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($model->childs as $index => $child) {?>
                                    <tr>
                                        <td><?= $index + 1 ?></td>
                                        <td>
                                            <div class="lang-block lang-block-ru">
                                                <strong>RU:</strong> <?=$child->name_ru ? $child->name_ru : '<em>Нет данных</em>';?>
                                            </div>
                                            <div class="lang-block lang-block-uz" style="display:none">
                                                <strong>UZ:</strong> <?=$child->name_uz ? $child->name_uz : '<em>Нет данных</em>';?>
                                            </div>
                                            <div class="lang-block lang-block-en" style="display:none">
                                                <strong>EN:</strong> <?=$child->name_en ? $child->name_en : '<em>Нет данных</em>';?>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="lang-block lang-block-ru">
                                                <strong>RU:</strong> <?=$child->value_ru ? $child->value_ru : '<em>Нет данных</em>';?>
                                            </div>
                                            <div class="lang-block lang-block-uz" style="display:none">
                                                <strong>UZ:</strong> <?=$child->value_uz ? $child->value_uz : '<em>Нет данных</em>';?>
                                            </div>
                                            <div class="lang-block lang-block-en" style="display:none">
                                                <strong>EN:</strong> <?=$child->value_en ? $child->value_en : '<em>Нет данных</em>';?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php }?>
                            </tbody>
                        </table>
                    </div>
                <?php }?>
            </div>
        </div>
    </section>
</div>