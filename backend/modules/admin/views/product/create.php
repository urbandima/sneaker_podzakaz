<?php

/** @var yii\web\View $this */
/** @var app\backend\modules\catalog\models\Product $model */
/** @var array $brands */
/** @var array $categories */

use yii\helpers\Html;
use yii\helpers\ArrayHelper;
use yii\widgets\ActiveForm;

$this->title = 'Новый товар';

$this->params['headerActions'] = [
    Html::a('<i class="bi bi-arrow-left"></i> К товарам', ['/admin/product'], ['class' => 'admin-btn admin-btn-secondary admin-btn-sm'])
];
?>

<?php $form = ActiveForm::begin([
    'options' => ['id' => 'product-create-form'],
    'fieldConfig' => [
        'template' => "<div class=\"admin-form-group\">{label}{input}{hint}{error}</div>",
        'labelOptions' => ['class' => 'admin-form-label'],
        'inputOptions' => ['class' => 'admin-form-input'],
        'hintOptions' => ['class' => 'admin-form-hint', 'tag' => 'div'],
        'errorOptions' => ['class' => 'admin-form-error', 'tag' => 'div'],
    ],
]); ?>

<div class="product-create-layout">

    <!-- LEFT: Main Content -->
    <div class="product-create-main">

        <!-- Основная информация -->
        <div class="admin-card">
            <div class="admin-card-header">
                <h2 class="admin-card-title"><i class="bi bi-info-circle"></i> Основная информация</h2>
            </div>
            <div class="admin-card-body">
                <?= $form->field($model, 'name')->textInput(['maxlength' => true, 'placeholder' => 'Например: Nike Air Force 1 Low White', 'autofocus' => true]) ?>

                <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px">
                    <?= $form->field($model, 'brand_id')->dropDownList(
                        ArrayHelper::map($brands ?? [], 'id', 'name'),
                        ['prompt' => 'Выберите бренд', 'class' => 'admin-form-input']
                    ) ?>

                    <?= $form->field($model, 'category_id')->dropDownList(
                        ArrayHelper::map($categories ?? [], 'id', 'name'),
                        ['prompt' => 'Выберите категорию', 'class' => 'admin-form-input']
                    ) ?>
                </div>

                <?= $form->field($model, 'description')->textarea(['rows' => 5, 'placeholder' => 'Описание товара, материалы, особенности...']) ?>
            </div>
        </div>

        <!-- Цены и инвентарь -->
        <div class="admin-card">
            <div class="admin-card-header">
                <h2 class="admin-card-title"><i class="bi bi-currency-exchange"></i> Цены и инвентарь</h2>
            </div>
            <div class="admin-card-body">
                <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:16px">
                    <?= $form->field($model, 'price')->input('number', ['step' => '0.01', 'min' => 0, 'placeholder' => '0.00'])->label('Цена, BYN *') ?>

                    <?= $form->field($model, 'poizon_price_cny')->input('number', ['step' => '0.01', 'min' => 0, 'placeholder' => '0.00'])->label('Цена, CNY') ?>

                    <div class="admin-form-group">
                        <label class="admin-form-label">Расчётная цена</label>
                        <div class="admin-form-input" id="calc-price-preview" style="background:var(--admin-surface-hover);color:var(--admin-text-secondary);display:flex;align-items:center;gap:4px">
                            <i class="bi bi-calculator"></i> <span id="calc-price-val">—</span>
                        </div>
                        <div class="admin-form-hint">По курсу CNY → BYN</div>
                    </div>
                </div>

                <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:16px;margin-top:4px">
                    <?= $form->field($model, 'sku')->textInput(['maxlength' => true, 'placeholder' => 'ABC-123'])->label('Артикул (SKU)') ?>

                    <?= $form->field($model, 'stock_count')->input('number', ['min' => 0, 'placeholder' => '0'])->label('Кол-во на складе') ?>

                    <div class="admin-form-group">
                        <label class="admin-form-label">Наличие</label>
                        <?= Html::activeDropDownList($model, 'stock_status', [
                            'in_stock' => 'В наличии',
                            'out_of_stock' => 'Нет в наличии',
                            'preorder' => 'Предзаказ',
                            'on_order' => 'Под заказ',
                        ], ['class' => 'admin-form-input']) ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- SEO -->
        <div class="admin-card">
            <div class="admin-card-header">
                <h2 class="admin-card-title"><i class="bi bi-search"></i> SEO</h2>
                <button type="button" class="admin-btn admin-btn-secondary admin-btn-sm" onclick="autoFillSeo()">
                    <i class="bi bi-magic"></i> Авто-заполнить
                </button>
            </div>
            <div class="admin-card-body">
                <?= $form->field($model, 'meta_title')->textInput(['maxlength' => 70, 'placeholder' => 'SEO заголовок', 'oninput' => 'updateSeoPreview()'])->hint('50–60 символов') ?>

                <?= $form->field($model, 'meta_description')->textarea(['rows' => 2, 'maxlength' => 160, 'placeholder' => 'Краткое описание для поисковиков', 'oninput' => 'updateSeoPreview()'])->hint('150–160 символов') ?>

                <?= $form->field($model, 'meta_keywords')->textInput(['maxlength' => 255, 'placeholder' => 'ключевое слово 1, ключевое слово 2'])->hint('Через запятую') ?>

                <!-- SEO Preview -->
                <div id="seo-preview" style="background:var(--admin-surface-hover);border-radius:var(--admin-radius-sm);padding:12px 16px;margin-top:12px">
                    <div style="font-size:11px;color:var(--admin-text-secondary);text-transform:uppercase;letter-spacing:.04em;margin-bottom:8px">Превью в Google</div>
                    <div id="seo-title" style="color:#1a0dab;font-size:18px;line-height:1.3;margin-bottom:2px">Название товара</div>
                    <div style="color:#006621;font-size:13px;margin-bottom:2px">sneakerhead.by/catalog/...</div>
                    <div id="seo-desc" style="color:#545454;font-size:14px;line-height:1.4">Описание страницы в поисковике</div>
                </div>
            </div>
        </div>

    </div>

    <!-- RIGHT: Sidebar -->
    <aside class="product-create-sidebar">

        <!-- Публикация -->
        <div class="admin-card">
            <div class="admin-card-header">
                <h2 class="admin-card-title"><i class="bi bi-send"></i> Публикация</h2>
            </div>
            <div class="admin-card-body">
                <div class="admin-form-group" style="margin-bottom:1rem">
                    <label class="admin-form-label">Статус</label>
                    <label style="display:flex;align-items:center;gap:8px;cursor:pointer;padding:8px 12px;background:var(--admin-surface-hover);border-radius:var(--admin-radius-sm)">
                        <?= Html::activeCheckbox($model, 'is_active', ['label' => false, 'id' => 'is_active_chk']) ?>
                        <span style="font-size:13px;font-weight:500">Активен (опубликован)</span>
                    </label>
                </div>
                <?php if ($model->hasAttribute('is_featured')): ?>
                <div class="admin-form-group">
                    <label style="display:flex;align-items:center;gap:8px;cursor:pointer;padding:8px 12px;background:var(--admin-surface-hover);border-radius:var(--admin-radius-sm)">
                        <?= Html::activeCheckbox($model, 'is_featured', ['label' => false]) ?>
                        <span style="font-size:13px;font-weight:500"><i class="bi bi-star"></i> Рекомендуемый</span>
                    </label>
                </div>
                <?php endif; ?>
            </div>
            <div style="padding:12px 20px;border-top:1px solid var(--admin-border);display:flex;gap:8px">
                <?= Html::submitButton('<i class="bi bi-check-circle"></i> Создать товар', ['class' => 'admin-btn admin-btn-primary', 'style' => 'flex:1']) ?>
            </div>
        </div>

        <!-- Изображения -->
        <div class="admin-card">
            <div class="admin-card-header">
                <h2 class="admin-card-title"><i class="bi bi-images"></i> Изображения</h2>
            </div>
            <div class="admin-card-body" style="text-align:center;padding:2rem 1rem">
                <i class="bi bi-cloud-upload" style="font-size:2.5rem;color:var(--admin-text-secondary);display:block;margin-bottom:0.5rem"></i>
                <p style="font-size:0.875rem;color:var(--admin-text-secondary);margin-bottom:0">
                    Загрузка фото будет доступна после создания товара
                </p>
            </div>
        </div>

        <!-- Изображение с URL -->
        <div class="admin-card">
            <div class="admin-card-header">
                <h2 class="admin-card-title"><i class="bi bi-link-45deg"></i> Обложка</h2>
            </div>
            <div class="admin-card-body">
                <?= $form->field($model, 'main_image')->textInput(['placeholder' => 'https://... или /uploads/...'])->label('URL изображения') ?>
                <div id="image-preview-wrap" style="display:none;margin-top:8px">
                    <img id="image-preview" src="data:," alt="" style="max-width:100%;border-radius:var(--admin-radius-sm)">
                </div>
            </div>
        </div>

    </aside>
