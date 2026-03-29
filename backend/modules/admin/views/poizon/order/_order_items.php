<?php

use yii\helpers\Html;

/** @var yii\web\View $this */
/** @var app\models\OrderItem[] $orderItems */
/** @var int $itemIndexStart */

$orderItems = $orderItems ?? [];
$itemIndexStart = $itemIndexStart ?? (count($orderItems) > 0 ? count($orderItems) : 1);

?>

<h5 class="mb-3">Товары</h5>
<div id="order-items">
    <?php if (!empty($orderItems)): ?>
        <?php foreach ($orderItems as $index => $item): ?>
            <div class="order-item row mb-3">
                <div class="col-md-5">
                    <label class="form-label">Название товара</label>
                    <input type="text" class="form-control" name="OrderItem[<?= (int)$index ?>][product_name]" value="<?= Html::encode($item->product_name) ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Количество</label>
                    <input type="number" class="form-control" name="OrderItem[<?= (int)$index ?>][quantity]" value="<?= (int)$item->quantity ?>" min="1">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Цена (BYN)</label>
                    <input type="number" step="0.01" class="form-control" name="OrderItem[<?= (int)$index ?>][price]" value="<?= Html::encode($item->price) ?>">
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button type="button" class="btn btn-outline-danger remove-item">Удалить</button>
                </div>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
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
    <?php endif; ?>
</div>

<button type="button" class="btn btn-outline-primary mb-4" id="add-item">+ Добавить товар</button>

<?php
$script = <<<JS
let itemIndex = {$itemIndexStart};

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

updateRemoveButtons();
JS;

$this->registerJs($script);
?>
