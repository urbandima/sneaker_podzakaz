# Open Graph и Twitter Cards - Реализация

## Обзор

Внедрены полные метатеги Open Graph и Twitter Cards для всех страниц каталога с динамической генерацией на основе активных фильтров.

## Реализованные метатеги

### Open Graph
- `og:title` - динамический заголовок на основе фильтров
- `og:description` - описание товаров с учетом выбранных фильтров
- `og:image` - изображение (приоритет: лого бренда/категория → первый товар → дефолт)
- `og:url` - канонический URL страницы с фильтрами
- `og:type` - `product.group` для каталога, `product` для товара
- `og:site_name` - "СНИКЕРХЭД"

### Twitter Cards
- `twitter:card` - `summary_large_image`
- `twitter:title` - дублирует og:title
- `twitter:description` - дублирует og:description
- `twitter:image` - дублирует og:image

## Динамическая генерация контента

### Страницы с поддержкой

1. **Главная страница каталога** (`/catalog`)
   - Базовое описание + фильтры
   - Изображение первого товара или дефолт

2. **Страница бренда** (`/catalog/brand/{slug}`)
   - Описание бренда + дополнительные фильтры
   - Приоритет: логотип бренда → первый товар → дефолт

3. **Страница категории** (`/catalog/category/{slug}`)
   - Описание категории + дополнительные фильтры
   - Приоритет: изображение категории → первый товар → дефолт

4. **Страница товара** (`/catalog/product/{slug}`)
   - Уже была реализована ранее
   - Полная информация о товаре с ценой и брендом

### Примеры динамической генерации

#### Каталог без фильтров
```
og:title: "Каталог товаров | СНИКЕРХЭД"
og:description: "Оригинальные товары из США и Европы"
```

#### Каталог с фильтром по бренду Nike
```
og:title: "Каталог товаров - Nike | СНИКЕРХЭД"
og:description: "Nike. Оригинальные товары из США и Европы"
```

#### Каталог с фильтром по цене
```
og:title: "Каталог товаров - от 100 до 500 BYN | СНИКЕРХЭД"
og:description: "от 100 до 500 BYN. Оригинальные товары из США и Европы"
```

#### Каталог с несколькими фильтрами
```
og:title: "Каталог товаров - Nike, Adidas - 100-500 BYN | СНИКЕРХЭД"
og:description: "Nike, Adidas. от 100 до 500 BYN. Оригинальные товары из США и Европы"
```

## Приоритет изображений

### Главная страница каталога
1. Изображение первого товара из выборки
2. `/images/og-default.jpg` (дефолтное изображение)

### Страница бренда
1. Логотип бренда (`brand.logo` или `brand.logo_url`)
2. Изображение первого товара бренда
3. `/images/og-default.jpg`

### Страница категории
1. Изображение категории (`category.image`)
2. Изображение первого товара категории
3. `/images/og-default.jpg`

### Страница товара
1. Главное изображение товара (`product.main_image_url`)

## Требования к изображениям

### Рекомендуемые размеры
- **Open Graph**: 1200x630 пикселей (соотношение 1.91:1)
- **Twitter Card**: 1200x675 пикселей (соотношение 16:9)
- **Минимальный размер**: 600x315 пикселей
- **Формат**: JPG или PNG
- **Размер файла**: до 5 МБ

### Дефолтное изображение
Необходимо создать файл `/web/images/og-default.jpg`:
- Размер: 1200x630 пикселей
- Содержание: логотип магазина, фирменные цвета
- Текст: "СНИКЕРХЭД - Оригинальные товары из США и Европы"

## Тестирование

### 1. Facebook Sharing Debugger

**URL**: https://developers.facebook.com/tools/debug/

**Шаги тестирования**:
1. Откройте Facebook Sharing Debugger
2. Введите URL страницы каталога (например: `https://your-domain.com/catalog`)
3. Нажмите "Debug"
4. Проверьте, что отображаются:
   - Правильный заголовок
   - Описание с учетом фильтров
   - Изображение товара или дефолтное
   - Канонический URL

