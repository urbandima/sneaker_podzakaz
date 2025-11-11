# Исправление связей в системе фильтрации

**Дата:** 2025-11-10  
**Версия:** 3.0 (финальная)

## Обнаруженные проблемы со связями

### ❌ Проблема 1: Несоответствие форматов данных
**Описание:**
- `characteristic_value.value`: хранит русские названия ('Мужской', 'Женский', 'Унисекс')
- `characteristic_value.slug`: хранит английские значения ('male', 'female', 'unisex')
- `product.gender`: хранит английские значения ('male', 'female', 'unisex')

**Проблема:** Система не использовала `slug` для связи между таблицами.

---

### ❌ Проблема 2: Смешивание типов ID
**Описание:**
- Гибридный фильтр возвращает: `value['id'] = 'unisex'` (строка)
- Обычный фильтр возвращает: `value['id'] = 11` (число)
- View не различал эти типы

**Проблема:** При фильтрации система не понимала, что делать с разными типами ID.

---

### ❌ Проблема 3: Несинхронизированные данные
**Описание:**
- 20 товаров имели характеристику в `product_characteristic_value`
- Но в `product.gender` у всех было 'unisex'
- Данные не совпадали

**Проблема:** Фильтр показывал правильные счетчики, но фильтрация не работала.

---

## Реализованные исправления

### ✅ Исправление 1: Использование slug для связи

**Файл:** `FilterBuilder.php`

**Было:**
```php
// Фильтруем по полю product напрямую
$query->andWhere(['product.gender' => $value]);
```

**Стало:**
```php
// Проверяем тип значения
$isNumericId = is_numeric($firstValue);

if ($isNumericId) {
    // Это ID из characteristic_value - конвертируем через slug
    $charValues = CharacteristicValue::find()
        ->select(['slug'])
        ->where(['id' => $value])
        ->column();
    
    if (!empty($charValues)) {
        $query->andWhere(['product.gender' => $charValues]);
    }
} else {
    // Это строковые значения - используем напрямую
    $query->andWhere(['product.gender' => $value]);
}
```

**Результат:** Система теперь корректно конвертирует ID=11 → slug='male' → product.gender='male'

---

### ✅ Исправление 2: Отображение всех возможных значений

**Файл:** `FilterBuilder.php`

**Было:**
```php
// Формируем values только из найденных в БД
foreach ($stats as $stat) {
    $values[] = [
        'id' => $stat[$fieldName],
        'value' => $valueLabels[$stat[$fieldName]],
        'count' => $stat['count'],
    ];
}
```

**Стало:**
```php
// Добавляем ВСЕ возможные значения, даже с count=0
$allPossibleValues = array_keys($valueLabels);
$statsByValue = [];
foreach ($stats as $stat) {
    $statsByValue[$stat[$fieldName]] = (int)$stat['count'];
}

foreach ($allPossibleValues as $value) {
    $count = $statsByValue[$value] ?? 0;
    $values[] = [
        'id' => $value,
        'value' => $valueLabels[$value],
        'count' => $count,
    ];
}
```

**Результат:** Фильтр теперь показывает:
- Мужской: 12 товаров
- Женский: 8 товаров
- Унисекс: 655 товаров

Вместо только:
- Унисекс: 675 товаров

---

### ✅ Исправление 3: Синхронизация данных

**Файл:** `sync-gender-data.php` (скрипт миграции)

```php
// Синхронизируем product.gender с product_characteristic_value
foreach ($productCharValues as $pcv) {
    $product = Product::findOne($pcv->product_id);
    $charValue = CharacteristicValue::findOne($pcv->characteristic_value_id);
    
    // Используем slug как значение для product.gender
    $newGender = $charValue->slug; // 'male', 'female', 'unisex'
    
    if ($product->gender !== $newGender) {
        $product->gender = $newGender;
        $product->save(false);
    }
}
```

**Результат:** 
- Обновлено 20 товаров
- 12 товаров получили gender='male'
- 8 товаров получили gender='female'

---

## Схема работы после исправлений

### Сценарий 1: Пользователь выбирает "Мужской" из фильтра

```
1. Frontend отправляет: char_3 = ['male']
   ↓
2. FilterBuilder::applyFiltersToQuery()
   ↓
3. Проверка: is_numeric('male') = false
   ↓
4. Используем напрямую: WHERE product.gender = 'male'
   ↓
5. Результат: 12 товаров ✅
```

