<?php

use yii\helpers\Html;
use yii\widgets\DetailView;
use yii\helpers\Url;

/** @var yii\web\View $this */
/** @var app\models\Product $product */

$this->title = $product->name;
$this->params['breadcrumbs'][] = ['label' => 'Товары', 'url' => ['products']];
$this->params['breadcrumbs'][] = $this->title;

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

// Объединяем keywords с meta_keywords (устраняем дублирование)
$allKeywords = [];
if (!empty($keywords)) {
    $allKeywords = array_merge($allKeywords, $keywords);
}
if ($product->meta_keywords) {
    $metaKeywordsArray = array_map('trim', explode(',', $product->meta_keywords));
    $allKeywords = array_merge($allKeywords, $metaKeywordsArray);
}
// Удаляем дубликаты и пустые значения
$allKeywords = array_unique(array_filter($allKeywords));
?>

<div class="product-view">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="mb-1"><?= Html::encode($this->title) ?></h1>
            <?php if ($product->poizon_id): ?>
                <span class="badge bg-info"><i class="bi bi-cloud-download"></i> Товар из Poizon</span>
            <?php endif; ?>
            <?php if ($product->is_limited): ?>
                <span class="badge bg-warning text-dark"><i class="bi bi-star-fill"></i> Limited Edition</span>
            <?php endif; ?>
            <?php if ($product->parent_product_id): ?>
                <span class="badge bg-secondary"><i class="bi bi-link"></i> Вариант товара</span>
            <?php endif; ?>
        </div>
        <div class="btn-group">
            <?= Html::a('<i class="bi bi-arrow-left"></i> Назад', ['products'], ['class' => 'btn btn-secondary']) ?>
            <?php if ($product->poizon_id): ?>
                <?= Html::a('<i class="bi bi-arrow-repeat"></i> Синхронизировать', ['sync-product', 'id' => $product->id], [
                    'class' => 'btn btn-info',
                    'data-method' => 'post',
                ]) ?>
            <?php endif; ?>
            <?= Html::a('<i class="bi bi-pencil"></i> Редактировать', ['edit-product', 'id' => $product->id], [
                'class' => 'btn btn-primary',
            ]) ?>
        </div>
    </div>

    <div class="row">
        <!-- Левая колонка: изображения -->
        <div class="col-md-4">
            <div class="card mb-3">
                <div class="card-header bg-light d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="bi bi-images"></i> Изображения</h5>
                    <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addImageModal">
                        <i class="bi bi-plus-circle"></i> Добавить
                    </button>
                </div>
                <div class="card-body">
                    <?php if ($product->images && count($product->images) > 0): ?>
                        <div class="row g-2">
                            <?php foreach ($product->images as $image): ?>
                                <div class="col-6">
                                    <div class="position-relative">
                                        <img src="<?= $image->getImageUrl() ?>" class="img-fluid rounded" alt="<?= Html::encode($product->name) ?>">
                                        <?php if ($image->is_main): ?>
                                            <span class="badge bg-success position-absolute top-0 start-0 m-2">Главное</span>
                                        <?php endif; ?>
                                        <div class="position-absolute top-0 end-0 m-2">
                                            <?php if (!$image->is_main): ?>
                                                <?= Html::a('<i class="bi bi-star"></i>', ['set-main-image', 'id' => $image->id], [
                                                    'class' => 'btn btn-sm btn-warning',
                                                    'title' => 'Сделать главным',
                                                    'data-method' => 'post',
                                                ]) ?>
                                            <?php endif; ?>
                                            <?= Html::a('<i class="bi bi-trash"></i>', ['delete-image', 'id' => $image->id], [
                                                'class' => 'btn btn-sm btn-danger',
                                                'title' => 'Удалить',
                                                'data-method' => 'post',
                                                'data-confirm' => 'Удалить это изображение?',
                                            ]) ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="text-center text-muted py-5">
                            <i class="bi bi-image" style="font-size: 48px;"></i>
                            <p class="mt-2">Нет изображений</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <?php if ($product->poizon_id): ?>
            <div class="card">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0"><i class="bi bi-cloud-download"></i> Данные Poizon</h5>
                </div>
                <div class="card-body">
                    <table class="table table-sm mb-0">
                        <tr>
                            <th>Poizon ID:</th>
                            <td><?= Html::encode($product->poizon_id) ?></td>
                        </tr>
                        <tr>
                            <th>SPU ID:</th>
                            <td><?= Html::encode($product->poizon_spu_id) ?></td>
                        </tr>
                        <?php if ($product->poizon_url): ?>
                        <tr>
                            <th>Ссылка:</th>
                            <td><?= Html::a('Открыть', $product->poizon_url, ['target' => '_blank']) ?></td>
                        </tr>
                        <?php endif; ?>
                        <tr>
                            <th>Цена CNY:</th>
                            <td><strong><?= $product->poizon_price_cny ? '¥' . number_format($product->poizon_price_cny, 2) : '-' ?></strong></td>
                        </tr>
                        <tr>
                            <th>Последняя синхр.:</th>
                            <td>
                                <?php if ($product->last_sync_at): ?>
                                    <?= Yii::$app->formatter->asDatetime($product->last_sync_at) ?>
                                <?php else: ?>
                                    <span class="text-danger">Не синхронизирован</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- Правая колонка: информация -->
        <div class="col-md-8">
            <!-- Основная информация -->
            <div class="card mb-3">
                <div class="card-header bg-light">
                    <h5 class="mb-0"><i class="bi bi-info-circle"></i> Основная информация</h5>
                </div>
                <div class="card-body">
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
                            ? '<span class="badge bg-success">Активен</span>' 
                            : '<span class="badge bg-secondary">Неактивен</span>',
                    ];
                    
                    if ($product->hasAttribute('is_limited')) {
                        $attributes[] = [
                            'attribute' => 'is_limited',
                            'format' => 'raw',
                            'value' => $product->is_limited 
                                ? '<span class="badge bg-warning">Limited Edition</span>' 
                                : 'Нет',
                        ];
                    }
                    
                    // Добавляем ключевые слова (объединенные)
                    if (!empty($allKeywords)) {
                        $attributes[] = [
                            'attribute' => 'meta_keywords',
                            'label' => 'Ключевые слова (SEO)',
                            'format' => 'raw',
                            'value' => implode(' ', array_map(function($kw) {
                                return '<span class="badge bg-secondary me-1 mb-1">' . Html::encode($kw) . '</span>';
                            }, $allKeywords)),
                        ];
                    }
                    ?>
                    
                    <?= DetailView::widget([
                        'model' => $product,
                        'attributes' => $attributes,
                    ]) ?>
                </div>
            </div>

            <!-- Характеристики товара (Единый блок) -->
            <div class="card mb-3">
                <div class="card-header d-flex justify-content-between align-items-center" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
                    <h5 class="mb-0"><i class="bi bi-list-check"></i> Характеристики товара</h5>
                    <button type="button" class="btn btn-sm btn-light" onclick="alert('Управление характеристиками в разработке')">
                        <i class="bi bi-pencil"></i> Редактировать
                    </button>
                </div>
                <div class="card-body">
                    <?php
                    // Получаем характеристики из справочников
                    $characteristicsFromRegistry = \app\models\ProductCharacteristicValue::find()
                        ->where(['product_id' => $product->id])
                        ->with(['characteristic', 'characteristicValue'])
                        ->all();
                    
                    // Получаем характеристики Poizon
                    $hasPoizonChars = !empty($properties);
                    
                    // Показываем объединенную таблицу, если есть данные
                    if (count($characteristicsFromRegistry) > 0 || $hasPoizonChars): ?>
                        <div class="table-responsive">
                            <table class="table table-sm table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th width="35%">Характеристика</th>
                                        <th>Значение</th>
                                        <th width="15%" class="text-center">Источник</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    // 1. Характеристики из справочников
                                    foreach ($characteristicsFromRegistry as $pcv): ?>
                                        <tr>
                                            <td><strong><?= Html::encode($pcv->characteristic->name) ?></strong></td>
                                            <td>
                                                <?php if ($pcv->characteristicValue): ?>
                                                    <span class="badge bg-primary"><?= Html::encode($pcv->characteristicValue->value) ?></span>
                                                <?php elseif ($pcv->value_text): ?>
                                                    <?= Html::encode($pcv->value_text) ?>
                                                <?php elseif ($pcv->value_number !== null): ?>
                                                    <?= Html::encode($pcv->value_number) ?>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-center">
                                                <span class="badge bg-success"><i class="bi bi-database"></i> Справочник</span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                    
                                    <?php
                                    // 2. Характеристики из Poizon
                                    if ($hasPoizonChars):
                                        foreach ($properties as $prop): ?>
                                            <tr>
                                                <td><strong><?= Html::encode($prop['key'] ?? '') ?></strong></td>
                                                <td><?= Html::encode($prop['value'] ?? '') ?></td>
                                                <td class="text-center">
                                                    <span class="badge bg-info"><i class="bi bi-cloud"></i> Poizon</span>
                                                </td>
                                            </tr>
                                        <?php endforeach;
                                    endif; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <!-- Фолбэк на старый формат -->
                        <div class="row">
                        <?php if ($product->hasAttribute('upper_material') && $product->upper_material): ?>
                        <div class="col-md-6 mb-3">
                            <div class="spec-item">
                                <span class="spec-label">Материал верха:</span>
                                <span class="spec-value"><?= Html::encode($product->upper_material) ?></span>
                            </div>
                        </div>
                        <?php endif; ?>

                        <?php if ($product->hasAttribute('sole_material') && $product->sole_material): ?>
                        <div class="col-md-6 mb-3">
                            <div class="spec-item">
                                <span class="spec-label">Материал подошвы:</span>
                                <span class="spec-value"><?= Html::encode($product->sole_material) ?></span>
                            </div>
                        </div>
                        <?php endif; ?>

                        <?php if ($product->hasAttribute('color_description') && $product->color_description): ?>
                        <div class="col-md-6 mb-3">
                            <div class="spec-item">
                                <span class="spec-label">Цвет:</span>
                                <span class="spec-value"><?= Html::encode($product->color_description) ?></span>
                            </div>
                        </div>
                        <?php endif; ?>

                        <?php if ($product->style_code): ?>
                        <div class="col-md-6 mb-3">
                            <div class="spec-item">
                                <span class="spec-label">Код модели:</span>
                                <span class="spec-value"><?= Html::encode($product->style_code) ?></span>
                            </div>
                        </div>
                        <?php endif; ?>

                        <?php if ($product->hasAttribute('release_year') && $product->release_year): ?>
                        <div class="col-md-6 mb-3">
                            <div class="spec-item">
                                <span class="spec-label">Год выпуска:</span>
                                <span class="spec-value"><?= Html::encode($product->release_year) ?></span>
                            </div>
                        </div>
                        <?php endif; ?>

                        <?php if ($product->hasAttribute('weight') && $product->weight): ?>
                        <div class="col-md-6 mb-3">
                            <div class="spec-item">
                                <span class="spec-label">Вес:</span>
                                <span class="spec-value"><?= Html::encode($product->weight) ?> г</span>
                            </div>
                        </div>
                        <?php endif; ?>

                        <?php if ($product->hasAttribute('material') && $product->material): ?>
                        <div class="col-md-6 mb-3">
                            <div class="spec-item">
                                <span class="spec-label">Материал:</span>
                                <span class="spec-value"><?= Html::encode($product->material) ?></span>
                            </div>
                        </div>
                        <?php endif; ?>

                        <?php if ($product->hasAttribute('season') && $product->season): ?>
                        <div class="col-md-6 mb-3">
                            <div class="spec-item">
                                <span class="spec-label">Сезон:</span>
                                <span class="spec-value"><?= Html::encode($product->season) ?></span>
                            </div>
                        </div>
                        <?php endif; ?>

                        <?php if ($product->hasAttribute('gender') && $product->gender): ?>
                        <div class="col-md-6 mb-3">
                            <div class="spec-item">
                                <span class="spec-label">Пол:</span>
                                <span class="spec-value"><?= Html::encode($product->gender) ?></span>
                            </div>
                        </div>
                        <?php endif; ?>

                        <?php if ($product->hasAttribute('country_of_origin') && $product->country_of_origin): ?>
                        <div class="col-md-6 mb-3">
                            <div class="spec-item">
                                <span class="spec-label">Страна производства:</span>
                                <span class="spec-value"><?= Html::encode($product->country_of_origin) ?></span>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                        </div>
                        <?php if (!$product->upper_material && !$product->sole_material && !$product->color_description && !$product->style_code): ?>
                        <div class="alert alert-info mb-0">
                            <i class="bi bi-info-circle"></i> Характеристики не заполнены. Добавьте их при редактировании товара.
                        </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Описание -->
            <?php if ($product->description): ?>
            <div class="card mb-3">
                <div class="card-header bg-light">
                    <h5 class="mb-0"><i class="bi bi-text-paragraph"></i> Описание</h5>
                </div>
                <div class="card-body">
                    <?= nl2br(Html::encode($product->description)) ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- БЛОК УДАЛЕН: Данные объединены с основными секциями -->

            <!-- Размеры -->
            <div class="card">
                <div class="card-header bg-light d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="bi bi-rulers"></i> Размеры</h5>
                    <div>
                        <?php if ($product->poizon_id): ?>
                            <span class="badge bg-info me-2">Синхронизация с Poizon</span>
                        <?php endif; ?>
                        <button type="button" class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#addSizeModal">
                            <i class="bi bi-plus-circle"></i> Добавить размер
                        </button>
                    </div>
                </div>
                <div class="card-body p-0">
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
                        <div class="table-responsive">
                            <table class="table table-sm table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>US</th>
                                        <th>EU</th>
                                        <th>UK</th>
                                        <th>CM</th>
                                        <th style="cursor: help;" title="Цена в юанях (CNY)">
                                            Цена ¥
                                            <i class="bi bi-info-circle-fill text-info"></i>
                                        </th>
                                        <th>Цена BYN</th>
                                        <th>Цена клиента</th>
                                        <?php if ($product->poizon_id): ?>
                                            <th>Poizon SKU</th>
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
                                        <td><strong><?= Html::encode($size->us_size ?: $size->size) ?></strong></td>
                                        <td><?= Html::encode($size->eu_size ?: '-') ?></td>
                                        <td><?= Html::encode($size->uk_size ?: '-') ?></td>
                                        <td><?= Html::encode($size->cm_size ?: '-') ?></td>
                                        <td>
                                            <?php if ($size->price_cny): ?>
                                                <span class="badge bg-info price-cny-badge" 
                                                      style="cursor: pointer;" 
                                                      onclick="copyToClipboard('<?= $size->price_cny ?>', this)"
                                                      title="Нажмите чтобы скопировать">
                                                    ¥<?= number_format($size->price_cny, 2) ?>
                                                    <i class="bi bi-clipboard ms-1"></i>
                                                </span>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($size->price_byn): ?>
                                                <?= number_format($size->price_byn, 2) ?> ₽
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($size->price_client_byn): ?>
                                                <strong class="text-success"><?= number_format($size->price_client_byn, 2) ?> ₽</strong>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <?php if ($product->poizon_id): ?>
                                            <td><small><?= Html::encode($size->poizon_sku_id ?: '-') ?></small></td>
                                            <td>
                                                <?php if ($size->poizon_stock > 0): ?>
                                                    <span class="badge bg-success"><?= $size->poizon_stock ?></span>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary">0</span>
                                                <?php endif; ?>
                                            </td>
                                        <?php endif; ?>
                                        <td>
                                            <?php if ($size->stock > 0): ?>
                                                <span class="badge bg-primary"><?= $size->stock ?></span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">0</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($size->is_available): ?>
                                                <span class="badge bg-success">Доступен</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">Недоступен</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="btn-group btn-group-sm" role="group">
                                                <button type="button" class="btn btn-outline-primary" 
                                                        data-bs-toggle="modal" 
                                                        data-bs-target="#editSizeModal<?= $size->id ?>"
                                                        title="Редактировать">
                                                    <i class="bi bi-pencil"></i>
                                                </button>
                                                <?= Html::a('<i class="bi bi-trash"></i>', ['delete-size', 'id' => $size->id], [
                                                    'class' => 'btn btn-outline-danger',
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
                        <div class="text-center text-muted py-4">
                            <i class="bi bi-rulers" style="font-size: 48px;"></i>
                            <p class="mt-2">Размеры не добавлены</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

</div>

<!-- Modal: Добавить изображение -->
<div class="modal fade" id="addImageModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-image"></i> Добавить изображение</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="<?= Url::to(['add-image', 'productId' => $product->id]) ?>" method="post">
                <?= Html::hiddenInput(Yii::$app->request->csrfParam, Yii::$app->request->csrfToken) ?>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">URL изображения <span class="text-danger">*</span></label>
                        <input type="url" name="image_url" class="form-control" required 
                               placeholder="https://example.com/image.jpg">
                        <div class="form-text">Введите прямую ссылку на изображение</div>
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

<!-- Modal: Добавить размер -->
<div class="modal fade" id="addSizeModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-rulers"></i> Добавить размер</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="<?= Url::to(['add-size', 'productId' => $product->id]) ?>" method="post">
                <?= Html::hiddenInput(Yii::$app->request->csrfParam, Yii::$app->request->csrfToken) ?>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-3">
                            <div class="mb-3">
                                <label class="form-label">US <span class="text-danger">*</span></label>
                                <input type="text" name="ProductSize[us_size]" class="form-control" required 
                                       placeholder="9.5">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="mb-3">
                                <label class="form-label">EU</label>
                                <input type="text" name="ProductSize[eu_size]" class="form-control" 
                                       placeholder="43">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="mb-3">
                                <label class="form-label">UK</label>
                                <input type="text" name="ProductSize[uk_size]" class="form-control" 
                                       placeholder="8.5">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="mb-3">
                                <label class="form-label">CM</label>
                                <input type="number" step="0.1" name="ProductSize[cm_size]" class="form-control" 
                                       placeholder="27.5">
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Размер (общий)</label>
                                <input type="text" name="ProductSize[size]" class="form-control" 
                                       placeholder="M, L, XL или 42">
                                <div class="form-text">Основной размер (обязательное поле)</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Остаток на складе</label>
                                <input type="number" name="ProductSize[stock]" class="form-control" 
                                       value="0" min="0">
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <div class="form-check">
                            <input type="checkbox" name="ProductSize[is_available]" value="1" 
                                   class="form-check-input" id="sizeAvailable" checked>
                            <label class="form-check-label" for="sizeAvailable">
                                Размер доступен для заказа
                            </label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                    <button type="submit" class="btn btn-success">Добавить размер</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modals: Редактирование размеров -->
<?php foreach ($product->getSizes()->all() as $size): ?>
<div class="modal fade" id="editSizeModal<?= $size->id ?>" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">
                    <i class="bi bi-pencil"></i> Редактировать размер: <?= Html::encode($size->us_size ?: $size->size) ?>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="<?= Url::to(['edit-size', 'id' => $size->id]) ?>" method="post">
                <?= Html::hiddenInput(Yii::$app->request->csrfParam, Yii::$app->request->csrfToken) ?>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-3">
                            <div class="mb-3">
                                <label class="form-label">US</label>
                                <input type="text" name="ProductSize[us_size]" class="form-control" 
                                       value="<?= Html::encode($size->us_size) ?>" placeholder="9.5">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="mb-3">
                                <label class="form-label">EU</label>
                                <input type="text" name="ProductSize[eu_size]" class="form-control" 
                                       value="<?= Html::encode($size->eu_size) ?>" placeholder="43">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="mb-3">
                                <label class="form-label">UK</label>
                                <input type="text" name="ProductSize[uk_size]" class="form-control" 
                                       value="<?= Html::encode($size->uk_size) ?>" placeholder="8.5">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="mb-3">
                                <label class="form-label">CM</label>
                                <input type="number" step="0.1" name="ProductSize[cm_size]" class="form-control" 
                                       value="<?= Html::encode($size->cm_size) ?>" placeholder="27.5">
                            </div>
                        </div>
                    </div>

                    <hr class="my-4">
                    <h6 class="mb-3"><i class="bi bi-currency-dollar"></i> Цены</h6>
                    
                    <div class="row">
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">Цена ¥ (CNY)</label>
                                <input type="number" step="0.01" name="ProductSize[price_cny]" class="form-control" 
                                       value="<?= Html::encode($size->price_cny) ?>" placeholder="490.00">
                                <div class="form-text">Цена в юанях</div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">Цена BYN (магазин)</label>
                                <input type="number" step="0.01" name="ProductSize[price_byn]" class="form-control" 
                                       value="<?= Html::encode($size->price_byn) ?>" placeholder="50.00">
                                <div class="form-text">Цена для магазина</div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">Цена для клиента</label>
                                <input type="number" step="0.01" name="ProductSize[price_client_byn]" class="form-control" 
                                       value="<?= Html::encode($size->price_client_byn) ?>" placeholder="65.00">
                                <div class="form-text">Итоговая цена</div>
                            </div>
                        </div>
                    </div>

                    <hr class="my-4">
                    <h6 class="mb-3"><i class="bi bi-box"></i> Остатки и статус</h6>
                    
                    <div class="row">
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">Размер (основной)</label>
                                <input type="text" name="ProductSize[size]" class="form-control" 
                                       value="<?= Html::encode($size->size) ?>" placeholder="M, L, XL или 42" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">Остаток на складе</label>
                                <input type="number" name="ProductSize[stock]" class="form-control" 
                                       value="<?= Html::encode($size->stock) ?>" min="0">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">Цвет</label>
                                <input type="text" name="ProductSize[color]" class="form-control" 
                                       value="<?= Html::encode($size->color) ?>" placeholder="Черный/Белый">
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <div class="form-check form-switch">
                            <input type="checkbox" name="ProductSize[is_available]" value="1" 
                                   class="form-check-input" id="sizeAvailable<?= $size->id ?>" 
                                   <?= $size->is_available ? 'checked' : '' ?>>
                            <label class="form-check-label" for="sizeAvailable<?= $size->id ?>">
                                <strong>Размер доступен для заказа</strong>
                            </label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-x-circle"></i> Отмена
                    </button>
                    <button type="submit" class="btn btn-success">
                        <i class="bi bi-check-circle"></i> Сохранить изменения
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endforeach; ?>

<style>
.spec-item {
    display: flex;
    flex-direction: column;
    padding: 12px;
    background: #f8f9fa;
    border-radius: 8px;
    border-left: 3px solid #667eea;
}

.spec-label {
    font-size: 12px;
    color: #6c757d;
    text-transform: uppercase;
    font-weight: 600;
    letter-spacing: 0.5px;
    margin-bottom: 4px;
}

.spec-value {
    font-size: 15px;
    color: #212529;
    font-weight: 500;
}

.card {
    border: none;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    transition: all 0.3s ease;
}

.card:hover {
    box-shadow: 0 4px 16px rgba(0,0,0,0.12);
}

.card-header {
    border-bottom: none;
    font-weight: 600;
}

.price-cny-badge:hover {
    transform: scale(1.05);
    transition: all 0.2s ease;
}

.badge {
    padding: 6px 12px;
    font-weight: 500;
}
</style>

<script>
// Копирование цены в юанях в буфер обмена
function copyToClipboard(text, element) {
    // Используем Clipboard API
    if (navigator.clipboard) {
        navigator.clipboard.writeText(text).then(function() {
            // Показываем уведомление
            const originalHTML = element.innerHTML;
            element.innerHTML = '✓ Скопировано!';
            element.classList.remove('bg-info');
            element.classList.add('bg-success');
            
            setTimeout(function() {
                element.innerHTML = originalHTML;
                element.classList.remove('bg-success');
                element.classList.add('bg-info');
            }, 1500);
        }).catch(function(err) {
            console.error('Ошибка копирования:', err);
            alert('Не удалось скопировать: ' + text);
        });
    } else {
        // Фолбэк для старых браузеров
        const tempInput = document.createElement('input');
        tempInput.value = text;
        document.body.appendChild(tempInput);
        tempInput.select();
        document.execCommand('copy');
        document.body.removeChild(tempInput);
        
        const originalHTML = element.innerHTML;
        element.innerHTML = '✓ Скопировано!';
        element.classList.add('bg-success');
        
        setTimeout(function() {
            element.innerHTML = originalHTML;
            element.classList.remove('bg-success');
            element.classList.add('bg-info');
        }, 1500);
    }
}
</script>
