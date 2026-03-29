<?php

use yii\helpers\Html;
use yii\helpers\Url;
use yii\widgets\ActiveForm;

/** @var yii\web\View $this */
/** @var app\models\CustomerLoginForm $model */

$this->title = 'Вход в личный кабинет - СНИКЕРХЭД';
echo $this->render('_auth-style');
?>

<div class="auth-page auth-wide login-page">
    <div class="auth-container">
        <a href="<?= Url::to(['/catalog']) ?>" class="back-to-site">
            <i class="bi bi-arrow-left"></i>
            Вернуться в каталог
        </a>

        <div class="auth-grid">
            <section class="auth-info">
                <span class="info-pill">
                    <i class="bi bi-stars"></i>
                    Новый облик 2025
                </span>

                <div class="auth-logo">
                    <img src="https://sneaker-head.by/images/logo.png" alt="СНИКЕРХЭД">
                    <span>СНИКЕРХЭД</span>
                </div>

                <h1>Быстрый вход</h1>
                <p>История заказов, бонусы и статусы доставки всегда под рукой.</p>

                <ul class="auth-benefits">
                    <li>
                        <span class="icon-ring"><i class="bi bi-shield-check"></i></span>
                        <div>
                            <strong>Безопасное подключение</strong>
                            <small>Обновленные протоколы и защита сессии.</small>
                        </div>
                    </li>
                    <li>
                        <span class="icon-ring"><i class="bi bi-bell"></i></span>
                        <div>
                            <strong>Умные уведомления</strong>
                            <small>Отслеживайте статус доставки и новые релизы.</small>
                        </div>
                    </li>
                    <li>
                        <span class="icon-ring"><i class="bi bi-lightning-charge"></i></span>
                        <div>
                            <strong>Мгновенная авторизация</strong>
                            <small>Запоминаем предпочтения и ускоряем вход.</small>
                        </div>
                    </li>
                </ul>

                <div class="auth-stats">
                    <div class="stat">
                        <span>12 000+</span>
                        <small>заказов по СНГ</small>
                    </div>
                    <div class="stat">
                        <span>4.9/5</span>
                        <small>оценка сервиса</small>
                    </div>
                </div>

                <div class="auth-info-footer">
                    <i class="bi bi-headset"></i>
                    <div>
                        <strong>Поддержка 24/7</strong><br>
                        <small>Телеграм, телефон и email</small>
                    </div>
                </div>
            </section>

            <section class="auth-card">
                <div class="auth-header">
                    <span class="mini-pill">
                        <i class="bi bi-lock"></i>
                        Секьюрный вход
                    </span>
                    <h2>Вход в личный кабинет</h2>
                    <p>Продолжите покупки с того места, где остановились, и отслеживайте статусы заказов онлайн.</p>
                </div>

                <div class="auth-body">
                    <?php $form = ActiveForm::begin([
                        'id' => 'login-form',
                        'options' => ['class' => 'auth-form'],
                        'fieldConfig' => [
                            'template' => "{label}\n{input}\n{error}",
                            'labelOptions' => ['class' => ''],
                            'inputOptions' => ['class' => 'form-control'],
                            'errorOptions' => ['class' => 'invalid-feedback'],
                            'options' => ['class' => 'form-group'],
                        ],
                    ]); ?>

                    <?= $form->field($model, 'email')->textInput([
                        'autofocus' => true,
                        'placeholder' => 'example@mail.com',
                        'value' => Yii::$app->request->get('email', ''),
                    ]) ?>

                    <?= $form->field($model, 'password')->passwordInput([
                        'placeholder' => 'Введите пароль',
                    ]) ?>

                    <div class="form-helpers">
                        <label class="form-check-slim" for="rememberMe">
                            <?= Html::activeCheckbox($model, 'rememberMe', ['label' => false, 'id' => 'rememberMe']) ?>
                            <span>Запомнить меня</span>
                        </label>
                        <a href="<?= Url::to(['account/forgot-password']) ?>" class="link-muted">Забыли пароль?</a>
                    </div>

                    <?= Html::submitButton('<i class="bi bi-box-arrow-in-right"></i><span>Войти</span>', ['class' => 'btn-auth']) ?>
                    <div class="secure-badge">
                        <i class="bi bi-shield-lock-fill"></i>
                        Соединение защищено SSL / ISO 27001
                    </div>

                    <?php ActiveForm::end(); ?>

                    <div class="auth-divider"><span>или</span></div>

                    <div class="social-login">
                        <a href="#" class="social-btn yandex">
                            <i class="bi bi-yelp"></i>
                            Яндекс ID
                        </a>
                        <a href="#" class="social-btn telegram">
                            <i class="bi bi-telegram"></i>
                            Telegram
                        </a>
                    </div>

                    <div class="support-links">
                        <a href="tel:+375447009001"><i class="bi bi-telephone"></i> +375 (44) 700-90-01</a>
                        <a href="mailto:sneakerkultura@gmail.com"><i class="bi bi-envelope"></i> sneakerkultura@gmail.com</a>
                        <small>Нажимая «Войти» вы соглашаетесь с договором оферты и политикой конфиденциальности.</small>
                    </div>
                </div>

                <div class="auth-footer">
                    Нет аккаунта? <a href="<?= Url::to(['account/register']) ?>">Создать профиль</a>
                </div>
            </section>
        </div>
    </div>
</div>
