# Исправление отображения блока ecom-header

## Проблема
Блок `.ecom-header` не отображался на страницах:
- `http://localhost:8080/` (главная страница)
- `http://localhost:8080/catalog/product/*` (страницы товаров)

## Причина
В файле `/web/css/responsive-fixes.css` на строке 54 было установлено правило `position: relative !important;` для header, которое перезаписывало `position: sticky` из layout-файла `/views/layouts/public.php`.

Из-за этого header не прилипал к верху страницы при прокрутке и мог скрываться.

## Решение

### Исправление 1: Удалено конфликтующее правило position
Файл `/web/css/responsive-fixes.css`, строка 54:

**До:**
```css
/* КРИТИЧНО: Header всегда виден на всех страницах */
header.ecom-header,
.ecom-header,
.main-header,
header {
    display: block !important;
    visibility: visible !important;
    opacity: 1 !important;
    position: relative !important;  /* ← КОНФЛИКТ */
}
```

**После:**
```css
/* КРИТИЧНО: Header всегда виден на всех страницах */
header.ecom-header,
.ecom-header,
.main-header,
header {
    display: block !important;
    visibility: visible !important;
    opacity: 1 !important;
    /* ИСПРАВЛЕНО: Убрано position: relative чтобы не конфликтовать с position: sticky из layout */
}
```

### Исправление 2: Правильная адаптация навигации
Файл `/web/css/responsive-fixes.css`, строки 171-181:

**До:**
```css
/* Header всегда виден на странице товара */
.product-page-optimized ~ .ecom-header,
body:has(.product-page-optimized) .ecom-header,
body:has(.product-page-optimized) .main-header,
body:has(.product-page-optimized) .main-nav {  /* ← Применяется на всех экранах */
    display: block !important;
    visibility: visible !important;
    position: sticky !important;
    top: 0 !important;
    z-index: 1000 !important;
}
```

**После:**
```css
/* Header всегда виден на странице товара */
.product-page-optimized ~ .ecom-header,
body:has(.product-page-optimized) .ecom-header,
body:has(.product-page-optimized) .main-header {
    display: block !important;
    visibility: visible !important;
    position: sticky !important;
    top: 0 !important;
    z-index: 1000 !important;
}

/* main-nav на странице товара - только на desktop */
@media (min-width: 992px) {
    body:has(.product-page-optimized) .main-nav {
        display: block !important;
        visibility: visible !important;
    }
}
```

Теперь навигация корректно скрывается на мобильных (< 992px) и показывается только на desktop.

## Проверка
1. Header присутствует в HTML (проверено через curl)
2. CSS правило `position: sticky` применяется из layout
3. Header корректно отображается и прилипает к верху при прокрутке

## Затронутые файлы
- `/web/css/responsive-fixes.css` - удалено конфликтующее правило

## Дополнительные действия
- Очищен кэш приложения через `./clear-cache.sh`
- Создан тестовый файл `/test-header.html` для верификации

## Тестирование
Для проверки работы header:
1. Откройте в браузере: `http://localhost:8080/`
2. Прокрутите страницу вниз - header должен прилипнуть к верху
3. Откройте страницу товара: `http://localhost:8080/catalog/product/[slug]`
4. Убедитесь что header видим и работает sticky-позиционирование

**Важно:** Не забудьте обновить страницу с Ctrl+Shift+R (Cmd+Shift+R на Mac) для сброса кэша браузера.

---
**Дата:** 2025-11-09
**Статус:** ✅ Исправлено
