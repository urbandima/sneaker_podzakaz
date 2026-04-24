<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/** @var yii\web\View $this */
/** @var app\backend\modules\catalog\models\Product $product */
/** @var array $brands */
/** @var array $categories */

$this->title = $product->isNewRecord ? 'Создание товара' : 'Редактирование: ' . $product->name;
$this->params['breadcrumbs'][] = ['label' => 'Товары', 'url' => ['/admin/product/index']];
if (!$product->isNewRecord) {
    $this->params['breadcrumbs'][] = ['label' => $product->name, 'url' => ['/admin/product/view', 'id' => $product->id]];
}
$this->params['breadcrumbs'][] = $product->isNewRecord ? 'Создание' : 'Редактирование';

// Парсим JSON данные если есть
$properties = [];
$keywords = [];
if ($product->properties) {
    $properties = is_array($product->properties) ? $product->properties : (json_decode($product->properties, true) ?: []);
}
if ($product->keywords) {
    $keywords = is_array($product->keywords) ? $product->keywords : (json_decode($product->keywords, true) ?: []);
}

// Объединяем keywords с meta_keywords для предзаполнения поля
$allKeywords = [];
if (!empty($keywords)) {
    $allKeywords = array_merge($allKeywords, $keywords);
}
if ($product->meta_keywords) {
    $metaKeywordsArray = array_map('trim', explode(',', $product->meta_keywords));
    $allKeywords = array_merge($allKeywords, $metaKeywordsArray);
}
$allKeywords = array_unique(array_filter($allKeywords));

// Объединяем характеристики из справочников
$characteristicsFromRegistry = [];
if (!$product->isNewRecord) {
    try {
        $characteristicsFromRegistry = \app\backend\modules\catalog\models\ProductCharacteristicValue::find()
            ->where(['product_id' => $product->id])
            ->with(['characteristic', 'characteristicValue'])
            ->all();
    } catch (\Exception $e) {
        $characteristicsFromRegistry = [];
    }
}
?>

<?php if (!$product->isNewRecord): ?>
<span id="js-product-id" data-id="<?= $product->id ?>" style="display:none;"></span>
<?php endif; ?>

