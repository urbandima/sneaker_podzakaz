<?php

use yii\helpers\Html;
use yii\helpers\Url;
use app\backend\shared\helpers\PriceHelper;
use app\backend\shared\helpers\AttributeMapper;
use app\backend\shared\helpers\OrderStatusHelper;

/** @var yii\web\View $this */
/** @var app\backend\modules\catalog\models\Product $product */

$this->title = $product->name;

$properties = [];
if ($product->properties) {
    $properties = is_array($product->properties) ? $product->properties : (json_decode($product->properties, true) ?: []);
}
$keywords = [];
if ($product->keywords) {
    $keywords = is_array($product->keywords) ? $product->keywords : (json_decode($product->keywords, true) ?: []);
}
$allKeywords = array_unique(array_filter(array_merge($keywords, $product->meta_keywords ? array_map('trim', explode(',', $product->meta_keywords)) : [])));

$orderStatusMap = [
    'created' => 'Создан', 'pending' => 'Ожидает', 'confirmed' => 'Подтверждён',
    'paid' => 'Оплачен', 'confirmed_and_paid' => 'Подтверждён и оплачен',
    'processing' => 'В обработке', 'shipped' => 'Отправлен', 'delivered' => 'Доставлен',
    'new' => 'Новый', 'cancelled' => 'Отменён', 'returned' => 'Возврат', 'refunded' => 'Возвращён',
];

// Status
$statusColor = $product->is_active ? '#059669' : '#6b7280';
$statusBg    = $product->is_active ? '#d1fae5' : '#f3f4f6';
$statusLabel = $product->is_active ? 'В продаже' : 'Неактивен';

// Sizes
$sizes = $product->getSizes()
    ->orderBy(['sort_order' => SORT_ASC])
    ->addOrderBy(new \yii\db\Expression('CAST(`us_size` AS DECIMAL) ASC, `us_size` ASC'))
    ->all();

// Characteristics
try {
    $characteristicsFromRegistry = \app\backend\modules\catalog\models\ProductCharacteristicValue::find()
        ->where(['product_id' => $product->id])
        ->with(['characteristic', 'characteristicValue'])
        ->all();
} catch (\Exception $e) { $characteristicsFromRegistry = []; }

$hasPoizonChars = !empty($properties);

$_msCharRows = [];
if ($product->brand_name)        $_msCharRows[] = ['label' => 'Бренд',          'value' => $product->brand_name,        'field' => 'brand_name'];
if ($product->model_name)        $_msCharRows[] = ['label' => 'Модель',          'value' => $product->model_name,        'field' => 'model_name'];
if ($product->gender)            $_msCharRows[] = ['label' => 'Пол',             'value' => $product->gender,            'field' => null];
if ($product->season)            $_msCharRows[] = ['label' => 'Сезон',           'value' => $product->season,            'field' => null];
if ($product->upper_material)    $_msCharRows[] = ['label' => 'Материал верха',  'value' => $product->upper_material,    'field' => null];
if ($product->color_description) $_msCharRows[] = ['label' => 'Цвет',           'value' => $product->color_description, 'field' => null];
if ($product->ms_size_grid)      $_msCharRows[] = ['label' => 'Размерная сетка', 'value' => $product->ms_size_grid,      'field' => 'ms_size_grid'];

$_msAttrs = $product->ms_attributes_json
    ? (is_array($product->ms_attributes_json) ? $product->ms_attributes_json : json_decode($product->ms_attributes_json, true))
    : [];

// MS Images
$msImagesJson     = $product->ms_images_json ?? null;
$msImagesData     = $msImagesJson ? (is_array($msImagesJson) ? $msImagesJson : json_decode($msImagesJson, true)) : null;
$hasCollectionUrl = !empty($msImagesData['__collection__']);

// Orders with this product
try {
    $productOrders = \app\backend\modules\checkout\models\OrderItem::find()
        ->where(['product_id' => $product->id])
        ->with(['order'])
        ->orderBy(['id' => SORT_DESC])
        ->limit(20)
        ->all();
} catch (\Exception $e) { $productOrders = []; }

// Similar products
try {
    $similarProducts = \app\backend\modules\catalog\models\Product::find()
        ->where(['brand_id' => $product->brand_id, 'is_active' => true])
        ->andWhere(['!=', 'id', $product->id])
        ->andWhere(['>', 'price', 0])
        ->limit(8)
        ->all();
} catch (\Exception $e) { $similarProducts = []; }

