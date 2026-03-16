# План рефакторинга CSS и миграции файлов

## Текущее состояние

### CSS файлы (41 файл без dist/)
```
accessibility.css
admin-dashboard.css
admin-design-system.css
admin-header-v2.css
admin-orders-v2.css
admin-pages.css
admin-product-view.css
admin-shell.css
admin-size-grid.css
cart-mobile.css
cart.css
catalog-card.css
catalog-inline.css
catalog-layout.css
catalog-mobile-fixes.css
checkout-enhancements.css
container-system.css
critical-inline.css
critical.css
dark-mode.css
design-system.css
design-tokens.css
favorites-premium.css
header-adaptive.css
landing-page.css
landing.css
mega-menu.css
micro-interactions.css
mobile-first.css
mobile-menu.css
order-success.css
page-404.css
pages-mobile.css
product-page.css
product-reviews.css
public-layout.css
quick-view.css
responsive-fixes.css
skeleton-loading.css
site.css
```

### Проблемы
1. **Дублирование стилей** - catalog-mobile-fixes.css и mobile-first.css пересекаются
2. **Разрозненные admin файлы** - 9 admin файлов можно объединить
3. **Избыточные файлы** - landing.css и landing-page.css дублируют друг друга
4. **Длинные пути** - `/frontend/web/css/` вместо `/frontend/css/`

## План рефакторинга

### Этап 1: Объединение CSS файлов

#### 1.1 Admin CSS (9 → 1 файл)
**Объединить в:**
```
frontend/css/admin.css
```
**Исходные файлы:**
- admin-dashboard.css
- admin-design-system.css
- admin-header-v2.css
- admin-orders-v2.css
- admin-pages.css
- admin-product-view.css
- admin-shell.css
- admin-size-grid.css

#### 1.2 Catalog CSS (4 → 2 файла)
**Оставить:**
```
frontend/css/catalog.css          # Основные стили каталога
frontend/css/catalog-grid.css     # Сетка товаров
```
**Исходные файлы:**
- catalog-layout.css → catalog.css
- catalog-card.css → catalog.css
- catalog-inline.css → catalog.css
- catalog-mobile-fixes.css → catalog-grid.css

#### 1.3 Landing CSS (2 → 1 файл)
**Объединить в:**
```
frontend/css/landing.css
```
**Исходные файлы:**
- landing-page.css
- landing.css

#### 1.4 Product CSS (2 → 1 файл)
**Объединить в:**
```
frontend/css/product.css
```
**Исходные файлы:**
- product-page.css
- product-reviews.css

#### 1.5 Cart CSS (2 → 1 файл)
**Объединить в:**
```
frontend/css/cart.css
```
**Исходные файлы:**
- cart.css
- cart-mobile.css

#### 1.6 Удалить избыточные файлы
- mobile-first.css (дублирует catalog-mobile-fixes.css)
- pages-mobile.css (дублирует responsive-fixes.css)
- favorites-premium.css (можно объединить в catalog.css)

### Этап 2: Новая структура CSS

```
frontend/css/
├── core/                    # Базовые стили
│   ├── critical.css
│   ├── critical-inline.css
│   ├── container-system.css
│   ├── design-tokens.css
│   └── design-system.css
├── components/              # Компоненты
│   ├── header.css           # header-adaptive.css + mobile-menu.css
│   ├── mega-menu.css
│   ├── buttons.css          # Из design-system.css
│   ├── forms.css            # Из design-system.css
│   └── cards.css            # Из design-system.css
├── layout/                  # Layout стили
│   ├── public-layout.css
│   ├── responsive-fixes.css
│   └── skeleton-loading.css
├── pages/                   # Страницы
│   ├── catalog.css
│   ├── catalog-grid.css
│   ├── product.css
│   ├── cart.css
│   ├── checkout.css        # checkout-enhancements.css + order-success.css
│   ├── landing.css
│   ├── page-404.css
│   └── admin.css
├── features/                # Функциональные стили
│   ├── accessibility.css
│   ├── dark-mode.css
│   └── micro-interactions.css
├── site.css                 # Общие стили
└── quick-view.css           # Quick view modal
```

**Итого: 22 файла вместо 41 (сокращение на 46%)**

### Этап 3: Миграция файлов из web в frontend

#### 3.1 Новая структура
```
frontend/
├── css/                     # Перемещено из web/css
│   ├── core/
│   ├── components/
│   ├── layout/
│   ├── pages/
│   ├── features/
│   ├── site.css
│   └── quick-view.css
├── js/                      # Перемещено из web/js
├── images/                  # Перемещено из web/images
├── uploads/                 # Перемещено из web/uploads
├── assets/                  # AssetBundle
├── controllers/
├── views/
└── web/                     # Только index.php, .htaccess, favicon.ico
    ├── index.php
    ├── index-prod.php
    ├── .htaccess
    └── favicon.ico
```

#### 3.2 Преимущества
- Короткие пути: `/frontend/css/` вместо `/frontend/web/css/`
- Логичная структура: core, components, layout, pages, features
- Легче найти нужный файл
- Соответствует лучшим практикам Yii2

### Этап 4: Обновление AssetBundle

#### 4.1 AppAsset
```php
public $css = [
    // Core
    'css/core/critical.css',
    'css/core/critical-inline.css',
    'css/core/container-system.css',
    'css/core/design-tokens.css',
    'css/core/design-system.css',
    
    // Components
    'css/components/header.css',
    'css/components/mega-menu.css',
    'css/components/buttons.css',
    'css/components/forms.css',
    'css/components/cards.css',
    
    // Layout
    'css/layout/public-layout.css',
    'css/layout/responsive-fixes.css',
    'css/layout/skeleton-loading.css',
    
    // Features
    'css/features/accessibility.css',
    'css/features/dark-mode.css',
    'css/features/micro-interactions.css',
    
    // Site
    'css/site.css',
];
```

#### 4.2 CatalogAsset
```php
public $css = [
    'css/pages/catalog.css',
    'css/pages/catalog-grid.css',
];
```

#### 4.3 ProductAsset
```php
public $css = [
    'css/pages/product.css',
];
```

#### 4.4 CartAsset
```php
public $css = [
    'css/pages/cart.css',
];
```

#### 4.5 CheckoutAsset
```php
public $css = [
    'css/pages/checkout.css',
];
```

#### 4.6 LandingAsset
```php
public $css = [
    'css/pages/landing.css',
];
```

#### 4.7 AdminAsset
```php
public $css = [
    'css/pages/admin.css',
];
```

### Этап 5: Анализ компонентов

#### 5.1 Проверить компоненты
- Header
- Mega Menu
- Product Cards
- Filters
- Pagination
- Forms
- Buttons
- Modals

#### 5.2 Выявить проблемы
- Дублирование стилей
- Несогласованные классы
- Отсутствие BEM
- Смешивание inline и CSS

## Порядок выполнения

1. ✅ Создать структуру папок в frontend/css/
2. ✅ Объединить CSS файлы
3. ✅ Переместить файлы из web/css в frontend/css/
4. ✅ Обновить AssetBundle
5. ✅ Обновить пути в view файлах
6. ✅ Протестировать

## Ожидаемый результат

- **CSS файлов:** 22 вместо 41 (сокращение на 46%)
- **Пути:** `/frontend/css/` вместо `/frontend/web/css/`
- **Структура:** Логичная и понятная
- **Поддержка:** Упрощена
