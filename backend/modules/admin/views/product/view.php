<?php

use yii\helpers\Html;
use yii\helpers\Url;
use app\backend\shared\helpers\PriceHelper;
use app\backend\shared\helpers\AttributeMapper;
use app\backend\shared\helpers\OrderStatusHelper;

/** @var yii\web\View $this */
/** @var app\backend\modules\catalog\models\Product $product */

$this->title = $product->name;

// Parse JSON fields
$properties = $product->properties ? (json_decode($product->properties, true) ?: []) : [];
$allKeywords = [];
if ($product->keywords) {
    $kw = json_decode($product->keywords, true);
    if (is_array($kw)) $allKeywords = $kw;
}
if ($product->meta_keywords) {
    $mk = array_map('trim', explode(',', $product->meta_keywords));
    $allKeywords = array_unique(array_merge($allKeywords, array_filter($mk)));
}

// Load relations once
$sizes  = $product->getSizes()->orderBy(['CAST(us_size AS DECIMAL)' => SORT_ASC, 'us_size' => SORT_ASC])->all();
$images = $product->images ?: [];

// Deduplicate and normalise registry characteristics (P1)
$rawChars = \app\backend\modules\catalog\models\ProductCharacteristicValue::find()
    ->where(['product_id' => $product->id])
    ->with(['characteristic', 'characteristicValue'])
    ->all();
$seenCharIds = [];
$characteristics = [];
foreach ($rawChars as $pcv) {
    $cid = $pcv->characteristic_id;
    if (isset($seenCharIds[$cid])) continue;
    $seenCharIds[$cid] = true;
    $characteristics[] = $pcv;
}

// P4: SKU vs vendor_code
$hasBothSkuVendor = $product->hasAttribute('vendor_code')
    && $product->vendor_code
    && $product->sku
    && $product->vendor_code !== $product->sku;

// P13/P14: MoySklad numeric fields — only show if non-zero/non-null
$msNumericFields = [];
if ($product->hasAttribute('sale_price') && $product->sale_price > 0)
    $msNumericFields['Акционная цена'] = PriceHelper::format($product->sale_price);
if ($product->hasAttribute('competitor_price') && $product->competitor_price > 0)
    $msNumericFields['Цена конкурента'] = PriceHelper::format($product->competitor_price);
if ($product->hasAttribute('min_price') && $product->min_price > 0)
    $msNumericFields['Минимальная цена'] = PriceHelper::format($product->min_price);

// P16: Determine which size columns have at least one non-empty value
$hasSizeCol = [
    'us' => false, 'eu' => false, 'uk' => false, 'cm' => false,
    'price_cny' => false, 'price_byn' => false, 'poizon_sku' => false, 'poizon_stock' => false,
];
foreach ($sizes as $s) {
    if ($s->us_size)     $hasSizeCol['us']          = true;
    if ($s->eu_size)     $hasSizeCol['eu']          = true;
    if ($s->uk_size)     $hasSizeCol['uk']          = true;
    if ($s->cm_size)     $hasSizeCol['cm']          = true;
    if ($s->price_cny)   $hasSizeCol['price_cny']   = true;
    if ($s->price_byn)   $hasSizeCol['price_byn']   = true;
    if ($product->poizon_id && $s->poizon_sku_id) $hasSizeCol['poizon_sku'] = true;
    if ($product->poizon_id && $s->poizon_stock)  $hasSizeCol['poizon_stock'] = true;
}

// P9 sticky header is rendered via CSS below; register CSS/JS
$csrfToken = Yii::$app->request->csrfToken;
$updatePriceUrl = Url::to(['/admin/product/update-price']);
$toggleActiveUrl = Url::to(['/admin/product/toggle-active']);
$productId = $product->id;

$this->registerCssFile('@web/css/admin-product-view.css');
$this->registerJsFile('@web/js/admin-product-view.js', ['depends' => []]);

