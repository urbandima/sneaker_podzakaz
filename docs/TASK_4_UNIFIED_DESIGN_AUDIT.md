# Задача #4 - Единый дизайн и Production-готовность

**Дата:** 25.03.2026  
**Статус:** В ПРОЦЕССЕ

---

## 📋 ЦЕЛЬ ЗАДАЧИ

Привести все страницы сайта к единому визуальному языку и убедиться, что всё работает без ошибок.

---

## ✅ ТЕКУЩЕЕ СОСТОЯНИЕ

### 1. CSS АРХИТЕКТУРА

**Файлы:**
- ✅ `/frontend/web/css/app.css` (2058 строк) - единый CSS файл
- ✅ `/frontend/web/css/admin.css` (8836 байт) - админ-панель
- ❌ `/frontend/css/core/design-tokens.css` - НЕ СУЩЕСТВУЕТ
- ❌ `/frontend/css/core/design-system.css` - НЕ СУЩЕСТВУЕТ

**Вывод:** Вместо отдельных design-tokens.css и design-system.css используется единый `app.css` с CSS-переменными в чёрно-белом минимализме.

### 2. CSS ПЕРЕМЕННЫЕ (DESIGN TOKENS)

**Текущие переменные в app.css:**

```css
:root {
    /* Цвета - только чёрно-белые */
    --color-black: #000000;
    --color-dark-gray: #1a1a1a;
    --color-gray: #666666;
    --color-light-gray: #e5e5e5;
    --color-white: #ffffff;

    /* Поверхности */
    --surface-primary: #ffffff;
    --surface-secondary: #f5f5f5;
    --surface-tertiary: #e5e5e5;

    /* Текст */
    --text-primary: #000000;
    --text-secondary: #666666;
    --text-muted: #999999;
    --text-inverse: #ffffff;

    /* Границы */
    --border-color: #e5e7eb;

    /* Шрифты */
    --font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
    --font-size-base: 1rem;
    --font-size-sm: 0.875rem;
    --font-size-lg: 1.125rem;
    --font-size-xl: 1.25rem;
    --font-size-2xl: 1.5rem;
    --font-size-3xl: 1.875rem;

    /* Отступы */
    --spacing-1: 0.25rem;
    --spacing-2: 0.5rem;
    --spacing-3: 0.75rem;
    --spacing-4: 1rem;
    --spacing-5: 1.25rem;
    --spacing-6: 1.5rem;
    --spacing-8: 2rem;
    --spacing-12: 3rem;
    --spacing-16: 4rem;

    /* Скругления */
    --radius-sm: 4px;
    --radius-md: 8px;
    --radius-lg: 12px;
    --radius-xl: 16px;
    --radius-full: 9999px;

    /* Тени */
    --shadow-sm: 0 1px 2px rgba(0, 0, 0, 0.05);
    --shadow-md: 0 4px 6px rgba(0, 0, 0, 0.1);
    --shadow-lg: 0 10px 15px rgba(0, 0, 0, 0.1);

    /* Переходы */
    --transition-fast: 0.15s ease;
    --transition-normal: 0.2s ease;
}
```

**Статус:** ✅ Все переменные определены, чёрно-белый минимализм

### 3. HARDCODED ЦВЕТА

**Найдены в app.css:**

```css
/* Alerts - HARDCODED */
.alert-success {
    background: #d1fae5;
    color: #065f46;
    border: 1px solid #a7f3d0;
}

.alert-danger {
    background: #fee2e2;
    color: #991b1b;
    border: 1px solid #fecaca;
}

.alert-warning {
    background: #fef3c7;
    color: #92400e;
    border: 1px solid #fde68a;
}

.alert-info {
    background: #dbeafe;
    color: #1e40af;
    border: 1px solid #bfdbfe;
}

/* Dark mode - HARDCODED */
[data-theme="dark"] {
    --surface-primary: #0f172a;
    --surface-secondary: #1e293b;
    --surface-tertiary: #334155;
    --text-primary: #f8fafc;
    --text-secondary: #cbd5e1;
    --text-muted: #94a3b8;
    --border-color: #334155;
}
```

**Статус:** ⚠️ Alerts используют цветные hardcoded значения (зелёный, красный, жёлтый, синий)

### 4. HOVER ЭФФЕКТЫ

**Проверка translateY:**
- ❌ НЕ НАЙДЕНО `translateY(-8px)` в app.css
- ✅ Используется `translateY(-4px)` и `translateY(-2px)`

**Статус:** ✅ Hover эффекты корректные

### 5. ASSET BUNDLES