<div class="product-edit">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="mb-1"><?= Html::encode($this->title) ?></h1>
            <?php if ($product->poizon_id): ?>
                <span class="badge bg-info"><i class="bi bi-cloud-download"></i> Товар из Poizon (ID: <?= $product->poizon_id ?>)</span>
            <?php endif; ?>
            <?php if ($product->parent_product_id): ?>
                <span class="badge bg-secondary"><i class="bi bi-link"></i> Вариант товара</span>
            <?php endif; ?>
        </div>
        <div class="btn-group">
            <?= Html::a('<i class="bi bi-arrow-left"></i> Назад', 
                $product->isNewRecord ? ['/admin/product/index'] : ['/admin/product/view', 'id' => $product->id], 
                ['class' => 'btn btn-secondary']
            ) ?>
        </div>
    </div>

    <?php if ($product->poizon_id): ?>
    <div class="alert alert-info">
        <i class="bi bi-info-circle"></i>
        <strong>Товар из Poizon</strong> - некоторые поля синхронизируются автоматически при импорте.
        Для обновления данных используйте кнопку "Синхронизировать" на странице просмотра.
    </div>
    <?php endif; ?>

    <!-- Быстрая навигация (закрепленная) -->
    <div class="quick-nav-sticky" id="quickNav">
        <div class="container-fluid">
            <div class="d-flex align-items-center">
                <span class="me-3 fw-bold text-muted">Быстрый переход:</span>
                <div class="btn-group btn-group-sm" role="group">
                    <a href="#section-basic" class="btn btn-outline-primary">
                        <i class="bi bi-info-circle"></i> Основное
                    </a>
                    <a href="#section-specs" class="btn btn-outline-secondary">
                        <i class="bi bi-list-ul"></i> Характеристики
                    </a>
                    <a href="#section-ms-attrs" class="btn btn-outline-secondary">
                        <i class="bi bi-database"></i> МойСклад
                    </a>
                    <a href="#section-seo" class="btn btn-outline-success">
                        <i class="bi bi-search"></i> SEO
                    </a>
                    <a href="#section-images" class="btn btn-outline-info">
                        <i class="bi bi-images"></i> Фото
                    </a>
                    <a href="#section-sizes" class="btn btn-outline-warning">
                        <i class="bi bi-rulers"></i> Размеры
                    </a>
                </div>
                <div class="ms-auto">
                    <button type="submit" form="product-form" class="btn btn-success btn-sm">
                        <i class="bi bi-check-circle"></i> Сохранить
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <div class="card mb-3 border-primary" id="section-basic">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="bi bi-info-circle-fill"></i> Основная информация</h5>
                </div>
                <div class="card-body">
                    <?php $form = ActiveForm::begin(['id' => 'product-form']); ?>

                    <?= $form->field($product, 'name')->textInput([
                        'maxlength' => true,
                        'placeholder' => 'Название товара',
                        'class' => 'form-control form-control-lg'
                    ])->label('Название товара <span class="text-danger">*</span>', ['encode' => false]) ?>

                    <?= $form->field($product, 'slug')->textInput([
                        'maxlength' => true,
                        'placeholder' => 'url-slug'
                    ])->hint('URL-адрес товара (например: nike-air-max-90)') ?>

                    <div class="row">
                        <div class="col-md-6">
                            <?= $form->field($product, 'sku')->textInput([
                                'maxlength' => true,
                                'placeholder' => 'SKU-12345'
                            ])->label('SKU (артикул)') ?>
                        </div>
                        <?php if ($product->hasAttribute('vendor_code')): ?>
                        <div class="col-md-6">
                            <?= $form->field($product, 'vendor_code')->textInput([
                                'maxlength' => true,
                                'placeholder' => '355152-106'
                            ])->label('Артикул производителя')->hint('Vendor Code от производителя') ?>
                        </div>
                        <?php endif; ?>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <?= $form->field($product, 'brand_id')->dropDownList(
                                \yii\helpers\ArrayHelper::map($brands, 'id', 'name'),
                                ['prompt' => 'Выберите бренд', 'id' => 'brand-select']
                            )->label('Бренд') ?>
                            <?php if ($product->brand && $product->brand->getLogoUrl()): ?>
                                <div class="mt-2" id="brand-logo-preview">
                                    <img src="<?= Html::encode($product->brand->getLogoUrl()) ?>" 
                                         alt="<?= Html::encode($product->brand->name) ?>" 
                                         style="max-height: 60px; max-width: 200px; object-fit: contain;"
                                         class="border rounded p-2 bg-light">
                                    <small class="d-block text-muted mt-1">Логотип бренда</small>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-6">
                            <?= $form->field($product, 'category_id')->dropDownList(
                                \yii\helpers\ArrayHelper::map($categories, 'id', 'name'),
                                ['prompt' => 'Выберите категорию']
                            )->label('Категория') ?>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <?= $form->field($product, 'price')->textInput([
                                'type' => 'number',
                                'step' => '0.01',
                                'min' => '0',
                                'placeholder' => '99.99'
                            ])->label('Цена (BYN)') ?>
                        </div>
                        <div class="col-md-6">
                            <?php if ($product->hasAttribute('poizon_id') && $product->poizon_id): ?>
                                <?= $form->field($product, 'poizon_price_cny')->textInput([
                                    'type' => 'number',
                                    'step' => '0.01',
                                    'readonly' => true
                                ])->label('Цена Poizon (CNY)')->hint('Обновляется автоматически') ?>
                            <?php endif; ?>
                        </div>
                    </div>

                    <?= $form->field($product, 'description')->textarea([
                        'rows' => 6,
                        'placeholder' => 'Подробное описание товара...'
                    ])->label('Описание') ?>

                    <div class="row">
                        <div class="col-md-4">
                            <?= $form->field($product, 'is_active')->checkbox([
                                'label' => 'Товар активен'
                            ]) ?>
                        </div>
                        <div class="col-md-4">
                            <?= $form->field($product, 'is_featured')->checkbox([
                                'label' => 'Хит продаж'
                            ]) ?>
                        </div>
                        <?php if ($product->hasAttribute('is_limited')): ?>
                        <div class="col-md-4">
                            <?= $form->field($product, 'is_limited')->checkbox([
                                'label' => 'Лимитированная'
                            ]) ?>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <?php if ($product->hasAttribute('purchase_price')): ?>
                    <div class="row">
                        <div class="col-md-6">
                            <?= $form->field($product, 'purchase_price')->textInput([
                                'type' => 'number',
                                'step' => '0.01',
                                'min' => '0',
                                'placeholder' => '85.00'
                            ])->label('Закупочная цена (BYN)')->hint('Цена закупки товара') ?>
                        </div>
                        <div class="col-md-6">
                            <?= $form->field($product, 'stock_count')->textInput([
                                'type' => 'number',
                                'min' => '0',
                                'placeholder' => '10'
                            ])->label('Количество на складе')->hint('0 = под заказ') ?>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Дополнительные параметры товара -->
                    <hr class="my-4">
                    <h6 class="mb-3"><i class="bi bi-box-seam"></i> Дополнительные параметры</h6>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <?= $form->field($product, 'series_name')->textInput([
                                'maxlength' => true,
                                'placeholder' => 'Dunk Low, Air Max 90, Jordan 1 High...'
                            ])->label('Серия товара')->hint('Название коллекции или серии') ?>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Сроки доставки (дни)</label>
                            <div class="input-group">
                                <?= Html::activeTextInput($product, 'delivery_time_min', [
                                    'class' => 'form-control',
                                    'type' => 'number',
                                    'min' => '1',
                                    'placeholder' => '2'
                                ]) ?>
                                <span class="input-group-text">—</span>
                                <?= Html::activeTextInput($product, 'delivery_time_max', [
                                    'class' => 'form-control',
                                    'type' => 'number',
                                    'min' => '1',
                                    'placeholder' => '5'
                                ]) ?>
                            </div>
                            <small class="text-muted">Минимум и максимум дней доставки</small>
                        </div>
                    </div>

                    <!-- anchor for quick nav -->
                    <div id="section-specs-anchor"></div>
                </div>
            </div>
            
            <!-- SEO настройки -->
            <div class="card mb-3" id="section-seo">
                <div class="card-header" style="background: #28a745; color: white;">
                    <h5 class="mb-0"><i class="bi bi-search"></i> SEO настройки</h5>
                </div>
                <div class="card-body">
                    <?= $form->field($product, 'meta_title')->textInput([
                        'maxlength' => true,
                        'placeholder' => 'SEO заголовок (50-60 символов)'
                    ])->label('Meta Title')->hint('Оптимальная длина: 50-60 символов') ?>

                    <?= $form->field($product, 'meta_description')->textarea([
                        'rows' => 3,
                        'placeholder' => 'SEO описание для поисковых систем (150-160 символов)',
                        'maxlength' => 160
                    ])->label('Meta Description')->hint('Оптимальная длина: 150-160 символов') ?>

                    <?php 
                    // Предзаполняем поле объединенными ключевыми словами
                    if (!empty($allKeywords) && empty($product->meta_keywords)) {
                        $product->meta_keywords = implode(', ', $allKeywords);
                    }
                    ?>
                    <?= $form->field($product, 'meta_keywords')->textarea([
                        'rows' => 3,
                        'placeholder' => 'кроссовки nike, nike air max, оригинальные кроссовки',
                        'value' => $product->meta_keywords ?: implode(', ', $allKeywords)
                    ])->label('Ключевые слова (SEO) <span class="badge bg-info ms-1">Объединенные</span>', ['encode' => false])->hint('Объединены ключевые слова из Poizon и meta_keywords. Редактируйте здесь, дубликаты будут удалены автоматически.') ?>
                    
                    <?php if (!empty($allKeywords)): ?>
                    <div class="mb-3">
                        <div class="alert alert-secondary mb-0">
                            <small>
                                <i class="bi bi-tags"></i> <strong>Текущие ключевые слова (<?= count($allKeywords) ?>):</strong><br>
                                <div class="mt-2">
                                <?php foreach ($allKeywords as $kw): ?>
                                    <span class="badge bg-dark me-1 mb-1"><?= Html::encode($kw) ?></span>
                                <?php endforeach; ?>
                                </div>
                            </small>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Изображения и ссылки -->
            <div class="card mb-3" id="section-images">
                <div class="card-header" style="background: #17a2b8; color: white;">
                    <h5 class="mb-0"><i class="bi bi-images"></i> Изображения и медиа</h5>
                </div>
                <div class="card-body">
                    <?= $form->field($product, 'main_image')->textInput([
                        'maxlength' => true,
                        'placeholder' => 'https://example.com/image.jpg или uploads/products/image.jpg'
                    ])->label('Главное изображение (URL)')->hint('Прямая ссылка на изображение или локальный путь') ?>
                    
                    <?php if ($product->hasAttribute('poizon_url') && $product->poizon_id): ?>
                    <div class="alert alert-info">
                        <strong>Poizon URL:</strong><br>
                        <?= $form->field($product, 'poizon_url')->textInput([
                            'readonly' => true,
                            'placeholder' => 'https://poizon.com/product/...'
                        ])->label(false) ?>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Галерея изображений -->
                    <?php if (!$product->isNewRecord && $product->images): ?>
                    <hr class="my-4">
                    <h6 class="mb-3">Галерея изображений</h6>
                    <div class="row g-2 mb-3">
                        <?php foreach ($product->images as $image): ?>
                            <div class="col-md-3">
                                <div class="position-relative">
                                    <img src="<?= $image->getImageUrl() ?>" class="img-fluid rounded" alt="">
                                    <?php if ($image->is_main): ?>
                                        <span class="badge bg-success position-absolute top-0 start-0 m-1">Главное</span>
                                    <?php endif; ?>
                                    <div class="position-absolute top-0 end-0 m-1">
                                        <?php if (!$image->is_main): ?>
                                            <?= Html::a('<i class="bi bi-star"></i>', ['/admin/product/set-main-image', 'id' => $image->id], [
                                                'class' => 'btn btn-sm btn-warning',
                                                'title' => 'Сделать главным',
                                                'data-method' => 'post',
                                            ]) ?>
                                        <?php endif; ?>
                                        <?= Html::a('<i class="bi bi-trash"></i>', ['/admin/product/delete-image', 'id' => $image->id], [
                                            'class' => 'btn btn-sm btn-danger',
                                            'title' => 'Удалить',
                                            'data-method' => 'post',
                                            'data-confirm' => 'Удалить изображение?',
                                        ]) ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addImageModal">
                        <i class="bi bi-plus-circle"></i> Добавить изображение
                    </button>
                    <?php else: ?>
                    <div class="alert alert-secondary">
                        <i class="bi bi-info-circle"></i> После сохранения товара вы сможете добавить дополнительные изображения.
                    </div>
                    <?php endif; ?>
                </div>
            </div></div>

            <!-- Характеристики товара -->
            <div class="card mt-3" id="section-specs">
                <div class="card-header d-flex justify-content-between align-items-center bg-light">
                    <h5 class="mb-0"><i class="bi bi-list-check"></i> Характеристики</h5>
                    <?php if (!$product->isNewRecord): ?>
                    <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#manageCharacteristicsModal">
                        <i class="bi bi-plus-circle"></i> Добавить
                    </button>
                    <?php endif; ?>
                </div>
                <div class="card-body p-0">
                    <?php if (!$product->isNewRecord):
                        $productParams = [
                            ['key' => 'material', 'name' => 'Материал верха', 'value' => $product->material, 'type' => 'select', 'options' => [
                                'leather' => 'Кожа', 'textile' => 'Текстиль', 'synthetic' => 'Синтетика',
                                'suede' => 'Замша', 'mesh' => 'Сетка', 'canvas' => 'Канвас'
                            ]],
                            ['key' => 'season', 'name' => 'Сезон', 'value' => $product->season, 'type' => 'select', 'options' => [
                                'summer' => 'Лето', 'winter' => 'Зима', 'demi' => 'Демисезон', 'all' => 'Всесезон'
                            ]],
                            ['key' => 'gender', 'name' => 'Пол', 'value' => $product->gender, 'type' => 'select', 'options' => [
                                'male' => 'Мужской', 'female' => 'Женский', 'unisex' => 'Унисекс'
                            ]],
                            ['key' => 'height', 'name' => 'Высота', 'value' => $product->height, 'type' => 'select', 'options' => [
                                'low' => 'Низкие', 'mid' => 'Средние', 'high' => 'Высокие'
                            ]],
                            ['key' => 'fastening', 'name' => 'Застёжка', 'value' => $product->fastening, 'type' => 'select', 'options' => [
                                'laces' => 'Шнурки', 'velcro' => 'Липучки', 'zipper' => 'Молния', 'slip_on' => 'Без застёжки'
                            ]],
                            ['key' => 'country', 'name' => 'Страна пр-ва', 'value' => $product->country, 'type' => 'text'],
                            ['key' => 'style_code', 'name' => 'Style Code', 'value' => $product->style_code, 'type' => 'text'],
                            ['key' => 'release_year', 'name' => 'Год релиза', 'value' => $product->release_year, 'type' => 'number'],
                            ['key' => 'weight', 'name' => 'Вес (г)', 'value' => $product->weight, 'type' => 'number'],
                        ];

                        // Parse MS attributes
                        $msAttrs = [];
                        if ($product->hasAttribute('ms_attributes_json') && !empty($product->ms_attributes_json)) {
                            $rawMsAttrs = is_array($product->ms_attributes_json)
                                ? $product->ms_attributes_json
                                : (json_decode($product->ms_attributes_json, true) ?: []);
                            foreach ($rawMsAttrs as $attr) {
                                if (empty($attr['name'])) continue;
                                $val = '';
                                if (isset($attr['value'])) {
                                    if (is_array($attr['value']) && isset($attr['value']['name'])) {
                                        $val = $attr['value']['name'];
                                    } elseif (is_string($attr['value']) || is_numeric($attr['value'])) {
                                        $val = (string)$attr['value'];
                                    } elseif (is_bool($attr['value'])) {
                                        $val = $attr['value'] ? 'Да' : 'Нет';
                                    }
                                }
                                if ($val !== '') {
                                    $msAttrs[] = ['name' => $attr['name'], 'value' => $val, 'type' => $attr['type'] ?? 'text'];
                                }
                            }
                        }

                        $hasPoizonProps = !empty($properties);
                        $hasMsAttrs = !empty($msAttrs);
                        $hasRegistryChars = count($characteristicsFromRegistry) > 0;
                        $hasAny = $hasRegistryChars || $hasPoizonProps || $hasMsAttrs || true; // always show params
                        if ($hasAny):
                    ?>
                    <div class="table-responsive">
                        <table class="table table-sm table-hover mb-0" id="characteristicsTable">
                            <thead class="table-light">
                                <tr>
                                    <th width="32%">Характеристика</th>
                                    <th>Значение</th>
                                    <th width="14%" class="text-center">Источник</th>
                                    <th width="90px" class="text-center">Авт.</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                // 1. Основные поля товара — авто-сохранение через saveField
                                foreach ($productParams as $param):
                                    if (empty($param['value'])) continue;
                                    $displayValue = $param['value'];
                                    if ($param['type'] === 'select' && isset($param['options'][$param['value']])) {
                                        $displayValue = $param['options'][$param['value']];
                                    }
                                ?>
                                <tr class="product-param-row" data-param-key="<?= $param['key'] ?>">
                                    <td class="text-muted small"><?= Html::encode($param['name']) ?></td>
                                    <td>
                                        <div class="param-value-display fw-semibold"><?= Html::encode($displayValue) ?></div>
                                        <div class="param-value-edit d-none">
                                            <?php if ($param['type'] === 'select'): ?>
                                                <select class="form-select form-select-sm param-edit-input"
                                                        name="Product[<?= $param['key'] ?>]"
                                                        data-field="<?= $param['key'] ?>"
                                                        data-original="<?= Html::encode($param['value']) ?>">
                                                    <option value="">— не выбрано —</option>
                                                    <?php foreach ($param['options'] as $optKey => $optValue): ?>
                                                        <option value="<?= $optKey ?>" <?= $optKey === $param['value'] ? 'selected' : '' ?>>
                                                            <?= Html::encode($optValue) ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            <?php elseif ($param['type'] === 'number'): ?>
                                                <input type="number" class="form-control form-control-sm param-edit-input"
                                                       name="Product[<?= $param['key'] ?>]"
                                                       data-field="<?= $param['key'] ?>"
                                                       value="<?= Html::encode($param['value']) ?>"
                                                       data-original="<?= Html::encode($param['value']) ?>">
                                            <?php else: ?>
                                                <input type="text" class="form-control form-control-sm param-edit-input"
                                                       name="Product[<?= $param['key'] ?>]"
                                                       data-field="<?= $param['key'] ?>"
                                                       value="<?= Html::encode($param['value']) ?>"
                                                       data-original="<?= Html::encode($param['value']) ?>">
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td class="text-center"><span class="badge bg-primary">Продукт</span></td>
                                    <td class="text-center">
                                        <span class="param-actions-display">
                                            <button type="button" class="btn btn-sm btn-link text-secondary p-0"
                                                    onclick="editProductParam('<?= $param['key'] ?>')" title="Редактировать">
                                                <i class="bi bi-pencil-square"></i>
                                            </button>
                                        </span>
                                        <span class="param-actions-edit d-none gap-1">
                                            <button type="button" class="btn btn-sm btn-link text-success p-0"
                                                    onclick="saveProductParamAjax('<?= $param['key'] ?>')" title="Сохранить">
                                                <i class="bi bi-check-lg"></i>
                                            </button>
                                            <button type="button" class="btn btn-sm btn-link text-secondary p-0"
                                                    onclick="cancelEditProductParam('<?= $param['key'] ?>')" title="Отмена">
                                                <i class="bi bi-x-lg"></i>
                                            </button>
                                        </span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>

                                <?php
                                // 2. Характеристики из справочников
                                foreach ($characteristicsFromRegistry as $pcv): ?>
                                <tr data-char-id="<?= $pcv->id ?>" class="editable-char-row">
                                    <td class="text-muted small"><?= Html::encode($pcv->characteristic ? $pcv->characteristic->name : '—') ?></td>
                                    <td>
                                        <div class="char-value-display fw-semibold">
                                            <?php if ($pcv->characteristicValue): ?>
                                                <?= Html::encode($pcv->characteristicValue->value) ?>
                                            <?php elseif ($pcv->value_text): ?>
                                                <?= Html::encode($pcv->value_text) ?>
                                            <?php elseif ($pcv->value_number !== null): ?>
                                                <?= Html::encode($pcv->value_number) ?>
                                            <?php endif; ?>
                                        </div>
                                        <div class="char-value-edit d-none">
                                            <?php $charType = $pcv->characteristic ? $pcv->characteristic->type : null;
                                            if ($charType === 'select'):
                                                $values = \yii\helpers\ArrayHelper::map($pcv->characteristic->values ?? [], 'id', 'value'); ?>
                                                <select class="form-select form-select-sm char-edit-input" data-original="<?= $pcv->characteristic_value_id ?>">
                                                    <?php foreach ($values as $valId => $valName): ?>
                                                        <option value="<?= $valId ?>" <?= $valId == $pcv->characteristic_value_id ? 'selected' : '' ?>>
                                                            <?= Html::encode($valName) ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            <?php elseif ($charType === 'number'): ?>
                                                <input type="number" step="0.01" class="form-control form-control-sm char-edit-input"
                                                       value="<?= $pcv->value_number ?>" data-original="<?= $pcv->value_number ?>">
                                            <?php else: ?>
                                                <input type="text" class="form-control form-control-sm char-edit-input"
                                                       value="<?= Html::encode($pcv->value_text) ?>" data-original="<?= Html::encode($pcv->value_text) ?>">
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td class="text-center"><span class="badge bg-light text-dark border">Справочник</span></td>
                                    <td class="text-center">
                                        <span class="char-actions-display gap-1">
                                            <button type="button" class="btn btn-sm btn-link text-secondary p-0"
                                                    onclick="editCharacteristic(<?= $pcv->id ?>)" title="Редактировать">
                                                <i class="bi bi-pencil-square"></i>
                                            </button>
                                            <button type="button" class="btn btn-sm btn-link text-danger p-0"
                                                    onclick="deleteCharacteristicInline(<?= $pcv->id ?>)" title="Удалить">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </span>
                                        <span class="char-actions-edit d-none gap-1">
                                            <button type="button" class="btn btn-sm btn-link text-success p-0"
                                                    onclick="saveCharacteristic(<?= $pcv->id ?>)" title="Сохранить">
                                                <i class="bi bi-check-lg"></i>
                                            </button>
                                            <button type="button" class="btn btn-sm btn-link text-secondary p-0"
                                                    onclick="cancelEditCharacteristic(<?= $pcv->id ?>)" title="Отмена">
                                                <i class="bi bi-x-lg"></i>
                                            </button>
                                        </span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>

                                <?php
                                // 3. Poizon props
                                if ($hasPoizonProps):
                                    $propIndex = 0;
                                    foreach ($properties as $prop): ?>
                                <tr class="poizon-prop-row" data-prop-index="<?= $propIndex ?>">
                                    <td class="text-muted small">
                                        <div class="poizon-prop-key-display"><?= Html::encode($prop['key'] ?? '') ?></div>
                                        <div class="poizon-prop-key-edit d-none">
                                            <input type="text" class="form-control form-control-sm"
                                                   name="poizon_props[<?= $propIndex ?>][key]"
                                                   value="<?= Html::encode($prop['key'] ?? '') ?>">
                                        </div>
                                    </td>
                                    <td>
                                        <div class="poizon-prop-value-display fw-semibold"><?= Html::encode($prop['value'] ?? '') ?></div>
                                        <div class="poizon-prop-value-edit d-none">
                                            <input type="text" class="form-control form-control-sm"
                                                   name="poizon_props[<?= $propIndex ?>][value]"
                                                   value="<?= Html::encode($prop['value'] ?? '') ?>">
                                        </div>
                                    </td>
                                    <td class="text-center"><span class="badge bg-info">Poizon</span></td>
                                    <td class="text-center">
                                        <span class="poizon-prop-actions-display gap-1">
                                            <button type="button" class="btn btn-sm btn-link text-secondary p-0"
                                                    onclick="editPoizonProp(<?= $propIndex ?>)" title="Редактировать">
                                                <i class="bi bi-pencil-square"></i>
                                            </button>
                                        </span>
                                        <span class="poizon-prop-actions-edit d-none gap-1">
                                            <button type="button" class="btn btn-sm btn-link text-success p-0"
                                                    onclick="savePoizonProp(<?= $propIndex ?>)" title="Сохранить">
                                                <i class="bi bi-check-lg"></i>
                                            </button>
                                            <button type="button" class="btn btn-sm btn-link text-secondary p-0"
                                                    onclick="cancelEditPoizonProp(<?= $propIndex ?>)" title="Отмена">
                                                <i class="bi bi-x-lg"></i>
                                            </button>
                                        </span>
                                    </td>
                                </tr>
                                    <?php $propIndex++; endforeach;
                                endif; ?>

                                <?php if ($hasMsAttrs): ?>
                                <tr class="table-secondary">
                                    <td colspan="4" class="py-1 px-3">
                                        <small class="text-muted fw-semibold"><i class="bi bi-database me-1"></i>Атрибуты МойСклад</small>
                                    </td>
                                </tr>
                                <?php foreach ($msAttrs as $msAttr): ?>
                                <tr id="section-ms-attrs">
                                    <td class="text-muted small"><?= Html::encode($msAttr['name']) ?></td>
                                    <td class="fw-semibold"><?= Html::encode($msAttr['value']) ?></td>
                                    <td class="text-center"><span class="badge" style="background:#5c3d1e;color:#fff;">МС</span></td>
                                    <td class="text-center text-muted small">—</td>
                                </tr>
                                <?php endforeach; ?>
                                <?php endif; ?>

                            </tbody>
                        </table>
                    </div>
                    <div class="px-3 py-2 text-muted border-top" style="font-size: 0.8rem;">
                        <i class="bi bi-pencil-square"></i> Нажмите карандаш — изменение сохраняется мгновенно без перезагрузки страницы.
                    </div>
                    <?php else: ?>
                    <div class="p-3 text-muted">Сохраните товар для редактирования характеристик.</div>
                    <?php endif; ?>
                    <?php else: ?>
                    <div class="p-3 text-muted">Сохраните товар для добавления характеристик.</div>
                    <?php endif; // !isNewRecord ?>
                </div>
            </div>

            <!-- Размерная сетка -->
            <div class="card mt-3 admin-card size-dashboard-card" id="section-sizes">
                <div class="card-header size-card-hero">
                    <div>
                        <p class="size-card-eyebrow mb-1">Размеры и наличие</p>
                        <h5 class="mb-1 size-card-title"><i class="bi bi-rulers"></i> Размерная матрица</h5>
                        <p class="size-card-subtitle mb-0">Контролируйте цены, остатки и статусы в одном месте</p>
                    </div>
                    <div class="size-card-hero-actions">
                        <button type="button" class="btn btn-light btn-sm size-hero-btn" data-bs-toggle="modal" data-bs-target="#addSizeModal">
                            <i class="bi bi-plus-circle"></i> Добавить размер
                        </button>
                        <?php if ($product->poizon_id): ?>
                        <span class="badge bg-light text-dark size-hero-badge">
                            <i class="bi bi-cloud-arrow-down"></i> Poizon linked
                        </span>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="card-body size-card-body">
                    <?php if (!$product->isNewRecord): ?>
                    <?php 
                    $sizes = $product->getSizes()->orderBy(['us_size' => SORT_ASC])->all();
                    $sizesCount = count($sizes);
                    $totalLocalStock = 0;
                    $availableSizeCount = 0;
                    $poizonLinkedCount = 0;
                    $priceRangeMin = null;
                    $priceRangeMax = null;
                    foreach ($sizes as $size) {
                        $totalLocalStock += (int)$size->stock;
                        if ($size->is_available) {
                            $availableSizeCount++;
                        }
                        if (!empty($size->poizon_sku_id)) {
                            $poizonLinkedCount++;
                        }
                        $effectivePrice = $size->price_byn ?? $size->price ?? $product->price;
                        if ($effectivePrice !== null) {
                            $effectivePrice = (float)$effectivePrice;
                            if ($priceRangeMin === null || $effectivePrice < $priceRangeMin) {
                                $priceRangeMin = $effectivePrice;
                            }
                            if ($priceRangeMax === null || $effectivePrice > $priceRangeMax) {
                                $priceRangeMax = $effectivePrice;
                            }
                        }
                    }
                    if ($sizesCount > 0):
                    ?>
                    <div class="size-metrics-grid mb-4">
                        <div class="size-metric-card">
                            <p class="size-metric-label">Размеров активно</p>
                            <div class="size-metric-value"><?= $sizesCount ?></div>
                            <span class="size-metric-chip">
                                <?= $availableSizeCount ?> доступны сейчас
                            </span>
                        </div>
                        <div class="size-metric-card">
                            <p class="size-metric-label">Локальные остатки</p>
                            <div class="size-metric-value"><?= $totalLocalStock ?></div>
                            <span class="size-metric-chip">единиц на складе</span>
                        </div>
                        <div class="size-metric-card">
                            <p class="size-metric-label">Диапазон цен (BYN)</p>
                            <div class="size-metric-value">
                                <?php if ($priceRangeMin !== null): ?>
                                    <?= number_format($priceRangeMin, 2) ?> — <?= number_format($priceRangeMax, 2) ?>
                                <?php else: ?>
                                    —
                                <?php endif; ?>
                            </div>
                            <span class="size-metric-chip">С учетом индивидуальных цен</span>
                        </div>
                        <?php if ($product->poizon_id): ?>
                        <div class="size-metric-card">
                            <p class="size-metric-label">Привязано к Poizon</p>
                            <div class="size-metric-value"><?= $poizonLinkedCount ?></div>
                            <span class="size-metric-chip">SKU синхронизированы</span>
                        </div>
                        <?php endif; ?>
                    </div>

                    <div class="size-legend d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3">
                        <div class="d-flex flex-wrap gap-2">
                            <span class="size-legend-item"><span class="legend-dot legend-dot--available"></span> Доступен</span>
                            <span class="size-legend-item"><span class="legend-dot legend-dot--wait"></span> Под заказ</span>
                            <?php if ($product->poizon_id): ?>
                            <span class="size-legend-item"><span class="legend-dot legend-dot--poizon"></span> Poizon SKU</span>
                            <?php endif; ?>
                        </div>
                        <div class="size-legend-actions">
                            <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-toggle="modal" data-bs-target="#addSizeModal">
                                <i class="bi bi-magic"></i> Массовое добавление
                            </button>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-sm table-hover size-table align-middle">
                            <thead>
                                <tr>
                                    <th class="text-uppercase">US</th>
                                    <th class="text-uppercase">EU</th>
                                    <th class="text-uppercase">UK</th>
                                    <th class="text-uppercase">CM</th>
                                    <th class="text-uppercase" style="cursor: help;" title="Цена в юанях (CNY)">
                                        Цена ¥
                                        <i class="bi bi-info-circle-fill text-info"></i>
                                    </th>
                                    <th class="text-uppercase">Цена BYN</th>
                                    <?php if ($product->poizon_id): ?>
                                        <th class="text-uppercase">Poizon SKU</th>
                                        <th class="text-uppercase">Poizon остаток</th>
                                    <?php endif; ?>
                                    <th class="text-uppercase">Остаток</th>
                                    <th class="text-uppercase">Статус</th>
                                    <th class="text-uppercase text-end">Действия</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($sizes as $size): ?>
                                <?php 
                                    $rowClasses = [];
                                    if ($size->is_available) {
                                        $rowClasses[] = 'size-row-available';
                                    } else {
                                        $rowClasses[] = 'size-row-waitlist';
                                    }
                                ?>
                                <tr class="<?= implode(' ', $rowClasses) ?>">
                                    <td><strong class="size-value-main"><?= Html::encode($size->us_size ?: $size->size) ?></strong></td>
                                    <td><span class="size-value-muted"><?= Html::encode($size->eu_size ?: '-') ?></span></td>
                                    <td><span class="size-value-muted"><?= Html::encode($size->uk_size ?: '-') ?></span></td>
                                    <td><span class="size-value-muted"><?= Html::encode($size->cm_size ?: '-') ?></span></td>
                                    <td>
                                        <?php if ($size->price_cny): ?>
                                            <button type="button"
                                                class="size-price-chip size-price-chip--cny"
                                                onclick="copyToClipboard('<?= $size->price_cny ?>', this)"
                                                title="Нажмите чтобы скопировать">
                                                ¥<?= number_format($size->price_cny, 2) ?>
                                                <i class="bi bi-clipboard ms-1"></i>
                                            </button>
                                        <?php else: ?>
                                            <span class="text-muted">—</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php
                                        $sizePriceByn = $size->price_byn ?: ($size->price ?: null);
                                        $sizePriceDisplay = $sizePriceByn
                                            ? number_format($sizePriceByn, 2)
                                            : number_format($product->price, 2);
                                        $sizePriceIsCustom = (bool)$sizePriceByn;
                                        ?>
                                        <div class="size-price-display" id="sp-display-<?= $size->id ?>">
                                            <span class="size-price-pill <?= $sizePriceIsCustom ? 'size-price-pill--custom' : 'size-price-pill--default' ?>"
                                                  style="cursor:pointer;" title="Нажмите для редактирования"
                                                  onclick="editSizePrice(<?= $size->id ?>)">
                                                <?= $sizePriceDisplay ?> BYN
                                                <i class="bi bi-pencil ms-1" style="font-size:0.7em;opacity:0.5;"></i>
                                            </span>
                                            <?php if (!$sizePriceIsCustom): ?>
                                            <small class="text-muted d-block" style="font-size:0.7em;">общая</small>
                                            <?php endif; ?>
                                        </div>
                                        <div class="size-price-edit d-none" id="sp-edit-<?= $size->id ?>">
                                            <div class="input-group input-group-sm" style="min-width:110px;max-width:150px;">
                                                <input type="number" step="0.01" min="0"
                                                       class="form-control form-control-sm"
                                                       id="sp-input-<?= $size->id ?>"
                                                       value="<?= $sizePriceByn ?: '' ?>"
                                                       placeholder="<?= number_format($product->price, 2) ?>">
                                                <button class="btn btn-success btn-sm px-1" type="button"
                                                        onclick="saveSizePrice(<?= $size->id ?>)" title="Сохранить">
                                                    <i class="bi bi-check"></i>
                                                </button>
                                                <button class="btn btn-secondary btn-sm px-1" type="button"
                                                        onclick="cancelSizePrice(<?= $size->id ?>)" title="Отмена">
                                                    <i class="bi bi-x"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </td>
                                    <?php if ($product->poizon_id): ?>
                                        <td>
                                            <span class="size-chip <?= $size->poizon_sku_id ? 'size-chip--poizon' : 'size-chip--muted' ?>">
                                                <?= Html::encode($size->poizon_sku_id ?: '—') ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if ($size->poizon_stock > 0): ?>
                                                <span class="size-chip size-chip--success"><?= $size->poizon_stock ?></span>
                                            <?php else: ?>
                                                <span class="size-chip size-chip--muted">0</span>
                                            <?php endif; ?>
                                        </td>
                                    <?php endif; ?>
                                    <td>
                                        <?php if ($size->stock > 0): ?>
                                            <span class="size-chip size-chip--primary"><?= $size->stock ?></span>
                                        <?php else: ?>
                                            <span class="size-chip size-chip--muted">0</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($size->is_available): ?>
                                            <span class="size-status size-status--available">
                                                <i class="bi bi-check-circle-fill"></i> Доступен
                                            </span>
                                        <?php else: ?>
                                            <span class="size-status size-status--wait">
                                                <i class="bi bi-hourglass-split"></i> Ожидание
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end">
                                        <div class="btn-group btn-group-sm size-row-actions">
                                            <button type="button" class="btn btn-outline-primary size-action-btn" 
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#editSizeModal<?= $size->id ?>"
                                                    title="Редактировать">
                                                <i class="bi bi-pencil"></i>
                                            </button>
                                            <?= Html::a('<i class="bi bi-trash"></i>', ['/admin/product/delete-size', 'id' => $size->id], [
                                                'class' => 'btn btn-outline-danger size-action-btn',
                                                'title' => 'Удалить',
                                                'data-method' => 'post',
                                                'data-confirm' => 'Удалить размер?',
                                            ]) ?>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="d-flex justify-content-between flex-wrap gap-2 mt-3 size-footer-actions">
                        <div class="text-muted size-footer-hint">
                            <i class="bi bi-info-circle"></i> Управляйте ценами для каждого размера или используйте общую цену товара
                        </div>
                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-toggle="modal" data-bs-target="#addSizeModal">
                                <i class="bi bi-arrow-repeat"></i> Синхронизировать сетку
                            </button>
                            <button type="button" class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#addSizeModal">
                                <i class="bi bi-plus-circle"></i> Добавить размер
                            </button>
                        </div>
                    </div>
                    <?php else: ?>
                    <div class="size-empty-state text-center py-5">
                        <div class="size-empty-icon mb-3">
                            <i class="bi bi-rulers"></i>
                        </div>
                        <h5 class="mb-2">Размеры еще не добавлены</h5>
                        <p class="text-muted mb-3">Задайте сетку вручную или импортируйте готовую из справочников брендов</p>
                        <div class="d-flex justify-content-center gap-2 flex-wrap">
                            <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addSizeModal">
                                <i class="bi bi-plus-circle"></i> Добавить первый размер
                            </button>
                            <button type="button" class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#addSizeModal">
                                <i class="bi bi-magic"></i> Импортировать сетку
                            </button>
                        </div>
                    </div>
                    <?php endif; ?>
                    <?php else: ?>
                    <div class="alert alert-warning">
                        <i class="bi bi-exclamation-triangle"></i> Сначала сохраните товар, чтобы добавить размеры.
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <?php if (!$product->isNewRecord): ?>
            <!-- Быстрая сетка доступных размеров (sizes_data) -->
            <?php
            $quickSizes = [];
            if (!empty($product->sizes_data)) {
                $rawSd = is_array($product->sizes_data) ? $product->sizes_data : (json_decode($product->sizes_data, true) ?: []);
                // Use only if dict format (not Poizon array)
                if (!empty($rawSd) && array_keys($rawSd) !== range(0, count($rawSd) - 1)) {
                    $quickSizes = $rawSd;
                }
            }
            $allEuSizes = ['36','36.5','37','37.5','38','38.5','39','39.5','40','40.5','41','41.5','42','42.5','43','43.5','44','44.5','45','46','47'];
            ?>
            <div class="card mt-3" id="sizes-quick-grid-card">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <h5 class="mb-0"><i class="bi bi-check2-square"></i> Быстрая сетка размеров EU</h5>
                    <small class="text-muted">Переопределяет отображение размеров на сайте</small>
                </div>
                <div class="card-body">
                    <div class="d-flex flex-wrap gap-2 mb-3" id="sizeCheckboxGrid">
                        <?php foreach ($allEuSizes as $eu): ?>
                        <?php $checked = !empty($quickSizes) ? !empty($quickSizes[$eu]) : false; ?>
                        <label class="size-quick-btn <?= $checked ? 'active' : '' ?>" title="EU <?= $eu ?>">
                            <input type="checkbox" value="<?= $eu ?>" <?= $checked ? 'checked' : '' ?> style="position:absolute;opacity:0;pointer-events:none">
                            <?= $eu ?>
                        </label>
                        <?php endforeach; ?>
                    </div>
                    <div class="d-flex gap-2 align-items-center">
                        <button type="button" class="btn btn-sm btn-dark" onclick="saveQuickSizesGrid()">
                            <i class="bi bi-save"></i> Сохранить сетку
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="selectAllSizes(true)">Все</button>
                        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="selectAllSizes(false)">Ни одного</button>
                        <span id="sizeGridSaveMsg" class="ms-2" style="font-size:13px"></span>
                    </div>
                </div>
            </div>
            <style>
            .size-quick-btn {
                display: inline-flex; align-items: center; justify-content: center;
                min-width: 48px; height: 36px; padding: 0 10px;
                border: 1.5px solid #dee2e6; border-radius: 8px;
                font-size: 13px; font-weight: 500; cursor: pointer;
                background: #f9fafb; color: #374151; transition: all 0.15s;
                user-select: none;
            }
            .size-quick-btn:hover { border-color: #6366f1; color: #6366f1; }
            .size-quick-btn.active { background: #111; color: #fff; border-color: #111; }
            </style>
            <script>
            document.querySelectorAll('.size-quick-btn').forEach(function(btn) {
                btn.addEventListener('click', function() {
                    btn.classList.toggle('active');
                    btn.querySelector('input').checked = btn.classList.contains('active');
                });
            });
            function selectAllSizes(val) {
                document.querySelectorAll('.size-quick-btn').forEach(function(btn) {
                    btn.classList.toggle('active', val);
                    btn.querySelector('input').checked = val;
                });
            }
            function saveQuickSizesGrid() {
                var sizes = {};
                document.querySelectorAll('#sizeCheckboxGrid .size-quick-btn').forEach(function(btn) {
                    sizes[btn.querySelector('input').value] = btn.classList.contains('active');
                });
                var msg = document.getElementById('sizeGridSaveMsg');
                msg.textContent = 'Сохраняем...'; msg.style.color = '#6b7280';
                fetch('<?= \yii\helpers\Url::to(['/admin/product/save-sizes-data']) ?>', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]')?.content || ''
                    },
                    body: JSON.stringify({id: <?= $product->id ?>, sizes: sizes})
                })
                .then(r => r.json())
                .then(d => {
                    msg.textContent = d.success ? ('✓ Сохранено (' + d.saved + ' размеров)') : ('✗ ' + (d.message || 'Ошибка'));
                    msg.style.color = d.success ? '#065f46' : '#991b1b';
                })
                .catch(() => { msg.textContent = '✗ Ошибка сети'; msg.style.color = '#991b1b'; });
            }
            </script>
            <?php endif; ?>

            <!-- Кнопки действий -->
            <div class="card mt-3 border-0 shadow">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <?= Html::submitButton('<i class="bi bi-check-circle-fill"></i> Сохранить изменения', [
                                'class' => 'btn btn-success btn-lg px-5'
                            ]) ?>
                            <?= Html::a('<i class="bi bi-x-circle"></i> Отмена', ['/admin/product/view', 'id' => $product->id], [
                                'class' => 'btn btn-secondary btn-lg px-4'
                            ]) ?>
                        </div>
                        <div>
                            <?= Html::a('<i class="bi bi-trash"></i> Удалить товар', ['/admin/product/delete', 'id' => $product->id], [
                                'class' => 'btn btn-outline-danger',
                                'data-method' => 'post',
                                'data-confirm' => 'Вы уверены, что хотите удалить этот товар? Это действие нельзя отменить.'
                            ]) ?>
                        </div>
                    </div>
                </div>
            </div>

            <?php ActiveForm::end(); ?>

        </div>

        <!-- Правая колонка: дополнительная информация -->
        <div class="col-lg-4">
            
            <?php if ($product->poizon_id): ?>
            <!-- Информация Poizon -->
            <div class="card mb-3">
                <div class="card-header bg-info text-white">
                    <h6 class="mb-0"><i class="bi bi-cloud-download"></i> Информация Poizon</h6>
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
                            <td><?= Html::a('Открыть', $product->poizon_url, [
                                'target' => '_blank',
                                'class' => 'btn btn-sm btn-outline-primary'
                            ]) ?></td>
                        </tr>
                        <?php endif; ?>
                        <tr>
                            <th>Последняя синхр.:</th>
                            <td>
                                <?php if ($product->last_sync_at): ?>
                                    <small><?= Yii::$app->formatter->asDatetime($product->last_sync_at) ?></small>
                                <?php else: ?>
                                    <span class="text-danger">Не синхронизирован</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    </table>
                    
                    <?= Html::a('<i class="bi bi-arrow-repeat"></i> Синхронизировать сейчас', ['/admin/product/sync', 'id' => $product->id], [
                        'class' => 'btn btn-info btn-sm w-100 mt-2',
                        'data-method' => 'post'
                    ]) ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Метаинформация -->
            <div class="card">
                <div class="card-header bg-light">
                    <h6 class="mb-0"><i class="bi bi-clock-history"></i> Метаинформация</h6>
                </div>
                <div class="card-body">
                    <table class="table table-sm mb-0">
                        <tr>
                            <th>ID:</th>
                            <td><?= $product->id ?></td>
                        </tr>
                        <tr>
                            <th>Создан:</th>
                            <td><small><?= Yii::$app->formatter->asDatetime($product->created_at) ?></small></td>
                        </tr>
                        <tr>
                            <th>Обновлен:</th>
                            <td><small><?= Yii::$app->formatter->asDatetime($product->updated_at) ?></small></td>
                        </tr>
                    </table>
                </div>
            </div>

        </div>
    </div>

