<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

$this->title = $model->isNewRecord ? '➕ Создать тариф' : '✏️ Редактировать тариф';
?>

<div class="tariff-form-page">
    <div class="form-card">
        <h1 class="form-title"><?= Html::encode($this->title) ?></h1>
        
        <?php $form = ActiveForm::begin(); ?>
        
        <div class="form-section">
            <h3 class="section-title">📋 Основная информация</h3>
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
            <h3 class="section-title">💰 Комиссии и сборы</h3>
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