$this->registerJs(<<<JS
window.PRODUCT_VIEW = {
    id: {$productId},
    price: {$product->price},
    updatePriceUrl: '{$updatePriceUrl}',
    toggleActiveUrl: '{$toggleActiveUrl}',
    csrf: '{$csrfToken}'
};
JS, \yii\web\View::POS_HEAD);
?>
<?php /* P9: Sticky top header ============================================ */ ?>
<div class="pv-sticky-header" id="pv-sticky-header">
    <div class="pv-sticky-inner">
        <div class="pv-sticky-left">
            <?= Html::a('<i class="bi bi-arrow-left"></i>', ['/admin/product/index'], ['class' => 'pv-back-btn', 'title' => 'К списку товаров']) ?>
            <div>
                <div class="pv-product-name-header"><?= Html::encode($product->name) ?></div>
                <span class="admin-badge admin-badge-<?= $product->is_active ? 'success' : 'secondary' ?> pv-status-badge" id="pv-status-badge">
                    <?= $product->is_active ? 'В продаже' : 'Неактивен' ?>
                </span>
            </div>
        </div>
        <div class="pv-sticky-actions">
            <?= Html::a('<i class="bi bi-pencil-square"></i> Редактировать', ['/admin/product/edit', 'id' => $product->id], ['class' => 'admin-btn admin-btn-primary admin-btn-sm']) ?>
            <button class="admin-btn admin-btn-<?= $product->is_active ? 'warning' : 'success' ?> admin-btn-sm" id="pv-toggle-btn" onclick="pvToggleActive()">
                <i class="bi bi-toggle-<?= $product->is_active ? 'on' : 'off' ?>"></i>
                <span id="pv-toggle-text"><?= $product->is_active ? 'Деактивировать' : 'Активировать' ?></span>
            </button>
            <div class="pv-more-menu">
                <button class="admin-btn admin-btn-secondary admin-btn-sm" onclick="document.getElementById('pv-more-dropdown').classList.toggle('open')">
                    <i class="bi bi-three-dots-vertical"></i>
                </button>
                <div class="pv-more-dropdown" id="pv-more-dropdown">
                    <?= Html::a('<i class="bi bi-bag-plus"></i> Создать заказ', ['/admin/order/create', 'product_id' => $product->id], ['class' => 'pv-dd-item']) ?>
                    <?= Html::a('<i class="bi bi-files"></i> Дублировать', ['/admin/product/clone', 'id' => $product->id], ['class' => 'pv-dd-item', 'data-method' => 'post']) ?>
                    <?php if ($product->poizon_id): ?>
                    <button class="pv-dd-item" onclick="pvSyncPoizon()"><i class="bi bi-arrow-clockwise"></i> Синхронизировать</button>
                    <?php endif; ?>
                    <div class="pv-dd-divider"></div>
                    <?= Html::a('<i class="bi bi-trash text-danger"></i> <span class="text-danger">Удалить</span>', ['/admin/product/delete', 'id' => $product->id], ['class' => 'pv-dd-item', 'data-method' => 'post', 'data-confirm' => 'Удалить товар ' . Html::encode($product->name) . '?']) ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php /* 60/40 main layout ================================================== */ ?>
