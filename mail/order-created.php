<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $order app\models\Order */
?>

<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;">
    <h2 style="color: #007bff;">Создан новый заказ №<?= Html::encode($order->order_number) ?></h2>
    
    <p>Здравствуйте, <?= Html::encode($order->client_name) ?>!</p>
    
    <p>Ваш заказ успешно создан. Для просмотра деталей и оплаты перейдите по ссылке:</p>
    
    <p style="text-align: center; margin: 30px 0;">
        <a href="<?= $order->getPublicUrl() ?>" style="background-color: #007bff; color: white; padding: 15px 30px; text-decoration: none; border-radius: 5px; display: inline-block;">
            Просмотреть заказ
        </a>
    </p>
    
    <div style="background-color: #f8f9fa; padding: 20px; border-radius: 5px; margin: 20px 0;">
        <h3 style="margin-top: 0;">Информация о заказе:</h3>
        <p><strong>Номер заказа:</strong> <?= Html::encode($order->order_number) ?></p>
        <p><strong>Сумма:</strong> <?= Yii::$app->formatter->asDecimal($order->total_amount, 2) ?> BYN</p>
        <p><strong>Ориентировочный срок доставки:</strong> <?= Html::encode($order->delivery_date) ?></p>
    </div>
    
    <p>По всем вопросам обращайтесь к вашему менеджеру.</p>
    
    <hr style="border: none; border-top: 1px solid #ddd; margin: 30px 0;">
    
    <p style="color: #6c757d; font-size: 12px;">
        С уважением,<br>
        <?= Yii::$app->params['senderName'] ?><br>
        <?= Yii::$app->params['companyDetails']['phone'] ?> | <?= Yii::$app->params['companyDetails']['email'] ?>
    </p>
</div>
