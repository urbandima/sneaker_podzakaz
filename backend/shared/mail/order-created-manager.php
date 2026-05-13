<?php
/**
 * Email шаблон: Уведомление менеджеру о новом заказе
 *
 * @var $order app\backend\modules\checkout\models\Order
 */

use yii\helpers\Html;
use yii\helpers\Url;

$deliveryLabels = [
    'pickup_minsk'  => 'Самовывоз из магазина (Минск)',
    'courier_minsk' => 'Курьер по Минску',
    'europochta'    => 'Европочта',
    'belpochta'     => 'Белпочта',
    'sdek'          => 'СДЭК (Россия)',
];
$deliveryLabel = $deliveryLabels[$order->delivery_method] ?? Html::encode($order->delivery_method);

$paymentLabels = [
    'bank_details' => 'Оплата по реквизитам',
    'pickup_cash'  => 'При получении (наличными/картой)',
    'online'       => 'Онлайн-оплата',
];
$paymentLabel = isset($order->payment_method) ? ($paymentLabels[$order->payment_method] ?? Html::encode($order->payment_method)) : 'Не указан';

$countryLabels = [
    'belarus'    => 'Беларусь',
    'russia'     => 'Россия',
    'kazakhstan' => 'Казахстан',
    'ukraine'    => 'Украина',
    'other'      => 'Другая страна',
];
$countryLabel = $countryLabels[$order->delivery_country ?? ''] ?? Html::encode($order->delivery_country ?? '');

