<?php

use yii\helpers\Html;
use yii\helpers\Url;

/* @var $this yii\web\View */
/* @var $order app\models\Order */
?>

<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;">
    <h2 style="color: #28a745;">Загружено подтверждение оплаты</h2>
    
    <p>Клиент загрузил подтверждение оплаты для заказа №<?= Html::encode($order->order_number) ?>.</p>
    
    <div style="background-color: #f8f9fa; padding: 20px; border-radius: 5px; margin: 20px 0;">
        <h3 style="margin-top: 0;">Детали заказа:</h3>
        <p><strong>Номер заказа:</strong> <?= Html::encode($order->order_number) ?></p>
        <p><strong>Клиент:</strong> <?= Html::encode($order->client_name) ?></p>
        <p><strong>Телефон:</strong> <?= Html::encode($order->client_phone) ?></p>
        <p><strong>Сумма:</strong> <?= Yii::$app->formatter->asDecimal($order->total_amount, 2) ?> BYN</p>
        <p><strong>Статус:</strong> <?= $order->getStatusLabel() ?></p>
    </div>
    
    <p style="text-align: center; margin: 30px 0;">
        <a href="<?= Yii::$app->urlManager->createAbsoluteUrl(['admin/view-order', 'id' => $order->id]) ?>" 
           style="background-color: #007bff; color: white; padding: 15px 30px; text-decoration: none; border-radius: 5px; display: inline-block;">
            Проверить оплату
        </a>
    </p>
    
    <p style="color: #dc3545;">Пожалуйста, проверьте подтверждение оплаты и обновите статус заказа.</p>
    
    <hr style="border: none; border-top: 1px solid #ddd; margin: 30px 0;">
    
    <p style="color: #6c757d; font-size: 12px;">
        Система управления заказами<br>
        <?= Yii::$app->params['senderName'] ?>
    </p>
</div>
