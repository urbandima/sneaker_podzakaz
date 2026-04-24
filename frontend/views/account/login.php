<?php

use yii\helpers\Html;
use yii\helpers\Url;
use yii\widgets\ActiveForm;

/** @var yii\web\View $this */
/** @var app\models\CustomerLoginForm $model */

$this->title = 'Вход в личный кабинет — СНИКЕРХЭД';
$this->registerMetaTag(['name' => 'description', 'content' => 'Войдите в личный кабинет СНИКЕРХЭД для просмотра заказов и управления профилем.']);
$this->registerMetaTag(['name' => 'robots', 'content' => 'noindex, nofollow']);
echo $this->render('_auth-style');
?>

<div class="auth-page login-page">
    <div class="auth-container">
        <a href="<?= Url::to(['/catalog']) ?>" class="back-to-site">
            <i class="bi bi-arrow-left"></i>
            Вернуться в каталог
        </a>

        <div class="auth-grid">
            <section class="auth-card">
                <div class="auth-header">
                    <div class="auth-logo">
                        <img src="/images/logo.png" alt="СНИКЕРХЭД">
                        <span>СНИКЕРХЭД</span>
                    </div>
                    <h2>Вход в личный кабинет</h2>
                    <p>История заказов, бонусы и статусы доставки всегда под рукой.</p>
                </div>

                <div class="auth-body">
                    <?php $form = ActiveForm::begin([
                        'id' => 'login-form',
                        'options' => ['class' => 'auth-form', 'novalidate' => true],
                        'fieldConfig' => [
                            'template' => "{label}\n{input}\n{error}",
                            'labelOptions' => ['class' => ''],
                            'inputOptions' => ['class' => 'form-control'],
                            'errorOptions' => ['class' => 'invalid-feedback', 'role' => 'alert'],
                            'options' => ['class' => 'form-group'],
                        ],
                    ]); ?>

                    <?= $form->field($model, 'email')->textInput([
                        'autofocus' => true,
                        'placeholder' => 'example@mail.com',
                        'value' => Yii::$app->request->get('email', ''),
                        'required' => true,
                        'autocomplete' => 'email',
                        'inputmode' => 'email',
                    ]) ?>

                    <?= $form->field($model, 'password')->passwordInput([
                        'placeholder' => 'Введите пароль',
                        'required' => true,
                        'autocomplete' => 'current-password',
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
                        Соединение защищено SSL
                    </div>

                    <?php ActiveForm::end(); ?>

                    <div class="auth-divider"><span>или</span></div>

                    <div class="social-login">
                        <a href="#" class="social-btn telegram">
                            <i class="bi bi-telegram"></i>
                            Telegram
                        </a>
                    </div>
                </div>

                <div class="auth-footer">
                    Нет аккаунта? <a href="<?= Url::to(['account/register']) ?>">Создать профиль</a>
                </div>
            </section>
        </div>
    </div>
</div>
