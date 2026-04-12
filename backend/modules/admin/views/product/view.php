<?php

use yii\helpers\Html;
use yii\widgets\DetailView;
use yii\helpers\Url;

/** @var yii\web\View $this */
/** @var app\backend\modules\catalog\models\Product $product */

$this->title = $product->name;

// Парсим JSON данные
$properties = [];
$sizesData = [];
$keywords = [];
if ($product->properties) {
    $properties = json_decode($product->properties, true) ?: [];
}
if ($product->sizes_data) {
    $sizesData = json_decode($product->sizes_data, true) ?: [];
}
if ($product->keywords) {
    $keywords = json_decode($product->keywords, true) ?: [];
}

// Объединяем keywords с meta_keywords
$allKeywords = [];
if (!empty($keywords)) {
    $allKeywords = array_merge($allKeywords, $keywords);
}
if ($product->meta_keywords) {
    $metaKeywordsArray = array_map('trim', explode(',', $product->meta_keywords));
    $allKeywords = array_merge($allKeywords, $metaKeywordsArray);
}
$allKeywords = array_unique(array_filter($allKeywords));
?>

<?php
$actions = [
    Html::a('<i class="bi bi-arrow-left"></i> Назад', ['/admin/product/index'], ['class' => 'admin-btn admin-btn-secondary admin-btn-sm']),
    Html::a('<i class="bi bi-download"></i> Импорт', ['/admin/import/upload'], ['class' => 'admin-btn admin-btn-success admin-btn-sm'])
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
        <div class="admin-stat-value"><?= Yii::$app->formatter->asCurrency($product->price, 'BYN') ?></div>
        <div class="admin-stat-label">Цена</div>
    </div>
    <div class="admin-stat">
        <div class="admin-stat-value"><?= $product->orders_count ?? 0 ?></div>
        <div class="admin-stat-label">Продано</div>
    </div>
    <div class="admin-stat">
        <div class="admin-stat-value"><?= Yii::$app->formatter->asDatetime($product->updated_at) ?></div>
        <div class="admin-stat-label">Обновлён</div>
    </div>
</div>

<div style="display: grid; grid-template-columns: 1fr 2fr; gap: 1.5rem;">
    <!-- Левая колонка: изображения -->
    <div>
        <div class="admin-card">
            <h2 class="admin-card-title">
                <i class="bi bi-images"></i>
                Изображения
                <button type="button" class="admin-btn admin-btn-primary admin-btn-sm" style="float: right;" data-bs-toggle="modal" data-bs-target="#addImageModal">
                    <i class="bi bi-plus-circle"></i>
                    Добавить
                </button>
            </h2>
            
            <div class="admin-card-content">
                <?php if ($product->images && count($product->images) > 0): ?>
                    <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 0.5rem;">
                        <?php foreach ($product->images as $image): ?>
                            <div style="position: relative;">
                                <img src="<?= $image->getImageUrl() ?>" style="width: 100%; border-radius: 0.5rem;" alt="<?= Html::encode($product->name) ?>">
                                <?php if ($image->is_main): ?>
                                    <span class="admin-badge admin-badge-success" style="position: absolute; top: 0.5rem; left: 0.5rem;">Главное</span>
                                <?php endif; ?>
                                <div style="position: absolute; top: 0.5rem; right: 0.5rem; display: flex; gap: 0.25rem;">
                                    <?php if (!$image->is_main): ?>
                                        <a href="<?= Url::to(['/admin/product/set-main-image', 'id' => $image->id]) ?>" class="admin-btn admin-btn-warning admin-btn-xs" data-method="post" title="Сделать главным">
                                            <i class="bi bi-star"></i>
                                        </a>
                                    <?php endif; ?>
                                    <a href="<?= Url::to(['/admin/product/delete-image', 'id' => $image->id]) ?>" class="admin-btn admin-btn-danger admin-btn-xs" data-method="post" title="Удалить">
                                        <i class="bi bi-trash"></i>
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div style="text-align: center; padding: 2rem; color: var(--admin-text-secondary);">
                        <i class="bi bi-image" style="font-size: 3rem;"></i>
                        <p style="margin-top: 1rem;">Нет изображений</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <?php if ($product->poizon_id): ?>
        <div class="admin-card" style="margin-top: 1.5rem;">
            <h2 class="admin-card-title">
                <i class="bi bi-cloud-download"></i>
                Данные Poizon
            </h2>
            
            <div class="admin-card-content">
                <table class="admin-table">
                    <tr>
                        <td style="font-weight: 600;">Poizon ID:</td>
                        <td><?= Html::encode($product->poizon_id) ?></td>
                    </tr>
                    <tr>
                        <td style="font-weight: 600;">SPU ID:</td>
                        <td><?= Html::encode($product->poizon_spu_id) ?></td>
                    </tr>
                    <?php if ($product->poizon_url): ?>
                    <tr>
                        <td style="font-weight: 600;">Ссылка:</td>
                        <td><?= Html::a('Открыть', $product->poizon_url, ['target' => '_blank']) ?></td>
                    </tr>
                    <?php endif; ?>
                    <tr>
                        <td style="font-weight: 600;">Цена CNY:</td>
                        <td><strong><?= $product->poizon_price_cny ? '¥' . number_format($product->poizon_price_cny, 2) : '-' ?></strong></td>
                    </tr>
                    <tr>
                        <td style="font-weight: 600;">Последняя синхр.:</td>
                        <td>
                            <?php if ($product->last_sync_at): ?>
                                <?= Yii::$app->formatter->asDatetime($product->last_sync_at) ?>
                            <?php else: ?>
                                <span style="color: var(--admin-danger);">Не синхронизирован</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                </table>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Правая колонка: информация -->
    <div>
        <!-- Основная информация -->
        <div class="admin-card">
            <h2 class="admin-card-title">
                <i class="bi bi-info-circle"></i>
                Основная информация
            </h2>
            
            <div class="admin-card-content">
                <?php 
                $attributes = [
                    'id',
                    'name',
                    'sku',
                ];
                
                if ($product->hasAttribute('vendor_code')) {
                    $attributes[] = 'vendor_code';
                }
                
                $attributes = array_merge($attributes, [
                    [
                        'attribute' => 'brand_id',
                        'value' => $product->brand ? $product->brand->name : '-',
                    ],
                    [
                        'attribute' => 'category_id',
                        'value' => $product->category ? $product->category->name : '-',
                    ],
                    [
                        'attribute' => 'price',
                        'format' => 'raw',
                        'value' => '<strong>' . ($product->price ? number_format($product->price, 2) : '0.00') . ' BYN</strong>',
                    ],
                ]);
                
                if ($product->hasAttribute('purchase_price') && $product->purchase_price > 0) {
                    $attributes[] = [
                        'attribute' => 'purchase_price',
                        'format' => 'raw',
                        'value' => ($product->purchase_price ? number_format($product->purchase_price, 2) : '0.00') . ' BYN',
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
                        'value' => implode(' ', array_map(function($kw) {
                            return '<span class="admin-badge admin-badge-secondary me-1 mb-1">' . Html::encode($kw) . '</span>';
                        }, $allKeywords)),
                    ];
                }
                ?>
                
                <?= DetailView::widget([
                    'model' => $product,
                    'attributes' => $attributes,
                    'options' => ['class' => 'admin-table'],
                ]) ?>
            </div>
        </div>

        <!-- Характеристики товара -->
        <div class="admin-card" style="margin-top: 1.5rem;">
            <h2 class="admin-card-title">
                <i class="bi bi-list-check"></i>
                Характеристики товара
                <a href="<?= Url::to(['/admin/product/edit', 'id' => $product->id]) ?>#characteristics" class="admin-btn admin-btn-secondary admin-btn-sm" style="float: right;">
                    <i class="bi bi-pencil"></i>
                    Редактировать
                </a>
            </h2>
            
            <div class="admin-card-content">
                <?php
                $characteristicsFromRegistry = \app\backend\modules\catalog\models\ProductCharacteristicValue::find()
                    ->where(['product_id' => $product->id])
                    ->with(['characteristic', 'characteristicValue'])
                    ->all();
                
                $hasPoizonChars = !empty($properties);
                
                if (count($characteristicsFromRegistry) > 0 || $hasPoizonChars): ?>
                    <div style="overflow-x: auto;">
                        <table class="admin-table">
                            <thead>
                                <tr>
                                    <th style="width: 35%;">Характеристика</th>
                                    <th>Значение</th>
                                    <th style="width: 15%; text-align: center;">Источник</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($characteristicsFromRegistry as $pcv): ?>
                                    <tr>
                                        <td style="font-weight: 600;"><?= Html::encode($pcv->characteristic->name) ?></td>
                                        <td>
                                            <?php if ($pcv->characteristicValue): ?>
                                                <span class="admin-badge admin-badge-primary"><?= Html::encode($pcv->characteristicValue->value) ?></span>
                                            <?php elseif ($pcv->value_text): ?>
                                                <?= Html::encode($pcv->value_text) ?>
                                            <?php elseif ($pcv->value_number !== null): ?>
                                                <?= Html::encode($pcv->value_number) ?>
                                            <?php endif; ?>
                                        </td>
                                        <td style="text-align: center;">
                                            <span class="admin-badge admin-badge-success">
                                                <i class="bi bi-database"></i> Справочник
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                
                                <?php if ($hasPoizonChars): ?>
                                    <?php foreach ($properties as $prop): ?>
                                        <tr>
                                            <td style="font-weight: 600;"><?= Html::encode($prop['key'] ?? '') ?></td>
                                            <td><?= Html::encode($prop['value'] ?? '') ?></td>
                                            <td style="text-align: center;">
                                                <span class="admin-badge admin-badge-info">
                                                    <i class="bi bi-cloud"></i> Poizon
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div style="padding: 2rem; text-align: center; color: var(--admin-text-secondary);">
                        <i class="bi bi-info-circle" style="font-size: 2rem;"></i>
                        <p style="margin-top: 1rem;">Характеристики не заполнены. Добавьте их при редактировании товара.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Описание -->
        <?php if ($product->description): ?>
        <div class="admin-card" style="margin-top: 1.5rem;">
            <h2 class="admin-card-title">
                <i class="bi bi-text-paragraph"></i>
                Описание
            </h2>
            
            <div class="admin-card-content">
                <?= nl2br(Html::encode($product->description)) ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Размеры -->
<div class="admin-card" style="margin-top: 1.5rem;">
    <h2 class="admin-card-title">
        <i class="bi bi-rulers"></i>
        Размеры
        <div style="float: right;">
            <?php if ($product->poizon_id): ?>
                <span class="admin-badge admin-badge-info me-2">Синхронизация с Poizon</span>
            <?php endif; ?>
            <button type="button" class="admin-btn admin-btn-success admin-btn-sm" data-bs-toggle="modal" data-bs-target="#addSizeModal">
                <i class="bi bi-plus-circle"></i>
                Добавить размер
            </button>
        </div>
    </h2>
    
    <div class="admin-card-content">
        <?php 
        $sizes = $product->getSizes()
            ->orderBy([
                'sort_order' => SORT_ASC,
                'CAST(us_size AS DECIMAL)' => SORT_ASC,
                'us_size' => SORT_ASC
            ])
            ->all();
        if (count($sizes) > 0): 
        ?>
            <div style="overflow-x: auto;">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>US</th>
                            <th>EU</th>
                            <th>UK</th>
                            <th>CM</th>
                            <th style="cursor: help;" title="Цена в юанях (CNY)">Цена ¥ <i class="bi bi-info-circle-fill text-info"></i></th>
                            <th>Цена BYN</th>
                            <th>Цена клиента</th>
                            <?php if ($product->poizon_id): ?>
                                <th>Poizon SKU</th>
                                <th>Артикул варианта</th>
                                <th>Фото варианта</th>
                                <th>Остаток Poizon</th>
                            <?php endif; ?>
                            <th>Остаток</th>
                            <th>Статус</th>
                            <th>Действия</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($sizes as $size): ?>
                        <tr>
                            <td style="font-weight: 600;"><?= Html::encode($size->us_size ?: $size->size) ?></td>
                            <td><?= Html::encode($size->eu_size ?: '-') ?></td>
                            <td><?= Html::encode($size->uk_size ?: '-') ?></td>
                            <td><?= Html::encode($size->cm_size ?: '-') ?></td>
                            <td>
                                <?php if ($size->price_cny): ?>
                                    <span class="admin-badge admin-badge-info" style="cursor: pointer;" onclick="copyToClipboard('<?= $size->price_cny ?>', this)" title="Нажмите чтобы скопировать">
                                        ¥<?= number_format($size->price_cny, 2) ?>
                                        <i class="bi bi-clipboard ms-1"></i>
                                    </span>
                                <?php else: ?>
                                    <span style="color: var(--admin-text-secondary);">-</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($size->price_byn): ?>
                                    <?= number_format($size->price_byn, 2) ?> ₽
                                <?php else: ?>
                                    <span style="color: var(--admin-text-secondary);">-</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($size->price_client_byn): ?>
                                    <strong style="color: var(--admin-success);"><?= number_format($size->price_client_byn, 2) ?> ₽</strong>
                                <?php else: ?>
                                    <span style="color: var(--admin-text-secondary);">-</span>
                                <?php endif; ?>
                            </td>
                            <?php if ($product->poizon_id): ?>
                                <td><small><?= Html::encode($size->poizon_sku_id ?: '-') ?></small></td>
                                <td>
                                    <?php if ($size->variant_vendor_code): ?>
                                        <code style="color: var(--admin-primary);"><?= Html::encode($size->variant_vendor_code) ?></code>
                                    <?php else: ?>
                                        <span style="color: var(--admin-text-secondary);">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php 
                                    $variantImages = $size->images_json ? json_decode($size->images_json, true) : [];
                                    if (!empty($variantImages)): ?>
                                        <button type="button" class="admin-btn admin-btn-secondary admin-btn-xs" data-bs-toggle="modal" data-bs-target="#imagesModal<?= $size->id ?>">
                                            <i class="bi bi-images"></i> <?= count($variantImages) ?>
                                        </button>
                                    <?php else: ?>
                                        <span style="color: var(--admin-text-secondary);">-</span>
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
                                <?php if ($size->stock > 0): ?>
                                    <span class="admin-badge admin-badge-primary"><?= $size->stock ?></span>
                                <?php else: ?>
                                    <span class="admin-badge admin-badge-secondary">0</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($size->is_available): ?>
                                    <span class="admin-badge admin-badge-success">Доступен</span>
                                <?php else: ?>
                                    <span class="admin-badge admin-badge-secondary">Недоступен</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div style="display: flex; gap: 0.25rem;">
                                    <button type="button" class="admin-btn admin-btn-secondary admin-btn-xs" data-bs-toggle="modal" data-bs-target="#editSizeModal<?= $size->id ?>" title="Редактировать">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <?= Html::a('<i class="bi bi-trash"></i>', ['/admin/product/delete-size', 'id' => $size->id], [
                                        'class' => 'admin-btn admin-btn-danger admin-btn-xs',
                                        'title' => 'Удалить',
                                        'data-method' => 'post',
                                        'data-confirm' => 'Удалить размер ' . ($size->us_size ?: $size->size) . '?',
                                    ]) ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div style="padding: 2rem; text-align: center; color: var(--admin-text-secondary);">
                <i class="bi bi-rulers" style="font-size: 2rem;"></i>
                <p style="margin-top: 1rem;">Размеры не добавлены</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- B7.2 Quick Actions Bar -->
<?php $this->registerCss('.product-quick-bar{display:flex;gap:0.5rem;align-items:center;flex-wrap:wrap;padding:0.75rem 1rem;background:var(--admin-surface);border:1px solid var(--admin-border);border-radius:var(--admin-radius);margin-bottom:1.5rem}') ?>
<div class="product-quick-bar" style="margin-top:-0.5rem">
    <button class="admin-btn admin-btn-secondary admin-btn-sm" onclick="editPrice(<?= $product->id ?>, <?= $product->price ?? 0 ?>)">
        <i class="bi bi-pencil"></i> Изменить цену (<?= number_format($product->price ?? 0, 2) ?> BYN)
    </button>
    <button class="admin-btn admin-btn-<?= $product->is_active ? 'warning' : 'success' ?> admin-btn-sm" onclick="toggleActive(<?= $product->id ?>)">
        <i class="bi bi-toggle-<?= $product->is_active ? 'on' : 'off' ?>"></i> <?= $product->is_active ? 'Деактивировать' : 'Активировать' ?>
    </button>
    <a href="<?= Url::to(['/admin/order/create', 'product_id' => $product->id]) ?>" class="admin-btn admin-btn-primary admin-btn-sm">
        <i class="bi bi-bag-plus"></i> Создать заказ
    </a>
</div>

<!-- B7.6 Похожие товары -->
<?php
try {
    $similarProducts = \app\backend\modules\catalog\models\Product::find()
        ->where(['brand_id' => $product->brand_id, 'is_active' => true])
        ->andWhere(['!=', 'id', $product->id])
        ->limit(6)->all();
} catch (\Exception $e) { $similarProducts = []; }
if (!empty($similarProducts)):
?>
<div class="admin-card" style="margin-top:1.5rem">
    <h2 class="admin-card-title"><i class="bi bi-grid-3x3-gap"></i> Похожие товары</h2>
    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(150px,1fr));gap:1rem;padding:1rem">
        <?php foreach ($similarProducts as $sp): ?>
        <div style="border:1px solid var(--admin-border);border-radius:var(--admin-radius);overflow:hidden;text-align:center">
            <?php $spImg = $sp->getMainImageUrl(); if ($spImg): ?><img src="<?= Html::encode($spImg) ?>" style="width:100%;height:100px;object-fit:cover" alt="<?= Html::encode($sp->name) ?>"><?php endif ?>
            <div style="padding:0.5rem">
                <div style="font-size:0.8rem;font-weight:600;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"><?= Html::encode($sp->name) ?></div>
                <div style="font-size:0.75rem;color:var(--admin-text-secondary)"><?= number_format($sp->price ?? 0, 0) ?> BYN</div>
                <a href="<?= Url::to(['/admin/order/create', 'product_id' => $sp->id]) ?>" class="admin-btn admin-btn-primary admin-btn-sm" style="margin-top:0.5rem;width:100%;justify-content:center;display:inline-flex;font-size:0.7rem">
                    <i class="bi bi-bag-plus"></i> Заказ
                </a>
            </div>
        </div>
        <?php endforeach ?>
    </div>
</div>
<?php endif ?>
