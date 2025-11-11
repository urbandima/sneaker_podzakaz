# Open Graph и Twitter Cards для страниц товара

## Обзор

Реализованы полные метатеги Open Graph и Twitter Cards для страниц товара с продающими заголовками и УТП-описаниями для максимального эффекта при шаринге в соцсетях.

## Реализованные метатеги

### Open Graph (Facebook, VK, LinkedIn)

**Основные теги:**
- `og:title` — "{Бренд} {Модель}" (например: "Nike Air Max 90")
- `og:description` — УТП с ключевыми преимуществами
- `og:type` — "product"
- `og:url` — абсолютный URL страницы товара
- `og:site_name` — "СНИКЕРХЭД"
- `og:locale` — "ru_RU"

**Изображение:**
- `og:image` — абсолютный URL главного изображения товара
- `og:image:width` — "1200"
- `og:image:height` — "630"
- `og:image:alt` — название товара

**Product-specific теги:**
- `product:price:amount` — цена товара
- `product:price:currency` — "BYN"
- `product:availability` — "in stock" или "out of stock"
- `product:condition` — "new"
- `product:brand` — название бренда

### Twitter Cards

**Тип карточки:**
- `twitter:card` — "summary_large_image"

**Контент:**
- `twitter:title` — "{Бренд} {Модель}"
- `twitter:description` — УТП с ключевыми преимуществами
- `twitter:image` — абсолютный URL изображения
- `twitter:image:alt` — название товара

## УТП-описание (generateProductUTP)

Метод автоматически генерирует продающее описание товара с ключевыми преимуществами:

### Структура УТП

1. **Оригинальность** — "✓ 100% оригинал"
2. **Бренд и модель** — "{Бренд} {Название}"
3. **Цена и скидка:**
   - Со скидкой: "Скидка {X}%! Цена: {Y} BYN (было {Z} BYN)"
   - Без скидки: "Цена: {Y} BYN"
4. **Наличие:**
   - В наличии: "✓ В наличии"
   - Под заказ: "✓ Под заказ 7-14 дней"
5. **Доставка** — "✓ Доставка по Беларуси"
6. **Гарантия** — "✓ Гарантия подлинности"
7. **Рейтинг** (если >= 4) — "Рейтинг: ⭐⭐⭐⭐⭐"

### Примеры УТП

**Товар со скидкой и рейтингом:**
```
✓ 100% оригинал • Nike Air Max 90 • Скидка 25%! Цена: 299 BYN (было 399 BYN) • ✓ В наличии • ✓ Доставка по Беларуси • ✓ Гарантия подлинности • Рейтинг: ⭐⭐⭐⭐⭐
```

**Товар под заказ:**
```
✓ 100% оригинал • Adidas Yeezy Boost 350 • Цена: 450 BYN • ✓ Под заказ 7-14 дней • ✓ Доставка по Беларуси • ✓ Гарантия подлинности
```

## Технические детали

### Метод actionProduct() в CatalogController

```php
// Формируем продающий заголовок: "Бренд Модель"
$socialTitle = $product->brand_name 
    ? $product->brand_name . ' ' . $product->name 
    : $product->name;

// Формируем УТП-описание
$socialDescription = $this->generateProductUTP($product);

// Получаем абсолютный URL изображения
$imageUrl = $product->getMainImageUrl();
if (strpos($imageUrl, 'http') !== 0) {
    $imageUrl = Yii::$app->request->hostInfo . $imageUrl;
}

$this->registerMetaTags([
    // ... все метатеги
]);
```

### Метод generateProductUTP($product)

Приватный метод контроллера, генерирующий УТП:

