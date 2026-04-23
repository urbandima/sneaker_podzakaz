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

<?php
$this->params['headerActions'] = [
    Html::a('<i class="bi bi-arrow-left"></i> К списку товаров', ['/admin/product/index'], ['class' => 'admin-btn admin-btn-secondary admin-btn-sm'])
];
?>

<!-- Фильтры -->
<div class="admin-card mb-5">
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
<div class="admin-card mb-5">
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
                        <br><small class="text-muted"><?= Html::encode($product->sku) ?></small>
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
                    <td class="text-right">
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

<span id="js-bulk-update-price-url" data-url="<?= Url::to(['/admin/product/bulk-update-price']) ?>" class="d-none"></span>