</div>

<!-- Modal: Добавить изображение -->
<?php if (!$product->isNewRecord): ?>
<div class="modal fade" id="addImageModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-image"></i> Добавить изображение</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="<?= yii\helpers\Url::to(['/admin/product/add-image', 'productId' => $product->id, 'returnUrl' => 'edit']) ?>" method="post">
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
            <form action="<?= yii\helpers\Url::to(['/admin/product/add-size', 'productId' => $product->id, 'returnUrl' => 'edit']) ?>" method="post">
                <?= Html::hiddenInput(Yii::$app->request->csrfParam, Yii::$app->request->csrfToken) ?>
                <div class="modal-body">
                    <!-- Выбор размерной сетки -->
                    <?php
                    try {
                        $sizeGrids = \app\backend\modules\catalog\models\SizeGrid::find()
                            ->where(['is_active' => true])
                            ->orderBy(['name' => SORT_ASC])
                            ->all();
                    } catch (\Exception $e) {
                        $sizeGrids = [];
                        Yii::warning('SizeGrid query error: ' . $e->getMessage(), 'admin');
                    }
                    
                    if ($sizeGrids): ?>
                    <div class="alert alert-info">
                        <i class="bi bi-magic"></i> <strong>Быстрое добавление:</strong>
                        <div class="mt-2">
                            <select id="size-grid-select" class="form-select form-select-sm">
                                <option value="">-- Выберите размерную сетку для массового добавления --</option>
                                <?php foreach ($sizeGrids as $grid): ?>
                                    <option value="<?= $grid->id ?>">
                                        <?= Html::encode($grid->getFullName()) ?> (<?= count($grid->items) ?> размеров)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <a href="<?= yii\helpers\Url::to(['/admin/product/add-sizes-from-grid', 'productId' => $product->id, 'gridId' => '__GRID_ID__', 'returnUrl' => 'edit']) ?>" 
                               class="btn btn-sm btn-success mt-2" id="add-from-grid-btn" style="display:none;">
                                <i class="bi bi-plus-circle"></i> Добавить все размеры из сетки
                            </a>
                        </div>
                    </div>
                    <hr>
                    <?php endif; ?>
                    
                    <h6 class="mb-3">Или добавьте размер вручную:</h6>
                    
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
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">Размер (общий)</label>
                                <input type="text" name="ProductSize[size]" class="form-control" 
                                       placeholder="M, L, XL или 42" required>
                                <div class="form-text">Обязательное поле</div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">Остаток на складе</label>
                                <input type="number" name="ProductSize[stock]" class="form-control" 
                                       value="0" min="0">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">Цена (если отличается)</label>
                                <input type="number" step="0.01" name="ProductSize[price]" class="form-control" 
                                       placeholder="Оставьте пустым для цены товара">
                                <div class="form-text">Опционально</div>
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

