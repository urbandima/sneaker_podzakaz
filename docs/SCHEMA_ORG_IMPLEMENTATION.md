# Schema.org микроразметка - Полная документация

## Обзор

Внедрена полная микроразметка Schema.org для всех страниц каталога товаров с использованием JSON-LD формата. Микроразметка включает Product, Offer, BreadcrumbList, ItemList, Organization и WebSite схемы.

**Компонент:** `app\components\SchemaOrgGenerator`  
**Формат:** JSON-LD (рекомендуется Google)  
**Расположение:** `<head>` секция HTML  

## Архитектура решения

Микроразметка реализована через централизованный компонент `SchemaOrgGenerator`, который:
- ✅ Генерирует валидные Schema.org структуры
- ✅ Поддерживает все ключевые типы (Product, Offer, BreadcrumbList)
- ✅ Легко расширяется новыми атрибутами через опции
- ✅ Автоматически переводит значения фильтров
- ✅ Использует данные напрямую из моделей Product

## Использование компонента SchemaOrgGenerator

### Базовый пример (страница товара)

```php
use app\components\SchemaOrgGenerator;

// Автоматическая генерация всей разметки
echo SchemaOrgGenerator::render($product);
```

### Расширенный пример с дополнительными полями

```php
// Генерация с кастомными опциями
echo SchemaOrgGenerator::render($product, [
    'additionalFields' => [
        'audience' => [
            '@type' => 'PeopleAudience',
            'suggestedGender' => 'male',
            'suggestedMinAge' => 18,
        ],
    ],
]);
```

### Методы компонента

#### `SchemaOrgGenerator::render($product, $options = [])`
Генерирует и возвращает HTML со всеми JSON-LD схемами для товара.

**Параметры:**
- `$product` (Product) — модель товара
- `$options` (array) — дополнительные опции для расширения

**Возвращает:** HTML строку с `<script type="application/ld+json">` тегами

#### `SchemaOrgGenerator::generateProductSchema($product, $options = [])`
Создает массив со структурами Product и Breadcrumbs.

**Возвращает:**
```php
[
    'product' => [...],      // Product + Offer схема
    'breadcrumbs' => [...],  // BreadcrumbList схема
]
```

#### `SchemaOrgGenerator::renderJsonLd($schema, $prettyPrint = true)`
Оборачивает JSON-LD схему в HTML script тег.

#### `SchemaOrgGenerator::generateOrganizationSchema($options = [])`
Генерирует схему Organization для footer/контактов.

#### `SchemaOrgGenerator::generateItemListSchema($products, $categoryName)`
Генерирует схему ItemList для страниц каталога со списком товаров.

### Поддерживаемые поля товара

Компонент автоматически извлекает и форматирует следующие поля:

**Основные:**
- ✅ `name` — название товара
- ✅ `description` — описание (auto-generated если пусто)
- ✅ `url` — абсолютный URL товара
- ✅ `image` — массив всех изображений (main + gallery)

**Идентификаторы:**
- ✅ `sku` — артикул (style_code или ID)
- ✅ `mpn` — артикул производителя
- ✅ `gtin` — штрихкод (если будет добавлен)

**Бренд:**
- ✅ `brand.name` — название бренда
- ✅ `brand.logo` — логотип бренда

**Категория:**
- ✅ `category` — название категории

**Цена и наличие:**
- ✅ `offers.price` — цена в BYN
- ✅ `offers.priceCurrency` — BYN
- ✅ `offers.availability` — InStock/OutOfStock/PreOrder
- ✅ `offers.priceValidUntil` — срок действия цены (+1 год)
- ✅ `offers.itemCondition` — NewCondition
- ✅ `offers.deliveryLeadTime` — срок доставки (если указан)

**Рейтинг:**
- ✅ `aggregateRating.ratingValue` — средний рейтинг
- ✅ `aggregateRating.ratingCount` — количество отзывов
- ✅ `aggregateRating.bestRating` — 5
- ✅ `aggregateRating.worstRating` — 1

**Дополнительные свойства (additionalProperty):**
- ✅ Материал (material)
- ✅ Материал верха (upper_material)
- ✅ Материал подошвы (sole_material)
- ✅ Пол (gender)
- ✅ Сезон (season)
- ✅ Высота (height)
- ✅ Застежка (fastening)
- ✅ Страна производства (country)
- ✅ Серия (series_name)
- ✅ Год выпуска (release_year)
- ✅ Вес (weight)
- ✅ Цвет (color_description)
- ✅ Лимитированная модель (is_limited)

