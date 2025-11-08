# ПЛАН УЛУЧШЕНИЯ УМНОГО ФИЛЬТРА

**Дата**: 02.11.2025, 00:15  
**Текущая оценка**: 6.2/10  
**Целевая оценка**: 9.5/10  

---

## 🔴 ЭТАП 1: КРИТИЧНЫЕ ИСПРАВЛЕНИЯ (2-3 часа)

### 1.1. Завершить AJAX фильтрацию ⚠️ БЛОКЕР
**Файл**: `web/js/catalog.js`  
**Проблема**: Функция `applyFiltersAjax()` не завершена (строка 99-104)

**Что сделать**:
```javascript
function applyFiltersAjax() {
    showLoadingIndicator();

    const formData = new FormData();
    formData.append('brands', JSON.stringify(filterState.brands));
    formData.append('categories', JSON.stringify(filterState.categories));
    formData.append('price_from', filterState.priceFrom || '');
    formData.append('price_to', filterState.priceTo || '');
    formData.append('sort', filterState.sortBy);
    formData.append('page', filterState.page);
    formData.append('perPage', filterState.perPage);

    fetch(CONFIG.ajaxFilterUrl, {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-Token': getCsrfToken(),
        },
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            renderProducts(data.products);
            updateFilterCounts(data.filters);
            updatePagination(data.pagination);
            updateURL();
            saveFilterHistory(data);
        } else {
            showError('Ошибка загрузки товаров');
        }
        hideLoadingIndicator();
    })
    .catch(error => {
        console.error('Ошибка AJAX:', error);
        showError('Ошибка соединения');
        hideLoadingIndicator();
    });
}
```

---

### 1.2. Генерация SEF URL на клиенте ⚠️ БЛОКЕР
**Файл**: `web/js/catalog.js`  
**Проблема**: updateURL() создает query параметры, а не SEF URL

**Что сделать**:
```javascript
/**
 * Генерация SEF URL на клиенте (аналог SmartFilter::generateSefUrl)
 */
function generateSefUrl() {
    if (filterState.brands.length === 0 && 
        filterState.categories.length === 0 && 
        !filterState.priceFrom && 
        !filterState.priceTo) {
        return '/catalog/';
    }

    const parts = [];

    // Бренды - получаем slug из DOM
    if (filterState.brands.length > 0) {
        const slugs = [];
        filterState.brands.forEach(brandId => {
            const checkbox = document.querySelector(`input[name="brands[]"][value="${brandId}"]`);
            if (checkbox) {
                const slug = checkbox.dataset.slug; // Добавить data-slug в HTML
                if (slug) slugs.push(slug);
            }
        });
        if (slugs.length > 0) {
            parts.push('brand-' + slugs.sort().join('-'));
        }
    }

    // Категории
    if (filterState.categories.length > 0) {
        const slugs = [];
        filterState.categories.forEach(catId => {
            const checkbox = document.querySelector(`input[name="categories[]"][value="${catId}"]`);
            if (checkbox) {
                const slug = checkbox.dataset.slug;
                if (slug) slugs.push(slug);
            }
        });
        if (slugs.length > 0) {
            parts.push('category-' + slugs.sort().join('-'));
        }
    }

    // Цена
    if (filterState.priceFrom || filterState.priceTo) {
        const from = filterState.priceFrom || 'min';
        const to = filterState.priceTo || 'max';
        parts.push(`price-${from}-${to}`);
    }

    return parts.length > 0 ? '/catalog/filter/' + parts.join('/') + '/' : '/catalog/';
}

/**
 * Обновление URL с SEF
 */
function updateURL() {
    const sefUrl = generateSefUrl();
    const params = new URLSearchParams();
    
    if (filterState.page > 1) {
        params.set('page', filterState.page);
    }
    if (filterState.sortBy !== 'popular') {
        params.set('sort', filterState.sortBy);
    }

    const newUrl = sefUrl + (params.toString() ? '?' + params.toString() : '');
    window.history.pushState({filters: filterState}, '', newUrl);
}
```

