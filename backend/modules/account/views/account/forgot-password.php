<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

$this->title = 'Восстановление пароля';
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="account-forgot-password">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header text-center">
                    <h4 class="mb-0">Восстановление пароля</h4>
                </div>
                <div class="card-body">
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle"></i>
                        Введите ваш email адрес. Мы отправим вам инструкции по восстановлению пароля.
                    </div>
                    
                    <?php $form = ActiveForm::begin([
                        'id' => 'forgot-password-form',
                        'options' => ['class' => 'needs-validation'],
                    ]); ?>
                    
                    <?= $form->field($model, 'email')->textInput([
                        'type' => 'email',
                        'placeholder' => 'Введите ваш email',
                        'autofocus' => true
                    ]) ?>
                    
                    <div class="form-group">
                        <?= Html::submitButton('Отправить инструкции', ['class' => 'btn btn-primary w-100']) ?>
                    </div>
                    
                    <?php ActiveForm::end(); ?>
                    
                    <div class="text-center mt-3">
                        <p class="mb-0">
                            <?= Html::a('← Вернуться к входу', ['account/login']) ?>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.account-forgot-password {
    margin-top: 2rem;
}

.account-forgot-password .card {
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    border: none;
}

.account-forgot-password .card-header {
    background: linear-gradient(135deg, #ffc107 0%, #ff9800 100%);
    color: white;
    border-bottom: none;
}
</style>
