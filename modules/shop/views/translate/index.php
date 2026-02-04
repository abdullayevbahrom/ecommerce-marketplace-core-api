<?php
use yii\helpers\Html;
use yii\bootstrap4\ActiveForm;

$this->title = 'Перевод сайта';
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
        <?php if (Yii::$app->session->hasFlash('word_saved')) {?>
            <div class="callout callout-success text-center">
                <?=Yii::$app->session->getFlash('word_saved');?>
            </div>
        <?php }?>
        <div class="box box-info color-palette-box">
            <div class="box-header">
                <div class="pull-right">
                    <a href="<?=Yii::$app->urlManager->createUrl(['/shop/translate/create']);?>" class="btn btn-primary"><i class="fa fa-plus"></i> Добавить перевод</a>
                </div>
            </div>
            <?php if ($words) {?>
                <?php $form = ActiveForm::begin(['options' => ['enctype' => 'multipart/form-data'], 'id' => 'form-profile']);?>
                    <div class="box-body">
                        <div class="row">
                            <div class="col-sm-3">
                                <img src="/assets_files/images/languages/ru.png" width="25"> Русский
                            </div>
                            <div class="col-sm-3">
                                <img src="/assets_files/images/languages/uz.png" width="25"> Узбекский
                            </div>
                            <div class="col-sm-3">
                                <img src="/assets_files/images/languages/en.png" width="25"> Английский
                            </div>
                            <div class="col-sm-3">
                                Действия
                            </div>
                        </div>
                        <div class="row" style="margin-top:20px">
                            <?php if ($words) {?>
                                <?php foreach ($words as $k => $v) {?>
                                    <input type="hidden" name="Words[ids][]" value="<?=$v->id;?>"/>
                                    <div class="col-sm-3">
                                        <input type="text" name="Words[name_ru_tr][]" value="<?=$v->name_ru;?>" class="form-control"/> 
                                    </div>
                                    <div class="col-sm-3">
                                        <input type="text" name="Words[name_uz_tr][]" value="<?=$v->name_uz;?>" class="form-control"/> 
                                    </div>
                                    <div class="col-sm-3">
                                        <input type="text" name="Words[name_en_tr][]" value="<?=$v->name_en;?>" class="form-control"/> 
                                    </div>
                                    <div class="col-sm-3">
                                        <a href="<?=Yii::$app->urlManager->createUrl(['/shop/words/remove', 'id'=>$v->id])?>" class="btn btn-danger"><i class="glyphicon glyphicon-remove"></i> Удалить</a>
                                    </div>
                                <?php }?>
                            <?php }?>
                        </div>
                    </div>
                    <div class="box-footer">
                        <div class="text-right">
                            <?=Html::submitButton('<i class="fa fa-check-square-o"></i> Сохранить', ['class'=>'btn btn-primary btn-md']);?>
                        </div>
                    </div>
                <?php ActiveForm::end();?>
            <?php } else {?>
                <div class="alert alert-warning text-center">Перевода нет</div>
            <?php }?>
        </div>
    </section>
</div>  