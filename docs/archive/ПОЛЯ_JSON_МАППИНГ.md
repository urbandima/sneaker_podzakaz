# Маппинг полей JSON → Product

## 📋 Поля в JSON

### Родительский товар (products[])
```json
{
  "productId": 392655,           // ID товара
  "variantId": 392655,            // ID варианта (для родителя = productId)
  "url": "https://...",           // Ссылка на Poizon
  "title": "Nike Dunk Low...",    // Название
  "description": "",              // Описание
  "vendorCode": "355152-106",     // Артикул
  "categoryId": 8699,             // ID категории
  "vendorId": 24,                 // ID бренда
  "vendor": "Nike",               // Название бренда
  "images": [...],                // Массив фото
  "price": 490,                   // Цена (BYN/RUB)
  "favoriteCount": 0,             // Избранное
  "countryOfOrigin": "Китай",     // Страна происхождения
  "gender": "Унисекс",            // Пол
  "seriesName": "",               // Серия
  "vat": "NO_VAT",                // НДС
  "currency": "RUR",              // Валюта
  "keywords": [...],              // Ключевые слова
  "properties": [...],            // Характеристики
  "sizes": [...],                 // Размерные сетки
  "relatedProducts": [...],       // Похожие товары
  "children": [...]               // Варианты (размеры)
}
```

### Дочерний вариант (children[])
```json
{
  "productId": 392655,            // ID товара (родителя)
  "parentId": 392655,             // ID родителя
  "variantId": 659423919,         // ID варианта (SKU)
  "title": "...",                 // Название с размером
  "vendorCode": "355152-106-1",   // Артикул варианта
  "price": 0,                     // Цена для клиента
  "purchasePrice": 0,             // Закупочная цена (CNY)
  "count": 0,                     // Остаток
  "available": false,             // Доступность
  "timeDelivery": {
    "min": 15,
    "max": 24
  },
  "params": [                     // Параметры варианта
    {"key": "Цвет", "value": "Белое"},
    {"key": "Размер", "value": "38"}
  ],
  "images": [...],                // Фото варианта
  "vendor": "Nike",
  "seriesName": "",
  "keywords": [...]
}
```

---

## ✅ ТЕКУЩИЙ МАППИНГ

### Product (родительский товар)

| JSON поле | Product поле | Статус | Комментарий |
|-----------|--------------|--------|-------------|
| `productId` | `poizon_id` | ✅ | Используется для поиска |
| `variantId` | ❌ | ✅ | НЕ сохраняется для родителя |
| `title` | `name` | ✅ | |
| `description` | `description` | ✅ | |
| `vendorCode` | `vendor_code` | ✅ | |
| `url` | `poizon_url` | ✅ | |
| `price` | `price` | ✅ | |
| `categoryId` | `category_id` | ✅ | Через поиск Category |
| `vendorId` | `brand_id` | ✅ | Через поиск Brand |
| `vendor` | `brand_name` | ✅ | Денормализация |
| `images[0]` | `main_image` | ✅ | Первое фото |
| `images[]` | ProductImage | ✅ | Все фото |
| `countryOfOrigin` | `country_of_origin` | ✅ | |
| `favoriteCount` | `favorite_count` | ✅ | |
| `gender` | `gender` | ✅ | Через `mapGender()` |
| `seriesName` | `series_name` | ✅ | |
| `properties` | `properties` | ✅ | JSON |
| `sizes` | `sizes_data` | ✅ | JSON |
| `keywords` | `keywords` | ✅ | JSON |
| `vat` | ❌ | ⚠️ | **НЕ сохраняется** |
| `currency` | ❌ | ⚠️ | **НЕ сохраняется** |
| `relatedProducts` | ❌ | ⚠️ | **НЕ сохраняется** |

### ProductSize (из children[])

| JSON поле | ProductSize поле | Статус | Комментарий |
|-----------|------------------|--------|-------------|
| `variantId` | `poizon_sku_id` | ✅ | Уникальный SKU |
| `params[Размер]` | `size`, `us_size`, `eu_size`, `uk_size` | ✅ | Парсится |
| `count` | `stock`, `poizon_stock` | ✅ | |
| `purchasePrice` | `poizon_price_cny` | ✅ | В юанях |
| `price` | `price` | ✅ | Для клиента |
| `available` | `is_available` | ✅ | |
| `params[Цвет]` | ❌ | ⚠️ | **НЕ сохраняется** |
| `timeDelivery` | ❌ | ⚠️ | **НЕ сохраняется** |

---

## ⚠️ ОБНАРУЖЕННЫЕ ПРОБЛЕМЫ

### 1. ❌ НЕ импортируются важные поля

**НЕ сохраняются**:
- `vat` (НДС) - может быть важно для бухгалтерии
- `currency` (валюта) - всегда "RUR", но стоит сохранять
- `relatedProducts` (похожие товары) - для рекомендаций
- `timeDelivery` (сроки доставки) - важно для клиента!
- `params[Цвет]` из children - цвет варианта

---

### 2. ⚠️ ДУБЛИРОВАНИЕ в properties

**Проблема**: В `properties` есть дублирование с основными полями:

```json
"properties": [
  {"key": "Идентификатор стиля", "value": "355152-106"},  // = vendorCode
  {"key": "Основной цвет", "value": "Белый"},              // дублируется
  {"key": "Комбинация", "value": "Белый"},                 // дублируется
  {"key": "Релиз Свидание", "value": "09/27/2024"}         // можно в release_date
]
```

**Что происходит**:
- ✅ Все `properties` сохраняются в JSON поле `product.properties`
- ❌ Но НЕ парсятся в отдельные поля (`color`, `release_year`, etc.)

---

