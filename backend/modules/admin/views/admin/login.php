<?php
/** @var yii\web\View $this */
/** @var app\backend\modules\admin\models\LoginForm $model */

use yii\helpers\Html;
use yii\widgets\ActiveForm;

$this->title = 'Вход в админ-панель - СНИКЕРХЭД';
?>

<div class="login-content">
    <?php $form = ActiveForm::begin([
        'id' => 'login-form',
        'options' => ['class' => 'login-form'],
        'enableClientValidation' => false,
        'enableAjaxValidation' => false,
    ]); ?>

    <?= $form->field($model, 'username', [
        'inputOptions' => [
            'class' => 'admin-form-input',
            'placeholder' => 'Логин',
            'autofocus' => true,
            'required' => true,
            'autocomplete' => 'username'
        ],
        'template' => '{label}{input}{error}'
    ])->label('Логин', ['class' => 'visually-hidden']) ?>

    <?= $form->field($model, 'password', [
        'inputOptions' => [
            'class' => 'admin-form-input',
            'placeholder' => 'Пароль',
            'required' => true,
            'autocomplete' => 'current-password'
        ],
        'template' => '{label}{input}{error}<button type="button" class="password-toggle" aria-label="Показать/скрыть пароль"><i class="bi bi-eye"></i></button>'
    ])->passwordInput()->label('Пароль', ['class' => 'visually-hidden']) ?>

    <div class="login-options">
        <?= $form->field($model, 'rememberMe')->checkbox([
            'class' => 'form-check-input',
            'id' => 'remember-me'
        ])->label('Запомнить меня', [
            'class' => 'form-check-label',
            'for' => 'remember-me'
        ]) ?>
    </div>

    <div class="login-actions">
        <?= Html::submitButton('Войти', [
            'class' => 'admin-btn admin-btn-primary w-100',
            'name' => 'login-button'
        ]) ?>
    </div>

    <?php ActiveForm::end(); ?>
</div>

<div class="login-footer">
    <p>
        <a href="/" class="login-back-link">← Вернуться на сайт</a>
    </p>
</div>

<style>
.visually-hidden {
    position: absolute !important;
    width: 1px !important;
    height: 1px !important;
    padding: 0 !important;
    margin: -1px !important;
    overflow: hidden !important;
    clip: rect(0, 0, 0, 0) !important;
    white-space: nowrap !important;
    border: 0 !important;
}

.password-toggle {
    position: absolute;
    right: 10px;
    top: 50%;
    transform: translateY(-50%);
    background: none;
    border: none;
    cursor: pointer;
    color: var(--admin-text-secondary);
    padding: 5px;
}

.password-toggle:hover {
    color: var(--admin-text-primary);
}

.form-group {
    position: relative;
}

.w-100 {
    width: 100%;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const firstInput = document.querySelector('#login-form input');
    if (firstInput) {
        firstInput.focus();
    }
    
    // Кнопка показать/скрыть пароль
    const passwordToggle = document.querySelector('.password-toggle');
    const passwordInput = document.querySelector('#loginform-password');
    
    if (passwordToggle && passwordInput) {
        passwordToggle.addEventListener('click', function() {
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);
            
            const icon = this.querySelector('i');
            if (type === 'text') {
                icon.classList.remove('bi-eye');
                icon.classList.add('bi-eye-slash');
            } else {
                icon.classList.remove('bi-eye-slash');
                icon.classList.add('bi-eye');
            }
        });
    }
});
</script>