## Реализованные типы схем

### 1. Product (Товар)

**Где используется:** Страница товара `/catalog/product/{slug}`

**Полная структура:**
```json
{
  "@context": "https://schema.org/",
  "@type": "Product",
  "name": "Nike Air Max 90 White",
  "description": "Nike Air Max 90 - купить оригинальные кроссовки...",
  "url": "https://sneakerhead.by/catalog/product/nike-air-max-90-white",
  "image": [
    "https://sneakerhead.by/uploads/products/main.jpg",
    "https://sneakerhead.by/uploads/products/gallery-1.jpg",
    "https://sneakerhead.by/uploads/products/gallery-2.jpg"
  ],
  "sku": "DM0029-100",
  "mpn": "DM0029-100",
  "brand": {
    "@type": "Brand",
    "name": "Nike",
    "logo": "https://sneakerhead.by/uploads/brands/nike-logo.png"
  },
  "category": "Кроссовки",
  "aggregateRating": {
    "@type": "AggregateRating",
    "ratingValue": "4.5",
    "bestRating": "5",
    "worstRating": "1",
    "ratingCount": 12
  },
  "additionalProperty": [
    {
      "@type": "PropertyValue",
      "name": "Материал",
      "value": "Кожа"
    },
    {
      "@type": "PropertyValue",
      "name": "Пол",
      "value": "Мужское"
    },
    {
      "@type": "PropertyValue",
      "name": "Сезон",
      "value": "Всесезонная"
    }
  ],
  "offers": {
    "@type": "Offer",
    "url": "https://sneakerhead.by/catalog/product/nike-air-max-90-white",
    "priceCurrency": "BYN",
    "price": "299.00",
    "priceValidUntil": "2026-11-08",
    "availability": "https://schema.org/InStock",
    "itemCondition": "https://schema.org/NewCondition",
    "seller": {
      "@type": "Organization",
      "name": "СНИКЕРХЭД"
    }
  }
}
```

### 2. BreadcrumbList (Хлебные крошки)

**Где используется:** Страница товара

**Структура:**
```json
{
  "@context": "https://schema.org",
  "@type": "BreadcrumbList",
  "itemListElement": [
    {
      "@type": "ListItem",
      "position": 1,
      "name": "Главная",
      "item": "https://sneakerhead.by"
    },
    {
      "@type": "ListItem",
      "position": 2,
      "name": "Каталог",
      "item": "https://sneakerhead.by/catalog"
    },
    {
      "@type": "ListItem",
      "position": 3,
      "name": "Кроссовки",
      "item": "https://sneakerhead.by/catalog/category/sneakers"
    },
    {
      "@type": "ListItem",
      "position": 4,
      "name": "Nike Air Max 90",
      "item": "https://sneakerhead.by/catalog/product/nike-air-max-90-white"
    }
  ]
}
```

### 3. ItemList (Список товаров)

**Где используется:** Все страницы каталога с товарами

**Структура:**
```json
{
  "@context": "https://schema.org",
  "@type": "ItemList",
  "numberOfItems": 24,
  "description": "Nike, Adidas. от 100 до 500 BYN",
  "itemListElement": [
    {
      "@type": "ListItem",
      "position": 1,
      "item": {
        "@type": "Product",
        "name": "Nike Air Max 90",
        "sku": "12345",
        "description": "Описание товара...",
        "url": "https://domain.com/catalog/product/nike-air-max-90",
        "image": "https://domain.com/uploads/products/nike-air-max-90.jpg",
        "brand": {
          "@type": "Brand",
          "name": "Nike"
        },
        "offers": {
          "@type": "Offer",
          "price": "299.00",
          "priceCurrency": "BYN",
          "availability": "https://schema.org/InStock",
          "url": "https://domain.com/catalog/product/nike-air-max-90",
          "priceValidUntil": "2026-11-08"
        },
        "aggregateRating": {
          "@type": "AggregateRating",
          "ratingValue": "4.5",
          "reviewCount": 12,
          "bestRating": "5",
          "worstRating": "1"
        }
      }
    }
  ]
}
```

