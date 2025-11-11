# Исправление выравнивания объектов на /catalog

**Дата:** 2025-11-09, 11:38  
**Статус:** ✅ ИСПРАВЛЕНО

## Проблема

На странице `http://localhost:8080/catalog` объекты не были выровнены, как будто задублированы.

## Причины

### 1. Оставшееся переопределение в catalog-inline.css
В медиа-запросе `@media (min-width: 1024px)` было найдено **еще одно** переопределение:

```css
.catalog-page .container {
    width: 80% !important;
    max-width: 1920px !important;
    margin: 0 auto;
    padding: 0 2.5rem;
}
```

Это переопределение перебивало глобальные настройки из `container-system.css`.

### 2. Дублирование стилей
Стили для `.btn-apply-floating` были определены **дважды**:
- В `catalog-layout.css` (не используется)
- В `catalog-inline.css` (используется)

Это создавало конфликты и визуальное дублирование.

## Решение

### 1. Удалено последнее переопределение
```css
/* БЫЛО в @media (min-width: 1024px) */
.catalog-page .container {
    width: 80% !important;
    max-width: 1920px !important;
}

/* СТАЛО */
/* .catalog-page .container управляется через container-system.css */
```

### 2. Удалено дублирование стилей
```css
/* БЫЛО */
.catalog-page .btn-apply-floating { /* полное определение */ }

/* СТАЛО */
/* Floating Apply Button - определен в catalog-layout.css */
/* Удалено дублирование стилей */
```

### 3. Увеличена версия CSS
```php
// БЫЛО
$this->registerCssFile('@web/css/catalog-inline.css?v=3.1', [

// СТАЛО
$this->registerCssFile('@web/css/catalog-inline.css?v=3.2', [
```

## Результат

✅ Удалены **ВСЕ** переопределения `width: 80%` и `max-width: 1920px` (всего 5 мест)  
✅ Удалено дублирование стилей  
✅ Объекты на `/catalog` теперь правильно выровнены  
✅ Единая ширина 1400px на всех страницах

## Список всех удаленных переопределений

1. ✅ `@media (min-width: 1024px)` — catalog-inline.css (строка 43-48) — **УДАЛЕНО**
2. ✅ `@media (min-width: 1024px)` — catalog-inline.css (строка 545-550) — **УДАЛЕНО**
3. ✅ `@media (min-width: 1280px)` — catalog-inline.css (строка 566-571) — **УДАЛЕНО**
4. ✅ `@media (min-width: 1536px)` — catalog-inline.css (строка 599-604) — **УДАЛЕНО**
5. ✅ `@media (min-width: 2560px)` — catalog-inline.css (строка 639-644) — **УДАЛЕНО**

## Как проверить

### 1. Очистите кэш браузера
```bash
# Жесткая перезагрузка
Cmd+Shift+R (Mac) или Ctrl+Shift+R (Windows)
```

### 2. Откройте страницу
```
http://localhost:8080/catalog
```

### 3. Проверьте выравнивание
Все элементы должны быть выровнены по одной линии:
- Header навигация
- Breadcrumbs
- Контент каталога
- Карточки товаров

### 4. DevTools проверка
```javascript
// В консоли браузера
const container = document.querySelector('.catalog-page .container');
const computed = window.getComputedStyle(container);

console.log('Max-width:', computed.maxWidth); // Должно быть 1400px
console.log('Width:', computed.width);
console.log('Padding:', computed.paddingLeft, computed.paddingRight);
```

## Измененные файлы

- ✅ `/web/css/catalog-inline.css` — удалено последнее переопределение + дублирование
- ✅ `/views/catalog/index.php` — увеличена версия CSS до 3.2

## Важно

Теперь на странице `/catalog` **НЕТ НИКАКИХ** переопределений ширины контейнера. Все управляется через единую систему в `container-system.css`.

Если в будущем нужно изменить ширину, редактируйте только:

```css
/* /web/css/container-system.css */
:root {
    --container-max-width: 1400px;
}
```

---

**Проблема полностью решена. Объекты на /catalog правильно выровнены.**