**Обновить HTML** (`views/catalog/index.php`):
```php
<input type="checkbox" 
       name="brands[]" 
       value="<?= $brand['id'] ?>"
       data-slug="<?= $brand['slug'] ?>"  <!-- Добавить slug -->
       <?= isset($currentFilters['brands']) && in_array($brand['id'], $currentFilters['brands']) ? 'checked' : '' ?>>
```

---

### 1.3. Кнопка "Сбросить все" с правильным URL
**Файл**: `views/catalog/index.php`, строка 115-130

**Заменить на**:
```php
<?php if (!empty($activeFilters)): ?>
    <div class="active-filters-tags">
        <div class="tags-container">
            <?php foreach ($activeFilters as $filter): ?>
                <div class="filter-tag">
                    <span><?= Html::encode($filter['label']) ?></span>
                    <a href="<?= Html::encode($filter['removeUrl']) ?>" 
                       class="remove-filter"
                       data-ajax="true">
                        <i class="bi bi-x"></i>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
        
        <a href="/catalog/" class="clear-all-filters">
            <i class="bi bi-x-circle"></i>
            Сбросить все
        </a>
    </div>
<?php endif; ?>
```

**Добавить стили**:
```css
.active-filters-tags {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1rem 0;
    margin-bottom: 2rem;
    border-bottom: 1px solid #e5e7eb;
}

.tags-container {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
}

.filter-tag {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem 1rem;
    background: #f9fafb;
    border: 1px solid #e5e7eb;
    border-radius: 20px;
    font-size: 0.875rem;
}

.remove-filter {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 18px;
    height: 18px;
    background: #000;
    color: #fff;
    border-radius: 50%;
    text-decoration: none;
    transition: all 0.2s;
}

.remove-filter:hover {
    background: #dc2626;
    transform: scale(1.1);
}

.clear-all-filters {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    color: #dc2626;
    text-decoration: none;
    font-weight: 600;
    white-space: nowrap;
}

.clear-all-filters:hover {
    color: #b91c1c;
}
```

---

### 1.4. Skeleton loading вместо opacity
**Файл**: `web/js/catalog.js`

**Заменить**:
```javascript
function showLoadingIndicator() {
    const container = document.getElementById('products-container');
    if (!container) return;
    
    // Создаем skeleton
    const skeletonHTML = `
        <div class="skeleton-grid">
            ${Array(8).fill(0).map(() => `
                <div class="skeleton-card">
                    <div class="skeleton-image"></div>
                    <div class="skeleton-brand"></div>
                    <div class="skeleton-title"></div>
                    <div class="skeleton-price"></div>
                </div>
            `).join('')}
        </div>
    `;
    
    container.innerHTML = skeletonHTML;
}

function hideLoadingIndicator() {
    // Просто отрисовываем результаты - skeleton заменится
}
```

**Добавить CSS**:
```css
.skeleton-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
    gap: 1.5rem;
}

.skeleton-card {
    background: #fff;
    border-radius: 8px;
    overflow: hidden;
}

.skeleton-image {
    width: 100%;
    height: 300px;
    background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
    background-size: 200% 100%;
    animation: shimmer 1.5s infinite;
}

.skeleton-brand,
.skeleton-title,
.skeleton-price {
    height: 20px;
    margin: 10px;
    background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
    background-size: 200% 100%;
    animation: shimmer 1.5s infinite;
    border-radius: 4px;
}

@keyframes shimmer {
    0% { background-position: -200% 0; }
    100% { background-position: 200% 0; }
}
```

---

## ⚠️ ЭТАП 2: ВАЖНЫЕ УЛУЧШЕНИЯ (1-2 часа)

### 2.1. Sticky фильтры при скролле
**Файл**: `views/catalog/index.php`, добавить CSS

```css
@media (min-width: 768px) {
    .filters-sidebar {
        position: sticky;
        top: 80px; /* высота header */
        max-height: calc(100vh - 100px);
        overflow-y: auto;
    }
    
    /* Плавная прокрутка */
    .filters-sidebar::-webkit-scrollbar {
        width: 6px;
    }
    
    .filters-sidebar::-webkit-scrollbar-thumb {
        background: #d1d5db;
        border-radius: 3px;
    }
}
```

