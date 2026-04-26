<?php

use yii\helpers\Html;
use yii\helpers\Url;
use yii\widgets\ActiveForm;

/** @var yii\web\View $this */
/** @var app\models\Customer $customer */
/** @var app\models\Order[] $orders */

$this->title = 'Мой профиль - СНИКЕРХЭД';

// Breadcrumbs для ЛК
$this->params['breadcrumbs'][] = ['label' => 'Главная', 'url' => ['/']];
$this->params['breadcrumbs'][] = ['label' => 'Личный кабинет', 'url' => ['/account']];
$this->params['breadcrumbs'][] = 'Профиль';
?>

<div class="account-page account-page--wide">
    <div class="container">
        <!-- Breadcrumbs -->
        <nav class="account-breadcrumbs">
            <a href="/">Главная</a>
            <span>/</span>
            <a href="/account">Личный кабинет</a>
            <span>/</span>
            <span class="current">Профиль</span>
        </nav>
        
        <div class="account-header">
            <h1><i class="bi bi-person-circle"></i> Личный кабинет</h1>
        </div>

        <div class="account-grid">
            <?= $this->render('_sidebar', [
                'customer' => $customer,
                'activePage' => 'profile',
                'orders' => $orders,
            ]) ?>

            <main class="account-content">
                <?php if (Yii::$app->session->hasFlash('success')): ?>
                    <div class="alert alert-success" style="background:#d1fae5;border:1px solid #6ee7b7;color:#065f46;padding:12px 16px;border-radius:8px;margin-bottom:16px;display:flex;align-items:center;gap:8px;">
                        <i class="bi bi-check-circle-fill"></i>
                        <?= Html::encode(Yii::$app->session->getFlash('success')) ?>
                    </div>
                <?php endif; ?>
                <?php if (Yii::$app->session->hasFlash('error')): ?>
                    <div class="alert alert-error" style="background:#fee2e2;border:1px solid #fca5a5;color:#991b1b;padding:12px 16px;border-radius:8px;margin-bottom:16px;display:flex;align-items:center;gap:8px;">
                        <i class="bi bi-exclamation-circle-fill"></i>
                        <?= Html::encode(Yii::$app->session->getFlash('error')) ?>
                    </div>
                <?php endif; ?>
                <div class="content-card">
                    <h2><i class="bi bi-person-lines-fill"></i> Личные данные</h2>

                    <?php $form = ActiveForm::begin([
                        'id' => 'profile-form',
                        'options' => ['class' => 'profile-form'],
                        'fieldConfig' => [
                            'template' => "{label}\n{input}\n{error}",
                            'inputOptions' => ['class' => 'form-control'],
                            'errorOptions' => ['class' => 'help-block'],
                        ],
                    ]); ?>

                    <div class="form-row">
                        <?= $form->field($customer, 'last_name')->textInput(['placeholder' => 'Иванов']) ?>
                        <?= $form->field($customer, 'first_name')->textInput(['placeholder' => 'Иван']) ?>
                    </div>
                    
                    <div class="form-row">
                        <?= $form->field($customer, 'middle_name')->textInput(['placeholder' => 'Иванович']) ?>
                        <?= $form->field($customer, 'phone')->textInput(['placeholder' => '+375 29 123-45-67']) ?>
                    </div>
                    
                    <div class="form-row">
                        <?= $form->field($customer, 'birth_date')->input('date') ?>
                        <?= $form->field($customer, 'gender')->dropDownList([
                            '' => 'Не указан',
                            'male' => 'Мужской',
                            'female' => 'Женский',
                        ]) ?>
                    </div>

                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" class="form-control" value="<?= Html::encode($customer->email) ?>" disabled>
                        <small class="form-hint">Для смены email обратитесь в поддержку</small>
                    </div>

                    <div class="form-section">
                        <div class="form-section-title">Адрес доставки по умолчанию</div>
                        
                        <div class="form-row form-row-3">
                            <?= $form->field($customer, 'default_country')->dropDownList([
                                'BY' => 'Беларусь',
                                'RU' => 'Россия',
                                'KZ' => 'Казахстан',
                            ]) ?>
                            <?= $form->field($customer, 'default_city')->textInput(['placeholder' => 'Минск']) ?>
                            <?= $form->field($customer, 'default_postal_code')->textInput(['placeholder' => '220000']) ?>
                        </div>
                        
                        <?= $form->field($customer, 'default_address')->textarea([
                            'rows' => 2,
                            'placeholder' => 'ул. Примерная, д. 1, кв. 123',
                        ]) ?>
                    </div>

                    <div class="form-section">
                        <div class="form-section-title">Паспортные данные (для таможенного оформления)</div>
                        
                        <div class="form-row form-row-3">
                            <?= $form->field($customer, 'passport_series')->textInput(['placeholder' => 'AB']) ?>
                            <?= $form->field($customer, 'passport_number')->textInput(['placeholder' => '1234567']) ?>
                            <?= $form->field($customer, 'passport_issue_date')->input('date') ?>
                        </div>
                        
                        <?= $form->field($customer, 'inn')->textInput(['placeholder' => 'ИНН (только для РФ)']) ?>
                    </div>

                    <div class="form-section">
                        <div class="form-section-title">Уведомления</div>
                        
                        <div class="checkbox-group">
                            <div class="checkbox-item">
                                <?= Html::activeCheckbox($customer, 'subscribe_news', ['label' => false, 'id' => 'subscribe_news']) ?>
                                <label for="subscribe_news">Получать новости о новых поступлениях</label>
                            </div>
                            <div class="checkbox-item">
                                <?= Html::activeCheckbox($customer, 'subscribe_promo', ['label' => false, 'id' => 'subscribe_promo']) ?>
                                <label for="subscribe_promo">Получать информацию об акциях и скидках</label>
                            </div>
                        </div>
                    </div>

                    <div class="profile-form-submit">
                        <?= Html::submitButton('Сохранить изменения', ['class' => 'btn-save btn-save--prominent']) ?>
                    </div>

                    <?php ActiveForm::end(); ?>
                </div>
            </main>
        </div>
    </div>
</div>
<?php
// Inline CSS for full-width profile
$this->registerCss('
.account-page--wide { padding: var(--space-6) 0 var(--space-12); }
.account-page--wide .account-grid { display: grid; grid-template-columns: 260px 1fr; gap: var(--space-8); }
.btn-save--prominent {
    display: block;
    width: 100%;
    padding: 14px 24px;
    background: #000;
    color: #fff;
    font-size: 1rem;
    font-weight: 700;
    border: none;
    border-radius: 10px;
    cursor: pointer;
    letter-spacing: 0.01em;
    transition: background 0.18s, transform 0.12s;
}
.btn-save--prominent:hover { background: #222; transform: translateY(-1px); }
.btn-save--prominent:active { transform: none; }
@media (max-width: 768px) {
    .account-page--wide .account-grid { grid-template-columns: 1fr; }
}
');
?>
