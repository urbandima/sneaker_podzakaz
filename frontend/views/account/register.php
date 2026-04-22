<?php

use yii\helpers\Html;
use yii\helpers\Url;
use yii\widgets\ActiveForm;

/** @var yii\web\View $this */
/** @var app\models\CustomerRegisterForm $model */

$this->title = 'Регистрация — СНИКЕРХЭД';
$this->registerMetaTag(['name' => 'description', 'content' => 'Создайте аккаунт СНИКЕРХЭД для быстрых заказов, отслеживания доставки и накопления бонусов.']);
$this->registerMetaTag(['name' => 'robots', 'content' => 'noindex, nofollow']);
echo $this->render('_auth-style');
?>

<div class="auth-page auth-wide register-page">
    <div class="auth-container">
        <a href="<?= Url::to(['/catalog']) ?>" class="back-to-site">
            <i class="bi bi-arrow-left"></i> Вернуться в каталог
        </a>
        
        <div class="auth-grid">
            <section class="auth-info">
                <span class="info-pill">
                    <i class="bi bi-stars"></i>
                    Премиум-программа
                </span>

                <div class="auth-logo">
                    <img src="/images/logo.png" alt="СНИКЕРХЭД">
                    <span>СНИКЕРХЭД</span>
                </div>

                <h1>Создайте профиль покупателя</h1>
                <p>Открывайте доступ к персональным рекомендациям, истории заказов и накопительным бонусам клуба.</p>

                <ul class="auth-benefits">
                    <li>
                        <span class="icon-ring"><i class="bi bi-award"></i></span>
                        <div>
                            <strong>Приватные предложения</strong>
                            <small>Промокоды и сборки для зарегистрированных клиентов.</small>
                        </div>
                    </li>
                    <li>
                        <span class="icon-ring"><i class="bi bi-lightning-charge"></i></span>
                        <div>
                            <strong>Быстрые повторные заказы</strong>
                            <small>Мы запомним размеры, адреса и предпочтения.</small>
                        </div>
                    </li>
                    <li>
                        <span class="icon-ring"><i class="bi bi-box-seam"></i></span>
                        <div>
                            <strong>Отслеживание поставок</strong>
                            <small>Статусы вручения, уведомления в Telegram и email.</small>
                        </div>
                    </li>
                </ul>

                <div class="auth-stats">
                    <div class="stat">
                        <span>5 000+</span>
                        <small>участников комьюнити</small>
                    </div>
                    <div class="stat">
                        <span>72 ч</span>
                        <small>раньше доступ к релизам</small>
                    </div>
                    <div class="stat">
                        <span>+10%</span>
                        <small>кэшбек в баллах клуба</small>
                    </div>
                </div>

                <div class="auth-info-footer">
                    <i class="bi bi-chat-heart"></i>
                    <div>
                        <strong>Персональные консультанты</strong><br>
                        <small>Подбор размера, подбор образа, трекинг поставок</small>
                    </div>
                </div>
            </section>

            <section class="auth-card">
                <div class="auth-header">
                    <span class="mini-pill">
                        <i class="bi bi-person-plus"></i>
                        Регистрация
                    </span>
                    <h2>Создайте аккаунт</h2>
                    <p>Понадобится всего пара минут — и у вас готов премиальный личный кабинет.</p>
                </div>

                <div class="auth-body">
                    <?php $form = ActiveForm::begin([
                        'id' => 'register-form',
                        'options' => ['class' => 'auth-form', 'novalidate' => true],
                        'fieldConfig' => [
                            'template' => "{label}\n{input}\n{error}",
                            'labelOptions' => ['class' => ''],
                            'inputOptions' => ['class' => 'form-control'],
                            'errorOptions' => ['class' => 'invalid-feedback', 'role' => 'alert'],
                            'options' => ['class' => 'form-group'],
                        ],
                    ]); ?>

                    <div class="form-grid two-columns">
                        <?= $form->field($model, 'first_name')->textInput([
                            'placeholder' => 'Иван',
                            'required' => true,
                            'autocomplete' => 'given-name',
                        ]) ?>
                        <?= $form->field($model, 'last_name')->textInput([
                            'placeholder' => 'Иванов',
                            'required' => true,
                            'autocomplete' => 'family-name',
                        ]) ?>
                    </div>

                    <?= $form->field($model, 'email')->textInput([
                        'type' => 'email',
                        'placeholder' => 'example@mail.com',
                        'autofocus' => true,
                        'required' => true,
                        'autocomplete' => 'email',
                        'inputmode' => 'email',
                    ]) ?>

                    <?= $form->field($model, 'phone')->textInput([
                        'placeholder' => '+375 29 123-45-67',
                        'required' => true,
                        'autocomplete' => 'tel',
                        'inputmode' => 'tel',
                        'type' => 'tel',
                    ]) ?>

                    <div class="form-grid two-columns">
                        <?= $form->field($model, 'password')->passwordInput([
                            'placeholder' => 'Минимум 6 символов',
                            'required' => true,
                            'autocomplete' => 'new-password',
                        ]) ?>
                        <?= $form->field($model, 'password_confirm')->passwordInput([
                            'placeholder' => 'Повторите пароль',
                            'required' => true,
                            'autocomplete' => 'new-password',
                        ]) ?>
                    </div>

                    <div class="form-check-extended">
                        <?= Html::activeCheckbox($model, 'agree_terms', ['label' => false, 'id' => 'agree_terms']) ?>
                        <label for="agree_terms">
                            Я принимаю условия <a href="<?= Url::to(['/site/offer-agreement']) ?>" target="_blank">договора оферты</a>
                            и <a href="/privacy" target="_blank">политики конфиденциальности</a>.
                        </label>
                    </div>
                    <?= Html::error($model, 'agree_terms', ['class' => 'invalid-feedback']) ?>

                    <div class="form-check-extended">
                        <?= Html::activeCheckbox($model, 'subscribe_news', ['label' => false, 'id' => 'subscribe_news']) ?>
                        <label for="subscribe_news">Хочу получать новости о релизах и приватные скидки.</label>
                    </div>

                    <?= Html::submitButton('<i class="bi bi-person-plus"></i><span>Завершить регистрацию</span>', ['class' => 'btn-auth']) ?>
                    <div class="form-note">
                        <i class="bi bi-shield-check"></i>
                        Мы используем зашифрованные соединения и многоуровневую проверку для защиты данных.
                    </div>

                    <?php ActiveForm::end(); ?>
                </div>

                <div class="auth-footer">
                    Уже есть аккаунт? <a href="<?= Url::to(['account/login']) ?>">Войти</a>
                </div>
            </section>
        </div>
    </div>
</div>
