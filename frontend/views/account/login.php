<?php

use yii\helpers\Html;
use yii\helpers\Url;
use yii\widgets\ActiveForm;

/** @var yii\web\View $this */
/** @var app\backend\modules\catalog\models\CustomerLoginForm $model */

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

                    <!-- Демо доступ -->
                    <div class="demo-access-section">
                        <div class="demo-access-header">
                            <i class="bi bi-star-fill text-warning"></i>
                            <span>Демо доступ для тестирования</span>
                        </div>
                        <div class="demo-access-buttons">
                            <button type="button" class="demo-btn btn-outline-primary" onclick="fillDemoData('user')">
                                <i class="bi bi-person"></i>
                                <span>Обычный пользователь</span>
                                <small>demo@sneakerhead.by</small>
                            </button>
                            <button type="button" class="demo-btn btn-outline-success" onclick="fillDemoData('vip')">
                                <i class="bi bi-award"></i>
                                <span>VIP клиент</span>
                                <small>vip@sneakerhead.by</small>
                            </button>
                        </div>
                        <div class="demo-access-note">
                            <small class="text-muted">
                                <i class="bi bi-info-circle"></i>
                                Нажмите кнопку для автоматического заполнения формы
                            </small>
                        </div>
                    </div>

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

<!-- Демо доступ стили -->
<style>
.demo-access-section {
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    border: 2px dashed #6c757d;
    border-radius: 12px;
    padding: 1.5rem;
    margin: 1.5rem 0;
    text-align: center;
}

.demo-access-header {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    margin-bottom: 1rem;
    font-weight: 600;
    color: #495057;
}

.demo-access-buttons {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
    margin-bottom: 1rem;
}

.demo-btn {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.5rem;
    padding: 1rem;
    border: 2px solid;
    border-radius: 8px;
    background: white;
    cursor: pointer;
    transition: all 0.3s ease;
    text-decoration: none;
    font-size: 0.9rem;
}

.demo-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}

.demo-btn.btn-outline-primary {
    border-color: #007bff;
    color: #007bff;
}

.demo-btn.btn-outline-primary:hover {
    background: #007bff;
    color: white;
}

.demo-btn.btn-outline-success {
    border-color: #28a745;
    color: #28a745;
}

.demo-btn.btn-outline-success:hover {
    background: #28a745;
    color: white;
}

.demo-btn i {
    font-size: 1.5rem;
}

.demo-btn small {
    opacity: 0.8;
    font-size: 0.8rem;
}

.demo-access-note {
    font-style: italic;
}

@media (max-width: 576px) {
    .demo-access-buttons {
        grid-template-columns: 1fr;
    }
}
</style>

<!-- Демо доступ JavaScript -->
<script>
function fillDemoData(type) {
    const demoAccounts = {
        user: {
            email: 'demo@sneakerhead.by',
            password: 'demo123'
        },
        vip: {
            email: 'vip@sneakerhead.by',
            password: 'vip123'
        }
    };
    
    const account = demoAccounts[type];
    const emailField = document.querySelector('#customerloginform-email');
    const passwordField = document.querySelector('#customerloginform-password');
    
    if (!emailField || !passwordField) {
        console.error('Поля формы не найдены');
        return;
    }
    
    // Анимация заполнения
    emailField.value = '';
    passwordField.value = '';
    
    // Эффект печатания для email
    let emailIndex = 0;
    const emailInterval = setInterval(() => {
        if (emailIndex < account.email.length) {
            emailField.value += account.email[emailIndex];
            emailIndex++;
        } else {
            clearInterval(emailInterval);
            
            // Эффект печатания для пароля
            let passwordIndex = 0;
            const passwordInterval = setInterval(() => {
                if (passwordIndex < account.password.length) {
                    passwordField.value += account.password[passwordIndex];
                    passwordIndex++;
                } else {
                    clearInterval(passwordInterval);
                    
                    // Подсветка кнопки входа
                    const submitBtn = document.querySelector('.btn-auth');
                    if (submitBtn) {
                        submitBtn.style.background = 'linear-gradient(45deg, #28a745, #20c997)';
                        submitBtn.style.transform = 'scale(1.05)';
                        submitBtn.innerHTML = '<i class="bi bi-check-circle"></i><span>Готово к входу!</span>';
                        
                        setTimeout(() => {
                            submitBtn.style.background = '';
                            submitBtn.style.transform = '';
                            submitBtn.innerHTML = '<i class="bi bi-box-arrow-in-right"></i><span>Войти</span>';
                        }, 2000);
                    }
                }
            }, 50);
        }
    }, 30);
    
    // Визуальная обратная связь
    const demoSection = document.querySelector('.demo-access-section');
    demoSection.style.borderColor = '#28a745';
    demoSection.style.background = 'linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%)';
    
    setTimeout(() => {
        demoSection.style.borderColor = '#6c757d';
        demoSection.style.background = '';
    }, 3000);
}
</script>
