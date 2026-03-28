<?php

use yii\helpers\Html;
use yii\helpers\Url;

$this->title = 'Сравнение товаров';

// Генерируем URL для AJAX-запросов (поддержка subfolder и pretty URL)
$removeUrl = Url::to(['/compare/compare/remove']);
$clearUrl  = Url::to(['/compare/compare/clear']);
$addCartUrl = Url::to(['/cart/cart/add']);
$csrfToken  = Yii::$app->request->csrfToken;
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
                <a href="<?= Url::to(['/catalog/catalog/index']) ?>" class="btn btn-primary">
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
                                        <img src="<?= Html::encode($product->getMainImageUrl()) ?>"
                                             alt="<?= Html::encode($product->name) ?>"
                                             style="max-width: 150px; max-height: 150px; object-fit: contain;">
                                    </div>
                                    <div class="product-name">
                                        <a href="<?= $product->getUrl() ?>">
                                            <?= Html::encode($product->name) ?>
                                        </a>
                                    </div>
                                    <div class="product-price mt-2">
                                        <strong><?= number_format($product->price, 2) ?> BYN</strong>
                                        <?php if ($product->hasDiscount()): ?>
                                            <span class="text-muted text-decoration-line-through ms-1">
                                                <?= number_format($product->old_price, 2) ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                    <button class="btn btn-sm btn-outline-danger mt-2"
                                            onclick="removeFromCompare(<?= (int)$product->id ?>)">
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
                                <td class="text-center"><?= Html::encode($product->brand->name ?? $product->brand_name ?? '-') ?></td>
                            <?php endforeach; ?>
                        </tr>
                        <tr>
                            <td><strong>Категория</strong></td>
                            <?php foreach ($products as $product): ?>
                                <td class="text-center"><?= Html::encode($product->category->name ?? $product->category_name ?? '-') ?></td>
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
                                    <?php if ($product->isInStock()): ?>
                                        <span class="badge bg-success">В наличии</span>
                                    <?php elseif ($product->stock_status === 'preorder'): ?>
                                        <span class="badge bg-warning text-dark">Под заказ</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger">Нет в наличии</span>
                                    <?php endif; ?>
                                </td>
                            <?php endforeach; ?>
                        </tr>
                        <tr>
                            <td><strong>Рейтинг</strong></td>
                            <?php foreach ($products as $product): ?>
                                <td class="text-center">
                                    <?php if ($product->rating > 0): ?>
                                        <span class="text-warning">★</span> <?= number_format($product->rating, 1) ?>
                                        <small class="text-muted">(<?= (int)$product->reviews_count ?>)</small>
                                    <?php else: ?>
                                        <span class="text-muted">—</span>
                                    <?php endif; ?>
                                </td>
                            <?php endforeach; ?>
                        </tr>

                        <?php
                        // Собираем все уникальные характеристики через characteristicValues
                        $allCharacteristics = [];
                        foreach ($products as $product) {
                            if (!empty($product->characteristicValues)) {
                                foreach ($product->characteristicValues as $charVal) {
                                    $charName = $charVal->characteristic->name ?? ($charVal->name ?? null);
                                    if ($charName) {
                                        $allCharacteristics[$charName] = true;
                                    }
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
                                        $value = '—';
                                        if (!empty($product->characteristicValues)) {
                                            foreach ($product->characteristicValues as $charVal) {
                                                $cn = $charVal->characteristic->name ?? ($charVal->name ?? null);
                                                if ($cn === $charName) {
                                                    $value = $charVal->value ?? '—';
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
                                    <a href="<?= $product->getUrl() ?>" class="btn btn-primary btn-sm mb-2">
                                        <i class="bi bi-eye"></i> Подробнее
                                    </a>
                                    <br>
                                    <?php if ($product->isInStock()): ?>
                                        <button class="btn btn-success btn-sm"
                                                onclick="compareAddToCart(<?= (int)$product->id ?>, this)">
                                            <i class="bi bi-cart-plus"></i> В корзину
                                        </button>
                                    <?php else: ?>
                                        <button class="btn btn-secondary btn-sm" disabled>
                                            <i class="bi bi-cart-x"></i> Нет в наличии
                                        </button>
                                    <?php endif; ?>
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
var compareRemoveUrl = <?= json_encode($removeUrl) ?>;
var compareClearUrl  = <?= json_encode($clearUrl) ?>;
var compareCartUrl   = <?= json_encode($addCartUrl) ?>;
var csrfToken        = <?= json_encode($csrfToken) ?>;

function removeFromCompare(productId) {
    fetch(compareRemoveUrl, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-CSRF-Token': csrfToken
        },
        body: 'product_id=' + encodeURIComponent(productId)
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
        if (data.success) {
            location.reload();
        } else {
            alert(data.message || 'Ошибка удаления');
        }
    })
    .catch(function() {
        alert('Ошибка соединения с сервером');
    });
}

function clearCompare() {
    if (!confirm('Очистить весь список сравнения?')) return;

    fetch(compareClearUrl, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-CSRF-Token': csrfToken
        }
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
        if (data.success) {
            location.reload();
        } else {
            alert(data.message || 'Ошибка очистки');
        }
    })
    .catch(function() {
        alert('Ошибка соединения с сервером');
    });
}

function compareAddToCart(productId, btn) {
    var originalText = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<i class="bi bi-hourglass-split"></i> Добавляем...';

    fetch(compareCartUrl, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-CSRF-Token': csrfToken
        },
        body: 'product_id=' + encodeURIComponent(productId) + '&quantity=1'
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
        if (data.success) {
            btn.innerHTML = '<i class="bi bi-check-lg"></i> Добавлено!';
            btn.classList.remove('btn-success');
            btn.classList.add('btn-outline-success');

            // Обновляем счётчик корзины в шапке
            var cartCounter = document.querySelector('.cart-counter');
            if (cartCounter && data.count) {
                cartCounter.textContent = data.count;
                cartCounter.style.display = 'inline-flex';
            }

            setTimeout(function() {
                btn.disabled = false;
                btn.innerHTML = originalText;
                btn.classList.remove('btn-outline-success');
                btn.classList.add('btn-success');
            }, 2000);
        } else {
            btn.disabled = false;
            btn.innerHTML = originalText;
            alert(data.message || 'Не удалось добавить в корзину');
        }
    })
    .catch(function() {
        btn.disabled = false;
        btn.innerHTML = originalText;
        alert('Ошибка соединения с сервером');
    });
}
</script>

<style>
.compare-table th,
.compare-table td {
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
.compare-table tbody tr:hover {
    background-color: #f8f9fa;
}
</style>
