<?php
use yii\helpers\Html;

use yii\bootstrap4\ActiveForm;

$this->title = 'Login to admin panel';
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="login-box">
    <div class="login-logo">
        <a href="javascript:;"><strong>app<strong></a>
    </div>
    <div class="login-box-body">
        <?php $form = ActiveForm::begin();?>
            <?=$form->field($model, 'login', ['template'=>'<div class="form-group has-feedback">{input} {error}<span class="glyphicon glyphicon-envelope form-control-feedback"></span></div>'])->textInput()->input('text', ['placeholder'=>'Login or E-mail', 'autofocus'=>'autofocus'])->label(false)?>
            <?=$form->field($model, 'password', ['template'=>'<div class="form-group has-feedback">{input} {error}<span class="glyphicon glyphicon-lock form-control-feedback"></span></div>'])->textInput()->input('password', ['placeholder'=>'Password'])->label(false)?>
            <div class="row">
                <div class="col-xs-8">
                    <div class="checkbox icheck">
                        <label>
                            <input type="checkbox" name="User[remember]"> remember me
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