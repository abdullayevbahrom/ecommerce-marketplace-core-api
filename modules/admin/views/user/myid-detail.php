<?php
use yii\helpers\Html;
use yii\helpers\Url;
use app\widgets\admin_user_menu\AdminUserMenu;

$this->title = 'MyID — ' . Html::encode($model->name . ' ' . $model->lastname);
$this->params['breadcrumbs'][] = ['label' => 'Пользователи', 'url' => ['/admin/user']];
$this->params['breadcrumbs'][] = ['label' => $model->name . ' ' . $model->lastname, 'url' => ['/admin/user/view', 'id' => $model->id]];
$this->params['breadcrumbs'][] = 'MyID';
?>

<div class="content-wrapper">
    <section class="content-header">
        <h1>
            <i class="fa fa-shield"></i> <?= Html::encode($this->title) ?>
        </h1>
        <ol class="breadcrumb">
            <li><a href="<?= Url::to(['/admin/']) ?>"><i class="fa fa-dashboard"></i> Главная</a></li>
            <li><a href="<?= Url::to(['/admin/user/']) ?>">Пользователи</a></li>
            <li><a href="<?= Url::to(['/admin/user/view', 'id' => $model->id]) ?>"><?= Html::encode($model->name ?: 'Пользователь #' . $model->id) ?></a></li>
            <li class="active">MyID</li>
        </ol>
    </section>

    <section class="content">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3">
                <?= AdminUserMenu::widget() ?>
            </div>

            <!-- Main Content -->
            <div class="col-md-9">
                <a href="<?= Url::to(['/admin/user/view', 'id' => $model->id]) ?>" class="btn btn-default btn-sm" style="margin-bottom: 15px;">
                    <i class="fa fa-arrow-left"></i> Назад к профилю
                </a>

                <div class="row">
                    <!-- Status Card -->
                    <div class="col-md-4">
                        <div class="box box-<?= $userMyid->isVerified() ? 'success' : 'warning' ?>">
                            <div class="box-header with-border">
                                <h3 class="box-title"><i class="fa fa-shield"></i> Статус верификации</h3>
                            </div>
                            <div class="box-body text-center">
                                <div style="font-size: 48px; margin: 10px 0;">
                                    <?php if ($userMyid->isVerified()): ?>
                                        <i class="fa fa-check-circle text-green"></i>
                                    <?php else: ?>
                                        <i class="fa fa-exclamation-circle text-yellow"></i>
                                    <?php endif; ?>
                                </div>
                                <h4><span class="label label-<?= $userMyid->getVerificationStatusClass() ?>"><?= Html::encode($userMyid->getVerificationStatusLabel()) ?></span></h4>
                                <?php if ($userMyid->comparison_value): ?>
                                    <p class="text-muted" style="margin-top: 10px;">
                                        Совпадение лица: <strong><?= round($userMyid->comparison_value * 100, 1) ?>%</strong>
                                    </p>
                                    <div class="progress" style="height: 8px; margin: 5px 30px;">
                                        <div class="progress-bar progress-bar-<?= $userMyid->comparison_value >= 0.8 ? 'success' : ($userMyid->comparison_value >= 0.5 ? 'warning' : 'danger') ?>"
                                             style="width: <?= round($userMyid->comparison_value * 100) ?>%"></div>
                                    </div>
                                <?php endif; ?>
                                <?php if ($userMyid->verified_at): ?>
                                    <p class="text-muted small">Дата: <?= Html::encode($userMyid->verified_at) ?></p>
                                <?php endif; ?>
                                <?php if ($userMyid->job_id): ?>
                                    <p class="text-muted small">Job ID: <?= Html::encode($userMyid->job_id) ?></p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Personal Data -->
                    <div class="col-md-8">
                        <div class="box box-primary">
                            <div class="box-header with-border">
                                <h3 class="box-title"><i class="fa fa-user"></i> Персональные данные</h3>
                            </div>
                            <div class="box-body">
                                <table class="table table-striped">
                                    <tr>
                                        <th style="width: 200px;">PINFL</th>
                                        <td><code><?= Html::encode($userMyid->pinfl) ?></code></td>
                                    </tr>
                                    <tr>
                                        <th>ФИО (кириллица)</th>
                                        <td><?= Html::encode(trim(($userMyid->last_name ?? '') . ' ' . ($userMyid->first_name ?? '') . ' ' . ($userMyid->middle_name ?? ''))) ?></td>
                                    </tr>
                                    <?php if ($userMyid->first_name_en || $userMyid->last_name_en): ?>
                                    <tr>
                                        <th>ФИО (латиница)</th>
                                        <td><?= Html::encode(trim(($userMyid->last_name_en ?? '') . ' ' . ($userMyid->first_name_en ?? ''))) ?></td>
                                    </tr>
                                    <?php endif; ?>
                                    <tr>
                                        <th>Дата рождения</th>
                                        <td><?= Html::encode($userMyid->birth_date ?? '—') ?></td>
                                    </tr>
                                    <tr>
                                        <th>Место рождения</th>
                                        <td><?= Html::encode($userMyid->birth_place ?? '—') ?></td>
                                    </tr>
                                    <tr>
                                        <th>Пол</th>
                                        <td><?= $userMyid->gender ? Html::encode($userMyid->getGenderLabel()) : '—' ?></td>
                                    </tr>
                                    <tr>
                                        <th>Национальность</th>
                                        <td><?= Html::encode($userMyid->nationality ?? '—') ?></td>
                                    </tr>
                                    <tr>
                                        <th>Гражданство</th>
                                        <td><?= Html::encode($userMyid->citizenship ?? '—') ?></td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <!-- Document Data -->
                    <div class="col-md-6">
                        <div class="box box-info">
                            <div class="box-header with-border">
                                <h3 class="box-title"><i class="fa fa-id-card"></i> Документ</h3>
                            </div>
                            <div class="box-body">
                                <table class="table table-striped">
                                    <tr>
                                        <th style="width: 180px;">Тип документа</th>
                                        <td><?= Html::encode($userMyid->doc_type ?? '—') ?></td>
                                    </tr>
                                    <tr>
                                        <th>Серия и номер</th>
                                        <td>
                                            <?php if ($userMyid->passport_series || $userMyid->passport_number): ?>
                                                <code><?= Html::encode(($userMyid->passport_series ?? '') . ' ' . ($userMyid->passport_number ?? '')) ?></code>
                                            <?php else: ?>
                                                —
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>Кем выдан</th>
                                        <td><?= Html::encode($userMyid->passport_issued_by ?? '—') ?></td>
                                    </tr>
                                    <tr>
                                        <th>Дата выдачи</th>
                                        <td><?= Html::encode($userMyid->passport_issued_date ?? '—') ?></td>
                                    </tr>
                                    <tr>
                                        <th>Срок действия</th>
                                        <td><?= Html::encode($userMyid->passport_expiry_date ?? '—') ?></td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Contact & Address -->
                    <div class="col-md-6">
                        <div class="box box-warning">
                            <div class="box-header with-border">
                                <h3 class="box-title"><i class="fa fa-map-marker"></i> Контакты и адреса</h3>
                            </div>
                            <div class="box-body">
                                <table class="table table-striped">
                                    <tr>
                                        <th style="width: 180px;">Телефон</th>
                                        <td><?= Html::encode($userMyid->phone ?? '—') ?></td>
                                    </tr>
                                    <tr>
                                        <th>Email</th>
                                        <td><?= Html::encode($userMyid->email ?? '—') ?></td>
                                    </tr>
                                    <tr>
                                        <th>Постоянный адрес</th>
                                        <td><?= Html::encode($userMyid->permanent_address ?? '—') ?></td>
                                    </tr>
                                    <tr>
                                        <th>Временный адрес</th>
                                        <td><?= Html::encode($userMyid->temporary_address ?? '—') ?></td>
                                    </tr>
                                    <?php if ($userMyid->living_address): ?>
                                    <tr>
                                        <th>Адрес проживания</th>
                                        <td><?= Html::encode($userMyid->living_address) ?></td>
                                    </tr>
                                    <?php endif; ?>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Technical Info -->
                <div class="row">
                    <div class="col-md-12">
                        <div class="box box-default collapsed-box">
                            <div class="box-header with-border">
                                <h3 class="box-title"><i class="fa fa-cog"></i> Техническая информация</h3>
                                <div class="box-tools pull-right">
                                    <button type="button" class="btn btn-box-tool" data-widget="collapse"><i class="fa fa-plus"></i></button>
                                </div>
                            </div>
                            <div class="box-body">
                                <table class="table table-striped">
                                    <tr>
                                        <th style="width: 200px;">ID записи</th>
                                        <td><?= $userMyid->id ?></td>
                                    </tr>
                                    <tr>
                                        <th>User ID</th>
                                        <td><?= $userMyid->user_id ?? '—' ?></td>
                                    </tr>
                                    <tr>
                                        <th>SDK Hash</th>
                                        <td><code><?= Html::encode($userMyid->sdk_hash ?? '—') ?></code></td>
                                    </tr>
                                    <tr>
                                        <th>Reuid</th>
                                        <td>
                                            <?php if ($userMyid->reuid): ?>
                                                <code><?= Html::encode($userMyid->reuid) ?></code>
                                                <?php if ($userMyid->reuid_expires_at): ?>
                                                    <br><small class="text-muted">Истекает: <?= date('Y-m-d H:i:s', $userMyid->reuid_expires_at) ?></small>
                                                    <?php if ($userMyid->hasValidReuid()): ?>
                                                        <span class="label label-success">Активен</span>
                                                    <?php else: ?>
                                                        <span class="label label-danger">Истёк</span>
                                                    <?php endif; ?>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                —
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>Создано</th>
                                        <td><?= Html::encode($userMyid->created_at ?? '—') ?></td>
                                    </tr>
                                    <tr>
                                        <th>Обновлено</th>
                                        <td><?= Html::encode($userMyid->updated_at ?? '—') ?></td>
                                    </tr>
                                </table>

                                <?php if ($userMyid->myid_response): ?>
                                <h4>Raw MyID Response</h4>
                                <pre style="max-height: 400px; overflow-y: auto; font-size: 12px;"><?= Html::encode(json_encode(json_decode($userMyid->myid_response, true), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) ?></pre>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>
