<?php
use yii\helpers\Html;
use yii\bootstrap4\ActiveForm;
use app\models\user\User;
use yii\helpers\ArrayHelper;

/** @var yii\web\View $this */
/** @var app\models\stock\Stock $model */
/** @var yii\bootstrap4\ActiveForm $form */

$isNewRecord = $model->isNewRecord;
$currentUser = Yii::$app->user->identity;
$isAdmin = $currentUser && $currentUser->role == User::ROLE_ADMIN;

// Context-aware title based on role
$roleLabel = User::ROLE_LABELS[$model->role] ?? 'Пользователя';
if ($isNewRecord) {
    $roleTitles = [
        User::ROLE_ADMIN => 'Добавить администратора',
        User::ROLE_MODERATOR => 'Добавить модератора',
        User::ROLE_USER => 'Добавить клиента',
        User::ROLE_SHOP => 'Добавить магазин',
        User::ROLE_LOGIST => 'Добавить логиста',
        User::ROLE_OPERATOR => 'Добавить оператора',
        User::ROLE_MANAGER => 'Добавить менеджера',
    ];
    $this->title = $roleTitles[$model->role] ?? 'Добавить пользователя';
} else {
    $this->title = 'Редактировать: ' . ($model->name ?: 'Пользователь #' . $model->id);
}

