<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

$this->title = $model->isNewRecord ? '➕ Создать тариф' : '✏️ Редактировать тариф';
?>

<style>
.tariff-form-page {
    padding: 1.5rem;
    background: #f8fafc;
    min-height: 100vh;
}

.form-card {
    background: white;
    border: 1px solid #e5e7eb;
    border-radius: 12px;
    padding: 2rem;
    max-width: 800px;
}

.form-title {
    font-size: 1.5rem;
    font-weight: 700;
    color: #1f2937;
    margin-bottom: 1.5rem;
    padding-bottom: 1rem;
    border-bottom: 1px solid #e5e7eb;
}

.form-section {
    margin-bottom: 1.5rem;
}

.section-title {
    font-size: 1rem;
    font-weight: 600;
    color: #374151;
    margin-bottom: 1rem;
}

.form-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 1rem;
}

.form-group {
    margin-bottom: 1rem;
}

.form-group label {
    display: block;
    font-size: 0.875rem;
    font-weight: 500;
    color: #374151;
    margin-bottom: 0.25rem;
}

.form-group input,
.form-group textarea,
.form-group select {
    width: 100%;
    padding: 0.5rem 0.75rem;
    border: 1px solid #e5e7eb;
    border-radius: 6px;
    font-size: 1rem;
}

.form-group input:focus,
.form-group textarea:focus,
.form-group select:focus {
    outline: none;
    border-color: #3b82f6;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.form-actions {
    display: flex;
    gap: 1rem;
    padding-top: 1.5rem;
    border-top: 1px solid #e5e7eb;
}

.btn {
    padding: 0.75rem 1.5rem;
    border-radius: 8px;
    font-weight: 600;
    text-decoration: none;
    transition: all 0.2s;
}

.btn-primary {
    background: #3b82f6;
    color: white;
    border: none;
}

.btn-primary:hover {
    background: #1d4ed8;
}

.btn-secondary {
    background: white;
    color: #374151;
    border: 1px solid #e5e7eb;
}

.btn-secondary:hover {
    background: #f3f4f6;
}

.help-text {
    font-size: 0.75rem;
    color: #6b7280;
    margin-top: 0.25rem;
}
</style>

<div class="tariff-form-page">
    <div class="form-card">
        <h1 class="form-title"><?= Html::encode($this->title) ?></h1>
        
        <?php $form = ActiveForm::begin(); ?>
        
        <div class="form-section">
            <h3 class="section-title"><i class="bi bi-clipboard"></i> Основная информация</h3>
            <div class="form-grid">
                <div class="form-group">
                    <?= $form->field($model, 'name')->textInput(['maxlength' => true, 'placeholder' => 'Название тарифа']) ?>
                </div>
                <div class="form-group">
                    <?= $form->field($model, 'sort_order')->textInput(['type' => 'number', 'placeholder' => '0']) ?>
                </div>
            </div>
            <div class="form-group">
                <?= $form->field($model, 'description')->textarea(['rows' => 3, 'placeholder' => 'Описание тарифа...']) ?>
            </div>
            <div class="form-group">
                <?= $form->field($model, 'is_active')->checkbox() ?>
            </div>
        </div>
        
        <div class="form-section">
            <h3 class="section-title"><i class="bi bi-currency-dollar"></i> Комиссии и сборы</h3>
            <div class="form-grid">
                <div class="form-group">
                    <?= $form->field($model, 'commission_percent')->textInput(['type' => 'number', 'step' => '0.01', 'placeholder' => '10.00']) ?>
                    <div class="help-text">Процент комиссии от стоимости товара</div>
                </div>
                <div class="form-group">
                    <?= $form->field($model, 'commission_fixed')->textInput(['type' => 'number', 'step' => '0.01', 'placeholder' => '0.00']) ?>
                    <div class="help-text">Фиксированная комиссия в юанях</div>
                </div>
                <div class="form-group">
                    <?= $form->field($model, 'insurance_percent')->textInput(['type' => 'number', 'step' => '0.01', 'placeholder' => '2.00']) ?>
                    <div class="help-text">Процент страховки от стоимости</div>
                </div>
                <div class="form-group">
                    <?= $form->field($model, 'min_order_amount')->textInput(['type' => 'number', 'step' => '0.01', 'placeholder' => '0.00']) ?>
                    <div class="help-text">Минимальная сумма заказа в юанях</div>
                </div>
            </div>
        </div>
        
        <div class="form-section">
            <h3 class="section-title"><i class="bi bi-truck"></i> Доставка</h3>
            <div class="form-grid">
                <div class="form-group">
                    <?= $form->field($model, 'delivery_cost_per_kg')->textInput(['type' => 'number', 'step' => '0.01', 'placeholder' => '15.00']) ?>
                    <div class="help-text">Стоимость доставки за 1 кг в юанях</div>
                </div>
            </div>
        </div>
        
        <div class="form-section">
            <h3 class="section-title">💱 Валюта и курс</h3>
            <div class="form-grid">
                <div class="form-group">
                    <?= $form->field($model, 'currency')->dropDownList([
                        'CNY' => 'CNY (Китайский юань)',
                        'USD' => 'USD (Доллар США)',
                        'EUR' => 'EUR (Евро)',
                    ]) ?>
                </div>
                <div class="form-group">
                    <?= $form->field($model, 'exchange_rate')->textInput(['type' => 'number', 'step' => '0.0001', 'placeholder' => '12.50']) ?>
                    <div class="help-text">Курс к BYN</div>
                </div>
            </div>
        </div>
        
        <div class="form-actions">
            <?= Html::submitButton('💾 Сохранить', ['class' => 'btn btn-primary']) ?>
            <?= Html::a('← Отмена', ['index'], ['class' => 'btn btn-secondary']) ?>
        </div>
        
        <?php ActiveForm::end(); ?>
    </div>
</div>
