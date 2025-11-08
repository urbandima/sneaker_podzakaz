<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/** @var yii\web\View $this */
/** @var app\models\Product $product */
/** @var array $brands */
/** @var array $categories */

$this->title = $product->isNewRecord ? 'Создание товара' : 'Редактирование: ' . $product->name;
$this->params['breadcrumbs'][] = ['label' => 'Товары', 'url' => ['products']];
if (!$product->isNewRecord) {
    $this->params['breadcrumbs'][] = ['label' => $product->name, 'url' => ['view-product', 'id' => $product->id]];
}
$this->params['breadcrumbs'][] = $product->isNewRecord ? 'Создание' : 'Редактирование';

// Парсим JSON данные если есть
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
$characteristicsFromRegistry = !$product->isNewRecord 
    ? \app\models\ProductCharacteristicValue::find()
        ->where(['product_id' => $product->id])
        ->with(['characteristic', 'characteristicValue'])
        ->all()
    : [];
?>

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
                $product->isNewRecord ? ['products'] : ['view-product', 'id' => $product->id], 
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
                            <?= $form->field($product, 'country')->textInput([
                                'maxlength' => true,
                                'placeholder' => 'Китай, Вьетнам, США...'
                            ])->label('Страна производства') ?>
                        </div>
                    </div>
                    
                    <div class="row">
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
                        <div class="col-md-6">
                            <?= $form->field($product, 'related_products_json')->textarea([
                                'rows' => 2,
                                'placeholder' => '{"productIds": [123, 456, 789]}'
                            ])->label('Связанные товары (JSON)')->hint('Массив ID связанных товаров') ?>
                        </div>
                    </div>
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

            <!-- Характеристики товара (Объединенные) -->
            <div class="card mt-3" id="section-specs">
                <div class="card-header d-flex justify-content-between align-items-center bg-light">
                    <h5 class="mb-0"><i class="bi bi-list-check"></i> Характеристики товара</h5>
                    <?php if (!$product->isNewRecord): ?>
                    <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#manageCharacteristicsModal">
                        <i class="bi bi-plus-circle"></i> Добавить характеристику
                    </button>
                    <?php endif; ?>
                </div>
                <div class="card-body">
                    <?php if (!$product->isNewRecord):
                        // Получаем характеристики из справочников
                        $hasRegistryChars = count($characteristicsFromRegistry) > 0;
                        $hasPoizonProps = !empty($properties);
                        
                        // Добавляем параметры продукта как характеристики
                        $productParams = [
                            ['key' => 'material', 'name' => 'Материал', 'value' => $product->material, 'type' => 'select', 'options' => [
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
                            ['key' => 'fastening', 'name' => 'Застежка', 'value' => $product->fastening, 'type' => 'select', 'options' => [
                                'laces' => 'Шнурки', 'velcro' => 'Липучки', 'zipper' => 'Молния', 'slip_on' => 'Без застежки'
                            ]],
                            ['key' => 'country', 'name' => 'Страна производства', 'value' => $product->country, 'type' => 'text'],
                            ['key' => 'style_code', 'name' => 'Артикул', 'value' => $product->style_code, 'type' => 'text'],
                            ['key' => 'release_year', 'name' => 'Дата релиза', 'value' => $product->release_year, 'type' => 'number'],
                            ['key' => 'weight', 'name' => 'Вес (граммы)', 'value' => $product->weight, 'type' => 'number'],
                        ];
                        
                        $hasProductParams = true;
                        
                        if ($hasRegistryChars || $hasPoizonProps || $hasProductParams):
                    ?>
                    <div class="table-responsive">
                        <table class="table table-sm mb-3" id="characteristicsTable">
                            <thead>
                                <tr>
                                    <th width="30%">Характеристика</th>
                                    <th width="45%">Значение</th>
                                    <th width="15%" class="text-center">Источник</th>
                                    <th width="10%" class="text-center">Действия</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                // 1. Параметры продукта (редактируемые inline)
                                foreach ($productParams as $param):
                                    if (!empty($param['value'])):
                                        $displayValue = $param['value'];
                                        if ($param['type'] === 'select' && isset($param['options'][$param['value']])) {
                                            $displayValue = $param['options'][$param['value']];
                                        }
                                ?>
                                    <tr class="product-param-row" data-param-key="<?= $param['key'] ?>">
                                        <td><?= Html::encode($param['name']) ?></td>
                                        <td>
                                            <div class="param-value-display"><?= Html::encode($displayValue) ?></div>
                                            <div class="param-value-edit" style="display: none;">
                                                <?php if ($param['type'] === 'select'): ?>
                                                    <select class="form-select form-select-sm param-edit-input" 
                                                            name="Product[<?= $param['key'] ?>]" 
                                                            data-original="<?= Html::encode($param['value']) ?>">
                                                        <option value="">Не выбрано</option>
                                                        <?php foreach ($param['options'] as $optKey => $optValue): ?>
                                                            <option value="<?= $optKey ?>" <?= $optKey === $param['value'] ? 'selected' : '' ?>>
                                                                <?= Html::encode($optValue) ?>
                                                            </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                <?php elseif ($param['type'] === 'number'): ?>
                                                    <input type="number" class="form-control form-control-sm param-edit-input" 
                                                           name="Product[<?= $param['key'] ?>]" 
                                                           value="<?= Html::encode($param['value']) ?>" 
                                                           data-original="<?= Html::encode($param['value']) ?>">
                                                <?php else: ?>
                                                    <input type="text" class="form-control form-control-sm param-edit-input" 
                                                           name="Product[<?= $param['key'] ?>]" 
                                                           value="<?= Html::encode($param['value']) ?>" 
                                                           data-original="<?= Html::encode($param['value']) ?>">
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-primary">Продукт</span>
                                        </td>
                                        <td class="text-center">
                                            <div class="btn-group btn-group-sm param-actions-display">
                                                <button type="button" class="btn btn-sm btn-link text-secondary p-0 px-1" 
                                                        onclick="editProductParam('<?= $param['key'] ?>')" title="Редактировать">
                                                    <i class="bi bi-pencil"></i>
                                                </button>
                                            </div>
                                            <div class="btn-group btn-group-sm param-actions-edit" style="display: none;">
                                                <button type="button" class="btn btn-sm btn-link text-success p-0 px-1" 
                                                        onclick="saveProductParam('<?= $param['key'] ?>')" title="Сохранить">
                                                    <i class="bi bi-check-lg"></i>
                                                </button>
                                                <button type="button" class="btn btn-sm btn-link text-secondary p-0 px-1" 
                                                        onclick="cancelEditProductParam('<?= $param['key'] ?>')" title="Отмена">
                                                    <i class="bi bi-x-lg"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php 
                                    endif;
                                endforeach; 
                                ?>
                                
                                <?php
                                // 2. Характеристики из справочников (редактируемые)
                                foreach ($characteristicsFromRegistry as $pcv): ?>
                                    <tr data-char-id="<?= $pcv->id ?>" class="editable-char-row">
                                        <td><?= Html::encode($pcv->characteristic->name) ?></td>
                                        <td>
                                            <div class="char-value-display">
                                                <?php if ($pcv->characteristicValue): ?>
                                                    <?= Html::encode($pcv->characteristicValue->value) ?>
                                                <?php elseif ($pcv->value_text): ?>
                                                    <?= Html::encode($pcv->value_text) ?>
                                                <?php elseif ($pcv->value_number !== null): ?>
                                                    <?= Html::encode($pcv->value_number) ?>
                                                <?php endif; ?>
                                            </div>
                                            <div class="char-value-edit" style="display: none;">
                                                <?php 
                                                $charType = $pcv->characteristic->type;
                                                if ($charType === 'select'): 
                                                    $values = \yii\helpers\ArrayHelper::map($pcv->characteristic->values, 'id', 'value');
                                                ?>
                                                    <select class="form-select form-select-sm char-edit-input" data-original="<?= $pcv->characteristic_value_id ?>">
                                                        <?php foreach ($values as $valId => $valName): ?>
                                                            <option value="<?= $valId ?>" <?= $valId == $pcv->characteristic_value_id ? 'selected' : '' ?>>
                                                                <?= Html::encode($valName) ?>
                                                            </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                <?php elseif ($charType === 'text'): ?>
                                                    <input type="text" class="form-control form-control-sm char-edit-input" 
                                                           value="<?= Html::encode($pcv->value_text) ?>" 
                                                           data-original="<?= Html::encode($pcv->value_text) ?>">
                                                <?php elseif ($charType === 'number'): ?>
                                                    <input type="number" step="0.01" class="form-control form-control-sm char-edit-input" 
                                                           value="<?= $pcv->value_number ?>" 
                                                           data-original="<?= $pcv->value_number ?>">
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-light text-dark border">Справочник</span>
                                        </td>
                                        <td class="text-center">
                                            <div class="btn-group btn-group-sm char-actions-display">
                                                <button type="button" class="btn btn-sm btn-link text-secondary p-0 px-1" 
                                                        onclick="editCharacteristic(<?= $pcv->id ?>)" title="Редактировать">
                                                    <i class="bi bi-pencil"></i>
                                                </button>
                                                <button type="button" class="btn btn-sm btn-link text-danger p-0 px-1" 
                                                        onclick="deleteCharacteristicInline(<?= $pcv->id ?>)" title="Удалить">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </div>
                                            <div class="btn-group btn-group-sm char-actions-edit" style="display: none;">
                                                <button type="button" class="btn btn-sm btn-link text-success p-0 px-1" 
                                                        onclick="saveCharacteristic(<?= $pcv->id ?>)" title="Сохранить">
                                                    <i class="bi bi-check-lg"></i>
                                                </button>
                                                <button type="button" class="btn btn-sm btn-link text-secondary p-0 px-1" 
                                                        onclick="cancelEditCharacteristic(<?= $pcv->id ?>)" title="Отмена">
                                                    <i class="bi bi-x-lg"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                
                                <?php
                                // 3. Характеристики из Poizon (редактируемые)
                                if ($hasPoizonProps):
                                    $propIndex = 0;
                                    foreach ($properties as $prop): 
                                        $propKey = 'poizon_prop_' . $propIndex;
                                    ?>
                                        <tr class="poizon-prop-row" data-prop-index="<?= $propIndex ?>" style="background-color: #f8f9fa;">
                                            <td>
                                                <div class="poizon-prop-key-display"><?= Html::encode($prop['key'] ?? '') ?></div>
                                                <div class="poizon-prop-key-edit" style="display: none;">
                                                    <input type="text" class="form-control form-control-sm" 
                                                           name="poizon_props[<?= $propIndex ?>][key]"
                                                           value="<?= Html::encode($prop['key'] ?? '') ?>" 
                                                           data-original="<?= Html::encode($prop['key'] ?? '') ?>">
                                                </div>
                                            </td>
                                            <td>
                                                <div class="poizon-prop-value-display"><?= Html::encode($prop['value'] ?? '') ?></div>
                                                <div class="poizon-prop-value-edit" style="display: none;">
                                                    <input type="text" class="form-control form-control-sm" 
                                                           name="poizon_props[<?= $propIndex ?>][value]"
                                                           value="<?= Html::encode($prop['value'] ?? '') ?>" 
                                                           data-original="<?= Html::encode($prop['value'] ?? '') ?>">
                                                </div>
                                            </td>
                                            <td class="text-center">
                                                <span class="badge bg-info">Poizon</span>
                                            </td>
                                            <td class="text-center">
                                                <div class="btn-group btn-group-sm poizon-prop-actions-display">
                                                    <button type="button" class="btn btn-sm btn-link text-secondary p-0 px-1" 
                                                            onclick="editPoizonProp(<?= $propIndex ?>)" title="Редактировать">
                                                        <i class="bi bi-pencil"></i>
                                                    </button>
                                                    <button type="button" class="btn btn-sm btn-link text-danger p-0 px-1" 
                                                            onclick="deletePoizonProp(<?= $propIndex ?>)" title="Удалить">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                </div>
                                                <div class="btn-group btn-group-sm poizon-prop-actions-edit" style="display: none;">
                                                    <button type="button" class="btn btn-sm btn-link text-success p-0 px-1" 
                                                            onclick="savePoizonProp(<?= $propIndex ?>)" title="Сохранить">
                                                        <i class="bi bi-check-lg"></i>
                                                    </button>
                                                    <button type="button" class="btn btn-sm btn-link text-secondary p-0 px-1" 
                                                            onclick="cancelEditPoizonProp(<?= $propIndex ?>)" title="Отмена">
                                                        <i class="bi bi-x-lg"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php 
                                        $propIndex++;
                                    endforeach;
                                endif; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="text-muted mt-2" style="font-size: 0.875rem;">
                        <i class="bi bi-info-circle"></i> 
                        Нажмите <i class="bi bi-pencil"></i> для редактирования значения. 
                        Для добавления новых характеристик используйте кнопку "Добавить характеристику".
                    </div>
                    <?php else: ?>
                    <div class="alert alert-light border mb-0">
                        <i class="bi bi-info-circle"></i> Характеристики не заполнены. 
                        <?php if ($product->poizon_id): ?>
                            Синхронизируйте товар с Poizon для автоматического импорта.
                        <?php else: ?>
                            Нажмите "Добавить характеристику" для добавления.
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                    
                    <?php else: ?>
                    <div class="alert alert-light border mb-0">
                        <i class="bi bi-info-circle"></i> Сохраните товар для редактирования характеристик.
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Размерная сетка -->
            <div class="card mt-3" id="section-sizes">
                <div class="card-header" style="background: #fd7e14; color: white;">
                    <h5 class="mb-0"><i class="bi bi-rulers"></i> Размерная сетка</h5>
                </div>
                <div class="card-body">
                    <?php if (!$product->isNewRecord): ?>
                    <?php 
                    $sizes = $product->getSizes()->orderBy(['us_size' => SORT_ASC])->all();
                    $sizesCount = count($sizes);
                    if ($sizesCount > 0):
                    ?>
                    <div class="table-responsive">
                        <table class="table table-sm table-hover">
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
                                            <strong class="text-success"><?= number_format($size->price_byn, 2) ?> ₽</strong>
                                        <?php elseif ($size->price): ?>
                                            <strong class="text-danger"><?= number_format($size->price, 2) ?> ₽</strong>
                                        <?php else: ?>
                                            <span class="text-muted">Общая (<?= number_format($product->price, 2) ?> ₽)</span>
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
                                            <span class="badge bg-secondary">Нет</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
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
                                                'data-confirm' => 'Удалить размер?',
                                            ]) ?>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <button type="button" class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#addSizeModal">
                        <i class="bi bi-plus-circle"></i> Добавить размер
                    </button>
                    <?php else: ?>
                    <div class="text-center text-muted py-3">
                        <i class="bi bi-rulers" style="font-size: 36px;"></i>
                        <p class="mt-2">Размеры еще не добавлены</p>
                        <button type="button" class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#addSizeModal">
                            <i class="bi bi-plus-circle"></i> Добавить первый размер
                        </button>
                    </div>
                    <?php endif; ?>
                    <?php else: ?>
                    <div class="alert alert-warning">
                        <i class="bi bi-exclamation-triangle"></i> Сначала сохраните товар, чтобы добавить размеры.
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Размерные сетки из Poizon (только для информации) -->
            <?php if (!empty($sizesData)): ?>
            <div class="card mt-3 border-info">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0"><i class="bi bi-rulers"></i> Размерные сетки Poizon (справочно)</h5>
                </div>
                <div class="card-body">
                    <div class="alert alert-light mb-3">
                        <i class="bi bi-info-circle"></i> 
                        <strong>Информация:</strong> Размерные сетки из Poizon уже интегрированы в основную таблицу размеров выше. 
                        Здесь показаны оригинальные данные для справки.
                    </div>
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered">
                            <thead class="table-light">
                                <tr>
                                    <th width="30%">Сетка</th>
                                    <th>Размеры</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($sizesData as $sizeGrid): ?>
                                <tr>
                                    <td><strong><?= Html::encode($sizeGrid['name'] ?? '') ?></strong></td>
                                    <td><small class="text-muted"><?= Html::encode($sizeGrid['value'] ?? '') ?></small></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Кнопки действий -->
            <div class="card mt-3 border-0 shadow">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <?= Html::submitButton('<i class="bi bi-check-circle-fill"></i> Сохранить изменения', [
                                'class' => 'btn btn-success btn-lg px-5'
                            ]) ?>
                            <?= Html::a('<i class="bi bi-x-circle"></i> Отмена', ['view-product', 'id' => $product->id], [
                                'class' => 'btn btn-secondary btn-lg px-4'
                            ]) ?>
                        </div>
                        <div>
                            <?= Html::a('<i class="bi bi-trash"></i> Удалить товар', ['delete-product', 'id' => $product->id], [
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
                    
                    <?= Html::a('<i class="bi bi-arrow-repeat"></i> Синхронизировать сейчас', ['sync-product', 'id' => $product->id], [
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
            <form action="<?= yii\helpers\Url::to(['add-image', 'productId' => $product->id, 'returnUrl' => 'edit-product']) ?>" method="post">
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
            <form action="<?= yii\helpers\Url::to(['add-size', 'productId' => $product->id, 'returnUrl' => 'edit-product']) ?>" method="post">
                <?= Html::hiddenInput(Yii::$app->request->csrfParam, Yii::$app->request->csrfToken) ?>
                <div class="modal-body">
                    <!-- Выбор размерной сетки -->
                    <?php
                    $sizeGrids = \app\models\SizeGrid::find()
                        ->where(['is_active' => 1])
                        ->andWhere([
                            'or',
                            ['brand_id' => $product->brand_id],
                            ['brand_id' => null]
                        ])
                        ->andWhere(['gender' => $product->gender ?? 'unisex'])
                        ->orderBy(['brand_id' => SORT_DESC, 'name' => SORT_ASC])
                        ->all();
                    
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
                            <a href="<?= yii\helpers\Url::to(['add-sizes-from-grid', 'productId' => $product->id, 'gridId' => '__GRID_ID__', 'returnUrl' => 'edit-product']) ?>" 
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

<script>
// Выбор размерной сетки
document.addEventListener('DOMContentLoaded', function() {
    const gridSelect = document.getElementById('size-grid-select');
    const addBtn = document.getElementById('add-from-grid-btn');
    
    if (gridSelect && addBtn) {
        gridSelect.addEventListener('change', function() {
            if (this.value) {
                const url = addBtn.getAttribute('href').replace('__GRID_ID__', this.value);
                addBtn.setAttribute('href', url);
                addBtn.style.display = 'inline-block';
            } else {
                addBtn.style.display = 'none';
            }
        });
    }
});
</script>
<?php endif; ?>

<style>
    /* Липкая навигация */
    .quick-nav-sticky {
        position: sticky;
        top: 0;
        z-index: 1000;
        background: white;
        border-bottom: 2px solid #e9ecef;
        padding: 15px 0;
        margin-bottom: 20px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    }
    
    .quick-nav-sticky .btn-group .btn {
        transition: all 0.3s ease;
    }
    
    .quick-nav-sticky .btn-group .btn:hover {
        transform: translateY(-2px);
    }

    .card {
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        border: none;
        scroll-margin-top: 100px; /* Отступ для якорей */
    }
    
    .card-header {
        border-bottom: 2px solid #e9ecef;
    }
    
    .form-control:focus {
        border-color: #667eea;
        box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
    }
    
    .btn-success {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border: none;
    }
    
    .btn-success:hover {
        background: linear-gradient(135deg, #5568d3 0%, #65398b 100%);
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
    }
    
    /* Плавная прокрутка */
    html {
        scroll-behavior: smooth;
    }
    
    .price-cny-badge:hover {
        transform: scale(1.05);
        transition: all 0.2s ease;
    }
</style>

<script>
// Копирование цены в юанях в буфер обмена
function copyToClipboard(text, element) {
    if (navigator.clipboard) {
        navigator.clipboard.writeText(text).then(function() {
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

<script>
// Подсветка активной секции в навигации
document.addEventListener('DOMContentLoaded', function() {
    const sections = document.querySelectorAll('[id^="section-"]');
    const navLinks = document.querySelectorAll('.quick-nav-sticky a');
    
    function highlightNav() {
        let current = '';
        sections.forEach(section => {
            const sectionTop = section.offsetTop;
            const sectionHeight = section.clientHeight;
            if (pageYOffset >= (sectionTop - 150)) {
                current = section.getAttribute('id');
            }
        });
        
        navLinks.forEach(link => {
            link.classList.remove('active');
            if (link.getAttribute('href') === '#' + current) {
                link.classList.add('active');
            }
        });
    }
    
    window.addEventListener('scroll', highlightNav);
});
</script>

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

<script>
// Глобальные переменные для управления характеристиками
const productId = <?= $product->id ?>;
let availableCharacteristics = [];
let currentCharacteristicId = null;

// ==================== INLINE EDITING ====================

// ==================== PRODUCT PARAMS INLINE EDITING ====================

// Включить режим редактирования параметра продукта
function editProductParam(key) {
    const row = document.querySelector(`tr[data-param-key="${key}"]`);
    if (!row) return;
    
    // Скрываем отображение, показываем редактирование
    row.querySelector('.param-value-display').style.display = 'none';
    row.querySelector('.param-value-edit').style.display = 'block';
    row.querySelector('.param-actions-display').style.display = 'none';
    row.querySelector('.param-actions-edit').style.display = 'block';
    
    // Фокус на поле ввода
    const input = row.querySelector('.param-edit-input');
    if (input) input.focus();
}

// Отмена редактирования параметра
function cancelEditProductParam(key) {
    const row = document.querySelector(`tr[data-param-key="${key}"]`);
    if (!row) return;
    
    // Восстанавливаем оригинальное значение
    const input = row.querySelector('.param-edit-input');
    if (input) {
        const original = input.dataset.original;
        input.value = original;
    }
    
    // Возвращаем к режиму просмотра
    row.querySelector('.param-value-display').style.display = 'block';
    row.querySelector('.param-value-edit').style.display = 'none';
    row.querySelector('.param-actions-display').style.display = 'block';
    row.querySelector('.param-actions-edit').style.display = 'none';
}

// Сохранить параметр продукта (сохранение происходит при submit формы)
function saveProductParam(key) {
    const row = document.querySelector(`tr[data-param-key="${key}"]`);
    if (!row) return;
    
    const input = row.querySelector('.param-edit-input');
    if (!input) return;
    
    const newValue = input.value;
    
    // Обновляем отображаемое значение
    const displayDiv = row.querySelector('.param-value-display');
    if (input.tagName === 'SELECT' && newValue) {
        const selectedText = input.options[input.selectedIndex].text;
        displayDiv.textContent = selectedText;
    } else {
        displayDiv.textContent = newValue || 'Не указано';
    }
    
    // Обновляем original значение
    input.dataset.original = newValue;
    
    // Возвращаем к режиму просмотра
    row.querySelector('.param-value-display').style.display = 'block';
    row.querySelector('.param-value-edit').style.display = 'none';
    row.querySelector('.param-actions-display').style.display = 'block';
    row.querySelector('.param-actions-edit').style.display = 'none';
    
    // Показываем сообщение о необходимости сохранить форму
    showInlineMessage('Не забудьте сохранить форму для применения изменений', 'warning');
}

// ==================== CHARACTERISTICS INLINE EDITING ====================

// Включить режим редактирования характеристики
function editCharacteristic(id) {
    const row = document.querySelector(`tr[data-char-id="${id}"]`);
    if (!row) return;
    
    // Скрываем отображение, показываем редактирование
    row.querySelector('.char-value-display').style.display = 'none';
    row.querySelector('.char-value-edit').style.display = 'block';
    row.querySelector('.char-actions-display').style.display = 'none';
    row.querySelector('.char-actions-edit').style.display = 'block';
    
    // Фокус на поле ввода
    const input = row.querySelector('.char-edit-input');
    if (input) input.focus();
}

// Отмена редактирования
function cancelEditCharacteristic(id) {
    const row = document.querySelector(`tr[data-char-id="${id}"]`);
    if (!row) return;
    
    // Восстанавливаем оригинальное значение
    const input = row.querySelector('.char-edit-input');
    if (input) {
        const original = input.dataset.original;
        if (input.tagName === 'SELECT') {
            input.value = original;
        } else {
            input.value = original;
        }
    }
    
    // Возвращаем к режиму просмотра
    row.querySelector('.char-value-display').style.display = 'block';
    row.querySelector('.char-value-edit').style.display = 'none';
    row.querySelector('.char-actions-display').style.display = 'block';
    row.querySelector('.char-actions-edit').style.display = 'none';
}

// Сохранить изменение характеристики
async function saveCharacteristic(id) {
    const row = document.querySelector(`tr[data-char-id="${id}"]`);
    if (!row) return;
    
    const input = row.querySelector('.char-edit-input');
    if (!input) return;
    
    const newValue = input.value;
    if (!newValue) {
        alert('Введите значение');
        return;
    }
    
    // Определяем тип значения
    let postData = `id=${id}&`;
    if (input.tagName === 'SELECT') {
        postData += `value_id=${newValue}`;
    } else if (input.type === 'number') {
        postData += `value_number=${newValue}`;
    } else {
        postData += `value_text=${encodeURIComponent(newValue)}`;
    }
    
    try {
        const response = await fetch('/admin/update-characteristic?id=' + id, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-CSRF-Token': $('meta[name="csrf-token"]').attr('content')
            },
            body: postData
        });
        
        const data = await response.json();
        
        if (data.success) {
            // Обновляем отображаемое значение
            const displayDiv = row.querySelector('.char-value-display');
            if (input.tagName === 'SELECT') {
                const selectedText = input.options[input.selectedIndex].text;
                displayDiv.innerHTML = `<span class="badge bg-primary">${selectedText}</span>`;
            } else {
                displayDiv.textContent = newValue;
            }
            
            // Обновляем original значение
            input.dataset.original = newValue;
            
            // Возвращаем к режиму просмотра
            row.querySelector('.char-value-display').style.display = 'block';
            row.querySelector('.char-value-edit').style.display = 'none';
            row.querySelector('.char-actions-display').style.display = 'block';
            row.querySelector('.char-actions-edit').style.display = 'none';
            
            // Показываем сообщение
            showInlineMessage('Характеристика обновлена', 'success');
        } else {
            alert('Ошибка: ' + data.message);
        }
    } catch (error) {
        console.error('Ошибка сохранения:', error);
        alert('Ошибка сохранения характеристики');
    }
}

// Удалить характеристику (inline)
async function deleteCharacteristicInline(id) {
    if (!confirm('Удалить эту характеристику?')) {
        return;
    }
    
    try {
        const response = await fetch(`/admin/delete-characteristic?id=${id}`, {
            method: 'POST',
            headers: {
                'X-CSRF-Token': $('meta[name="csrf-token"]').attr('content')
            }
        });
        
        const data = await response.json();
        
        if (data.success) {
            // Удаляем строку из таблицы
            const row = document.querySelector(`tr[data-char-id="${id}"]`);
            if (row) {
                row.remove();
            }
            
            showInlineMessage('Характеристика удалена', 'success');
        } else {
            alert('Ошибка: ' + data.message);
        }
    } catch (error) {
        console.error('Ошибка удаления:', error);
        alert('Ошибка удаления характеристики');
    }
}

// ==================== POIZON PROPS INLINE EDITING ====================

// Включить режим редактирования Poizon характеристики
function editPoizonProp(index) {
    const row = document.querySelector(`tr[data-prop-index="${index}"]`);
    if (!row) return;
    
    // Скрываем отображение, показываем редактирование
    row.querySelector('.poizon-prop-key-display').style.display = 'none';
    row.querySelector('.poizon-prop-key-edit').style.display = 'block';
    row.querySelector('.poizon-prop-value-display').style.display = 'none';
    row.querySelector('.poizon-prop-value-edit').style.display = 'block';
    row.querySelector('.poizon-prop-actions-display').style.display = 'none';
    row.querySelector('.poizon-prop-actions-edit').style.display = 'block';
    
    // Фокус на поле ввода значения
    const input = row.querySelector('.poizon-prop-value-edit input');
    if (input) input.focus();
}

// Отмена редактирования Poizon характеристики
function cancelEditPoizonProp(index) {
    const row = document.querySelector(`tr[data-prop-index="${index}"]`);
    if (!row) return;
    
    // Восстанавливаем оригинальные значения
    const keyInput = row.querySelector('.poizon-prop-key-edit input');
    const valueInput = row.querySelector('.poizon-prop-value-edit input');
    
    if (keyInput) keyInput.value = keyInput.dataset.original;
    if (valueInput) valueInput.value = valueInput.dataset.original;
    
    // Возвращаем к режиму просмотра
    row.querySelector('.poizon-prop-key-display').style.display = 'block';
    row.querySelector('.poizon-prop-key-edit').style.display = 'none';
    row.querySelector('.poizon-prop-value-display').style.display = 'block';
    row.querySelector('.poizon-prop-value-edit').style.display = 'none';
    row.querySelector('.poizon-prop-actions-display').style.display = 'block';
    row.querySelector('.poizon-prop-actions-edit').style.display = 'none';
}

// Сохранить Poizon характеристику (сохранение происходит при submit формы)
function savePoizonProp(index) {
    const row = document.querySelector(`tr[data-prop-index="${index}"]`);
    if (!row) return;
    
    const keyInput = row.querySelector('.poizon-prop-key-edit input');
    const valueInput = row.querySelector('.poizon-prop-value-edit input');
    
    if (!keyInput || !valueInput) return;
    
    const newKey = keyInput.value;
    const newValue = valueInput.value;
    
    // Обновляем отображаемые значения
    row.querySelector('.poizon-prop-key-display').textContent = newKey;
    row.querySelector('.poizon-prop-value-display').textContent = newValue;
    
    // Обновляем original значения
    keyInput.dataset.original = newKey;
    valueInput.dataset.original = newValue;
    
    // Возвращаем к режиму просмотра
    row.querySelector('.poizon-prop-key-display').style.display = 'block';
    row.querySelector('.poizon-prop-key-edit').style.display = 'none';
    row.querySelector('.poizon-prop-value-display').style.display = 'block';
    row.querySelector('.poizon-prop-value-edit').style.display = 'none';
    row.querySelector('.poizon-prop-actions-display').style.display = 'block';
    row.querySelector('.poizon-prop-actions-edit').style.display = 'none';
    
    // Показываем сообщение о необходимости сохранить форму
    showInlineMessage('Не забудьте сохранить форму для применения изменений', 'warning');
}

// Удалить Poizon характеристику
function deletePoizonProp(index) {
    if (!confirm('Удалить эту характеристику?')) {
        return;
    }
    
    const row = document.querySelector(`tr[data-prop-index="${index}"]`);
    if (row) {
        row.remove();
        showInlineMessage('Характеристика будет удалена при сохранении формы', 'warning');
    }
}

// Показать inline сообщение
function showInlineMessage(message, type) {
    const container = document.getElementById('characteristicsTable');
    if (!container) return;
    
    // Создаем временное сообщение
    const alert = document.createElement('div');
    alert.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
    alert.style.cssText = 'top: 80px; right: 20px; z-index: 9999; min-width: 300px;';
    alert.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    document.body.appendChild(alert);
    
    setTimeout(() => {
        alert.remove();
    }, 3000);
}

// ==================== MODAL CHARACTERISTICS ====================

// Загрузка списка доступных характеристик
async function loadCharacteristics() {
    try {
        const response = await fetch(`/admin/get-characteristics?productId=${productId}`);
        const data = await response.json();
        
        if (data.success) {
            availableCharacteristics = data.characteristics;
            populateCharacteristicSelect();
        }
    } catch (error) {
        console.error('Ошибка загрузки характеристик:', error);
    }
}

// Заполнение списка характеристик
function populateCharacteristicSelect() {
    const select = document.getElementById('characteristicSelect');
    select.innerHTML = '<option value="">Выберите характеристику...</option>';
    
    availableCharacteristics.forEach(char => {
        const option = document.createElement('option');
        option.value = char.id;
        option.textContent = char.name;
        option.dataset.type = char.type;
        option.dataset.values = JSON.stringify(char.values);
        select.appendChild(option);
    });
    
    const newOption = document.createElement('option');
    newOption.value = '__new__';
    newOption.textContent = '➕ Создать новую...';
    newOption.className = 'text-primary fw-bold';
    select.appendChild(newOption);
}

// Обработка выбора характеристики
document.addEventListener('DOMContentLoaded', function() {
    const charSelect = document.getElementById('characteristicSelect');
    if (charSelect) {
        charSelect.addEventListener('change', function() {
            const selectedValue = this.value;
            
            if (selectedValue === '__new__') {
                document.getElementById('newCharacteristicForm').style.display = 'block';
                document.getElementById('valueContainer').style.display = 'none';
                document.getElementById('addCharBtn').disabled = true;
            } else if (selectedValue) {
                document.getElementById('newCharacteristicForm').style.display = 'none';
                const option = this.options[this.selectedIndex];
                const type = option.dataset.type;
                const values = JSON.parse(option.dataset.values || '{}');
                
                currentCharacteristicId = selectedValue;
                showValueInput(type, values);
                document.getElementById('addCharBtn').disabled = false;
            } else {
                document.getElementById('valueContainer').style.display = 'none';
                document.getElementById('addCharBtn').disabled = true;
            }
        });
    }
    
    // Загружаем характеристики при открытии модального окна
    const modal = document.getElementById('manageCharacteristicsModal');
    if (modal) {
        modal.addEventListener('show.bs.modal', function() {
            loadCharacteristics();
        });
    }
});

// Показать нужное поле ввода значения
function showValueInput(type, values) {
    document.getElementById('valueSelect').style.display = 'none';
    document.getElementById('valueText').style.display = 'none';
    document.getElementById('valueNumber').style.display = 'none';
    document.getElementById('valueContainer').style.display = 'block';
    
    if (type === 'select' || type === 'multiselect') {
        const select = document.getElementById('characteristicValueSelect');
        select.innerHTML = '<option value="">Выберите значение...</option>';
        
        for (const [id, value] of Object.entries(values)) {
            const option = document.createElement('option');
            option.value = id;
            option.textContent = value;
            select.appendChild(option);
        }
        
        const newOption = document.createElement('option');
        newOption.value = '__new__';
        newOption.textContent = '➕ Добавить новое значение...';
        newOption.className = 'text-primary fw-bold';
        select.appendChild(newOption);
        
        select.onchange = function() {
            if (this.value === '__new__') {
                document.getElementById('newValueForm').style.display = 'block';
            } else {
                document.getElementById('newValueForm').style.display = 'none';
            }
        };
        
        document.getElementById('valueSelect').style.display = 'block';
    } else if (type === 'text') {
        document.getElementById('valueText').style.display = 'block';
    } else if (type === 'number') {
        document.getElementById('valueNumber').style.display = 'block';
    }
}

// Создание новой характеристики
async function createNewCharacteristic() {
    const name = document.getElementById('newCharName').value.trim();
    const type = document.getElementById('newCharType').value;
    
    if (!name) {
        showMessage('Введите название характеристики', 'danger');
        return;
    }
    
    try {
        const response = await fetch('/admin/create-characteristic', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-CSRF-Token': $('meta[name="csrf-token"]').attr('content')
            },
            body: `name=${encodeURIComponent(name)}&type=${type}`
        });
        
        const data = await response.json();
        
        if (data.success) {
            showMessage(data.message, 'success');
            await loadCharacteristics();
            
            // Выбираем созданную характеристику
            document.getElementById('characteristicSelect').value = data.data.id;
            document.getElementById('characteristicSelect').dispatchEvent(new Event('change'));
            document.getElementById('newCharacteristicForm').style.display = 'none';
            document.getElementById('newCharName').value = '';
        } else {
            showMessage(data.message, 'danger');
        }
    } catch (error) {
        showMessage('Ошибка создания характеристики', 'danger');
        console.error(error);
    }
}

function cancelNewCharacteristic() {
    document.getElementById('newCharacteristicForm').style.display = 'none';
    document.getElementById('characteristicSelect').value = '';
    document.getElementById('newCharName').value = '';
}

// Создание нового значения
async function createNewValue() {
    const value = document.getElementById('newValueInput').value.trim();
    
    if (!value) {
        showMessage('Введите значение', 'danger');
        return;
    }
    
    try {
        const response = await fetch('/admin/create-characteristic-value', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-CSRF-Token': $('meta[name="csrf-token"]').attr('content')
            },
            body: `characteristic_id=${currentCharacteristicId}&value=${encodeURIComponent(value)}`
        });
        
        const data = await response.json();
        
        if (data.success) {
            showMessage(data.message, 'success');
            
            // Добавляем новое значение в список
            const select = document.getElementById('characteristicValueSelect');
            const newOption = document.createElement('option');
            newOption.value = data.data.id;
            newOption.textContent = data.data.value;
            newOption.selected = true;
            select.insertBefore(newOption, select.lastElementChild);
            
            document.getElementById('newValueForm').style.display = 'none';
            document.getElementById('newValueInput').value = '';
        } else {
            showMessage(data.message, 'danger');
        }
    } catch (error) {
        showMessage('Ошибка создания значения', 'danger');
        console.error(error);
    }
}

function cancelNewValue() {
    document.getElementById('newValueForm').style.display = 'none';
    document.getElementById('characteristicValueSelect').value = '';
    document.getElementById('newValueInput').value = '';
}

// Добавление характеристики к товару
async function addCharacteristicToProduct() {
    const charId = document.getElementById('characteristicSelect').value;
    
    if (!charId || charId === '__new__') {
        showMessage('Выберите характеристику', 'danger');
        return;
    }
    
    // Получаем значение в зависимости от типа
    let valueId = null;
    let valueText = null;
    let valueNumber = null;
    
    const charOption = document.querySelector(`#characteristicSelect option[value="${charId}"]`);
    const type = charOption.dataset.type;
    
    if (type === 'select' || type === 'multiselect') {
        valueId = document.getElementById('characteristicValueSelect').value;
        if (!valueId || valueId === '__new__') {
            showMessage('Выберите или создайте значение', 'danger');
            return;
        }
    } else if (type === 'text') {
        valueText = document.getElementById('characteristicValueText').value.trim();
        if (!valueText) {
            showMessage('Введите текстовое значение', 'danger');
            return;
        }
    } else if (type === 'number') {
        valueNumber = document.getElementById('characteristicValueNumber').value;
        if (!valueNumber) {
            showMessage('Введите числовое значение', 'danger');
            return;
        }
    }
    
    try {
        const response = await fetch('/admin/add-characteristic', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-CSRF-Token': $('meta[name="csrf-token"]').attr('content')
            },
            body: `product_id=${productId}&characteristic_id=${charId}&value_id=${valueId || ''}&value_text=${encodeURIComponent(valueText || '')}&value_number=${valueNumber || ''}`
        });
        
        const data = await response.json();
        
        if (data.success) {
            showMessage(data.message, 'success');
            
            // Закрываем модальное окно
            const modal = bootstrap.Modal.getInstance(document.getElementById('manageCharacteristicsModal'));
            if (modal) {
                modal.hide();
            }
            
            // Перезагружаем страницу для обновления таблицы
            setTimeout(() => location.reload(), 1000);
        } else {
            showMessage(data.message, 'danger');
        }
    } catch (error) {
        showMessage('Ошибка добавления характеристики', 'danger');
        console.error(error);
    }
}

// Удаление характеристики
async function deleteCharacteristic(id) {
    if (!confirm('Удалить эту характеристику?')) {
        return;
    }
    
    try {
        const response = await fetch(`/admin/delete-characteristic?id=${id}`, {
            method: 'POST',
            headers: {
                'X-CSRF-Token': $('meta[name="csrf-token"]').attr('content')
            }
        });
        
        const data = await response.json();
        
        if (data.success) {
            showMessage(data.message, 'success');
            
            // Удаляем из списка
            const item = document.querySelector(`[data-id="${id}"]`);
            if (item) {
                item.remove();
            }
            
            // Перезагружаем страницу через 1 сек для обновления таблицы
            setTimeout(() => location.reload(), 1000);
        } else {
            showMessage(data.message, 'danger');
        }
    } catch (error) {
        showMessage('Ошибка удаления характеристики', 'danger');
        console.error(error);
    }
}

// Показать сообщение
function showMessage(message, type) {
    const container = document.getElementById('addCharMessage');
    container.innerHTML = `
        <div class="alert alert-${type} alert-dismissible fade show" role="alert">
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    `;
    
    setTimeout(() => {
        container.innerHTML = '';
    }, 5000);
}
</script>
<?php endif; ?>