<div class="pv-layout">

    <?php /* ====== LEFT COLUMN (60%) ====================================== */ ?>
    <div class="pv-col-main">

        <?php /* P11: Unified Media block with tabs ========================= */ ?>
        <div class="admin-card pv-media-card">
            <div class="pv-card-header">
                <h2 class="admin-card-title"><i class="bi bi-images"></i> Медиа</h2>
                <button type="button" class="admin-btn admin-btn-secondary admin-btn-sm" data-bs-toggle="modal" data-bs-target="#addImageModal"
                        aria-label="Добавить фото" title="Добавить фото">
                    <i class="bi bi-plus-circle"></i> Добавить фото
                </button>
            </div>
            <?php
            $poizonImages = [];
            foreach ($sizes as $sz) {
                $sji = $sz->images_json ? json_decode($sz->images_json, true) : [];
                foreach ($sji as $url) { $poizonImages[$url] = true; }
            }
            $poizonImages = array_keys($poizonImages);
            ?>
            <div class="pv-media-tabs">
                <button class="pv-tab active" data-tab="all" onclick="pvTab(this,'all')">Все <span class="pv-tab-count"><?= count($images) + count($poizonImages) ?></span></button>
                <button class="pv-tab" data-tab="uploaded" onclick="pvTab(this,'uploaded')">Загруженные <span class="pv-tab-count"><?= count($images) ?></span></button>
                <?php if ($poizonImages): ?>
                <button class="pv-tab" data-tab="poizon" onclick="pvTab(this,'poizon')">Poizon <span class="pv-tab-count"><?= count($poizonImages) ?></span></button>
                <?php endif; ?>
            </div>

            <?php if ($images || $poizonImages): ?>
            <div class="pv-media-grid" id="pv-media-grid">
                <?php foreach ($images as $img): ?>
                <div class="pv-media-item" data-source="uploaded">
                    <img src="<?= Html::encode($img->getImageUrl()) ?>" alt="<?= Html::encode($product->name) ?>" loading="lazy">
                    <?php if ($img->is_main): ?>
                        <span class="pv-img-badge pv-img-badge-main">Главное</span>
                    <?php else: ?>
                        <?= Html::a('<i class="bi bi-star"></i>', ['/admin/product/set-main-image', 'id' => $img->id],
                            ['class' => 'pv-img-btn pv-img-btn-star', 'data-method' => 'post',
                             'title' => 'Сделать главным фото', 'aria-label' => 'Сделать главным фото']) ?>
                    <?php endif; ?>
                    <?= Html::a('<i class="bi bi-trash"></i>', ['/admin/product/delete-image', 'id' => $img->id],
                        ['class' => 'pv-img-btn pv-img-btn-del', 'data-method' => 'post',
                         'title' => 'Удалить фото', 'aria-label' => 'Удалить фото',
                         'data-confirm' => 'Удалить изображение?']) ?>
                </div>
                <?php endforeach; ?>
                <?php foreach ($poizonImages as $purl): ?>
                <div class="pv-media-item" data-source="poizon">
                    <img src="<?= Html::encode($purl) ?>" alt="Poizon" loading="lazy">
                    <span class="pv-img-badge pv-img-badge-poizon">Poizon</span>
                </div>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
            <div class="pv-empty-state"><i class="bi bi-image"></i><p>Нет изображений</p></div>
            <?php endif; ?>
        </div>

        <?php /* Description ============================================== */ ?>
        <?php if ($product->description): ?>
        <div class="admin-card">
            <h2 class="admin-card-title"><i class="bi bi-text-paragraph"></i> Описание</h2>
            <div class="admin-card-content">
                <?= nl2br(Html::encode($product->description)) ?>
            </div>
        </div>
        <?php endif; ?>

        <?php /* P16: Compact sizes table, hide empty columns ============= */ ?>
        <div class="admin-card">
            <div class="pv-card-header">
                <h2 class="admin-card-title"><i class="bi bi-rulers"></i> Размеры <?= count($sizes) ? '(' . count($sizes) . ')' : '' ?></h2>
                <button type="button" class="admin-btn admin-btn-success admin-btn-sm" data-bs-toggle="modal" data-bs-target="#addSizeModal">
                    <i class="bi bi-plus-circle"></i> Добавить
                </button>
            </div>
            <?php if ($sizes): ?>
            <div class="overflow-x-auto">
                <table class="admin-table pv-sizes-table">
                    <thead>
                        <tr>
                            <?php if ($hasSizeCol['us']): ?><th>US</th><?php endif; ?>
                            <?php if ($hasSizeCol['eu']): ?><th>EU</th><?php endif; ?>
                            <?php if ($hasSizeCol['uk']): ?><th>UK</th><?php endif; ?>
                            <?php if ($hasSizeCol['cm']): ?><th>CM</th><?php endif; ?>
                            <?php if ($hasSizeCol['price_cny']): ?><th>¥ CNY</th><?php endif; ?>
                            <?php if ($hasSizeCol['price_byn']): ?><th>BYN</th><?php endif; ?>
                            <?php if ($product->poizon_id && $hasSizeCol['poizon_sku']): ?>
                            <th class="pv-uuid-col" id="pv-uuid-th" style="display:none">
                                Poizon SKU
                                <button class="pv-uuid-toggle" onclick="pvToggleUUID()" title="Скрыть служебные поля">×</button>
                            </th>
                            <?php endif; ?>
                            <?php if ($product->poizon_id && $hasSizeCol['poizon_stock']): ?><th>Остаток ↗</th><?php endif; ?>
                            <th>Остаток</th>
                            <th>Статус</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($sizes as $sz): ?>
                        <tr>
                            <?php if ($hasSizeCol['us']): ?><td class="fw-600"><?= Html::encode($sz->us_size ?: $sz->size) ?></td><?php endif; ?>
                            <?php if ($hasSizeCol['eu']): ?><td><?= Html::encode($sz->eu_size ?: '—') ?></td><?php endif; ?>
                            <?php if ($hasSizeCol['uk']): ?><td><?= Html::encode($sz->uk_size ?: '—') ?></td><?php endif; ?>
                            <?php if ($hasSizeCol['cm']): ?><td><?= Html::encode($sz->cm_size ?: '—') ?></td><?php endif; ?>
                            <?php if ($hasSizeCol['price_cny']): ?><td><?= $sz->price_cny ? PriceHelper::cny($sz->price_cny) : '—' ?></td><?php endif; ?>
                            <?php if ($hasSizeCol['price_byn']): ?><td><?= $sz->price_byn ? PriceHelper::format($sz->price_byn) : '—' ?></td><?php endif; ?>
                            <?php if ($product->poizon_id && $hasSizeCol['poizon_sku']): ?>
                            <td class="pv-uuid-col" style="display:none">
                                <code style="font-size:0.75rem" title="<?= Html::encode($sz->poizon_sku_id) ?>"
                                      onclick="navigator.clipboard.writeText('<?= Html::encode($sz->poizon_sku_id) ?>')"
                                      style="cursor:pointer" aria-label="Скопировать UUID">
                                    <?= Html::encode(substr($sz->poizon_sku_id ?? '', 0, 8)) ?>…
                                </code>
                            </td>
                            <?php endif; ?>
                            <?php if ($product->poizon_id && $hasSizeCol['poizon_stock']): ?>
                            <td><span class="admin-badge admin-badge-<?= $sz->poizon_stock > 0 ? 'info' : 'secondary' ?>"><?= (int)$sz->poizon_stock ?></span></td>
                            <?php endif; ?>
                            <td><span class="admin-badge admin-badge-<?= $sz->stock > 0 ? 'primary' : 'secondary' ?>"><?= (int)$sz->stock ?></span></td>
                            <td><span class="admin-badge admin-badge-<?= $sz->is_available ? 'success' : 'secondary' ?>"><?= $sz->is_available ? 'Доступен' : 'Недоступен' ?></span></td>
                            <td>
                                <div style="display:flex;gap:0.25rem">
                                    <button class="admin-btn admin-btn-secondary admin-btn-xs" data-bs-toggle="modal"
                                            data-bs-target="#editSizeModal<?= $sz->id ?>"
                                            title="Редактировать размер" aria-label="Редактировать размер <?= Html::encode($sz->us_size ?: $sz->size) ?>">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <?= Html::a('<i class="bi bi-trash"></i>', ['/admin/product/delete-size', 'id' => $sz->id], [
                                        'class' => 'admin-btn admin-btn-danger admin-btn-xs',
                                        'title' => 'Удалить размер',
                                        'aria-label' => 'Удалить размер ' . Html::encode($sz->us_size ?: $sz->size),
                                        'data-method' => 'post',
                                        'data-confirm' => 'Удалить размер ' . ($sz->us_size ?: $sz->size) . '?',
                                    ]) ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php if ($product->poizon_id && $hasSizeCol['poizon_sku']): ?>
                <div style="margin-top:0.5rem">
                    <button class="admin-btn admin-btn-secondary admin-btn-xs" onclick="pvToggleUUID()">
                        <i class="bi bi-eye"></i> Показать служебные поля (UUID)
                    </button>
                </div>
                <?php endif; ?>
            </div>
            <?php else: ?>
            <div class="pv-empty-state"><i class="bi bi-rulers"></i><p>Размеры не добавлены</p></div>
            <?php endif; ?>
        </div>

        <?php /* P1/P3: Characteristics — deduped, boolean as toggle ====== */ ?>
        <div class="admin-card">
            <div class="pv-card-header">
                <h2 class="admin-card-title"><i class="bi bi-list-check"></i> Характеристики</h2>
                <?= Html::a('<i class="bi bi-pencil"></i>', ['/admin/product/edit', 'id' => $product->id, '#' => 'characteristics'],
                    ['class' => 'admin-btn admin-btn-secondary admin-btn-sm', 'title' => 'Редактировать характеристики']) ?>
            </div>
            <?php if ($characteristics || $properties): ?>
            <div class="overflow-x-auto">
                <table class="admin-table">
                    <thead><tr><th style="width:40%">Характеристика</th><th>Значение</th><th style="width:12%;text-align:center">Источник</th></tr></thead>
                    <tbody>
                        <?php foreach ($characteristics as $pcv):
                            $char = $pcv->characteristic;
                            $type = $char->type ?? 'text';
                        ?>
                        <tr>
                            <td class="fw-600"><?= Html::encode(AttributeMapper::canonical($char->name)) ?></td>
                            <td>
                                <?php if ($type === 'boolean'): ?>
                                    <span class="admin-badge admin-badge-<?= $pcv->value_boolean ? 'success' : 'secondary' ?>">
                                        <?= $pcv->value_boolean ? 'Да' : 'Нет' ?>
                                    </span>
                                <?php elseif ($pcv->characteristicValue): ?>
                                    <span class="admin-badge admin-badge-primary"><?= Html::encode($pcv->characteristicValue->value) ?></span>
                                <?php elseif ($pcv->value_text): ?>
                                    <?= Html::encode($pcv->value_text) ?>
                                <?php elseif ($pcv->value_number !== null): ?>
                                    <?= Html::encode($pcv->value_number) ?>
                                <?php else: ?>
                                    <span class="text-muted">—</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <span class="admin-badge admin-badge-success" title="Данные из справочника">
                                    <i class="bi bi-database"></i>
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; ?>

                        <?php
                        // Poizon properties — deduplicate against registry and synonyms
                        $usedPoizonKeys = [];
                        foreach ($characteristics as $pcv) {
                            $usedPoizonKeys[mb_strtolower(AttributeMapper::canonical($pcv->characteristic->name))] = true;
                        }
                        foreach ($properties as $prop):
                            $pName = $prop['key'] ?? '';
                            $canon = AttributeMapper::canonical($pName);
                            if (isset($usedPoizonKeys[mb_strtolower($canon)])) continue;
                            $usedPoizonKeys[mb_strtolower($canon)] = true;
                        ?>
                        <tr>
                            <td class="fw-600"><?= Html::encode($canon) ?></td>
                            <td><?= Html::encode($prop['value'] ?? '') ?></td>
                            <td class="text-center">
                                <span class="admin-badge admin-badge-info" title="Данные из Poizon">
                                    <i class="bi bi-cloud"></i>
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
            <div class="pv-empty-state"><i class="bi bi-list-check"></i><p>Характеристики не заполнены</p></div>
            <?php endif; ?>
        </div>

        <?php /* Orders with this product ==================================== */ ?>
        <?php
        try {
            $recentOrders = \app\backend\modules\checkout\models\Order::find()
                ->joinWith('orderItems')
                ->where(['like', 'order_item.product_name', $product->name])
                ->orderBy(['order.created_at' => SORT_DESC])
                ->limit(5)->all();
        } catch (\Exception $e) { $recentOrders = []; }
        if ($recentOrders):
        ?>
        <div class="admin-card">
            <h2 class="admin-card-title"><i class="bi bi-bag-check"></i> Заказы с этим товаром</h2>
            <table class="admin-table">
                <thead><tr><th>№ заказа</th><th>Клиент</th><th>Статус</th><th>Сумма</th><th>Дата</th></tr></thead>
                <tbody>
                    <?php foreach ($recentOrders as $o): ?>
                    <tr>
                        <td><?= Html::a('#' . Html::encode($o->order_number), ['/admin/order/view', 'id' => $o->id]) ?></td>
                        <td><?= Html::encode($o->client_name) ?></td>
                        <td><span class="admin-badge admin-badge-primary"><?= OrderStatusHelper::label($o->status) ?></span></td>
                        <td><?= PriceHelper::format($o->total_amount) ?></td>
                        <td><?= date('d.m.Y', $o->created_at) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>

    </div><!-- end pv-col-main -->

    <?php /* ====== RIGHT COLUMN (40%) ===================================== */ ?>
    <div class="pv-col-side">

        <?php /* P2: Inline price edit + P12: consistent price format ======= */ ?>
        <div class="admin-card pv-price-card">
            <h2 class="admin-card-title"><i class="bi bi-tag"></i> Цена и наличие</h2>
            <div class="pv-price-row">
                <div class="pv-price-display" id="pv-price-display">
                    <span class="pv-price-value" id="pv-price-value"><?= PriceHelper::format($product->price) ?></span>
                    <button class="pv-price-edit-btn" id="pv-price-edit-btn" onclick="pvEditPrice()"
                            title="Редактировать цену" aria-label="Редактировать цену">
                        <i class="bi bi-pencil"></i>
                    </button>
                </div>
                <div class="pv-price-input-row" id="pv-price-input-row" style="display:none">
                    <input type="number" id="pv-price-input" step="0.01" min="0" class="pv-inline-input"
                           value="<?= (float)$product->price ?>" aria-label="Новая цена">
                    <span>BYN</span>
                    <button class="admin-btn admin-btn-success admin-btn-xs" onclick="pvSavePrice()" id="pv-price-save"
                            title="Сохранить цену" aria-label="Сохранить цену">
                        <i class="bi bi-check"></i>
                    </button>
                    <button class="admin-btn admin-btn-secondary admin-btn-xs" onclick="pvCancelPrice()"
                            title="Отменить" aria-label="Отменить редактирование цены">
                        <i class="bi bi-x"></i>
                    </button>
                </div>
            </div>
            <?php if ($product->old_price && $product->old_price > $product->price): ?>
            <div class="pv-old-price">Старая цена: <s><?= PriceHelper::format($product->old_price) ?></s>
                <span class="admin-badge admin-badge-warning">-<?= $product->getDiscountPercent() ?>%</span>
            </div>
            <?php endif; ?>
            <?php if ($product->purchase_price > 0): ?>
            <div class="pv-purchase-price text-muted" style="font-size:0.8125rem">
                Закупка: <?= PriceHelper::format($product->purchase_price) ?>
            </div>
            <?php endif; ?>
            <div class="pv-stock-row">
                <span class="admin-badge admin-badge-<?= $product->is_active ? 'success' : 'secondary' ?>">
                    <?= $product->stock_status === 'in_stock' ? 'В наличии' : ($product->stock_status === 'out_of_stock' ? 'Нет в наличии' : 'Под заказ') ?>
                </span>
            </div>
        </div>

        <?php /* P20: Inline-editable main info ============================= */ ?>
        <div class="admin-card">
            <h2 class="admin-card-title"><i class="bi bi-info-circle"></i> Основная информация</h2>
            <table class="admin-table">
                <tr><td class="fw-600" style="width:40%">ID</td><td><?= $product->id ?></td></tr>
                <tr>
                    <td class="fw-600">Название</td>
                    <td>
                        <span class="pv-editable" data-field="name" data-type="text"
                              data-value="<?= Html::encode($product->name) ?>"><?= Html::encode($product->name) ?></span>
                    </td>
                </tr>
                <tr>
                    <td class="fw-600">SKU<?= !$hasBothSkuVendor ? ' / Артикул' : '' ?></td>
                    <td>
                        <code><?= Html::encode($product->sku) ?></code>
                        <?php if (!$hasBothSkuVendor && $product->vendor_code && $product->vendor_code === $product->sku): ?>
                            <span class="text-muted" style="font-size:0.75rem">(совпадает с артикулом)</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php if ($hasBothSkuVendor): ?>
                <tr>
                    <td class="fw-600">Артикул</td>
                    <td><code><?= Html::encode($product->vendor_code) ?></code></td>
                </tr>
                <?php endif; ?>
                <tr>
                    <td class="fw-600">Бренд</td>
                    <td><?= $product->brand ? Html::encode($product->brand->name) : '—' ?></td>
                </tr>
                <tr>
                    <td class="fw-600">Категория</td>
                    <td><?= $product->category ? Html::encode($product->category->name) : '—' ?></td>
                </tr>
                <tr>
                    <td class="fw-600">Обновлён</td>
                    <td><?= Yii::$app->formatter->asDatetime($product->updated_at) ?></td>
                </tr>
            </table>
        </div>

        <?php /* Poizon / МойСклад data ===================================== */ ?>
        <?php if ($product->poizon_id): ?>
        <div class="admin-card">
            <h2 class="admin-card-title"><i class="bi bi-cloud-download"></i> Данные Poizon</h2>
            <table class="admin-table">
                <tr><td class="fw-600">Poizon ID</td><td><?= Html::encode($product->poizon_id) ?></td></tr>
                <?php if ($product->poizon_spu_id): ?>
                <tr><td class="fw-600">SPU ID</td><td><?= Html::encode($product->poizon_spu_id) ?></td></tr>
                <?php endif; ?>
                <tr>
                    <td class="fw-600">Цена ¥</td>
                    <td><?= $product->poizon_price_cny ? PriceHelper::cny($product->poizon_price_cny) : '—' ?></td>
                </tr>
                <?php if ($product->poizon_url): ?>
                <tr>
                    <td class="fw-600">Ссылка</td>
                    <td>
                        <?= Html::a('<i class="bi bi-box-arrow-up-right"></i> Открыть', $product->poizon_url,
                            ['target' => '_blank', 'rel' => 'noopener', 'title' => 'Страница на Poizon (источнике)']) ?>
                    </td>
                </tr>
                <?php endif; ?>
                <tr>
                    <td class="fw-600">Последняя синхр.</td>
                    <td><?= $product->last_sync_at ? Yii::$app->formatter->asDatetime($product->last_sync_at) : '<span class="text-danger">Нет</span>' ?></td>
                </tr>
            </table>
            <?php /* P13-P14: hide zero MS numeric fields */ ?>
            <?php if ($msNumericFields): ?>
            <hr style="margin:0.75rem 0;border-color:var(--admin-border)">
            <table class="admin-table">
                <?php foreach ($msNumericFields as $label => $val): ?>
                <tr><td class="fw-600"><?= Html::encode($label) ?></td><td><?= Html::encode($val) ?></td></tr>
                <?php endforeach; ?>
            </table>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <?php /* P10: Stats block ========================================== */ ?>
        <div class="admin-card">
            <div class="pv-card-header">
                <h2 class="admin-card-title"><i class="bi bi-bar-chart"></i> Статистика</h2>
                <div class="pv-period-tabs">
                    <button class="pv-ptab active" data-period="week">7д</button>
                    <button class="pv-ptab" data-period="month">30д</button>
                    <button class="pv-ptab" data-period="all">Всё</button>
                </div>
            </div>
            <div class="pv-stats-grid">
                <?php
                $statsItems = [
                    ['label' => 'Просмотров', 'value' => $product->views_count ?? 0, 'icon' => 'bi-eye'],
                    ['label' => 'В избранном', 'value' => 0, 'icon' => 'bi-heart'],
                    ['label' => 'Заказов', 'value' => $product->orders_count ?? 0, 'icon' => 'bi-bag-check'],
                ];
                foreach ($statsItems as $stat): ?>
                <div class="pv-stat-item">
                    <i class="bi <?= $stat['icon'] ?> pv-stat-icon"></i>
                    <div class="pv-stat-value <?= $stat['value'] == 0 ? 'pv-stat-zero' : '' ?>"
                         title="<?= $stat['value'] == 0 ? 'Нет данных за период' : '' ?>">
                        <?= $stat['value'] ?: '—' ?>
                    </div>
                    <div class="pv-stat-label"><?= $stat['label'] ?></div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <?php /* Keywords ================================================= */ ?>
        <?php if ($allKeywords): ?>
        <div class="admin-card">
            <h2 class="admin-card-title"><i class="bi bi-tags"></i> Ключевые слова</h2>
            <div style="display:flex;flex-wrap:wrap;gap:0.25rem;padding:0.5rem 0">
                <?php foreach ($allKeywords as $kw): ?>
                <span class="admin-badge admin-badge-secondary"><?= Html::encode($kw) ?></span>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

    </div><!-- end pv-col-side -->
