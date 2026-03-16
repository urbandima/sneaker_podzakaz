<?php
/** @var yii\web\View $this */
/** @var app\backend\modules\admin\models\LoginForm $model */

use yii\helpers\Html;
use yii\widgets\ActiveForm;

$this->title = 'Вход в админ-панель - СНИКЕРХЭД';
?>

<div class="admin-login-container">
    <div class="admin-login-card">
        <div class="admin-login-header">
            <h1>Админ-панель</h1>
            <p>Вход в систему управления</p>
        </div>

        <?php $form = ActiveForm::begin([
            'id' => 'login-form',
            'options' => ['class' => 'admin-login-form'],
        ]); ?>

        <?= $form->field($model, 'username', [
            'inputOptions' => [
                'class' => 'form-control form-control-lg',
                'placeholder' => 'Введите имя пользователя',
                'autofocus' => true
            ],
            'template' => '{input}{error}'
        ])->label(false) ?>

        <?= $form->field($model, 'password', [
            'inputOptions' => [
                'class' => 'form-control form-control-lg',
                'placeholder' => 'Введите пароль'
            ],
            'template' => '{input}{error}'
        ])->passwordInput()->label(false) ?>

        <div class="admin-login-options">
            <?= $form->field($model, 'rememberMe', [
                'template' => '<div class="form-check">{input}{label}</div>'
            ])->checkbox([
                'class' => 'form-check-input',
                'id' => 'remember-me'
            ])->label('Запомнить меня', [
                'class' => 'form-check-label',
                'for' => 'remember-me'
            ]) ?>
        </div>

        <div class="admin-login-actions">
            <?= Html::submitButton('Войти', [
                'class' => 'btn btn-primary btn-lg w-100',
                'name' => 'login-button'
            ]) ?>
        </div>

        <?php ActiveForm::end(); ?>

        <div class="admin-login-footer">
            <div class="login-hint alert alert-info">
                <strong>Данные для входа:</strong><br>
                Логин: <code>admin</code><br>
                Пароль: <code>admin123</code>
            </div>
            <p class="text-muted small">
                Для входа используйте учетные данные администратора
            </p>
            <p class="text-muted small">
                <a href="/">← Вернуться на сайт</a>
            </p>
        </div>
    </div>
</div>

<style>
.admin-login-container {
    min-height: 100vh;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 20px;
}

.admin-login-card {
    background: white;
    border-radius: 12px;
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
    overflow: hidden;
    width: 100%;
    max-width: 400px;
}

.admin-login-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 30px;
    text-align: center;
}

.admin-login-header h1 {
    margin: 0 0 10px 0;
    font-size: 24px;
    font-weight: 600;
}

.admin-login-header p {
    margin: 0;
    opacity: 0.9;
    font-size: 14px;
}

.admin-login-form {
    padding: 30px;
}

.admin-login-form .form-group {
    margin-bottom: 20px;
}

.admin-login-form .form-control {
    border: 2px solid #e9ecef;
    border-radius: 8px;
    padding: 12px 16px;
    font-size: 16px;
    transition: all 0.3s ease;
}

.admin-login-form .form-control:focus {
    border-color: #667eea;
    box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
}

.admin-login-form .has-error .form-control {
    border-color: #dc3545;
}

.admin-login-form .help-block {
    color: #dc3545;
    font-size: 13px;
    margin-top: 5px;
}

.admin-login-options {
    margin-bottom: 25px;
}

.admin-login-actions .btn {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border: none;
    border-radius: 8px;
    padding: 12px;
    font-size: 16px;
    font-weight: 600;
    transition: all 0.3s ease;
}

.admin-login-actions .btn:hover {
    transform: translateY(-1px);
    box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3);
}

.admin-login-footer {
    padding: 20px 30px;
    background: #f8f9fa;
    text-align: center;
}

.login-hint {
    margin-bottom: 15px;
    padding: 10px;
    border-radius: 6px;
    font-size: 13px;
    text-align: left;
}

.login-hint code {
    background: #e9ecef;
    padding: 2px 6px;
    border-radius: 3px;
    font-family: 'Courier New', monospace;
    font-size: 12px;
}

.admin-login-footer a {
    color: #667eea;
    text-decoration: none;
}

.admin-login-footer a:hover {
    text-decoration: underline;
}

.form-check {
    display: flex;
    align-items: center;
    gap: 8px;
}

.form-check-input {
    width: 18px;
    height: 18px;
    margin: 0;
}

.form-check-label {
    font-size: 14px;
    color: #6c757d;
    margin: 0;
    cursor: pointer;
}

@media (max-width: 480px) {
    .admin-login-container {
        padding: 10px;
    }
    
    .admin-login-card {
        max-width: 100%;
    }
    
    .admin-login-header,
    .admin-login-form,
    .admin-login-footer {
        padding: 20px;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Фокус на первое поле при загрузке
    const firstInput = document.querySelector('#login-form input');
    if (firstInput) {
        firstInput.focus();
    }
    
    // Автоматическое скрытие ошибок при вводе
    const inputs = document.querySelectorAll('#login-form input');
    inputs.forEach(input => {
        input.addEventListener('input', function() {
            const formGroup = this.closest('.form-group');
            if (formGroup) {
                formGroup.classList.remove('has-error');
                const helpBlock = formGroup.querySelector('.help-block');
                if (helpBlock) {
                    helpBlock.remove();
                }
            }
        });
    });
});
</script>
