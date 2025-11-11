# Руководство по умным фильтрам (Smart Filters)

## Обзор

Полностью динамическая система фильтрации товаров с управлением через админку, REST API и CLI.

**Масштабируемость: 15/15** ✅

## Быстрый старт

### 1. Проверка данных
```bash
php check-characteristics.php
```

Должно вывести:
```
✅ Активных характеристик-фильтров: 6
✅ Активных значений: 24
✅ Связей товар-характеристика: 120
```

### 2. Открыть каталог
```
http://localhost:8080/catalog
```

Фильтры отображаются в сайдбаре:
- Материал (мультивыбор)
- Сезон (одиночный выбор)
- Пол, Высота, Застежка, Страна

### 3. Проверить фильтрацию
1. Выберите "Материал → Кожа"
2. Товары отфильтруются без перезагрузки (AJAX)
3. Счётчики других фильтров обновятся ("умное сужение")

## Управление характеристиками

### Админка

#### Список
```
/admin/characteristic/index
```
- GridView со всеми характеристиками
- Кнопки: Создать, Массовое назначение, Импорт, Справочник

#### Создание
```
/admin/characteristic/create
```
- Динамическое добавление значений через JS
- Автогенерация slug
- Типы: select, multiselect, number, boolean

#### Массовое назначение
```
/admin/characteristic/bulk-assign
```
Пример: назначить "Материал: Кожа" всем товарам бренда Nike:
1. Выбрать характеристику: Материал
2. Выбрать значение: Кожа
3. Фильтр: Бренд = Nike
4. Нажать "Назначить"

#### CSV Импорт
```
/admin/characteristic/import
```
Формат CSV:
```csv
key,name,type,value,slug,sort_order
material,Материал,select,Кожа,leather,0
material,Материал,select,Замша,suede,1
season,Сезон,select,Лето,summer,0
```

### REST API

#### Получить все характеристики
```bash
curl http://localhost:8080/api/characteristic?is_filter=1
```

#### Получить значения характеристики
```bash
curl http://localhost:8080/api/characteristic/1/values
```

#### Создать характеристику (требует авторизацию)
```bash
curl -X POST http://localhost:8080/api/characteristic \
  -H "Content-Type: application/json" \
  -d '{
    "key": "style",
    "name": "Стиль",
    "type": "select",
    "is_filter": 1,
    "is_active": 1
  }'
```

#### Добавить значение
```bash
curl -X POST http://localhost:8080/api/characteristic/1/values \
  -H "Content-Type: application/json" \
  -d '{
    "value": "Спортивный",
    "slug": "sport"
  }'
```

### CLI Commands

#### Импорт из CSV
```bash
php yii characteristic/import --file=@app/data/characteristics.csv
```

#### Массовое назначение
```bash
# Назначить "Материал: Кожа" товарам бренда 5
php yii characteristic/bulk-assign \
  --char=material \
  --value=leather \
  --brand=5

# Назначить с фильтром по цене
php yii characteristic/bulk-assign \
  --char=season \
  --value=summer \
  --priceFrom=5000 \
  --priceTo=15000
```

## Архитектура

### Backend Flow

```
[User выбирает фильтр]
    ↓
[catalog.js → collectFilterState()]
    ↓
[AJAX POST → /catalog/filter]
    ↓
[CatalogController::actionFilter()]
    ↓
[FilterBuilder::applyFiltersToProductQuery()]
    ↓
[SQL с подзапросами к product_characteristic_value]
    ↓
[Отфильтрованные товары + обновленные счётчики]
    ↓
[JSON response]
    ↓
[catalog.js обновляет UI]
```

### Database Schema

```sql
characteristic (id, key, name, type, is_filter, is_active, sort_order, version, updated_by)
    ↓
characteristic_value (id, characteristic_id, value, slug, sort_order, is_active)
    ↓
product_characteristic_value (product_id, characteristic_id, characteristic_value_id)
    ↓
product (id, name, price, ...)
```

### FilterBuilder

**Оптимизации:**
- Batch-запрос для всех значений всех характеристик (N+1 → 1)
- Кэширование через `CacheManager` (TTL: medium, тег: filters)
- Умное сужение: счётчики учитывают активные фильтры

**Пример SQL:**
```sql
SELECT cv.id, cv.value, COUNT(DISTINCT pcv.product_id) as count
FROM characteristic_value cv
LEFT JOIN product_characteristic_value pcv 
  ON pcv.characteristic_value_id = cv.id 
  AND pcv.product_id IN (
    SELECT id FROM product 
    WHERE is_active=1 
      AND brand_id IN (1,2,3)  -- активные фильтры
  )
WHERE cv.characteristic_id = 1
GROUP BY cv.id
```