<?php endif; ?>

<!-- Modal: Добавление характеристики -->
<?php if (!$product->isNewRecord): ?>
<div class="modal fade" id="manageCharacteristicsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-light">
                <h5 class="modal-title"><i class="bi bi-plus-circle"></i> Добавить новую характеристику</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-info mb-3">
                    <i class="bi bi-info-circle"></i> 
                    Редактировать существующие характеристики можно прямо в таблице — нажмите <i class="bi bi-pencil"></i> рядом с характеристикой.
                </div>
                        
                        <div id="addCharacteristicForm">
                            <!-- Выбор существующей характеристики -->
                            <div class="mb-3">
                                <label class="form-label">Характеристика</label>
                                <select class="form-select" id="characteristicSelect">
                                    <option value="">Выберите характеристику...</option>
                                    <option value="__new__" class="text-primary fw-bold">➕ Создать новую...</option>
                                </select>
                            </div>

                            <!-- Форма создания новой характеристики -->
                            <div id="newCharacteristicForm" style="display: none;" class="mb-3 p-3 bg-light border rounded">
                                <h6 class="text-primary"><i class="bi bi-magic"></i> Новая характеристика</h6>
                                <div class="mb-2">
                                    <label class="form-label">Название</label>
                                    <input type="text" class="form-control" id="newCharName" placeholder="Материал подошвы">
                                </div>
                                <div class="mb-2">
                                    <label class="form-label">Тип</label>
                                    <select class="form-select" id="newCharType">
                                        <option value="text">Текст</option>
                                        <option value="select">Выбор из списка</option>
                                        <option value="number">Число</option>
                                        <option value="boolean">Да/Нет</option>
                                    </select>
                                </div>
                                <button type="button" class="btn btn-sm btn-primary" onclick="createNewCharacteristic()">
                                    <i class="bi bi-check"></i> Создать и выбрать
                                </button>
                                <button type="button" class="btn btn-sm btn-secondary" onclick="cancelNewCharacteristic()">
                                    Отмена
                                </button>
                            </div>

                            <!-- Поле значения (динамическое) -->
                            <div id="valueContainer" style="display: none;">
                                <!-- Для select: dropdown -->
                                <div id="valueSelect" style="display: none;" class="mb-3">
                                    <label class="form-label">Значение</label>
                                    <select class="form-select" id="characteristicValueSelect">
                                        <option value="">Выберите значение...</option>
                                        <option value="__new__" class="text-primary fw-bold">➕ Добавить новое значение...</option>
                                    </select>
                                    
                                    <!-- Добавление нового значения -->
                                    <div id="newValueForm" style="display: none;" class="mt-2 p-2 bg-light border rounded">
                                        <input type="text" class="form-control form-control-sm mb-2" id="newValueInput" placeholder="Новое значение">
                                        <button type="button" class="btn btn-sm btn-primary" onclick="createNewValue()">
                                            <i class="bi bi-check"></i> Добавить
                                        </button>
                                        <button type="button" class="btn btn-sm btn-secondary" onclick="cancelNewValue()">
                                            Отмена
                                        </button>
                                    </div>
                                </div>

                                <!-- Для text: input -->
                                <div id="valueText" style="display: none;" class="mb-3">
                                    <label class="form-label">Значение (текст)</label>
                                    <input type="text" class="form-control" id="characteristicValueText" placeholder="Введите значение">
                                </div>

                                <!-- Для number: number input -->
                                <div id="valueNumber" style="display: none;" class="mb-3">
                                    <label class="form-label">Значение (число)</label>
                                    <input type="number" step="0.01" class="form-control" id="characteristicValueNumber" placeholder="0">
                                </div>
                            </div>

                            <button type="button" class="btn btn-success w-100" onclick="addCharacteristicToProduct()" id="addCharBtn" disabled>
                                <i class="bi bi-plus-circle"></i> Добавить характеристику
                            </button>
                        </div>

                        <div id="addCharMessage" class="mt-3"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Закрыть</button>
            </div>
        </div>
    </div>
