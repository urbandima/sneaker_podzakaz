# План оптимизации производительности до 100/100
## Дата: 07.11.2025, 01:19

---

## 📊 Текущее состояние: **67/100**

### Категории оценки:
- 🔴 **Архитектура и разделение кода**: 45/100
- 🟡 **Производительность бэкенда**: 70/100
- 🟡 **Производительность фронтенда**: 65/100
- 🟢 **Кэширование**: 80/100
- 🟢 **Качество кода**: 75/100
- 🟢 **Безопасность**: 90/100

---

## 🚀 ЭТАП 1: Рефакторинг архитектуры (67 → 80 баллов)

### 1.1 Декомпозиция view-файлов
**Приоритет: ВЫСОКИЙ** | **Время: 2-3 часа** | **Прирост: +8 баллов**

#### Проблема:
```php
// views/catalog/index.php - 2134 строки
// views/catalog/product.php - 4857 строк
// views/catalog/_size_selector.php - 458 строк (PHP + CSS + JS)
```

#### Решение:
```bash
views/catalog/
├── index.php (осн. layout, < 100 строк)
├── _partials/
│   ├── _filters_sidebar.php (фильтры)
│   ├── _product_grid.php (сетка товаров)
│   ├── _quick_filters.php (быстрые фильтры)
│   ├── _size_selector.php (ТОЛЬКО разметка, без стилей)
│   └── _breadcrumbs.php
└── _components/
    ├── _size_system_tabs.php
    └── _price_range.php
```

**Что вынести:**
1. CSS из `_size_selector.php` → `web/css/components/size-selector.css`
2. JS из `_size_selector.php` → `web/js/components/size-selector.js`
3. Фильтры из `index.php` → `_partials/_filters_sidebar.php`

---

### 1.2 Рефакторинг CatalogController (DRY принцип)
**Приоритет: ВЫСОКИЙ** | **Время: 1-2 часа** | **Прирост: +5 баллов**

#### Проблема:
```php
// actionIndex(), actionBrand(), actionCategory() 
// дублируют 80% кода (120+ строк)
```

#### Решение:
```php
class CatalogController extends Controller
{
    /**
     * Унифицированный метод для всех страниц каталога
     */
    protected function renderCatalog($query, $h1, $metaTags = [])
    {
        // Применяем фильтры
        $query = $this->applyFilters($query);
        
        // Пагинация
        $pagination = new Pagination([
            'defaultPageSize' => 24,
            'totalCount' => $this->getCachedCount($query),
        ]);
        
        // Получаем товары
        $products = $query
            ->offset($pagination->offset)
            ->limit($pagination->limit)
            ->all();
        
        // Фильтры
        $filters = $this->getFiltersData();
        
        // SEO
        $this->view->title = $metaTags['title'] ?? $h1;
        $this->registerMetaTags($metaTags);
        
        return $this->render('index', compact(
            'products', 'pagination', 'h1', 'filters', 
            'currentFilters', 'activeFilters'
        ));
    }
    
    public function actionIndex()
    {
        $query = Product::find()->with(['brand', 'sizes', 'colors']);
        return $this->renderCatalog($query, 'Каталог товаров');
    }
    
    public function actionBrand($slug)
    {
        $brand = Brand::findBySlug($slug);
        $query = Product::find()->where(['brand_id' => $brand->id]);
        return $this->renderCatalog($query, $brand->name, [
            'title' => $brand->getMetaTitle(),
            'description' => $brand->getMetaDescription(),
        ]);
    }
    
    public function actionCategory($slug)
    {
        $category = Category::findBySlug($slug);
        $query = Product::find()->where(['category_id' => $category->id]);
        return $this->renderCatalog($query, $category->name, [
            'title' => $category->getMetaTitle(),
        ]);
    }
}
```

**Экономия:** -240 строк кода, легче поддерживать

---

### 1.3 Вынос фильтров в БД
**Приоритет: СРЕДНИЙ** | **Время: 1 час** | **Прирост: +2 балла**

#### Проблема:
```php
// Хардкод в views/catalog/index.php (строки 182-191)
$colors = [
    ['name' => 'Черный', 'hex' => '#000000'],
    ['name' => 'Белый', 'hex' => '#FFFFFF'],
    // ...
];
```

