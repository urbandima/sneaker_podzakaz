<?php

use yii\helpers\Html;
use yii\widgets\DetailView;
use yii\helpers\Url;

/** @var yii\web\View $this */
/** @var app\backend\modules\catalog\models\Product $product */

$this->title = $product->name;

$properties = [];
$keywords = [];
if ($product->properties) {
    $properties = is_array($product->properties) ? $product->properties : (json_decode($product->properties, true) ?: []);
}
if ($product->keywords) {
    $keywords = is_array($product->keywords) ? $product->keywords : (json_decode($product->keywords, true) ?: []);
}

$allKeywords = [];
if (!empty($keywords)) {
    $allKeywords = array_merge($allKeywords, $keywords);
}
if ($product->meta_keywords) {
    $metaKeywordsArray = array_map('trim', explode(',', $product->meta_keywords));
    $allKeywords = array_merge($allKeywords, $metaKeywordsArray);
}
$allKeywords = array_unique(array_filter($allKeywords));

// Status translation map for orders
$orderStatusMap = [
    'created'             => 'Создан',
    'pending'             => 'Ожидает',
    'confirmed'           => 'Подтверждён',
    'paid'                => 'Оплачен',
    'confirmed_and_paid'  => 'Подтверждён и оплачен',
    'processing'          => 'В обработке',
    'shipped'             => 'Отправлен',
    'delivered'           => 'Доставлен',
    'cancelled'           => 'Отменён',
    'returned'            => 'Возврат',
    'refunded'            => 'Возвращён',
];
?>

<?php
$actions = [
    Html::a('<i class="bi bi-arrow-left"></i> Назад', ['/admin/product/index'], ['class' => 'admin-btn admin-btn-secondary admin-btn-sm']),
    Html::a('<i class="bi bi-download"></i> Импорт', ['/admin/import/upload'], ['class' => 'admin-btn admin-btn-success admin-btn-sm']),
];
if ($product->poizon_id) {
    $actions[] = Html::a('<i class="bi bi-arrow-clockwise"></i> Синхронизация', ['/admin/product/sync', 'id' => $product->id], ['class' => 'admin-btn admin-btn-info admin-btn-sm', 'data-method' => 'post']);
}
$this->params['headerActions'] = $actions;
?>

<!-- Краткая сводка -->
<div class="admin-stats">
    <div class="admin-stat">
        <div class="admin-stat-value">
            <span class="admin-badge admin-badge-<?= $product->is_active ? 'success' : 'secondary' ?>">
                <?= $product->is_active ? 'В продаже' : 'Неактивен' ?>
            </span>
        </div>
        <div class="admin-stat-label">Статус</div>
    </div>
    <div class="admin-stat">
        <div class="admin-stat-value"><?= number_format($product->price ?? 0, 2) ?> BYN</div>
        <div class="admin-stat-label">Цена</div>
    </div>
    <div class="admin-stat">
        <div class="admin-stat-value"><?= $product->favorite_count ?? 0 ?></div>
        <div class="admin-stat-label">В избранном</div>
    </div>
    <div class="admin-stat">
        <div class="admin-stat-value"><?= $product->views_count ?? 0 ?></div>
        <div class="admin-stat-label">Просмотров</div>
    </div>
</div>

<!-- Quick Actions Bar — перед основным контентом -->
<?php $this->registerCss('.product-quick-bar{display:flex;gap:0.5rem;align-items:center;flex-wrap:wrap;padding:0.75rem 1rem;background:var(--admin-surface);border:1px solid var(--admin-border);border-radius:var(--admin-radius);margin-bottom:1.5rem}') ?>
<div class="product-quick-bar">
    <button class="admin-btn admin-btn-secondary admin-btn-sm" onclick="openEditPrice(<?= $product->id ?>, <?= (float)($product->price ?? 0) ?>)">
        <i class="bi bi-pencil"></i> Изменить цену (<?= number_format($product->price ?? 0, 2) ?> BYN)
    </button>
    <button class="admin-btn admin-btn-<?= $product->is_active ? 'warning' : 'success' ?> admin-btn-sm" onclick="toggleActive(<?= $product->id ?>)">
        <i class="bi bi-toggle-<?= $product->is_active ? 'on' : 'off' ?>"></i> <?= $product->is_active ? 'Деактивировать' : 'Активировать' ?>
    </button>
    <a href="<?= Url::to(['/admin/order/create', 'product_id' => $product->id]) ?>" class="admin-btn admin-btn-primary admin-btn-sm">
        <i class="bi bi-bag-plus"></i> Создать заказ
    </a>
    <a href="<?= Url::to(['/admin/product/duplicate', 'id' => $product->id]) ?>" class="admin-btn admin-btn-secondary admin-btn-sm" data-method="post" data-confirm="Дублировать товар?">
        <i class="bi bi-copy"></i> Дублировать
    </a>
    <a href="<?= Url::to(['/admin/product/edit', 'id' => $product->id]) ?>" class="admin-btn admin-btn-secondary admin-btn-sm">
        <i class="bi bi-pencil-square"></i> Редактировать
    </a>
</div>

