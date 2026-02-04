<?php
use yii\helpers\Html;
use yii\bootstrap4\ActiveForm;

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
        <?php $form = ActiveForm::begin(['options' => ['enctype' => 'multipart/form-data'], 'id' => 'form-profile']);?>
            <div class="box box-info color-palette-box">
                <div class="box-header with-border">
                    <div class="box-title">
                        Создать/Редактировать фильтр
                    </div>
                </div>
                <div class="box-body">
                    <?=AdminLanguageTab::widget();?>
                    <br/>
                    <div id="category-block">
                        <?=$form->field($model, 'category_id')->dropDownList(
                            $categories,
                            ['id'=>'filter-category', 'class'=>'filter-item form-control select2', 'prompt'=>'Выбрать категорию', 'options' => [$tree[0] => ['selected'=>'selected']]]
                        )->label('Категории <span class="error_field">*</span>');?>
                        <?php if ($current_categories) {?>
                            <?php foreach ($current_categories as $key => $category) {?>
                                <?php if ($category) {?>
                                    <?=$form->field($model, 'sub_category_id[]', ['options'=>['class'=>['filter-group form-group']]])->dropDownList(
                                        $category,
                                        ['class'=>'filter-item form-control select2', 'prompt'=>'Выбрать подкатегорию', 'options' => [$tree[$key+1] => ['selected'=>'selected']]]
                                    )->label('Подкатегории');?>
                                <?php }?>
                            <?php }?>
                        <?php }?>
                    </div>

                    <div id="filter-block" <?php if (!Yii::$app->request->get('id')) {?>style="display:none"<?php }?>>
                        <?=$form->field($model, 'type')->dropDownList([
                            'input' => 'input',
                            'select' => 'select',
                            'checkbox' => 'checkbox'
                        ],
                        ['id'=>'filter-type', 'class'=>'form-control select2'])->label('Тип фильтра');?>

                        <div class="lang-block lang-block-ru">
                            <?=$form->field($model, 'name_ru')->textInput()->input('text')->label('Заголовок фильтра (RU) <span class="error_field">*</span>');?>
                        </div>
                        <div class="lang-block lang-block-uz" style="display:none">
                            <?=$form->field($model, 'name_uz')->textInput()->input('text')->label('Заголовок фильтра (UZ)');?>
                        </div>
                        <div class="lang-block lang-block-en" style="display:none">
                            <?=$form->field($model, 'name_en')->textInput()->input('text')->label('Заголовок фильтра (EN)');?>
                        </div>
                        <div id="filter-variable" style="display:<?=$model->childs ? 'block' : 'none';?>">
                            <hr/>
                            <div id="item-form">
                                <?php if ($model->childs) {?>
                                    <?php foreach ($model->childs as $k => $v) {?>
                                        <div class="item-block">
                                            <div class="lang-block lang-block-ru">
                                                <?=$form->field($model, 'property_value_ru[]')->textInput()->input('text', ['value'=>$v->value_ru])->label('Значение аттрибута (RU)');?>
                                            </div>
                                            <div class="lang-block lang-block-uz" style="display:none">
                                                <?=$form->field($model, 'property_value_uz[]')->textInput()->input('text', ['value'=>$v->value_uz])->label('Значение аттрибута (UZ)');?>
                                            </div>
                                            <div class="lang-block lang-block-en" style="display:none">
                                                <?=$form->field($model, 'property_value_en[]')->textInput()->input('text', ['value'=>$v->value_en])->label('Значение аттрибута (EN)');?>
                                            </div>
                                        </div>
                                    <?php }?>
                                <?php } else {?>
                                    <div class="item-block">
                                        <div class="lang-block lang-block-ru">
                                            <?=$form->field($model, 'property_value_ru[]')->textInput()->input('text')->label('Значение аттрибута (RU)');?>
                                        </div>
                                        <div class="lang-block lang-block-uz" style="display:none">
                                            <?=$form->field($model, 'property_value_uz[]')->textInput()->input('text')->label('Значение аттрибута (UZ)');?>
                                        </div>
                                        <div class="lang-block lang-block-en" style="display:none">
                                            <?=$form->field($model, 'property_value_en[]')->textInput()->input('text')->label('Значение аттрибута (EN)');?>
                                        </div>
                                    </div>
                                <?php }?>
                            </div>

                            <div class="text-center">
                                <a href="javascript:;" class="add-item btn btn-warning btn-sm">
                                    <i class="glyphicon glyphicon-plus"></i> Добавить еще
                                </a>
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