</div>

<?php ActiveForm::end(); ?>

<style>
.product-create-layout {
    display: grid;
    grid-template-columns: 1fr 300px;
    gap: 16px;
    align-items: start;
}
.product-create-main { display: flex; flex-direction: column; gap: 16px; }
.product-create-sidebar { display: flex; flex-direction: column; gap: 16px; position: sticky; top: 80px; }
.admin-form-hint { font-size: 11px; color: var(--admin-text-secondary); margin-top: 4px; }
.admin-form-error { font-size: 11px; color: var(--admin-danger, #dc3545); margin-top: 4px; }
@media (max-width: 900px) {
    .product-create-layout { grid-template-columns: 1fr; }
    .product-create-sidebar { position: static; }
}
</style>

<script>
// Image URL preview
var imgInput = document.querySelector('[id$="main_image"]');
if (imgInput) {
    imgInput.addEventListener('input', function() {
        var wrap = document.getElementById('image-preview-wrap');
        var img = document.getElementById('image-preview');
        if (this.value) {
            img.src = this.value;
            wrap.style.display = 'block';
        } else {
            wrap.style.display = 'none';
        }
    });
}

// SEO preview updater
function updateSeoPreview() {
    var titleEl = document.querySelector('[id$="meta_title"]');
    var descEl = document.querySelector('[id$="meta_description"]');
    var nameEl = document.querySelector('[id$="name"]');
    var title = titleEl?.value || nameEl?.value || 'Название товара';
    var desc = descEl?.value || 'Описание страницы в поисковике';
    document.getElementById('seo-title').textContent = title;
    document.getElementById('seo-desc').textContent = desc;
}

function autoFillSeo() {
    var nameEl = document.querySelector('[id$="name"]');
    var brandEl = document.querySelector('[id$="brand_id"]');
    var titleEl = document.querySelector('[id$="meta_title"]');
    var descEl = document.querySelector('[id$="meta_description"]');
    var kwEl = document.querySelector('[id$="meta_keywords"]');
    var name = nameEl?.value || '';
    var brand = brandEl?.options[brandEl.selectedIndex]?.text || '';
    if (titleEl && !titleEl.value) titleEl.value = name + (brand ? ' — купить в Минске' : '');
    if (descEl && !descEl.value) descEl.value = 'Купить ' + name + ' в СНИКЕРХЭД. Оригинальные кроссовки с доставкой по Беларуси.';
    if (kwEl && !kwEl.value) kwEl.value = name.toLowerCase() + (brand ? ', ' + brand.toLowerCase() : '') + ', купить кроссовки';
    updateSeoPreview();
}

// CNY price calculator
var cnyInput = document.querySelector('[id$="poizon_price_cny"]');
if (cnyInput) {
    cnyInput.addEventListener('input', function() {
        var rate = 0.45; // default
        var byn = parseFloat(this.value || 0) * rate;
        document.getElementById('calc-price-val').textContent = byn > 0 ? byn.toFixed(2) + ' BYN' : '—';
    });
}

// Init SEO preview
document.addEventListener('DOMContentLoaded', updateSeoPreview);
</script>
