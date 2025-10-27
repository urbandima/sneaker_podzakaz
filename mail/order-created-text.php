<?php

/* @var $this yii\web\View */
/* @var $order app\models\Order */

?>
Здравствуйте, <?= $order->client_name ?>!

Ваш заказ №<?= $order->order_number ?> успешно создан.

Сумма: <?= Yii::$app->formatter->asDecimal($order->total_amount, 2) ?> BYN
Срок доставки: <?= $order->delivery_date ?>

Для просмотра деталей и оплаты перейдите по ссылке:
<?= $order->getPublicUrl() ?>

По всем вопросам обращайтесь к вашему менеджеру.

С уважением,
<?= Yii::$app->params['senderName'] ?>

<?= Yii::$app->params['companyDetails']['phone'] ?> | <?= Yii::$app->params['companyDetails']['email'] ?>