<!-- 60/40 split: LEFT — медиа+характеристики+размеры, RIGHT — инфо+МС -->
<div style="display:grid;grid-template-columns:3fr 2fr;gap:1.5rem;align-items:start">

    <!-- ============================================================
         LEFT COLUMN (60%): Images · Description · Characteristics · Sizes
         ============================================================ -->
    <div>

        <!-- Изображения -->
        <div class="admin-card">
            <h2 class="admin-card-title">
                <i class="bi bi-images"></i>
                Изображения
                <button type="button" class="admin-btn admin-btn-primary admin-btn-sm" style="float:right" data-bs-toggle="modal" data-bs-target="#addImageModal">
                    <i class="bi bi-plus-circle"></i> Добавить
                </button>
            </h2>
            <div class="admin-card-content">
                <?php if ($product->images && count($product->images) > 0): ?>
                    <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:0.5rem">
                        <?php foreach ($product->images as $image): ?>
                            <div style="position:relative">
                                <img src="<?= $image->getImageUrl() ?>" style="width:100%;aspect-ratio:1;object-fit:cover;border-radius:0.5rem" alt="<?= Html::encode($product->name) ?>">
                                <?php if ($image->is_main): ?>
                                    <span class="admin-badge admin-badge-success" style="position:absolute;top:0.5rem;left:0.5rem;font-size:0.6rem">Главное</span>
                                <?php endif; ?>
                                <div style="position:absolute;top:0.5rem;right:0.5rem;display:flex;gap:0.25rem">
                                    <?php if (!$image->is_main): ?>
                                        <a href="<?= Url::to(['/admin/product/set-main-image', 'id' => $image->id]) ?>" class="admin-btn admin-btn-warning admin-btn-xs" data-method="post" title="Сделать главным">
                                            <i class="bi bi-star"></i>
                                        </a>
                                    <?php endif; ?>
                                    <a href="<?= Url::to(['/admin/product/delete-image', 'id' => $image->id]) ?>" class="admin-btn admin-btn-danger admin-btn-xs" data-method="post" title="Удалить" data-confirm="Удалить изображение?">
                                        <i class="bi bi-trash"></i>
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div style="text-align:center;padding:2.5rem;color:var(--admin-text-secondary)">
                        <i class="bi bi-image" style="font-size:3rem"></i>
                        <p style="margin-top:1rem">Нет изображений. Добавьте по URL.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <?php
        $msImagesJson = $product->ms_images_json ?? null;
        $msImagesData = $msImagesJson ? (is_array($msImagesJson) ? $msImagesJson : json_decode($msImagesJson, true)) : null;
        $hasCollectionUrl = !empty($msImagesData['__collection__']);
        if ($hasCollectionUrl):
        ?>
        <!-- Фото МойСклад -->
        <div class="admin-card" style="margin-top:1.5rem">
            <h2 class="admin-card-title">
                <i class="bi bi-cloud-download"></i>
                Фото МойСклад
            </h2>
            <div class="admin-card-content" id="ms-images-container">
                <div style="text-align:center;padding:1rem;color:var(--admin-text-secondary)">
                    <button class="admin-btn admin-btn-secondary admin-btn-sm" onclick="loadMsImages(<?= $product->id ?>)" id="ms-load-btn">
                        <i class="bi bi-cloud-download"></i> Загрузить фото из МС
                    </button>
                </div>
                <div id="ms-images-grid" style="display:none;grid-template-columns:repeat(3,1fr);gap:0.5rem"></div>
            </div>
        </div>
        <?php endif; ?>

        <?php if ($product->poizon_id): ?>
        <!-- Данные Poizon -->
        <div class="admin-card" style="margin-top:1.5rem">
            <h2 class="admin-card-title">
                <i class="bi bi-cloud-download"></i>
                Данные Poizon
            </h2>
            <div class="admin-card-content">
                <table class="admin-table">
                    <tr>
                        <td style="font-weight:600">Poizon ID:</td>
                        <td><?= Html::encode($product->poizon_id) ?></td>
                    </tr>
                    <tr>
                        <td style="font-weight:600">SPU ID:</td>
                        <td><?= Html::encode($product->poizon_spu_id) ?></td>
                    </tr>
                    <?php if ($product->poizon_url): ?>
                    <tr>
                        <td style="font-weight:600">Ссылка:</td>
                        <td><?= Html::a('Открыть', $product->poizon_url, ['target' => '_blank']) ?></td>
                    </tr>
                    <?php endif; ?>
                    <tr>
                        <td style="font-weight:600">Цена CNY:</td>
                        <td><strong><?= $product->poizon_price_cny ? '¥' . number_format($product->poizon_price_cny, 2) : '—' ?></strong></td>
                    </tr>
                    <tr>
                        <td style="font-weight:600">Последняя синхр.:</td>
                        <td>
                            <?php if ($product->last_sync_at): ?>
                                <?= date('d.m.Y H:i', $product->last_sync_at) ?>
                            <?php else: ?>
                                <span style="color:var(--admin-danger)">Не синхронизирован</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                </table>
            </div>
        </div>
        <?php endif; ?>

        <!-- Описание -->
        <?php if ($product->description): ?>
        <div class="admin-card" style="margin-top:1.5rem">
            <h2 class="admin-card-title">
                <i class="bi bi-text-paragraph"></i>
                Описание
            </h2>
            <div class="admin-card-content">
                <?= nl2br(Html::encode($product->description)) ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Характеристики товара -->
        <div class="admin-card" style="margin-top:1.5rem">
            <h2 class="admin-card-title">
                <i class="bi bi-list-check"></i>
                Характеристики товара
                <a href="<?= Url::to(['/admin/product/edit', 'id' => $product->id]) ?>#characteristics" class="admin-btn admin-btn-secondary admin-btn-sm" style="float:right">
                    <i class="bi bi-pencil"></i> Редактировать
                </a>
            </h2>
            <div class="admin-card-content">
                <?php
                try {
                    $characteristicsFromRegistry = \app\backend\modules\catalog\models\ProductCharacteristicValue::find()
                        ->where(['product_id' => $product->id])
                        ->with(['characteristic', 'characteristicValue'])
                        ->all();
                } catch (\Exception $e) {
                    $characteristicsFromRegistry = [];
                }

                $hasPoizonChars = !empty($properties);

                $_msCharRows = [];
                if ($product->brand_name)        $_msCharRows[] = ['label' => 'Бренд',              'value' => $product->brand_name,        'field' => 'brand_name'];
                if ($product->model_name)        $_msCharRows[] = ['label' => 'Модель',              'value' => $product->model_name,        'field' => 'model_name'];
                if ($product->gender)            $_msCharRows[] = ['label' => 'Пол',                 'value' => $product->gender,            'field' => null];
                if ($product->season)            $_msCharRows[] = ['label' => 'Сезон',               'value' => $product->season,            'field' => null];
                if ($product->height)            $_msCharRows[] = ['label' => 'Высота',              'value' => $product->height,            'field' => null];
                if ($product->upper_material)    $_msCharRows[] = ['label' => 'Материал верха',      'value' => $product->upper_material,    'field' => null];
                if ($product->color_description) $_msCharRows[] = ['label' => 'Цвет',               'value' => $product->color_description, 'field' => null];
                if ($product->ms_size_grid)      $_msCharRows[] = ['label' => 'Размерная сетка',     'value' => $product->ms_size_grid,      'field' => 'ms_size_grid'];
                if ($product->ms_purpose)        $_msCharRows[] = ['label' => 'Назначение',          'value' => $product->ms_purpose,        'field' => 'ms_purpose'];
                if ($product->ms_sole_height)    $_msCharRows[] = ['label' => 'Высота подошвы',      'value' => $product->ms_sole_height,    'field' => 'ms_sole_height'];
                if ($product->ms_sole_color)     $_msCharRows[] = ['label' => 'Цвет подошвы',        'value' => $product->ms_sole_color,     'field' => 'ms_sole_color'];
                if ($product->ms_inner_material) $_msCharRows[] = ['label' => 'Внутренний материал', 'value' => $product->ms_inner_material, 'field' => 'ms_inner_material'];

                $_msAttrs = $product->ms_attributes_json ? (is_array($product->ms_attributes_json) ? $product->ms_attributes_json : json_decode($product->ms_attributes_json, true)) : [];
                $hasMsChars = !empty($_msCharRows) || !empty($_msAttrs);

                if (count($characteristicsFromRegistry) > 0 || $hasPoizonChars || $hasMsChars): ?>
                    <div style="overflow-x:auto">
                        <table class="admin-table">
                            <thead>
                                <tr>
                                    <th style="width:35%">Характеристика</th>
                                    <th>Значение</th>
                                    <th style="width:15%;text-align:center">Источник</th>
                                </tr>
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
                                        <td style="text-align:center">
                                            <span class="admin-badge admin-badge-success"><i class="bi bi-database"></i> Справочник</span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>

                                <?php if ($hasPoizonChars): ?>
                                    <?php foreach ($properties as $prop): ?>
                                        <tr>
                                            <td style="font-weight:600"><?= Html::encode($prop['key'] ?? '') ?></td>
                                            <td><?= Html::encode($prop['value'] ?? '') ?></td>
                                            <td style="text-align:center">
                                                <span class="admin-badge admin-badge-info"><i class="bi bi-cloud"></i> Poizon</span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>

                                <?php foreach ($_msCharRows as $_mcr): ?>
                                    <tr>
                                        <td style="font-weight:600"><?= Html::encode($_mcr['label']) ?></td>
                                        <td>
                                            <?php if ($_mcr['field']): ?>
                                                <span class="inline-editable" data-entity="product" data-id="<?= $product->id ?>" data-field="<?= Html::encode($_mcr['field']) ?>"><?= Html::encode($_mcr['value']) ?></span>
                                            <?php else: ?>
                                                <?= Html::encode($_mcr['value']) ?>
                                            <?php endif; ?>
                                        </td>
                                        <td style="text-align:center">
                                            <span class="admin-badge" style="background:#dbeafe;color:#1e40af"><i class="bi bi-cloud-check"></i> MC</span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>

                                <?php foreach ($_msAttrs as $_ma): ?>
                                    <tr>
                                        <td style="font-weight:600"><?= Html::encode($_ma['name'] ?? '') ?></td>
                                        <td><?= Html::encode(is_array($_ma['value'] ?? '') ? ($_ma['value']['name'] ?? json_encode($_ma['value'])) : ($_ma['value'] ?? '')) ?></td>
                                        <td style="text-align:center">
                                            <span class="admin-badge" style="background:#dbeafe;color:#1e40af"><i class="bi bi-cloud-check"></i> MC</span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div style="padding:2rem;text-align:center;color:var(--admin-text-secondary)">
                        <i class="bi bi-info-circle" style="font-size:2rem"></i>
                        <p style="margin-top:1rem">Характеристики не заполнены. Добавьте при редактировании.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Размеры -->
        <div class="admin-card" style="margin-top:1.5rem">
            <h2 class="admin-card-title">
                <i class="bi bi-rulers"></i>
                Размеры
                <div style="float:right;display:flex;gap:0.5rem;align-items:center">
                    <?php if ($product->poizon_id): ?>
                        <span class="admin-badge admin-badge-info">Синхр. Poizon</span>
                    <?php endif; ?>
                    <button type="button" class="admin-btn admin-btn-success admin-btn-sm" data-bs-toggle="modal" data-bs-target="#addSizeModal">
                        <i class="bi bi-plus-circle"></i> Добавить размер
                    </button>
                </div>
            </h2>
            <div class="admin-card-content">
                <?php
                $sizes = $product->getSizes()
                    ->orderBy(['sort_order' => SORT_ASC])
                    ->addOrderBy(new \yii\db\Expression('CAST(`us_size` AS DECIMAL) ASC, `us_size` ASC'))
                    ->all();
                if (count($sizes) > 0):
                ?>
                    <div style="overflow-x:auto">
                        <table class="admin-table">
                            <thead>
                                <tr>
                                    <th>US</th>
                                    <th>EU</th>
                                    <th>UK</th>
                                    <th>CM</th>
                                    <th title="Цена в юанях (CNY)">Цена ¥ <i class="bi bi-info-circle-fill text-info"></i></th>
                                    <th title="Редактируемое поле">Цена BYN <i class="bi bi-pencil-fill" style="font-size:0.6rem;color:var(--admin-primary)"></i></th>
                                    <th>Цена клиента</th>
                                    <th title="МойСклад Variant ID">МС Вариант</th>
                                    <th title="Штрихкод МС">Штрихкод</th>
                                    <?php if ($product->poizon_id): ?>
                                        <th>Poizon SKU</th>
                                        <th>Артикул</th>
                                        <th>Фото</th>
                                        <th>Остаток Poizon</th>
                                    <?php endif; ?>
                                    <th title="Редактируемое поле">Остаток <i class="bi bi-pencil-fill" style="font-size:0.6rem;color:var(--admin-primary)"></i></th>
                                    <th>Статус</th>
                                    <th>Действия</th>
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
                                            <span class="admin-badge admin-badge-info" style="cursor:pointer" onclick="copyToClipboard('<?= $size->price_cny ?>', this)" title="Нажмите чтобы скопировать">
                                                ¥<?= number_format($size->price_cny, 2) ?> <i class="bi bi-clipboard ms-1"></i>
                                            </span>
                                        <?php else: ?>
                                            <span style="color:var(--admin-text-secondary)">—</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="inline-editable-size" data-id="<?= $size->id ?>" data-field="price_byn" data-type="number" title="Нажмите для редактирования">
                                            <?php if ($size->price_byn): ?>
                                                <?= number_format($size->price_byn, 2) ?> BYN
                                            <?php else: ?>
                                                <span style="color:var(--admin-text-secondary)">—</span>
                                            <?php endif; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($size->price_client_byn): ?>
                                            <strong style="color:var(--admin-success)"><?= number_format($size->price_client_byn, 2) ?> BYN</strong>
                                        <?php else: ?>
                                            <span style="color:var(--admin-text-secondary)">—</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if (!empty($size->ms_variant_id)): ?>
                                            <code style="font-size:0.65rem;color:#6b7280" title="<?= Html::encode($size->ms_variant_id) ?>"><?= Html::encode(substr($size->ms_variant_id, 0, 8)) ?>…</code>
                                        <?php else: ?>
                                            <span style="color:var(--admin-text-secondary)">—</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if (!empty($size->ms_barcode)): ?>
                                            <code style="font-size:0.7rem"><?= Html::encode($size->ms_barcode) ?></code>
                                        <?php else: ?>
                                            <span style="color:var(--admin-text-secondary)">—</span>
                                        <?php endif; ?>
                                    </td>
                                    <?php if ($product->poizon_id): ?>
                                        <td><small><?= Html::encode($size->poizon_sku_id ?: '—') ?></small></td>
                                        <td>
                                            <?php if ($size->variant_vendor_code): ?>
                                                <code style="color:var(--admin-primary)"><?= Html::encode($size->variant_vendor_code) ?></code>
                                            <?php else: ?>
                                                <span style="color:var(--admin-text-secondary)">—</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php
                                            $variantImages = $size->images_json ? (is_array($size->images_json) ? $size->images_json : json_decode($size->images_json, true)) : [];
                                            if (!empty($variantImages)): ?>
                                                <button type="button" class="admin-btn admin-btn-secondary admin-btn-xs" data-bs-toggle="modal" data-bs-target="#imagesModal<?= $size->id ?>">
                                                    <i class="bi bi-images"></i> <?= count($variantImages) ?>
                                                </button>
                                            <?php else: ?>
                                                <span style="color:var(--admin-text-secondary)">—</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($size->poizon_stock > 0): ?>
                                                <span class="admin-badge admin-badge-success"><?= $size->poizon_stock ?></span>
                                            <?php else: ?>
                                                <span class="admin-badge admin-badge-secondary">0</span>
                                            <?php endif; ?>
                                        </td>
                                    <?php endif; ?>
                                    <td>
                                        <span class="inline-editable-size" data-id="<?= $size->id ?>" data-field="stock" data-type="number" title="Нажмите для редактирования">
                                            <?php if ($size->stock > 0): ?>
                                                <span class="admin-badge admin-badge-primary"><?= $size->stock ?></span>
                                            <?php else: ?>
                                                <span class="admin-badge admin-badge-secondary">0</span>
                                            <?php endif; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($size->is_available): ?>
                                            <span class="admin-badge admin-badge-success" style="cursor:pointer" onclick="toggleSizeAvailable(<?= $size->id ?>, 0, this)">Доступен</span>
                                        <?php else: ?>
                                            <span class="admin-badge admin-badge-secondary" style="cursor:pointer" onclick="toggleSizeAvailable(<?= $size->id ?>, 1, this)">Недоступен</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?= Html::a('<i class="bi bi-trash"></i>', ['/admin/product/delete-size', 'id' => $size->id], [
                                            'class' => 'admin-btn admin-btn-danger admin-btn-xs',
                                            'title' => 'Удалить',
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
                    <div style="padding:2rem;text-align:center;color:var(--admin-text-secondary)">
                        <i class="bi bi-rulers" style="font-size:2rem"></i>
                        <p style="margin-top:1rem">Размеры не добавлены</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

    </div><!-- /LEFT COLUMN -->

    <!-- ============================================================
         RIGHT COLUMN (40%): Basic Info · МойСклад
         ============================================================ -->
    <div>

        <!-- Основная информация -->
        <div class="admin-card">
            <h2 class="admin-card-title">
                <i class="bi bi-info-circle"></i>
                Основная информация
            </h2>
            <div class="admin-card-content">
                <?php
                $attributes = ['id', 'name', 'sku'];

                if ($product->hasAttribute('vendor_code')) {
                    $attributes[] = 'vendor_code';
                }

                $attributes = array_merge($attributes, [
                    [
                        'attribute' => 'brand_id',
                        'value' => $product->brand ? $product->brand->name : '—',
                    ],
                    [
                        'attribute' => 'category_id',
                        'value' => $product->category ? $product->category->name : '—',
                    ],
                    [
                        'attribute' => 'price',
                        'format' => 'raw',
                        'value' => '<strong>' . ($product->price ? number_format($product->price, 2) . ' BYN' : '<span style="color:var(--admin-text-secondary)">Цена не задана</span>') . '</strong>',
                    ],
                ]);

                if ($product->hasAttribute('purchase_price') && $product->purchase_price > 0) {
                    $attributes[] = [
                        'attribute' => 'purchase_price',
                        'format' => 'raw',
                        'value' => number_format($product->purchase_price, 2) . ' BYN',
                    ];
                }

                $attributes[] = [
                    'attribute' => 'is_active',
                    'format' => 'raw',
                    'value' => $product->is_active
                        ? '<span class="admin-badge admin-badge-success">Активен</span>'
                        : '<span class="admin-badge admin-badge-secondary">Неактивен</span>',
                ];

                if ($product->hasAttribute('is_limited')) {
                    $attributes[] = [
                        'attribute' => 'is_limited',
                        'format' => 'raw',
                        'value' => $product->is_limited
                            ? '<span class="admin-badge admin-badge-warning">Limited Edition</span>'
                            : 'Нет',
                    ];
                }

                if (!empty($allKeywords)) {
                    $attributes[] = [
                        'attribute' => 'meta_keywords',
                        'label' => 'Ключевые слова (SEO)',
                        'format' => 'raw',
                        'value' => implode(' ', array_map(function ($kw) {
                            return '<span class="admin-badge admin-badge-secondary me-1 mb-1">' . Html::encode($kw) . '</span>';
                        }, $allKeywords)),
                    ];
                }
                ?>

                <?= DetailView::widget([
                    'model'      => $product,
                    'attributes' => $attributes,
                    'options'    => ['class' => 'admin-table'],
                ]) ?>
            </div>
        </div>

        <!-- МойСклад данные -->
        <?php if ($product->moysklad_id || $product->ms_code || $product->ms_external_code): ?>
        <div class="admin-card" style="margin-top:1.5rem">
            <h2 class="admin-card-title">
                <i class="bi bi-cloud-check"></i>
                МойСклад
                <?php if ($product->moysklad_id): ?>
                <a href="https://online.moysklad.ru/app/#good/edit?id=<?= Html::encode($product->moysklad_id) ?>" target="_blank" class="admin-btn admin-btn-secondary admin-btn-sm" style="float:right">
                    <i class="bi bi-box-arrow-up-right"></i> Открыть в МС
                </a>
                <?php endif; ?>
            </h2>
            <div class="admin-card-content">
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px 24px">
                    <?php if ($product->ms_code): ?>
                    <div><span style="font-size:0.75rem;color:var(--admin-text-secondary)">Код МС:</span> <code><?= Html::encode($product->ms_code) ?></code></div>
                    <?php endif; ?>
                    <?php if ($product->ms_external_code): ?>
                    <div><span style="font-size:0.75rem;color:var(--admin-text-secondary)">Внешний код:</span> <code><?= Html::encode($product->ms_external_code) ?></code></div>
                    <?php endif; ?>
                    <?php if ($product->ms_path_name): ?>
                    <div style="grid-column:span 2"><span style="font-size:0.75rem;color:var(--admin-text-secondary)">Группа:</span> <?= Html::encode($product->ms_path_name) ?></div>
                    <?php endif; ?>
                    <?php if ($product->ms_supplier_name): ?>
                    <div><span style="font-size:0.75rem;color:var(--admin-text-secondary)">Поставщик:</span> <strong><?= Html::encode($product->ms_supplier_name) ?></strong></div>
                    <?php endif; ?>
                    <?php if ($product->ms_volume): ?>
                    <div><span style="font-size:0.75rem;color:var(--admin-text-secondary)">Объём:</span> <?= Html::encode($product->ms_volume) ?></div>
                    <?php endif; ?>
                </div>

                <?php if ($product->ms_price_full || $product->ms_price_rub || $product->ms_price_sale || $product->ms_price_competitor || $product->ms_min_price): ?>
                <div style="margin-top:12px;border-top:1px solid var(--admin-border);padding-top:10px">
                    <div style="font-size:0.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.04em;color:var(--admin-text-secondary);margin-bottom:6px">Цены МС</div>
                    <div style="display:flex;flex-wrap:wrap;gap:10px;font-size:0.875rem">
                        <?php if ($product->ms_price_full): ?><div><span style="color:var(--admin-text-secondary)">Полная:</span> <strong><?= number_format($product->ms_price_full, 2) ?></strong></div><?php endif; ?>
                        <?php if ($product->ms_price_rub): ?><div><span style="color:var(--admin-text-secondary)">В руб.:</span> <strong><?= number_format($product->ms_price_rub, 2) ?> ₽</strong></div><?php endif; ?>
                        <?php if ($product->ms_price_sale): ?><div><span style="color:var(--admin-text-secondary)">Акц.:</span> <strong style="color:#059669"><?= number_format($product->ms_price_sale, 2) ?></strong></div><?php endif; ?>
                        <?php if ($product->ms_price_competitor): ?><div><span style="color:var(--admin-text-secondary)">Конкурент:</span> <?= number_format($product->ms_price_competitor, 2) ?></div><?php endif; ?>
                        <?php if ($product->ms_min_price): ?><div><span style="color:var(--admin-text-secondary)">Мин.:</span> <?= number_format($product->ms_min_price, 2) ?></div><?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>

                <?php
                $msFlags = [];
                if ($product->ms_archived)  $msFlags[] = '<span class="admin-badge" style="background:#fee2e2;color:#991b1b">Архив МС</span>';
                if ($product->ms_no_export) $msFlags[] = '<span class="admin-badge" style="background:#fef3c7;color:#92400e">Не экспортировать</span>';
                ?>
                <?php if (!empty($msFlags)): ?><div style="margin-top:8px;display:flex;gap:6px"><?= implode('', $msFlags) ?></div><?php endif; ?>

                <?php if ($product->ms_site_link): ?>
                <div style="margin-top:8px;font-size:0.8rem"><i class="bi bi-link-45deg"></i> <a href="<?= Html::encode($product->ms_site_link) ?>" target="_blank">Ссылка МС на сайте</a></div>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

    </div><!-- /RIGHT COLUMN -->

</div><!-- /60-40 GRID -->

<!-- ============================================================
     FULL-WIDTH BELOW: Orders · Similar products
     ============================================================ -->

<!-- Заказы с этим товаром -->
<?php
try {
    $productOrders = \app\backend\modules\checkout\models\OrderItem::find()
        ->where(['product_id' => $product->id])
        ->with(['order'])
        ->orderBy(['id' => SORT_DESC])
        ->limit(20)
        ->all();
} catch (\Exception $e) {
    $productOrders = [];
}
?>
<?php if (!empty($productOrders)): ?>
<div class="admin-card" style="margin-top:1.5rem">
    <h2 class="admin-card-title">
        <i class="bi bi-bag-check"></i>
        Заказы с этим товаром
        <span class="admin-badge admin-badge-secondary ms-2"><?= count($productOrders) ?></span>
    </h2>
    <div style="overflow-x:auto">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>№ Заказа</th>
                    <th>Размер</th>
                    <th>Кол-во</th>
                    <th>Цена</th>
                    <th>Статус заказа</th>
                    <th>Дата</th>
                </tr>
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
                        $stClass = in_array($st, ['delivered', 'paid', 'confirmed_and_paid']) ? 'success'
                            : (in_array($st, ['cancelled', 'returned', 'refunded']) ? 'danger' : 'secondary');
                        ?>
                        <span class="admin-badge admin-badge-<?= $stClass ?>"><?= Html::encode($stRu) ?></span>
                    </td>
                    <td style="font-size:0.8rem;color:var(--admin-text-secondary)"><?= date('d.m.Y', $oi->order->created_at) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<!-- Похожие товары -->
<?php
try {
    $similarProducts = \app\backend\modules\catalog\models\Product::find()
        ->where(['brand_id' => $product->brand_id, 'is_active' => true])
        ->andWhere(['!=', 'id', $product->id])
        ->andWhere(['>', 'price', 0])
        ->limit(6)
        ->all();
} catch (\Exception $e) {
    $similarProducts = [];
}
if (!empty($similarProducts)):
?>
<div class="admin-card" style="margin-top:1.5rem">
    <h2 class="admin-card-title"><i class="bi bi-grid-3x3-gap"></i> Похожие товары</h2>
    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(160px,1fr));gap:1rem;padding:1rem">
        <?php foreach ($similarProducts as $sp): ?>
        <div style="border:1px solid var(--admin-border);border-radius:var(--admin-radius);overflow:hidden;text-align:center">
            <?php $spImg = $sp->getMainImageUrl(); if ($spImg): ?>
                <img src="<?= Html::encode($spImg) ?>" style="width:100%;height:100px;object-fit:cover" alt="<?= Html::encode($sp->name) ?>">
            <?php else: ?>
                <div style="width:100%;height:100px;background:var(--admin-border-light,#f3f4f6);display:flex;align-items:center;justify-content:center">
                    <i class="bi bi-image" style="font-size:2rem;color:var(--admin-text-secondary)"></i>
                </div>
            <?php endif; ?>
            <div style="padding:0.5rem">
                <div style="font-size:0.8rem;font-weight:600;overflow:hidden;text-overflow:ellipsis;white-space:nowrap" title="<?= Html::encode($sp->name) ?>"><?= Html::encode($sp->name) ?></div>
                <div style="font-size:0.75rem;color:var(--admin-text-secondary);margin:2px 0"><?= number_format($sp->price, 2) ?> BYN</div>
                <div style="display:flex;gap:4px;margin-top:0.5rem">
                    <a href="<?= Url::to(['/admin/product/view', 'id' => $sp->id]) ?>" class="admin-btn admin-btn-secondary admin-btn-sm" style="flex:1;justify-content:center;display:inline-flex;font-size:0.7rem" title="Просмотр товара">
                        <i class="bi bi-eye"></i>
                    </a>
                    <a href="<?= Url::to(['/admin/order/create', 'product_id' => $sp->id]) ?>" class="admin-btn admin-btn-primary admin-btn-sm" style="flex:1;justify-content:center;display:inline-flex;font-size:0.7rem" title="Создать заказ">
                        <i class="bi bi-bag-plus"></i>
                    </a>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<!-- ============================================================
     MODALS
     ============================================================ -->

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
                    <div class="mb-3">
                        <label class="form-label">URL изображения</label>
                        <input type="url" name="image_url" class="form-control" placeholder="https://…" required>
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
                    <div class="row">
                        <div class="col-md-3 mb-3">
                            <label class="form-label">US</label>
                            <input type="text" name="us_size" class="form-control" required>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">EU</label>
                            <input type="text" name="eu_size" class="form-control">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">UK</label>
                            <input type="text" name="uk_size" class="form-control">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">CM</label>
                            <input type="text" name="cm_size" class="form-control">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Цена ¥</label>
                            <input type="number" step="0.01" min="0" name="price_cny" class="form-control">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Цена BYN</label>
                            <input type="number" step="0.01" min="0" name="price_byn" class="form-control">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Остаток</label>
                            <input type="number" min="0" name="stock" class="form-control" value="0">
                        </div>
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

<!-- Редактировать цену -->
<div class="modal fade" id="editPriceModal" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-pencil"></i> Изменить цену</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <label class="form-label fw-semibold">Новая цена (BYN)</label>
                <div class="input-group">
                    <input type="number" id="editPriceInput" class="form-control" step="0.01" min="0" placeholder="0.00">
                    <span class="input-group-text">BYN</span>
                </div>
                <div id="editPriceError" class="text-danger mt-1" style="display:none;font-size:0.8rem"></div>
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

<!-- Изображения вариантов размеров (Poizon) -->
<?php
foreach ($sizes as $size):
    $variantImages = $size->images_json ? (is_array($size->images_json) ? $size->images_json : json_decode($size->images_json, true)) : [];
    if (!empty($variantImages)):
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
                        <img src="<?= Html::encode($img) ?>" style="width:100%;border-radius:var(--admin-radius)" alt="Фото варианта">
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Закрыть</button>
            </div>
        </div>
    </div>
</div>
<?php
    endif;
endforeach;
?>

<?php $this->registerCss('
.inline-editable, .inline-editable-size {
    cursor: pointer;
    padding: 2px 6px;
    border-radius: 4px;
    transition: background 0.15s;
    display: inline-block;
    min-width: 30px;
}
.inline-editable:hover, .inline-editable-size:hover {
    background: rgba(59,130,246, 0.08);
    outline: 1px dashed var(--admin-primary, #3b82f6);
}
.inline-edit-input {
    border: 1px solid var(--admin-primary, #3b82f6);
    border-radius: 4px;
    padding: 2px 6px;
    font-size: inherit;
    font-family: inherit;
    width: 100%;
    max-width: 140px;
    outline: none;
    background: var(--admin-surface, #fff);
}
.inline-saved-indicator {
    display: inline-block;
    color: #059669;
    font-size: 0.75rem;
    font-weight: 700;
    margin-left: 4px;
    animation: fadeInOut 1.5s ease forwards;
}
@keyframes fadeInOut {
    0%   { opacity: 0; }
    15%  { opacity: 1; }
    70%  { opacity: 1; }
    100% { opacity: 0; }
}
'); ?>

<script>
var _inlineUpdateUrl = '<?= Url::to(['/admin/product/inline-update']) ?>';
var _updatePriceUrl  = '<?= Url::to(['/admin/product/update-price']) ?>';
var _csrfToken = '<?= Yii::$app->request->getCsrfToken() ?>';
var _editPriceProductId = null;

function inlineSave(entity, id, field, value, displayEl) {
    fetch(_inlineUpdateUrl, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-Token': _csrfToken,
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify({entity: entity, id: id, field: field, value: value})
    })
    .then(function(r) { return r.json(); })
    .then(function(res) {
        if (res.success) {
            var ind = document.createElement('span');
            ind.className = 'inline-saved-indicator';
            ind.textContent = '✓';
            displayEl.parentNode.insertBefore(ind, displayEl.nextSibling);
            setTimeout(function() { if (ind.parentNode) ind.parentNode.removeChild(ind); }, 1600);
        } else {
            alert('Ошибка: ' + (res.message || 'Неизвестная ошибка'));
        }
    })
    .catch(function() { alert('Ошибка сети'); });
}

// Inline edit — характеристики
document.addEventListener('click', function(e) {
    var el = e.target.closest('.inline-editable');
    if (!el || el.querySelector('input')) return;
    var entity = el.dataset.entity;
    var id = el.dataset.id;
    var field = el.dataset.field;
    var currentVal = el.textContent.trim();
    var input = document.createElement('input');
    input.type = 'text';
    input.className = 'inline-edit-input';
    input.value = currentVal;
    el.textContent = '';
    el.appendChild(input);
    input.focus();
    input.select();
    function commit() {
        var newVal = input.value.trim();
        el.textContent = newVal || currentVal;
        if (newVal !== currentVal) inlineSave(entity, id, field, newVal, el);
    }
    input.addEventListener('blur', commit);
    input.addEventListener('keydown', function(ev) {
        if (ev.key === 'Enter')  { ev.preventDefault(); input.blur(); }
        if (ev.key === 'Escape') { el.textContent = currentVal; }
    });
});

// Inline edit — размеры
document.addEventListener('click', function(e) {
    var el = e.target.closest('.inline-editable-size');
    if (!el || el.querySelector('input')) return;
    var id    = el.dataset.id;
    var field = el.dataset.field;
    var rawVal = el.textContent.replace(/[^0-9.\-]/g, '').trim();
    var input = document.createElement('input');
    input.type = 'number';
    input.step = (field === 'stock') ? '1' : '0.01';
    input.min  = '0';
    input.className = 'inline-edit-input';
    input.value = rawVal || '0';
    var origHtml = el.innerHTML;
    el.innerHTML = '';
    el.appendChild(input);
    input.focus();
    input.select();
    function commit() {
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
        if (newVal !== rawVal) inlineSave('size', id, field, newVal, el);
    }
    input.addEventListener('blur', commit);
    input.addEventListener('keydown', function(ev) {
        if (ev.key === 'Enter')  { ev.preventDefault(); input.blur(); }
        if (ev.key === 'Escape') { el.innerHTML = origHtml; }
    });
});

// Toggle размер available
function toggleSizeAvailable(sizeId, newVal, el) {
    inlineSave('size', sizeId, 'is_available', newVal, el);
    if (newVal) {
        el.className = 'admin-badge admin-badge-success';
        el.textContent = 'Доступен';
        el.setAttribute('onclick', 'toggleSizeAvailable(' + sizeId + ', 0, this)');
    } else {
        el.className = 'admin-badge admin-badge-secondary';
        el.textContent = 'Недоступен';
        el.setAttribute('onclick', 'toggleSizeAvailable(' + sizeId + ', 1, this)');
    }
}

// Копировать в буфер
function copyToClipboard(text, element) {
    navigator.clipboard.writeText(text).then(function() {
        var orig = element.innerHTML;
        element.innerHTML = '<i class="bi bi-check"></i> Скопировано';
        setTimeout(function() { element.innerHTML = orig; }, 1500);
    });
}

// Редактировать цену — открыть модал
function openEditPrice(productId, currentPrice) {
    _editPriceProductId = productId;
    var input = document.getElementById('editPriceInput');
    var err   = document.getElementById('editPriceError');
    input.value = currentPrice;
    err.style.display = 'none';
    var modal = new bootstrap.Modal(document.getElementById('editPriceModal'));
    modal.show();
    setTimeout(function() { input.focus(); input.select(); }, 300);
}

function doSavePrice() {
    var input = document.getElementById('editPriceInput');
    var err   = document.getElementById('editPriceError');
    var price = parseFloat(input.value);
    if (isNaN(price) || price < 0) {
        err.textContent = 'Введите корректную цену (≥ 0)';
        err.style.display = 'block';
        return;
    }
    var btn = document.getElementById('editPriceSaveBtn');
    btn.disabled = true;
    btn.innerHTML = '<i class="bi bi-hourglass-split"></i> Сохранение…';

    var params = new URLSearchParams();
    params.append('id', _editPriceProductId);
    params.append('price', price.toFixed(2));
    params.append('_csrf', _csrfToken);

    fetch(_updatePriceUrl, {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest'},
        body: params.toString()
    })
    .then(function(r) {
        if (r.ok) {
            bootstrap.Modal.getInstance(document.getElementById('editPriceModal')).hide();
            location.reload();
        } else {
            throw new Error('HTTP ' + r.status);
        }
    })
    .catch(function(e) {
        err.textContent = 'Ошибка сохранения: ' + e.message;
        err.style.display = 'block';
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-check-lg"></i> Сохранить';
    });
}

// Enter в поле цены → сохранить
document.getElementById('editPriceInput').addEventListener('keydown', function(e) {
    if (e.key === 'Enter') doSavePrice();
});

// Загрузка фото МойСклад
function loadMsImages(productId) {
    var btn  = document.getElementById('ms-load-btn');
    var grid = document.getElementById('ms-images-grid');
    btn.disabled = true;
    btn.innerHTML = '<i class="bi bi-hourglass-split"></i> Загрузка…';
    fetch('<?= \yii\helpers\Url::to(['/admin/moysklad/ms-images']) ?>?product_id=' + productId)
        .then(function(r) { return r.json(); })
        .then(function(images) {
            if (!images || images.length === 0) {
                btn.innerHTML = '<i class="bi bi-image"></i> Нет фото в МС';
                return;
            }
            btn.style.display = 'none';
            grid.style.display = 'grid';
            images.forEach(function(img) {
                var div = document.createElement('div');
                div.style.position = 'relative';
                var el = document.createElement('img');
                el.src = img.miniature;
                el.alt = img.filename || '';
                el.style.cssText = 'width:100%;border-radius:0.5rem;cursor:pointer';
                el.title = img.filename || '';
                el.onclick = function() { window.open(img.miniature, '_blank'); };
                div.appendChild(el);
                grid.appendChild(div);
            });
        })
        .catch(function() {
            btn.innerHTML = '<i class="bi bi-exclamation-circle"></i> Ошибка загрузки';
            btn.disabled = false;
        });
}

// Переключение активности
function toggleActive(productId) {
    if (confirm('Вы уверены?')) {
        fetch('<?= Url::to(['/admin/product/toggle', 'id' => $product->id]) ?>', {
            method: 'POST',
            headers: {'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-Token': _csrfToken}
        })
        .then(function(r) {
            if (r.ok) location.reload();
            else alert('Ошибка изменения статуса');
        })
        .catch(function() { alert('Ошибка сети'); });
    }
}
</script>