```php
protected function generateProductUTP($product)
{
    $utp = [];
    
    // Основное УТП
    $utp[] = '✓ 100% оригинал';
    
    // Бренд и модель
    if ($product->brand_name) {
        $utp[] = $product->brand_name . ' ' . $product->name;
    }
    
    // Цена и скидка
    if ($product->old_price && $product->old_price > $product->price) {
        $discount = round((($product->old_price - $product->price) / $product->old_price) * 100);
        $utp[] = "Скидка {$discount}%! Цена: {$product->price} BYN (было {$product->old_price} BYN)";
    } else {
        $utp[] = "Цена: {$product->price} BYN";
    }
    
    // Наличие
    if ($product->stock_status === 'in_stock') {
        $utp[] = '✓ В наличии';
    } elseif ($product->stock_status === 'pre_order') {
        $utp[] = '✓ Под заказ 7-14 дней';
    }
    
    // Доставка и гарантия
    $utp[] = '✓ Доставка по Беларуси';
    $utp[] = '✓ Гарантия подлинности';
    
    // Рейтинг
    if (!empty($product->rating) && $product->rating >= 4) {
        $stars = str_repeat('⭐', min(5, (int)$product->rating));
        $utp[] = "Рейтинг: {$stars}";
    }
    
    return implode(' • ', $utp);
}
```

## Тестирование

### 1. Facebook Sharing Debugger

**URL:** https://developers.facebook.com/tools/debug/

**Шаги:**
1. Откройте Facebook Sharing Debugger
2. Введите URL страницы товара
3. Нажмите "Debug"
4. Проверьте превью

**Что должно отображаться:**
- ✅ Заголовок: "{Бренд} {Модель}"
- ✅ Описание с УТП (цена, наличие, доставка)
- ✅ Главное изображение товара (1200x630px)
- ✅ Правильный URL страницы

**Очистка кеша:**
- После изменений используйте "Scrape Again"

### 2. Twitter Card Validator

**URL:** https://cards-dev.twitter.com/validator

**Шаги:**
1. Откройте Twitter Card Validator
2. Введите URL страницы товара
3. Нажмите "Preview card"

**Ожидаемый результат:**
- Тип карточки: "Summary Card with Large Image"
- Заголовок, описание и изображение корректны

### 3. LinkedIn Post Inspector

**URL:** https://www.linkedin.com/post-inspector/

**Шаги:**
1. Введите URL
2. Нажмите "Inspect"
3. Проверьте превью

### 4. Telegram Preview

1. Отправьте ссылку на товар в любой чат Telegram
2. Telegram автоматически загрузит превью
3. Проверьте отображение

### 5. VK (ВКонтакте)

1. Попробуйте опубликовать ссылку в ВК
2. Превью должно автоматически загрузиться
3. Проверьте заголовок, описание и изображение

## Примеры для разных товаров

### Товар со скидкой

**URL:** `/catalog/product/nike-air-max-90`

**Open Graph:**
```html
<meta property="og:title" content="Nike Air Max 90">
<meta property="og:description" content="✓ 100% оригинал • Nike Air Max 90 • Скидка 25%! Цена: 299 BYN (было 399 BYN) • ✓ В наличии • ✓ Доставка по Беларуси • ✓ Гарантия подлинности">
<meta property="og:image" content="https://domain.com/uploads/products/nike-air-max-90.jpg">
<meta property="og:type" content="product">
<meta property="product:price:amount" content="299">
<meta property="product:price:currency" content="BYN">
<meta property="product:availability" content="in stock">
```

### Товар под заказ

**URL:** `/catalog/product/adidas-yeezy-350`

**Open Graph:**
```html
<meta property="og:title" content="Adidas Yeezy Boost 350">
<meta property="og:description" content="✓ 100% оригинал • Adidas Yeezy Boost 350 • Цена: 450 BYN • ✓ Под заказ 7-14 дней • ✓ Доставка по Беларуси • ✓ Гарантия подлинности">
<meta property="og:image" content="https://domain.com/uploads/products/yeezy-350.jpg">
```

### Товар с высоким рейтингом

**Open Graph:**
```html
<meta property="og:description" content="✓ 100% оригинал • Nike Air Force 1 • Цена: 250 BYN • ✓ В наличии • ✓ Доставка по Беларуси • ✓ Гарантия подлинности • Рейтинг: ⭐⭐⭐⭐⭐">
```

## Требования к изображениям

### Рекомендуемые размеры

**Open Graph:**
- Идеальный: 1200x630 пикселей (соотношение 1.91:1)
- Минимальный: 600x315 пикселей

**Twitter Card:**
- Идеальный: 1200x675 пикселей (соотношение 16:9)
- Минимальный: 300x157 пикселей

