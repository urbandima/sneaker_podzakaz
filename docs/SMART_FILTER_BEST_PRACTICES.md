# Умный фильтр: Лучшие практики и анализ

**Дата**: 01.11.2025, 23:50  
**Анализ**: Битрикс24, Amazon, Wildberries, Ozon, AliExpress

---

## 🔍 АНАЛИЗ БИТРИКС24 "Умный фильтр"

### Архитектура фильтра Битрикс24

#### 1. SEF URL (Search Engine Friendly)
```
/catalog/filter/brand-is-nike/price-from-100/
/catalog/filter/apply/?set_filter=y&brand[]=1&price_from=100
```

**Как работает:**
- Параметры фильтра кодируются в ЧПУ URL
- `brand-is-nike` преобразуется в `brand[]=1`
- Индексируется поисковиками как отдельная страница
- Каждая комбинация = уникальный URL

#### 2. Canonical и индексация
```php
// Для комбинаций с < 3 товарами
<meta name="robots" content="noindex, nofollow">

// Для популярных комбинаций
<link rel="canonical" href="/catalog/brand/nike/">
```

**Логика:**
- **> 10 товаров**: индексировать (index, follow)
- **3-10 товаров**: не индексировать, но следовать (noindex, follow)
- **< 3 товара**: не индексировать, не следовать (noindex, nofollow)

#### 3. AJAX с историей
```javascript
// Битрикс использует History API
BX.ajax.runComponentAction('bitrix:catalog.smart.filter', 'filter', {
    data: { filters: {...} }
}).then(function(response) {
    // Обновление контента
    history.pushState({}, '', newUrl);
});
```

#### 4. Кэширование с тегами
```php
$cache->startCache([
    'tags' => ['catalog_filter', 'brand_1', 'category_5']
]);

// При изменении бренда 1 - инвалидация всех комбинаций с ним
TaggedCache::clearByTag('brand_1');
```

#### 5. Умный подсчет
```php
// Битрикс показывает только доступные комбинации
// Если выбран brand=Nike, то показывает только размеры Nike
SELECT DISTINCT size_id, COUNT(*) 
FROM product 
WHERE brand_id = 1 AND is_active = 1
GROUP BY size_id
```

---

## 🌍 ЛУЧШИЕ ПРАКТИКИ В МИРЕ

### 1. AMAZON

**Особенности:**
- ✅ **Faceted navigation** (фасетная навигация)
- ✅ **Динамическое сужение** - показывает только релевантные опции
- ✅ **Множественный выбор** + кнопка "Применить"
- ✅ **Breadcrumbs для фильтров** - можно удалить отдельный фильтр
- ✅ **Сохранение состояния** при переходах

**Пример URL:**
```
/s?k=shoes&rh=n:7141123011,p_n_feature_twenty_browse-bin:3254104011
```

**Что круто:**
- Показывает количество товаров для каждой опции
- Отключает неактуальные опции (серым)
- Clear all filters одной кнопкой

### 2. WILDBERRIES

**Особенности:**
- ✅ **Sticky фильтры** - прилипают при скролле
- ✅ **Цветовые фильтры** - визуальные кружки цветов
- ✅ **Умная сортировка** - "Популярное", "По скидке"
- ✅ **Диапазоны** - цена, размер с input полями
- ✅ **Теги активных фильтров** - над списком товаров

**Пример URL:**
```
/catalog/obuv/krossovki?apparel=3&brand=Nike;Adidas&priceU=10000;50000
```

**Что круто:**
- Моментальная фильтрация (< 100ms)
- Показывает скидки в фильтрах
- "Быстрые фильтры" сверху (Со скидкой, Новинки)

### 3. OZON

**Особенности:**
- ✅ **Группировка фильтров** - по категориям
- ✅ **Поиск по фильтрам** - для брендов (> 20 штук)
- ✅ **Показать все/Скрыть** - для длинных списков
- ✅ **История просмотров** - в сайдбаре
- ✅ **Рекомендации** - "Часто ищут с этим"

**Пример URL:**
```
/category/obuv-10500/?brand=Nike&price_from=5000&price_to=15000
```

**Что круто:**
- Умная группировка (Бренд, Размер, Цвет, Цена, Характеристики)
- Collapsible секции фильтров
- Показывает популярные комбинации

### 4. ALIEXPRESS

**Особенности:**
- ✅ **Визуальные фильтры** - картинки для типов товаров
- ✅ **Рейтинг продавца** в фильтрах
- ✅ **Диапазон доставки** - сроки
- ✅ **Страна отправки** в фильтрах
- ✅ **Free shipping** чекбокс

**Пример URL:**
```
/category/shoes.html?SearchText=&g=y&minPrice=10&maxPrice=100
```

**Что круто:**
- Мультиязычность фильтров
- Currency switcher влияет на цены в фильтрах
- "Ships from" - важно для международных

