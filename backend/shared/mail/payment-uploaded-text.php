<?php

/* @var $this yii\web\View */
/* @var $order app\modules\checkout\models\Order */

?>
Загружено подтверждение оплаты для заказа №<?= $order->order_number ?>

Детали заказа:
- Номер: <?= $order->order_number ?>

- Клиент: <?= $order->client_name ?>

- Телефон: <?= $order->client_phone ?>

- Сумма: <?= Yii::$app->formatter->asDecimal($order->total_amount, 2) ?> BYN
- Статус: <?= $order->getStatusLabel() ?>


Для проверки перейдите по ссылке:
<?= Yii::$app->urlManager->createAbsoluteUrl(['admin/order/view', 'id' => $order->id]) ?>

Система управления заказами
<?= Yii::$app->params['senderName'] ?>
