<?php
use yii\helpers\Html;
use yii\helpers\Url;
use app\widgets\admin_user_menu\AdminUserMenu;
use app\models\user\User;
use app\models\order\Order;
use app\models\transaction\Transaction;
use app\models\user\cart\UserCart;
use app\models\user\favorite\UserFavorite;
use app\models\user\compare\UserCompare;
use app\models\user\card\UserCard;
use app\models\product\ProductRequest;
use app\models\seller\SellerApplication;

/**
 * Safe date formatting function to handle various date formats
 */
function formatDateSafe($date, $format = 'date') {
    if (!$date) {
        return null;
    }
    
    try {
        // Check if the date is in DD/MM/YYYY format and convert it
        if (preg_match('/^(\d{2})\/(\d{2})\/(\d{4})$/', $date, $matches)) {
            // Convert DD/MM/YYYY to YYYY-MM-DD format
            $formattedDate = $matches[3] . '-' . $matches[2] . '-' . $matches[1];
            return $format === 'datetime' ? Yii::$app->formatter->asDatetime($formattedDate) : Yii::$app->formatter->asDate($formattedDate);
        } else {
            // Try to format as-is
            return $format === 'datetime' ? Yii::$app->formatter->asDatetime($date) : Yii::$app->formatter->asDate($date);
        }
    } catch (Exception $e) {
        // If formatting fails, return the raw value
        return Html::encode($date);
    }
}

