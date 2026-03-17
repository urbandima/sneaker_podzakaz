# Дизайн-система: Гайдлайны и документация

**Версия:** 3.0  
**Обновлено:** 17.03.2026  
**Статус:** Production-ready

---

## 🎨 Общая философия

Дизайн-система построена на принципах:
- **Согласованность:** Единые токены для всех компонентов
- **Доступность:** WCAG AA compliance по умолчанию
- **Mobile-first:** Touch-оптимизация и адаптивность
- **Производительность:** Эффективная загрузка и рендеринг

---

## 🏗️ Архитектура

### 📁 Структура файлов
```
frontend/css/
├── core/
│   └── design-tokens.css     # 🎯 Канонические токены (ЕДИНЫЙ ИСТОЧНИК ПРАВДЫ)
├── features/
│   ├── accessibility.css    # ♿ WCAG AA улучшения
│   ├── micro-interactions.css # ✨ Анимации и transition
│   ├── dark-mode.css        # 🌙 Тёмная тема
│   └── mobile-menu.css      # 📱 Mobile UX
└── dist/                    # 📦 Скомпилированные бандлы
```

### 🔄 Иерархия токенов
1. **Core tokens:** Базовые значения (цвета, размеры, шрифты)
2. **Semantic tokens:** Контекстные значения (surface-primary, text-primary)
3. **Component tokens:** Специфичные для компонентов (btn-height-md)

---

## 🎨 Цветовая система

### 🌈 Primary Palette
```css
--color-primary: #1e293b;        /* Основной брендовый */
--color-primary-hover: #334155;  /* Hover состояние */
--color-primary-light: rgba(30, 41, 59, 0.1); /* Фон/тени */
```

### 💎 Accent Colors
```css
--color-accent: #3b82f6;          /* Синий акцент */
--color-success: #10b981;         /* Успех */
--color-warning: #f59e0b;         /* Предупреждение */
--color-error: #ef4444;           /* Ошибка */
--color-info: #06b6d4;            /* Информация */
```

### 🌙 Dark Mode Colors
```css
/* Переопределяются в dark-mode.css */
[data-theme="dark"] {
    --surface-primary: #0f172a;
    --text-primary: #f8fafc;
    --border-color: #334155;
}
```

---

## 📝 Типографика

### 🎯 Шрифты
```css
--font-family: 'Inter', system-ui, sans-serif;
--font-family-mono: 'JetBrains Mono', monospace;
```

### 📏 Размеры и веса
```css
/* Базовые размеры */
--font-size-xs: 0.75rem;     /* 12px */
--font-size-sm: 0.875rem;    /* 14px */
--font-size-base: 1rem;      /* 16px - БАЗОВЫЙ */
--font-size-lg: 1.125rem;    /* 18px */
--font-size-xl: 1.25rem;     /* 20px */

/* Веса */
--font-weight-normal: 400;
--font-weight-medium: 500;
--font-weight-semibold: 600;
--font-weight-bold: 700;
```

### 📱 Адаптивные заголовки
```css
--text-h1: clamp(2rem, 5vw, 3rem);      /* 32px-48px */
--text-h2: clamp(1.5rem, 4vw, 2.5rem); /* 24px-40px */
--text-h3: clamp(1.25rem, 3vw, 2rem);  /* 20px-32px */
```

### 💰 Цены
```css
.price {
    font-variant-numeric: tabular-nums lining-nums;
    font-size: var(--text-price-medium);
    font-weight: var(--font-weight-bold);
}
```

---

## 📏 Пространственная система

### 📐 Отступы
```css
--spacing-1: 0.25rem;   /* 4px */
--spacing-2: 0.5rem;    /* 8px */
--spacing-3: 0.75rem;   /* 12px */
--spacing-4: 1rem;      /* 16px - БАЗОВЫЙ */
--spacing-5: 1.25rem;   /* 20px */
--spacing-6: 1.5rem;    /* 24px */
--spacing-8: 2rem;      /* 32px */
```

### 🔲 Скругления
```css
--radius-sm: 6px;
--radius-md: 8px;
--radius-lg: 12px;
--radius-xl: 16px;
--radius-full: 9999px;
```

---

## 🎭 Компоненты

### 🔘 Кнопки
```css
.btn-primary {
    background: var(--color-primary);
    color: var(--text-inverse);
    min-height: 44px;           /* WCAG touch target */
    touch-action: manipulation; /* Убирает 300ms задержку */
    transition: background var(--transition-normal), 
                transform var(--transition-normal), 
                box-shadow var(--transition-normal);
}
```

#### Состояния кнопки "В корзину"
```css
.btn-add-to-cart.is-loading { /* Загрузка */ }
.btn-add-to-cart.is-success { /* ✓ Добавлено */ }
.btn-add-to-cart.is-disabled { /* Недоступно */ }
```

### 🃏 Карточки
```css
.card {
    background: var(--surface-primary);
    border: 1px solid var(--border-color);
    border-radius: var(--card-radius);
    padding: var(--card-padding);
    box-shadow: var(--shadow-sm);
}
```

### 📝 Инпуты
```css
.input {
    height: var(--input-height-md);
    border: 1px solid var(--border-color);
    font-size: var(--font-size-sm);
    transition: border-color var(--transition-fast), 
                box-shadow var(--transition-fast);
}

.input:focus {
    border-color: var(--color-accent);
    box-shadow: 0 0 0 3px var(--color-accent-light);
}
```

