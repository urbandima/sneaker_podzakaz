<?php

/** @var yii\web\View $this */
/** @var app\models\Order $model */

use yii\helpers\Html;
use yii\bootstrap5\ActiveForm;

$this->title = 'Создать заказ';
?>

<div class="admin-create-order">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1><?= Html::encode($this->title) ?></h1>
        <?= Html::a('← Назад к списку', ['/admin/order/index'], ['class' => 'btn btn-outline-secondary']) ?>
    </div>

    <div class="card">
        <div class="card-body">
            <?php $form = ActiveForm::begin(['id' => 'order-form']); ?>

            <h5 class="mb-3">Информация о клиенте</h5>
            <div class="row">
                <div class="col-md-6">
                    <?= $form->field($model, 'client_name')->textInput(['maxlength' => true, 'placeholder' => 'Иванов Иван Иванович']) ?>
                </div>
                <div class="col-md-6">
                    <?= $form->field($model, 'client_phone')->textInput(['maxlength' => true, 'placeholder' => '+375 29 123-45-67']) ?>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <?= $form->field($model, 'client_email')->textInput(['maxlength' => true, 'type' => 'email', 'placeholder' => 'client@example.com']) ?>
                </div>
                <div class="col-md-6">
                    <?= $form->field($model, 'delivery_date')->textInput(['placeholder' => 'Например: 20-25 ноября 2024']) ?>
                </div>
            </div>

            <hr class="my-4">

            <h5 class="mb-3">Товары</h5>
            <div id="order-items">
                <div class="order-item row mb-3">
                    <div class="col-md-5">
                        <label class="form-label">Название товара</label>
                        <input type="text" class="form-control" name="OrderItem[0][product_name]" placeholder="Кроссовки Nike Air Max">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Количество</label>
                        <input type="number" class="form-control" name="OrderItem[0][quantity]" value="1" min="1">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Цена (BYN)</label>
                        <input type="number" step="0.01" class="form-control" name="OrderItem[0][price]" placeholder="300.00">
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <button type="button" class="btn btn-outline-danger remove-item" disabled>Удалить</button>
                    </div>
                </div>
            </div>

            <button type="button" class="btn btn-outline-primary mb-4" id="add-item">+ Добавить товар</button>

            <div class="form-group">
                <?= Html::submitButton('Создать заказ', ['class' => 'btn btn-success btn-lg']) ?>
            </div>

            <?php ActiveForm::end(); ?>
        </div>
    </div>
</div>

<?php
$script = <<<JS
let itemIndex = 1;

$('#add-item').on('click', function() {
    const newItem = `
        <div class="order-item row mb-3">
            <div class="col-md-5">
                <label class="form-label">Название товара</label>
                <input type="text" class="form-control" name="OrderItem[\${itemIndex}][product_name]" placeholder="Кроссовки Nike Air Max">
            </div>
            <div class="col-md-2">
                <label class="form-label">Количество</label>
                <input type="number" class="form-control" name="OrderItem[\${itemIndex}][quantity]" value="1" min="1">
            </div>
            <div class="col-md-3">
                <label class="form-label">Цена (BYN)</label>
                <input type="number" step="0.01" class="form-control" name="OrderItem[\${itemIndex}][price]" placeholder="300.00">
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <button type="button" class="btn btn-outline-danger remove-item">Удалить</button>
            </div>
        </div>
    `;
    
    $('#order-items').append(newItem);
    itemIndex++;
    
    updateRemoveButtons();
});

$(document).on('click', '.remove-item', function() {
    $(this).closest('.order-item').remove();
    updateRemoveButtons();
});

function updateRemoveButtons() {
    const items = $('.order-item');
    if (items.length === 1) {
        items.find('.remove-item').prop('disabled', true);
    } else {
        $('.remove-item').prop('disabled', false);
    }
}
JS;

$this->registerJs($script);
?>