$this->params['breadcrumbs'][] = ['label' => 'Пользователи', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;

$regions = Yii::$app->bts->getRegions('ru');
$currentCities = [];
if ($model->bts_region_id) {
    $cities = Yii::$app->bts->getCities($model->bts_region_id);
    $currentCities = ArrayHelper::map($cities, 'code', 'name.ru');
}
?>

<div class="content-wrapper">
    <section class="content-header">
        <h1><?= $this->title; ?></h1>

        <ol class="breadcrumb">
            <li><a href="<?= Yii::$app->urlManager->createUrl(['/admin/']) ?>"><i class="fa fa-dashboard"></i> Главная</a>
            </li>
            <li><a href="<?= Yii::$app->urlManager->createUrl(['/admin/user/']) ?>">Пользователи</a></li>
            <li class="active"><?= $this->title; ?></li>
        </ol>
    </section>
    <section class="content">
        <?php $form = ActiveForm::begin(); ?>
        <div class="box box-info color-palette-box">
            <div class="box-header">Основная информация</div>
            <div class="box-body">
                <div class="row">
                    <div class="col-sm-5">
                        <div class="row">
                            <div class="col-xs-6">
                                <?= $form->field($model, 'imageFiles[]')->fileInput(['class' => 'file-upload-ajax'])->label('Фото'); ?>
                            </div>
                            <div class="col-xs-6">
                                <img src="<?= $model->getPhoto('200x200/') ?>" width="200" class="photo-admin-user" />
                                <?php if ($model->image) { ?>
                                    <br /><br />
                                    <a href="<?= Yii::$app->urlManager->createUrl(['/admin/default/remove-photo', 'id' => $model->image->id]) ?>"
                                        class="edit-user remove-object btn btn-sm btn-danger"><i class="fa fa-remove"></i>
                                        Удалить фото</a>
                                <?php } ?>
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-7">
                        <div class="row">
                            <div class="col-sm-4">
                                <?= $form->field($model, 'name')->textInput()->input('text')->label('Имя'); ?>
                            </div>
                            <div class="col-sm-4">
                                <?= $form->field($model, 'lastname')->textInput()->input('text')->label('Фамилия'); ?>
                            </div>
                            <div class="col-sm-4">
                                <?= $form->field($model, 'middlename')->textInput()->input('text')->label('Отчество'); ?>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-sm-6">
                                <?= $form->field($model, 'phone')->textInput()->input('text', ['class' => 'form-control '])->label('Телефон'); ?>
                            </div>
                            <div class="col-sm-6">
                                <?= $form->field($model, 'organization_name')->textInput()->input('text')->label('Название организации'); ?>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-sm-6">
                                <?= $form->field($model, 'email')->textInput()->input('text')->label('E-mail'); ?>
                            </div>
                            <div class="col-sm-6">
                                <?= $form->field($model, 'password')->textInput()->input('password', ['value' => ''])->label($isNewRecord ? 'Пароль' : 'Новый пароль (оставьте пустым, если не меняете)'); ?>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-sm-6">
                                <?= $form->field($model, 'birthday')->textInput()->input('text', ['class' => 'form-control datepicker'])->label('Дата рождения'); ?>
                            </div>
                            <div class="col-sm-6">
                                <?= $form->field($model, 'gender')->radioList(['1' => 'Мужской', '2' => 'Женский'])->label('Пол'); ?>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-sm-6">
                                <?= $form->field($model, 'type')->dropDownList([
                                    'fiz' => 'Физическое лицо',
                                    'yur' => 'Юридическое лицо'
                                ])->label('Тип'); ?>
                            </div>
                            <?php if ($isAdmin): ?>
                                <div class="col-sm-6">
                                    <?= $form->field($model, 'role')->dropDownList(
                                        User::ROLE_LABELS,
                                        ['class' => 'form-control', 'id' => 'user-role']
                                    )->label('Роль'); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        <?php
                        $isManagerRole = $model->role == User::ROLE_MANAGER;
                        $shops = \app\models\shop\Shop::find()->where(['status' => 1])->orderBy('name_ru')->all();
                        $shopList = \yii\helpers\ArrayHelper::map($shops, 'id', function ($shop) {
                            return ($shop->name_ru ?: $shop->name_en ?: "Магазин #{$shop->id}");
                        });
                        ?>
                        <div class="row" id="shop-selector" style="display:<?= $isManagerRole ? 'flex' : 'none' ?>">
                            <div class="col-sm-6">
                                <?= $form->field($model, 'shop_id')->dropDownList(
                                    $shopList,
                                    ['class' => 'form-control select2', 'prompt' => 'Выберите магазин']
                                )->label('Магазин'); ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="box box-info color-palette-box" id="yur-data"
            style="display:<?= ($model->type == 'yur') ? 'block' : 'none'; ?>">
            <div class="box-header">Юридические реквизиты</div>
            <div class="box-body">
                <div class="row">
                    <div class="col-sm-6">
                        <?= $form->field($model, 'inn')->textInput()->input('text')->label('ИНН'); ?>
                    </div>
                    <div class="col-sm-6">
                        <?= $form->field($model, 'account')->textInput()->input('text')->label('Расчетный счет'); ?>
                    </div>
                </div>
                <div class="row">
                    <div class="col-sm-6">
                        <?= $form->field($model, 'bank')->textInput()->input('text')->label('Банк'); ?>
                    </div>
                    <div class="col-sm-6">
                        <?= $form->field($model, 'oked')->textInput()->input('text')->label('ОКЭД'); ?>
                    </div>
                </div>
                <div class="row">
                    <div class="col-sm-6">
                        <?= $form->field($model, 'okohx')->textInput()->input('text')->label('ОКОХХ'); ?>
                    </div>
                    <div class="col-sm-6">
                        <?= $form->field($model, 'mfo')->textInput()->input('text')->label('МФО'); ?>
                    </div>
                </div>
                <?= $form->field($model, 'address_legal')->textInput()->input('text')->label('Юридический адрес'); ?>
            </div>
        </div>
        <div class="box box-info color-palette-box" id="delivery-section"
            style="display:<?= $isManagerRole ? 'none' : 'block' ?>">
            <div class="box-header">Адреса доставки</div>
            <div class="box-body">
                <div id="item-form">
                    <?php if ($model->addresses) { ?>
                        <?php foreach ($model->addresses as $v) { ?>
                            <div class="item-block">
                                <div class="row">
                                    <div class="col-sm-10">
                                        <?= $form->field($model, 'address[]')->textInput()->input('text', ['value' => $v->address, 'placeholder' => 'Введите адрес'])->label(false); ?>
                                    </div>
                                    <div class="col-sm-2">
                                        <a href="javascript:;" class="btn btn-danger remove-block" title="Удалить"><i
                                                class="fa fa-remove"></i> </a>
                                    </div>
                                </div>
                            </div>
                        <?php } ?>
                    <?php } else { ?>
                        <div class="item-block">
                            <div class="row">
                                <div class="col-sm-10">
                                    <?= $form->field($model, 'address[]')->textInput()->input('text', ['placeholder' => 'Введите адрес'])->label(false); ?>
                                </div>
                                <div class="col-sm-2">
                                    <a href="javascript:;" class="btn btn-danger remove-block"><i class="fa fa-remove"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php } ?>
                </div>

                <div class="text-center">
                    <a href="javascript:;" class="add-variant-item btn btn-success btn-sm">
                        <i class="glyphicon glyphicon-plus"></i> Добавить ещё
                    </a>
                </div>
            </div>
            <div class="box-footer">
                <div class="text-right">
                    <?= Html::submitButton('<i class="fa fa-check-square-o"></i> Сохранить', ['class' => 'btn btn-primary']); ?>
                </div>
            </div>
        </div>

        <!-- BTS Region and City -->
        <div class="box box-info color-palette-box">
            <div class="box-header">Регион и город</div>
            <div class="box-body">
                <div class="row">
                    <div class="col-sm-6">
                        <?= $form->field($model, 'bts_region_id')->dropDownList(
                            $regions,
                            [
                                'class' => 'form-control select2',
                                'prompt' => 'Выберите регион',
                                'id' => 'user-region'
                            ]
                        )->label('Регион'); ?>
                    </div>
                    <div class="col-sm-6">
                        <?= $form->field($model, 'bts_city_id')->dropDownList(
                            $currentCities,
                            [
                                'class' => 'form-control select2',
                                'prompt' => 'Выберите город',
                                'id' => 'user-city',
                                'disabled' => empty($model->bts_region_id)
                            ]
                        )->label('Город'); ?>
                    </div>
                </div>
                <div class="alert alert-info">
                    <i class="icon fa fa-info"></i>
                    <strong>Подсказка:</strong> Сначала выберите регион, затем город. Города фильтруются по выбранному
                    региону.
                </div>
            </div>
            <div class="box-footer">
                <div class="text-right">
                    <?= Html::submitButton('<i class="fa fa-check-square-o"></i> Сохранить', ['class' => 'btn btn-primary']); ?>
                </div>
            </div>
        </div>
        <?php ActiveForm::end() ?>
    </section>
</div>

<?php

$roleManager = User::ROLE_MANAGER;
$script = <<<"JS"
    $('#user-type').on('change', function() {
        if ($(this).val() == 'fiz') {
            $('#yur-data').hide();
        }
        if ($(this).val() == 'yur') {
            $('#yur-data').show();
        }
    });

    // Toggle shop selector and delivery section based on role
    let ROLE_MANAGER = {$roleManager};
    function toggleManagerFields() {
        let role = parseInt($('#user-role').val());
        if (role === ROLE_MANAGER) {
            $('#shop-selector').show();
            $('#delivery-section').hide();
        } else {
            $('#shop-selector').hide();
            $('#delivery-section').show();
        }
    }
    $('#user-role').on('change', toggleManagerFields);

    // BTS Region/City dynamic loading
    $('#user-region').on('change', async function() {
        let regionId = $(this).val();
        let citySelect = $('#user-city');

        if (regionId) {
            citySelect.prop('disabled', true).html('<option value="">Загрузка городов...</option>');
            let params = new URLSearchParams({ region_id: regionId }).toString();
            try {
                const response = await fetch('/admin/bts/cities?' + params, {
                    method: 'GET',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });

                if (!response.ok) {
                    throw new Error('Tarmoq xatosi yuz berdi');
                }

                const data = await response.json();
                
                citySelect.html('<option value="">Выберите город</option>');
                
                if (data.success && data.cities) {
                    Object.values(data.cities).forEach(function(city) {
                        if (city && city.name) {
                            let code = city.code;
                            let title = city.name.ru;
                            citySelect.append('<option value="' + code + '">' + title + '</option>');
                        }
                    });
                }
                
            } catch (error) {
                console.error('Xatolik:', error);
                citySelect.html('<option value="">Ошибка загрузки</option>');
            } finally {
                citySelect.prop('disabled', false);
            }
        } else {
            citySelect.html('<option value="">Выберите город</option>').prop('disabled', true);
        }
    });

    // Initialize city dropdown on page load if region is already selected
    var currentRegionId = $('#user-region').val();
    var currentCityId = $('#user-city').find('option[selected]').val();
    if (currentRegionId && currentCityId) {
        $('#user-region').trigger('change');
        setTimeout(function() {
            $('#user-city').val(currentCityId);
        }, 500);
    }
JS;

$this->registerJs($script);
?>