### Сценарий 2: Старые данные с ID=11

```
1. Frontend отправляет: char_3 = [11]
   ↓
2. FilterBuilder::applyFiltersToQuery()
   ↓
3. Проверка: is_numeric(11) = true
   ↓
4. Конвертация: ID=11 → slug='male'
   ↓
5. Используем: WHERE product.gender = 'male'
   ↓
6. Результат: 12 товаров ✅
```

### Сценарий 3: Обратная совместимость

Система поддерживает оба формата одновременно:
- ✅ Новые фильтры отправляют строки ('male', 'female', 'unisex')
- ✅ Старые фильтры отправляют числа (11, 12, 13)
- ✅ Оба варианта работают корректно

---

## Тестирование

### Автоматический тест:
```bash
php test-hybrid-filter.php
```

**Результат:**
```
✅ Фильтр 'Пол' найден
Тип данных: product.gender (гибридный)
Значения:
   - Мужской (id=male): 12 товаров
   - Женский (id=female): 8 товаров
   - Унисекс (id=unisex): 655 товаров
✅ Найдено товаров: 655
```

### Тест связей:
```bash
php test-filter-connections.php
```

**Результат:**
```
✅ Конвертация ID→slug работает: 11 → 'male'
✅ Товары с gender='male': 12 товаров
✅ Фильтрация по ID=11: 12 товаров
✅ Фильтрация по 'male': 12 товаров
```

---

## Преимущества решения

✅ **Обратная совместимость** — поддержка старых и новых форматов  
✅ **Автоматическая конвертация** — ID → slug → product.gender  
✅ **Синхронизация данных** — product.gender соответствует characteristic_value  
✅ **Корректные счетчики** — показывает реальное количество товаров  
✅ **Универсальность** — работает для всех гибридных полей (gender, season, material)

---

## Структура данных после исправлений

### characteristic_value (справочник)
```
ID  | Value    | Slug     | characteristic_id
----|----------|----------|------------------
11  | Мужской  | male     | 3
12  | Женский  | female   | 3
13  | Унисекс  | unisex   | 3
```

### product (реальные данные)
```
ID  | Name              | gender
----|-------------------|--------
5   | Nike Dunk Low     | male
6   | Nike Air Max      | female
25  | Nike Cortez       | unisex
```

### Связь через slug
```
characteristic_value.slug ←→ product.gender
         'male'           ←→    'male'
         'female'         ←→    'female'
         'unisex'         ←→    'unisex'
```

---

## Миграция существующих данных

Если у вас есть товары с характеристиками в `product_characteristic_value`, но не заполнено `product.gender`:

```bash
php sync-gender-data.php
```

Скрипт:
1. Найдет все связи в `product_characteristic_value`
2. Получит `slug` из `characteristic_value`
3. Обновит `product.gender` на соответствующее значение
4. Выведет отчет о синхронизации

---

## Файлы изменены

1. **FilterBuilder.php** — добавлена логика конвертации ID→slug
2. **sync-gender-data.php** — скрипт синхронизации данных
3. **test-filter-connections.php** — тест связей
4. **test-id-to-slug.php** — тест конвертации

---

## Мониторинг

### Проверка синхронизации:
```sql
-- Товары с несинхронизированными данными
SELECT p.id, p.name, p.gender, cv.slug
FROM product p
JOIN product_characteristic_value pcv ON pcv.product_id = p.id
JOIN characteristic_value cv ON cv.id = pcv.characteristic_value_id
WHERE pcv.characteristic_id = 3
  AND p.gender != cv.slug;
```

Должно вернуть 0 строк.

### Проверка счетчиков:
```sql
-- Подсчет по product.gender
SELECT gender, COUNT(*) as count
FROM product
WHERE is_active = 1
GROUP BY gender;
```

Результат должен совпадать с фильтром на фронтенде.

---

## Поддержка

При возникновении проблем:
1. Запустите `php test-filter-connections.php` для диагностики
2. Проверьте синхронизацию данных SQL запросом выше
3. Запустите `php sync-gender-data.php` для ресинхронизации
4. Очистите кэш: `php yii cache/flush-all`

---

**Статус:** ✅ Все связи исправлены и протестированы