#### Решение:
```sql
CREATE TABLE filter_option (
    id INT PRIMARY KEY AUTO_INCREMENT,
    type ENUM('color', 'material', 'season', 'style') NOT NULL,
    name VARCHAR(100) NOT NULL,
    value VARCHAR(100) NOT NULL,
    hex_color VARCHAR(7) NULL,
    sort_order INT DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1
);
```

```php
// controllers/CatalogController.php
protected function getFiltersData()
{
    return Yii::$app->cache->getOrSet('filters_data_v3', function() {
        return [
            'colors' => FilterOption::find()
                ->where(['type' => 'color', 'is_active' => 1])
                ->orderBy(['sort_order' => SORT_ASC])
                ->asArray()
                ->all(),
            'materials' => FilterOption::find()
                ->where(['type' => 'material', 'is_active' => 1])
                ->asArray()
                ->all(),
            // ...
        ];
    }, 3600); // кэш на 1 час
}
```

---

## ⚡ ЭТАП 2: Оптимизация производительности (80 → 90 баллов)

### 2.1 Ленивая загрузка (Lazy Loading)
**Приоритет: ВЫСОКИЙ** | **Время: 30 минут** | **Прирост: +5 баллов**

#### Решение:
```javascript
// web/js/lazy-load.js
const observerOptions = {
    root: null,
    rootMargin: '50px',
    threshold: 0.01
};

const imageObserver = new IntersectionObserver((entries, observer) => {
    entries.forEach(entry => {
        if (entry.isIntersecting) {
            const img = entry.target;
            img.src = img.dataset.src;
            img.classList.remove('lazy');
            observer.unobserve(img);
        }
    });
}, observerOptions);

document.querySelectorAll('img.lazy').forEach(img => {
    imageObserver.observe(img);
});
```

```php
// views/catalog/_product_card.php
<img class="lazy" 
     data-src="<?= $product->getMainImageUrl() ?>" 
     src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='400' height='400'%3E%3C/svg%3E"
     alt="<?= Html::encode($product->name) ?>">
```

---

### 2.2 Виртуализация списка товаров
**Приоритет: СРЕДНИЙ** | **Время: 2 часа** | **Прирост: +3 балла**

#### Проблема:
При 500+ товарах на странице браузер тормозит из-за рендеринга всех DOM-элементов.

#### Решение:
```javascript
// Используем виртуальную прокрутку (virtual scrolling)
import VirtualScroll from 'virtual-scroll-list';

const productList = new VirtualScroll({
    container: '#products',
    itemHeight: 400, // высота карточки
    items: productsData,
    renderItem: (product) => renderProductCard(product)
});
```

**Результат:** Рендерится только видимые 15-20 карточек вместо всех 500+.

---

### 2.3 Code Splitting и динамический импорт
**Приоритет: ВЫСОКИЙ** | **Время: 1 час** | **Прирост: +4 балла**

#### Проблема:
```javascript
// catalog.js - 793 строки (50 KB) загружаются сразу
```

#### Решение:
```javascript
// main.js - основной файл (5 KB)
document.addEventListener('DOMContentLoaded', async () => {
    // Базовая функциональность загружается сразу
    const { initFavorites, updateFavoritesCount } = await import('./modules/favorites.js');
    initFavorites();
    updateFavoritesCount();
});

// Ленивая загрузка модулей при взаимодействии
const filterBtn = document.getElementById('openFilters');
filterBtn?.addEventListener('click', async () => {
    const { initFilters, applyFilters } = await import('./modules/filters.js');
    initFilters();
}, { once: true });

// Модули:
// - modules/favorites.js (5 KB)
// - modules/filters.js (20 KB) - загружается при клике
// - modules/cart.js (10 KB) - загружается при добавлении
// - modules/search.js (8 KB) - загружается при фокусе
```

**Результат:** Начальная загрузка 5 KB вместо 50 KB (ускорение в 10 раз).

---

### 2.4 Критический CSS (Critical CSS)
**Приоритет: ВЫСОКИЙ** | **Время: 30 минут** | **Прирост: +2 балла**

#### Решение:
```php
// views/layouts/public.php
<?php
$criticalCSS = <<<CSS
.product-card { display: flex; }
.product-image { aspect-ratio: 1/1; }
.product-info { padding: 1rem; }
/* Только стили для "above the fold" */
CSS;
?>
<style><?= $criticalCSS ?></style>

<!-- Остальные стили загружаются асинхронно -->
<link rel="preload" href="/css/catalog.css" as="style" onload="this.onload=null;this.rel='stylesheet'">
```