</div>

<?php endif; ?>

<script>
// Обработчики для модальных окон (без Bootstrap JS)
document.addEventListener('DOMContentLoaded', function() {
    // Функция для открытия модального окна
    function openModal(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.style.display = 'flex';
            modal.classList.add('show');
            document.body.style.overflow = 'hidden';
        }
    }

    // Функция для закрытия модального окна
    function closeModal(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.style.display = 'none';
            modal.classList.remove('show');
            document.body.style.overflow = '';
        }
    }

    // Закрытие по кнопке закрытия
    document.querySelectorAll('[data-bs-dismiss="modal"]').forEach(function(btn) {
        btn.addEventListener('click', function() {
            const modal = this.closest('.modal');
            if (modal) {
                modal.style.display = 'none';
                modal.classList.remove('show');
                document.body.style.overflow = '';
            }
        });
    });

    // Закрытие по клику вне модального окна
    document.querySelectorAll('.modal').forEach(function(modal) {
        modal.addEventListener('click', function(e) {
            if (e.target === modal) {
                modal.style.display = 'none';
                modal.classList.remove('show');
                document.body.style.overflow = '';
            }
        });
    });

    // Открытие модальных окон по data-bs-toggle="modal"
    document.querySelectorAll('[data-bs-toggle="modal"]').forEach(function(btn) {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const targetId = this.getAttribute('data-bs-target');
            if (targetId) {
                const modalId = targetId.replace('#', '');
                openModal(modalId);
            }
        });
    });

    // Функция для кнопки "Добавить характеристику"
    window.addCharacteristicToProduct = function() {
        const charSelect = document.getElementById('characteristicSelect');
        const valueText = document.getElementById('characteristicValueText');
        const valueNumber = document.getElementById('characteristicValueNumber');
        const messageDiv = document.getElementById('addCharMessage');

        if (!charSelect.value) {
            messageDiv.innerHTML = '<div class="alert alert-danger">Выберите характеристику</div>';
            return;
        }

        const value = charSelect.value === 'number' ? valueNumber.value : valueText.value;
        if (!value) {
            messageDiv.innerHTML = '<div class="alert alert-danger">Введите значение</div>';
            return;
        }

        // Здесь должна быть отправка на сервер
        messageDiv.innerHTML = '<div class="alert alert-success">Характеристика добавлена</div>';
        
        // Закрыть модальное окно
        setTimeout(function() {
            closeModal('manageCharacteristicsModal');
        }, 1000);
    };

    // Активация/деактивация кнопки добавления характеристики
    const charSelect = document.getElementById('characteristicSelect');
    const addCharBtn = document.getElementById('addCharBtn');

    if (charSelect && addCharBtn) {
        charSelect.addEventListener('change', function() {
            addCharBtn.disabled = !this.value;
        });
    }
});

