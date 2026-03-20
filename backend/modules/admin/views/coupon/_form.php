<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use app\backend\modules\coupon\models\Coupon;

?>

<div class="coupon-form">
    <?php $form = ActiveForm::begin(); ?>

    <div class="row">
        <div class="col-md-6">
            <div class="card mb-3">
                <div class="card-header">
                    <h5 class="mb-0">Основная информация</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <?= $form->field($model, 'code')->textInput([
                            'maxlength' => true,
                            'placeholder' => 'SUMMER2024',
                            'style' => 'text-transform: uppercase;'
                        ])->hint('Уникальный код купона (будет преобразован в верхний регистр)') ?>
                        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="generateCouponCode()">
                            <i class="bi bi-arrow-repeat"></i> Сгенерировать код
                        </button>
                    </div>

                    <?= $form->field($model, 'name')->textInput(['maxlength' => true, 'placeholder' => 'Летняя распродажа']) ?>

                    <?= $form->field($model, 'description')->textarea(['rows' => 3, 'placeholder' => 'Описание купона для внутреннего использования']) ?>

                    <?= $form->field($model, 'is_active')->checkbox() ?>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card mb-3">
                <div class="card-header">
                    <h5 class="mb-0">Скидка</h5>
                </div>
                <div class="card-body">
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
        </div>
    </div>

    <div class="row">
        <div class="col-md-6">
            <div class="card mb-3">
                <div class="card-header">
                    <h5 class="mb-0">Условия применения</h5>
                </div>
                <div class="card-body">
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
            </div>
        </div>

        <div class="col-md-6">
            <div class="card mb-3">
                <div class="card-header">
                    <h5 class="mb-0">Срок действия</h5>
                </div>
                <div class="card-body">
                    <?= $form->field($model, 'valid_from')->input('datetime-local')->hint('Дата начала действия купона') ?>

                    <?= $form->field($model, 'valid_until')->input('datetime-local')->hint('Дата окончания действия купона') ?>
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-header">
            <h5 class="mb-0">Применимость к товарам (опционально)</h5>
        </div>
        <div class="card-body">
            <p class="text-muted">Если не указано, купон применим ко всем товарам</p>
            
            <div class="mb-3">
                <label class="form-label">ID применимых товаров (через запятую)</label>
                <input type="text" name="applicable_products" class="form-control" placeholder="1,2,3,4,5">
                <small class="form-text text-muted">Купон будет применяться только к указанным товарам</small>
            </div>

            <div class="mb-3">
                <label class="form-label">ID применимых категорий (через запятую)</label>
                <input type="text" name="applicable_categories" class="form-control" placeholder="1,2,3">
                <small class="form-text text-muted">Купон будет применяться к товарам из указанных категорий</small>
            </div>
        </div>
    </div>

    <div class="form-group">
        <?= Html::submitButton($model->isNewRecord ? 'Создать купон' : 'Сохранить изменения', ['class' => 'btn btn-success btn-lg']) ?>
        <?= Html::a('Отмена', ['index'], ['class' => 'btn btn-secondary btn-lg']) ?>
    </div>

    <?php ActiveForm::end(); ?>
</div>

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