**Тестовые URL**:
```
https://your-domain.com/catalog
https://your-domain.com/catalog?brands=1,2
https://your-domain.com/catalog?price_from=100&price_to=500
https://your-domain.com/catalog/brand/nike
https://your-domain.com/catalog/brand/nike?price_from=200
https://your-domain.com/catalog/category/sneakers
https://your-domain.com/catalog/product/some-product-slug
```

**Очистка кеша**:
- После изменений используйте "Scrape Again" для обновления кеша Facebook

### 2. Twitter Card Validator

**URL**: https://cards-dev.twitter.com/validator

**Шаги тестирования**:
1. Откройте Twitter Card Validator
2. Введите URL страницы
3. Нажмите "Preview card"
4. Проверьте превью карточки

**Ожидаемый результат**:
- Тип карточки: "Summary Card with Large Image"
- Заголовок, описание и изображение отображаются корректно

### 3. LinkedIn Post Inspector

**URL**: https://www.linkedin.com/post-inspector/

**Шаги**:
1. Введите URL
2. Нажмите "Inspect"
3. Проверьте превью

### 4. Telegram Preview

**Проверка в Telegram**:
1. Отправьте ссылку на страницу в любой чат
2. Telegram автоматически загрузит превью
3. Проверьте отображение заголовка, описания и изображения

### 5. Локальное тестирование

**Просмотр метатегов в HTML**:
```bash
curl https://your-domain.com/catalog | grep -E 'og:|twitter:'
```

**Проверка с помощью browser DevTools**:
1. Откройте страницу в браузере
2. Откройте DevTools (F12)
3. Перейдите на вкладку Elements
4. Найдите секцию `<head>`
5. Проверьте наличие всех метатегов `og:*` и `twitter:*`

## Проверочный чек-лист

### Главная страница каталога
- [ ] `og:title` содержит "Каталог товаров"
- [ ] `og:description` присутствует
- [ ] `og:image` ведет на валидное изображение
- [ ] `og:url` совпадает с текущим URL
- [ ] `og:type` = "product.group"
- [ ] Все Twitter метатеги присутствуют

### Страница с фильтрами
- [ ] Заголовок включает информацию о фильтрах (бренды, цена)
- [ ] Описание содержит данные о выбранных фильтрах
- [ ] URL содержит параметры фильтров
- [ ] Изображение соответствует выборке

### Страница бренда
- [ ] Заголовок содержит название бренда
- [ ] Описание специфично для бренда
- [ ] Изображение: логотип бренда или товар бренда
- [ ] `og:type` = "product.group"

### Страница категории
- [ ] Заголовок содержит название категории
- [ ] Описание специфично для категории
- [ ] Изображение: баннер категории или товар категории
- [ ] `og:type` = "product.group"

### Страница товара
- [ ] `og:type` = "product"
- [ ] `product:price:amount` присутствует
- [ ] `product:price:currency` = "BYN"
- [ ] Изображение товара высокого качества

## Интеграция с контроллером

### Методы контроллера

#### `generateFilteredDescription($filters, $baseDescription)`
Генерирует динамическое описание на основе активных фильтров.

**Параметры**:
- `$filters` - массив с ключами: brands, categories, price_from, price_to
- `$baseDescription` - базовое описание (опционально)

**Возвращает**: строку с описанием

#### `generateFilteredTitle($filters, $baseTitle)`
Генерирует динамический заголовок.

**Параметры**:
- `$filters` - массив с фильтрами
- `$baseTitle` - базовый заголовок

**Возвращает**: строку заголовка

#### `getFirstProductImage($query)`
Получает URL изображения первого товара из выборки.

**Параметры**:
- `$query` - ActiveQuery запрос товаров

**Возвращает**: string|null - URL изображения или null

### Использование