---

## 📊 СРАВНИТЕЛЬНАЯ ТАБЛИЦА ФИЧЕЙ

| Фича | Битрикс | Amazon | WB | Ozon | AliExpress | Наш |
|------|---------|--------|-----|------|------------|-----|
| SEF URL | ✅ | ✅ | ✅ | ✅ | ✅ | ⚠️ |
| Динамическое сужение | ✅ | ✅ | ✅ | ✅ | ✅ | ❌ |
| Breadcrumbs фильтров | ✅ | ✅ | ✅ | ✅ | ❌ | ❌ |
| Кнопка Clear all | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| Подсчет товаров | ✅ | ✅ | ✅ | ✅ | ❌ | ✅ |
| Отключение недоступных | ✅ | ✅ | ✅ | ✅ | ❌ | ❌ |
| OR логика | ✅ | ✅ | ✅ | ✅ | ✅ | ❌ |
| Сохраненные фильтры | ✅ | ✅ | ❌ | ❌ | ❌ | ⚠️ |
| Sticky фильтры | ❌ | ❌ | ✅ | ✅ | ❌ | ❌ |
| Поиск по брендам | ❌ | ❌ | ❌ | ✅ | ✅ | ❌ |
| Визуальные фильтры | ❌ | ❌ | ✅ | ❌ | ✅ | ⚠️ |
| Schema.org ItemList | ✅ | ✅ | ✅ | ✅ | ✅ | ❌ |
| rel prev/next | ✅ | ✅ | ✅ | ✅ | ✅ | ❌ |
| Canonical для комбинаций | ✅ | ✅ | ✅ | ✅ | ✅ | ❌ |
| Теги активных фильтров | ✅ | ✅ | ✅ | ✅ | ✅ | ❌ |

**Итог:**
- **Битрикс**: 11/15 (73%)
- **Amazon**: 11/15 (73%)
- **Wildberries**: 12/15 (80%) 🏆
- **Ozon**: 12/15 (80%) 🏆
- **AliExpress**: 9/15 (60%)
- **Наш каталог**: 4/15 (27%) ⚠️

---

## 🎯 ЧТО НУЖНО ДОБАВИТЬ В НАШ ФИЛЬТР

### КРИТИЧНО (Must Have)

#### 1. SEF URL для комбинаций фильтров ❗
**Проблема**: Сейчас `/catalog?brands=1,2&price_from=100`  
**Нужно**: `/catalog/filter/nike-adidas/price-100-500/`

**Реализация:**
```php
// config/web.php
'catalog/filter/<filters:[\w\-/]+>' => 'catalog/filter',

// CatalogController::actionFilter($filters)
public function actionFilter($filters = '')
{
    $params = $this->parseFiltersFromSef($filters);
    // brand-nike => ['brand' => ['nike']]
    // price-100-500 => ['price_from' => 100, 'price_to' => 500]
    
    $query = Product::find()->where(['is_active' => 1]);
    $query = $this->applyParsedFilters($query, $params);
    
    // ...
}

protected function parseFiltersFromSef($filtersString)
{
    $parts = explode('/', trim($filtersString, '/'));
    $filters = [];
    
    foreach ($parts as $part) {
        if (preg_match('/^(brand|category)-(.+)$/', $part, $m)) {
            $filters[$m[1]][] = $m[2];
        }
        elseif (preg_match('/^price-(\d+)-(\d+)$/', $part, $m)) {
            $filters['price_from'] = $m[1];
            $filters['price_to'] = $m[2];
        }
    }
    
    return $filters;
}

protected function generateSefUrl($filters)
{
    $parts = [];
    
    if (!empty($filters['brands'])) {
        $brandSlugs = Brand::find()
            ->select('slug')
            ->where(['id' => $filters['brands']])
            ->column();
        $parts[] = 'brand-' . implode('-', $brandSlugs);
    }
    
    if (!empty($filters['price_from']) || !empty($filters['price_to'])) {
        $from = $filters['price_from'] ?? 0;
        $to = $filters['price_to'] ?? 999999;
        $parts[] = "price-{$from}-{$to}";
    }
    
    return '/catalog/filter/' . implode('/', $parts) . '/';
}
```

#### 2. Динамическое сужение фильтров ❗
**Проблема**: Показываем все опции, даже если товаров нет  
**Нужно**: Показывать только доступные комбинации

