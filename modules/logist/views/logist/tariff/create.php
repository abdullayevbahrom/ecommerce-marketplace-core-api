<?php
use yii\helpers\Html;
use yii\bootstrap4\ActiveForm;
use yii\helpers\ArrayHelper;

use app\widgets\admin_logist_menu\AdminLogistMenu;

$this->title = 'Добавить регион/тариф';
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
        <div class="row">
            <div class="col-sm-3">
                <?=AdminLogistMenu::widget();?>
            </div>
            <div class="col-sm-9">
                <?php $form = ActiveForm::begin(['options' => ['enctype' => 'multipart/form-data']]);?>
                    <div class="box box-info color-palette-box">
                        <div class="box-header">
                            Данные
                        </div>
                        <div class="box-body">
                            <input type="hidden" id="current-page" value="LogistRegion"/>
                            <div id="category-block">
                                <!-- regions -->
                                <?=$form->field($model, 'region_id')->dropDownList(
                                    $regions,
                                    [
                                        'class'=>'category-item-simple-region form-control select2',
                                        'prompt'=>'Выбрать категорию',
                                        'options' => [$tree[0] => ['selected'=>'selected']]
                                    ]
                                )->label('Регион: <span class="error_field">*</span>');?>
                                <?php if ($current_regions) {?>
                                    <?php foreach ($current_regions as $key => $region) {?>
                                        <?php if ($region) {?>
                                            <?=$form->field($model, 'sub_region_id[]', ['options'=>['class'=>['region-group form-group']]])->dropDownList(
                                                $region,
                                                ['class'=>'category-item-simple-region form-control select2', 'prompt'=>'Выбрать подкатегорию', 'options' => [$tree[$key+1] => ['selected'=>'selected']]]
                                            )->label('Подкатегории');?>
                                        <?php }?>
                                    <?php }?>
                                <?php }?>
                                <!-- end regions -->
                            </div>
                            
                            <div style="display:<?=Yii::$app->request->get('tariff_id') ? 'block' : 'none';?>" id="main-form">
                                <div id="item-form">
                                    <?php if ($model->logistRegionPrices) {?>
                                        <?php foreach ($model->logistRegionPrices as $item) {?>
                                            <div class="item-block">
                                                <div class="row">
                                                    <div class="col-sm-3">
                                                        <?=$form->field($model, 'prices[unit_id][]')->dropDownList($units, ['prompt'=>'Выберите еденицу измерения', 'options' => [$item->unit_id => ['selected'=>'selected']]])->label('Еденица измерения');?>
                                                    </div>
                                                    <div class="col-sm-3">
                                                        <?=$form->field($model, 'prices[unit_amount][]')->textInput()->input('text', ['value'=>$item->unit_amount])->label('Количество');?>
                                                    </div>
                                                    <div class="col-sm-3">
                                                        <?=$form->field($model, 'prices[price][]')->textInput()->input('text', ['value'=>$item->price])->label('Стоимость');?>
                                                    </div>
                                                    <div class="col-sm-3">
                                                        <a href="javascript:;" class="btn btn-danger remove-block" style="margin-top:25px"><i class="fa fa-remove"></i> </a>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php }?>
                                    <?php } else {?>
                                        <div class="item-block">
                                            <div class="row">
                                                <div class="col-sm-3">
                                                    <?=$form->field($model, 'prices[unit_id][]')->dropDownList($units, ['prompt'=>'Выберите еденицу измерения'])->label('Еденица измерения');?>
                                                </div>
                                                <div class="col-sm-3">
                                                    <?=$form->field($model, 'prices[unit_amount][]')->textInput()->input('text')->label('Количество');?>
                                                </div>
                                                <div class="col-sm-3">
                                                    <?=$form->field($model, 'prices[price][]')->textInput()->input('text')->label('Стоимость');?>
                                                </div>
                                                <div class="col-sm-3">
                                                    <a href="javascript:;" class="btn btn-danger remove-block" style="margin-top:25px"><i class="fa fa-remove"></i> </a>
                                                </div>
                                            </div>
                                        </div>
                                    <?php }?>
                                </div>

                                <div class="text-center">
                                    <a href="javascript:;" class="add-variant-item btn btn-info btn-sm">
                                        <i class="glyphicon glyphicon-plus"></i> Добавить еще
                                    </a>
                                </div>
                            </div>
                        </div>
                        <div class="box-footer">
                            <div class="text-right">
                                <?=Html::submitButton('<i class="fa fa-check-square-o"></i> Сохранить', ['class'=>'btn btn-primary'])?>
                            </div>
                        </div>
                    </div>
                <?php ActiveForm::end();?>
            </div>
        </div>
    </section>
</div>