---

## ♿ Доступность

### 🎯 Focus States
```css
*:focus-visible {
    outline: 2px solid var(--color-accent);
    outline-offset: 3px;
}

/* Для тёмных кнопок */
.btn-primary:focus-visible {
    outline: 2px solid var(--text-inverse);
    box-shadow: 0 0 0 5px rgba(255, 255, 255, 0.3);
}
```

### 🦯 Skip Link
```html
<a href="#main-content" class="skip-link">Перейти к основному содержимому</a>
```

### 📢 ARIA Labels
```html
<button aria-label="Поиск товаров" aria-haspopup="dialog">
<span class="cart-counter" role="status" aria-live="polite">0</span>
<button aria-expanded="false" aria-controls="mobileMenu">
```

---

## 📱 Mobile UX

### 👆 Touch Targets
```css
/* ВСЕ интерактивные элементы */
.btn, .filter-chip, .mobile-nav-item > a {
    min-height: 44px;
    min-width: 44px;
    touch-action: manipulation;
}
```

### 🍔 Mobile Menu
```css
.mobile-menu {
    transform: translateX(-100%);
    transition: transform 0.3s var(--ease-in-out);
}

.mobile-menu.active {
    transform: translateX(0);
}
```

---

## 🌙 Тёмная тема

### 🔀 Три состояния
```css
/* Автоматически (системные настройки) */
@media (prefers-color-scheme: dark) { ... }

/* Ручное управление */
[data-theme="light"] { ... }
[data-theme="dark"] { ... }
[data-theme="auto"] { ... } /* Следует системным */
```

### 🎨 Переключатель темы
```html
<button class="dark-mode-toggle" aria-label="Переключить тему">
    <i class="icon-sun">☀️</i>
    <i class="icon-moon">🌙</i>
    <i class="icon-auto">🔄</i>
</button>
```

---

## ✨ Анимации

### ⚡ Transition правила
```css
/* ✅ ПРАВИЛЬНО */
transition: background var(--transition-normal), 
            transform var(--transition-normal);

/* ❌ НЕПРАВИЛЬНО */
transition: all 0.3s ease;
```

### 🎭 Reduced Motion
```css
@media (prefers-reduced-motion: reduce) {
    *, *::before, *::after {
        animation-duration: 0.01ms !important;
        transition-duration: 0.01ms !important;
    }
}
```

---

## 🚀 Performance

### 📸 WebP Images
```bash
# Автоматическая конвертация в Gulp
gulp webp
```

### ⚡ Critical Resources
```html
<link rel="preload" href="/css/core.css" as="style">
<link rel="preload" href="/js/cookies-consent.js" as="script">
```

### 🔤 Font Optimization
```css
@font-face {
    font-family: 'Inter';
    src: url('inter.woff2') format('woff2');
    font-display: swap; /* Не блокирует рендеринг */
}
```

---

## 🛠️ Использование

### 📦 Подключение
```html
<!-- Critical CSS (инлайн) -->
<link rel="stylesheet" href="/css/dist/critical.css">

<!-- Основные стили -->
<link rel="stylesheet" href="/css/dist/public.css">

<!-- Каталог (только на нужных страницах) -->
<link rel="stylesheet" href="/css/dist/catalog.css">
```

### 🎨 Кастомизация
```css
/* Расширение токенов */
:root {
    --brand-primary: #your-brand-color;
    --custom-spacing: 2.5rem;
}

/* Переопределение компонентов */
.btn-primary {
    background: var(--brand-primary);
}
```

### 🧪 Тестирование
```bash
# Сборка для продакшена
gulp production

# Оптимизация изображений
gulp image-optimization

# Разработка
gulp watch
```

---

## 📋 Чек-лист для разработки

### ✅ Перед коммитом
- [ ] Использую переменные из `design-tokens.css`
- [ ] Touch targets ≥ 44px для мобильных
- [ ] `transition: all` заменен на конкретные свойства
- [ ] ARIA labels добавлены для иконок
- [ ] Reduced motion учтён в анимациях
- [ ] Dark mode работает корректно

### 🧪 Тестирование
- [ ] Chrome/Firefox/Safari compatibility
- [ ] Mobile Safari/Chrome touch-тест
- [ ] Screen reader navigation
- [ ] Keyboard-only navigation
- [ ] Dark/light theme switching
- [ ] Performance audit (Lighthouse)

---

## 🔮 Будущие улучшения

### 🎯 V4.0 Roadmap
- [ ] CSS-in-JS миграция (опционально)
- [ ] Design tokens в JSON формате
- [ ] Автоматическая документация
- [ ] Component library (Storybook)
- [ ] Figma integration

### 🚀 Performance V2
- [ ] Critical CSS автоматизация
- [ ] Image lazy loading
- [ ] Service Worker для кеширования
- [ ] Bundle size optimization

---

## 📞 Поддержка

**Мейнтейнер:** Frontend Team  
**Документация:** Обновляется при каждом релизе  
**Issues:** Создавать в проектном трекере  
**Обсуждения:** Frontend Slack канал

---

**Версия:** 3.0  
**Следующее обновление:** По необходимости  
**Совместимость:** Все современные браузеры (ES2020+)