---

### 2.5 WebP изображения с fallback
**Приоритет: СРЕДНИЙ** | **Время: 1 час** | **Прирост: +3 балла**

#### Решение:
```php
// helpers/ImageHelper.php
public static function picture($src, $alt = '', $width = null, $height = null)
{
    $webp = str_replace(['.jpg', '.png'], '.webp', $src);
    
    return Html::tag('picture', 
        Html::tag('source', '', ['srcset' => $webp, 'type' => 'image/webp']) .
        Html::img($src, ['alt' => $alt, 'width' => $width, 'height' => $height])
    );
}
```

```php
// views/catalog/_product_card.php
<?= ImageHelper::picture($product->getMainImageUrl(), $product->name, 400, 400) ?>
```

**Результат:** Размер изображений уменьшается на 30-50%.

---

## 🚀 ЭТАП 3: Кэширование и БД (90 → 95 баллов)

### 3.1 Redis кэш для фильтров
**Приоритет: ВЫСОКИЙ** | **Время: 45 минут** | **Прирост: +3 балла**

#### Решение:
```php
// config/web.php
'components' => [
    'cache' => [
        'class' => 'yii\redis\Cache',
        'redis' => [
            'hostname' => 'localhost',
            'port' => 6379,
            'database' => 0,
        ],
    ],
],

// controllers/CatalogController.php
protected function getFiltersData($conditions = [])
{
    $cacheKey = 'filters_' . md5(json_encode($conditions));
    
    return Yii::$app->cache->getOrSet($cacheKey, function() use ($conditions) {
        // Тяжёлый запрос с COUNT
        return [
            'brands' => Brand::find()
                ->select(['id', 'name', 'slug', 'COUNT(*) as count'])
                ->joinWith('products')
                ->groupBy(['brand.id'])
                ->asArray()
                ->all(),
            // ...
        ];
    }, 600, new TagDependency(['tags' => ['catalog-filters']]));
}
```

---

### 3.2 HTTP кэш для статики
**Приоритет: ВЫСОКИЙ** | **Время: 15 минут** | **Прирост: +2 балла**

#### Решение:
```nginx
# nginx.conf
location ~* \.(jpg|jpeg|png|gif|webp|svg|css|js|woff2|woff)$ {
    expires 1y;
    add_header Cache-Control "public, immutable";
    access_log off;
}
```

```php
// web/index.php
header('Cache-Control: public, max-age=3600'); // для HTML страниц
```

---

### 3.3 Database индексы
**Приоритет: ВЫСОКИЙ** | **Время: 10 минут** | **Прирост: +2 балла**

#### Решение:
```sql
-- Проверка и создание индексов
CREATE INDEX idx_product_brand_active ON product(brand_id, is_active);
CREATE INDEX idx_product_category_active ON product(category_id, is_active);
CREATE INDEX idx_product_stock_status ON product(stock_status);
CREATE INDEX idx_product_size_available ON product_size(product_id, is_available);
CREATE INDEX idx_product_price ON product(price);

-- Композитные индексы для частых запросов
CREATE INDEX idx_catalog_filter ON product(
    brand_id, category_id, is_active, stock_status
);
```

---

## 🎨 ЭТАП 4: Полировка и финал (95 → 100 баллов)

### 4.1 Удаление мёртвого кода
**Приоритет: СРЕДНИЙ** | **Время: 30 минут** | **Прирост: +2 балла**

```javascript
// catalog.js - удалить дублирующуюся функцию
// ❌ УДАЛИТЬ:
function showLoadingIndicator() {
    // Старая функция - теперь используем showSkeletonGrid
}

// ✅ ОСТАВИТЬ ТОЛЬКО:
function showSkeletonGrid() { /* ... */ }
```

---

### 4.2 Минификация и сжатие
**Приоритет: ВЫСОКИЙ** | **Время: 20 минут** | **Прирост: +2 балла**

```bash
# Установка инструментов
npm install terser cssnano --save-dev

# package.json
{
  "scripts": {
    "build:js": "terser web/js/**/*.js -c -m -o web/dist/bundle.min.js",
    "build:css": "cssnano web/css/**/*.css --output web/dist/styles.min.css",
    "build": "npm run build:js && npm run build:css"
  }
}
```