---

### 2.2. Поиск по брендам (если > 10)
**Файл**: `views/catalog/index.php`

```php
<div class="filter-group">
    <h4 class="filter-title">Бренд</h4>
    
    <?php if (count($filters['brands']) > 10): ?>
        <input type="text" 
               class="brand-search" 
               placeholder="Поиск бренда..."
               oninput="filterBrands(this.value)">
    <?php endif; ?>
    
    <div class="brand-list">
        <?php foreach ($filters['brands'] as $brand): ?>
            <label class="filter-checkbox <?= $brand['count'] == 0 ? 'disabled' : '' ?>" 
                   data-brand-name="<?= strtolower($brand['name']) ?>">
                <!-- ... checkbox ... -->
            </label>
        <?php endforeach; ?>
    </div>
</div>
```

**Добавить JS**:
```javascript
window.filterBrands = function(search) {
    const searchLower = search.toLowerCase();
    document.querySelectorAll('.brand-list .filter-checkbox').forEach(item => {
        const brandName = item.dataset.brandName;
        item.style.display = brandName.includes(searchLower) ? '' : 'none';
    });
};
```

---

### 2.3. OR логика для брендов
**Файл**: `views/catalog/index.php`

```php
<div class="filter-group">
    <h4 class="filter-title">Бренд</h4>
    
    <div class="filter-logic">
        <label class="logic-option">
            <input type="radio" name="brand_logic" value="or" checked>
            <span>Любой из выбранных (ИЛИ)</span>
        </label>
        <label class="logic-option">
            <input type="radio" name="brand_logic" value="and">
            <span>Все выбранные (И)</span>
        </label>
    </div>
    
    <!-- ... brands list ... -->
</div>
```

**Backend** (`CatalogController::applyParsedFilters`):
```php
protected function applyParsedFilters($query, $filters)
{
    if (!empty($filters['brands'])) {
        $brandLogic = Yii::$app->request->get('brand_logic', 'or');
        
        if ($brandLogic === 'or') {
            $query->andWhere(['brand_id' => $filters['brands']]);
        } else {
            // AND логика - редко используется, но можем реализовать через теги
            // Оставляем OR по умолчанию
            $query->andWhere(['brand_id' => $filters['brands']]);
        }
    }
    
    // ... остальные фильтры ...
}
```

---

