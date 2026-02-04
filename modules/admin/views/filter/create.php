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
            <li><a href="<?=Yii::$app->urlManager->createUrl(['/admin/'])?>"><i class="fa fa-dashboard"></i> Главная</a></li>
            <li class="active"><?=$this->title;?></li>
        </ol>
    </section>
    <section class="content">
        <?php $form = ActiveForm::begin(['options' => ['enctype' => 'multipart/form-data'], 'id' => 'form-profile']);?>
            <div class="box box-info color-palette-box">
                <div class="box-header with-border">
                    <div class="box-title">
                        Добавить/Редактировать Фильтр
                    </div>
                </div>
                <div class="box-body">
                    <?=AdminLanguageTab::widget();?>
                    <br/>
                    <div id="category-block">
                        <?=$form->field($model, 'category_id')->dropDownList(
                            $categories,
                            ['id'=>'filter-category', 'class'=>'filter-item form-control select2', 'prompt'=>'Выбрать категорию', 'options' => [$tree[0] => ['selected'=>'selected']]]
                        )->label('Категория <span class="error_field">*</span>');?>
                        <?php if ($current_categories) {?>
                            <?php foreach ($current_categories as $key => $category) {?>
                                <?php if ($category) {?>
                                    <?=$form->field($model, 'sub_category_id[]', ['options'=>['class'=>['filter-group form-group']]])->dropDownList(
                                        $category,
                                        ['class'=>'filter-item form-control select2', 'prompt'=>'Выбрать подкатегорию', 'options' => [$tree[$key+1] => ['selected'=>'selected']]]
                                    )->label('Подкатегория');?>
                                <?php }?>
                            <?php }?>
                        <?php }?>
                    </div>

                    <div id="filter-block" <?php if (!Yii::$app->request->get('id')) {?>style="display:none"<?php }?>>
                        <?=$form->field($model, 'type')->dropDownList([
                            'input' => 'Ввод текста',
                            'select' => 'Выбор одного варианта',
                            'checkbox' => 'Чекбокс',
                            // 'file' => 'file'
                        ],
                        ['id'=>'filter-type', 'class'=>'form-control select2'])->label('Тип фильтра');?>

                        <div class="lang-block lang-block-ru">
                            <?=$form->field($model, 'name_ru')->textInput()->input('text')->label('Название фильтра (RU) <span class="error_field">*</span>');?>
                        </div>
                        <div class="lang-block lang-block-uz" style="display:none">
                            <?=$form->field($model, 'name_uz')->textInput()->input('text')->label('Название фильтра (UZ)');?>
                        </div>
                        <div class="lang-block lang-block-en" style="display:none">
                            <?=$form->field($model, 'name_en')->textInput()->input('text')->label('Название фильтра (EN)');?>
                        </div>
                        <div id="filter-variable" style="display:<?=$model->childs ? 'block' : 'none';?>">
                            <hr/>
                            <div class="alert alert-info">
                                <h4><i class="fa fa-info-circle"></i> Варианты фильтра</h4>
                                <p><strong>Название варианта:</strong> Отображаемое имя для пользователей (например, "Маленький", "Средний", "Большой")</p>
                                <p><strong>Значение варианта:</strong> Внутреннее значение в базе данных (например, "S", "M", "L" или то же что и название)</p>
                                <p><em>В большинстве случаев можно использовать одинаковый текст для названия и значения.</em></p>
                            </div>
                            <div id="item-form">
                                <?php if ($model->childs) {?>
                                    <?php foreach ($model->childs as $k => $v) {?>
                                        <div class="item-block" style="border: 1px solid #ddd; padding: 15px; margin-bottom: 10px; border-radius: 5px;">
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="lang-block lang-block-ru">
                                                        <?=$form->field($model, 'property_key_ru[]')->textInput()->input('text', ['value'=>$v->name_ru])->label('Название варианта (RU)');?>
                                                    </div>
                                                    <div class="lang-block lang-block-uz" style="display:none">
                                                        <?=$form->field($model, 'property_key_uz[]')->textInput()->input('text', ['value'=>$v->name_uz])->label('Название варианта (UZ)');?>
                                                    </div>
                                                    <div class="lang-block lang-block-en" style="display:none">
                                                        <?=$form->field($model, 'property_key_en[]')->textInput()->input('text', ['value'=>$v->name_en])->label('Название варианта (EN)');?>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="lang-block lang-block-ru">
                                                        <?=$form->field($model, 'property_value_ru[]')->textInput()->input('text', ['value'=>$v->value_ru])->label('Значение варианта (RU)');?>
                                                    </div>
                                                    <div class="lang-block lang-block-uz" style="display:none">
                                                        <?=$form->field($model, 'property_value_uz[]')->textInput()->input('text', ['value'=>$v->value_uz])->label('Значение варианта (UZ)');?>
                                                    </div>
                                                    <div class="lang-block lang-block-en" style="display:none">
                                                        <?=$form->field($model, 'property_value_en[]')->textInput()->input('text', ['value'=>$v->value_en])->label('Значение варианта (EN)');?>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="text-right" style="margin-top: 10px;">
                                                <button type="button" class="btn btn-danger btn-sm remove-item">
                                                    <i class="glyphicon glyphicon-trash"></i> Удалить
                                                </button>
                                            </div>
                                        </div>
                                    <?php }?>
                                <?php } else {?>
                                    <div class="item-block" style="border: 1px solid #ddd; padding: 15px; margin-bottom: 10px; border-radius: 5px;">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="lang-block lang-block-ru">
                                                    <?=$form->field($model, 'property_key_ru[]')->textInput()->input('text')->label('Option Name (RU)');?>
                                                </div>
                                                <div class="lang-block lang-block-uz" style="display:none">
                                                    <?=$form->field($model, 'property_key_uz[]')->textInput()->input('text')->label('Option Name (UZ)');?>
                                                </div>
                                                <div class="lang-block lang-block-en" style="display:none">
                                                    <?=$form->field($model, 'property_key_en[]')->textInput()->input('text')->label('Название варианта (EN)');?>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="lang-block lang-block-ru">
                                                    <?=$form->field($model, 'property_value_ru[]')->textInput()->input('text')->label('Значение варианта (RU)');?>
                                                </div>
                                                <div class="lang-block lang-block-uz" style="display:none">
                                                    <?=$form->field($model, 'property_value_uz[]')->textInput()->input('text')->label('Значение варианта (UZ)');?>
                                                </div>
                                                <div class="lang-block lang-block-en" style="display:none">
                                                    <?=$form->field($model, 'property_value_en[]')->textInput()->input('text')->label('Значение варианта (EN)');?>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="text-right" style="margin-top: 10px;">
                                            <button type="button" class="btn btn-danger btn-sm remove-item">
                                                <i class="glyphicon glyphicon-trash"></i> Удалить
                                            </button>
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
                    <?=$form->field($model, 'is_filter')->checkbox(['checked'])->label('Для поиска');?>
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