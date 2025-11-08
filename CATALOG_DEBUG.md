# 🔍 ДИАГНОСТИКА ПРОБЛЕМЫ С КАТАЛОГОМ

## Статус проверки

### ✅ ЧТО РАБОТАЕТ:
1. **База данных**: 46 товаров загружаются корректно
2. **Контроллер**: `CatalogController::actionIndex()` возвращает 24 товара
3. **View рендеринг**: HTML генерируется с карточками товаров  
4. **Связи**: `$product->brand` работает после добавления `brand_id` в SELECT
5. **HTML структура**: `<div id="products">` содержит товары

### ❓ ЧТО ПРОВЕРИТЬ:

1. **Откройте каталог в браузере**: http://localhost:8080/catalog/
2. **Откройте DevTools** (F12)
3. **Проверьте консоль** на наличие JavaScript ошибок
4. **Проверьте Elements** - есть ли `<div class="product product-card">` внутри `#products`
5. **Проверьте CSS** - нет ли `display: none` или `visibility: hidden` на товарах

## Тестовые страницы

### 1. Простая HTML страница (без PHP/JS):
```
http://localhost:8080/test-catalog-visual.html
```
- Если товары ВИДНЫ → проблема в JS/PHP
- Если товары НЕ ВИДНЫ → проблема в CSS

### 2. PHP тест (бэкенд):
```bash
php /Users/user/CascadeProjects/splitwise/debug-catalog-view.php
```

## Возможные причины проблемы

### 1. JavaScript очищает контейнер
- Проверить консоль браузера
- Отключить `/web/js/catalog.js` временно

### 2. CSS скрывает товары
- Проверить computed styles в DevTools
- Проверить `.products { display: grid }` применяется ли

### 3. Кэш браузера
```bash
# Очистите кэш браузера:
Cmd + Shift + R (macOS)
Ctrl + Shift + R (Windows)
```

### 4. Конфликт JavaScript
- Проверить порядок загрузки скриптов
- Проверить ошибки в консоли

## Быстрая проверка

Откройте консоль браузера (F12) и выполните:

```javascript
// Проверка 1: Контейнер существует?
console.log('Container:', document.getElementById('products'));

// Проверка 2: Есть ли товары внутри?
console.log('Products count:', document.querySelectorAll('#products .product-card').length);

// Проверка 3: Проверка CSS
const container = document.getElementById('products');
if (container) {
    const styles = window.getComputedStyle(container);
    console.log('Display:', styles.display);
    console.log('Visibility:', styles.visibility);
    console.log('Opacity:', styles.opacity);
}

// Проверка 4: Первая карточка товара
const firstProduct = document.querySelector('.product-card');
if (firstProduct) {
    console.log('First product:', firstProduct);
    const productStyles = window.getComputedStyle(firstProduct);
    console.log('Product display:', productStyles.display);
    console.log('Product visibility:', productStyles.visibility);
}
```

## Следующие шаги

1. Откройте http://localhost:8080/catalog/ в браузере
2. Откройте DevTools (F12)
3. Скопируйте и выполните код выше в консоли
4. Скриншот результата или опишите, что видите