// ─── Auto-save helpers ──────────────────────────────────────────────────────
const _productId = document.getElementById('js-product-id')
    ? parseInt(document.getElementById('js-product-id').dataset.id)
    : 0;
const _csrfToken = (document.querySelector('meta[name="csrf-token"]') || {}).content || '';
const _saveFieldUrl = '<?= \yii\helpers\Url::to(['/admin/product/save-field']) ?>';
const _updateSizePriceUrl = '<?= \yii\helpers\Url::to(['/admin/product/update-size-price']) ?>';

function _showSaveToast(msg, ok) {
    let t = document.getElementById('_saveToast');
    if (!t) {
        t = document.createElement('div');
        t.id = '_saveToast';
        t.style.cssText = 'position:fixed;bottom:20px;right:20px;z-index:9999;padding:8px 16px;border-radius:6px;font-size:14px;font-weight:500;box-shadow:0 2px 8px rgba(0,0,0,.2);transition:opacity .3s;';
        document.body.appendChild(t);
    }
    t.textContent = msg;
    t.style.background = ok ? '#065f46' : '#991b1b';
    t.style.color = '#fff';
    t.style.opacity = '1';
    clearTimeout(t._timer);
    t._timer = setTimeout(() => { t.style.opacity = '0'; }, 2000);
}

