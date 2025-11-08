# Исправление ошибки в category.php

## Дата: 2025-11-05 21:39

## Проблема

При открытии страницы категории возникала ошибка:

```
PHP Warning – yii\base\ErrorException
Undefined array key "products_count"

in /Users/user/CascadeProjects/splitwise/views/catalog/category.php at line 64
```

## Причина

В файле `category.php` использовался неправильный ключ массива для получения количества товаров бренда:

**Было (строка 64):**
```php
<span class="count">(<?= $brand['products_count'] ?>)</span>
```

В проекте используется ключ `'count'`, а не `'products_count'`.

## Решение

Добавлена проверка с fallback на оба возможных ключа, аналогично тому как это реализовано в `index.php`:

**Стало (строки 61-65):**
```php
<?php $brandCount = isset($brand['count']) ? $brand['count'] : (isset($brand['products_count']) ? $brand['products_count'] : 0); ?>
<label class="filter-checkbox">
    <input type="checkbox" name="brands[]" value="<?= $brand['id'] ?>">
    <span><?= Html::encode($brand['name']) ?></span>
    <span class="count">(<?= $brandCount ?>)</span>
</label>
```

## Логика проверки

```php
$brandCount = isset($brand['count']) 
    ? $brand['count']                      // Приоритет 1: используем 'count'
    : (isset($brand['products_count'])     // Приоритет 2: fallback на 'products_count'
        ? $brand['products_count'] 
        : 0);                              // Приоритет 3: по умолчанию 0
```

## Преимущества решения

1. ✅ **Совместимость** — работает с обоими форматами данных
2. ✅ **Безопасность** — нет ошибок при отсутствии ключа
3. ✅ **Консистентность** — используется тот же подход что и в `index.php` для категорий
4. ✅ **Надежность** — fallback на 0 если данных нет

## Проверенные файлы

- ✅ `/views/catalog/category.php` — **ИСПРАВЛЕНО**
- ✅ `/views/catalog/index.php` — уже использует правильную проверку
- ✅ `/views/catalog/brands.php` — использует `??` оператор, проблем нет

## Статус

✅ **Ошибка исправлена**

Теперь страница категории работает корректно без PHP warnings.

---

## Файл изменен

- `/views/catalog/category.php` (строки 61-65)