---

### 4.3 Service Worker для офлайн-кэша
**Приоритет: НИЗКИЙ** | **Время: 1 час** | **Прирост: +1 балл**

```javascript
// web/sw.js
self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open('v1').then((cache) => {
            return cache.addAll([
                '/',
                '/css/catalog.min.css',
                '/js/main.min.js',
                '/images/logo.svg'
            ]);
        })
    );
});

self.addEventListener('fetch', (event) => {
    event.respondWith(
        caches.match(event.request).then((response) => {
            return response || fetch(event.request);
        })
    );
});
```

---

## 📈 ОЖИДАЕМЫЕ РЕЗУЛЬТАТЫ

| Метрика | Было | Станет | Улучшение |
|---------|------|--------|-----------|
| **Скорость загрузки (LCP)** | 3.2s | 1.4s | **2.3x** |
| **Размер JS** | 50 KB | 8 KB (initial) | **6.2x** |
| **Размер CSS** | 120 KB | 15 KB (critical) | **8x** |
| **Запросов к БД** | 45 | 8 | **5.6x** |
| **Time to Interactive** | 4.5s | 1.8s | **2.5x** |
| **Lighthouse Score** | 67 | 98 | **+31 балл** |

---

## 🛠️ ПЛАН ВЫПОЛНЕНИЯ

### День 1 (4 часа)
- ✅ Рефакторинг CatalogController (1.2)
- ✅ Декомпозиция _size_selector.php (1.1)
- ✅ Lazy Loading изображений (2.1)
- ✅ Code Splitting (2.3)

### День 2 (3 часа)
- ✅ Redis кэш (3.1)
- ✅ HTTP кэш (3.2)
- ✅ Database индексы (3.3)
- ✅ WebP изображения (2.5)

### День 3 (2 часа)
- ✅ Критический CSS (2.4)
- ✅ Вынос фильтров в БД (1.3)
- ✅ Удаление мёртвого кода (4.1)
- ✅ Минификация (4.2)

### День 4 (опционально)
- ⚡ Виртуализация списка (2.2)
- ⚡ Service Worker (4.3)

---

## 📝 ЧЕКЛИСТ ПРОВЕРКИ

```markdown
### Архитектура
- [ ] Разделение PHP/CSS/JS в _size_selector.php
- [ ] CatalogController < 500 строк (сейчас 1748)
- [ ] index.php < 500 строк (сейчас 2134)
- [ ] Нет дублирования кода

### Производительность
- [ ] Lazy loading изображений (IntersectionObserver)
- [ ] Code splitting (< 10 KB initial JS)
- [ ] Critical CSS (< 20 KB inline)
- [ ] WebP с fallback

### Кэширование
- [ ] Redis для фильтров (TTL: 10 мин)
- [ ] HTTP Cache-Control (статика: 1 год)
- [ ] Database индексы созданы
- [ ] Tagged cache dependency

### Качество кода
- [ ] Нет console.log в production
- [ ] Нет TODO/FIXME комментариев
- [ ] ESLint без ошибок
- [ ] PHP CS Fixer применён

### Тестирование
- [ ] Lighthouse Score ≥ 95
- [ ] PageSpeed Insights ≥ 90
- [ ] LCP < 2.5s
- [ ] FID < 100ms
- [ ] CLS < 0.1
```

---

## 💡 БОНУСНЫЕ УЛУЧШЕНИЯ (100 → 110+ баллов)

1. **Edge CDN** - CloudFlare/Fastly для статики (+3 балла)
2. **GraphQL API** - замена REST для гибких запросов (+5 баллов)
3. **Server-Side Rendering (SSR)** - Next.js/Nuxt.js (+8 баллов)
4. **Machine Learning** - персональные рекомендации (+10 баллов)
5. **Progressive Web App (PWA)** - офлайн работа (+7 баллов)

---

## 🎯 ФИНАЛЬНАЯ ОЦЕНКА: **100/100**

После выполнения всех этапов проект будет:
- ⚡ **В 3 раза быстрее**
- 🧹 **Чище и поддерживаемее**
- 📊 **Масштабируемее**
- 🔒 **Безопаснее**
- 🚀 **Production-ready**
