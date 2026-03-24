<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/**
 * @var yii\web\View $this
 * @var app\backend\modules\account\models\Customer $customer
 */

$this->title = 'Редактирование покупателя #' . $customer->id;
?>

<div class="admin-header">
    <h1 class="admin-header-title"><?= Html::encode($this->title) ?></h1>
    <div class="admin-header-actions">
        <a href="<?= \yii\helpers\Url::to(['view', 'id' => $customer->id]) ?>" class="admin-btn admin-btn-secondary">
            <i class="bi bi-eye"></i>
            Просмотр
        </a>
    </div>
</div>

<div class="admin-card">
    <h2 class="admin-card-title">
        <i class="bi bi-person-badge"></i>
        Редактирование данных покупателя
    </h2>
    
    <?php $form = ActiveForm::begin([
        'options' => ['class' => 'admin-form'],
        'fieldConfig' => [
            'template' => "{label}\n{input}\n{error}",
            'labelOptions' => ['class' => 'form-label'],
            'inputOptions' => ['class' => 'form-control'],
            'errorOptions' => ['class' => 'form-error']
        ]
    ]); ?>

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(400px, 1fr)); gap: 1.5rem;">
        <!-- Персональные данные -->
        <div class="form-section">
            <h3 class="form-section-title">
                <i class="bi bi-person-badge"></i>
                Персональные данные
            </h3>
            
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
                <?= $form->field($customer, 'first_name')->textInput(['maxlength' => true]) ?>
                <?= $form->field($customer, 'last_name')->textInput(['maxlength' => true]) ?>
                <?= $form->field($customer, 'middle_name')->textInput(['maxlength' => true]) ?>
                <?= $form->field($customer, 'birth_date')->input('date') ?>
                <?= $form->field($customer, 'gender')->dropDownList([
                    '' => 'Не выбран',
                    'male' => 'Мужской',
                    'female' => 'Женский',
                ]) ?>
            </div>
        </div>

        <!-- Контакты -->
        <div class="form-section">
            <h3 class="form-section-title">
                <i class="bi bi-envelope"></i>
                Контакты
            </h3>
            
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
                <?= $form->field($customer, 'email')->textInput(['maxlength' => true, 'disabled' => true]) ?>
                <?= $form->field($customer, 'phone')->textInput(['maxlength' => true]) ?>
            </div>
            
            <div class="form-hint" style="margin-top: 0.5rem;">
                Email нельзя изменять из админки
            </div>
        </div>
    </div>

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(400px, 1fr)); gap: 1.5rem; margin-top: 1.5rem;">
        <!-- Адрес по умолчанию -->
        <div class="form-section">
            <h3 class="form-section-title">
                <i class="bi bi-geo-alt"></i>
                Адрес по умолчанию
            </h3>
            
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
                <?= $form->field($customer, 'default_country')->textInput(['maxlength' => true]) ?>
                <?= $form->field($customer, 'default_city')->textInput(['maxlength' => true]) ?>
                <?= $form->field($customer, 'default_postal_code')->textInput(['maxlength' => true]) ?>
            </div>
            
            <?= $form->field($customer, 'default_address')->textarea(['rows' => 2]) ?>
        </div>

        <!-- Документы -->
        <div class="form-section">
            <h3 class="form-section-title">
                <i class="bi bi-card-text"></i>
                Документы
            </h3>
            
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
                <?= $form->field($customer, 'passport_series')->textInput(['maxlength' => true]) ?>
                <?= $form->field($customer, 'passport_number')->textInput(['maxlength' => true]) ?>
                <?= $form->field($customer, 'passport_issue_date')->input('date') ?>
                <?= $form->field($customer, 'inn')->textInput(['maxlength' => true]) ?>
            </div>
        </div>
    </div>

    <!-- Коммуникации -->
    <div class="form-section" style="margin-top: 1.5rem;">
        <h3 class="form-section-title">
            <i class="bi bi-bell"></i>
            Коммуникации
        </h3>
        
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1rem;">
            <div class="form-group">
                <label style="display: flex; align-items: center; gap: 0.5rem;">
                    <?= Html::activeCheckbox($customer, 'subscribe_news', ['label' => false]) ?>
                    <span>Подписка на новости</span>
                </label>
            </div>
            <div class="form-group">
                <label style="display: flex; align-items: center; gap: 0.5rem;">
                    <?= Html::activeCheckbox($customer, 'subscribe_promo', ['label' => false]) ?>
                    <span>Подписка на акции и промокоды</span>
                </label>
            </div>
        </div>
    </div>

    <!-- Кнопки действий -->
    <div class="form-actions" style="margin-top: 2rem;">
        <?= Html::submitButton('<i class="bi bi-save"></i> Сохранить изменения', ['class' => 'admin-btn admin-btn-primary']) ?>
        <?= Html::a('Отмена', ['view', 'id' => $customer->id], ['class' => 'admin-btn admin-btn-secondary']) ?>
    </div>

    <?php ActiveForm::end(); ?>
</div>

<style>
.admin-form {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

.form-section {
    background: var(--admin-bg);
    border: 1px solid var(--admin-border);
    border-radius: 0.75rem;
    padding: 1.5rem;
}

.form-section-title {
    margin: 0 0 1rem 0;
    font-size: 1.125rem;
    font-weight: 600;
    color: var(--admin-text-primary);
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.form-group {
    margin-bottom: 1rem;
}

.form-label {
    display: block;
    margin-bottom: 0.5rem;
    font-weight: 600;
    color: var(--admin-text-primary);
    font-size: 0.875rem;
}

.form-control {
    width: 100%;
    padding: 0.75rem 1rem;
    border: 1px solid var(--admin-border);
    border-radius: 0.5rem;
    background: var(--admin-bg);
    color: var(--admin-text-primary);
    font-size: 1rem;
    transition: border-color 0.2s, box-shadow 0.2s;
}

.form-control:focus {
    outline: none;
    border-color: var(--admin-primary);
    box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
}

.form-control:disabled {
    background: var(--admin-border);
    color: var(--admin-text-secondary);
}

.form-error {
    color: var(--admin-danger);
    font-size: 0.75rem;
    margin-top: 0.25rem;
}

.form-hint {
    font-size: 0.75rem;
    color: var(--admin-text-secondary);
    display: block;
}

.form-actions {
    display: flex;
    gap: 1rem;
    padding-top: 1.5rem;
    border-top: 1px solid var(--admin-border);
}
</style>
