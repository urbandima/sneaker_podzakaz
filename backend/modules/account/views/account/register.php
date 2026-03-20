<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

$this->title = 'Регистрация';
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="account-register">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header text-center">
                    <h4 class="mb-0">Регистрация нового клиента</h4>
                </div>
                <div class="card-body">
                    <?php $form = ActiveForm::begin([
                        'id' => 'register-form',
                        'options' => ['class' => 'needs-validation'],
                    ]); ?>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <?= $form->field($model, 'first_name')->textInput([
                                'placeholder' => 'Имя',
                                'autofocus' => true
                            ]) ?>
                        </div>
                        <div class="col-md-6">
                            <?= $form->field($model, 'last_name')->textInput([
                                'placeholder' => 'Фамилия'
                            ]) ?>
                        </div>
                    </div>
                    
                    <?= $form->field($model, 'email')->textInput([
                        'type' => 'email',
                        'placeholder' => 'Email адрес'
                    ]) ?>
                    
                    <?= $form->field($model, 'phone')->textInput([
                        'placeholder' => '+375 (XX) XXX-XX-XX'
                    ]) ?>
                    
                    <?= $form->field($model, 'password')->passwordInput([
                        'placeholder' => 'Пароль (минимум 6 символов)'
                    ]) ?>
                    
                    <?= $form->field($model, 'confirm_password')->passwordInput([
                        'placeholder' => 'Подтвердите пароль'
                    ]) ?>
                    
                    <div class="form-group mb-3">
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" id="agreeTerms" name="agreeTerms" required>
                            <label class="form-check-label" for="agreeTerms">
                                Я согласен с 
                                <?= Html::a('условиями обработки персональных данных', ['/page/privacy'], [
                                    'target' => '_blank'
                                ]) ?> и 
                                <?= Html::a('условиями использования', ['/page/payment-terms'], [
                                    'target' => '_blank'
                                ]) ?>
                            </label>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <?= Html::submitButton('Зарегистрироваться', ['class' => 'btn btn-primary w-100']) ?>
                    </div>
                    
                    <?php ActiveForm::end(); ?>
                    
                    <div class="text-center mt-3">
                        <p class="mb-0">
                            Уже есть аккаунт? <?= Html::a('Войдите', ['account/login']) ?>
                        </p>
                    </div>
                </div>
            </div>
            
            <!-- Преимущества регистрации -->
            <div class="row mt-4">
                <div class="col-md-4">
                    <div class="text-center">
                        <i class="bi bi-gift" style="font-size: 2rem; color: #007bff;"></i>
                        <h6 class="mt-2">Бонусы за регистрацию</h6>
                        <p class="text-muted small">Получите 100 баллов при регистрации</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="text-center">
                        <i class="bi bi-box-seam" style="font-size: 2rem; color: #28a745;"></i>
                        <h6 class="mt-2">История заказов</h6>
                        <p class="text-muted small">Отслеживайте статус ваших заказов</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="text-center">
                        <i class="bi bi-heart" style="font-size: 2rem; color: #dc3545;"></i>
                        <h6 class="mt-2">Избранное</h6>
                        <p class="text-muted small">Сохраняйте понравившиеся товары</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.account-register {
    margin-top: 2rem;
}

.account-register .card {
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    border: none;
}

.account-register .card-header {
    background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
    color: white;
    border-bottom: none;
}
</style>
