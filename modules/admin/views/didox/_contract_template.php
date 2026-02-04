<?php
/* @var $this yii\web\View */
/* @var $arbitrary app\models\didox\DidoxDocumentArbitrary */
/* @var $order app\models\order\Order */
/* @var $document app\models\didox\DidoxDocument */

use yii\helpers\Html;
?>

<div class="contract-document">
    <!-- Contract Header -->
    <div class="contract-header">
        <h2 class="contract-title">ДОГОВОР № <?= Html::encode($arbitrary->contract_no ?: $arbitrary->document_no) ?></h2>
        <p><strong>от <?= date('d.m.Y', strtotime($arbitrary->contract_date ?: $arbitrary->document_date)) ?> г.</strong></p>
        <h3><?= Html::encode($arbitrary->document_name ?: 'Договор на оказание услуг') ?></h3>
        <?php if ($order): ?>
            <p style="font-size: 14px; color: #666; margin-top: 10px;">
                <em>На основании заказа №<?= Html::encode($order->id) ?> от <?= date('d.m.Y H:i', strtotime($order->date)) ?></em>
            </p>
        <?php endif; ?>
    </div>

    <!-- Contract Parties Section -->
    <div class="contract-section">
        <h4>1. СТОРОНЫ ДОГОВОРА</h4>
        
        <table class="contract-table" style="margin-bottom: 20px;">
            <tr>
                <td style="width: 50%; vertical-align: top;">
                    <strong>ПОСТАВЩИК (Исполнитель):</strong><br>
                    <?= Html::encode($arbitrary->seller_name) ?><br>
                    <strong>ИНН:</strong> <?= Html::encode($arbitrary->seller_tin) ?><br>
                    <strong>Адрес:</strong> <?= Html::encode($arbitrary->seller_address) ?><br>
                    <?php if ($arbitrary->seller_branch_name): ?>
                        <strong>Филиал:</strong> <?= Html::encode($arbitrary->seller_branch_name) ?><br>
                    <?php endif; ?>
                </td>
                <td style="width: 50%; vertical-align: top;">
                    <strong>ЗАКАЗЧИК (Покупатель):</strong><br>
                    <?= Html::encode($arbitrary->buyer_name) ?><br>
                    <strong>ИНН/ПИНФЛ:</strong> <?= Html::encode($arbitrary->buyer_tin) ?><br>
                    <strong>Адрес:</strong> <?= Html::encode($arbitrary->buyer_address) ?><br>
                    <?php if ($arbitrary->buyer_branch_name): ?>
                        <strong>Филиал:</strong> <?= Html::encode($arbitrary->buyer_branch_name) ?><br>
                    <?php endif; ?>
                    <?php if ($order && $order->phone): ?>
                        <strong>Телефон:</strong> <?= Html::encode($order->phone) ?><br>
                    <?php endif; ?>
                    <?php if ($order && $order->email): ?>
                        <strong>Email:</strong> <?= Html::encode($order->email) ?><br>
                    <?php endif; ?>
                </td>
            </tr>
        </table>
    </div>

    <?php if ($order): ?>
    <!-- Order Details Section -->
    <div class="contract-section">
        <h4>2. ПРЕДМЕТ ДОГОВОРА</h4>
        <p>Поставщик обязуется поставить, а Заказчик принять и оплатить следующие товары/услуги согласно заказу №<?= Html::encode($order->id) ?>:</p>
        
        <table class="contract-table">
            <thead>
                <tr style="background-color: #f5f5f5;">
                    <th style="width: 5%;">№</th>
                    <th style="width: 35%;">Наименование</th>
                    <th style="width: 10%;">Кол-во</th>
                    <th style="width: 15%;">Ед. изм.</th>
                    <th style="width: 15%;">Цена за ед.</th>
                    <th style="width: 20%;">Сумма</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $totalAmount = 0;
                $totalQuantity = 0;
                $itemIndex = 1;
                ?>
                <?php if ($order->orderProducts): ?>
                    <?php foreach ($order->orderProducts as $orderProduct): ?>
                        <?php 
                        $itemTotal = $orderProduct->amount * $orderProduct->price; // Using amount (quantity) from order_product
                        $totalAmount += $itemTotal;
                        $totalQuantity += $orderProduct->amount;
                        ?>
                        <tr>
                            <td style="text-align: center;"><?= $itemIndex++ ?></td>
                            <td>
                                <?= Html::encode(\app\models\didox\DidoxDocumentArbitrary::getProductName($orderProduct->product)) ?>
                                <?php if ($orderProduct->product && $orderProduct->product->sku): ?>
                                    <br><small style="color: #666;">SKU: <?= Html::encode($orderProduct->product->sku) ?></small>
                                <?php elseif ($orderProduct->product && $orderProduct->product->barcode): ?>
                                    <br><small style="color: #666;">Штрихкод: <?= Html::encode($orderProduct->product->barcode) ?></small>
                                <?php endif; ?>
                            </td>
                            <td style="text-align: center;"><?= number_format($orderProduct->amount, 0) ?></td>
                            <td style="text-align: center;">шт.</td>
                            <td style="text-align: right;"><?= number_format($orderProduct->price, 2) ?> сум</td>
                            <td style="text-align: right;"><?= number_format($itemTotal, 2) ?> сум</td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" style="text-align: center; font-style: italic;">Детали заказа недоступны</td>
                    </tr>
                <?php endif; ?>
                <tr style="background-color: #f0f0f0;">
                    <td colspan="2" style="text-align: right; font-weight: bold;">ИТОГО:</td>
                    <td style="text-align: center; font-weight: bold;"><?= number_format($totalQuantity, 0) ?></td>
                    <td></td>
                    <td></td>
                    <td style="text-align: right; font-weight: bold;"><?= number_format($totalAmount, 2) ?> сум</td>
                </tr>
                <?php if ($order->amount && $order->amount != $totalAmount): ?>
                <tr style="background-color: #f9f9f9;">
                    <td colspan="5" style="text-align: right; font-weight: bold;">Сумма заказа с учетом доставки:</td>
                    <td style="text-align: right; font-weight: bold;"><?= number_format($order->amount, 2) ?> сум</td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Detailed Order Information -->
    <div class="contract-section">
        <h4>3. ДЕТАЛИ ЗАКАЗА</h4>
        <table class="contract-table">
            <tr>
                <td style="width: 25%;"><strong>Номер заказа:</strong></td>
                <td style="width: 25%;"><?= Html::encode($order->id) ?></td>
                <td style="width: 25%;"><strong>Дата заказа:</strong></td>
                <td style="width: 25%;"><?= date('d.m.Y H:i', strtotime($order->date)) ?></td>
            </tr>
            <tr>
                <td><strong>Статус заказа:</strong></td>
                <td><?= Html::encode(\app\models\didox\DidoxDocumentArbitrary::getOrderStatusLabel($order)) ?></td>
                <td><strong>Статус оплаты:</strong></td>
                <td><?= Html::encode(\app\models\didox\DidoxDocumentArbitrary::getOrderPaymentStatusLabel($order)) ?></td>
            </tr>
            <tr>
                <td><strong>Способ доставки:</strong></td>
                <td colspan="3"><?= Html::encode(\app\models\didox\DidoxDocumentArbitrary::getDeliveryName($order->delivery)) ?></td>
            </tr>

            <?php if ($order->comment): ?>
            <tr>
                <td><strong>Комментарий к заказу:</strong></td>
                <td colspan="3"><?= Html::encode($order->comment) ?></td>
            </tr>
            <?php endif; ?>
            <?php if ($order->hasBtsIntegration()): ?>
            <tr>
                <td><strong>BTS информация:</strong></td>
                <td colspan="3">
                    <?php foreach ($order->orderProducts as $orderProduct): ?>
                        <?php if ($orderProduct->bts_id): ?>
                            <div style="margin-bottom: 5px;">
                                <strong>ID:</strong> <?= Html::encode($orderProduct->bts_id) ?> | 
                                <strong>Статус:</strong> <?= Html::encode($orderProduct->bts_status_info) ?>
                                <?php if ($orderProduct->bts_price): ?>
                                    | <strong>Стоимость:</strong> <?= number_format($orderProduct->bts_price, 2) ?> сум
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </td>
            </tr>
            <?php endif; ?>
        </table>
    </div>

    <!-- Payment and Delivery Terms -->
    <div class="contract-section">
        <h4>4. УСЛОВИЯ ОПЛАТЫ И ДОСТАВКИ</h4>
        <ol>
            <li><strong>Общая стоимость заказа:</strong> <?= number_format($order->amount ?: $totalAmount, 2) ?> сум</li>
            <li><strong>Способ оплаты:</strong> Согласно выбранному способу оплаты при оформлении заказа</li>
            <li><strong>Доставка:</strong> <?= Html::encode(\app\models\didox\DidoxDocumentArbitrary::getDeliveryName($order->delivery)) ?>         </li>
            <li><strong>Адрес доставки:</strong> <?= Html::encode($order->address ?: $arbitrary->buyer_address) ?></li>
        </ol>
    </div>
    <?php endif; ?>

    <!-- Terms and Conditions -->
    <div class="contract-section">
        <h4><?= $order ? '5.' : '2.' ?> ОБЩИЕ УСЛОВИЯ ДОГОВОРА</h4>
        <ol>
            <li><strong>Качество:</strong> Все товары/услуги должны соответствовать заявленным характеристикам и требованиям качества.</li>
            <li><strong>Оплата:</strong> Оплата производится в соответствии с условиями, указанными в заказе.</li>
            <li><strong>Приемка товара:</strong> Покупатель обязан осмотреть товар при получении и незамедлительно сообщить о выявленных недостатках.</li>
            <li><strong>Ответственность:</strong> Стороны несут ответственность за неисполнение или ненадлежащее исполнение своих обязательств в соответствии с действующим законодательством Республики Узбекистан.</li>
            <li><strong>Форс-мажор:</strong> Стороны освобождаются от ответственности при наступлении обстоятельств непреодолимой силы.</li>
            <?php if ($order): ?>
            <li><strong>Особые условия:</strong> Настоящий договор составлен на основании заказа №<?= Html::encode($order->id) ?> и является его неотъемлемой частью.</li>
            <?php endif; ?>
        </ol>
    </div>

    <!-- Additional Terms -->
    <div class="contract-section">
        <h4><?= $order ? '6.' : '3.' ?> ДОПОЛНИТЕЛЬНЫЕ УСЛОВИЯ</h4>
        <ul>
            <li>Все споры и разногласия решаются путем переговоров, а при невозможности достижения соглашения - в судебном порядке.</li>
            <li>Договор вступает в силу с момента подписания и действует до полного исполнения сторонами своих обязательств.</li>
            <li>Изменения и дополнения к договору оформляются письменно и подписываются обеими сторонами.</li>
            <li>Настоящий договор может быть подписан с использованием электронной цифровой подписи, что придает ему юридическую силу наравне с документом, подписанным собственноручно.</li>
            <?php if ($order): ?>
            <li>В случае изменения заказа №<?= Html::encode($order->id) ?> стороны обязуются уведомить друг друга и внести соответствующие изменения в настоящий договор.</li>
            <?php endif; ?>
            <li>Во всем остальном, что не предусмотрено настоящим договором, стороны руководствуются действующим законодательством Республики Узбекистан.</li>
        </ul>
    </div>

    <!-- Signatures Section -->
    <div class="signature-section">
        <h4><?= $order ? '7.' : '4.' ?> ПОДПИСИ СТОРОН</h4>
        
        <table style="width: 100%; margin-top: 30px;">
            <tr>
                <td style="width: 50%; vertical-align: top;">
                    <p><strong>ПОСТАВЩИК:</strong></p>
                    <p><?= Html::encode($arbitrary->seller_name) ?></p>
                    <p><strong>ИНН:</strong> <?= Html::encode($arbitrary->seller_tin) ?></p>
                    <br><br>
                    <p>___________________ (_________________)</p>
                    <p style="font-size: 12px;">подпись / Ф.И.О.</p>
                    <br>
                    <p>М.П.</p>
                    <p style="font-size: 11px;">Дата: _______________</p>
                </td>
                <td style="width: 50%; vertical-align: top;">
                    <p><strong>ЗАКАЗЧИК:</strong></p>
                    <p><?= Html::encode($arbitrary->buyer_name) ?></p>
                    <p><strong>ИНН/ПИНФЛ:</strong> <?= Html::encode($arbitrary->buyer_tin) ?></p>
                    <?php if ($order && $order->phone): ?>
                    <p><strong>Телефон:</strong> <?= Html::encode($order->phone) ?></p>
                    <?php endif; ?>
                    <br>
                    <p>___________________ (_________________)</p>
                    <p style="font-size: 12px;">подпись / Ф.И.О.</p>
                    <br>
                    <?php if (strlen($arbitrary->buyer_tin) >= 9): // Likely a company ?>
                    <p>М.П.</p>
                    <?php endif; ?>
                    <p style="font-size: 11px;">Дата: _______________</p>
                </td>
            </tr>
        </table>
    </div>

    <!-- Footer -->
    <div style="margin-top: 40px; text-align: center; font-size: 11px; color: #666;">
        <p>Договор составлен <?= date('d.m.Y') ?> в 2-х экземплярах, имеющих одинаковую юридическую силу, по одному для каждой из сторон.</p>
        <?php if ($order): ?>
        <p style="margin-top: 10px;"><strong>Основание:</strong> Заказ №<?= Html::encode($order->id) ?> от <?= date('d.m.Y H:i', strtotime($order->date)) ?></p>
        <?php endif; ?>
    </div>
</div> 