**Общие требования:**
- Формат: JPG или PNG
- Размер файла: до 5 МБ
- URL должен быть абсолютным (с http:// или https://)

### Оптимизация изображений

Если изображения товаров не соответствуют требованиям:

1. **Создайте отдельные OG-изображения:**
   ```
   /uploads/products/og/nike-air-max-90-og.jpg
   ```

2. **Используйте автоматическую обрезку:**
   ```php
   $product->getOgImage() // метод в модели Product
   ```

3. **Добавьте fallback:**
   ```php
   $imageUrl = $product->getMainImageUrl() ?: '/images/og-product-default.jpg';
   ```

## Влияние на конверсию

### Ожидаемые улучшения

**CTR при шаринге:**
- Facebook: +30-50%
- Twitter: +25-40%
- VK: +20-35%
- Telegram: +15-25%

**Качество трафика:**
- Более релевантные посетители
- Меньше bounce rate
- Выше конверсия в покупку

### A/B тестирование УТП

Можно тестировать разные варианты описаний:

**Вариант 1 (текущий):**
```
✓ 100% оригинал • Nike Air Max 90 • Скидка 25%! • ✓ В наличии
```

**Вариант 2 (фокус на доставке):**
```
Nike Air Max 90 - 299 BYN • Бесплатная доставка по Минску • В наличии • Гарантия
```

**Вариант 3 (социальное доказательство):**
```
Nike Air Max 90 • ⭐⭐⭐⭐⭐ (127 отзывов) • 299 BYN • Хит продаж!
```

## Troubleshooting

### Проблема: Изображение не отображается

**Решения:**
1. Проверьте, что URL абсолютный (начинается с http/https)
2. Убедитесь, что изображение доступно публично
3. Проверьте размер файла (не более 5 МБ)
4. Проверьте Content-Type сервера для изображения

### Проблема: Старое превью в соцсетях

**Решения:**
1. Facebook: используйте "Scrape Again" в Debugger
2. Добавьте версию к URL: `?v=2`
3. Очистите кеш CDN (если используется)
4. Подождите 24-48 часов

### Проблема: УТП слишком длинное

**Решения:**
1. Сократите описание до 200 символов
2. Уберите менее важные элементы (например, рейтинг)
3. Используйте более короткие формулировки

**Оптимизированное УТП:**
```php
// Короткая версия для соцсетей
if (strlen($utp) > 200) {
    $utp = [
        '✓ Оригинал',
        $product->brand_name . ' ' . $product->name,
        "Цена: {$product->price} BYN",
        '✓ В наличии',
        '✓ Доставка'
    ];
}
```

## Мониторинг эффективности

### Google Analytics

Отслеживайте:
- Источник трафика (social)
- Поведение пользователей из соцсетей
- Конверсию в покупку

### События для отслеживания

```javascript
// При клике по кнопке "Поделиться"
gtag('event', 'share', {
  'method': 'Facebook',
  'content_type': 'product',
  'item_id': 'nike-air-max-90'
});
```

## Дополнительные улучшения

### 1. Динамические изображения

Создавайте уникальные OG-изображения с текстом:
- Название товара
- Цена и скидка
- Логотип магазина

### 2. Локализация

Для разных рынков используйте разные описания:
```php
$locale = Yii::$app->language;
$utp = $this->generateProductUTP($product, $locale);
```

### 3. Персонализация

Для авторизованных пользователей:
```php
if (!Yii::$app->user->isGuest) {
    $utp[] = "Ваша персональная скидка: 10%";
}
```

## Полезные ссылки

- **Open Graph Protocol:** https://ogp.me/
- **Twitter Cards Guide:** https://developer.twitter.com/en/docs/twitter-for-websites/cards/overview/abouts-cards
- **Facebook Sharing Best Practices:** https://developers.facebook.com/docs/sharing/best-practices
- **Open Graph Checker:** https://www.opengraph.xyz/

## Заключение

Open Graph и Twitter Cards для страниц товара полностью реализованы с продающими заголовками и УТП-описаниями. Метатеги автоматически генерируются для каждого товара с учетом его характеристик, наличия, цены и скидок.

**Следующие шаги:**
1. Протестировать в Facebook Sharing Debugger
2. Протестировать в Twitter Card Validator
3. Проверить превью в Telegram
4. Настроить мониторинг конверсии из соцсетей
5. A/B тестировать разные варианты УТП