// Product param: enter edit mode
function editProductParam(key) {
    const row = document.querySelector('.product-param-row[data-param-key="' + key + '"]');
    if (!row) return;
    row.querySelector('.param-value-display').classList.add('d-none');
    row.querySelector('.param-value-edit').classList.remove('d-none');
    row.querySelector('.param-actions-display').classList.add('d-none');
    row.querySelector('.param-actions-edit').classList.remove('d-none');
    const inp = row.querySelector('.param-edit-input');
    if (inp) inp.focus();
}

function cancelEditProductParam(key) {
    const row = document.querySelector('.product-param-row[data-param-key="' + key + '"]');
    if (!row) return;
    row.querySelector('.param-value-display').classList.remove('d-none');
    row.querySelector('.param-value-edit').classList.add('d-none');
    row.querySelector('.param-actions-display').classList.remove('d-none');
    row.querySelector('.param-actions-edit').classList.add('d-none');
    const inp = row.querySelector('.param-edit-input');
    if (inp) inp.value = inp.dataset.original;
}

// Auto-save via saveField endpoint
function saveProductParamAjax(key) {
    const row = document.querySelector('.product-param-row[data-param-key="' + key + '"]');
    if (!row) return;
    const inp = row.querySelector('.param-edit-input');
    if (!inp) return;
    const value = inp.value;

    fetch(_saveFieldUrl, {
        method: 'POST',
        headers: {'Content-Type': 'application/json', 'X-CSRF-Token': _csrfToken},
        body: JSON.stringify({id: _productId, field: key, value: value})
    })
    .then(r => r.json())
    .then(d => {
        if (d.success) {
            // Update display label
            const display = row.querySelector('.param-value-display');
            display.textContent = d.displayValue || value;
            inp.dataset.original = value;
            cancelEditProductParam(key);
            _showSaveToast('✓ Сохранено', true);
        } else {
            _showSaveToast('✗ ' + (d.message || 'Ошибка'), false);
        }
    })
    .catch(() => _showSaveToast('✗ Ошибка сети', false));
}

