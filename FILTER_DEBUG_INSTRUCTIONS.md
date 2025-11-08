# Инструкция по отладке фильтров каталога

## Что было исправлено

### 1. Frontend (`/web/js/catalog.js`)
- ✅ Добавлен сбор размеров в `collectFilterState()`
- ✅ Добавлен параметр `sizeSystem` для системы размеров (EU/US/UK/CM)
- ✅ Размеры передаются на бэкенд через POST

### 2. Backend (`/controllers/CatalogController.php`)
- ✅ `actionFilter()` читает POST параметры вместо GET
- ✅ Декодирует JSON параметры (brands, sizes, categories)
- ✅ Поддержка размерных систем

### 3. Views (`/views/catalog/index.php`)
- ✅ Улучшена логика поиска чекбоксов размеров
- ✅ Добавлена подробная отладка

## Как протестировать

### Шаг 1: Очистить кэш
```
Ctrl+Shift+R (Windows/Linux)
Cmd+Shift+R (Mac)
```

### Шаг 2: Открыть DevTools
1. Откройте страницу каталога: `/catalog`
2. Нажмите F12 (или Cmd+Option+I на Mac)
3. Перейдите на вкладку **Console**

### Шаг 3: Кликнуть на размер
1. Выберите любой размер в quick-filters (например, **40 EU**)
2. Смотрите логи в консоли

### Ожидаемые логи

```javascript
toggleSizeFilter вызван: {size: "40", system: "eu", wasActive: false}
Видимый grid: <div class="sidebar-size-grid" data-system="eu">
Найденный чекбокс: <input type="checkbox" name="sizes[]" value="40">
Чекбокс 40 (eu) обновлен: true
=== ПЕРЕД setTimeout ===
=== ПОСЛЕ setTimeout ===
=== ВНУТРИ setTimeout ===
Попытка вызвать applyFilters... function
Вызываем applyFilters()
```

### Шаг 4: Проверить результат
- Товары должны отфильтроваться
- Показаны только товары с размером 40 EU

## Возможные ошибки

### Ошибка 1: "Grid не найден"
```
Grid для системы eu не найден!
```
**Решение:** Проверьте, что sidebar с фильтрами загружен на странице

### Ошибка 2: "window.applyFilters не найдена"
```
window.applyFilters не найдена!
```
**Решение:** 
1. Проверьте загрузку `/web/js/catalog.js` в Network вкладке
2. Очистите кэш браузера
3. Убедитесь, что нет JavaScript ошибок выше в консоли

### Ошибка 3: "Чекбокс не найден"
```
Чекбокс для размера 40 в системе eu не найден!
```
**Решение:** Проверьте структуру HTML - должен быть `.sidebar-size-grid[data-system="eu"]` с `input[name="sizes[]"]` внутри

## Структура HTML фильтров

### Quick filters (чипы)
```html
<div class="quick-filters-sizes">
    <div class="size-group" data-system="eu">
        <button class="quick-chip size-chip" 
                data-size="40" 
                data-system="eu"
                onclick="toggleSizeFilter('40', 'eu')">
            <span>40</span>
        </button>
    </div>
</div>
```

### Sidebar filters (чекбоксы)
```html
<div class="sidebar-size-grid" data-system="eu">
    <label class="size-filter-btn">
        <input type="checkbox" name="sizes[]" value="40" data-system="eu">
        <span>40</span>
    </label>
</div>
```

## Проверка AJAX запроса

1. Откройте вкладку **Network** в DevTools
2. Кликните на размер
3. Найдите запрос к `/catalog/filter`
4. Проверьте **Payload** (должны быть размеры):

```json
{
  "brands": "[]",
  "categories": "[]",
  "sizes": "[\"40\"]",
  "sizeSystem": "eu",
  "price_from": "",
  "price_to": "",
  "sort": "popular",
  "page": "1",
  "perPage": "24"
}
```

## Контакты для поддержки

Если проблема сохраняется, предоставьте:
1. Скриншот логов из Console
2. Скриншот запроса из Network → `/catalog/filter`
3. Версию браузера
