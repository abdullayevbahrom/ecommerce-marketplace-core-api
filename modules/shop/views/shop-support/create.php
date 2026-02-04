<?php
use yii\helpers\Html;
use yii\bootstrap4\ActiveForm;

use mihaildev\ckeditor\CKEditor;

use app\widgets\admin_language_tab\AdminLanguageTab;
use app\models\File;

$this->title = 'Отправить сообщение';
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
        <div class="box box-info color-palette-box">
            <?php $form = ActiveForm::begin(['options' => ['enctype' => 'multipart/form-data'], 'id' => 'form-profile']);?>
                <div class="box-body">
                    <?=$form->field($model, 'theme', ['template'=>'{label}<div class="input-group"><span class="input-group-addon"><i class="fa fa-pencil"></i></span>{input}</div>{error}'])->textInput()->input('text')->label('Тема <span class="error_field">*</span>');?>
                    <?=$form->field($model, 'message')->textarea(['rows' => '6'])->label('Сообщение: <span class="error_field">*</span>');?>
                </div>
                <div class="box-footer">
                    <div class="text-right">
                        <?=Html::submitButton('<i class="fa fa-send"></i> Отправить', ['class'=>'btn btn-primary']);?>
                    </div>
                </div>
            <?php ActiveForm::end();?>
        </div>
    </section>
</div>  