### 3. ⚠️ sizes[] не используется для ProductSize

**В JSON есть готовые размерные сетки**:
```json
"sizes": [
  {"name": "Европейский кодекс ЕС", "value": "35.5,36,36.5,..."},
  {"name": "Кодекс красоты США", "value": "3.5,4,4.5,..."}
]
```

**Проблема**:
- ✅ Сохраняется в `product.sizes_data` (JSON)
- ❌ НЕ используется для автозаполнения `ProductSize.eu_size`, `us_size`
- ❌ Вместо этого парсим из `children[].params[Размер]`
- ⚠️ Если в params нет "EU 42 / US 8.5", размеры теряются!

---

## 🔧 РЕКОМЕНДАЦИИ ПО ИСПРАВЛЕНИЮ

### 1. Добавить недостающие поля в Product

```php
// Миграция
ALTER TABLE product ADD COLUMN vat VARCHAR(50);
ALTER TABLE product ADD COLUMN currency VARCHAR(10) DEFAULT 'BYN';
ALTER TABLE product ADD COLUMN delivery_time_min INT;
ALTER TABLE product ADD COLUMN delivery_time_max INT;
```

**В импорте**:
```php
$product->vat = $data['vat'] ?? null;
$product->currency = $data['currency'] ?? 'BYN';
```

---

### 2. Парсить полезные properties

**Добавить в `parseProperties()`**:
```php
private function parseProperties($product, $properties)
{
    foreach ($properties as $prop) {
        $key = mb_strtolower($prop['key']);
        $value = $prop['value'];
        
        // Релиз дата
        if (strpos($key, 'релиз') !== false || strpos($key, 'release') !== false) {
            if (preg_match('/(\d{4})/', $value, $matches)) {
                $product->release_year = $matches[1];
            }
        }
        
        // Основной цвет
        if (strpos($key, 'основной цвет') !== false || strpos($key, 'color') !== false) {
            $product->color_description = $value;
        }
        
        // Тип закрытия → fastening
        if (strpos($key, 'закрытия') !== false) {
            $product->fastening = $this->mapFastening($value);
        }
        
        // Высота → height
        if (strpos($key, 'высота') !== false || strpos($key, 'голенища') !== false) {
            $product->height = $this->mapHeight($value);
        }
    }
}
```

---

### 3. Использовать sizes[] для заполнения размеров

**Проблема**: Сейчас полагаемся только на `children[].params[Размер]`

**Решение**: Использовать `sizes[]` как lookup таблицу:
```php
private function importSizes($product, $children)
{
    // Создаем lookup таблицу из sizes
    $sizesLookup = $this->buildSizesLookup($product->sizes_data);
    
    foreach ($children as $childData) {
        $sizeValue = $this->extractSize($childData['params']);
        
        // Если нашли размер, ищем соответствие в lookup
        if ($sizeValue && isset($sizesLookup[$sizeValue])) {
            $size->us_size = $sizesLookup[$sizeValue]['us'];
            $size->eu_size = $sizesLookup[$sizeValue]['eu'];
            $size->uk_size = $sizesLookup[$sizeValue]['uk'];
            $size->cm_size = $sizesLookup[$sizeValue]['cm'];
        }
    }
}
```

---

### 4. Сохранять цвет варианта

**Добавить в ProductSize**:
```php
// Миграция
ALTER TABLE product_size ADD COLUMN color VARCHAR(100);
```

**В импорте**:
```php
foreach ($childData['params'] as $param) {
    if (strpos(mb_strtolower($param['key']), 'цвет') !== false) {
        $size->color = $param['value'];
    }
}
```

---

### 5. Сохранять сроки доставки

**Уже есть поля в Product**:
- `delivery_time_min`
- `delivery_time_max`

**Заполнять из children**:
```php
if (!empty($childData['timeDelivery'])) {
    // Сохраняем среднее или максимальное время
    $product->delivery_time_min = min(
        $product->delivery_time_min ?? 999,
        $childData['timeDelivery']['min']
    );
    $product->delivery_time_max = max(
        $product->delivery_time_max ?? 0,
        $childData['timeDelivery']['max']
    );
}
```

---

## ✅ ИТОГОВЫЙ ЧЕКЛИСТ

### Обязательно исправить

- [x] ~~`products_count` → `count` в brand.php~~ **ИСПРАВЛЕНО**
- [ ] Парсить `properties` → отдельные поля (color, release_year, etc.)
- [ ] Использовать `sizes[]` для lookup размеров
- [ ] Сохранять `timeDelivery` из children
- [ ] Сохранять цвет варианта в ProductSize

### Желательно добавить

- [ ] Поле `vat` в Product
- [ ] Поле `currency` в Product  
- [ ] Связь `relatedProducts` (через таблицу many-to-many)
- [ ] Импорт `vendor` как строки, если Brand не найден

---

## 📊 ТЕКУЩЕЕ ПОКРЫТИЕ ПОЛЕЙ

**Импортируется**: 18 из 23 полей (78%)  
**Не импортируется**: 5 полей (22%)

**ProductSize**: 7 из 10 полей (70%)  
**ProductImage**: 100% ✅

---

## 🎯 ПРИОРИТЕТЫ

### 🔴 Критично (сломан UX)
1. ✅ ~~Ошибка `products_count`~~ - **ИСПРАВЛЕНО**

### 🟡 Важно (теряются данные)
2. Парсинг `properties` → отдельные поля
3. Сохранение `timeDelivery`
4. Использование `sizes[]` для ProductSize

### 🟢 Желательно (улучшение)
5. Сохранение `vat`, `currency`
6. Импорт `relatedProducts`
7. Цвет варианта

---

**Готово!** Документация по маппингу полей создана.
