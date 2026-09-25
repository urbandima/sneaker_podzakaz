<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $request app\backend\modules\returns\models\ReturnRequest */

$order = $request->order;
?>

<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;">
    <h2 style="color: #dc3545;">Возврат отклонён</h2>

    <p>Здравствуйте<?= $order ? ', ' . Html::encode($order->client_name) : '' ?>!</p>

    <p>Ваша заявка на возврат №<?= Html::encode($request->return_number) ?> по заказу №<?= Html::encode($order ? $order->order_number : $request->order_id) ?> отклонена.</p>

    <div style="background-color: #f8f9fa; padding: 20px; border-radius: 5px; margin: 20px 0;">
        <?php if ($request->admin_comment) : ?>
            <p><strong>Причина отказа:</strong> <?= Html::encode($request->admin_comment) ?></p>
        <?php endif; ?>
    </div>

    <p>Если вы не согласны с решением или у вас есть вопросы, свяжитесь с нашей службой поддержки.</p>

    <hr style="border: none; border-top: 1px solid #ddd; margin: 30px 0;">

    <p style="color: #6c757d; font-size: 12px;">
        С уважением,<br>
        <?= Yii::$app->params['senderName'] ?><br>
        <?= Yii::$app->params['companyDetails']['phone'] ?> | <?= Yii::$app->params['companyDetails']['email'] ?>
    </p>
</div>
