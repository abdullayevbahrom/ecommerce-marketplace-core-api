<?php
use yii\helpers\Html;
use yii\bootstrap4\ActiveForm;
use yii\helpers\ArrayHelper;

use mihaildev\ckeditor\CKEditor;

use app\widgets\admin_language_tab\AdminLanguageTab;

use app\models\filter\Filter;
use app\models\category\CategoryFilter;
use app\models\user\User;

$this->title = 'Categories';
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
        <div class="category-result alert alert-success text-center alert-bottom" id="save-category-success">Categories saved successfully</div>
        <div class="category-result alert alert-danger text-center alert-bottom" id="save-category-error">Error saving categories</div>
        <?php if (Yii::$app->session->hasFlash('category_saved')) {?>
            <div class="callout callout-success text-center">
                <?=Yii::$app->session->getFlash('category_saved');?>
            </div>
        <?php }?>
        <?php if (Yii::$app->session->hasFlash('category_removed')) {?>
            <div class="callout callout-success text-center">
                <?=Yii::$app->session->getFlash('category_removed');?>
            </div>
        <?php }?>
        <?php if (Yii::$app->session->hasFlash('photo_removed')) {?>
            <div class="callout callout-success text-center">
                <?=Yii::$app->session->getFlash('photo_removed');?>
            </div>
        <?php }?>
        
        <?php if ($categories) {?>
            <div id="category-form-block">
                <div class="row">
                    <div class="col-sm-6">
                        <div class="box box-info color-palette-box">
                            <div class="box-header with-border">
                                <strong>List categories</strong>
                            </div>
                            <div class="box-body">
                                <div id="nestable3" class="dd">
                                    <ol class="dd-list">
                                        <?php function category($data, $count = 0) {?>
                                            <?php foreach ($data as $k => $c) {?>
                                                <li class="dd-item dd3-item dd-collapsed">
                                                    <div class="dd-handle dd3-handle"></div>
                                                    <div class="dd3-content">
                                                        <div class="btn-group pull-right">
                                                            <a href="javascript:;" type="button" class="dropdown-toggle" data-toggle="dropdown">
                                                                <span class="fa fa-cog"></span>
                                                            </a>
                                                            <ul class="dropdown-menu pull-right">
                                                                <li><a href="javascript:;" class="view_category" data-value="<?=$c['id'];?>" data-toggle="modal" data-target="#view-category">View</a></li>
                                                                <li><a href="<?=Yii::$app->urlManager->createUrl(['/admin/category/lock', 'id'=>$c['id']]);?>"><?=($c['status'] == 1) ? 'Заблокировать' : 'Разблокировать';?></a></li>
                                                                <?php if (Yii::$app->user->identity->role != \app\models\user\User::ROLE_MODERATOR): ?>
                                                                <li><a href="javascript:;" class="add_category" data-value="<?=$c['id'];?>" data-toggle="modal" data-target="#add-category">Add subcategory</a></li>
                                                                <li><a href="javascript:;" class="update_category" data-value="<?=$c['id'];?>" data-toggle="modal" data-target="#update-category">Edit</a></li>
                                                                <li><a href="<?=Yii::$app->urlManager->createUrl(['/admin/category/remove', 'id'=>$c['id']]);?>" class="remove-object">Delete</a></li>
                                                                <?php endif; ?>
                                                            </ul>
                                                        </div>
                                                        <?=$c['name_ru'];?> 
                                                    </div>
                                                    <?php if (array_key_exists('children', $c)) {?>
                                                        <ol class="dd-list">
                                                            <?php category($c['children'], $count++);?>
                                                        </ol>
                                                    <?php }?>
                                                    <?php if ($count == 3) {?>
                                                        <input type="hidden" name="third" class="third"  value="<?=$c['id']?>"/>
                                                    <?php }?>
                                                    <?php if ($count == 1) {?>
                                                        <input type="hidden" name="top" class="top" value="<?=$c['id']?>"/>
                                                    <?php }?>
                                                    <?php if ($count == 0) {?>
                                                        <input type="hidden" name="second" class="second" value="<?=$c['id']?>"/>
                                                    <?php }?>
                                                </li>
                                            <?php }?>
                                        <?php } category($categories);?>
                                    </ol>
                                </div>
                            </div>
                            <div class="box-footer">
                                <?=Html::submitButton('<i class="fa fa-check-square-o"></i> Save sort', ['name'=>'save_sort', 'class'=>'btn btn-primary width-full', 'id'=>'save-category-sort']);?>
                            </div>
                        </div>
                    </div>
                    <?php if (Yii::$app->user->identity->role != \app\models\user\User::ROLE_MODERATOR): ?>
                    <div class="col-sm-6">
                        <div class="box box-warning color-palette-box">
                            <div class="box-header with-border">
                                <strong>Add new category</strong>
                            </div>
                            <?php $form = ActiveForm::begin(['options' => ['enctype' => 'multipart/form-data']]);?>
                                <div class="box-body">
                                    <?=AdminLanguageTab::widget();?>
                                    <br/>
                                    <div class="lang-block lang-block-ru">
                                        <?=$form->field($model, 'name_ru', ['template'=>'{label}<div class="input-group"><span class="input-group-addon"><i class="fa fa-pencil"></i></span>{input}</div>{error}'])->textInput()->input('text', ['class'=>'form-control'])->label('Name category (RU) <span class="required-field">*</span>')?>
                                        <?=$form->field($model, 'description_ru')->widget(CKEditor::className(),[
                                            'editorOptions' => [
                                                'preset' => 'basic',
                                                'inline' => false,
                                                'height' => 150
                                            ],
                                        ])->label('Description (RU)');?>
                                    </div>
                                    <div class="lang-block lang-block-uz">
                                        <?=$form->field($model, 'name_uz', ['template'=>'{label}<div class="input-group"><span class="input-group-addon"><i class="fa fa-pencil"></i></span>{input}</div>{error}'])->textInput()->input('text', ['class'=>'form-control'])->label('Name category (UZ)');?>
                                        <?=$form->field($model, 'description_uz')->widget(CKEditor::className(),[
                                            'editorOptions' => [
                                                'preset' => 'basic',
                                                'inline' => false,
                                                'height' => 150
                                            ],
                                        ])->label('Description (UZ)');?>
                                    </div>
                                    <div class="lang-block lang-block-en">
                                        <?=$form->field($model, 'name_en', ['template'=>'{label}<div class="input-group"><span class="input-group-addon"><i class="fa fa-pencil"></i></span>{input}</div>{error}'])->textInput()->input('text', ['class'=>'form-control'])->label('Name category (EN)');?>
                                        <?=$form->field($model, 'description_en')->widget(CKEditor::className(),[
                                            'editorOptions' => [
                                                'preset' => 'basic',
                                                'inline' => false,
                                                'height' => 150
                                            ],
                                        ])->label('Description (EN)');?>
                                    </div>
                                    <?=$form->field($model, 'popular')->checkbox()->label('Popular');?>
                                    <?=$form->field($model, 'imageFiles[]')->fileInput(['multiple' => true, 'accept' => 'image/*', 'class'=>'file-upload-ajax'])->label('Photo');?>
                                </div>
                                <div class="box-footer">
                                    <?=Html::submitButton('<i class="fa fa-check-square-o"></i> Add', ['name'=>'add_item', 'class'=>'btn btn-primary width-full']);?>
                                </div>
                            <?php ActiveForm::end();?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
                <hr/>
            </div>
        <?php } else {?>
            <?php if (Yii::$app->user->identity->role != \app\models\user\User::ROLE_MODERATOR): ?>
            <div class="callout callout-warning text-center"> There are no categories yet, create the first category</div>
            <div class="box box-info color-palette-box">
                <?php $form = ActiveForm::begin(); ?>
                    <div class="box-body">
                        <?=AdminLanguageTab::widget();?>
                        <br/>
                        <div class="lang-block lang-block-ru">
                            <?=$form->field($model, 'name_ru', ['template'=>'{label}<div class="input-group"><span class="input-group-addon"><i class="fa fa-pencil"></i></span>{input}</div>{error}'])->textInput()->input('text')->label('Name category (RU) <span class="required-field">*</span>');?>
                            <?=$form->field($model, 'description_ru')->widget(CKEditor::className(),[
                                'editorOptions' => [
                                    'preset' => 'basic',
                                    'inline' => false,
                                    'height' => 200
                                ],
                            ])->label('Description (RU)');?>
                        </div>
                        <div class="lang-block lang-block-uz">
                            <?=$form->field($model, 'name_uz', ['template'=>'{label}<div class="input-group"><span class="input-group-addon"><i class="fa fa-pencil"></i></span>{input}</div>{error}'])->textInput()->input('text')->label('Name category (UZ)');?>
                            <?=$form->field($model, 'description_uz')->widget(CKEditor::className(),[
                                'editorOptions' => [
                                    'preset' => 'basic',
                                    'inline' => false,
                                    'height' => 200
                                ],
                            ])->label('Description (UZ)');?>
                        </div>
                        <div class="lang-block lang-block-en">
                            <?=$form->field($model, 'name_en', ['template'=>'{label}<div class="input-group"><span class="input-group-addon"><i class="fa fa-pencil"></i></span>{input}</div>{error}'])->textInput()->input('text')->label('Name category (EN)');?>
                            <?=$form->field($model, 'description_en')->widget(CKEditor::className(),[
                                'editorOptions' => [
                                    'preset' => 'basic',
                                    'inline' => false,
                                    'height' => 200
                                ],
                            ])->label('Description (EN)');?>
                        </div>
                        <?=$form->field($model, 'popular')->checkbox()->label('Popular');?>
                        <?=$form->field($model, 'imageFiles[]')->fileInput(['multiple' => true, 'accept' => 'image/*', 'class'=>'file-upload-ajax'])->label('Photo');?>
                    </div>
                    <div class="box-footer">
                        <div class="text-right">
                            <?=Html::submitButton('<i class="fa fa-check-square-o"></i> Save', ['class'=>'btn btn-primary']);?>
                        </div>
                    </div>
                <?php ActiveForm::end()?>
            </div>
            <?php endif; ?>
        <?php }?>
    </section>