</div><!-- end pv-layout -->

<?php /* P6: Similar products — exclude zero price ========================= */ ?>
<?php
try {
    $similarProducts = \app\backend\modules\catalog\models\Product::find()
        ->where(['brand_id' => $product->brand_id, 'is_active' => true])
        ->andWhere(['!=', 'id', $product->id])
        ->andWhere(['>', 'price', 0])
        ->limit(6)->all();
} catch (\Exception $e) { $similarProducts = []; }
if ($similarProducts):
?>
<div class="admin-card" style="margin-top:1.5rem">
    <h2 class="admin-card-title"><i class="bi bi-grid-3x3-gap"></i> Похожие товары</h2>
    <div class="pv-similar-grid">
        <?php foreach ($similarProducts as $sp):
            $spImg = $sp->getMainImageUrl();
        ?>
        <div class="pv-similar-item">
            <?php if ($spImg): ?>
            <img src="<?= Html::encode($spImg) ?>" alt="<?= Html::encode($sp->name) ?>" loading="lazy">
            <?php endif; ?>
            <div class="pv-similar-body">
                <div class="pv-similar-name"><?= Html::encode($sp->name) ?></div>
                <div class="pv-similar-price"><?= PriceHelper::format($sp->price) ?></div>
                <?= Html::a('<i class="bi bi-eye"></i>', ['/admin/product/view', 'id' => $sp->id],
                    ['class' => 'admin-btn admin-btn-secondary admin-btn-xs', 'title' => 'Просмотр']) ?>
                <?= Html::a('<i class="bi bi-bag-plus"></i> Заказ', ['/admin/order/create', 'product_id' => $sp->id],
                    ['class' => 'admin-btn admin-btn-primary admin-btn-xs']) ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<?php /* Add image modal ================================================== */ ?>
<div class="modal fade" id="addImageModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title">Добавить изображение</h5></div>
            <form method="post" action="<?= Url::to(['/admin/product/add-image', 'id' => $product->id]) ?>">
                <?= Html::hiddenInput(Yii::$app->request->csrfParam, Yii::$app->request->csrfToken) ?>
                <div class="modal-body">
                    <input type="url" name="image_url" class="form-control" placeholder="https://..." required>
                </div>
                <div class="modal-footer">
                    <button type="button" class="admin-btn admin-btn-secondary" data-bs-dismiss="modal">Отмена</button>
                    <button type="submit" class="admin-btn admin-btn-primary">Добавить</button>
                </div>
            </form>
        </div>
    </div>
</div>
