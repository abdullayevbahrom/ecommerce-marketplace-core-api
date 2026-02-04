<?php
use yii\helpers\Html;
use yii\bootstrap4\ActiveForm;
use yii\helpers\ArrayHelper;

use app\widgets\admin_logist_menu\AdminLogistMenu;

$this->title = 'Add region/tariff';
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="content-wrapper">
    <section class="content-header">
        <h1><?=$this->title;?></h1>

        <ol class="breadcrumb">
            <li><a href="<?=Yii::$app->urlManager->createUrl(['/admin/'])?>"><i class="fa fa-dashboard"></i> Home</a></li>
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
                            Info
                        </div>
                        <div class="box-body">
                            <input type="hidden" id="current-page" value="LogistRegion"/>
                            <div id="category-block_a">
                                <!-- regions -->
                                <?=$form->field($model, 'region_a_id')->dropDownList(
                                    $regions,
                                    [
                                        'class'=>'category-item-simple-region-a form-control select2',
                                        'prompt'=>'Select category',
                                        'options' => [$tree_a[0] => ['selected'=>'selected']]
                                    ]
                                )->label('Dispatch region: <span class="error_field">*</span>');?>
                                <?php if ($current_regions_a) {?>
                                    <?php foreach ($current_regions_a as $key_a => $region_a) {?>
                                        <?php if ($region_a) {?>
                                            <?=$form->field($model, 'sub_region_a_id[]', ['options'=>['class'=>['region-group form-group']]])->dropDownList(
                                                $region_a,
                                                ['class'=>'category-item-simple-region-a form-control select2', 'prompt'=>'Select subcategory', 'options' => [$tree_a[$key_a+1] => ['selected'=>'selected']]]
                                            )->label('Subcategory');?>
                                        <?php }?>
                                    <?php }?>
                                <?php }?>
                                <!-- end regions -->
                            </div>
                            <div id="category-block">
                                <!-- regions -->
                                <?=$form->field($model, 'region_id')->dropDownList(
                                    $regions,
                                    [
                                        'class'=>'category-item-simple-region form-control select2',
                                        'prompt'=>'Select category',
                                        'options' => [$tree[0] => ['selected'=>'selected']]
                                    ]
                                )->label('Регион shipping: <span class="error_field">*</span>');?>
                                <?php if ($current_regions) {?>
                                    <?php foreach ($current_regions as $key => $region) {?>
                                        <?php if ($region) {?>
                                            <?=$form->field($model, 'sub_region_id[]', ['options'=>['class'=>['region-group form-group']]])->dropDownList(
                                                $region,
                                                ['class'=>'category-item-simple-region form-control select2', 'prompt'=>'Select subcategory', 'options' => [$tree[$key+1] => ['selected'=>'selected']]]
                                            )->label('Subcategory');?>
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
                                                        <?=$form->field($model, 'prices[unit_id][]')->dropDownList($units, ['prompt'=>'Выберите еденицу измерения', 'options' => [$item->unit_id => ['selected'=>'selected']]])->label('Unit of measurement');?>
                                                    </div>
                                                    <div class="col-sm-3">
                                                        <?=$form->field($model, 'prices[unit_amount][]')->textInput()->input('text', ['value'=>$item->unit_amount])->label('Amount');?>
                                                    </div>
                                                    <div class="col-sm-3">
                                                        <?=$form->field($model, 'prices[price][]')->textInput()->input('text', ['value'=>$item->price])->label('Price');?>
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
                                                    <?=$form->field($model, 'prices[unit_id][]')->dropDownList($units, ['prompt'=>'Select unit of measurement'])->label('Unit of measurement');?>
                                                </div>
                                                <div class="col-sm-3">
                                                    <?=$form->field($model, 'prices[unit_amount][]')->textInput()->input('text')->label('Amount');?>
                                                </div>
                                                <div class="col-sm-3">
                                                    <?=$form->field($model, 'prices[price][]')->textInput()->input('text')->label('Price');?>
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
                                        <i class="glyphicon glyphicon-plus"></i> Add more
                                    </a>
                                </div>
                            </div>
                        </div>
                        <div class="box-footer">
                            <div class="text-right">
                                <?=Html::submitButton('<i class="fa fa-check-square-o"></i> Save', ['class'=>'btn btn-primary'])?>
                            </div>
                        </div>
                    </div>
                <?php ActiveForm::end();?>
            </div>
        </div>
    </section>
</div>