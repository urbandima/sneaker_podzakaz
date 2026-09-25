<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $customer app\backend\modules\account\models\Customer */
/* @var $cart app\backend\modules\cart\models\Cart */
/* @var $recoveryUrl string */

$product = $cart->product;
?>

<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;">
    <h2 style="color: #007bff;">Вы забыли товар в корзине</h2>

    <p>Здравствуйте, <?= Html::encode($customer->getFullName() ?: $customer->email) ?>!</p>

    <p>Вы добавили товар в корзину, но не завершили оформление заказа. Он всё ещё ждёт вас:</p>

    <div style="background-color: #f8f9fa; padding: 20px; border-radius: 5px; margin: 20px 0;">
        <?php if ($product) : ?>
            <p><strong><?= Html::encode($product->brand_name) ?> <?= Html::encode($product->name) ?></strong></p>
        <?php endif; ?>
        <?php if ($cart->size) : ?>
            <p>Размер: <?= Html::encode($cart->size) ?></p>
        <?php endif; ?>
        <?php if ($cart->color) : ?>
            <p>Цвет: <?= Html::encode($cart->color) ?></p>
        <?php endif; ?>
        <p>Количество: <?= (int) $cart->quantity ?></p>
        <p><strong>Сумма:</strong> <?= Yii::$app->formatter->asDecimal($cart->getSubtotal(), 2) ?> BYN</p>
    </div>

    <p style="text-align: center; margin: 30px 0;">
        <a href="<?= Html::encode($recoveryUrl) ?>" style="background-color: #007bff; color: white; padding: 15px 30px; text-decoration: none; border-radius: 5px; display: inline-block;">
            Вернуться в корзину
        </a>
    </p>

    <p style="color: #6c757d; font-size: 13px;">Если вы уже оформили заказ или корзина вам больше не нужна, просто проигнорируйте это письмо.</p>

    <hr style="border: none; border-top: 1px solid #ddd; margin: 30px 0;">

    <p style="color: #6c757d; font-size: 12px;">
        С уважением,<br>
        <?= Yii::$app->params['senderName'] ?><br>
        <?= Yii::$app->params['companyDetails']['phone'] ?> | <?= Yii::$app->params['companyDetails']['email'] ?>
    </p>
</div>