</div>

<div class="modal fade" id="view-category">
    <div class="modal-dialog">
        <div class="text-center" id="preloader">
            <img src="/assets_files/images/preloader.gif">
        </div>
        <div class="modal-content" id="category-view-block">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">×</span>
                </button>
                <h4 class="modal-title">View category</h4>
            </div>
            <div class="modal-body">
                <?=AdminLanguageTab::widget();?>
                <br/>
                <div class="row">
                    <div class="col-sm-3">
                        <img src="" id="category-view-icon" width="100%"/>
                    </div>
                    <div class="col-sm-12">
                        <i id="category-icon-fa"></i>
                        <div class="lang-block lang-block-ru">
                            <strong id="category-name-ru"></strong>
                            <p id="category-description-ru"></p>
                        </div>
                        <div class="lang-block lang-block-uz">
                            <strong id="category-name-uz"></strong>
                            <p id="category-description-uz"></p>
                        </div>
                        <div class="lang-block lang-block-en">
                            <strong id="category-name-en"></strong>
                            <p id="category-description-en"></p>
                        </div>
                        <strong id="category-popular-view"></strong>
                    </div>
                </div>
                <?php if (Yii::$app->user->identity->role === User::ROLE_MODERATOR): ?>
                    <hr>
                    <h4><i class="fa fa-comment"></i> Комментарий модератора</h4>
                    <form method="post" id="moderator-comment-form">
                        <?= Html::csrfMetaTags() ?>

                        <textarea name="comment" class="form-control" rows="4" required placeholder="Укажите причину, почему категория остаётся заблокированной"></textarea>

                        <br>

                        <button type="submit" class="btn btn-warning btn-sm">
                            <i class="fa fa-paper-plane"></i> Отправить комментарий
                        </button>
                    </form>
                <?php endif; ?>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default pull-left" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="update-category">
    <div class="modal-dialog">
        <div class="text-center" id="preloader-update">
            <img src="/assets_files/images/preloader.gif">
        </div>
        <div class="modal-content" id="category-update-block">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">×</span>
                </button>
                <h4 class="modal-title">Edit category</h4>
            </div>
            <?php $form = ActiveForm::begin(); ?>
                <?=$form->field($model, 'id')->hiddenInput(['id'=>'category-update-id'])->label(false);?>
                <?=$form->field($model, 'parent_id')->hiddenInput(['id'=>'category-update-parent_id'])->label(false);?>
                <div class="modal-body">
                    <?=AdminLanguageTab::widget();?>
                    <br/>
                    <div class="lang-block lang-block-ru">
                        <?=$form->field($model, 'name_ru', ['template'=>'{label}<div class="input-group"><span class="input-group-addon"><i class="fa fa-pencil"></i></span>{input}</div>{error}'])->textInput()->input('text', ['id'=>'category-update-name-ru'])->label('Name category (RU) <span class="required-field">*</span>');?>
                        <?=$form->field($model, 'description_ru')->widget(CKEditor::className(),[
                            'editorOptions' => [
                                'preset' => 'basic',
                                'inline' => false,
                                'height' => 200
                            ],
                            'options' => [
                                'id' => 'category-update-description-ru'
                            ]
                        ])->label('Description (RU)');?>
                        
                    </div>
                    <div class="lang-block lang-block-uz">
                        <?=$form->field($model, 'name_uz', ['template'=>'{label}<div class="input-group"><span class="input-group-addon"><i class="fa fa-pencil"></i></span>{input}</div>{error}'])->textInput()->input('text', ['id'=>'category-update-name-uz'])->label('Name category (UZ)');?>
                        <?=$form->field($model, 'description_uz')->widget(CKEditor::className(),[
                            'editorOptions' => [
                                'preset' => 'basic',
                                'inline' => false,
                                'height' => 200
                            ],
                            'options' => [
                                'id' => 'category-update-description-uz'
                            ]
                        ])->label('Description (UZ)');?>
                        
                    </div>
                    <div class="lang-block lang-block-en">
                        <?=$form->field($model, 'name_en', ['template'=>'{label}<div class="input-group"><span class="input-group-addon"><i class="fa fa-pencil"></i></span>{input}</div>{error}'])->textInput()->input('text', ['id'=>'category-update-name-en'])->label('Name category (EN)');?>
                        <?=$form->field($model, 'description_en')->widget(CKEditor::className(),[
                            'editorOptions' => [
                                'preset' => 'basic',
                                'inline' => false,
                                'height' => 200
                            ],
                            'options' => [
                                'id' => 'category-update-description-en'
                            ]
                        ])->label('Description (EN)');?>
                    </div>
                    <?=$form->field($model, 'popular')->checkbox(['id'=>'category-update-popular'])->label('Popular');?>
                    <?php if ($filters) {?>
                        <?php foreach ($filters as $key => $value) {?>
                            <div class="value-<?=$value->category_id;?> category-filter" style="display:none">
                                <?php if ($value) {?>
                                    <?php if ($value->type == 'input') {?>
                                        <?=$form->field($model, 'filters['.$value->id.']', ['options'=>['class'=>'category-group form-group']])->textInput()->input('text', ['value'=>$value->categoryFilter ? $value->categoryFilter->value_ru : ''])->label($value->name_ru);?>
                                    <?php }?>
                                    <?php if ($value->type == 'select') {?>
                                        <?php $filter_childs = ArrayHelper::map(Filter::find()->where(['parent_id'=>$value->id])->all(), 'value_ru', 'value_ru');?>
                                        <?php if ($filter_childs) {?>
                                            <?=$form->field($model, 'filters['.$value->id.']', ['options'=>['class'=>'category-group form-group']])->dropDownList(
                                                $filter_childs,
                                                [
                                                    'class'=>'form-control select2',
                                                    'prompt'=>'Select subcategory',
                                                    'options' => [$value->categoryFilter->value_ru => ['selected'=>'selected']]
                                                ]
                                            )->label($value->name_ru);?>
                                        <?php }?>
                                    <?php }?>
                                    <?php if ($value->type == 'checkbox') {?>
                                        <div class="category-group form-group">
                                            <strong><?=$value->name_ru;?></strong>
                                            <br/>
                                            <?php $filter_childs_ids = ArrayHelper::map(CategoryFilter::find()->where(['category_id'=>$value->category_id, 'filter_id'=>$value->id])->all(), 'value_ru', 'value_ru');?>
                                            <?php $filter_childs = Filter::find()->where(['parent_id'=>$value->id])->all();?>
                                            <?php foreach ($filter_childs as $k => $v) {?>
                                                <?=$form->field($model, 'filters['.$value->id.']['.$k.']')->checkbox(['value' => $v->value_ru, 'checked'=>array_key_exists($v->value_ru, $filter_childs_ids) ? true : false])->label($v->value_ru);?>
                                            <?php }?>
                                        </div>
                                    <?php }?>
                                <?php }?>
                            </div>
                        <?php }?>
                    <?php }?>
                    <div class="row">
                        <div class="col-sm-6">
                            <?=$form->field($model, 'imageFiles[]')->fileInput(['multiple' => true, 'accept' => 'image/*', 'class'=>'file-upload-ajax'])->label('Photo');?>
                        </div>
                        <div class="col-sm-6">
                            <img id="category-update-icon" width="200px"/>
                            <br/>
                            <a id="remove-photo" class="remove-object" style="display:none"><i class="fa fa-remove"></i> Delete photo</a>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <a type="button" class="btn btn-default pull-left" data-dismiss="modal">Close</a>
                    <?=Html::submitButton('<i class="fa fa-check-square-o"></i> Save', ['class'=>'btn btn-primary']);?>
                </div>
            <?php ActiveForm::end();?>
        </div>
    </div>
