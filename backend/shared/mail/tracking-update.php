<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $order app\backend\modules\checkout\models\Order */
/* @var $tracking app\backend\modules\checkout\models\DeliveryTracking */
/* @var $data array */
?>

<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;">
    <h2 style="color: #007bff;">Обновление статуса доставки заказа №<?= Html::encode($order->order_number) ?></h2>

    <p>Здравствуйте, <?= Html::encode($order->client_name) ?>!</p>

    <p>Статус доставки вашего заказа изменился:</p>

    <div style="background-color: #f8f9fa; padding: 20px; border-radius: 5px; margin: 20px 0;">
        <p><strong>Новый статус:</strong> <?= Html::encode($tracking->getStatusName()) ?></p>
        <?php if ($tracking->status_description) : ?>
            <p><strong>Описание:</strong> <?= Html::encode($tracking->status_description) ?></p>
        <?php endif; ?>
        <?php if ($tracking->location) : ?>
            <p><strong>Местоположение:</strong> <?= Html::encode($tracking->location) ?></p>
        <?php endif; ?>
        <?php if ($tracking->tracking_number) : ?>
            <p><strong>Трек-номер:</strong> <?= Html::encode($tracking->tracking_number) ?></p>
        <?php endif; ?>
        <?php if ($tracking->estimated_delivery) : ?>
            <p><strong>Ожидаемая дата доставки:</strong> <?= Html::encode($tracking->estimated_delivery) ?></p>
        <?php endif; ?>
    </div>

    <p style="text-align: center; margin: 30px 0;">
        <a href="<?= $order->getPublicUrl() ?>" style="background-color: #007bff; color: white; padding: 15px 30px; text-decoration: none; border-radius: 5px; display: inline-block;">
            Просмотреть заказ
        </a>
    </p>

    <hr style="border: none; border-top: 1px solid #ddd; margin: 30px 0;">

    <p style="color: #6c757d; font-size: 12px;">
        С уважением,<br>
        <?= Yii::$app->params['senderName'] ?><br>
        <?= Yii::$app->params['companyDetails']['phone'] ?> | <?= Yii::$app->params['companyDetails']['email'] ?>
    </p>
</div>