// Margin calc
$marginPct = 0;
if (($product->price ?? 0) > 0 && ($product->purchase_price ?? 0) > 0) {
    $marginPct = round(($product->price - $product->purchase_price) / $product->price * 100, 1);
}
$marginColor = $marginPct >= 20 ? '#059669' : ($marginPct >= 0 ? '#d97706' : '#dc2626');
?>
<style>
/* ═══ PRODUCT CRM — identical tokens to order pages ═══ */
.crm-wrap{display:flex;flex-direction:column;gap:0;min-height:calc(100vh - 120px);width:100%;min-width:0}
.crm-topbar{display:flex;align-items:center;gap:12px;padding:12px 20px;background:var(--admin-surface,#fff);border-bottom:1px solid var(--admin-border,#e5e7eb);flex-wrap:wrap;position:sticky;top:0;z-index:30;overflow-x:auto}
.crm-topbar-left{display:flex;align-items:center;gap:10px;flex:1;min-width:0}
.crm-order-num{font-size:1.125rem;font-weight:800;color:var(--admin-text-primary,#111);white-space:nowrap;cursor:pointer}
.crm-order-date{font-size:0.8rem;color:var(--admin-text-secondary,#6b7280)}
.crm-status-pill{display:inline-flex;align-items:center;gap:5px;padding:4px 10px;border-radius:999px;font-size:0.75rem;font-weight:700;white-space:nowrap;border:1px solid color-mix(in srgb,currentColor 20%,transparent)}
.crm-topbar-actions{display:flex;align-items:center;gap:6px;flex-wrap:nowrap;flex-shrink:0}
.crm-int-btn{display:inline-flex;align-items:center;gap:4px;padding:4px 8px;border-radius:8px;font-size:0.75rem;font-weight:600;border:1px solid var(--admin-border,#e5e7eb);background:var(--admin-surface,#fff);color:var(--admin-text-secondary,#6b7280);cursor:pointer;text-decoration:none;transition:all .15s;white-space:nowrap}
.crm-int-btn:hover{border-color:var(--admin-accent,#111);color:var(--admin-text-primary,#111);background:var(--admin-surface-hover,#f9fafb)}
.crm-int-sep{width:1px;height:20px;background:var(--admin-border,#e5e7eb);margin:0 2px;flex-shrink:0}
.crm-body{display:grid;grid-template-columns:1fr 380px;gap:20px;flex:1;align-items:start;width:100%;min-width:0}
.crm-main{padding:16px;display:flex;flex-direction:column;gap:12px;min-width:0}
.crm-sidebar{padding:10px 12px;display:flex;flex-direction:column;gap:8px}
.crm-sidebar .crm-card-head{padding:7px 12px}.crm-sidebar .crm-card-head h3{font-size:0.75rem}.crm-sidebar .crm-card-body{padding:10px 12px}
.crm-card{background:var(--admin-surface,#fff);border:1px solid var(--admin-border,#e5e7eb);border-radius:12px;overflow:hidden}
.crm-card-head{display:flex;align-items:center;justify-content:space-between;padding:10px 16px;border-bottom:1px solid var(--admin-border,#e5e7eb);background:var(--admin-surface-hover,#f9fafb)}
.crm-card-head h3{font-size:0.8125rem;font-weight:700;color:var(--admin-text-primary,#111);display:flex;align-items:center;gap:6px;margin:0}
.crm-card-head h3 i{color:var(--admin-text-secondary,#6b7280)}
.crm-card-body{padding:14px 16px}
.crm-info-grid{display:grid;grid-template-columns:1fr 1fr;gap:10px}
.crm-field{display:flex;flex-direction:column;gap:3px}
.crm-field-label{font-size:0.7rem;font-weight:600;text-transform:uppercase;letter-spacing:.04em;color:var(--admin-text-secondary,#9ca3af)}
.crm-field-val{font-size:0.875rem;font-weight:500;color:var(--admin-text-primary,#111)}
.crm-editable{display:flex;align-items:center;gap:4px;padding:4px 8px;border-radius:6px;cursor:pointer;border:1px solid transparent;transition:border-color .15s,background .15s;font-size:0.875rem;font-weight:500;color:var(--admin-text-primary,#111);min-height:30px;position:relative}
.crm-editable:hover{border-color:var(--admin-border,#e5e7eb);background:var(--admin-surface-hover,#f9fafb)}
.crm-editable:hover::after{content:'✎';position:absolute;right:6px;font-size:10px;color:var(--admin-text-secondary,#9ca3af)}
.crm-editable-input{width:100%;padding:4px 8px;border:1.5px solid var(--admin-accent,#111);border-radius:6px;font-size:0.875rem;font-weight:500;background:var(--admin-surface,#fff);color:var(--admin-text-primary,#111);outline:none}
.crm-editable-empty{color:var(--admin-text-secondary,#9ca3af);font-weight:400;font-style:italic}
.crm-save-flash{position:fixed;bottom:20px;right:20px;background:#059669;color:#fff;padding:6px 14px;border-radius:8px;font-size:0.8125rem;font-weight:600;opacity:0;transition:opacity .2s;z-index:9999;pointer-events:none}
.crm-save-flash.show{opacity:1}
/* Images */
.prod-img-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:8px;padding:14px 16px}
.prod-img-wrap{position:relative;border-radius:8px;overflow:hidden;aspect-ratio:1}
.prod-img-wrap img{width:100%;height:100%;object-fit:cover;display:block}
.prod-img-actions{position:absolute;top:6px;right:6px;display:flex;gap:4px;opacity:0;transition:opacity .15s}
.prod-img-wrap:hover .prod-img-actions{opacity:1}
.prod-img-badge{position:absolute;top:6px;left:6px}
/* Tables */
.crm-tbl{width:100%;border-collapse:collapse;font-size:0.8125rem}
.crm-tbl th{text-align:left;padding:6px 10px;font-size:0.7rem;font-weight:600;color:var(--admin-text-secondary,#6b7280);text-transform:uppercase;letter-spacing:.04em;border-bottom:1px solid var(--admin-border,#e5e7eb);position:sticky;top:0;background:var(--admin-surface-hover,#f9fafb);z-index:1}
.crm-tbl td{padding:7px 10px;border-bottom:1px solid var(--admin-border,#f3f4f6);vertical-align:middle}
.crm-tbl tr:last-child td{border-bottom:none}
/* Legacy inline-editable (chars + sizes) */
.inline-editable,.inline-editable-size{cursor:pointer;padding:2px 6px;border-radius:4px;transition:background .15s;display:inline-block;min-width:30px}
.inline-editable:hover,.inline-editable-size:hover{background:rgba(59,130,246,.08);outline:1px dashed #3b82f6}
.inline-edit-input{border:1px solid #3b82f6;border-radius:4px;padding:2px 6px;font-size:inherit;font-family:inherit;width:100%;max-width:140px;outline:none;background:var(--admin-surface,#fff)}
@media(max-width:1023px){.crm-body{grid-template-columns:1fr}}
@media(max-width:600px){.crm-info-grid{grid-template-columns:1fr}}
</style>

<div class="crm-wrap">

<!-- ═══ TOP BAR ═══ -->
<div class="crm-topbar">
    <div class="crm-topbar-left">
        <a href="<?= Url::to(['/admin/product/index']) ?>" class="admin-btn admin-btn-secondary admin-btn-sm" style="padding:4px 8px">
            <i class="bi bi-arrow-left"></i>
        </a>
        <span class="crm-order-num" id="product-title-span"
              title="Нажмите для редактирования названия"
              onclick="startEditTitle(this)"><?= Html::encode($product->name) ?></span>
        <span class="crm-status-pill"
              style="background:<?= $statusBg ?>;color:<?= $statusColor ?>">
            <i class="bi bi-circle-fill" style="font-size:6px"></i>
            <?= $statusLabel ?>
        </span>
        <span class="crm-order-date">
            #<?= $product->id ?>
            <?php if ($product->sku): ?> · <?= Html::encode($product->sku) ?><?php endif; ?>
            <?php if ($product->brand): ?> · <?= Html::encode($product->brand->name) ?><?php endif; ?>
        </span>
    </div>
    <div class="crm-topbar-actions">
        <?php if ($product->poizon_id): ?>
        <a href="<?= Url::to(['/admin/product/sync', 'id' => $product->id]) ?>"
           class="crm-int-btn" data-method="post" title="Синхронизация Poizon">
            <i class="bi bi-arrow-clockwise"></i> Poizon
        </a>
        <span class="crm-int-sep"></span>
        <?php endif; ?>
        <a href="<?= Url::to(['/admin/import/upload']) ?>" class="crm-int-btn">
            <i class="bi bi-download"></i> Импорт
        </a>
        <a href="<?= Url::to(['/admin/product/duplicate', 'id' => $product->id]) ?>"
           class="admin-btn admin-btn-secondary admin-btn-sm"
           data-method="post" data-confirm="Дублировать товар?">
            <i class="bi bi-copy"></i>
        </a>
        <a href="<?= Url::to(['/admin/product/edit', 'id' => $product->id]) ?>"
           class="admin-btn admin-btn-secondary admin-btn-sm">
            <i class="bi bi-pencil"></i> Редактировать
        </a>
        <a href="<?= Url::to(['/admin/order/create', 'product_id' => $product->id]) ?>"
           class="admin-btn admin-btn-primary admin-btn-sm">
            <i class="bi bi-bag-plus"></i> Создать заказ
        </a>
    </div>
</div>

<!-- ═══ BODY ═══ -->
<div class="crm-body">

<!-- ── MAIN COLUMN ── -->
<div class="crm-main">

    <!-- Изображения -->
    <div class="crm-card">
        <div class="crm-card-head">
            <h3><i class="bi bi-images"></i> Изображения</h3>
            <button type="button" class="admin-btn admin-btn-secondary admin-btn-sm"
                    data-bs-toggle="modal" data-bs-target="#addImageModal">
                <i class="bi bi-plus"></i> Добавить
            </button>
        </div>
        <?php if ($product->images && count($product->images) > 0): ?>
        <div class="prod-img-grid">
            <?php foreach ($product->images as $image): ?>
            <div class="prod-img-wrap">
                <img src="<?= $image->getImageUrl() ?>" alt="">
                <?php if ($image->is_main): ?>
                <span class="prod-img-badge admin-badge admin-badge-success" style="font-size:.6rem">Главное</span>
                <?php endif; ?>
                <div class="prod-img-actions">
                    <?php if (!$image->is_main): ?>
                    <a href="<?= Url::to(['/admin/product/set-main-image', 'id' => $image->id]) ?>"
                       class="admin-btn admin-btn-warning admin-btn-xs" data-method="post" title="Сделать главным">
                        <i class="bi bi-star"></i>
                    </a>
                    <?php endif; ?>
                    <a href="<?= Url::to(['/admin/product/delete-image', 'id' => $image->id]) ?>"
                       class="admin-btn admin-btn-danger admin-btn-xs" data-method="post"
                       data-confirm="Удалить изображение?">
                        <i class="bi bi-trash"></i>
                    </a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <div class="crm-card-body" style="text-align:center;padding:2.5rem;color:var(--admin-text-secondary)">
            <i class="bi bi-image" style="font-size:2.5rem"></i>
            <p style="margin-top:.75rem;font-size:.8125rem">Нет изображений. Добавьте по URL.</p>
        </div>
        <?php endif; ?>
    </div>

    <?php if ($hasCollectionUrl): ?>
    <!-- Фото МойСклад -->
    <div class="crm-card">
        <div class="crm-card-head"><h3><i class="bi bi-cloud-download"></i> Фото МойСклад</h3></div>
        <div class="crm-card-body" id="ms-images-container" style="text-align:center">
            <button class="admin-btn admin-btn-secondary admin-btn-sm"
                    onclick="loadMsImages(<?= $product->id ?>)" id="ms-load-btn">
                <i class="bi bi-cloud-download"></i> Загрузить фото из МС
            </button>
            <div id="ms-images-grid" style="display:none;grid-template-columns:repeat(3,1fr);gap:.5rem;margin-top:10px"></div>
        </div>
    </div>
    <?php endif; ?>

    <?php if ($product->poizon_id): ?>
    <!-- Данные Poizon -->
    <div class="crm-card">
        <div class="crm-card-head">
            <h3><i class="bi bi-cloud-check"></i> Данные Poizon</h3>
            <?php if ($product->poizon_url): ?>
            <a href="<?= Html::encode($product->poizon_url) ?>" target="_blank" class="crm-int-btn">
                <i class="bi bi-box-arrow-up-right"></i> Открыть
            </a>
            <?php endif; ?>
        </div>
        <div class="crm-card-body">
            <div class="crm-info-grid">
                <div class="crm-field">
                    <div class="crm-field-label">Poizon ID</div>
                    <div class="crm-field-val"><code><?= Html::encode($product->poizon_id) ?></code></div>
                </div>
                <?php if ($product->poizon_spu_id): ?>
                <div class="crm-field">
                    <div class="crm-field-label">SPU ID</div>
                    <div class="crm-field-val"><code><?= Html::encode($product->poizon_spu_id) ?></code></div>
                </div>
                <?php endif; ?>
                <div class="crm-field">
                    <div class="crm-field-label">Цена CNY</div>
                    <div class="crm-field-val"><strong><?= $product->poizon_price_cny ? '¥' . number_format($product->poizon_price_cny, 2) : '—' ?></strong></div>
                </div>
                <div class="crm-field">
                    <div class="crm-field-label">Последняя синхр.</div>
                    <div class="crm-field-val" style="<?= $product->last_sync_at ? '' : 'color:var(--admin-danger,#dc2626)' ?>">
                        <?= $product->last_sync_at ? date('d.m.Y H:i', $product->last_sync_at) : 'Не синхронизирован' ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <?php if ($product->description): ?>
    <!-- Описание -->
    <div class="crm-card">
        <div class="crm-card-head"><h3><i class="bi bi-text-paragraph"></i> Описание</h3></div>
        <div class="crm-card-body">
            <div class="crm-editable" data-field="description" data-id="<?= $product->id ?>"
                 onclick="startEdit(this)"
                 style="white-space:pre-wrap;min-height:60px;display:block"><?= Html::encode($product->description) ?></div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Характеристики -->
    <div class="crm-card">
        <div class="crm-card-head">
            <h3><i class="bi bi-list-check"></i> Характеристики</h3>
            <a href="<?= Url::to(['/admin/product/edit', 'id' => $product->id]) ?>#characteristics"
               class="admin-btn admin-btn-secondary admin-btn-sm">
                <i class="bi bi-pencil"></i> Редактировать
            </a>
        </div>
        <?php
        $hasMsChars = !empty($_msCharRows) || !empty($_msAttrs);
        $hasAnyChars = count($characteristicsFromRegistry) > 0 || $hasPoizonChars || $hasMsChars;
        ?>
        <?php if ($hasAnyChars): ?>
        <div style="overflow-x:auto">
            <table class="crm-tbl">
                <thead>
                    <tr><th style="width:40%">Характеристика</th><th>Значение</th><th style="width:18%;text-align:center">Источник</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($characteristicsFromRegistry as $pcv): ?>
                    <tr>
                        <td style="font-weight:600"><?= Html::encode($pcv->characteristic ? $pcv->characteristic->name : '—') ?></td>
                        <td>
                            <?php if ($pcv->characteristicValue): ?>
                                <span class="admin-badge admin-badge-primary"><?= Html::encode($pcv->characteristicValue->value) ?></span>
                            <?php elseif ($pcv->value_text): ?>
                                <?= Html::encode($pcv->value_text) ?>
                            <?php elseif ($pcv->value_number !== null): ?>
                                <?= Html::encode($pcv->value_number) ?>
                            <?php endif; ?>
                        </td>
                        <td style="text-align:center"><span class="admin-badge admin-badge-success" style="font-size:.65rem"><i class="bi bi-database"></i> Справочник</span></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php foreach ($properties as $prop): ?>
                    <tr>
                        <td style="font-weight:600"><?= Html::encode($prop['key'] ?? '') ?></td>
                        <td><?= Html::encode($prop['value'] ?? '') ?></td>
                        <td style="text-align:center"><span class="admin-badge admin-badge-info" style="font-size:.65rem"><i class="bi bi-cloud"></i> Poizon</span></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php foreach ($_msCharRows as $_mcr): ?>
                    <tr>
                        <td style="font-weight:600"><?= Html::encode($_mcr['label']) ?></td>
                        <td>
                            <?php if ($_mcr['field']): ?>
                            <span class="inline-editable" data-entity="product" data-id="<?= $product->id ?>"
                                  data-field="<?= Html::encode($_mcr['field']) ?>"><?= Html::encode($_mcr['value']) ?></span>
                            <?php else: ?><?= Html::encode($_mcr['value']) ?><?php endif; ?>
                        </td>
                        <td style="text-align:center"><span class="admin-badge" style="background:#dbeafe;color:#1e40af;font-size:.65rem"><i class="bi bi-cloud-check"></i> МС</span></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php foreach ($_msAttrs as $_ma): ?>
                    <tr>
                        <td style="font-weight:600"><?= Html::encode($_ma['name'] ?? '') ?></td>
                        <td><?= Html::encode(is_array($_ma['value'] ?? '') ? ($_ma['value']['name'] ?? json_encode($_ma['value'])) : ($_ma['value'] ?? '')) ?></td>
                        <td style="text-align:center"><span class="admin-badge" style="background:#dbeafe;color:#1e40af;font-size:.65rem"><i class="bi bi-cloud-check"></i> МС</span></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
        <div class="crm-card-body" style="text-align:center;color:var(--admin-text-secondary);padding:2rem">
            <i class="bi bi-info-circle" style="font-size:1.75rem"></i>
            <p style="margin-top:.75rem;font-size:.8125rem">Характеристики не заполнены.</p>
        </div>
        <?php endif; ?>
    </div>

    <!-- Размеры -->
    <div class="crm-card">
        <div class="crm-card-head">
            <h3><i class="bi bi-rulers"></i> Размеры
                <?php if ($product->poizon_id): ?><span class="admin-badge admin-badge-info" style="font-size:.65rem;margin-left:4px">Poizon sync</span><?php endif; ?>
            </h3>
            <button type="button" class="admin-btn admin-btn-success admin-btn-sm"
                    data-bs-toggle="modal" data-bs-target="#addSizeModal">
                <i class="bi bi-plus-circle"></i> Добавить
            </button>
        </div>
        <?php if (count($sizes) > 0): ?>
        <div style="overflow-x:auto">
            <table class="crm-tbl">
                <thead>
                    <tr>
                        <th>US</th><th>EU</th><th>UK</th><th>CM</th>
                        <th title="Цена CNY">¥</th>
                        <th>BYN <i class="bi bi-pencil-fill" style="font-size:.5rem;color:var(--admin-primary,#3b82f6)"></i></th>
                        <th>Цена клиента</th>
                        <th>МС вариант</th>
                        <th>Штрихкод</th>
                        <?php if ($product->poizon_id): ?>
                        <th>SKU</th><th>Артикул</th><th>Фото</th><th>Pzn Ост.</th>
                        <?php endif; ?>
                        <th>Остаток <i class="bi bi-pencil-fill" style="font-size:.5rem;color:var(--admin-primary,#3b82f6)"></i></th>
                        <th>Статус</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($sizes as $size): ?>
                    <tr>
                        <td style="font-weight:600"><?= Html::encode($size->us_size ?: $size->size) ?></td>
                        <td><?= Html::encode($size->eu_size ?: '—') ?></td>
                        <td><?= Html::encode($size->uk_size ?: '—') ?></td>
                        <td><?= Html::encode($size->cm_size ?: '—') ?></td>
                        <td>
                            <?php if ($size->price_cny): ?>
                            <span class="admin-badge admin-badge-info" style="cursor:pointer"
                                  onclick="copyToClipboard('<?= $size->price_cny ?>', this)" title="Копировать">
                                ¥<?= number_format($size->price_cny, 2) ?> <i class="bi bi-clipboard"></i>
                            </span>
                            <?php else: ?><span style="color:var(--admin-text-secondary)">—</span><?php endif; ?>
                        </td>
                        <td>
                            <span class="inline-editable-size" data-id="<?= $size->id ?>" data-field="price_byn" data-type="number" title="Редактировать">
                                <?= $size->price_byn ? number_format($size->price_byn, 2) . ' BYN' : '<span style="color:var(--admin-text-secondary)">—</span>' ?>
                            </span>
                        </td>
                        <td>
                            <?= $size->price_client_byn
                                ? '<strong style="color:var(--admin-success,#059669)">' . number_format($size->price_client_byn, 2) . ' BYN</strong>'
                                : '<span style="color:var(--admin-text-secondary)">—</span>' ?>
                        </td>
                        <td>
                            <?php if (!empty($size->ms_variant_id)): ?>
                            <code style="font-size:.65rem;color:#6b7280" title="<?= Html::encode($size->ms_variant_id) ?>"><?= Html::encode(substr($size->ms_variant_id, 0, 8)) ?>…</code>
                            <?php else: ?><span style="color:var(--admin-text-secondary)">—</span><?php endif; ?>
                        </td>
                        <td><?= !empty($size->ms_barcode) ? '<code style="font-size:.7rem">' . Html::encode($size->ms_barcode) . '</code>' : '<span style="color:var(--admin-text-secondary)">—</span>' ?></td>
                        <?php if ($product->poizon_id): ?>
                        <td><small><?= Html::encode($size->poizon_sku_id ?: '—') ?></small></td>
                        <td>
                            <?= $size->variant_vendor_code
                                ? '<code style="color:var(--admin-primary,#3b82f6)">' . Html::encode($size->variant_vendor_code) . '</code>'
                                : '<span style="color:var(--admin-text-secondary)">—</span>' ?>
                        </td>
                        <td>
                            <?php
                            $variantImages = $size->images_json ? (is_array($size->images_json) ? $size->images_json : json_decode($size->images_json, true)) : [];
                            ?>
                            <?php if (!empty($variantImages)): ?>
                            <button type="button" class="admin-btn admin-btn-secondary admin-btn-xs"
                                    data-bs-toggle="modal" data-bs-target="#imagesModal<?= $size->id ?>">
                                <i class="bi bi-images"></i> <?= count($variantImages) ?>
                            </button>
                            <?php else: ?><span style="color:var(--admin-text-secondary)">—</span><?php endif; ?>
                        </td>
                        <td>
                            <?= ($size->poizon_stock ?? 0) > 0
                                ? '<span class="admin-badge admin-badge-success">' . $size->poizon_stock . '</span>'
                                : '<span class="admin-badge admin-badge-secondary">0</span>' ?>
                        </td>
                        <?php endif; ?>
                        <td>
                            <span class="inline-editable-size" data-id="<?= $size->id ?>" data-field="stock" data-type="number" title="Редактировать">
                                <?= $size->stock > 0
                                    ? '<span class="admin-badge admin-badge-primary">' . $size->stock . '</span>'
                                    : '<span class="admin-badge admin-badge-secondary">0</span>' ?>
                            </span>
                        </td>
                        <td>
                            <?php if ($size->is_available): ?>
                            <span class="admin-badge admin-badge-success" style="cursor:pointer"
                                  onclick="toggleSizeAvailable(<?= $size->id ?>, 0, this)">Доступен</span>
                            <?php else: ?>
                            <span class="admin-badge admin-badge-secondary" style="cursor:pointer"
                                  onclick="toggleSizeAvailable(<?= $size->id ?>, 1, this)">Недоступен</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?= Html::a('<i class="bi bi-trash"></i>', ['/admin/product/delete-size', 'id' => $size->id], [
                                'class' => 'admin-btn admin-btn-danger admin-btn-xs',
                                'data-method' => 'post',
                                'data-confirm' => 'Удалить размер ' . ($size->us_size ?: $size->size) . '?',
                            ]) ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
        <div class="crm-card-body" style="text-align:center;color:var(--admin-text-secondary);padding:2rem">
            <i class="bi bi-rulers" style="font-size:1.75rem"></i>
            <p style="margin-top:.75rem;font-size:.8125rem">Размеры не добавлены.</p>
        </div>
        <?php endif; ?>
    </div>

</div><!-- /crm-main -->

<!-- ── RIGHT SIDEBAR ── -->
<div class="crm-sidebar">

    <!-- Статус -->
    <div class="crm-card">
        <div class="crm-card-head">
            <h3><i class="bi bi-toggle-on"></i> Статус</h3>
            <span style="background:<?= $statusBg ?>;color:<?= $statusColor ?>;font-size:0.7rem;padding:2px 8px;border-radius:6px;font-weight:700">
                <?= $statusLabel ?>
            </span>
        </div>
        <div class="crm-card-body" style="display:flex;flex-direction:column;gap:8px">
            <button type="button"
                    class="admin-btn admin-btn-<?= $product->is_active ? 'warning' : 'success' ?>"
                    onclick="toggleActive(<?= $product->id ?>)"
                    style="width:100%;justify-content:center">
                <i class="bi bi-toggle-<?= $product->is_active ? 'on' : 'off' ?>"></i>
                <?= $product->is_active ? 'Деактивировать' : 'Активировать' ?>
            </button>
            <?php if ($product->hasAttribute('is_limited')): ?>
            <label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-size:.8125rem;padding:2px 0">
                <input type="checkbox" <?= $product->is_limited ? 'checked' : '' ?>
                       onchange="saveProductField('is_limited', this.checked ? 1 : 0)">
                Limited Edition
            </label>
            <?php endif; ?>
        </div>
    </div>

    <!-- Цена -->
    <div class="crm-card">
        <div class="crm-card-head">
            <h3><i class="bi bi-tag"></i> Цена</h3>
            <button class="admin-btn admin-btn-secondary admin-btn-sm"
                    onclick="openEditPrice(<?= $product->id ?>, <?= (float)($product->price ?? 0) ?>)">
                <i class="bi bi-pencil"></i>
            </button>
        </div>
        <div class="crm-card-body" style="display:flex;flex-direction:column;gap:8px">
            <div class="crm-field">
                <div class="crm-field-label">Цена продажи</div>
                <div style="font-size:1.25rem;font-weight:800">
                    <?= ($product->price ?? 0) ? number_format($product->price, 2) . ' BYN' : '<span style="color:var(--admin-danger,#dc2626)">Не задана</span>' ?>
                </div>
            </div>
            <?php if (($product->purchase_price ?? 0) > 0): ?>
            <div class="crm-field">
                <div class="crm-field-label">Закупка</div>
                <div class="crm-editable" data-field="purchase_price" data-id="<?= $product->id ?>"
                     onclick="startEdit(this)"><?= number_format($product->purchase_price, 2) ?> BYN</div>
            </div>
            <?php if ($marginPct != 0): ?>
            <div class="crm-field">
                <div class="crm-field-label">Маржа</div>
                <div style="font-weight:700;color:<?= $marginColor ?>"><?= $marginPct ?>%
                    <span style="font-weight:400;font-size:.75rem;color:var(--admin-text-secondary)">(<?= number_format($product->price - $product->purchase_price, 2) ?> BYN)</span>
                </div>
            </div>
            <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Основная информация -->
    <div class="crm-card">
        <div class="crm-card-head"><h3><i class="bi bi-info-circle"></i> Основная информация</h3></div>
        <div class="crm-card-body" style="display:flex;flex-direction:column;gap:8px">
            <div class="crm-field">
                <div class="crm-field-label">ID</div>
                <div class="crm-field-val">#<?= $product->id ?></div>
            </div>
            <?php if ($product->sku): ?>
            <div class="crm-field">
                <div class="crm-field-label">SKU</div>
                <div class="crm-editable" data-field="sku" data-id="<?= $product->id ?>"
                     onclick="startEdit(this)"><?= Html::encode($product->sku) ?></div>
            </div>
            <?php endif; ?>
            <?php if ($product->hasAttribute('vendor_code') && $product->vendor_code): ?>
            <div class="crm-field">
                <div class="crm-field-label">Артикул</div>
                <div class="crm-editable" data-field="vendor_code" data-id="<?= $product->id ?>"
                     onclick="startEdit(this)"><?= Html::encode($product->vendor_code) ?></div>
            </div>
            <?php endif; ?>
            <?php if ($product->brand): ?>
            <div class="crm-field">
                <div class="crm-field-label">Бренд</div>
                <div class="crm-field-val"><?= Html::encode($product->brand->name) ?></div>
            </div>
            <?php endif; ?>
            <?php if ($product->category): ?>
            <div class="crm-field">
                <div class="crm-field-label">Категория</div>
                <div class="crm-field-val"><?= Html::encode($product->category->name) ?></div>
            </div>
            <?php endif; ?>
            <?php if (!empty($allKeywords)): ?>
            <div class="crm-field">
                <div class="crm-field-label">Ключевые слова</div>
                <div style="display:flex;flex-wrap:wrap;gap:3px;margin-top:2px">
                    <?php foreach ($allKeywords as $kw): ?>
                    <span class="admin-badge admin-badge-secondary" style="font-size:.65rem"><?= Html::encode($kw) ?></span>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
            <?php if ($product->created_at): ?>
            <div class="crm-field">
                <div class="crm-field-label">Добавлен</div>
                <div class="crm-field-val" style="font-size:.8rem">
                    <?= is_numeric($product->created_at) ? date('d.m.Y H:i', $product->created_at) : date('d.m.Y', strtotime($product->created_at)) ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Статистика -->
    <div class="crm-card">
        <div class="crm-card-head"><h3><i class="bi bi-bar-chart"></i> Статистика</h3></div>
        <div class="crm-card-body">
            <div class="crm-info-grid">
                <div style="background:var(--admin-surface-hover,#f9fafb);border-radius:8px;padding:10px;text-align:center">
                    <div style="font-size:1.25rem;font-weight:800"><?= count($productOrders) ?></div>
                    <div style="font-size:.65rem;color:var(--admin-text-secondary);text-transform:uppercase;letter-spacing:.04em">Заказов</div>
                </div>
                <div style="background:var(--admin-surface-hover,#f9fafb);border-radius:8px;padding:10px;text-align:center">
                    <div style="font-size:1.25rem;font-weight:800"><?= $product->views_count ?? 0 ?></div>
                    <div style="font-size:.65rem;color:var(--admin-text-secondary);text-transform:uppercase;letter-spacing:.04em">Просмотров</div>
                </div>
                <div style="background:var(--admin-surface-hover,#f9fafb);border-radius:8px;padding:10px;text-align:center">
                    <div style="font-size:1.25rem;font-weight:800"><?= $product->favorite_count ?? 0 ?></div>
                    <div style="font-size:.65rem;color:var(--admin-text-secondary);text-transform:uppercase;letter-spacing:.04em">Избранное</div>
                </div>
                <div style="background:var(--admin-surface-hover,#f9fafb);border-radius:8px;padding:10px;text-align:center">
                    <div style="font-size:1.25rem;font-weight:800"><?= count($sizes) ?></div>
                    <div style="font-size:.65rem;color:var(--admin-text-secondary);text-transform:uppercase;letter-spacing:.04em">Размеров</div>
                </div>
            </div>
        </div>
    </div>

    <!-- МойСклад -->
    <?php if ($product->moysklad_id || $product->ms_code || $product->ms_external_code): ?>
    <div class="crm-card">
        <div class="crm-card-head">
            <h3><i class="bi bi-cloud-check"></i> МойСклад</h3>
            <?php if ($product->moysklad_id): ?>
            <a href="https://online.moysklad.ru/app/#good/edit?id=<?= Html::encode($product->moysklad_id) ?>"
               target="_blank" class="crm-int-btn"><i class="bi bi-box-arrow-up-right"></i> Открыть</a>
            <?php endif; ?>
        </div>
        <div class="crm-card-body" style="display:flex;flex-direction:column;gap:6px;font-size:.8125rem">
            <?php if ($product->ms_code): ?><div><span style="color:var(--admin-text-secondary)">Код МС:</span> <code><?= Html::encode($product->ms_code) ?></code></div><?php endif; ?>
            <?php if ($product->ms_external_code): ?><div><span style="color:var(--admin-text-secondary)">Внешний:</span> <code><?= Html::encode($product->ms_external_code) ?></code></div><?php endif; ?>
            <?php if ($product->ms_path_name): ?><div><span style="color:var(--admin-text-secondary)">Группа:</span> <?= Html::encode($product->ms_path_name) ?></div><?php endif; ?>
            <?php if ($product->ms_supplier_name): ?><div><span style="color:var(--admin-text-secondary)">Поставщик:</span> <strong><?= Html::encode($product->ms_supplier_name) ?></strong></div><?php endif; ?>
            <?php if ($product->ms_price_full || $product->ms_price_rub): ?>
            <div style="border-top:1px solid var(--admin-border,#e5e7eb);padding-top:6px;margin-top:2px;display:flex;flex-wrap:wrap;gap:8px">
                <?php if ($product->ms_price_full): ?><span><span style="color:var(--admin-text-secondary)">Полная:</span> <strong><?= number_format($product->ms_price_full, 2) ?></strong></span><?php endif; ?>
                <?php if ($product->ms_price_rub): ?><span><span style="color:var(--admin-text-secondary)">₽:</span> <strong><?= number_format($product->ms_price_rub, 2) ?></strong></span><?php endif; ?>
                <?php if ($product->ms_price_sale): ?><span><span style="color:var(--admin-text-secondary)">Акц.:</span> <strong style="color:#059669"><?= number_format($product->ms_price_sale, 2) ?></strong></span><?php endif; ?>
            </div>
            <?php endif; ?>
            <?php
            $msFlags = [];
            if (!empty($product->ms_archived))  $msFlags[] = '<span class="admin-badge" style="background:#fee2e2;color:#991b1b;font-size:.65rem">Архив МС</span>';
            if (!empty($product->ms_no_export)) $msFlags[] = '<span class="admin-badge" style="background:#fef3c7;color:#92400e;font-size:.65rem">Не экспортировать</span>';
            if (!empty($msFlags)): ?><div style="display:flex;gap:4px"><?= implode('', $msFlags) ?></div><?php endif; ?>
        </div>
    </div>
    <?php endif; ?>

</div><!-- /crm-sidebar -->
</div><!-- /crm-body -->

<!-- ═══ FULL-WIDTH SECTIONS ═══ -->
<?php if (!empty($productOrders)): ?>
<div style="padding:0 16px 16px">
    <div class="crm-card">
        <div class="crm-card-head">
            <h3><i class="bi bi-bag-check"></i> Заказы с этим товаром</h3>
            <span style="font-size:.75rem;color:var(--admin-text-secondary)"><?= count($productOrders) ?> заказов</span>
        </div>
        <div style="overflow-x:auto">
            <table class="crm-tbl">
                <thead>
                    <tr><th>№ Заказа</th><th>Размер</th><th>Кол-во</th><th>Цена</th><th>Статус</th><th>Дата</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($productOrders as $oi): if (!$oi->order) continue; ?>
                    <tr>
                        <td><a href="<?= Url::to(['/admin/order/view', 'id' => $oi->order->id]) ?>" style="font-weight:600">#<?= Html::encode($oi->order->order_number ?: $oi->order->id) ?></a></td>
                        <td><?= Html::encode($oi->size ?: '—') ?></td>
                        <td><?= (int)$oi->quantity ?></td>
                        <td><?= number_format($oi->price, 2) ?> BYN</td>
                        <td>
                            <?php
                            $st = $oi->order->status;
                            $stRu = $orderStatusMap[$st] ?? $st;
                            $stClass = in_array($st, ['delivered','paid','confirmed_and_paid']) ? 'success'
                                : (in_array($st, ['cancelled','returned','refunded']) ? 'danger' : 'secondary');
                            ?>
                            <span class="admin-badge admin-badge-<?= $stClass ?>"><?= Html::encode($stRu) ?></span>
                        </td>
                        <td style="font-size:.8rem;color:var(--admin-text-secondary)"><?= date('d.m.Y', $oi->order->created_at) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php endif; ?>

<?php if (!empty($similarProducts)): ?>
<div style="padding:0 16px 20px">
    <div class="crm-card">
        <div class="crm-card-head">
            <h3><i class="bi bi-grid-3x3-gap"></i> Похожие товары</h3>
            <span style="font-size:.75rem;color:var(--admin-text-secondary)"><?= count($similarProducts) ?></span>
        </div>
        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(150px,1fr));gap:1rem;padding:14px 16px">
            <?php foreach ($similarProducts as $sp): ?>
            <div style="border:1px solid var(--admin-border,#e5e7eb);border-radius:10px;overflow:hidden">
                <?php $spImg = $sp->getMainImageUrl(); ?>
                <?php if ($spImg): ?>
                <img src="<?= Html::encode($spImg) ?>" style="width:100%;height:90px;object-fit:cover" alt="">
                <?php else: ?>
                <div style="width:100%;height:90px;background:#f3f4f6;display:flex;align-items:center;justify-content:center">
                    <i class="bi bi-image" style="font-size:1.75rem;color:#9ca3af"></i>
                </div>
                <?php endif; ?>
                <div style="padding:8px">
                    <div style="font-size:.75rem;font-weight:600;overflow:hidden;text-overflow:ellipsis;white-space:nowrap" title="<?= Html::encode($sp->name) ?>"><?= Html::encode($sp->name) ?></div>
                    <div style="font-size:.7rem;color:var(--admin-text-secondary);margin:2px 0"><?= number_format($sp->price, 2) ?> BYN</div>
                    <div style="display:flex;gap:4px;margin-top:6px">
                        <a href="<?= Url::to(['/admin/product/view', 'id' => $sp->id]) ?>"
                           class="admin-btn admin-btn-secondary admin-btn-sm" style="flex:1;justify-content:center;display:inline-flex;font-size:.65rem" title="Просмотр">
                            <i class="bi bi-eye"></i>
                        </a>
                        <a href="<?= Url::to(['/admin/order/create', 'product_id' => $sp->id]) ?>"
                           class="admin-btn admin-btn-primary admin-btn-sm" style="flex:1;justify-content:center;display:inline-flex;font-size:.65rem" title="Создать заказ">
                            <i class="bi bi-bag-plus"></i>
                        </a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>
<?php endif; ?>

</div><!-- /crm-wrap -->

<!-- ═══ MODALS ═══ -->

<!-- Добавить изображение -->
<div class="modal fade" id="addImageModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-image"></i> Добавить изображение</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="post" action="<?= Url::to(['/admin/product/add-image', 'productId' => $product->id]) ?>">
                <?= Html::hiddenInput(\Yii::$app->request->csrfParam, \Yii::$app->request->getCsrfToken()) ?>
                <div class="modal-body">
                    <label class="form-label">URL изображения</label>
                    <input type="url" name="image_url" class="form-control" placeholder="https://…" required>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                    <button type="submit" class="btn btn-primary">Добавить</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Добавить размер -->
<div class="modal fade" id="addSizeModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-rulers"></i> Добавить размер</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="post" action="<?= Url::to(['/admin/product/add-size', 'productId' => $product->id]) ?>">
                <?= Html::hiddenInput(\Yii::$app->request->csrfParam, \Yii::$app->request->getCsrfToken()) ?>
                <div class="modal-body">
                    <div class="row g-2">
                        <div class="col-3"><label class="form-label">US</label><input type="text" name="us_size" class="form-control" required></div>
                        <div class="col-3"><label class="form-label">EU</label><input type="text" name="eu_size" class="form-control"></div>
                        <div class="col-3"><label class="form-label">UK</label><input type="text" name="uk_size" class="form-control"></div>
                        <div class="col-3"><label class="form-label">CM</label><input type="text" name="cm_size" class="form-control"></div>
                        <div class="col-4"><label class="form-label">Цена ¥</label><input type="number" step="0.01" min="0" name="price_cny" class="form-control"></div>
                        <div class="col-4"><label class="form-label">Цена BYN</label><input type="number" step="0.01" min="0" name="price_byn" class="form-control"></div>
                        <div class="col-4"><label class="form-label">Остаток</label><input type="number" min="0" name="stock" class="form-control" value="0"></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                    <button type="submit" class="btn btn-primary">Добавить</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Изменить цену -->
<div class="modal fade" id="editPriceModal" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-tag"></i> Изменить цену</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <label class="form-label fw-semibold">Новая цена (BYN)</label>
                <div class="input-group">
                    <input type="number" id="editPriceInput" class="form-control" step="0.01" min="0" placeholder="0.00">
                    <span class="input-group-text">BYN</span>
                </div>
                <div id="editPriceError" class="text-danger mt-1" style="display:none;font-size:.8rem"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                <button type="button" class="btn btn-primary" id="editPriceSaveBtn" onclick="doSavePrice()">
                    <i class="bi bi-check-lg"></i> Сохранить
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Фото вариантов (Poizon) -->
<?php
foreach ($sizes as $size):
    $variantImages = $size->images_json ? (is_array($size->images_json) ? $size->images_json : json_decode($size->images_json, true)) : [];
    if (empty($variantImages)) continue;
?>
<div class="modal fade" id="imagesModal<?= $size->id ?>" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Фото варианта <?= Html::encode($size->us_size ?: $size->size) ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(150px,1fr));gap:1rem">
                    <?php foreach ($variantImages as $img): ?>
                    <img src="<?= Html::encode($img) ?>" style="width:100%;border-radius:8px" alt="">
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Закрыть</button>
            </div>
        </div>
    </div>
</div>
<?php endforeach; ?>

<script>
(function() {
var _inlineUpdateUrl = '<?= Url::to(['/admin/product/inline-update']) ?>';
var _updatePriceUrl  = '<?= Url::to(['/admin/product/update-price']) ?>';
var _toggleUrl       = '<?= Url::to(['/admin/product/toggle', 'id' => $product->id]) ?>';
var _csrfToken       = '<?= Yii::$app->request->getCsrfToken() ?>';
var _productId       = <?= $product->id ?>;
var _editPriceProductId = null;

function showFlash(msg) {
    var f = document.createElement('span');
    f.className = 'crm-save-flash';
    f.textContent = msg || '✓ Сохранено';
    document.body.appendChild(f);
    setTimeout(function(){ f.classList.add('show'); }, 10);
    setTimeout(function(){ f.classList.remove('show'); setTimeout(function(){ f.remove(); }, 300); }, 1800);
}

function inlineSaveProduct(field, value, cb) {
    fetch(_inlineUpdateUrl, {
        method: 'POST',
        headers: {'Content-Type':'application/json','X-CSRF-Token':_csrfToken,'X-Requested-With':'XMLHttpRequest'},
        body: JSON.stringify({entity:'product', id:_productId, field:field, value:value})
    }).then(function(r){ return r.json(); }).then(function(res){
        if (res.success) { showFlash('✓ Сохранено'); if (cb) cb(); }
        else alert('Ошибка: ' + (res.message || ''));
    }).catch(function(){ alert('Ошибка сети'); });
}

window.saveProductField = function(field, value) { inlineSaveProduct(field, value); };

// CRM-style inline edit for crm-editable divs
window.startEdit = function(el) {
    if (el.querySelector('input,textarea')) return;
    var field  = el.dataset.field;
    var curVal = el.innerText.trim();
    var isArea = (field === 'description' || field === 'comment');
    var input  = document.createElement(isArea ? 'textarea' : 'input');
    input.className = 'crm-editable-input';
    input.value = curVal;
    if (isArea) { input.rows = 4; input.style.resize = 'vertical'; input.style.width = '100%'; }
    el.innerHTML = '';
    el.appendChild(input);
    input.focus();
    var save = function() {
        var newVal = input.value;
        inlineSaveProduct(field, newVal);
        el.innerHTML = newVal || '<span class="crm-editable-empty">—</span>';
    };
    input.addEventListener('blur', save);
    input.addEventListener('keydown', function(e) {
        if (e.key === 'Enter' && !isArea) { e.preventDefault(); save(); }
        if (e.key === 'Escape') { el.innerHTML = curVal || '<span class="crm-editable-empty">—</span>'; }
    });
};

// Editable product title in topbar
window.startEditTitle = function(el) {
    if (el.contentEditable === 'true') return;
    el.contentEditable = 'true';
    el.focus();
    var range = document.createRange();
    range.selectNodeContents(el);
    window.getSelection().removeAllRanges();
    window.getSelection().addRange(range);
    var origName = el.textContent;
    el.addEventListener('blur', function onBlur() {
        el.removeEventListener('blur', onBlur);
        el.contentEditable = 'false';
        var newName = el.textContent.trim();
        if (newName && newName !== origName) {
            inlineSaveProduct('name', newName, function() { document.title = newName + ' — Админка'; });
        } else { el.textContent = origName; }
    });
    el.addEventListener('keydown', function(e) {
        if (e.key === 'Enter') { e.preventDefault(); el.blur(); }
        if (e.key === 'Escape') { el.textContent = origName; el.contentEditable = 'false'; }
    }, {once: true});
};

// Legacy inline-editable (char rows with entity/id/field)
document.addEventListener('click', function(e) {
    var el = e.target.closest('.inline-editable');
    if (!el || el.querySelector('input')) return;
    var entity = el.dataset.entity, id = el.dataset.id, field = el.dataset.field;
    var curVal = el.textContent.trim();
    var input = document.createElement('input');
    input.type = 'text'; input.className = 'inline-edit-input'; input.value = curVal;
    el.textContent = ''; el.appendChild(input); input.focus(); input.select();
    function commit() {
        var newVal = input.value.trim();
        el.textContent = newVal || curVal;
        if (newVal !== curVal) {
            fetch(_inlineUpdateUrl, {
                method: 'POST',
                headers: {'Content-Type':'application/json','X-CSRF-Token':_csrfToken,'X-Requested-With':'XMLHttpRequest'},
                body: JSON.stringify({entity:entity, id:id, field:field, value:newVal})
            }).then(function(r){ return r.json(); }).then(function(res){ if (res.success) showFlash('✓'); else alert('Ошибка'); });
        }
    }
    input.addEventListener('blur', commit);
    input.addEventListener('keydown', function(ev) {
        if (ev.key === 'Enter') { ev.preventDefault(); input.blur(); }
        if (ev.key === 'Escape') { el.textContent = curVal; }
    });
});

// inline-editable-size (price_byn, stock)
document.addEventListener('click', function(e) {
    var el = e.target.closest('.inline-editable-size');
    if (!el || el.querySelector('input')) return;
    var id = el.dataset.id, field = el.dataset.field;
    var rawVal = el.textContent.replace(/[^0-9.\-]/g,'').trim();
    var input = document.createElement('input');
    input.type = 'number'; input.step = field === 'stock' ? '1' : '0.01'; input.min = '0';
    input.className = 'inline-edit-input'; input.value = rawVal || '0';
    var origHtml = el.innerHTML;
    el.innerHTML = ''; el.appendChild(input); input.focus(); input.select();
    function commitSize() {
        var newVal = input.value.trim();
        if (field === 'price_byn') {
            el.innerHTML = newVal && parseFloat(newVal)
                ? parseFloat(newVal).toFixed(2) + ' BYN'
                : '<span style="color:var(--admin-text-secondary)">—</span>';
        } else if (field === 'stock') {
            var n = parseInt(newVal) || 0;
            el.innerHTML = n > 0
                ? '<span class="admin-badge admin-badge-primary">' + n + '</span>'
                : '<span class="admin-badge admin-badge-secondary">0</span>';
        }
        if (newVal !== rawVal) {
            fetch(_inlineUpdateUrl, {
                method: 'POST',
                headers: {'Content-Type':'application/json','X-CSRF-Token':_csrfToken,'X-Requested-With':'XMLHttpRequest'},
                body: JSON.stringify({entity:'size', id:id, field:field, value:newVal})
            }).then(function(r){ return r.json(); }).then(function(res){ if (res.success) showFlash('✓'); else alert('Ошибка'); });
        }
    }
    input.addEventListener('blur', commitSize);
    input.addEventListener('keydown', function(ev) {
        if (ev.key === 'Enter') { ev.preventDefault(); input.blur(); }
        if (ev.key === 'Escape') { el.innerHTML = origHtml; }
    });
});

window.toggleSizeAvailable = function(sizeId, newVal, el) {
    fetch(_inlineUpdateUrl, {
        method: 'POST',
        headers: {'Content-Type':'application/json','X-CSRF-Token':_csrfToken,'X-Requested-With':'XMLHttpRequest'},
        body: JSON.stringify({entity:'size', id:sizeId, field:'is_available', value:newVal})
    }).then(function(r){ return r.json(); }).then(function(res){ if (res.success) showFlash('✓'); else alert('Ошибка'); });
    if (newVal) {
        el.className = 'admin-badge admin-badge-success'; el.textContent = 'Доступен';
        el.setAttribute('onclick', 'toggleSizeAvailable(' + sizeId + ', 0, this)');
    } else {
        el.className = 'admin-badge admin-badge-secondary'; el.textContent = 'Недоступен';
        el.setAttribute('onclick', 'toggleSizeAvailable(' + sizeId + ', 1, this)');
    }
};

window.copyToClipboard = function(text, element) {
    navigator.clipboard.writeText(text).then(function() {
        var orig = element.innerHTML;
        element.innerHTML = '<i class="bi bi-check"></i> Скопировано';
        setTimeout(function() { element.innerHTML = orig; }, 1500);
    });
};

window.openEditPrice = function(productId, currentPrice) {
    _editPriceProductId = productId;
    var input = document.getElementById('editPriceInput');
    var err = document.getElementById('editPriceError');
    input.value = currentPrice; err.style.display = 'none';
    new bootstrap.Modal(document.getElementById('editPriceModal')).show();
    setTimeout(function() { input.focus(); input.select(); }, 300);
};

window.doSavePrice = function() {
    var input = document.getElementById('editPriceInput');
    var err = document.getElementById('editPriceError');
    var price = parseFloat(input.value);
    if (isNaN(price) || price < 0) { err.textContent = 'Введите корректную цену (≥ 0)'; err.style.display = 'block'; return; }
    var btn = document.getElementById('editPriceSaveBtn');
    btn.disabled = true; btn.innerHTML = '<i class="bi bi-hourglass-split"></i> Сохранение…';
    var params = new URLSearchParams();
    params.append('id', _editPriceProductId); params.append('price', price.toFixed(2)); params.append('_csrf', _csrfToken);
    fetch(_updatePriceUrl, {
        method:'POST',
        headers:{'Content-Type':'application/x-www-form-urlencoded','X-Requested-With':'XMLHttpRequest'},
        body: params.toString()
    }).then(function(r) {
        if (r.ok) { bootstrap.Modal.getInstance(document.getElementById('editPriceModal')).hide(); location.reload(); }
        else throw new Error('HTTP ' + r.status);
    }).catch(function(e) {
        err.textContent = 'Ошибка: ' + e.message; err.style.display = 'block';
        btn.disabled = false; btn.innerHTML = '<i class="bi bi-check-lg"></i> Сохранить';
    });
};

document.getElementById('editPriceInput').addEventListener('keydown', function(e) {
    if (e.key === 'Enter') doSavePrice();
});

window.toggleActive = function(productId) {
    if (!confirm('Изменить статус товара?')) return;
    fetch(_toggleUrl, {
        method:'POST',
        headers:{'X-Requested-With':'XMLHttpRequest','X-CSRF-Token':_csrfToken}
    }).then(function(r){ if (r.ok) location.reload(); else alert('Ошибка'); })
      .catch(function(){ alert('Ошибка сети'); });
};

window.loadMsImages = function(productId) {
    var btn = document.getElementById('ms-load-btn');
    var grid = document.getElementById('ms-images-grid');
    btn.disabled = true; btn.innerHTML = '<i class="bi bi-hourglass-split"></i> Загрузка…';
    fetch('<?= \yii\helpers\Url::to(['/admin/moysklad/ms-images']) ?>?product_id=' + productId)
        .then(function(r){ return r.json(); })
        .then(function(images) {
            if (!images || images.length === 0) { btn.innerHTML = '<i class="bi bi-image"></i> Нет фото'; return; }
            btn.style.display = 'none'; grid.style.display = 'grid';
            images.forEach(function(img) {
                var el = document.createElement('img');
                el.src = img.miniature; el.alt = img.filename || '';
                el.style.cssText = 'width:100%;border-radius:6px;cursor:pointer';
                el.onclick = function() { window.open(img.miniature, '_blank'); };
                grid.appendChild(el);
            });
        }).catch(function(){ btn.innerHTML = '<i class="bi bi-exclamation-circle"></i> Ошибка'; btn.disabled = false; });
};
})();
</script>