</div>

<div class="modal fade" id="add-category">
    <div class="modal-dialog">
        <div class="text-center" id="preloader-add">
            <img src="/assets_files/images/preloader.gif">
        </div>
        <div class="modal-content" id="category-add-block">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">×</span>
                </button>
                <h4 class="modal-title">Add category</h4>
            </div>
            <?php $form = ActiveForm::begin(); ?>
                <?=$form->field($model, 'parent_id')->hiddenInput(['id'=>'category-add-parent_id'])->label(false);?>
                <div class="modal-body">
                    <?=AdminLanguageTab::widget();?>
                    <br/>
                    <div class="lang-block lang-block-ru">
                        <?=$form->field($model, 'name_ru', ['template'=>'{label}<div class="input-group"><span class="input-group-addon"><i class="fa fa-pencil"></i></span>{input}</div>{error}'])->textInput()->input('text', ['id'=>'category-add-name-ru'])->label('Name category (RU) <span class="required-field">*</span>');?>
                        <?=$form->field($model, 'description_ru')->widget(CKEditor::className(),[
                            'editorOptions' => [
                                'preset' => 'basic',
                                'inline' => false,
                                'height' => 200
                            ],
                            'options' => [
                                'id' => 'category-add-description-ru'
                            ]
                        ])->label('Description (RU)');?>
                        
                    </div>
                    <div class="lang-block lang-block-uz">
                        <?=$form->field($model, 'name_uz', ['template'=>'{label}<div class="input-group"><span class="input-group-addon"><i class="fa fa-pencil"></i></span>{input}</div>{error}'])->textInput()->input('text', ['id'=>'category-add-name-uz'])->label('Name category (UZ)');?>
                        <?=$form->field($model, 'description_uz')->widget(CKEditor::className(),[
                            'editorOptions' => [
                                'preset' => 'basic',
                                'inline' => false,
                                'height' => 200
                            ],
                            'options' => [
                                'id' => 'category-add-description-uz'
                            ]
                        ])->label('Description (UZ)');?>
                        
                    </div>
                    <div class="lang-block lang-block-en">
                        <?=$form->field($model, 'name_en', ['template'=>'{label}<div class="input-group"><span class="input-group-addon"><i class="fa fa-pencil"></i></span>{input}</div>{error}'])->textInput()->input('text', ['id'=>'category-add-name-en'])->label('Name category (EN)');?>
                        <?=$form->field($model, 'description_en')->widget(CKEditor::className(),[
                            'editorOptions' => [
                                'preset' => 'basic',
                                'inline' => false,
                                'height' => 200
                            ],
                            'options' => [
                                'id' => 'category-add-description-en'
                            ]
                        ])->label('Description (EN)');?>
                    </div>
                    <?=$form->field($model, 'popular')->checkbox()->label('Popular');?>
                    <div class="row">
                        <div class="col-sm-6">
                            <?=$form->field($model, 'imageFiles[]')->fileInput(['multiple' => true, 'accept' => 'image/*', 'class'=>'file-upload-ajax'])->label('Photo');?>
                        </div>
                        <div class="col-sm-6">
                            <img id="category-add-icon" width="200px"/>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <a type="button" class="btn btn-default pull-left" data-dismiss="modal">Close</a>
                    <?=Html::submitButton('<i class="fa fa-check-square-o"></i> Save', ['class'=>'btn btn-primary']);?>
                </div>
            <?php ActiveForm::end();?>
        </div>
    </div>
</div>