**Поля Product:**
- ✅ `name` — название товара
- ✅ `sku` — артикул (используется ID товара)
- ✅ `description` — описание (первые 200 символов)
- ✅ `url` — URL страницы товара
- ✅ `image` — главное изображение товара
- ✅ `brand` — бренд товара
- ✅ `offers` — информация о предложении (цена, валюта, наличие)
- ✅ `aggregateRating` — рейтинг товара (если есть)

**Особенности:**
- Включает информацию о фильтрах в поле `description`
- Автоматически определяет статус наличия (InStock, OutOfStock, PreOrder)
- Добавляет срок действия цены (1 год с текущей даты)
- Для товаров со скидкой добавляет `priceSpecification`

### 2. BreadcrumbList (Хлебные крошки)

**Где используется:** Все страницы каталога

**Структура:**
```json
{
  "@context": "https://schema.org",
  "@type": "BreadcrumbList",
  "itemListElement": [
    {
      "@type": "ListItem",
      "position": 1,
      "name": "Главная",
      "item": "https://domain.com"
    },
    {
      "@type": "ListItem",
      "position": 2,
      "name": "Каталог",
      "item": "https://domain.com/catalog"
    },
    {
      "@type": "ListItem",
      "position": 3,
      "name": "Nike",
      "item": "https://domain.com/catalog/brand/nike"
    },
    {
      "@type": "ListItem",
      "position": 4,
      "name": "Nike, Adidas • Цена: от 100 до 500 BYN",
      "item": "https://domain.com/catalog?brands=1,2&price_from=100&price_to=500"
    }
  ]
}
```

**Особенности:**
- Всегда начинается с главной страницы и каталога
- Добавляет текущую страницу (бренд или категорию)
- **Включает активные фильтры как последний элемент навигации**
- Фильтры форматируются как "Бренд1, Бренд2 • Цена: от X до Y BYN"

### 3. WebSite (Информация о сайте)

**Где используется:** Главная страница каталога

**Структура:**
```json
{
  "@context": "https://schema.org",
  "@type": "WebSite",
  "name": "СНИКЕРХЭД",
  "url": "https://domain.com",
  "potentialAction": {
    "@type": "SearchAction",
    "target": {
      "@type": "EntryPoint",
      "urlTemplate": "https://domain.com/catalog?search={search_term_string}"
    },
    "query-input": "required name=search_term_string"
  }
}
```

**Особенности:**
- Регистрируется только на главной странице каталога
- Добавляет поддержку sitelinks search box в Google
- Позволяет искать прямо из результатов поиска Google

## Технические детали реализации

### Новые методы в CatalogController

#### 1. `registerJsonLd($schema, $key)`

Helper метод для регистрации JSON-LD схем в `<head>`.

**Параметры:**
- `$schema` (array) — массив схемы
- `$key` (string) — уникальный ключ

**Как работает:**
1. Преобразует массив в JSON с форматированием
2. Сохраняет JSON в `$this->view->params['jsonLdSchemas'][$key]`
3. Схемы выводятся в layout через `<?php echo $this->renderJsonLdSchemas() ?>`

#### 2. `registerSchemaItemList($products, $totalCount, $filters)`

Регистрирует Schema.org ItemList с полной информацией о товарах.

**Параметры:**
- `$products` (array) — массив товаров
- `$totalCount` (int) — общее количество товаров
- `$filters` (array) — активные фильтры

**Что добавляет:**
- Полную структуру ItemList
- Product для каждого товара с всеми полями
- Информацию о фильтрах в description

#### 3. `registerSchemaBreadcrumbs($breadcrumbs, $filters)`

Регистрирует Schema.org BreadcrumbList с учетом фильтров.

**Параметры:**
- `$breadcrumbs` (array) — массив крошек `[['name' => '...', 'url' => '...']]`
- `$filters` (array) — активные фильтры

**Что добавляет:**
- Путь навигации от главной до текущей страницы
- **Активные фильтры как часть навигации**
- Форматированное отображение фильтров

#### 4. `registerSchemaWebSite()`

Регистрирует Schema.org WebSite для главной каталога.

**Что добавляет:**
- Информацию о сайте
- SearchAction для sitelinks search box

### Изменения в renderCatalogPage()

Добавлена автоматическая регистрация всех схем:

