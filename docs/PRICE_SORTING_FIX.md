# Исправление сортировки по цене в каталоге

**Дата**: 10.11.2024  
**Статус**: ✅ Исправлено  
**Приоритет**: Критический

---

## Проблема

При сортировке каталога по цене (возрастание) товары отображались в неправильном порядке:

- **Первые страницы**: товары с нулевыми ценами (price = 0 или NULL)
- **Пятая страница**: товары с реальными ценами
- **Шестая страница**: снова нулевые цены

### Причина

Сортировка использовала поле `product.price`, но:
- Реальные цены хранятся в связанной таблице `product_size.price_byn`
- Поле `product.price` может быть NULL или 0
- При сортировке по возрастанию NULL/0 попадают в начало выборки

---

## Решение

### 1. Исправлена логика сортировки

**Файл**: `controllers/CatalogController.php`  
**Метод**: `applyFilters()`  
**Строки**: 1099-1123

#### Сортировка по возрастанию (`sort=price_asc`)

```php
case 'price_asc':
    // Вычисляем минимальную цену из product_size
    $query->addSelect([
        'min_price' => '(SELECT MIN(price_byn) FROM product_size 
                        WHERE product_size.product_id = product.id 
                        AND product_size.is_available = 1 
                        AND product_size.price_byn > 0)'
    ]);
    
    // Исключаем товары без цен
    $query->andWhere([
        'product.id' => new \yii\db\Expression(
            'SELECT DISTINCT product_id FROM product_size 
             WHERE is_available = 1 AND price_byn > 0'
        )
    ]);
    
    $query->orderBy(['min_price' => SORT_ASC]);
    break;
```

#### Сортировка по убыванию (`sort=price_desc`)

```php
case 'price_desc':
    // Вычисляем максимальную цену из product_size
    $query->addSelect([
        'max_price' => '(SELECT MAX(price_byn) FROM product_size 
                        WHERE product_size.product_id = product.id 
                        AND product_size.is_available = 1 
                        AND product_size.price_byn > 0)'
    ]);
    
    // Исключаем товары без цен
    $query->andWhere([
        'product.id' => new \yii\db\Expression(
            'SELECT DISTINCT product_id FROM product_size 
             WHERE is_available = 1 AND price_byn > 0'
        )
    ]);
    
    $query->orderBy(['max_price' => SORT_DESC]);
    break;
```

### 2. Добавлен индекс для оптимизации

**Файл**: `migrations/m251110_191000_add_price_sorting_index.php`

Создан составной индекс для ускорения подзапросов:

```php
$this->createIndex(
    'idx_product_size_price_sorting',
    '{{%product_size}}',
    ['product_id', 'is_available', 'price_byn']
);
```

**Покрывает условия**:
- `WHERE product_id = X`
- `AND is_available = 1`
- `AND price_byn > 0`

---

## Результат

✅ **Товары с нулевыми ценами исключены** из выборки при сортировке  
✅ **Сортировка работает корректно** на всех страницах пагинации  
✅ **Используется реальная цена** из таблицы `product_size.price_byn`  
✅ **Оптимизирована производительность** через составной индекс

---

## Тестирование

### Ручное тестирование

1. **Откройте каталог**: `/catalog`
2. **Выберите сортировку**: "Цена: по возрастанию"
3. **Проверьте**:
   - Первая страница содержит товары с минимальными ценами
   - Нет товаров с ценой 0 или NULL
   - Цены растут при переходе на следующие страницы
4. **Выберите сортировку**: "Цена: по убыванию"
5. **Проверьте**:
   - Первая страница содержит товары с максимальными ценами
   - Цены уменьшаются при переходе на следующие страницы

### SQL для проверки

```sql
-- Проверка товаров без цен
SELECT p.id, p.name, COUNT(ps.id) as sizes_count, MIN(ps.price_byn) as min_price
FROM product p
LEFT JOIN product_size ps ON ps.product_id = p.id AND ps.is_available = 1
WHERE p.is_active = 1
GROUP BY p.id
HAVING min_price IS NULL OR min_price = 0;

-- Проверка индекса
SHOW INDEX FROM product_size WHERE Key_name = 'idx_product_size_price_sorting';
```

### Проверка производительности

```sql
-- EXPLAIN для сортировки по возрастанию
EXPLAIN SELECT p.*, 
    (SELECT MIN(price_byn) FROM product_size 
     WHERE product_size.product_id = p.id 
     AND product_size.is_available = 1 
     AND product_size.price_byn > 0) as min_price
FROM product p
WHERE p.is_active = 1
AND p.id IN (SELECT DISTINCT product_id FROM product_size 
             WHERE is_available = 1 AND price_byn > 0)
ORDER BY min_price ASC
LIMIT 24;
```

**Ожидаемый результат**: индекс `idx_product_size_price_sorting` должен использоваться в подзапросах.

---

## Связанные файлы

- `controllers/CatalogController.php` — основная логика сортировки
- `migrations/m251110_191000_add_price_sorting_index.php` — индекс для оптимизации
- `models/Product.php` — метод `getPriceRange()` для получения диапазона цен
- `PROJECT_TASKS.md` — запись о выполненной работе

---

## Дополнительные улучшения (опционально)

### Кэширование минимальных/максимальных цен

Для дальнейшей оптимизации можно денормализовать данные:

1. Добавить поля в таблицу `product`:
   - `min_price_byn` — минимальная цена из размеров
   - `max_price_byn` — максимальная цена из размеров

2. Обновлять эти поля при изменении цен в `product_size`

3. Использовать их для сортировки вместо подзапросов

**Преимущества**:
- Быстрее (нет подзапросов)
- Проще индексировать

**Недостатки**:
- Требует синхронизации данных
- Дополнительная логика обновления

---

## Changelog

- **10.11.2024, 22:10** — Исправлена сортировка по цене, добавлен индекс