**Реализация:**
```php
protected function getAvailableFilterOptions($currentFilters = [])
{
    // Базовый запрос с текущими фильтрами
    $query = Product::find()->where(['is_active' => 1]);
    $query = $this->applyFilters($query, $currentFilters);
    
    // Получаем доступные бренды
    $availableBrands = Brand::find()
        ->select(['brand.id', 'brand.name', 'COUNT(product.id) as count'])
        ->innerJoin('product', 'product.brand_id = brand.id')
        ->where(['product.is_active' => 1])
        ->andWhere($query->where) // Применяем текущие фильтры кроме бренда
        ->groupBy('brand.id')
        ->having(['>', 'count', 0])
        ->asArray()
        ->all();
    
    // Аналогично для категорий, цветов, размеров
    
    return [
        'brands' => $availableBrands,
        'categories' => $availableCategories,
        // ...
    ];
}
```

#### 3. Теги активных фильтров ❗
**Проблема**: Не видно какие фильтры применены  
**Нужно**: Теги над списком товаров

**Реализация:**
```php
// View
<div class="active-filters">
    <?php foreach ($activeFilters as $filter): ?>
        <div class="filter-tag">
            <?= Html::encode($filter['label']) ?>
            <a href="<?= $filter['removeUrl'] ?>" class="remove-filter">×</a>
        </div>
    <?php endforeach; ?>
    
    <?php if (!empty($activeFilters)): ?>
        <a href="/catalog" class="clear-all-filters">Сбросить все</a>
    <?php endif; ?>
</div>
```

```javascript
// JS для удаления фильтра
document.querySelectorAll('.remove-filter').forEach(btn => {
    btn.addEventListener('click', function(e) {
        e.preventDefault();
        const url = this.href;
        
        // AJAX запрос
        filterProducts(url);
    });
});
```

#### 4. Schema.org ItemList ❗
**Проблема**: Нет разметки для списков товаров  
**Нужно**: ItemList + numberOfItems

**Реализация:**
```php
$schema = [
    '@context' => 'https://schema.org',
    '@type' => 'ItemList',
    'numberOfItems' => $totalCount,
    'itemListElement' => []
];

foreach ($products as $index => $product) {
    $schema['itemListElement'][] = [
        '@type' => 'ListItem',
        'position' => $index + 1,
        'item' => [
            '@type' => 'Product',
            'name' => $product->name,
            'url' => $product->getAbsoluteUrl(),
            'image' => $product->getMainImageUrl(),
            'offers' => [
                '@type' => 'Offer',
                'price' => $product->price,
                'priceCurrency' => 'BYN',
                'availability' => 'https://schema.org/InStock'
            ]
        ]
    ];
}

$this->registerMetaTag([
    'type' => 'application/ld+json',
    'content' => json_encode($schema, JSON_UNESCAPED_UNICODE)
], 'schema-itemlist');
```

#### 5. Canonical для комбинаций ❗
**Проблема**: Дубли контента  
**Нужно**: Canonical на базовую страницу

**Реализация:**
```php
// Если есть фильтры - canonical на базовую страницу
if (!empty($appliedFilters)) {
    if (count($products) < 3) {
        // Мало товаров - не индексировать
        $this->view->registerMetaTag([
            'name' => 'robots',
            'content' => 'noindex, nofollow'
        ]);
    } elseif (count($products) < 10) {
        // Средне - не индексировать, но следовать
        $this->view->registerMetaTag([
            'name' => 'robots',
            'content' => 'noindex, follow'
        ]);
    }
    
    // Canonical на страницу без фильтров или с одним фильтром
    $canonicalUrl = $this->getCanonicalUrl($appliedFilters);
    $this->view->registerLinkTag([
        'rel' => 'canonical',
        'href' => $canonicalUrl
    ]);
}
```

---

### ВАЖНО (Should Have)

#### 6. OR логика в фильтрах
**Реализация:**
```php
// Добавить radio buttons "И/ИЛИ"
<div class="filter-logic">
    <label>
        <input type="radio" name="brand_logic" value="and" checked> И
    </label>
    <label>
        <input type="radio" name="brand_logic" value="or"> ИЛИ
    </label>
</div>

// В контроллере
if ($brandLogic === 'or') {
    $query->orWhere(['brand_id' => $brandIds]);
} else {
    $query->andWhere(['brand_id' => $brandIds]);
}
```

#### 7. Sticky фильтры при скролле
**Реализация:**
```javascript
const sidebar = document.querySelector('.filters-sidebar');
const sidebarTop = sidebar.offsetTop;

window.addEventListener('scroll', function() {
    if (window.pageYOffset > sidebarTop) {
        sidebar.classList.add('sticky');
    } else {
        sidebar.classList.remove('sticky');
    }
});
```

```css
.filters-sidebar.sticky {
    position: fixed;
    top: 80px;
    width: 280px;
    max-height: calc(100vh - 100px);
    overflow-y: auto;
}
```

#### 8. Поиск по брендам (для > 10 брендов)
**Реализация:**
```html
<div class="filter-section">
    <h3>Бренд</h3>
    <input type="text" 
           class="brand-search" 
           placeholder="Поиск бренда...">
    
    <div class="brand-list">
        <!-- Список брендов -->
    </div>
</div>
```

