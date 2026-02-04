<?php
use yii\helpers\Html;

use yii\bootstrap4\ActiveForm;

$this->title = 'Вход в админ-панель';
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="login-box">
    <div class="login-logo">
        <a href="javascript:;"><strong>app<strong></a>
    </div>
    <div class="login-box-body">
        <?php if (Yii::$app->session->hasFlash('error')): ?>
            <div class="alert alert-danger alert-dismissible">
                <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                <h4><i class="icon fa fa-ban"></i> Ошибка!</h4>
                <?= Yii::$app->session->getFlash('error'); ?>
            </div>
        <?php endif; ?>
        
        <?php if (Yii::$app->session->hasFlash('success')): ?>
            <div class="alert alert-success alert-dismissible">
                <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                <h4><i class="icon fa fa-check"></i> Успешно!</h4>
                <?= Yii::$app->session->getFlash('success'); ?>
            </div>
        <?php endif; ?>
        
        <?php $form = ActiveForm::begin();?>
            <?=$form->field($model, 'login', ['template'=>'<div class="form-group has-feedback">{input} {error}<span class="glyphicon glyphicon-envelope form-control-feedback"></span></div>'])->textInput()->input('text', ['placeholder'=>'Login or Email', 'autofocus'=>'autofocus'])->label(false)?>
            <?=$form->field($model, 'password', ['template'=>'<div class="form-group has-feedback">{input} {error}<span class="glyphicon glyphicon-lock form-control-feedback"></span></div>'])->textInput()->input('password', ['placeholder'=>'Password'])->label(false)?>
            <div class="row">
                <div class="col-xs-8">
                    <div class="checkbox icheck">
                        <label>
                            <input type="checkbox" name="User[remember]"> Remember me
                        </label>
                    </div>
                </div>
                <div class="col-xs-4">
                    <?=Html::submitButton('Login <i class="fa fa-chevron-circle-right"></i>', ['class'=>'btn btn-primary btn-block btn-flat'])?>
                </div>
            </div>
        <?php ActiveForm::end();?>
    </div>
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