// Poizon props helpers (keep existing pattern, just convert style to d-none)
function editPoizonProp(idx) {
    const row = document.querySelector('.poizon-prop-row[data-prop-index="' + idx + '"]');
    if (!row) return;
    row.querySelector('.poizon-prop-key-display').classList.add('d-none');
    row.querySelector('.poizon-prop-key-edit').classList.remove('d-none');
    row.querySelector('.poizon-prop-value-display').classList.add('d-none');
    row.querySelector('.poizon-prop-value-edit').classList.remove('d-none');
    row.querySelector('.poizon-prop-actions-display').classList.add('d-none');
    row.querySelector('.poizon-prop-actions-edit').classList.remove('d-none');
}
function cancelEditPoizonProp(idx) {
    const row = document.querySelector('.poizon-prop-row[data-prop-index="' + idx + '"]');
    if (!row) return;
    row.querySelector('.poizon-prop-key-display').classList.remove('d-none');
    row.querySelector('.poizon-prop-key-edit').classList.add('d-none');
    row.querySelector('.poizon-prop-value-display').classList.remove('d-none');
    row.querySelector('.poizon-prop-value-edit').classList.add('d-none');
    row.querySelector('.poizon-prop-actions-display').classList.remove('d-none');
    row.querySelector('.poizon-prop-actions-edit').classList.add('d-none');
}
function savePoizonProp(idx) {
    // Just submit the whole form (Poizon props go via normal form POST)
    document.getElementById('product-form').submit();
}

// Registry char helpers
function editCharacteristic(id) {
    const row = document.querySelector('tr[data-char-id="' + id + '"]');
    if (!row) return;
    row.querySelector('.char-value-display').classList.add('d-none');
    row.querySelector('.char-value-edit').classList.remove('d-none');
    row.querySelector('.char-actions-display').classList.add('d-none');
    row.querySelector('.char-actions-edit').classList.remove('d-none');
}
function cancelEditCharacteristic(id) {
    const row = document.querySelector('tr[data-char-id="' + id + '"]');
    if (!row) return;
    row.querySelector('.char-value-display').classList.remove('d-none');
    row.querySelector('.char-value-edit').classList.add('d-none');
    row.querySelector('.char-actions-display').classList.remove('d-none');
    row.querySelector('.char-actions-edit').classList.add('d-none');
}
function saveCharacteristic(id) { /* extend as needed */ document.getElementById('product-form').submit(); }
function deleteCharacteristicInline(id) { if (confirm('Удалить характеристику?')) { /* TODO */ } }

// ─── Size price inline edit ──────────────────────────────────────────────────
function editSizePrice(sizeId) {
    document.getElementById('sp-display-' + sizeId).classList.add('d-none');
    document.getElementById('sp-edit-' + sizeId).classList.remove('d-none');
    const inp = document.getElementById('sp-input-' + sizeId);
    if (inp) inp.focus();
}
function cancelSizePrice(sizeId) {
    document.getElementById('sp-display-' + sizeId).classList.remove('d-none');
    document.getElementById('sp-edit-' + sizeId).classList.add('d-none');
}
function saveSizePrice(sizeId) {
    const inp = document.getElementById('sp-input-' + sizeId);
    const priceByn = parseFloat(inp.value) || 0;
    fetch(_updateSizePriceUrl, {
        method: 'POST',
        headers: {'Content-Type': 'application/json', 'X-CSRF-Token': _csrfToken},
        body: JSON.stringify({size_id: sizeId, price_byn: priceByn})
    })
    .then(r => r.json())
    .then(d => {
        if (d.success) {
            const display = document.getElementById('sp-display-' + sizeId);
            const pill = display.querySelector('.size-price-pill');
            const formatted = priceByn > 0
                ? priceByn.toFixed(2) + ' BYN'
                : '<?= number_format($product->price, 2) ?> BYN';
            pill.textContent = formatted;
            pill.className = 'size-price-pill ' + (priceByn > 0 ? 'size-price-pill--custom' : 'size-price-pill--default');
            const note = display.querySelector('small');
            if (note) note.remove();
            cancelSizePrice(sizeId);
            _showSaveToast('✓ Цена сохранена', true);
        } else {
            _showSaveToast('✗ ' + (d.message || 'Ошибка'), false);
        }
    })
    .catch(() => _showSaveToast('✗ Ошибка сети', false));
}

// Clipboard copy helper (used by CNY price buttons)
function copyToClipboard(text, btn) {
    navigator.clipboard.writeText(text).then(() => {
        const orig = btn.innerHTML;
        btn.innerHTML = btn.innerHTML.replace('bi-clipboard', 'bi-clipboard-check');
        setTimeout(() => { btn.innerHTML = orig; }, 1200);
    });
}
</script>