```javascript
document.querySelector('.brand-search').addEventListener('input', function(e) {
    const search = e.target.value.toLowerCase();
    document.querySelectorAll('.brand-list .filter-checkbox').forEach(item => {
        const text = item.textContent.toLowerCase();
        item.style.display = text.includes(search) ? '' : 'none';
    });
});
```

#### 9. rel="prev"/"next" для пагинации
**Реализация:**
```php
if ($currentPage > 1) {
    $this->view->registerLinkTag([
        'rel' => 'prev',
        'href' => Url::to(['/catalog/index', 'page' => $currentPage - 1], true)
    ]);
}

if ($currentPage < $totalPages) {
    $this->view->registerLinkTag([
        'rel' => 'next',
        'href' => Url::to(['/catalog/index', 'page' => $currentPage + 1], true)
    ]);
}
```

---

### ОПЦИОНАЛЬНО (Nice to Have)

#### 10. Сохраненные комбинации фильтров
**Реализация:**
```php
// Модель SavedFilter
class SavedFilter extends ActiveRecord
{
    public static function tableName()
    {
        return 'saved_filter';
    }
    
    // user_id, name, filter_params (JSON)
}

// Кнопка "Сохранить фильтр"
<button onclick="saveCurrentFilter()">
    <i class="bi bi-bookmark"></i> Сохранить
</button>
```

#### 11. Визуальные цветовые фильтры
**Реализация:**
```html
<div class="color-filters">
    <?php foreach ($colors as $color): ?>
        <label class="color-checkbox">
            <input type="checkbox" 
                   name="colors[]" 
                   value="<?= $color->id ?>">
            <span class="color-circle" 
                  style="background: <?= $color->hex ?>"></span>
            <span class="color-name"><?= $color->name ?></span>
        </label>
    <?php endforeach; ?>
</div>
```

#### 12. История просмотренных товаров
**Реализация:**
```javascript
// Сохраняем в localStorage
function addToHistory(productId) {
    let history = JSON.parse(localStorage.getItem('viewedProducts') || '[]');
    history = history.filter(id => id !== productId);
    history.unshift(productId);
    history = history.slice(0, 10); // Максимум 10
    localStorage.setItem('viewedProducts', JSON.stringify(history));
}

// При просмотре товара
addToHistory(<?= $product->id ?>);
```

---

## 📈 ОЦЕНКА НАШЕГО ФИЛЬТРА

### Текущее состояние: **6.5/10**

| Критерий | Оценка | Комментарий |
|----------|--------|-------------|
| Функционал | 7/10 | Базовое есть, нет динамического сужения |
| SEO | 6/10 | Нет SEF URL, canonical, Schema ItemList |
| UX | 8/10 | Хороший дизайн, но нет тегов фильтров |
| Производительность | 8/10 | Кэширование есть, можно лучше |
| Mobile | 9/10 | Отличная адаптивность |

### После внедрения всех фич: **9/10** 🎯

---

## 🚀 ПЛАН ВНЕДРЕНИЯ (Поэтапный)

### Этап 1: Критичные фичи (2-3 часа)
1. ✅ SEF URL для комбинаций
2. ✅ Теги активных фильтров
3. ✅ Schema.org ItemList
4. ✅ Canonical для комбинаций
5. ✅ Динамическое сужение

### Этап 2: Важные фичи (1-2 часа)
6. ✅ OR логика
7. ✅ Sticky фильтры
8. ✅ rel="prev"/"next"

### Этап 3: Опциональные фичи (1-2 часа)
9. ⚠️ Поиск по брендам
10. ⚠️ Визуальные цвета
11. ⚠️ История просмотров
12. ⚠️ Сохраненные фильтры

---

## 💡 ВЫВОДЫ

### Что делает фильтр "умным":

1. **SEO-friendly URLs** - каждая комбинация = страница
2. **Динамическое сужение** - показываем только релевантное
3. **Правильная индексация** - canonical + robots
4. **Быстрая работа** - AJAX + кэширование
5. **Удобный UX** - теги, sticky, очистка

### Наш фильтр сейчас:
- ✅ Функционально работает
- ✅ Быстрый (AJAX + cache)
- ✅ Красивый UI
- ⚠️ Нехватает SEO фичей
- ⚠️ Нехватает динамического сужения

### После доработки будет:
- 🏆 **Enterprise-уровень**
- 🏆 **Лучше чем Битрикс** по UX
- 🏆 **На уровне WB/Ozon** по функционалу
- 🏆 **Оценка 9/10**

---

**Рекомендация**: Внедрить **Этап 1** (критичные фичи) для достижения уровня Битрикс24.

**Дата**: 01.11.2025, 23:50  
**Аналитик**: Senior Full-Stack Team