```php
// В методах actionIndex(), actionBrand(), actionCategory()
$currentFilters = [
    'brands' => $request->get('brands') ? explode(',', $request->get('brands')) : [],
    'categories' => $request->get('categories') ? explode(',', $request->get('categories')) : [],
    'price_from' => $request->get('price_from'),
    'price_to' => $request->get('price_to'),
];

$description = $this->generateFilteredDescription($currentFilters, 'Базовое описание');
$title = $this->generateFilteredTitle($currentFilters, 'Базовый заголовок');
$ogImage = $this->getFirstProductImage($query) ?: Yii::$app->request->hostInfo . '/images/og-default.jpg';
```

## Troubleshooting

### Изображение не отображается

**Проблема**: Facebook/Twitter не могут загрузить изображение

**Решения**:
1. Убедитесь, что URL изображения абсолютный (начинается с http:// или https://)
2. Проверьте, что изображение доступно публично (не требует авторизации)
3. Проверьте размер изображения (не более 5 МБ)
4. Убедитесь, что сервер отдает правильный Content-Type для изображения
5. Проверьте CORS заголовки, если изображения на другом домене

### Старый контент в превью

**Проблема**: После обновления метатегов отображается старая версия

**Решения**:
1. Используйте "Scrape Again" в Facebook Debugger
2. Добавьте параметр `?v=2` к URL для принудительного обновления
3. Очистите кеш CDN, если используется
4. Подождите 24-48 часов для автоматического обновления

### Превью не генерируется

**Проблема**: Социальные сети не видят метатеги

**Решения**:
1. Проверьте, что метатеги находятся в `<head>` секции
2. Убедитесь, что страница доступна для роботов (нет robots.txt блокировки)
3. Проверьте, что сервер отвечает с HTTP 200 (не 301/302)
4. Убедитесь, что контент не генерируется через JavaScript (должен быть в server-side HTML)

## Производительность

### Кеширование

Метатеги генерируются на каждом запросе, но:
- Запросы к БД для получения брендов/категорий минимальны (только ID и name)
- Используется `getFirstProductImage()` с `limit(1)` для быстрого получения изображения
- HTTP Cache headers установлены через `HttpCacheHeaders` компонент

### Оптимизация

Если требуется дополнительная оптимизация:
1. Добавьте кеширование для `generateFilteredDescription()` и `generateFilteredTitle()`
2. Кешируйте результат `getFirstProductImage()` на основе хэша фильтров
3. Используйте CDN для изображений Open Graph

## Дальнейшие улучшения

### Рекомендации для продакшена

1. **Создать дефолтное изображение** `/web/images/og-default.jpg`
2. **Добавить изображения для категорий** в админке
3. **Оптимизировать изображения товаров** до рекомендуемого размера 1200x630
4. **Настроить автоматическую генерацию** Open Graph изображений для товаров без фото
5. **Добавить A/B тестирование** разных заголовков и описаний
6. **Отслеживать CTR** из социальных сетей в Google Analytics

### Дополнительные метатеги

При необходимости можно добавить:
- `fb:app_id` - ID приложения Facebook для аналитики
- `twitter:site` - @username вашего Twitter аккаунта
- `og:locale` - язык контента (ru_RU, en_US)
- `article:author` - для блога/статей
- `product:availability` - для товаров (in stock / out of stock)
- `product:condition` - состояние товара (new)

## Документация

### Официальная документация

- [Open Graph Protocol](https://ogp.me/)
- [Twitter Cards Guide](https://developer.twitter.com/en/docs/twitter-for-websites/cards/overview/abouts-cards)
- [Facebook Sharing Best Practices](https://developers.facebook.com/docs/sharing/best-practices)
- [LinkedIn Post Inspector](https://www.linkedin.com/help/linkedin/answer/a521928)

### Полезные инструменты

- [Open Graph Checker](https://www.opengraph.xyz/)
- [Meta Tags Validator](https://metatags.io/)
- [Social Share Preview](https://socialsharepreview.com/)
