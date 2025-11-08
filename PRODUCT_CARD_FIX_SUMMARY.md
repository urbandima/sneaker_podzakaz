# Исправление верстки карточки товара

**Дата:** 06.11.2024  
**Страница:** http://localhost:8080/catalog/product/nike-zoom-fly-6

## Проблемы

1. ❌ Слишком большой размер фото и галереи
2. ❌ Галерея смещена вправо, не отцентрирована
3. ❌ Ширина галереи не совпадает с header

## Решения

### 1. Уменьшен размер фото и галереи

**Файл:** `web/css/product.css`

#### Mobile (до 768px)
- `min-height: 340px` → `280px`
- `max-height: 450px` → `320px`
- `padding: 2rem` → `1.5rem`

#### Desktop (1024px+)
- `min-height: 500px` → `400px`
- `max-height: 550px` → `450px`
- `padding: 3rem` → `2rem`

#### Очень маленькие экраны (<380px)
- `min-height: 280px` → `240px`
- Добавлено: `max-height: 280px`

### 2. Отцентрована галерея и выровнена по ширине header

**Файлы:** `web/css/product.css`, `web/css/product-adaptive.css`, `web/css/product-layout-fixes.css`

#### Mobile (до 768px)
```css
.product-container {
    width: 100%;
    max-width: 100%;
    padding: 0;
}
```

#### Tablet (768px+)
```css
.product-container {
    width: 90%;
    max-width: 1400px;
    margin: 0 auto;
}
```

#### Desktop (1024px+)
```css
.product-container {
    width: 80%;           /* Как у header */
    max-width: 1920px;    /* Как у header */
    margin: 0 auto;
}
```

### 3. Breadcrumbs адаптированы
- **Mobile:** `padding: 1rem` (с боковыми отступами)
- **Desktop:** `padding: 1rem 0` (только вертикальные)

## Проблема с кешированием

### Причина
Yii2 кеширует CSS файлы через Asset Manager в:
- `web/assets/*` - скомпилированные assets
- `runtime/cache/*` - кеш приложения

### Решение

#### Автоматическая очистка (создан скрипт)
```bash
./clear-cache.sh
```

#### Ручная очистка
```bash
rm -rf web/assets/*
touch web/assets/.gitkeep
rm -rf runtime/cache/*
```

#### Жесткая перезагрузка в браузере
- **Mac:** `Cmd + Shift + R`
- **Windows/Linux:** `Ctrl + Shift + R`

## Измененные файлы

1. ✅ `web/css/product.css` - основные стили, размеры, центрирование
2. ✅ `web/css/product-adaptive.css` - удалены дубли, оптимизация
3. ✅ `web/css/product-layout-fixes.css` - breadcrumbs адаптация
4. ✅ `views/catalog/product.php` - комментарий о версионировании
5. ✅ `clear-cache.sh` - скрипт очистки кеша (новый)
6. ✅ `CACHE_CLEAR_GUIDE.md` - руководство по кешу (новый)

## Проверка результата

1. Очистите кеш: `./clear-cache.sh`
2. Обновите страницу: `Cmd + Shift + R`
3. Проверьте в DevTools:
   - CSS файлы загружаются с timestamp
   - Галерея отцентрирована
   - Размер фото уменьшен

## Технические детали

### Версионирование CSS
В `config/web.php` включено:
```php
'assetManager' => [
    'appendTimestamp' => true,  // Автоматическое добавление ?v=timestamp
    'linkAssets' => true,
],
```

### Ширина контейнеров (соответствие header)
- Mobile: 100%
- Tablet: 90%, max 1400px
- Desktop: 80%, max 1920px

## Следующие шаги

1. ✅ Очистить кеш
2. ✅ Проверить на разных разрешениях
3. ⏳ Протестировать на реальных устройствах
4. ⏳ Проверить другие страницы товаров

## Команды для быстрого доступа

```bash
# Очистка кеша
./clear-cache.sh

# Проверка изменений в CSS
grep -n "min-height\|max-height" web/css/product.css | grep swipe

# Проверка ширины контейнера
grep -n "width: 80%" web/css/product.css
```