### 2.4. Range slider для цены
**Подключить библиотеку**: [noUiSlider](https://refreshless.com/nouislider/)

```html
<!-- В head -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/nouislider@15.7.1/dist/nouislider.min.css">
<script src="https://cdn.jsdelivr.net/npm/nouislider@15.7.1/dist/nouislider.min.js"></script>
```

**HTML** (`views/catalog/index.php`):
```php
<div class="filter-group">
    <h4 class="filter-title">Цена</h4>
    <div id="price-slider"></div>
    <div class="price-inputs">
        <input type="number" id="price-from" name="price_from" readonly>
        <span>—</span>
        <input type="number" id="price-to" name="price_to" readonly>
    </div>
</div>
```

**JS**:
```javascript
const priceSlider = document.getElementById('price-slider');
if (priceSlider) {
    noUiSlider.create(priceSlider, {
        start: [<?= $filters['priceRange']['min'] ?>, <?= $filters['priceRange']['max'] ?>],
        connect: true,
        range: {
            'min': <?= $filters['priceRange']['min'] ?>,
            'max': <?= $filters['priceRange']['max'] ?>
        },
        format: {
            to: value => Math.round(value),
            from: value => Number(value)
        }
    });

    priceSlider.noUiSlider.on('update', function(values) {
        document.getElementById('price-from').value = values[0];
        document.getElementById('price-to').value = values[1];
    });

    priceSlider.noUiSlider.on('change', handleFilterChange);
}
```

---

## 💡 ЭТАП 3: ОПЦИОНАЛЬНЫЕ ФИЧИ (1-2 часа)

### 3.1. Визуальные цветовые фильтры
```php
<div class="filter-group">
    <h4 class="filter-title">Цвет</h4>
    <div class="color-filters">
        <?php foreach ($filters['colors'] as $color): ?>
            <label class="color-checkbox" title="<?= $color['name'] ?>">
                <input type="checkbox" name="colors[]" value="<?= $color['id'] ?>">
                <span class="color-circle" style="background: <?= $color['hex'] ?>"></span>
            </label>
        <?php endforeach; ?>
    </div>
</div>
```

```css
.color-filters {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
}

.color-checkbox {
    position: relative;
    cursor: pointer;
}

.color-checkbox input {
    position: absolute;
    opacity: 0;
}

.color-circle {
    display: block;
    width: 40px;
    height: 40px;
    border-radius: 50%;
    border: 2px solid #e5e7eb;
    transition: all 0.2s;
}

.color-checkbox input:checked + .color-circle {
    border-color: #000;
    box-shadow: 0 0 0 3px rgba(0,0,0,0.1);
}
```

---

### 3.2. Сохраненные комбинации фильтров
```javascript
// LocalStorage для сохранения последних фильтров
function saveFilterPreset(name) {
    const presets = JSON.parse(localStorage.getItem('filterPresets') || '{}');
    presets[name] = filterState;
    localStorage.setItem('filterPresets', JSON.stringify(presets));
}

function loadFilterPreset(name) {
    const presets = JSON.parse(localStorage.getItem('filterPresets') || '{}');
    if (presets[name]) {
        filterState = presets[name];
        applyFiltersAjax();
    }
}
```

**UI**:
```html
<div class="filter-presets">
    <button onclick="saveFilterPreset('favorite')">💾 Сохранить фильтр</button>
    <select onchange="loadFilterPreset(this.value)">
        <option value="">Загрузить...</option>
        <!-- Динамически из localStorage -->
    </select>
</div>
```

---

### 3.3. История просмотренных товаров
```javascript
// При просмотре товара добавляем в историю
function addToViewHistory(productId) {
    let history = JSON.parse(localStorage.getItem('viewHistory') || '[]');
    history = history.filter(id => id !== productId);
    history.unshift(productId);
    history = history.slice(0, 12); // Максимум 12
    localStorage.setItem('viewHistory', JSON.stringify(history));
}
```

**Показать в sidebar**:
```html
<div class="recently-viewed">
    <h4>Вы смотрели</h4>
    <div class="viewed-products">
        <!-- AJAX загрузка товаров из localStorage -->
    </div>
</div>
```

---

## 📊 ИТОГОВАЯ ОЦЕНКА ПОСЛЕ ВСЕХ УЛУЧШЕНИЙ

| Этап | Оценка до | Оценка после |
|------|-----------|--------------|
| Этап 1 (Критично) | 6.2/10 | **8.5/10** ⭐⭐⭐⭐ |
| Этап 2 (Важно) | 8.5/10 | **9.2/10** ⭐⭐⭐⭐⭐ |
| Этап 3 (Опционально) | 9.2/10 | **9.8/10** 🏆 |

---

## 🎯 ПРИОРИТЕТ РЕАЛИЗАЦИИ

### Сейчас должны сделать ОБЯЗАТЕЛЬНО:
1. ✅ **AJAX фильтрация** (1 час) — критично
2. ✅ **SEF URL на клиенте** (1 час) — критично
3. ✅ **Кнопка "Сбросить все"** (15 минут) — критично
4. ✅ **Skeleton loading** (30 минут) — важно для UX

### После базы:
5. ✅ **Sticky sidebar** (15 минут) — важно
6. ✅ **Поиск по брендам** (30 минут) — важно
7. ✅ **Range slider цены** (45 минут) — важно

### Опционально (если будет время):
8. ⚠️ OR логика (30 минут)
9. ⚠️ Визуальные цвета (45 минут)
10. ⚠️ Сохраненные фильтры (1 час)

---

## 🚀 НАЧАТЬ С ЧЕГО?

**Первый шаг** — исправить `catalog.js` (пункты 1.1 и 1.2).  
Без этого фильтр вообще не работает через AJAX.

Готов начать прямо сейчас?
