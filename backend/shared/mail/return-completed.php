<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $request app\backend\modules\returns\models\ReturnRequest */

$order = $request->order;
?>

<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;">
    <h2 style="color: #28a745;">Возврат завершён</h2>

    <p>Здравствуйте<?= $order ? ', ' . Html::encode($order->client_name) : '' ?>!</p>

    <p>Возврат по заявке №<?= Html::encode($request->return_number) ?> (заказ №<?= Html::encode($order ? $order->order_number : $request->order_id) ?>) завершён. Средства возвращены.</p>

    <div style="background-color: #f8f9fa; padding: 20px; border-radius: 5px; margin: 20px 0;">
        <p><strong>Сумма возврата:</strong> <?= Yii::$app->formatter->asDecimal($request->refund_amount, 2) ?> BYN</p>
        <?php if ($request->refund_method) : ?>
            <p><strong>Способ возврата:</strong> <?= Html::encode($request->refund_method) ?></p>
        <?php endif; ?>
        <?php if ($request->refund_transaction) : ?>
            <p><strong>Номер транзакции:</strong> <?= Html::encode($request->refund_transaction) ?></p>
        <?php endif; ?>
    </div>

    <p>Спасибо, что оставались с нами. Будем рады видеть вас снова.</p>

    <hr style="border: none; border-top: 1px solid #ddd; margin: 30px 0;">

    <p style="color: #6c757d; font-size: 12px;">
        С уважением,<br>
        <?= Yii::$app->params['senderName'] ?><br>
        <?= Yii::$app->params['companyDetails']['phone'] ?> | <?= Yii::$app->params['companyDetails']['email'] ?>
    </p>
</div>