$adminUrl = Url::to(['/admin/order/view', 'id' => $order->id], true);
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 640px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background: #000;
            color: #fff;
            padding: 20px;
            text-align: center;
            border-radius: 8px 8px 0 0;
        }
        .content {
            background: #fff;
            border: 1px solid #e5e7eb;
            border-top: none;
            padding: 30px;
            border-radius: 0 0 8px 8px;
        }
        .alert {
            background: #fef2f2;
            border-left: 4px solid #ef4444;
            padding: 15px;
            margin: 0 0 20px;
        }
        .section-title {
            color: #000;
            margin: 24px 0 10px;
            font-size: 16px;
        }
        .info-block {
            background: #f9fafb;
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 15px;
        }
        .info-row {
            display: flex;
            padding: 7px 0;
            border-bottom: 1px solid #e5e7eb;
        }
        .info-row:last-child { border-bottom: none; }
        .info-label {
            font-weight: 600;
            min-width: 150px;
            color: #555;
        }
        .info-value { flex: 1; color: #000; }
        table.items {
            width: 100%;
            border-collapse: collapse;
            font-size: 14px;
            margin-bottom: 15px;
        }
        table.items th {
            background: #000;
            color: #fff;
            padding: 8px 10px;
            text-align: left;
        }
        table.items td {
            padding: 8px 10px;
            border-bottom: 1px solid #e5e7eb;
            vertical-align: top;
        }
        table.items tr:last-child td { border-bottom: none; }
        .totals {
            background: #f0f9ff;
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 15px;
        }
        .totals .info-row:last-child {
            font-weight: 700;
            font-size: 16px;
        }
        .button {
            display: inline-block;
            background: #000;
            color: #fff;
            padding: 12px 28px;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 600;
        }
        .footer {
            text-align: center;
            color: #888;
            font-size: 13px;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #e5e7eb;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1 style="margin:0;font-size:20px;">Новый заказ на сайте</h1>
    </div>

    <div class="content">
        <div class="alert">
            <strong>Требуется действие!</strong><br>
            Оформлен новый заказ №<?= Html::encode($order->order_number) ?>. Свяжитесь с клиентом и подтвердите заказ.
        </div>

        <h2 class="section-title">Данные клиента</h2>
        <div class="info-block">
            <div class="info-row">
                <div class="info-label">Имя:</div>
                <div class="info-value"><strong><?= Html::encode($order->client_name) ?></strong></div>
            </div>
            <div class="info-row">
                <div class="info-label">Телефон:</div>
                <div class="info-value">
                    <a href="tel:<?= Html::encode($order->client_phone) ?>" style="color:#000;text-decoration:none;">
                        <?= Html::encode($order->client_phone) ?>
                    </a>
                </div>
            </div>
            <?php if ($order->client_email): ?>
            <div class="info-row">
                <div class="info-label">Email:</div>
                <div class="info-value">
                    <a href="mailto:<?= Html::encode($order->client_email) ?>" style="color:#000;">
                        <?= Html::encode($order->client_email) ?>
                    </a>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <h2 class="section-title">Состав заказа</h2>
        <table class="items">
            <thead>
                <tr>
                    <th>Товар</th>
                    <th>Размер / Цвет</th>
                    <th style="text-align:center;">Кол-во</th>
                    <th style="text-align:right;">Цена</th>
                    <th style="text-align:right;">Итого</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($order->orderItems as $item): ?>
                <tr>
                    <td>
                        <?= Html::encode($item->product_name) ?>
                        <?php if ($item->product_article): ?>
                            <br><span style="color:#888;font-size:12px;"><?= Html::encode($item->product_article) ?></span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php
                        $attrs = [];
                        if ($item->size)  $attrs[] = 'р. ' . Html::encode($item->size);
                        if ($item->color) $attrs[] = Html::encode($item->color);
                        echo implode(' / ', $attrs) ?: '—';
                        ?>
                    </td>
                    <td style="text-align:center;"><?= (int)$item->quantity ?></td>
                    <td style="text-align:right;"><?= Yii::$app->formatter->asDecimal($item->price, 2) ?> BYN</td>
                    <td style="text-align:right;"><?= Yii::$app->formatter->asDecimal($item->price * $item->quantity, 2) ?> BYN</td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <div class="totals">
            <div class="info-row">
                <div class="info-label">Стоимость товаров:</div>
                <div class="info-value"><?= Yii::$app->formatter->asDecimal($order->total_amount - $order->delivery_cost, 2) ?> BYN</div>
            </div>
            <div class="info-row">
                <div class="info-label">Доставка:</div>
                <div class="info-value">
                    <?= $order->delivery_cost > 0
                        ? Yii::$app->formatter->asDecimal($order->delivery_cost, 2) . ' BYN'
                        : 'Бесплатно' ?>
                </div>
            </div>
            <div class="info-row">
                <div class="info-label">ИТОГО:</div>
                <div class="info-value"><?= Yii::$app->formatter->asDecimal($order->total_amount, 2) ?> BYN</div>
            </div>
        </div>

        <h2 class="section-title">Доставка</h2>
        <div class="info-block">
            <div class="info-row">
                <div class="info-label">Способ доставки:</div>
                <div class="info-value"><?= $deliveryLabel ?></div>
            </div>
            <?php if ($countryLabel): ?>
            <div class="info-row">
                <div class="info-label">Страна:</div>
                <div class="info-value"><?= $countryLabel ?></div>
            </div>
            <?php endif; ?>
            <?php if ($order->delivery_address): ?>
            <div class="info-row">
                <div class="info-label">Адрес:</div>
                <div class="info-value"><?= Html::encode($order->delivery_address) ?></div>
            </div>
            <?php endif; ?>
            <div class="info-row">
                <div class="info-label">Способ оплаты:</div>
                <div class="info-value"><?= $paymentLabel ?></div>
            </div>
        </div>

        <?php if ($order->comment): ?>
        <h2 class="section-title">Комментарий клиента</h2>
        <div class="info-block">
            <p style="margin:0;"><?= Html::encode($order->comment) ?></p>
        </div>
        <?php endif; ?>

        <div style="text-align:center;margin-top:30px;">
            <a href="<?= Html::encode($adminUrl) ?>" class="button">Открыть заказ в панели управления</a>
        </div>

        <div class="footer">
            <p>Заказ №<?= Html::encode($order->order_number) ?> · <?= Yii::$app->formatter->asDatetime(time()) ?></p>
            <p>СНИКЕРХЭД — система управления заказами</p>
        </div>
    </div>
</body>
</html>
