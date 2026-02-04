<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use app\models\Promocode;
use yii\helpers\ArrayHelper;
use app\models\user\User;
use yii\web\JsExpression;
use yii\helpers\Url;

/* @var $this yii\web\View */
/* @var $model app\models\Promocode */
/* @var $form yii\widgets\ActiveForm */
?>

<div class="promocode-form">

    <?php $form = ActiveForm::begin(); ?>

        <div class="row">
            <div class="col-md-6">
                <div class="box box-primary">
                    <div class="box-header with-border">
                        <h3 class="box-title">Основные настройки</h3>
                        <?php if ($model->isNewRecord): ?>
                            <div class="pull-right">
                                <?= $form->field($model, 'is_generator')->checkbox(['id' => 'is-generator-checkbox', 'label' => 'Массовая генерация']) ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="box-body">
                        
                        <div id="single-code-fields">
                            <?= $form->field($model, 'code')->textInput(['maxlength' => true]) ?>
                        </div>

                        <div id="generator-fields" style="display: none; background: #f9f9f9; padding: 15px; border-radius: 5px; margin-bottom: 20px;">
                            <h4 style="margin-top: 0;">Параметры генерации</h4>
                            <?= $form->field($model, 'target_group')->dropDownList([
                                'registered' => 'Пользователи, зарегистрированные в период',
                                'reviewers' => 'Пользователи, оставившие отзыв в период'
                            ], ['prompt' => 'Выберите группу']) ?>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <?= $form->field($model, 'generator_start_date')->input('datetime-local') ?>
                                </div>
                                <div class="col-md-6">
                                    <?= $form->field($model, 'generator_end_date')->input('datetime-local') ?>
                                </div>
                            </div>
                        </div>

                        <?= $form->field($model, 'type')->dropDownList([
                            Promocode::TYPE_FIXED => 'Фикс. сумма',
                            Promocode::TYPE_PERCENT => 'Процент',
                        ], ['id' => 'promo-type']) ?>

                        <?= $form->field($model, 'value')->textInput() ?>

                        <?= $form->field($model, 'status')->dropDownList([
                            1 => 'Активен',
                            0 => 'Неактивен',
                        ]) ?>
                    </div>
                </div>

                <div class="box box-success" id="user-bind-box">
                    <div class="box-header with-border">
                        <h3 class="box-title">Привязка к пользователю</h3>
                    </div>
                <div class="box-body">
                    <?php 
                    // Prepare initial data for Select2
                    $initialUser = null;
                    if ($model->user_id) {
                        $user = User::findOne($model->user_id);
                        if ($user) {
                            $text = $user->name;
                            if (!empty($user->lastname)) $text .= ' ' . $user->lastname;
                            $text .= ' (ID: ' . $user->id . ')';
                            $initialUser = ['id' => $user->id, 'text' => $text];
                        }
                    }
                    ?>
                    
                    <?= $form->field($model, 'user_id')->dropDownList([], [
                        'class' => 'form-control user-select2',
                        'prompt' => '', 
                        'style' => 'width: 100%'
                    ])->label('Персональный промокод (для конкретного пользователя)') ?>
                    
                    <div class="callout callout-info" style="margin-bottom: 0!important;">
                        <h4><i class="fa fa-info"></i> Примечание</h4>
                        <p>Оставьте поле пустым, чтобы промокод был доступен для всех пользователей.</p>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-6">
            <div class="box box-warning">
                <div class="box-header with-border">
                    <h3 class="box-title">Ограничения</h3>
                </div>
                <div class="box-body">
                    <?= $form->field($model, 'min_order_amount')->textInput() ?>

                    <div id="max-discount-field">
                        <?= $form->field($model, 'max_discount_amount')->textInput(['placeholder' => 'Макс. сумма скидки (например, не более 50 000 сум)']) ?>
                    </div>

                    <?= $form->field($model, 'usage_limit')->textInput(['placeholder' => 'Общий лимит (пусто - безлимит)']) ?>

                    <?= $form->field($model, 'usage_limit_per_user')->textInput(['value' => $model->isNewRecord ? 1 : $model->usage_limit_per_user]) ?>
                    
                    <?= $form->field($model, 'is_first_order')->checkbox() ?>

                    <div class="form-group">
                        <label>Быстрый выбор периода</label>
                        <div class="btn-group btn-group-justified">
                            <div class="btn-group">
                                <button type="button" class="btn btn-default set-period" data-period="week">1 Неделя</button>
                            </div>
                            <div class="btn-group">
                                <button type="button" class="btn btn-default set-period" data-period="month">1 Месяц</button>
                            </div>
                            <div class="btn-group">
                                <button type="button" class="btn btn-default set-period" data-period="year">1 Год</button>
                            </div>
                            <div class="btn-group">
                                <button type="button" class="btn btn-default set-period" data-period="custom">Свой</button>
                            </div>
                        </div>
                    </div>

                    <?= $form->field($model, 'start_date')->input('datetime-local', ['id' => 'start-date']) ?>

                    <?= $form->field($model, 'end_date')->input('datetime-local', ['id' => 'end-date']) ?>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">
            <div class="box box-default">
                <div class="box-header with-border">
                    <h3 class="box-title">Мультиязычные описания</h3>
                </div>
                <div class="box-body">
                    <div class="row">
                        <div class="col-md-4">
                            <?= $form->field($model, 'title_ru')->textInput(['maxlength' => true]) ?>
                            <?= $form->field($model, 'description_ru')->textarea(['rows' => 3]) ?>
                        </div>
                        <div class="col-md-4">
                            <?= $form->field($model, 'title_uz')->textInput(['maxlength' => true]) ?>
                            <?= $form->field($model, 'description_uz')->textarea(['rows' => 3]) ?>
                        </div>
                        <div class="col-md-4">
                            <?= $form->field($model, 'title_en')->textInput(['maxlength' => true]) ?>
                            <?= $form->field($model, 'description_en')->textarea(['rows' => 3]) ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="form-group">
        <?= Html::submitButton('Сохранить', ['class' => 'btn btn-success']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>

<?php
$initialUserJson = $initialUser ? json_encode($initialUser) : 'null';

$script = <<< JS
    // Function to handle field visibility
    function toggleFields() {
        var type = $('#promo-type').val();
        
        // If Fixed Amount (1), hide Max Discount Amount (only relevant for percent)
        if (type == 1) {
            $('#max-discount-field').hide();
        } else {
            $('#max-discount-field').show();
        }
    }

    // Initialize Select2 for User Search
    var userSelect = $('.user-select2').select2({
        ajax: {
            url: '/admin/promocode/user-list',
            dataType: 'json',
            delay: 250,
            data: function (params) {
                return {
                    q: params.term
                };
            },
            processResults: function (data) {
                return {
                    results: data.results
                };
            },
            cache: true
        },
        minimumInputLength: 3,
        placeholder: 'Выберите пользователя (для персонального промокода) ...',
        allowClear: true,
        language: {
            inputTooShort: function() {
                return 'Пожалуйста, введите 3 или более символов';
            },
            searching: function() {
                return 'Поиск...';
            },
            noResults: function() {
                return 'Ничего не найдено';
            }
        }
    });

    // Manually add option if initial user is present
    var initialUser = $initialUserJson;
    if (initialUser) {
        var option = new Option(initialUser.text, initialUser.id, true, true);
        userSelect.append(option).trigger('change');
    }

    // Function to handle generator visibility
    function toggleGenerator() {
        if ($('#is-generator-checkbox').is(':checked')) {
            $('#generator-fields').slideDown();
            $('#single-code-fields').slideUp();
            $('#user-bind-box').slideUp();
        } else {
            $('#generator-fields').slideUp();
            $('#single-code-fields').slideDown();
            $('#user-bind-box').slideDown();
        }
    }

    $('#is-generator-checkbox').change(function() {
        toggleGenerator();
    });
    
    // Initialize on load
    toggleGenerator();
    toggleFields();

    // Bind change event
    $('#promo-type').change(function() {
        toggleFields();
    });

    // Date helper functions
    function formatDateLocal(date) {
        var year = date.getFullYear();
        var month = ('0' + (date.getMonth() + 1)).slice(-2);
        var day = ('0' + date.getDate()).slice(-2);
        var hours = ('0' + date.getHours()).slice(-2);
        var minutes = ('0' + date.getMinutes()).slice(-2);
        return year + '-' + month + '-' + day + 'T' + hours + ':' + minutes;
    }

    // Period buttons handler
    $('.set-period').click(function() {
        var period = $(this).data('period');
        var now = new Date();
        var startInput = $('#start-date');
        var endInput = $('#end-date');
        
        // If start date is empty, set it to now
        if (!startInput.val()) {
            startInput.val(formatDateLocal(now));
        }
        
        // Use current start date as base
        var startDate = new Date(startInput.val());
        if (isNaN(startDate.getTime())) {
            startDate = now;
            startInput.val(formatDateLocal(now));
        }
        
        var endDate = new Date(startDate.getTime());
        
        if (period === 'week') {
            endDate.setDate(startDate.getDate() + 7);
        } else if (period === 'month') {
            endDate.setMonth(startDate.getMonth() + 1);
        } else if (period === 'year') {
            endDate.setFullYear(startDate.getFullYear() + 1);
        } else if (period === 'custom') {
            // Just clear end date or focus it
            endInput.focus();
            return;
        }
        
        endInput.val(formatDateLocal(endDate));
        
        // Highlight active button
        $('.set-period').removeClass('active btn-primary').addClass('btn-default');
        $(this).removeClass('btn-default').addClass('active btn-primary');
    });
JS;
$this->registerJs($script);
?>
