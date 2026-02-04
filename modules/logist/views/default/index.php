<?php
use yii\helpers\Html;

use yii\bootstrap4\ActiveForm;

$this->title = 'Вход в админ-панель';
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="login-box">
    <div class="login-logo">
        <a href="javascript:;"><strong>NovaMarket<strong></a>
    </div>
    <div class="login-box-body">
        <?php $form = ActiveForm::begin();?>
            <?=$form->field($model, 'login', ['template'=>'<div class="form-group has-feedback">{input} {error}<span class="glyphicon glyphicon-envelope form-control-feedback"></span></div>'])->textInput()->input('text', ['placeholder'=>'Логин или E-mail', 'autofocus'=>'autofocus'])->label(false)?>
            <?=$form->field($model, 'password', ['template'=>'<div class="form-group has-feedback">{input} {error}<span class="glyphicon glyphicon-lock form-control-feedback"></span></div>'])->textInput()->input('password', ['placeholder'=>'Пароль'])->label(false)?>
            <div class="row">
                <div class="col-xs-8">
                    <div class="checkbox icheck">
                        <label>
                            <input type="checkbox" name="User[remember]"> Запомнить меня
                        </label>
                    </div>
                </div>
                <div class="col-xs-4">
                    <?=Html::submitButton('Войти <i class="fa fa-chevron-circle-right"></i>', ['class'=>'btn btn-primary btn-block btn-flat'])?>
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