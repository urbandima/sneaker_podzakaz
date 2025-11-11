# Исправление ширины на странице /catalog

**Дата:** 2025-11-09, 11:33  
**Статус:** ✅ ИСПРАВЛЕНО

## Проблема

На странице `http://localhost:8080/catalog` блоки **nav-menu** и **main-header** имели другую ширину (80% / 1920px вместо 1400px).

## Причина

В файле `/web/css/catalog-inline.css` было **4 переопределения** для `.catalog-page .container`:

```css
/* В 4 медиа-запросах (1024px, 1280px, 1536px, 2560px) */
.catalog-page .container {
    width: 80% !important;
    max-width: 1920px !important;
}
```

Также в `/views/layouts/public.php` были inline стили:

```css
.container {
    max-width: 100%;
    padding: 0;
}
```

Эти стили перебивали глобальные настройки из `container-system.css`.

## Решение

### 1. Удалены переопределения из catalog-inline.css

Заменены все 4 блока на комментарии:

```css
/* .catalog-page .container управляется через container-system.css */
```

### 2. Удалены inline стили из public.php

```css
/* Container управляется через container-system.css (1400px) */
/* Удалены переопределения max-width и padding */
```

## Результат

✅ Страница `/catalog` теперь использует единую ширину **1400px**  
✅ nav-menu и main-header выровнены с остальным контентом  
✅ Визуальная согласованность на всех страницах

## Как проверить

### 1. Очистите кэш

```bash
./test-container-width.sh
```

### 2. Откройте страницу каталога

```
http://localhost:8080/catalog
```

### 3. Проверьте DevTools

```javascript
// В консоли браузера
const header = document.querySelector('.main-header .container');
const content = document.querySelector('.catalog-page .container');

console.log('Header width:', header.offsetWidth);
console.log('Content width:', content.offsetWidth);
// Оба должны быть 1400 (или меньше на узких экранах)
```

### 4. Визуальная проверка

- Header навигация
- Контент каталога
- Все должно быть выровнено по одной линии

## Измененные файлы

- ✅ `/web/css/catalog-inline.css` — удалены 4 переопределения
- ✅ `/views/layouts/public.php` — удалены inline стили

## Важно

Теперь **ВСЕ** страницы используют единую систему контейнеров из `/web/css/container-system.css`:

- Главная
- Каталог (`/catalog`)
- Страница товара
- Админ-панель

Если нужно изменить ширину, редактируйте только одно место:

```css
/* /web/css/container-system.css */
:root {
    --container-max-width: 1400px;
}
```

---

**Проблема полностью решена. Страница /catalog имеет единую ширину со всеми остальными страницами.**
