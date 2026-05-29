# CMP-302 — Мобильная UX-оптимизация карточки товара

**Автор:** UXDesigner  
**Дата:** 2026-05-29  
**Статус:** Готово к имплементации CTO

---

## Контекст

Мобильный трафик: 60–70% для sneaker e-commerce. Целевой диапазон: 320–768px.  
Затронутые файлы:
- `frontend/views/catalog/product.php` — шаблон карточки товара
- `frontend/web/css/pages/product.css` — стили страницы товара

---

## 1. Touch-target спецификация

Требование: **WCAG 2.5.5 / Apple HIG — минимум 44×44 CSS px**.

| Элемент | Было | Стало | Файл/класс |
|---|---|---|---|
| `.size-btn` | 36px height | **44px min-height** | product.css |
| `.thumbnail-item` | ~48px | **64px (480px: 56px)** | product.css |
| `.tab-btn` | ~40px | **44px min-height** | product.css |
| `.action-btn` (❤️ share) | ~40px | **44×44px** | product.css |
| `.sticky-add-cart` (sticky bar) | уже ≥44px | ≥44px ✓ | product.css |
| `.sticky-size-btn` | уже ≥44px | ≥44px ✓ | product.css |

---

## 2. Фотогалерея — swipe и thumbnail-strip

### 2.1 Touch swipe (JS — product.php)

Inline `<script>` в конце `product.php` содержит IIFE-обработчик:

```
touchstart → записать startX, startY
touchend   → 
  ├── если |dx| < 10 && |dy| < 10 и 2-й тап < 300ms → zoom toggle (scale 2×→1×)
  ├── если |dx| < 40 или |dx| < |dy| → игнорировать (вертикальный скролл)
  ├── dx > 0 → swipe left → следующее фото
  └── dx < 0 → swipe right → предыдущее фото
```

- `passive: true` на всех touch-листенерах — не блокирует scroll/LCP
- Отображает пагинационные точки (`.gallery-dots`) через `insertBefore`

### 2.2 Pagination dots

Инжектируются JS-кодом в `product.php` под `.main-image-container`, если `productGalleryImages.length > 1`.

```
.gallery-dots  — flex, justify-content: center, gap: 6px
.gallery-dot   — 6px круг, серый; .active → чёрный, width: 18px (pill)
```

### 2.3 Thumbnail strip (CSS — product.css, @media ≤768px)

| Свойство | Значение |
|---|---|
| Позиция | Ниже главного изображения (`order: 2` на `.gallery-thumbnails`, `order: 1` на `.main-image-container`) |
| Направление | Горизонтальный scroll (`display: flex; overflow-x: auto`) |
| Scroll-snap | `scroll-snap-type: x mandatory` на контейнере, `scroll-snap-align: start` на элементах |
| Размер thumb | 64px (480px: 56px) |
| Скроллбар | скрыт (`scrollbar-width: none`) |

### 2.4 Double-tap zoom

JS в product.php. При двойном тапе (интервал < 300ms, движение < 10px):
- `img.style.transform = 'scale(2)'` / `'scale(1)'`
- `img.style.transition = 'transform 0.25s ease'`
- CSS `transform-origin: center center` в product.css

---

## 3. Выбор размера — горизонтальный scroll

### До (768px)
```css
.size-grid { grid-template-columns: repeat(3, 1fr); }
.size-btn  { min-height: 36px; }
```

### После (768px, CMP-302)
```css
.size-grid {
    display: flex;
    flex-wrap: nowrap;
    overflow-x: auto;
    scroll-snap-type: x mandatory;
    scrollbar-width: none;
    -webkit-overflow-scrolling: touch;
}
.size-btn {
    flex: 0 0 auto;
    min-width: 52px;    /* 480px: 48px */
    min-height: 44px;   /* ≥WCAG 2.5.5 */
    scroll-snap-align: start;
}
```

Видимые размеры при ширине 390px: ~6 кнопок в видимой зоне, остальные скроллируются.

---

## 4. CTA — sticky кнопка

Sticky bar уже реализован ранее (CMP-23). Поведение:
- Появляется через `IntersectionObserver` когда основная кнопка "Добавить в корзину" уходит за верх экрана
- На 768px: `padding: space-2 space-3`, flex layout
- Кнопка "В корзину" занимает основное пространство (`.sticky-add-cart`)

---

## 5. Spacing — ключевые значения на мобиле

| Зона | Значение | CSS-токен |
|---|---|---|
| Внутренний padding `.product-content` | 16px | `--space-4` |
| Gap галереи / секций | 24px | `--space-6` |
| Между size buttons | 8px | `--space-2` |
| Thumbnail gap | 8px | `--space-2` |
| Sticky bar padding | 8px 12px | `--space-2 --space-3` |
| Padding tab кнопок | 12px 16px | `--space-3 --space-4` |

---

## 6. LCP — изображения не деградируют

- Главное изображение (`#mainImage`): `loading="eager" fetchpriority="high"` ✓
- Preload LCP via `$this->params['lcpImageUrl']` в layout ✓
- Touch-листенеры: `{ passive: true }` — не блокируют парсинг ✓
- Pagination dots инжектируются JS после DOMContentLoaded — вне критического пути ✓
- Thumbnails: `loading="lazy"` ✓

---

## 7. Ограничения и что остаётся CTO

| Задача | Статус | Комментарий |
|---|---|---|
| CSS touch-targets (size-btn, tabs, thumbnails) | **Готово** | product.css |
| Horizontal scroll size grid | **Готово** | product.css |
| Thumbnail strip below gallery | **Готово** | product.css (order: 1/2) |
| Touch swipe JS (IOS Safari + Chrome) | **Готово** | product.php inline script |
| Double-tap zoom | **Готово** | product.php inline script |
| Pagination dots (JS inject) | **Готово** | product.php inline script |
| Accordion вместо Tabs | **Спецификация ниже** | требует изменений HTML в product.php |

### Accordion спецификация (для CTO)

Замена tabs (`.product-tabs-section`) на accordion на мобиле:

```html
<!-- Каждая секция: -->
<details class="product-accordion">
    <summary class="product-accordion__trigger">
        Описание
        <i class="bi bi-chevron-down product-accordion__icon"></i>
    </summary>
    <div class="product-accordion__body">
        <!-- текущий .tab-content-inner -->
    </div>
</details>
```

CSS:
```css
@media (max-width: 768px) {
    .tabs-header { display: none; }  /* скрыть tab-переключатели */
    .tab-pane { display: block; }    /* показать все панели */
    
    .product-accordion__trigger {
        min-height: 44px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 12px 16px;
        cursor: pointer;
        list-style: none;
        border-bottom: 1px solid var(--color-border);
        font-weight: var(--font-weight-semibold);
    }
    
    details[open] .product-accordion__icon {
        transform: rotate(180deg);
    }
}
```

---

## 8. Acceptance criteria — статус проверки

| AC | Статус |
|---|---|
| Все touch-targets ≥ 44px | ✅ size-btn, tab-btn, thumbnail-item, action-btn |
| CTA кнопка sticky при скролле | ✅ уже реализовано (CMP-23) |
| Gallery поддерживает swipe | ✅ JS touch events + dots |
| LCP не деградирует | ✅ eager/preload сохранены, passive listeners |
| Chrome DevTools iPhone SE (375px) | Проверить CTO: size-btn overflow |
| Chrome DevTools iPhone 14 (390px) | ✅ скриншот после изменений |
| Chrome DevTools Galaxy S21 (360px) | Проверить CTO |