## Типы характеристик

### Select (radio)
Одиночный выбор. Пример: Сезон, Пол
```php
'type' => Characteristic::TYPE_SELECT
```

### Multiselect (checkbox)
Множественный выбор. Пример: Материал, Застежка
```php
'type' => Characteristic::TYPE_MULTISELECT
```

### Number (range)
Диапазон чисел. Пример: Вес, Высота (см)
```php
'type' => Characteristic::TYPE_NUMBER
```

### Boolean (checkbox)
Да/Нет. Пример: Водонепроницаемые, С утеплителем
```php
'type' => Characteristic::TYPE_BOOLEAN
```

## Audit & History

### Просмотр истории (SQL)
```sql
SELECT 
  h.field_name,
  h.old_value,
  h.new_value,
  u.username,
  h.created_at
FROM characteristic_history h
LEFT JOIN user u ON u.id = h.changed_by
WHERE h.characteristic_id = 1
ORDER BY h.created_at DESC;
```

### Версионирование
При каждом изменении:
1. Инкремент `characteristic.version`
2. Запись в `characteristic_history`
3. Обновление `characteristic.updated_by`

## Troubleshooting

### Фильтры не отображаются
```bash
# Проверить данные
php check-characteristics.php

# Очистить кэш
rm -rf runtime/cache/*
```

### Фильтрация не работает
```sql
-- Проверить связи
SELECT COUNT(*) FROM product_characteristic_value;
-- Должно быть > 0

-- Проверить товары
SELECT p.name, c.name as char_name, cv.value
FROM product p
JOIN product_characteristic_value pcv ON pcv.product_id = p.id
JOIN characteristic c ON c.id = pcv.characteristic_id
JOIN characteristic_value cv ON cv.id = pcv.characteristic_value_id
LIMIT 10;
```

### AJAX не работает
1. Открыть DevTools → Network
2. Выбрать фильтр
3. Найти запрос `/catalog/filter`
4. Проверить POST параметры (должны быть `char_*`)

## Best Practices

### 1. Именование
- **key**: латиница, snake_case (`material`, `country_origin`)
- **slug**: латиница, kebab-case (`leather`, `made-in-usa`)

### 2. Сортировка
Используйте `sort_order` для управления порядком:
- Характеристики: популярные наверху
- Значения: логический порядок (S→M→L, Зима→Лето)

### 3. Производительность
- Добавляйте индексы на часто фильтруемые поля
- Используйте `is_active=0` вместо удаления
- Назначайте характеристики batch-операциями, не по одной

### 4. UX
- Не создавайте > 10 характеристик-фильтров
- Группируйте похожие (Размер EU/US/UK → одна характеристика)
- Скрывайте пустые значения (count=0)

## Примеры использования

### Сценарий 1: Новая коллекция
```bash
# 1. Создать характеристику "Коллекция"
curl -X POST /api/characteristic -d '{"key":"collection","name":"Коллекция","type":"select"}'

# 2. Добавить значения
curl -X POST /api/characteristic/7/values -d '{"value":"Осень 2024","slug":"fall-2024"}'

# 3. Назначить всем новым товарам
php yii characteristic/bulk-assign \
  --char=collection \
  --value=fall-2024 \
  --priceFrom=0 \
  --priceTo=999999
```

### Сценарий 2: Импорт из Excel
```bash
# 1. Экспортировать в CSV (key,name,type,value,slug,sort_order)
# 2. Загрузить через админку: /admin/characteristic/import
# 3. Проверить: SELECT * FROM characteristic_value ORDER BY id DESC LIMIT 10;
```

### Сценарий 3: A/B тестирование фильтров
```sql
-- Отключить характеристику
UPDATE characteristic SET is_filter=0 WHERE key='old_filter';

-- Включить новую
UPDATE characteristic SET is_filter=1 WHERE key='new_filter';

-- Очистить кэш
DELETE FROM cache WHERE id LIKE 'filter_builder_%';
```

## Roadmap

- [ ] Зависимые фильтры (Страна → Город)
- [ ] Фильтры с картинками (цвета, паттерны)
- [ ] Экспорт характеристик в Excel
- [ ] Массовое редактирование через GridView
- [ ] Предустановленные наборы (комплекты фильтров)

---

**Версия:** 1.0  
**Дата:** 2025-11-09  
**Автор:** СНИКЕРХЭД Development Team