```php
// Регистрируем Schema.org микроразметку
if (!empty($products)) {
    // ItemList с расширенной информацией о товарах
    $this->registerSchemaItemList($products, $totalCount, $currentFilters);
    
    // BreadcrumbList с учетом фильтров
    $breadcrumbs = isset($metaTags['breadcrumbs']) ? $metaTags['breadcrumbs'] : [];
    $this->registerSchemaBreadcrumbs($breadcrumbs, $currentFilters);
    
    // WebSite schema (только для главной страницы каталога)
    if ($request->pathInfo === 'catalog' || $request->pathInfo === 'catalog/index') {
        $this->registerSchemaWebSite();
    }
}
```

### Изменения в action методах

#### actionBrand()
Добавлен параметр `breadcrumbs` в метатеги:
```php
'breadcrumbs' => [
    ['name' => $brand->name, 'url' => '/catalog/brand/' . $brand->slug]
],
```

#### actionCategory()
Добавлен параметр `breadcrumbs` в метатеги:
```php
'breadcrumbs' => [
    ['name' => $category->name, 'url' => '/catalog/category/' . $category->slug]
],
```

## Интеграция с layout

Для вывода JSON-LD схем необходимо добавить в layout файл (`views/layouts/public.php`):

```php
<?php
// В секции <head> перед </head>
if (isset($this->params['jsonLdSchemas']) && is_array($this->params['jsonLdSchemas'])) {
    foreach ($this->params['jsonLdSchemas'] as $key => $jsonLd) {
        echo '<script type="application/ld+json">' . $jsonLd . '</script>' . "\n";
    }
}
?>
```

**Полный пример для layout:**

```php
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title><?= Html::encode($this->title) ?></title>
    
    <!-- Другие метатеги -->
    <?php $this->head() ?>
    
    <!-- JSON-LD Schemas -->
    <?php
    if (isset($this->params['jsonLdSchemas']) && is_array($this->params['jsonLdSchemas'])) {
        foreach ($this->params['jsonLdSchemas'] as $key => $jsonLd) {
            echo '<script type="application/ld+json">' . $jsonLd . '</script>' . "\n";
        }
    }
    ?>
</head>
<body>
    <?php $this->beginBody() ?>
    <!-- Контент -->
    <?php $this->endBody() ?>
</body>
</html>
```

## Тестирование

### 1. Google Rich Results Test

**URL:** https://search.google.com/test/rich-results

**Шаги:**
1. Откройте Google Rich Results Test
2. Введите URL страницы каталога
3. Нажмите "Test URL"
4. Проверьте результаты валидации

**Что должно отображаться:**
- ✅ ItemList обнаружен
- ✅ Product элементы валидны
- ✅ BreadcrumbList обнаружен
- ✅ Нет ошибок валидации

### 2. Schema Markup Validator

**URL:** https://validator.schema.org/

**Шаги:**
1. Откройте Schema.org Validator
2. Введите URL или вставьте JSON-LD код
3. Нажмите "Run Test"
4. Проверьте результаты

### 3. Локальная проверка

**Просмотр в браузере:**
```
1. Откройте страницу каталога
2. F12 (DevTools)
3. Elements → <head>
4. Найдите <script type="application/ld+json">
5. Проверьте содержимое JSON
```

**Через curl:**
```bash
curl https://your-domain.com/catalog | grep -A 50 'application/ld+json'
```

**Скрипт для проверки:**
```bash
./check-schema-org.sh https://your-domain.com/catalog
```

### 4. Проверка в браузерных расширениях

**Рекомендуемые расширения:**
- **Schema.org Validator** (Chrome)
- **Structured Data Testing Tool** (Chrome/Firefox)
- **SEO META in 1 CLICK** (Chrome)

## Примеры для разных страниц

### Главная страница каталога (`/catalog`)

**JSON-LD схемы:**
1. ✅ ItemList — список всех товаров
2. ✅ BreadcrumbList — Главная → Каталог
3. ✅ WebSite — информация о сайте с поиском

### Страница бренда (`/catalog/brand/nike`)

**JSON-LD схемы:**
1. ✅ ItemList — товары Nike
2. ✅ BreadcrumbList — Главная → Каталог → Nike

### Страница категории (`/catalog/category/sneakers`)

**JSON-LD схемы:**
1. ✅ ItemList — товары категории
2. ✅ BreadcrumbList — Главная → Каталог → Кроссовки

### С фильтрами (`/catalog?brands=1,2&price_from=100&price_to=500`)

**JSON-LD схемы:**
1. ✅ ItemList с description: "Nike, Adidas. от 100 до 500 BYN"
2. ✅ BreadcrumbList — Главная → Каталог → "Nike, Adidas • Цена: от 100 до 500 BYN"

