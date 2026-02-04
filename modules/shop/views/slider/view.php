<?php
use app\widgets\admin_slider_menu\AdminSliderMenu;
use app\widgets\admin_language_tab\AdminLanguageTab;

$this->title = $model ? $model->name_ru : 'Слайдер';
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="content-wrapper">
    <section class="content-header">
        <h1>
            <?=mb_substr($this->title, 0, 30, 'utf-8');?>
            <?=(mb_strlen($this->title) >= 30) ? '...' : '';?>        
        </h1>
        
        <ol class="breadcrumb">
            <li><a href="<?=Yii::$app->urlManager->createUrl(['/shop/'])?>"><i class="fa fa-dashboard"></i> Главная</a></li>
            <li class="active"><?=$this->title;?></li>
        </ol>
    </section>
    <section class="content">
        <?php if (Yii::$app->session->hasFlash('slider_saved')) {?>
            <div class="callout callout-success text-center">
                <?=Yii::$app->session->getFlash('slider_saved');?>
            </div>
        <?php }?>
        <?php if (Yii::$app->session->hasFlash('slider_locked')) {?>
            <div class="callout callout-success text-center">
                <?=Yii::$app->session->getFlash('slider_locked');?>
            </div>
        <?php }?>
        <div class="row">
            <div class="col-sm-3">
                <?=AdminSliderMenu::widget();?>
            </div>
            <div class="col-sm-9">
                <?=AdminLanguageTab::widget();?>
                <br/>
                <div class="box box-info color-palette-box">
                    <div class="box-body">
                        <table class="table table-striped">
                            <tr>
                                <td>ID</td>
                                <td><?=$model->id;?></td>
                            </tr>
                            <tr>
                                <td>Тип</td>
                                <td>
                                    <?php if ($model->type == 'site') {?>
                                        <small class="label bg-aqua">Сайт</small>
                                    <?php }?>
                                    <?php if ($model->type == 'mobile') {?>
                                        <small class="label bg-black">Мобильный</small>
                                    <?php }?>
                                </td>
                            </tr>
                            <tr>
                                <td>Статус</td>
                                <td>
                                    <?php if ($model->status == 1) {?>
                                        <small class="label bg-green">Активный</small>
                                    <?php }?>
                                    <?php if ($model->status == 2) {?>
                                        <small class="label bg-red">Заблокирован</small>
                                    <?php }?>
                                </td>
                            </tr>
                            <tr>
                                <td>Название</td>
                                <td>
                                    <div class="lang-block lang-block-ru">
                                        <?=$model->name_ru ? $model->name_ru : '-';?>
                                    </div>
                                    <div class="lang-block lang-block-uz">
                                        <?=$model->name_uz ? $model->name_uz : '-';?>
                                    </div>
                                    <div class="lang-block lang-block-en">
                                        <?=$model->name_en ? $model->name_en : '-';?>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td>Ссылка</td>
                                <td><?=$model->link ? '<a href="'.$model->link.'" target="_blank">'.$model->link.'</a>' : '-';?></td>
                            </tr>
                            <tr>
                                <td>Дата</td>
                                <td><?=$model->date ? $model->date : '-';?></td>
                            </tr>
                        </table>
                    </div>
                </div>
                <div class="box box-info color-palette-box">
                    <div class="box-header">Описание</div>
                    <div class="box-body">
                        <div class="lang-block lang-block-ru">
                            <?=$model->content_ru ? $model->content_ru : '<div class="alert alert-warning text-center">Текст не найден</div>';?>
                        </div>
                        <div class="lang-block lang-block-uz">
                            <?=$model->content_uz ? $model->content_uz : '<div class="alert alert-warning text-center">Текст не найден</div>';?>
                        </div>
                        <div class="lang-block lang-block-en">
                            <?=$model->content_en ? $model->content_en : '<div class="alert alert-warning text-center">Текст не найден</div>';?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>