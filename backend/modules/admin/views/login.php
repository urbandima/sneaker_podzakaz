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
            'autofocus' => true
        ],
        'template' => '{input}{error}'
    ])->label(false) ?>

    <?= $form->field($model, 'password', [
        'inputOptions' => [
            'class' => 'admin-form-input',
            'placeholder' => 'Пароль'
        ],
        'template' => '{input}{error}'
    ])->passwordInput()->label(false) ?>

    <div class="login-options">
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
.login-hint {
    background: var(--admin-border-light);
    padding: 0.75rem;
    border-radius: var(--admin-radius);
    font-size: 0.875rem;
    text-align: left;
    margin-bottom: 1rem;
}

.login-hint code {
    background: var(--admin-border);
    padding: 2px 6px;
    border-radius: 4px;
    font-family: monospace;
    font-size: 0.75rem;
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
});
</script>