$this->title = 'Профиль пользователя: ' . ($model->name ?: 'Пользователь #' . $model->id);
$this->params['breadcrumbs'][] = ['label' => 'Пользователи', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;

// Get user statistics
$orderCount = Order::find()->where(['user_id' => $model->id])->count();
$totalSpent = Order::find()->where(['user_id' => $model->id, 'status' => 1])->sum('price') ?: 0;
$transactionCount = Transaction::find()->where(['user_id' => $model->id])->count();
$cartItemCount = UserCart::find()->where(['user_id' => $model->id])->count();
$favoriteCount = UserFavorite::find()->where(['user_id' => $model->id])->count();
$compareCount = UserCompare::find()->where(['user_id' => $model->id])->count();
$cardCount = UserCard::find()->where(['user_id' => $model->id])->count();
$productRequestCount = ProductRequest::find()->where(['user_id' => $model->id])->count();

// Recent orders
$recentOrders = Order::find()->where(['user_id' => $model->id])->orderBy('id DESC')->limit(5)->all();
$recentTransactions = Transaction::find()->where(['user_id' => $model->id])->orderBy('id DESC')->limit(5)->all();
?>

<div class="content-wrapper">
    <section class="content-header">
        <h1>
            <i class="fa fa-user"></i> <?= Html::encode($this->title) ?>
        </h1>
        
        <ol class="breadcrumb">
            <li><a href="<?= Url::to(['/admin/']) ?>"><i class="fa fa-dashboard"></i> Главная</a></li>
            <li><a href="<?= Url::to(['/admin/user/']) ?>">Пользователи</a></li>
            <li class="active"><?= Html::encode($model->name ?: 'Пользователь #' . $model->id) ?></li>
        </ol>
    </section>
    
    <section class="content">
        <!-- Flash Messages -->
        <?php foreach (['user_saved', 'user_locked', 'photo_uploaded', 'wallet_generated'] as $flash): ?>
            <?php if (Yii::$app->session->hasFlash($flash)): ?>
            <div class="callout callout-success text-center shadow-sm">
                    <i class="fa fa-check"></i> <?= Yii::$app->session->getFlash($flash) ?>
            </div>
            <?php endif; ?>
        <?php endforeach; ?>
        
        <?php if (Yii::$app->session->hasFlash('error')): ?>
            <div class="callout callout-danger text-center shadow-sm">
                <i class="fa fa-warning"></i> <?= Yii::$app->session->getFlash('error') ?>
            </div>
        <?php endif; ?>

        <?php if ($model): ?>
            <div class="row">
                <!-- Sidebar -->
                <div class="col-md-3">
                    <?= AdminUserMenu::widget() ?>
                </div>

                <!-- Main Content -->
                <div class="col-md-9">
                    <!-- User Info Card -->
                    <div class="box box-primary">
                        <div class="box-header with-border">
                            <h3 class="box-title"><i class="fa fa-info-circle"></i> Основная информация</h3>
                        </div>
                        <div class="box-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <strong><i class="fa fa-user margin-r-5"></i> ФИО</strong>
                                    <p class="text-muted">
                                        <?= Html::encode(trim($model->lastname . ' ' . $model->name . ' ' . $model->middlename) ?: 'Не указано') ?>
                                    </p>

                                    <strong><i class="fa fa-phone margin-r-5"></i> Телефон</strong>
                                    <p class="text-muted">
                                        <?= Html::encode($model->phone ?: 'Не указан') ?>
                                        <?php if ($model->phone): ?>
                                            <a href="tel:<?= $model->phone ?>" class="btn btn-xs btn-default margin-left-5" title="Позвонить">
                                                <i class="fa fa-phone text-blue"></i>
                                            </a>
                                        <?php endif; ?>
                                    </p>

                                    <strong><i class="fa fa-envelope margin-r-5"></i> Email</strong>
                                    <p class="text-muted">
                                        <?= Html::encode($model->email ?: 'Не указан') ?>
                                        <?php if ($model->email): ?>
                                            <a href="mailto:<?= $model->email ?>" class="btn btn-xs btn-default margin-left-5" title="Написать">
                                                <i class="fa fa-envelope text-blue"></i>
                                            </a>
                                        <?php endif; ?>
                                    </p>
                                </div>
                                <div class="col-md-6">
                                    <strong><i class="fa fa-birthday-cake margin-r-5"></i> Дата рождения</strong>
                                    <p class="text-muted">
                                        <?= formatDateSafe($model->birthday) ?: 'Не указана' ?>
                                    </p>

                                    <strong><i class="fa fa-venus-mars margin-r-5"></i> Пол</strong>
                                    <p class="text-muted">
                                        <?php
                                        $genders = [1 => 'Мужской', 2 => 'Женский'];
                                        echo $model->gender ? ($genders[$model->gender] ?? 'Не указан') : 'Не указан';
                                        ?>
                                    </p>
                                    
                                    <strong><i class="fa fa-calendar margin-r-5"></i> Дата регистрации</strong>
                                    <p class="text-muted">
                                        <?= formatDateSafe($model->date, 'datetime') ?>
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Tabbed Content -->
                    <div class="nav-tabs-custom shadow-sm">
                        <ul class="nav nav-tabs">
                            <li class="active"><a href="#activity" data-toggle="tab"><i class="fa fa-history"></i> Активность</a></li>
                            <li><a href="#orders" data-toggle="tab"><i class="fa fa-shopping-bag"></i> Заказы</a></li>
                            <li><a href="#transactions" data-toggle="tab"><i class="fa fa-exchange"></i> Транзакции</a></li>
                            <li><a href="#addresses" data-toggle="tab"><i class="fa fa-map-marker"></i> Адреса</a></li>
                            <li><a href="#promocodes" data-toggle="tab"><i class="fa fa-ticket"></i> Промокоды</a></li>
                            <?php if ($model->type == 'yur'): ?>
                                <li><a href="#business" data-toggle="tab"><i class="fa fa-briefcase"></i> Реквизиты</a></li>
                            <?php endif; ?>
                            <li><a href="#security" data-toggle="tab"><i class="fa fa-shield"></i> Безопасность</a></li>
                        </ul>
                        <div class="tab-content">
                            <!-- Activity Tab -->
                            <div class="active tab-pane" id="activity">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="box box-solid">
                                            <div class="box-header with-border">
                                                <h3 class="box-title text-green"><i class="fa fa-shopping-cart"></i> Последние заказы</h3>
                                            </div>
                                            <div class="box-body">
                                                <?php if ($recentOrders): ?>
                                                    <ul class="products-list product-list-in-box">
                                                    <?php foreach ($recentOrders as $order): ?>
                                                        <li class="item">
                                                            <div class="product-img">
                                                                <span class="label label-<?= $order->status == 1 ? 'success' : 'warning' ?> pull-left" style="font-size: 14px; padding: 10px; border-radius: 50%;">
                                                                    <i class="fa fa-shopping-bag"></i>
                                                                </span>
                                                            </div>
                                                            <div class="product-info">
                                                                <a href="<?= Url::to(['/admin/order/view', 'id' => $order->id]) ?>" class="product-title">
                                                                    Заказ #<?= $order->id ?>
                                                                    <span class="label label-default pull-right"><?= number_format($order->price, 0, '.', ' ') ?> сум</span>
                                                                </a>
                                                                <span class="product-description">
                                                                    <?= formatDateSafe($order->date) ?> | Товаров: <?= $order->amount ?>
                                                                </span>
                                                            </div>
                                                        </li>
                                                    <?php endforeach; ?>
                                                    </ul>
                                                    <div class="text-center" style="margin-top: 15px;">
                                                        <a href="<?= Url::to(['/admin/order/', 'OrderSearch[user_id]' => $model->id]) ?>" class="uppercase">Посмотреть все заказы</a>
                                                    </div>
                                                <?php else: ?>
                                                    <div class="text-center text-muted" style="padding: 20px;">
                                                        <i class="fa fa-shopping-cart fa-3x" style="opacity: 0.3;"></i><br>
                                                        <span style="margin-top: 10px; display: block;">Заказов пока нет</span>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="box box-solid">
                                            <div class="box-header with-border">
                                                <h3 class="box-title text-blue"><i class="fa fa-credit-card"></i> Последние транзакции</h3>
                                            </div>
                                            <div class="box-body">
                                                <?php if ($recentTransactions): ?>
                                                    <ul class="products-list product-list-in-box">
                                                    <?php foreach ($recentTransactions as $transaction): ?>
                                                        <li class="item">
                                                            <div class="product-img">
                                                                <span class="label label-info pull-left" style="font-size: 14px; padding: 10px; border-radius: 50%;">
                                                                    <i class="fa fa-exchange"></i>
                                                                </span>
                                                            </div>
                                                            <div class="product-info">
                                                                <span class="product-title">
                                                                    <?= Html::encode($transaction->type_payment ?: 'Транзакция') ?>
                                                                    <span class="label label-success pull-right"><?= number_format($transaction->amount, 0, '.', ' ') ?> сум</span>
                                                                </span>
                                                                <span class="product-description">
                                                                    <?= formatDateSafe($transaction->date) ?> | #<?= $transaction->id ?>
                                                                </span>
                                                            </div>
                                                        </li>
                                                    <?php endforeach; ?>
                                                    </ul>
                                                    <div class="text-center" style="margin-top: 15px;">
                                                        <a href="<?= Url::to(['/admin/transaction/', 'TransactionSearch[user_id]' => $model->id]) ?>" class="uppercase">Посмотреть все транзакции</a>
                                                    </div>
                                                <?php else: ?>
                                                    <div class="text-center text-muted" style="padding: 20px;">
                                                        <i class="fa fa-credit-card fa-3x" style="opacity: 0.3;"></i><br>
                                                        <span style="margin-top: 10px; display: block;">Транзакций пока нет</span>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Orders Tab -->
                            <div class="tab-pane" id="orders">
                                <div class="box box-solid">
                                    <div class="box-body table-responsive no-padding">
                                        <?php if ($recentOrders): ?>
                                            <table class="table table-hover">
                                                <thead>
                                                    <tr>
                                                        <th>ID</th>
                                                        <th>Дата</th>
                                                        <th>Сумма</th>
                                                        <th>Товаров</th>
                                                        <th>Статус</th>
                                                        <th></th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($recentOrders as $order): ?>
                                                        <tr>
                                                            <td>#<?= $order->id ?></td>
                                                            <td><?= formatDateSafe($order->date, 'datetime') ?></td>
                                                            <td><strong><?= number_format($order->price, 0, '.', ' ') ?> сум</strong></td>
                                                            <td><?= $order->amount ?></td>
                                                            <td>
                                                                <span class="label label-<?= $order->status == 1 ? 'success' : 'warning' ?>">
                                                                    <?= $order->status == 1 ? 'Завершен' : 'В процессе' ?>
                                                                </span>
                                                            </td>
                                                            <td class="text-right">
                                                                <a href="<?= Url::to(['/admin/order/view', 'id' => $order->id]) ?>" class="btn btn-default btn-xs"><i class="fa fa-eye"></i></a>
                                                            </td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                            <div class="box-footer text-center">
                                                <a href="<?= Url::to(['/admin/order/', 'OrderSearch[user_id]' => $model->id]) ?>" class="btn btn-default">Показать все заказы</a>
                                            </div>
                                        <?php else: ?>
                                            <div class="text-center" style="padding: 40px;">
                                                <i class="fa fa-shopping-cart fa-4x text-muted" style="opacity: 0.3;"></i>
                                                <h4 class="text-muted">История заказов пуста</h4>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>

                            <!-- Transactions Tab -->
                            <div class="tab-pane" id="transactions">
                                <div class="box box-solid">
                                    <div class="box-body table-responsive no-padding">
                                        <?php if ($recentTransactions): ?>
                                            <table class="table table-hover">
                                                <thead>
                                                    <tr>
                                                        <th>ID</th>
                                                        <th>Дата</th>
                                                        <th>Сумма</th>
                                                        <th>Тип</th>
                                                        <th>Метод</th>
                                                        <th>Статус</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($recentTransactions as $transaction): ?>
                                                        <tr>
                                                            <td>#<?= $transaction->id ?></td>
                                                            <td><?= formatDateSafe($transaction->date, 'datetime') ?></td>
                                                            <td class="text-green"><strong>+<?= number_format($transaction->amount, 0, '.', ' ') ?> сум</strong></td>
                                                            <td><?= Html::encode($transaction->type_transaction ?: '-') ?></td>
                                                            <td><?= Html::encode($transaction->type_payment ?: '-') ?></td>
                                                            <td>
                                                                <span class="label label-<?= $transaction->status == 1 ? 'success' : 'warning' ?>">
                                                                    <?= $transaction->status == 1 ? 'Успешно' : 'В процессе' ?>
                                                                </span>
                                                            </td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                            <div class="box-footer text-center">
                                                <a href="<?= Url::to(['/admin/transaction/', 'TransactionSearch[user_id]' => $model->id]) ?>" class="btn btn-default">Показать все транзакции</a>
                                            </div>
                                        <?php else: ?>
                                            <div class="text-center" style="padding: 40px;">
                                                <i class="fa fa-exchange fa-4x text-muted" style="opacity: 0.3;"></i>
                                                <h4 class="text-muted">История транзакций пуста</h4>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>

                            <!-- Addresses Tab -->
                            <div class="tab-pane" id="addresses">
                                <div class="row">
                                    <?php if ($model->addresses): ?>
                                        <?php foreach ($model->addresses as $address): ?>
                                            <div class="col-md-6">
                                                <div class="box box-default box-solid">
                                                    <div class="box-header with-border">
                                                        <h3 class="box-title"><i class="fa fa-map-marker text-red"></i> Адрес доставки</h3>
                                                        <div class="box-tools pull-right">
                                                            <span class="text-muted small"><?= formatDateSafe($address->date) ?></span>
                                                        </div>
                                                    </div>
                                                    <div class="box-body">
                                                        <?= Html::encode($address->address) ?>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <div class="col-md-12 text-center" style="padding: 40px;">
                                            <i class="fa fa-map-marker fa-4x text-muted" style="opacity: 0.3;"></i>
                                            <h4 class="text-muted">Адреса не добавлены</h4>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <?php if ($model->last_address): ?>
                                        <div class="col-md-12">
                                            <div class="callout callout-warning">
                                                <h4><i class="fa fa-clock-o"></i> Последний использованный адрес</h4>
                                                <p><?= Html::encode($model->last_address) ?></p>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- Promocodes Tab -->
                            <div class="tab-pane" id="promocodes">
                                <div class="box box-solid">
                                    <div class="box-header with-border">
                                        <h3 class="box-title"><i class="fa fa-ticket text-purple"></i> Персональные промокоды</h3>
                                        <div class="box-tools pull-right">
                                            <a href="<?= Url::to(['/admin/promocode/create', 'user_id' => $model->id]) ?>" class="btn btn-sm btn-success">
                                                <i class="fa fa-plus"></i> Добавить
                                            </a>
                                        </div>
                                    </div>
                                    <div class="box-body table-responsive no-padding">
                                        <?php 
                                        $userPromocodes = \app\models\Promocode::find()
                                            ->where(['user_id' => $model->id])
                                            ->orderBy('id DESC')
                                            ->all();
                                        ?>
                                        <?php if ($userPromocodes): ?>
                                            <table class="table table-hover">
                                                <thead>
                                                    <tr>
                                                        <th>Код</th>
                                                        <th>Скидка</th>
                                                        <th>Мин. заказ</th>
                                                        <th>Лимит</th>
                                                        <th>Действует до</th>
                                                        <th>Статус</th>
                                                        <th></th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($userPromocodes as $promo): ?>
                                                        <tr>
                                                            <td>
                                                                <code><?= Html::encode($promo->code) ?></code>
                                                                <?php if($promo->is_first_order): ?>
                                                                    <span class="label label-info">Первый заказ</span>
                                                                <?php endif; ?>
                                                            </td>
                                                            <td>
                                                                <?php if($promo->type == 1): ?>
                                                                    <?= number_format($promo->value, 0, '.', ' ') ?> сум
                                                                <?php else: ?>
                                                                    <?= $promo->value ?>%
                                                                <?php endif; ?>
                                                            </td>
                                                            <td><?= $promo->min_order_amount ? number_format($promo->min_order_amount, 0, '.', ' ') . ' сум' : '-' ?></td>
                                                            <td><?= $promo->usage_limit ?: '∞' ?></td>
                                                            <td><?= $promo->end_date ? formatDateSafe($promo->end_date, 'datetime') : 'Бессрочно' ?></td>
                                                            <td>
                                                                <?php if ($promo->status == 1): ?>
                                                                    <span class="label label-success">Активен</span>
                                                                <?php else: ?>
                                                                    <span class="label label-danger">Неактивен</span>
                                                                <?php endif; ?>
                                                            </td>
                                                            <td class="text-right">
                                                                <a href="<?= Url::to(['/admin/promocode/view', 'id' => $promo->id]) ?>" class="btn btn-default btn-xs"><i class="fa fa-eye"></i></a>
                                                                <a href="<?= Url::to(['/admin/promocode/update', 'id' => $promo->id]) ?>" class="btn btn-primary btn-xs"><i class="fa fa-pencil"></i></a>
                                                            </td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        <?php else: ?>
                                            <div class="text-center" style="padding: 40px;">
                                                <i class="fa fa-ticket fa-4x text-muted" style="opacity: 0.3;"></i>
                                                <h4 class="text-muted">Персональных промокодов нет</h4>
                                                <p>Вы можете создать персональный промокод для этого пользователя.</p>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>

                            <!-- Business Info Tab (Only for yur type) -->
                            <?php if ($model->type == 'yur'): ?>
                                <div class="tab-pane" id="business">
                                    <div class="box box-solid">
                                        <div class="box-body">
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <dl class="dl-horizontal">
                                                        <dt>ИНН:</dt>
                                                        <dd><?= Html::encode($model->inn ?: 'Не указан') ?></dd>
                                                        <dt>Расчетный счет:</dt>
                                                        <dd><?= Html::encode($model->account ?: 'Не указан') ?></dd>
                                                        <dt>Банк:</dt>
                                                        <dd><?= Html::encode($model->bank ?: 'Не указан') ?></dd>
                                                        <dt>МФО:</dt>
                                                        <dd><?= Html::encode($model->mfo ?: 'Не указан') ?></dd>
                                                    </dl>
                                                </div>
                                                <div class="col-md-6">
                                                    <dl class="dl-horizontal">
                                                        <dt>ОКЭД:</dt>
                                                        <dd><?= Html::encode($model->oked ?: 'Не указан') ?></dd>
                                                        <dt>ОКОХХ:</dt>
                                                        <dd><?= Html::encode($model->okohx ?: 'Не указан') ?></dd>
                                                        <dt>Юридический адрес:</dt>
                                                        <dd><?= Html::encode($model->address_legal ?: 'Не указан') ?></dd>
                                                    </dl>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <!-- Security Tab -->
                            <div class="tab-pane" id="security">
                                <!-- Crypto Wallet Section -->
                                <div class="box box-primary box-solid">
                                    <div class="box-header with-border">
                                        <h3 class="box-title"><i class="fa fa-google-wallet"></i> Crypto Wallet</h3>
                                    </div>
                                    <div class="box-body">
                                        <?php 
                                        $walletService = new \app\services\WalletService();
                                        
                                        // Get addresses (1 API call)
                                        $addresses = $walletService->predictWallet($model->id);
                                        $eoaAddress = $addresses['eoa_address'];
                                        $aaAddress = $addresses['aa_address'];
                                        
                                        // Get balance and status (1 API call)
                                        $balanceData = ['balance' => 0, 'isDeployed' => false, 'tokens' => [], 'recentTransactions' => []];
                                        try {
                                            $balanceData = $walletService->getBalance($model->id);
                                        } catch (\Exception $e) {
                                            // Ignore connection errors for view
                                        }
                                        
                                        $balance = $balanceData['balance'] ?? 0;
                                        $isDeployed = $balanceData['isDeployed'] ?? false;
                                        $tokens = $balanceData['tokens'] ?? [];
                                        $recentTransactions = $balanceData['recentTransactions'] ?? [];
                                        
                                        // Define images for view rendering
                                        $images = [
                                            'ETH' => 'https://raw.githubusercontent.com/trustwallet/assets/master/blockchains/ethereum/info/logo.png',
                                            'USDT' => 'https://raw.githubusercontent.com/trustwallet/assets/master/blockchains/ethereum/assets/0xdAC17F958D2ee523a2206206994597C13D831ec7/logo.png',
                                            'USDC' => 'https://raw.githubusercontent.com/trustwallet/assets/master/blockchains/ethereum/assets/0xA0b86991c6218b36c1d19D4a2e9Eb0cE3606eB48/logo.png',
                                            'HUMO' => 'https://cdn.example.com/assets/cards/humo.png', 
                                            'app' => 'https://cdn-icons-png.flaticon.com/512/555/555526.png',
                                        ];
                                        
                                        // Add dummy tokens if not present (logic duplicated from controller for view consistency if service doesn't return them yet)
                                        $dummies = [
                                            ['symbol' => 'USDC', 'address' => '0xA0b8...B48', 'balance' => '0.0', 'image' => $images['USDC']],
                                            ['symbol' => 'HUMO', 'address' => '0x0000...HUMO', 'balance' => '0.0', 'image' => $images['HUMO']],
                                            ['symbol' => 'app', 'address' => '0x0000...app', 'balance' => '0.0', 'image' => $images['app']]
                                        ];
                                        
                                        // Simple merge for view display if tokens are empty (fallback)
                                        if (empty($tokens)) {
                                            $tokens = $dummies;
                                        }
                                        
                                        if ($eoaAddress): 
                                        ?>
                                            <div class="row">
                                                <div class="col-md-5">
                                                    <!-- Wallet Card Visual -->
                                                    <div class="wallet-card bg-blue-gradient shadow-lg">
                                                        <div class="wallet-header">
                                                            <i class="fa fa-wifi fa-2x opacity-50"></i>
                                                            <span class="wallet-title">ETH Wallet</span>
                                                        </div>
                                                        <div class="wallet-balance">
                                                            <small class="opacity-75">Total Balance</small>
                                                            <div class="balance-amount"><?= $balance ?> ETH</div>
                                                        </div>
                                                        <div class="wallet-footer">
                                                            <div class="wallet-address-label">AA Address</div>
                                                            <code class="wallet-address-code"><?= $aaAddress ?: 'Not generated' ?></code>
                                                        </div>
                                                        <div class="wallet-status-badge">
                                                            <?php if ($isDeployed): ?>
                                                                <span class="label label-success"><i class="fa fa-check"></i> Active</span>
                                                            <?php else: ?>
                                                                <span class="label label-warning"><i class="fa fa-clock-o"></i> Undeployed</span>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                    
                                                    <div class="text-center mt-20">
                                                         <?php if (!$isDeployed): ?>
                                                            <a href="<?= Url::to(['/admin/user/generate-wallet', 'id' => $model->id]) ?>" 
                                                               class="btn btn-success btn-block btn-lg shadow-sm"
                                                               data-confirm="Are you sure you want to deploy this wallet?">
                                                                <i class="fa fa-rocket"></i> Deploy Smart Wallet
                                                            </a>
                                                            <p class="text-muted small mt-10">Deploying enables advanced features like gasless transactions.</p>
                                                        <?php else: ?>
                                                            <div class="alert alert-success">
                                                                <i class="fa fa-check-circle"></i> Wallet is deployed and active on Sepolia network.
                                                            </div>
                                                            <div class="btn-group btn-group-justified">
                                                                <div class="btn-group">
                                                                    <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#mintModal">
                                                                        <i class="fa fa-plus-circle"></i> Mint Token
                                                                    </button>
                                                                </div>
                                                                <div class="btn-group">
                                                                    <button type="button" class="btn btn-warning" data-toggle="modal" data-target="#payModal">
                                                                        <i class="fa fa-paper-plane"></i> Pay
                                                                    </button>
                                                                </div>
                                                                <div class="btn-group">
                                                                    <button type="button" class="btn btn-info" data-toggle="modal" data-target="#transferModal">
                                                                        <i class="fa fa-exchange"></i> Transfer
                                                                    </button>
                                                                </div>
                                                            </div>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                                
                                                <div class="col-md-7">
                                                    <h4><i class="fa fa-cubes"></i> Assets</h4>
                                                    <div class="table-responsive">
                                                        <table class="table table-hover">
                                                            <tbody>
                                                                <tr>
                                                                    <td width="50"><img src="<?= $images['ETH'] ?>" width="32" height="32" class="img-circle"></td>
                                                                    <td>
                                                                        <strong>Ethereum</strong>
                                                                        <div class="text-muted small">ETH</div>
                                                                    </td>
                                                                    <td class="text-right">
                                                                        <strong><?= $balance ?></strong>
                                                                    </td>
                                                                </tr>
                                                                <?php foreach ($tokens as $token): ?>
                                                                    <?php 
                                                                        $sym = strtoupper($token['symbol']);
                                                                        $img = $token['image'] ?? ($images[$sym] ?? 'https://cdn-icons-png.flaticon.com/512/121/121799.png');
                                                                    ?>
                                                                    <tr>
                                                                        <td width="50">
                                                                            <img src="<?= Html::encode($img) ?>" width="32" height="32" class="img-circle">
                                                                        </td>
                                                                        <td>
                                                                            <strong><?= Html::encode($token['symbol']) ?></strong>
                                                                            <div class="text-muted small"><?= substr($token['address'], 0, 6) ?>...<?= substr($token['address'], -4) ?></div>
                                                                        </td>
                                                                        <td class="text-right">
                                                                            <strong><?= $token['balance'] ?></strong>
                                                                        </td>
                                                                    </tr>
                                                                <?php endforeach; ?>
                                                            </tbody>
                                                        </table>
                                                    </div>
                                                    
                                                    <hr>
                                                    
                                                    <h4><i class="fa fa-history"></i> Recent Transactions</h4>
                                                    <div class="table-responsive" style="max-height: 200px; overflow-y: auto;">
                                                        <?php if ($recentTransactions): ?>
                                                            <table class="table table-condensed table-striped">
                                                                <thead>
                                                                    <tr>
                                                                        <th>Type</th>
                                                                        <th>Asset</th>
                                                                        <th>Amount</th>
                                                                        <th>Time</th>
                                                                        <th>Hash</th>
                                                                    </tr>
                                                                </thead>
                                                                <tbody>
                                                                    <?php foreach ($recentTransactions as $tx): ?>
                                                                        <tr>
                                                                            <td>
                                                                                <?php if (($tx['direction'] ?? '') == 'in'): ?>
                                                                                    <span class="label label-success"><i class="fa fa-arrow-down"></i> IN</span>
                                                                                <?php else: ?>
                                                                                    <span class="label label-warning"><i class="fa fa-arrow-up"></i> OUT</span>
                                                                                <?php endif; ?>
                                                                            </td>
                                                                            <td><?= Html::encode($tx['asset'] ?? '-') ?></td>
                                                                            <td><?= Html::encode($tx['value'] ?? '0') ?></td>
                                                                            <td><?= Yii::$app->formatter->asRelativeTime($tx['timestamp'] ?? time()) ?></td>
                                                                            <td>
                                                                                <a href="https://sepolia.etherscan.io/tx/<?= $tx['hash'] ?? '' ?>" target="_blank" title="<?= $tx['hash'] ?? '' ?>">
                                                                                    <?= substr($tx['hash'] ?? '', 0, 6) ?>...
                                                                                </a>
                                                                            </td>
                                                                        </tr>
                                                                    <?php endforeach; ?>
                                                                </tbody>
                                                            </table>
                                                        <?php else: ?>
                                                            <p class="text-muted text-center">No transactions found.</p>
                                                        <?php endif; ?>
                                                    </div>

                                                    <hr>
                                                    
                                                    <h4><i class="fa fa-info-circle"></i> Details</h4>
                                                    <dl class="dl-horizontal">
                                                        <dt>EOA Address</dt>
                                                        <dd><code class="text-muted"><?= $eoaAddress ?></code></dd>
                                                        <dt>Network</dt>
                                                        <dd><span class="label label-info">Sepolia Testnet</span></dd>
                                                    </dl>
                                                </div>
                                            </div>
                                        <?php else: ?>
                                            <div class="text-center" style="padding: 40px;">
                                                <i class="fa fa-google-wallet fa-4x text-muted" style="opacity: 0.3;"></i>
                                                <h4 class="text-muted">Wallet not initialized</h4>
                                                <p>User does not have a wallet yet.</p>
                                                <br>
                                                <a href="<?= Url::to(['/admin/user/generate-wallet', 'id' => $model->id]) ?>" 
                                                   class="btn btn-primary btn-lg"
                                                   data-confirm="Are you sure you want to generate a wallet for this user?">
                                                    <i class="fa fa-plus"></i> Generate Wallet
                                                </a>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="box box-danger box-solid">
                                            <div class="box-header with-border">
                                                <h3 class="box-title"><i class="fa fa-lock"></i> Безопасность аккаунта</h3>
                                            </div>
                                            <div class="box-body">
                                                <dl class="dl-horizontal">
                                                    <dt>ID пользователя:</dt>
                                                    <dd><?= $model->id ?></dd>
                                                    
                                                    <dt>Токен:</dt>
                                                    <dd>
                                                        <?php if ($model->token): ?>
                                                            <code><?= Html::encode(substr($model->token, 0, 20)) ?>...</code>
                                                        <?php else: ?>
                                                            <span class="text-muted">Не создан</span>
                                                        <?php endif; ?>
                                                    </dd>
                                                    
                                                    <dt>IP адрес:</dt>
                                                    <dd><?= Html::encode($model->ip ?: 'Не указан') ?></dd>
                                                    
                                                    <dt>Device ID:</dt>
                                                    <dd><?= Html::encode($model->device_id ?: 'Не указан') ?></dd>
                                                    
                                                    <dt>Роль:</dt>
                                                    <dd>
                                                        <?php
                                                        $roles = [
                                                            User::ROLE_ADMIN => 'Администратор',
                                                            User::ROLE_MODERATOR => 'Модератор', 
                                                            User::ROLE_USER => 'Пользователь',
                                                            User::ROLE_SHOP => 'Магазин',
                                                            User::ROLE_LOGIST => 'Логист'
                                                        ];
                                                        echo $roles[$model->role] ?? 'Неизвестная роль';
                                                        ?>
                                                    </dd>
                                                </dl>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="box box-info box-solid">
                                            <div class="box-header with-border">
                                                <h3 class="box-title"><i class="fa fa-credit-card"></i> Платежные карты</h3>
                                            </div>
                                            <div class="box-body">
                                                <?php if ($cardCount > 0): ?>
                                                    <div class="text-center">
                                                        <h2 class="text-blue"><?= $cardCount ?></h2>
                                                        <p>привязанных карт</p>
                                                        <a href="<?= Url::to(['/admin/user/cards', 'id' => $model->id]) ?>" class="btn btn-info btn-outline">
                                                            <i class="fa fa-eye"></i> Управление картами
                                                        </a>
                                                    </div>
                                                <?php else: ?>
                                                    <div class="text-center text-muted" style="padding: 20px;">
                                                        <i class="fa fa-credit-card fa-3x" style="opacity: 0.3;"></i><br>
                                                        <span style="margin-top: 10px; display: block;">Карты не добавлены</span>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <div class="alert alert-warning text-center shadow-lg" style="margin-top: 50px;">
                <i class="fa fa-exclamation-triangle fa-4x"></i><br><br>
                <h4>Пользователь не найден</h4>
                <p>Запрашиваемый пользователь не существует или был удален.</p>
                <br>
                <a href="<?= Url::to(['/admin/user/']) ?>" class="btn btn-warning btn-flat">
                    <i class="fa fa-arrow-left"></i> Вернуться к списку пользователей
                </a>
            </div>
        <?php endif; ?>
    </section>
</div>

<!-- Mint Modal -->
<div class="modal fade" id="mintModal" tabindex="-1" role="dialog" aria-labelledby="mintModalLabel">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title" id="mintModalLabel">Mint Tokens</h4>
      </div>
      <?= Html::beginForm(['/admin/user/wallet-mint', 'id' => $model->id], 'post') ?>
      <div class="modal-body">
          <div class="form-group">
              <label>To Address (AA Address)</label>
              <input type="text" name="to" class="form-control" value="<?= $aaAddress ?? '' ?>" required>
          </div>
          <div class="form-group">
              <label>Token Address</label>
              <select name="token" class="form-control" id="mintTokenSelect">
                  <option value="0x7b95CaDaf3Fe1154A7B663f3793856F7e9f21d16">USDT (0x7b95...d16)</option>
                  <option value="0x3f4A04341122360b304C9A896a2Dbfe4cca5B4AE">USDC (0x3f4A...4AE)</option>
                  <option value="custom">Custom...</option>
              </select>
          </div>
          <div class="form-group" id="mintCustomTokenGroup" style="display:none;">
              <label>Custom Token Address</label>
              <input type="text" name="custom_token" class="form-control" placeholder="0x...">
          </div>
          <div class="form-group">
              <label>Amount</label>
              <input type="number" name="amount" class="form-control" value="1000" required>
          </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
        <button type="submit" class="btn btn-primary">Mint</button>
      </div>
      <?= Html::endForm() ?>
    </div>
  </div>
</div>

<!-- Pay Modal -->
<div class="modal fade" id="payModal" tabindex="-1" role="dialog" aria-labelledby="payModalLabel">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title" id="payModalLabel">Execute Payment</h4>
      </div>
      <?= Html::beginForm(['/admin/user/wallet-pay', 'id' => $model->id], 'post') ?>
      <div class="modal-body">
          <div class="form-group">
              <label>From AA Address</label>
              <input type="text" name="aaWalletAddress" class="form-control" value="<?= $aaAddress ?? '' ?>" required>
          </div>
          <div class="form-group">
              <label>Token Address</label>
              <select name="token" class="form-control">
                  <option value="0x7b95CaDaf3Fe1154A7B663f3793856F7e9f21d16">USDT (0x7b95...d16)</option>
                  <option value="0x3f4A04341122360b304C9A896a2Dbfe4cca5B4AE">USDC (0x3f4A...4AE)</option>
              </select>
              <p class="help-block">Payments are restricted to USDT and USDC.</p>
          </div>
          <div class="form-group">
              <label>Merchant Address</label>
              <input type="text" name="merchant" class="form-control" value="0x41Dc3526Aa84a9EEE8ae210FCEE94E016E081EEb" required>
          </div>
          <div class="form-group">
              <label>Amount</label>
              <input type="number" name="amount" class="form-control" value="10" required>
          </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
        <button type="submit" class="btn btn-warning">Pay</button>
      </div>
      <?= Html::endForm() ?>
    </div>
  </div>
</div>

<!-- Transfer Modal -->
<div class="modal fade" id="transferModal" tabindex="-1" role="dialog" aria-labelledby="transferModalLabel">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title" id="transferModalLabel">Transfer Tokens</h4>
      </div>
      <?= Html::beginForm(['/admin/user/wallet-transfer', 'id' => $model->id], 'post') ?>
      <div class="modal-body">
          <div class="form-group">
              <label>To Address</label>
              <input type="text" name="to" class="form-control" placeholder="0x..." required>
          </div>
          <div class="form-group">
              <label>Token</label>
              <select name="token" class="form-control" id="transferTokenSelect">
                  <option value="0x7b95CaDaf3Fe1154A7B663f3793856F7e9f21d16">USDT (0x7b95...d16)</option>
                  <option value="0x3f4A04341122360b304C9A896a2Dbfe4cca5B4AE">USDC (0x3f4A...4AE)</option>
                  <option value="custom">Custom...</option>
              </select>
          </div>
          <div class="form-group" id="customTokenGroup" style="display:none;">
              <label>Custom Token Address</label>
              <input type="text" name="custom_token" class="form-control" placeholder="0x...">
          </div>
          <div class="form-group">
              <label>Amount</label>
              <input type="number" name="amount" class="form-control" step="any" required>
          </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
        <button type="submit" class="btn btn-info">Transfer</button>
      </div>
      <?= Html::endForm() ?>
    </div>
  </div>
</div>

<script>
// Simple script to handle custom token toggle
document.addEventListener('DOMContentLoaded', function() {
    function setupTokenSelect(selectId, groupId) {
        var tokenSelect = document.getElementById(selectId);
        var customGroup = document.getElementById(groupId);
        if (tokenSelect && customGroup) {
            var customInput = customGroup.querySelector('input');
            tokenSelect.addEventListener('change', function() {
                if(this.value === 'custom') {
                    customGroup.style.display = 'block';
                    customInput.required = true;
                } else {
                    customGroup.style.display = 'none';
                    customInput.required = false;
                }
            });
        }
    }

    setupTokenSelect('transferTokenSelect', 'customTokenGroup');
    setupTokenSelect('mintTokenSelect', 'mintCustomTokenGroup');
});
</script>

<style>
.margin-left-5 { margin-left: 5px; }
.margin-left-10 { margin-left: 10px; }
.mt-10 { margin-top: 10px; }
.mt-20 { margin-top: 20px; }
.shadow-sm { box-shadow: 0 1px 3px rgba(0,0,0,0.12), 0 1px 2px rgba(0,0,0,0.24); }
.shadow-lg { box-shadow: 0 10px 20px rgba(0,0,0,0.19), 0 6px 6px rgba(0,0,0,0.23); }
.opacity-50 { opacity: 0.5; }
.opacity-75 { opacity: 0.75; }

/* Wallet Card Styles */
.wallet-card {
    border-radius: 16px;
    padding: 25px;
    color: white;
    margin-bottom: 20px;
    position: relative;
    overflow: hidden;
}
.wallet-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 25px;
}
.wallet-title {
    font-size: 1.2em;
    font-weight: 600;
    letter-spacing: 1px;
}
.wallet-balance {
    margin-bottom: 20px;
}
.balance-amount {
    font-size: 2.8em;
    font-weight: 700;
    line-height: 1;
    margin-top: 5px;
}
.wallet-footer {
    background: rgba(0,0,0,0.15);
    padding: 10px 15px;
    border-radius: 10px;
    backdrop-filter: blur(5px);
}
.wallet-address-label {
    font-size: 0.8em;
    opacity: 0.8;
    margin-bottom: 2px;
}
.wallet-address-code {
    color: white;
    background: transparent;
    padding: 0;
    font-family: monospace;
    font-size: 0.9em;
}
.wallet-status-badge {
    position: absolute;
    top: 20px;
    right: 20px;
}
</style>