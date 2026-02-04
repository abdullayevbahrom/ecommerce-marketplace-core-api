<?php
use yii\helpers\Html;
use yii\bootstrap4\ActiveForm;

$page = Yii::$app->request->get('id') ? 'Edit' : 'Add'; 

$this->title = $page.' moderator';
$this->params['breadcrumbs'][] = ['label' => 'Users', 'url' => ['index']];
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
        <?php $form = ActiveForm::begin(); ?>
            <div class="box box-info color-palette-box">
                <div class="box-body">
                    <div class="row">
                        <div class="col-sm-5">
                            <div class="row">
                                <div class="col-xs-6"> 
                                    <?=$form->field($model, 'imageFiles[]')->fileInput(['class'=>'file-upload-ajax'])->label('Photo'); ?>
                                </div>
                                <div class="col-xs-6">
                                    <img src="<?=$model->getPhoto('200x200/')?>" width="200" class="photo-admin-user"/>
                                    <?php if ($model->image) {?>
                                        <br/>
                                        <a href="<?=Yii::$app->urlManager->createUrl(['/admin/default/remove-photo', 'id'=>$model->image->id])?>" class="edit-user remove-object"><i class="fa fa-times"></i> Delete photo</a>
                                    <?php }?>
                                </div>
                            </div>
                        </div>
                        <div class="col-sm-7">
                            <div class="row">
                                <div class="col-sm-6">
                                    <?=$form->field($model, 'name')->textInput()->input('text', ['placeholder'=>'Enter имя', 'class'=>'form-control'])->label('Name <span class="required-field">*</span>');?>
                                </div>
                                <div class="col-sm-6">
                                    <?=$form->field($model, 'phone')->textInput()->input('text', ['class'=>'form-control', 'placeholder'=>'Enter phone'])->label('phone');?>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-sm-6">
                                    <?=$form->field($model, 'login')->textInput()->input('text', ['class'=>'form-control', 'placeholder'=>'Enter login'])->label('Login <span class="required-field">*</span>');?>
                                </div>
                                <div class="col-sm-6">
                                    <?php $required = !$model->id ? ' <span class="required-field">*</span>' : '';?>
                                    <?=$form->field($model, 'password')->textInput()->input('password', ['value'=>'', 'class'=>'form-control', 'placeholder'=>'Password'])->label('Password'.$required);?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php if (!$urls) {?>
                    <div class="box-footer">
                        <div class="text-right">
                            <?=Html::submitButton('<i class="fa fa-check-square-o"></i> Save', ['class'=>'btn btn-primary']);?>
                        </div>
                    </div>
                <?php }?>
            </div>
            <?php if ($urls) {?>
                <div class="box box box-info color-palette-box">
                    <div class="box-header">
                        Access
                    </div>
                    <div class="box-body">
                        <div class="row">
                            <div class="col-sm-3">
                                <strong>Pages</strong><hr/>
                                <?php foreach ($urls as $v) {?>
                                    <?php if ($v->type == 'single') {?>
                                        <?=$form->field($model, 'moderator_access[]', ['template'=>'{input}{label}'])->input('checkbox', ['value'=>$v->id, 'id'=>$v->id, 'checked'=>$v->moderatorAccessUser ? true : false, 'class'=>''])->label($v->name, ['class'=>'', 'for'=>$v->id]);?>
                                    <?php }?>
                                <?php }?>
                            </div>
                            <div class="col-sm-3">
                                <strong>Products</strong><hr/>
                                <?php foreach ($urls as $v) {?>
                                    <?php if ($v->type == 'product') {?>
                                        <?=$form->field($model, 'moderator_access[]', ['template'=>'{input}{label}'])->input('checkbox', ['value'=>$v->id, 'id'=>$v->id, 'checked'=>$v->moderatorAccessUser ? true : false, 'class'=>''])->label($v->name, ['class'=>'', 'for'=>$v->id]);?>
                                    <?php }?>
                                <?php }?>
                            </div>
                            <div class="col-sm-3">
                                <strong>Shops</strong><hr/>
                                <?php foreach ($urls as $v) {?>
                                    <?php if ($v->type == 'shop') {?>
                                        <?=$form->field($model, 'moderator_access[]', ['template'=>'{input}{label}'])->input('checkbox', ['value'=>$v->id, 'id'=>$v->id, 'checked'=>$v->moderatorAccessUser ? true : false, 'class'=>''])->label($v->name, ['class'=>'', 'for'=>$v->id]);?>
                                    <?php }?>
                                <?php }?>
                            </div>
                            <div class="col-sm-3">
                                <strong>Directory</strong><hr/>
                                <?php foreach ($urls as $v) {?>
                                    <?php if ($v->type == 'handbook') {?>
                                        <?=$form->field($model, 'moderator_access[]', ['template'=>'{input}{label}'])->input('checkbox', ['value'=>$v->id, 'id'=>$v->id, 'checked'=>$v->moderatorAccessUser ? true : false, 'class'=>''])->label($v->name, ['class'=>'', 'for'=>$v->id]);?>
                                    <?php }?>
                                <?php }?>
                            </div>
                        </div>
                    </div>
                    <div class="box-footer">
                        <div class="text-right">
                            <?=Html::submitButton('<i class="fa fa-check-square-o"></i> Save', ['class'=>'btn btn-primary']);?>
                        </div>
                    </div>
                </div>
            <?php }?>
        <?php ActiveForm::end();?>
    </section>
</div>

<?php
$script = <<<JS
    $('input').iCheck({
        checkboxClass: 'icheckbox_square-blue',
        radioClass: 'iradio_square-blue',
        increaseArea: '20%'
    });
JS;

$this->registerJs($script);
?>