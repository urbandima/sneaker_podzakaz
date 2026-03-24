<?php

use yii\helpers\Html;

$this->title = 'Сравнение товаров';
?>

<div class="compare-page">
    <div class="container py-5">
        <h1 class="mb-4">
            <i class="bi bi-arrow-left-right"></i> Сравнение товаров
        </h1>

        <?php if (empty($products)): ?>
            <div class="alert alert-info">
                <h4>Список сравнения пуст</h4>
                <p>Добавьте товары для сравнения из каталога</p>
                <a href="/catalog" class="btn btn-primary">
                    <i class="bi bi-grid"></i> Перейти в каталог
                </a>
            </div>
        <?php else: ?>
            <div class="compare-actions mb-4">
                <button class="btn btn-secondary" onclick="clearCompare()">
                    <i class="bi bi-trash"></i> Очистить список
                </button>
                <span class="text-muted ms-3">Товаров в сравнении: <?= count($products) ?></span>
            </div>

            <div class="table-responsive">
                <table class="table table-bordered compare-table">
                    <thead>
                        <tr>
                            <th style="width: 200px;">Характеристика</th>
                            <?php foreach ($products as $product): ?>
                                <th class="text-center">
                                    <div class="product-image mb-2">
                                        <img src="<?= $product->mainImage ?>" alt="<?= Html::encode($product->name) ?>" style="max-width: 150px;">
                                    </div>
                                    <div class="product-name">
                                        <a href="/catalog/product/<?= $product->id ?>">
                                            <?= Html::encode($product->name) ?>
                                        </a>
                                    </div>
                                    <div class="product-price mt-2">
                                        <strong><?= number_format($product->price, 2) ?> BYN</strong>
                                    </div>
                                    <button class="btn btn-sm btn-outline-danger mt-2" onclick="removeFromCompare(<?= $product->id ?>)">
                                        <i class="bi bi-x"></i> Удалить
                                    </button>
                                </th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><strong>Бренд</strong></td>
                            <?php foreach ($products as $product): ?>
                                <td class="text-center"><?= Html::encode($product->brand->name ?? '-') ?></td>
                            <?php endforeach; ?>
                        </tr>
                        <tr>
                            <td><strong>Категория</strong></td>
                            <?php foreach ($products as $product): ?>
                                <td class="text-center"><?= Html::encode($product->category->name ?? '-') ?></td>
                            <?php endforeach; ?>
                        </tr>
                        <tr>
                            <td><strong>Артикул</strong></td>
                            <?php foreach ($products as $product): ?>
                                <td class="text-center"><?= Html::encode($product->sku ?? '-') ?></td>
                            <?php endforeach; ?>
                        </tr>
                        <tr>
                            <td><strong>Наличие</strong></td>
                            <?php foreach ($products as $product): ?>
                                <td class="text-center">
                                    <?php if ($product->stock > 0): ?>
                                        <span class="badge bg-success">В наличии</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger">Нет в наличии</span>
                                    <?php endif; ?>
                                </td>
                            <?php endforeach; ?>
                        </tr>
                        
                        <?php
                        // Собираем все уникальные характеристики
                        $allCharacteristics = [];
                        foreach ($products as $product) {
                            if ($product->characteristics) {
                                foreach ($product->characteristics as $char) {
                                    $allCharacteristics[$char->name] = true;
                                }
                            }
                        }
                        ?>
                        
                        <?php foreach (array_keys($allCharacteristics) as $charName): ?>
                            <tr>
                                <td><strong><?= Html::encode($charName) ?></strong></td>
                                <?php foreach ($products as $product): ?>
                                    <td class="text-center">
                                        <?php
                                        $value = '-';
                                        if ($product->characteristics) {
                                            foreach ($product->characteristics as $char) {
                                                if ($char->name === $charName) {
                                                    $value = $char->value;
                                                    break;
                                                }
                                            }
                                        }
                                        echo Html::encode($value);
                                        ?>
                                    </td>
                                <?php endforeach; ?>
                            </tr>
                        <?php endforeach; ?>
                        
                        <tr>
                            <td><strong>Действия</strong></td>
                            <?php foreach ($products as $product): ?>
                                <td class="text-center">
                                    <a href="/catalog/product/<?= $product->id ?>" class="btn btn-primary btn-sm mb-2">
                                        <i class="bi bi-eye"></i> Подробнее
                                    </a>
                                    <br>
                                    <button class="btn btn-success btn-sm" onclick="addToCart(<?= $product->id ?>)">
                                        <i class="bi bi-cart-plus"></i> В корзину
                                    </button>
                                </td>
                            <?php endforeach; ?>
                        </tr>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
function removeFromCompare(productId) {
    fetch('/compare/remove', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-CSRF-Token': '<?= Yii::$app->request->csrfToken ?>'
        },
        body: 'product_id=' + productId
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            location.reload();
        }
    });
}

function clearCompare() {
    if (!confirm('Очистить весь список сравнения?')) return;
    
    fetch('/compare/clear', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-CSRF-Token': '<?= Yii::$app->request->csrfToken ?>'
        }
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            location.reload();
        }
    });
}

function addToCart(productId) {
    // Реализация добавления в корзину
    console.log('Add to cart:', productId);
}
</script>

<style>
.compare-table th, .compare-table td {
    vertical-align: middle;
}
.product-image img {
    max-height: 150px;
    object-fit: contain;
}
.product-name {
    font-weight: 500;
    min-height: 40px;
}
</style>
