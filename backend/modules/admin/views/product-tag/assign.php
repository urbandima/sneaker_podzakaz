<?php
/**
 * Массовое назначение тегов товарам
 * 
 * @var array $products
 * @var array $tags
 */

use yii\helpers\Html;
use yii\helpers\Url;

$this->title = 'Массовое назначение тегов';
$this->params['breadcrumbs'][] = ['label' => 'Теги', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="tag-assign">
    <div class="page-header">
        <h1><?= Html::encode($this->title) ?></h1>
        <?= Html::a('← Назад к списку', ['index'], ['class' => 'btn btn-link']) ?>
    </div>

    <form action="<?= Url::to(['assign']) ?>" method="post" class="assign-form">
        <input type="hidden" name="<?= Yii::$app->request->csrfParam ?>" value="<?= Yii::$app->request->csrfToken ?>">

        <div class="assign-layout">
            <!-- Выбор товаров -->
            <div class="assign-section">
                <h2 class="assign-section-title">
                    1. Выберите товары 
                    <span class="selected-count">(выбрано: <span id="products-count">0</span>)</span>
                </h2>
                
                <div class="search-box">
                    <input type="text" id="product-search" class="form-control" 
                           placeholder="Поиск товаров по названию или SKU...">
                </div>

                <div class="products-list" id="products-list">
                    <?php foreach ($products as $product): ?>
                        <label class="product-item" data-name="<?= strtolower(Html::encode($product['name'])) ?>" data-sku="<?= strtolower(Html::encode($product['sku'] ?? '')) ?>">
                            <input type="checkbox" name="product_ids[]" value="<?= $product['id'] ?>" class="product-checkbox">
                            <span class="product-info">
                                <span class="product-name"><?= Html::encode($product['name']) ?></span>
                                <?php if ($product['sku']): ?>
                                    <span class="product-sku">SKU: <?= Html::encode($product['sku']) ?></span>
                                <?php endif; ?>
                            </span>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Выбор тегов -->
            <div class="assign-section">
                <h2 class="assign-section-title">
                    2. Выберите теги
                    <span class="selected-count">(выбрано: <span id="tags-count">0</span>)</span>
                </h2>

                <div class="tags-list">
                    <?php foreach ($tags as $tag): ?>
                        <label class="tag-item" <?= $tag->color ? 'style="--tag-color: ' . $tag->color . '"' : '' ?>">
                            <input type="checkbox" name="tag_ids[]" value="<?= $tag->id ?>" class="tag-checkbox">
                            <span class="tag-name"><?= Html::encode($tag->name) ?></span>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Опции -->
        <div class="assign-options">
            <h2 class="assign-section-title">3. Настройки</h2>
            
            <label class="option-item">
                <input type="checkbox" name="replace_existing" value="1">
                <span class="option-text">
                    <strong>Заменить существующие теги</strong>
                    <span class="option-hint">Если отключено — новые теги добавятся к существующим</span>
                </span>
            </label>
        </div>

        <!-- Кнопка -->
        <div class="assign-actions">
            <button type="submit" class="btn btn-primary" id="submit-btn" disabled>
                Назначить теги
            </button>
        </div>
    </form>
</div>

<?php
$this->registerCss("
.tag-assign {
    padding: 20px;
    max-width: 1200px;
}

.page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 24px;
}

.page-header h1 {
    margin: 0;
    font-size: 24px;
}

.assign-form {
    background: #fff;
    border-radius: 12px;
    padding: 24px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.assign-layout {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 40px;
    margin-bottom: 30px;
}

@media (max-width: 768px) {
    .assign-layout {
        grid-template-columns: 1fr;
    }
}

.assign-section {
    min-height: 400px;
}

.assign-section-title {
    font-size: 16px;
    font-weight: 600;
    margin-bottom: 16px;
    display: flex;
    align-items: center;
    gap: 10px;
}

.selected-count {
    font-size: 13px;
    color: #6b7280;
    font-weight: 400;
}

.search-box {
    margin-bottom: 12px;
}

.search-box input {
    width: 100%;
    padding: 10px 14px;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    font-size: 14px;
}

.products-list {
    max-height: 400px;
    overflow-y: auto;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
}

.product-item {
    display: flex;
    align-items: flex-start;
    gap: 10px;
    padding: 12px;
    border-bottom: 1px solid #f3f4f6;
    cursor: pointer;
    transition: background 0.2s;
}

.product-item:hover {
    background: #f9fafb;
}

.product-item.hidden {
    display: none;
}

.product-item input[type=\"checkbox\"] {
    margin-top: 2px;
    flex-shrink: 0;
}

.product-info {
    flex: 1;
    min-width: 0;
}

.product-name {
    display: block;
    font-size: 14px;
    color: #111827;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.product-sku {
    display: block;
    font-size: 12px;
    color: #6b7280;
    margin-top: 2px;
}

.tags-list {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
}

.tag-item {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 8px 14px;
    background: var(--tag-color, #f3f4f6);
    border-radius: 20px;
    cursor: pointer;
    transition: all 0.2s;
    font-size: 13px;
}

.tag-item:has(input:checked) {
    box-shadow: 0 0 0 2px #000;
}

.tag-item input[type=\"checkbox\"] {
    display: none;
}

.tag-name {
    color: var(--tag-color) ? '#fff' : '#374151';
}

.assign-options {
    background: #f9fafb;
    padding: 20px;
    border-radius: 8px;
    margin-bottom: 20px;
}

.option-item {
    display: flex;
    align-items: flex-start;
    gap: 10px;
    cursor: pointer;
}

.option-item input {
    margin-top: 3px;
}

.option-text {
    display: flex;
    flex-direction: column;
}

.option-hint {
    font-size: 12px;
    color: #6b7280;
    margin-top: 2px;
}

.assign-actions {
    display: flex;
    justify-content: center;
}

.btn {
    padding: 12px 30px;
    border-radius: 8px;
    font-size: 14px;
    font-weight: 500;
    cursor: pointer;
    border: none;
    transition: all 0.2s;
}

.btn-primary {
    background: #000;
    color: #fff;
}

.btn-primary:hover:not(:disabled) {
    background: #374151;
}

.btn-primary:disabled {
    background: #d1d5db;
    cursor: not-allowed;
}

.btn-link {
    background: transparent;
    color: #374151;
    padding: 8px 16px;
    text-decoration: none;
}

.btn-link:hover {
    color: #000;
}
");

// JS для фильтрации товаров и подсчета выбранных
$this->registerJs("
// Поиск товаров
$('#product-search').on('input', function() {
    var query = $(this).val().toLowerCase();
    $('.product-item').each(function() {
        var name = $(this).data('name');
        var sku = $(this).data('sku');
        if (name.indexOf(query) !== -1 || sku.indexOf(query) !== -1) {
            $(this).removeClass('hidden');
        } else {
            $(this).addClass('hidden');
        }
    });
});

// Подсчет выбранных товаров
function updateProductsCount() {
    var count = $('.product-checkbox:checked').length;
    $('#products-count').text(count);
    updateSubmitButton();
}

// Подсчет выбранных тегов
function updateTagsCount() {
    var count = $('.tag-checkbox:checked').length;
    $('#tags-count').text(count);
    updateSubmitButton();
}

// Активация кнопки
function updateSubmitButton() {
    var productsCount = $('.product-checkbox:checked').length;
    var tagsCount = $('.tag-checkbox:checked').length;
    $('#submit-btn').prop('disabled', productsCount === 0 || tagsCount === 0);
}

$('.product-checkbox, .tag-checkbox').on('change', function() {
    updateProductsCount();
    updateTagsCount();
});

// Клик по label активирует чекбокс
$('.product-item, .tag-item').on('click', function(e) {
    if (e.target.tagName !== 'INPUT') {
        var checkbox = $(this).find('input[type=\"checkbox\"]');
        checkbox.prop('checked', !checkbox.prop('checked')).trigger('change');
    }
});
");
?>
