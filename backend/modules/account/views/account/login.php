<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use yii\helpers\Url;

$this->title = 'Вход';
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="account-login">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header text-center">
                    <h4 class="mb-0">Вход в личный кабинет</h4>
                </div>
                <div class="card-body">
                    <?php $form = ActiveForm::begin([
                        'id' => 'login-form',
                        'options' => ['class' => 'needs-validation'],
                    ]); ?>
                    
                    <?= $form->field($model, 'email')->textInput([
                        'type' => 'email',
                        'placeholder' => 'Введите ваш email',
                        'autofocus' => true
                    ]) ?>
                    
                    <?= $form->field($model, 'password')->passwordInput([
                        'placeholder' => 'Введите пароль'
                    ]) ?>
                    
                    <div class="form-group mb-3">
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" id="rememberMe" name="rememberMe">
                            <label class="form-check-label" for="rememberMe">
                                Запомнить меня
                            </label>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <?= Html::submitButton('Войти', ['class' => 'btn btn-primary w-100']) ?>
                    </div>
                    
                    <?php ActiveForm::end(); ?>
                    
                    <div class="text-center mt-3">
                        <p class="mb-2">
                            <?= Html::a('Забыли пароль?', ['account/forgot-password']) ?>
                        </p>
                        <p class="mb-0">
                            Нет аккаунта? <?= Html::a('Зарегистрироваться', ['account/register']) ?>
                        </p>
                    </div>
                    
                    <hr>
                    
                    <!-- Демо доступ -->
                    <div class="demo-access">
                        <div class="alert alert-success">
                            <h6><i class="bi bi-star-fill"></i> Демо доступ</h6>
                            <p class="mb-2 small">Попробуйте личный кабинет с демо-аккаунтом:</p>
                            <div class="row">
                                <div class="col-6">
                                    <button class="btn btn-sm btn-outline-primary w-100" onclick="fillDemo('user')">
                                        <i class="bi bi-person"></i> Обычный пользователь
                                    </button>
                                </div>
                                <div class="col-6">
                                    <button class="btn btn-sm btn-outline-success w-100" onclick="fillDemo('vip')">
                                        <i class="bi bi-award"></i> VIP клиент
                                    </button>
                                </div>
                            </div>
                            <p class="mb-0 mt-2 small text-muted">
                                Email и пароль заполнятся автоматически
                            </p>
                        </div>
                    </div>
                    
                    <!-- Быстрый доступ к заказам -->
                    <div class="quick-access text-center mt-3">
                        <h6>Быстрый доступ к заказам</h6>
                        <p class="text-muted small">Если у вас есть заказ, но нет аккаунта</p>
                        <?= Html::a('Найти заказы', ['account/find-orders'], ['class' => 'btn btn-outline-secondary btn-sm']) ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.account-login {
    margin-top: 2rem;
}

.account-login .card {
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    border: none;
}

.account-login .card-header {
    background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
    color: white;
    border-bottom: none;
}

.quick-access {
    background: #f8f9fa;
    padding: 1rem;
    border-radius: 8px;
    margin-top: 1rem;
}

.demo-access .alert {
    border: none;
    border-radius: 8px;
}

.demo-access .btn:hover {
    transform: translateY(-1px);
    transition: all 0.2s ease;
}

@keyframes pulse {
    0% {
        box-shadow: 0 0 0 0 rgba(0, 123, 255, 0.7);
    }
    70% {
        box-shadow: 0 0 0 10px rgba(0, 123, 255, 0);
    }
    100% {
        box-shadow: 0 0 0 0 rgba(0, 123, 255, 0);
    }
}

.pulse {
    animation: pulse 2s infinite;
}
</style>

<script>
function fillDemo(type) {
    const emailField = document.querySelector('#customerloginform-email');
    const passwordField = document.querySelector('#customerloginform-password');
    
    if (!emailField || !passwordField) {
        console.error('Поля формы не найдены');
        return;
    }
    
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
    
    // Анимация заполнения
    emailField.value = '';
    passwordField.value = '';
    
    // Заполняем email
    let emailText = '';
    const emailInterval = setInterval(() => {
        if (emailText.length < account.email.length) {
            emailText += account.email[emailText.length];
            emailField.value = emailText;
        } else {
            clearInterval(emailInterval);
            
            // Заполняем пароль
            let passwordText = '';
            const passwordInterval = setInterval(() => {
                if (passwordText.length < account.password.length) {
                    passwordText += account.password[passwordText.length];
                    passwordField.value = passwordText;
                } else {
                    clearInterval(passwordInterval);
                    
                    // Фокус на кнопку входа
                    const submitBtn = document.querySelector('#login-form button[type="submit"]');
                    if (submitBtn) {
                        submitBtn.classList.add('pulse');
                        setTimeout(() => {
                            submitBtn.classList.remove('pulse');
                        }, 2000);
                    }
                }
            }, 50);
        }
    }, 30);
    
    // Показываем уведомление
    const alert = document.querySelector('.demo-access .alert');
    const originalContent = alert.innerHTML;
    alert.innerHTML = `
        <h6><i class="bi bi-check-circle-fill"></i> Данные заполнены!</h6>
        <p class="mb-0 small">Нажмите "Войти" для входа в демо-аккаунт</p>
    `;
    alert.classList.remove('alert-success');
    alert.classList.add('alert-info');
    
    setTimeout(() => {
        alert.innerHTML = originalContent;
        alert.classList.remove('alert-info');
        alert.classList.add('alert-success');
    }, 3000);
}
</script>
