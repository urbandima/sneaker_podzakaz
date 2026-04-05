<?php
/**
 * Массовое изменение цен товаров
 *
 * @var yii\web\View $this
 * @var app\backend\modules\catalog\models\Product[] $products
 * @var app\backend\modules\catalog\models\Brand[] $brands
 * @var app\backend\modules\catalog\models\Category[] $categories
 */

use yii\helpers\Html;
use yii\helpers\Url;

$this->title = 'Массовое изменение цен';
$this->params['breadcrumbs'][] = ['label' => 'Товары', 'url' => ['/admin/product/index']];
$this->params['breadcrumbs'][] = $this->title;

$currentBrand    = Yii::$app->request->get('brand', '');
$currentCategory = Yii::$app->request->get('category', '');
?>

<div class="admin-header">
    <h1 class="admin-header-title"><?= Html::encode($this->title) ?></h1>
    <a href="<?= Url::to(['/admin/product/index']) ?>" class="admin-btn admin-btn-secondary">
        <i class="bi bi-arrow-left"></i> К списку товаров
    </a>
</div>

<!-- Фильтры -->
<div class="admin-card" style="margin-bottom:1.5rem;">
    <h2 class="admin-card-title"><i class="bi bi-funnel"></i> Фильтр товаров</h2>
    <form method="get" style="display:flex;gap:1rem;flex-wrap:wrap;margin-top:1rem;align-items:flex-end;">
        <div class="form-group" style="margin:0;min-width:200px;">
            <label>Бренд</label>
            <select name="brand" class="form-control">
                <option value="">Все бренды</option>
                <?php foreach ($brands as $brand): ?>
                <option value="<?= $brand->id ?>" <?= $currentBrand == $brand->id ? 'selected' : '' ?>>
                    <?= Html::encode($brand->name) ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group" style="margin:0;min-width:200px;">
            <label>Категория</label>
            <select name="category" class="form-control">
                <option value="">Все категории</option>
                <?php foreach ($categories as $cat): ?>
                <option value="<?= $cat->id ?>" <?= $currentCategory == $cat->id ? 'selected' : '' ?>>
                    <?= Html::encode($cat->name) ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div style="padding-bottom:1px;">
            <button type="submit" class="admin-btn admin-btn-primary">
                <i class="bi bi-filter"></i> Применить
            </button>
            <a href="<?= Url::to(['/admin/product/bulk-price']) ?>" class="admin-btn admin-btn-secondary" style="margin-left:0.5rem;">
                Сбросить
            </a>
        </div>
    </form>
</div>

<!-- Управление наценкой -->
<div class="admin-card" style="margin-bottom:1.5rem;">
    <h2 class="admin-card-title"><i class="bi bi-percent"></i> Массовая наценка</h2>
    <div style="display:flex;gap:1rem;align-items:flex-end;flex-wrap:wrap;margin-top:1rem;">
        <div class="form-group" style="margin:0;min-width:160px;">
            <label>Наценка %</label>
            <input type="number" id="global-markup" class="form-control" min="-100" max="10000" step="0.1" placeholder="например, 30">
        </div>
        <div style="padding-bottom:1px;">
            <button class="admin-btn admin-btn-primary" onclick="applyGlobalMarkup()">
                <i class="bi bi-calculator"></i> Рассчитать
            </button>
            <button class="admin-btn admin-btn-success" id="apply-btn" onclick="applyPrices()" style="margin-left:0.5rem;" disabled>
                <i class="bi bi-check-circle"></i> Применить цены
            </button>
        </div>
    </div>
    <div id="apply-result" style="display:none;margin-top:0.75rem;padding:0.5rem 0.75rem;border-radius:0.5rem;font-size:0.875rem;"></div>
</div>

<!-- Таблица товаров -->
<div class="admin-card">
    <h2 class="admin-card-title">
        <i class="bi bi-list-ul"></i>
        Товары (<?= count($products) ?>)
    </h2>
    <div style="margin-top:1rem;overflow-x:auto;">
        <?php if (empty($products)): ?>
        <p style="text-align:center;color:var(--admin-text-secondary);padding:2rem;">Товары не найдены</p>
        <?php else: ?>
        <table class="admin-table" id="bulk-price-table">
            <thead>
                <tr>
                    <th style="width:40px;"><input type="checkbox" id="select-all" onchange="toggleSelectAll(this)"></th>
                    <th>Товар</th>
                    <th>Бренд</th>
                    <th style="text-align:right;min-width:110px;">Текущая цена, BYN</th>
                    <th style="min-width:110px;">Наценка, %</th>
                    <th style="text-align:right;min-width:120px;">Новая цена, BYN</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($products as $product): ?>
                <tr data-id="<?= $product->id ?>" data-base-price="<?= (float)($product->price ?? 0) ?>">
                    <td><input type="checkbox" class="row-check" value="<?= $product->id ?>"></td>
                    <td>
                        <strong><?= Html::encode($product->name) ?></strong>
                        <?php if (!empty($product->sku)): ?>
                        <br><small style="color:var(--admin-text-secondary);"><?= Html::encode($product->sku) ?></small>
                        <?php endif; ?>
                    </td>
                    <td><?= Html::encode($product->brand->name ?? '—') ?></td>
                    <td style="text-align:right;font-weight:600;">
                        <?= number_format((float)($product->price ?? 0), 2, ',', ' ') ?>
                    </td>
                    <td>
                        <input type="number" class="form-control markup-input" min="-100" max="10000" step="0.1"
                               placeholder="%" style="width:90px;padding:0.4rem 0.6rem;"
                               onchange="recalcRow(this)" oninput="recalcRow(this)">
                    </td>
                    <td style="text-align:right;">
                        <span class="new-price" style="font-weight:700;color:var(--admin-primary,#2563eb);">—</span>
                        <input type="hidden" class="new-price-val" value="">
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
</div>