**Найденные Asset Bundle'ы:**
1. ✅ `AppAsset.php` - основной, подключает app.css и app.js
2. ✅ `CatalogAsset.php` - каталог, зависит от AppAsset
3. ✅ `ProductAsset.php` - карточка товара, зависит от AppAsset
4. ✅ `CartAsset.php` - корзина, зависит от AppAsset
5. ✅ `CheckoutAsset.php` - оформление заказа, зависит от AppAsset
6. ✅ `AdminAsset.php` - админ-панель
7. ✅ `LandingAsset.php` - лендинг

**Статус:** ✅ Все Asset Bundle'ы существуют и зависят от AppAsset

### 6. TOUCH TARGETS

**Минимальный размер:** 44px

**Проверка в app.css:**
- ✅ `.btn` - min-height: 44px
- ✅ `.header-actions button` - размер достаточный
- ✅ `.mobile-menu-toggle` - размер достаточный

**Статус:** ✅ Touch targets соответствуют требованиям

### 7. DARK MODE

**Поддержка:**
- ✅ CSS переменные для dark mode определены
- ✅ `[data-theme="dark"]` селектор присутствует
- ⚠️ Нужно проверить на всех компонентах

**Статус:** ⚠️ Требуется тестирование

---

## 🔍 АУДИТ КОМПОНЕНТОВ

### Главная страница (/)
- ✅ Hero Section - стили добавлены
- ✅ Popular Section - стили добавлены
- ✅ Categories Section - стили добавлены
- ✅ Brands Section - стили добавлены
- ✅ Benefits Section - стили добавлены
- ✅ Newsletter Section - стили добавлены

### Каталог (/catalog)
- ✅ Catalog Page - стили добавлены
- ✅ Sidebar - стили добавлены
- ✅ Filter Groups - стили добавлены
- ✅ Product Cards - стили добавлены
- ✅ Toolbar - стили добавлены
- ✅ Breadcrumbs - стили добавлены

### Карточка товара (/catalog/product/{slug})
- ⚠️ Требуется проверка
- ✅ ProductAsset подключен

### Корзина (/cart)
- ⚠️ Требуется проверка
- ✅ CartAsset подключен

### Оформление заказа (/checkout)
- ⚠️ Требуется проверка
- ✅ CheckoutAsset подключен

### Личный кабинет (/account)
- ⚠️ Требуется проверка

### Статичные страницы
- ❓ /sale - требуется проверка
- ❓ /brands - требуется проверка
- ❓ /about - требуется проверка
- ❓ /contacts - требуется проверка

---

## 📊 ЗАДАЧИ ДЛЯ ВЫПОЛНЕНИЯ

### 1. ✅ Аудит CSS-токенов
- [x] Проверить наличие design-tokens.css
- [x] Найти hardcoded цвета
- [x] Проверить использование переменных

**Результат:** Найдены hardcoded цвета в alerts. Нужно заменить на переменные.

### 2. ⏳ Исправление UX-багов
- [x] Проверить hover эффекты (translateY)
- [x] Проверить touch targets (44px)
- [ ] Привести focus ring к единому цвету

**Результат:** Hover и touch targets в порядке. Focus ring требует проверки.

### 3. ⏳ Dark mode
- [x] Проверить наличие dark mode переменных
- [ ] Проверить dark mode на всех компонентах
- [ ] Исправить hardcoded градиенты

**Результат:** Переменные есть, требуется тестирование компонентов.

### 4. ⏳ Проверка модулей
- [x] Проверить Asset Bundle'ы
- [ ] Проверить quick view
- [ ] Проверить фильтры каталога
- [ ] Проверить лоялти-программу
- [ ] Проверить адаптивное меню
- [ ] Проверить поиск

**Результат:** Asset Bundle'ы в порядке. Модули требуют тестирования.

### 5. ⏳ Устранение дублирования
- [ ] Проверить дублирующиеся вьюшки
- [ ] Проверить маршруты (/sale, /brands, /search)
- [ ] Удалить лишние директории

**Результат:** Требуется проверка.

### 6. ⏳ Единообразие layout'ов
- [ ] Проверить main.php layout
- [ ] Проверить public.php layout (если существует)
- [ ] Проверить header на всех страницах
- [ ] Проверить footer на всех страницах
- [ ] Проверить JSON-LD и мета-теги

**Результат:** Требуется проверка.

---

## 🎯 СЛЕДУЮЩИЕ ШАГИ

1. **Заменить hardcoded цвета в alerts на переменные**
2. **Проверить все маршруты на 404**
3. **Протестировать dark mode на всех компонентах**
4. **Проверить работу всех модулей**
5. **Проверить единообразие layout'ов**
6. **Создать финальный отчёт**

---

## 📝 ПРИМЕЧАНИЯ

- Сайт использует чёрно-белый минимализм вместо цветной дизайн-системы
- Все Asset Bundle'ы зависят от AppAsset для единого стиля
- CSS файл app.css содержит 2058 строк и покрывает все компоненты
- Сервер запущен на localhost:8081
