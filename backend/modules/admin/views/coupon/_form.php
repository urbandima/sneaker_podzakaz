<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use app\backend\modules\coupon\models\Coupon;

?>
<div class="coupon-form">
    <?php $form = ActiveForm::begin([
        'options' => ['class' => 'admin-form'],
        'fieldConfig' => [
            'template' => "{label}\n{input}\n{error}",
            'labelOptions' => ['class' => 'form-label'],
            'inputOptions' => ['class' => 'form-control'],
            'errorOptions' => ['class' => 'form-error']
        ]
    ]); ?>

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 1.5rem;">
        <!-- Основная информация -->
        <div class="form-section">
            <h3 class="form-section-title">Основная информация</h3>
            
            <div class="form-group">
                <?= $form->field($model, 'code')->textInput([
                    'maxlength' => true,
                    'placeholder' => 'SUMMER2024',
                    'style' => 'text-transform: uppercase;'
                ])->hint('Уникальный код купона (будет преобразован в верхний регистр)') ?>
                <button type="button" class="admin-btn admin-btn-secondary" style="margin-top: 0.5rem;" onclick="generateCouponCode()">
                    <i class="bi bi-arrow-repeat"></i> Сгенерировать код
                </button>
            </div>

            <?= $form->field($model, 'name')->textInput(['maxlength' => true, 'placeholder' => 'Летняя распродажа']) ?>

            <?= $form->field($model, 'description')->textarea(['rows' => 3, 'placeholder' => 'Описание купона для внутреннего использования']) ?>

            <?= $form->field($model, 'is_active')->checkbox() ?>
        </div>

        <!-- Скидка -->
        <div class="form-section">
            <h3 class="form-section-title">Скидка</h3>
            
            <?= $form->field($model, 'type')->dropDownList(Coupon::getTypeList(), [
                'prompt' => 'Выберите тип скидки',
                'onchange' => 'updateDiscountFields(this.value)'
            ]) ?>

            <div id="value-field">
                <?= $form->field($model, 'value')->textInput([
                    'type' => 'number',
                    'step' => '0.01',
                    'min' => '0',
                    'placeholder' => '10'
                ])->hint('Для процентной скидки: 10 = 10%, для фиксированной: сумма в BYN') ?>
            </div>

            <?= $form->field($model, 'max_discount')->textInput([
                'type' => 'number',
                'step' => '0.01',
                'min' => '0',
                'placeholder' => '100'
            ])->hint('Максимальная сумма скидки (для процентных купонов)') ?>
        </div>
    </div>

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 1.5rem; margin-top: 1.5rem;">
        <!-- Условия применения -->
        <div class="form-section">
            <h3 class="form-section-title">Условия применения</h3>
            
            <?= $form->field($model, 'min_order_amount')->textInput([
                'type' => 'number',
                'step' => '0.01',
                'min' => '0',
                'placeholder' => '50'
            ])->hint('Минимальная сумма заказа для применения купона') ?>

            <?= $form->field($model, 'max_uses')->textInput([
                'type' => 'number',
                'min' => '0',
                'placeholder' => '100'
            ])->hint('Максимальное количество использований (0 = без ограничений)') ?>

            <?= $form->field($model, 'max_uses_per_user')->textInput([
                'type' => 'number',
                'min' => '0',
                'placeholder' => '1'
            ])->hint('Максимальное количество использований на одного пользователя') ?>

            <?= $form->field($model, 'is_first_order')->checkbox()->hint('Купон действует только для первого заказа пользователя') ?>
        </div>

        <!-- Срок действия -->
        <div class="form-section">
            <h3 class="form-section-title">Срок действия</h3>
            
            <?= $form->field($model, 'valid_from')->input('datetime-local')->hint('Дата начала действия купона') ?>

            <?= $form->field($model, 'valid_until')->input('datetime-local')->hint('Дата окончания действия купона') ?>
        </div>
    </div>

    <!-- Применимость к товарам -->
    <div class="form-section" style="margin-top: 1.5rem;">
        <h3 class="form-section-title">
            <i class="bi bi-tags"></i>
            Применимость к товарам (опционально)
        </h3>
        
        <p class="form-hint">Если не указано, купон применим ко всем товарам</p>
        
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 1rem;">
            <div class="form-group">
                <label class="form-label">ID применимых товаров (через запятую)</label>
                <input type="text" name="applicable_products" class="form-control" placeholder="1,2,3,4,5">
                <small class="form-hint">Купон будет применяться только к указанным товарам</small>
            </div>

            <div class="form-group">
                <label class="form-label">ID применимых категорий (через запятую)</label>
                <input type="text" name="applicable_categories" class="form-control" placeholder="1,2,3">
                <small class="form-hint">Купон будет применяться к товарам из указанных категорий</small>
            </div>
        </div>
    </div>

    <div class="form-actions">
        <?= Html::submitButton($model->isNewRecord ? 'Создать купон' : 'Сохранить изменения', ['class' => 'admin-btn admin-btn-primary']) ?>
        <?= Html::a('Отмена', ['index'], ['class' => 'admin-btn admin-btn-secondary']) ?>
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

.form-hint {
    font-size: 0.75rem;
    color: var(--admin-text-secondary);
    display: block;
    margin-top: 0.25rem;
}

.form-error {
    color: var(--admin-danger);
    font-size: 0.75rem;
    margin-top: 0.25rem;
}

.form-actions {
    display: flex;
    gap: 1rem;
    padding-top: 1.5rem;
    border-top: 1px solid var(--admin-border);
}
</style>

<script>
function generateCouponCode() {
    fetch('<?= \yii\helpers\Url::to(['generate-code']) ?>', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-CSRF-Token': '<?= Yii::$app->request->csrfToken ?>'
        },
        body: 'prefix=&length=8'
    })
    .then(response => response.json())
    .then(data => {
        document.querySelector('#coupon-code').value = data.code;
    });
}

function updateDiscountFields(type) {
    const valueField = document.querySelector('#value-field');
    const valueInput = document.querySelector('#coupon-value');
    
    if (type === '<?= Coupon::TYPE_FREE_SHIPPING ?>') {
        valueField.style.display = 'none';
        valueInput.value = 0;
    } else {
        valueField.style.display = 'block';
    }
}

// Инициализация при загрузке
document.addEventListener('DOMContentLoaded', function() {
    const typeSelect = document.querySelector('#coupon-type');
    if (typeSelect && typeSelect.value) {
        updateDiscountFields(typeSelect.value);
    }
});
</script>