## Влияние на SEO

### Преимущества микроразметки

1. **Rich Snippets в Google**
   - Рейтинги товаров со звездами
   - Цены и наличие в поиске
   - Breadcrumbs в результатах поиска

2. **Sitelinks Search Box**
   - Поиск по сайту прямо из Google
   - Увеличение CTR на 10-20%

3. **Улучшенная индексация**
   - Лучшее понимание контента Google
   - Быстрее индексация новых товаров
   - Правильная структура сайта в индексе

4. **Карусель товаров**
   - Возможность попасть в карусель товаров Google
   - Визуальное выделение в поиске

5. **Knowledge Graph**
   - Информация о бренде в Knowledge Panel
   - Связи между товарами и категориями

## Troubleshooting

### Проблема: Schema не отображается в HTML

**Проверьте:**
1. ✅ Добавлен код в layout для вывода `jsonLdSchemas`
2. ✅ Метод `registerJsonLd()` вызывается
3. ✅ Параметр `$this->view->params['jsonLdSchemas']` заполнен

**Решение:**
```php
// В layout добавьте перед </head>
<?php
if (isset($this->params['jsonLdSchemas'])) {
    foreach ($this->params['jsonLdSchemas'] as $jsonLd) {
        echo '<script type="application/ld+json">' . $jsonLd . '</script>' . "\n";
    }
}
?>
```

### Проблема: Ошибки валидации в Google

**Частые ошибки:**
1. **Missing required field "offers"** → проверьте, что у товара есть цена
2. **Invalid URL** → убедитесь, что URL абсолютные (с http/https)
3. **Invalid price format** → цена должна быть строкой: "299.00"

**Решение:**
- Проверьте данные в БД
- Убедитесь, что `Yii::$app->request->hostInfo` возвращает корректный домен
- Проверьте формат цены (должна быть string, а не number)

### Проблема: Не работает SearchAction

**Проверьте:**
1. ✅ WebSite schema регистрируется только на главной каталога
2. ✅ URL в `urlTemplate` корректный
3. ✅ Параметр поиска соответствует реальному

**Решение:**
- Проверьте, что страница действительно главная каталога
- Убедитесь, что поиск по URL `/catalog?search=test` работает

## Дополнительные улучшения

### Рекомендуется добавить

1. **Review Schema**
   ```php
   'review' => [
       '@type' => 'Review',
       'author' => 'John Doe',
       'datePublished' => '2025-11-08',
       'reviewRating' => [
           '@type' => 'Rating',
           'ratingValue' => '5'
       ]
   ]
   ```

2. **Organization Schema**
   ```php
   '@type' => 'Organization',
   'name' => 'СНИКЕРХЭД',
   'url' => 'https://domain.com',
   'logo' => 'https://domain.com/logo.png',
   'contactPoint' => [
       '@type' => 'ContactPoint',
       'telephone' => '+375-XX-XXX-XX-XX',
       'contactType' => 'Customer Service'
   ]
   ```

3. **FAQPage Schema** (для страниц с FAQ)

## Мониторинг эффективности

### Google Search Console

1. **Отслеживайте Rich Results:**
   - Перейдите в "Enhancements" → "Products"
   - Проверяйте количество валидных страниц
   - Исправляйте ошибки по мере их появления

2. **Анализируйте клики:**
   - Сравните CTR до и после внедрения
   - Ожидаемый рост: 15-30%

3. **Мониторьте индексацию:**
   - Проверяйте скорость индексации новых товаров
   - Убедитесь, что breadcrumbs отображаются в поиске

## Полезные ссылки

- **Schema.org Documentation:** https://schema.org/
- **Google Structured Data Guide:** https://developers.google.com/search/docs/appearance/structured-data/intro-structured-data
- **Google Rich Results Test:** https://search.google.com/test/rich-results
- **Schema Markup Validator:** https://validator.schema.org/
- **JSON-LD Playground:** https://json-ld.org/playground/

## Заключение

Микроразметка Schema.org полностью внедрена и готова к использованию. Все схемы автоматически генерируются на основе данных товаров и активных фильтров, обеспечивая актуальную и корректную информацию для поисковых систем.

**Следующие шаги:**
1. Добавить код вывода JSON-LD в layout
2. Протестировать в Google Rich Results Test
3. Отправить sitemap в Google Search Console
4. Мониторить результаты через 2-4 недели