<style>
.form-control { width:100%;padding:.75rem 1rem;border:1px solid var(--admin-border,#e2e8f0);border-radius:.5rem;background:var(--admin-bg,#fff);color:var(--admin-text-primary,#1e293b);font-size:1rem; }
.form-group label { display:block;margin-bottom:.5rem;font-weight:600;color:var(--admin-text-secondary,#64748b);font-size:.875rem; }
.form-group { margin-bottom:1rem; }
.admin-btn-success { background:#10b981;color:#fff;border-color:#10b981; }
.admin-btn-success:hover { background:#059669; }
.admin-btn-success:disabled { opacity:.5;cursor:not-allowed; }
</style>

<script>
var csrfToken = document.querySelector('meta[name="csrf-token"]') ? document.querySelector('meta[name="csrf-token"]').content : '';
var bulkUpdateUrl = '<?= Url::to(['/admin/product/bulk-update-price']) ?>';

function recalcRow(input) {
    var tr        = input.closest('tr');
    var base      = parseFloat(tr.dataset.basePrice) || 0;
    var markup    = parseFloat(input.value);
    var newPriceEl = tr.querySelector('.new-price');
    var hiddenEl   = tr.querySelector('.new-price-val');

    if (!isNaN(markup)) {
        var newPrice = base * (1 + markup / 100);
        newPrice = Math.round(newPrice * 100) / 100;
        newPriceEl.textContent = newPrice.toFixed(2).replace('.', ',');
        hiddenEl.value = newPrice;
    } else {
        newPriceEl.textContent = '—';
        hiddenEl.value = '';
    }
    updateApplyBtn();
}

function applyGlobalMarkup() {
    var markup = parseFloat(document.getElementById('global-markup').value);
    if (isNaN(markup)) { alert('Введите значение наценки'); return; }
    document.querySelectorAll('#bulk-price-table tbody tr').forEach(function(tr) {
        var markupInput = tr.querySelector('.markup-input');
        if (markupInput) {
            markupInput.value = markup;
            recalcRow(markupInput);
        }
    });
}

function toggleSelectAll(cb) {
    document.querySelectorAll('.row-check').forEach(function(c) { c.checked = cb.checked; });
}

function updateApplyBtn() {
    var hasNew = false;
    document.querySelectorAll('.new-price-val').forEach(function(el) {
        if (el.value !== '') hasNew = true;
    });
    document.getElementById('apply-btn').disabled = !hasNew;
}

function applyPrices() {
    var prices = [];
    document.querySelectorAll('#bulk-price-table tbody tr').forEach(function(tr) {
        var hiddenEl = tr.querySelector('.new-price-val');
        var check    = tr.querySelector('.row-check');
        // apply only rows with new price (and checked if any are checked)
        var anyChecked = document.querySelectorAll('.row-check:checked').length > 0;
        if (hiddenEl && hiddenEl.value !== '') {
            if (!anyChecked || (check && check.checked)) {
                prices.push({ id: parseInt(tr.dataset.id), price: parseFloat(hiddenEl.value) });
            }
        }
    });

    if (prices.length === 0) { alert('Нет товаров для обновления'); return; }

    var btn = document.getElementById('apply-btn');
    var resultEl = document.getElementById('apply-result');
    btn.disabled = true;
    btn.innerHTML = '<i class="bi bi-hourglass-split"></i> Применение...';

    fetch(bulkUpdateUrl, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-Token': csrfToken,
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify({ prices: prices })
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-check-circle"></i> Применить цены';
        if (resultEl) {
            resultEl.style.display = 'block';
            resultEl.style.background = data.success ? '#d1fae5' : '#fee2e2';
            resultEl.style.color = data.success ? '#065f46' : '#991b1b';
            resultEl.textContent = data.success
                ? 'Обновлено товаров: ' + data.updated
                : (data.error || 'Ошибка обновления');
        }
        if (data.success) {
            // Update base prices in the DOM
            document.querySelectorAll('#bulk-price-table tbody tr').forEach(function(tr) {
                var hiddenEl = tr.querySelector('.new-price-val');
                if (hiddenEl && hiddenEl.value !== '') {
                    tr.dataset.basePrice = hiddenEl.value;
                    var currentPriceCell = tr.querySelector('td:nth-child(4)');
                    if (currentPriceCell) {
                        currentPriceCell.textContent = parseFloat(hiddenEl.value).toFixed(2).replace('.', ',');
                    }
                }
            });
        }
    })
    .catch(function() {
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-check-circle"></i> Применить цены';
        if (resultEl) {
            resultEl.style.display = 'block';
            resultEl.style.background = '#fee2e2';
            resultEl.style.color = '#991b1b';
            resultEl.textContent = 'Ошибка соединения';
        }
    });
}
</script>
