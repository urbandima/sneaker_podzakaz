<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $resetUrl string */
/* @var $customer app\backend\modules\account\models\Customer */
?>

<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;">
    <h2 style="color: #007bff;">Восстановление пароля</h2>

    <p>Вы (или кто-то от вашего имени) запросили восстановление пароля для аккаунта <?= Html::encode($customer->email) ?>.</p>

    <p style="margin: 20px 0;">
        <a href="<?= Html::encode($resetUrl) ?>" style="background-color: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block;">
            Придумать новый пароль
        </a>
    </p>

    <p style="color: #6c757d; font-size: 13px;">Ссылка действительна 1 час. Если вы не запрашивали восстановление пароля, просто проигнорируйте это письмо — пароль не изменится.</p>

    <hr style="border: none; border-top: 1px solid #ddd; margin: 30px 0;">

    <p style="color: #6c757d; font-size: 12px;">
        С уважением,<br>
        <?= Yii::$app->params['senderName'] ?><br>
        <?= Yii::$app->params['companyDetails']['phone'] ?> | <?= Yii::$app->params['companyDetails']['email'] ?>
    </p>
</div>
