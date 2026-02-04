<?php
use yii\helpers\Html;
use yii\bootstrap4\ActiveForm;

use app\widgets\admin_language_tab\AdminLanguageTab;

$this->title = 'Сохранить бренд';
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
        <?php $form = ActiveForm::begin(['options' => ['enctype' => 'multipart/form-data'], 'id' => 'form-profile']);?>
            <div class="box box-info color-palette-box">
                <div class="box-body">
                    <div class="row">
                        <div class="col-sm-5">
                            <div class="row">
                                <div class="col-xs-6"> 
                                    <?=$form->field($model, 'imageFiles[]')->fileInput(['class'=>'file-upload-ajax'])->label('Главное фото'); ?>
                                </div>
                                <div class="col-xs-6">
                                    <img src="<?=$model->getPhoto('200x200')?>" width="200" class="photo-admin-user"/>
                                    <?php if ($model->image) {?>
                                        <br/><br/>
                                        <a href="<?=Yii::$app->urlManager->createUrl(['/shop/default/remove-photo', 'id'=>$model->image->id])?>" class="edit-user remove-object btn btn-sm btn-danger"><i class="fa fa-remove"></i> Удалить фото</a>
                                    <?php }?>
                                </div>
                            </div>
                        </div>
                        <div class="col-sm-7">
                            <?=AdminLanguageTab::widget();?>
                            <br/>
                            <div class="lang-block lang-block-ru">
                                <?=$form->field($model, 'name_ru')->textInput()->input('text')->label('Название (RU): <span class="error_field">*</span>');?>
                                <?=$form->field($model, 'description_ru')->textarea(['rows' => '6'])->label('Описание (RU)');?>
                            </div>
                            <div class="lang-block lang-block-en" style="display: none">
                                <?=$form->field($model, 'name_en')->textInput()->input('text')->label('Название (EN):');?>
                                <?=$form->field($model, 'description_en')->textarea(['rows' => '6'])->label('Описание (EN)');?>
                            </div>
                            <div class="lang-block lang-block-uz" style="display: none">
                                <?=$form->field($model, 'name_uz')->textInput()->input('text')->label('Название (UZ):');?>
                                <?=$form->field($model, 'description_uz')->textarea(['rows' => '6'])->label('Описание (UZ)');?>
                            </div>
                            <div id="category-block">
                                <input type="hidden" id="current-page" value="CategoryBrand">
                                <!-- categories -->
                                <?=$form->field($model, 'category_id')->dropDownList(
                                    $categories,
                                    [
                                        'class'=>'category-item-simple form-control select2',
                                        'prompt'=>'Выбрать категорию',
                                        'options' => [$tree[0] => ['selected'=>'selected']]
                                    ]
                                )->label('Категория: <span class="error_field">*</span>');?>
                                <?php if ($current_categories) {?>
                                    <?php foreach ($current_categories as $key => $category) {?>
                                        <?php if ($category) {?>
                                            <?=$form->field($model, 'sub_category_id[]', ['options'=>['class'=>['category-group form-group']]])->dropDownList(
                                                $category,
                                                ['class'=>'category-item form-control select2', 'prompt'=>'Выбрать подкатегорию', 'options' => [$tree[$key+1] => ['selected'=>'selected']]]
                                            )->label('Подкатегории');?>
                                        <?php }?>
                                    <?php }?>
                                <?php }?>
                                <!-- end categories -->
                            </div>
                        </div>
                    </div>
                </div>
                <div class="box-footer">
                    <div class="text-right">
                        <?=Html::submitButton('<i class="glyphicon glyphicon-ok"></i> Сохранить', ['class'=>'btn btn-primary']);?>
                    </div>
                </div>
            </div>
        <?php ActiveForm::end();?>
    </section>
</div>  