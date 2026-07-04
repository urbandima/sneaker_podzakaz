<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $orders app\backend\modules\checkout\models\Order[] */
?>

<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;">
    <h2 style="color: #007bff;">Ссылка на отслеживание заказа</h2>

    <p>Вы (или кто-то от вашего имени) запросили ссылки для отслеживания заказов, оформленных на этот email.</p>

    <?php foreach ($orders as $order): ?>
        <div style="background-color: #f8f9fa; padding: 20px; border-radius: 5px; margin: 20px 0;">
            <p><strong>Номер заказа:</strong> <?= Html::encode($order->order_number) ?></p>
            <p><strong>Сумма:</strong> <?= Yii::$app->formatter->asDecimal($order->total_amount, 2) ?> BYN</p>
            <p style="margin: 15px 0 0;">
                <a href="<?= $order->getPublicUrl() ?>" style="background-color: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block;">
                    Просмотреть заказ
                </a>
            </p>
        </div>
    <?php endforeach; ?>

    <p style="color: #6c757d; font-size: 13px;">Если вы не запрашивали эту ссылку, просто проигнорируйте это письмо.</p>

    <hr style="border: none; border-top: 1px solid #ddd; margin: 30px 0;">

    <p style="color: #6c757d; font-size: 12px;">
        С уважением,<br>
        <?= Yii::$app->params['senderName'] ?><br>
        <?= Yii::$app->params['companyDetails']['phone'] ?> | <?= Yii::$app->params['companyDetails']['email'] ?>
    </p>
</div>
