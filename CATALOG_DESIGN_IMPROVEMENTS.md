# Улучшения Дизайна Каталога | Конкретные Решения с Кодом

**Дата**: 02.11.2024  
**Статус**: 📝 Готово к внедрению

---

## 🎨 Современные Карточки Товаров

### Текущее состояние (проблемы)
- Статичные изображения без интерактивности
- Бейджи перекрывают изображение товара
- Кнопка "В корзину" в отдельном блоке (занимает место)
- Цвета и размеры не кликабельны
- Swipeable галерея на desktop (избыточно)

### Новая структура карточки

#### HTML (_products.php)
```php
<div class="product-card modern">
    <a href="<?= $product->getUrl() ?>" class="product-link">
        <!-- Image с hover-эффектом смены фото -->
        <div class="product-image-wrapper">
            <img src="<?= $product->getMainImageUrl() ?>" 
                 alt="<?= Html::encode($product->name) ?>" 
                 class="product-image primary"
                 loading="lazy">
            
            <?php if (!empty($product->images[1])): ?>
            <img src="<?= $product->images[1]->getUrl() ?>" 
                 alt="<?= Html::encode($product->name) ?>" 
                 class="product-image secondary"
                 loading="lazy">
            <?php endif; ?>
            
            <!-- Компактные бейджи (верхний правый угол) -->
            <div class="product-badges-compact">
                <?php if ($product->hasDiscount()): ?>
                <span class="badge-discount">-<?= $product->getDiscountPercent() ?>%</span>
                <?php endif; ?>
                <?php if ($product->isNew()): ?>
                <span class="badge-new">NEW</span>
                <?php endif; ?>
            </div>
            
            <!-- Quick Actions (показываются при hover на desktop) -->
            <div class="quick-actions">
                <button class="action-btn favorite" 
                        onclick="toggleFav(event,<?= $product->id ?>)"
                        title="В избранное">
                    <i class="bi bi-heart"></i>
                </button>
                <button class="action-btn compare" 
                        onclick="toggleCompare(event,<?= $product->id ?>)"
                        title="Сравнить">
                    <i class="bi bi-arrow-left-right"></i>
                </button>
                <button class="action-btn quick-view" 
                        onclick="openQuickView(event,<?= $product->id ?>)"
                        title="Быстрый просмотр">
                    <i class="bi bi-eye"></i>
                </button>
            </div>
        </div>
        
        <!-- Компактная информация -->
        <div class="product-info-compact">
            <div class="product-brand"><?= Html::encode($product->brand->name) ?></div>
            <h3 class="product-name"><?= Html::encode($product->name) ?></h3>
            
            <!-- Рейтинг (если есть) -->
            <?php if ($product->rating > 0): ?>
            <div class="product-rating">
                <span class="stars">⭐ <?= number_format($product->rating, 1) ?></span>
                <span class="reviews">(<?= $product->reviews_count ?>)</span>
            </div>
            <?php endif; ?>
            
            <!-- Кликабельные размеры (первые 5) -->
            <?php if (!empty($product->sizes)): ?>
            <div class="sizes-inline">
                <?php foreach (array_slice($product->sizes, 0, 5) as $size): ?>
                <span class="size-item" 
                      onclick="filterBySize(event,'<?= $size->size ?>')"
                      title="Фильтровать по размеру <?= $size->size ?>">
                    <?= $size->size ?>
                </span>
                <?php endforeach; ?>
                <?php if (count($product->sizes) > 5): ?>
                <span class="size-more">+<?= count($product->sizes) - 5 ?></span>
                <?php endif; ?>
            </div>
            <?php endif; ?>
            
            <!-- Цена + Быстрая покупка -->
            <div class="price-action-row">
                <div class="price">
                    <?php if ($product->hasDiscount()): ?>
                    <span class="price-old"><?= Yii::$app->formatter->asCurrency($product->old_price, 'BYN') ?></span>
                    <?php endif; ?>
                    <span class="price-current"><?= Yii::$app->formatter->asCurrency($product->price, 'BYN') ?></span>
                </div>
                <button class="btn-quick-buy" 
                        onclick="quickBuy(event,<?= $product->id ?>)"
                        title="Быстрая покупка">
                    <i class="bi bi-cart-plus"></i>
                </button>
            </div>
        </div>
    </a>
</div>
```

#### CSS (добавить в catalog.css)
```css
/* ========================================
   СОВРЕМЕННЫЕ КАРТОЧКИ ТОВАРОВ
   ======================================== */

.product-card.modern {
  background: #fff;
  border-radius: 12px;
  overflow: hidden;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  box-shadow: 0 1px 3px rgba(0,0,0,0.06);
  height: 100%;
  display: flex;
  flex-direction: column;
}

.product-card.modern:hover {
  transform: translateY(-8px);
  box-shadow: 0 12px 24px rgba(0,0,0,0.12);
}

/* Изображение с hover-эффектом */
.product-image-wrapper {
  position: relative;
  padding-top: 125%;
  overflow: hidden;
  background: #f9fafb;
}

.product-image {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  object-fit: cover;
  transition: opacity 0.3s ease;
}

.product-image.secondary {
  opacity: 0;
}

/* Hover-эффект: смена изображения */
.product-card.modern:hover .product-image.primary {
  opacity: 0;
}

.product-card.modern:hover .product-image.secondary {
  opacity: 1;
}

/* Компактные бейджи (правый верхний угол) */
.product-badges-compact {
  position: absolute;
  top: 0.75rem;
  right: 0.75rem;
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
  z-index: 2;
}

.badge-discount {
  display: inline-block;
  padding: 0.375rem 0.625rem;
  background: rgba(239, 68, 68, 0.95);
  color: #fff;
  font-size: 0.75rem;
  font-weight: 700;
  border-radius: 6px;
  backdrop-filter: blur(4px);
  box-shadow: 0 2px 8px rgba(239, 68, 68, 0.3);
}

.badge-new {
  display: inline-block;
  padding: 0.375rem 0.625rem;
  background: rgba(16, 185, 129, 0.95);
  color: #fff;
  font-size: 0.75rem;
  font-weight: 700;
  border-radius: 6px;
  backdrop-filter: blur(4px);
}

/* Quick Actions (показываются при hover) */
.quick-actions {
  position: absolute;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  display: flex;
  gap: 0.5rem;
  opacity: 0;
  transition: opacity 0.3s ease;
  z-index: 3;
}

.product-card.modern:hover .quick-actions {
  opacity: 1;
}

.action-btn {
  width: 44px;
  height: 44px;
  background: rgba(255,255,255,0.95);
  border: none;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  font-size: 1.125rem;
  color: #111;
  box-shadow: 0 4px 12px rgba(0,0,0,0.15);
  transition: all 0.2s ease;
  backdrop-filter: blur(4px);
}

.action-btn:hover {
  background: #000;
  color: #fff;
  transform: scale(1.1);
}

.action-btn.favorite.active {
  color: #ef4444;
}

.action-btn.compare.active {
  background: #3b82f6;
  color: #fff;
}

/* Компактная информация */
.product-info-compact {
  padding: 1rem;
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
  flex: 1;
}

.product-brand {
  font-size: 0.75rem;
  font-weight: 700;
  text-transform: uppercase;
  color: #666;
  letter-spacing: 0.5px;
}

.product-name {
  font-size: 0.9375rem;
  font-weight: 600;
  line-height: 1.4;
  color: #111;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
  margin: 0;
}

.product-rating {
  display: flex;
  align-items: center;
  gap: 0.375rem;
  font-size: 0.75rem;
}

.product-rating .stars {
  color: #fbbf24;
  font-weight: 600;
}

.product-rating .reviews {
  color: #666;
}

/* Кликабельные размеры */
.sizes-inline {
  display: flex;
  flex-wrap: wrap;
  gap: 0.25rem;
}

.size-item {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-width: 28px;
  height: 24px;
  padding: 0 0.375rem;
  background: #f9fafb;
  border: 1px solid #e5e7eb;
  border-radius: 4px;
  font-size: 0.6875rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s ease;
}

.size-item:hover {
  background: #000;
  color: #fff;
  border-color: #000;
  transform: scale(1.05);
}

.size-more {
  display: inline-flex;
  align-items: center;
  font-size: 0.6875rem;
  color: #666;
  font-weight: 600;
}

/* Цена + Кнопка быстрой покупки */
.price-action-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-top: auto;
}

.price {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.price-old {
  font-size: 0.875rem;
  color: #9ca3af;
  text-decoration: line-through;
}

.price-current {
  font-size: 1.125rem;
  font-weight: 800;
  color: #000;
}

.btn-quick-buy {
  width: 36px;
  height: 36px;
  background: #000;
  color: #fff;
  border: none;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  font-size: 1.125rem;
  transition: all 0.2s ease;
  flex-shrink: 0;
}

.btn-quick-buy:hover {
  background: #1f2937;
  transform: scale(1.1);
  box-shadow: 0 4px 12px rgba(0,0,0,0.2);
}

/* Mobile: скрываем Quick Actions */
@media (max-width: 768px) {
  .quick-actions {
    display: none;
  }
  
  /* На mobile показываем кнопки в другом месте */
  .product-card.modern .action-btn {
    width: 36px;
    height: 36px;
    font-size: 1rem;
  }
}
```

#### JavaScript (добавить в catalog.js)
```javascript
// Фильтрация по размеру при клике
function filterBySize(event, size) {
    event.preventDefault();
    event.stopPropagation();
    
    // Находим чекбокс размера
    const sizeCheckbox = document.querySelector(`input[name="sizes[]"][value="${size}"]`);
    if (sizeCheckbox) {
        sizeCheckbox.checked = true;
        applyFilters();
    }
}

// Быстрая покупка
function quickBuy(event, productId) {
    event.preventDefault();
    event.stopPropagation();
    
    // Открываем Quick View или модальное окно выбора размера
    openQuickView(event, productId);
}

// Сравнение товаров
let compareProducts = JSON.parse(localStorage.getItem('compareProducts') || '[]');

function toggleCompare(event, productId) {
    event.preventDefault();
    event.stopPropagation();
    
    const button = event.currentTarget;
    const index = compareProducts.indexOf(productId);
    
    if (index > -1) {
        // Убираем из сравнения
        compareProducts.splice(index, 1);
        button.classList.remove('active');
        showNotification('Товар убран из сравнения');
    } else {
        // Добавляем в сравнение (макс 4 товара)
        if (compareProducts.length >= 4) {
            showNotification('Максимум 4 товара для сравнения', 'error');
            return;
        }
        compareProducts.push(productId);
        button.classList.add('active');
        showNotification('Товар добавлен к сравнению');
    }
    
    // Сохраняем в localStorage
    localStorage.setItem('compareProducts', JSON.stringify(compareProducts));
    
    // Обновляем счетчик
    updateCompareCount();
}

function updateCompareCount() {
    const badge = document.getElementById('compareCount');
    if (badge) {
        badge.textContent = compareProducts.length;
        badge.style.display = compareProducts.length > 0 ? 'flex' : 'none';
    }
}

// Инициализация при загрузке
document.addEventListener('DOMContentLoaded', function() {
    // Восстанавливаем активные кнопки сравнения
    compareProducts.forEach(productId => {
        const button = document.querySelector(`.action-btn.compare[onclick*="${productId}"]`);
        if (button) {
            button.classList.add('active');
        }
    });
    
    updateCompareCount();
});
```

---

## 🔄 Сравнение Товаров

### Sticky панель сравнения

#### HTML (добавить в layout)
```html
<div class="compare-panel" id="comparePanel" style="display:none">
    <div class="compare-content">
        <div class="compare-title">
            <i class="bi bi-arrow-left-right"></i>
            Сравнение (<span id="compareCountText">0</span>)
        </div>
        <div class="compare-items" id="compareItems">
            <!-- Товары для сравнения -->
        </div>
        <button class="btn-compare-show" onclick="showCompareTable()">
            Сравнить
        </button>
    </div>
</div>
```

#### CSS
```css
.compare-panel {
  position: fixed;
  bottom: 20px;
  right: 20px;
  background: #fff;
  border-radius: 12px;
  padding: 1rem;
  box-shadow: 0 8px 32px rgba(0,0,0,0.15);
  z-index: 100;
  max-width: 400px;
  animation: slideUp 0.3s ease-out;
}

@keyframes slideUp {
  from {
    transform: translateY(100px);
    opacity: 0;
  }
  to {
    transform: translateY(0);
    opacity: 1;
  }
}

.compare-items {
  display: flex;
  gap: 0.5rem;
  margin: 0.75rem 0;
}

.compare-item {
  position: relative;
  width: 60px;
  height: 60px;
  border-radius: 8px;
  overflow: hidden;
}

.compare-item img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.compare-item-remove {
  position: absolute;
  top: -4px;
  right: -4px;
  width: 20px;
  height: 20px;
  background: #ef4444;
  color: #fff;
  border: 2px solid #fff;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 0.75rem;
  cursor: pointer;
}

.btn-compare-show {
  width: 100%;
  background: #000;
  color: #fff;
  border: none;
  padding: 0.75rem;
  border-radius: 8px;
  font-weight: 700;
  cursor: pointer;
}
```

---

## 🎯 Персонализация

### Блок "Недавно просмотренные"

#### HTML (добавить в catalog/index.php)
```php
<?php if (!empty($recentlyViewed)): ?>
<section class="recently-viewed-section">
    <div class="section-header">
        <h2><i class="bi bi-clock-history"></i> Вы недавно смотрели</h2>
        <button class="btn-clear-history" onclick="clearViewHistory()">
            Очистить историю
        </button>
    </div>
    
    <div class="products-slider">
        <?php foreach ($recentlyViewed as $product): ?>
            <?= $this->render('_product_card', ['product' => $product]) ?>
        <?php endforeach; ?>
    </div>
</section>
<?php endif; ?>
```

#### Controller (CatalogController.php)
```php
public function actionIndex()
{
    // ... существующий код ...
    
    // Получаем недавно просмотренные товары
    $recentlyViewed = $this->getRecentlyViewedProducts();
    
    return $this->render('index', [
        'products' => $products,
        'pagination' => $pagination,
        'filters' => $filters,
        'currentFilters' => $currentFilters,
        'activeFilters' => $activeFilters,
        'recentlyViewed' => $recentlyViewed,
    ]);
}

protected function getRecentlyViewedProducts($limit = 6)
{
    // Читаем из cookie или localStorage через JavaScript
    $viewedIds = Yii::$app->request->cookies->getValue('recently_viewed', '');
    
    if (empty($viewedIds)) {
        return [];
    }
    
    $ids = explode(',', $viewedIds);
    $ids = array_slice(array_unique($ids), 0, $limit);
    
    return Product::find()
        ->where(['id' => $ids, 'is_active' => 1])
        ->orderBy([new \yii\db\Expression('FIELD(id, ' . implode(',', $ids) . ')')])
        ->all();
}
```

---

## 🧭 Улучшенная Навигация

### Мега-меню категорий

См. детали в [CATALOG_UX_ISSUES.md](./CATALOG_UX_ISSUES.md#7-слабая-навигация)

---

## 📊 Умная Сортировка

### Формула популярности

#### Миграция
```php
// migrations/mXXXXXX_add_popularity_score.php

public function up()
{
    $this->addColumn('product', 'popularity_score', $this->integer()->defaultValue(0));
    $this->createIndex('idx-product-popularity_score', 'product', 'popularity_score');
}
```

#### Model (Product.php)
```php
/**
 * Рассчитать и обновить popularity score
 */
public function updatePopularityScore()
{
    // Формула: views * 1 + orders * 10 + (rating * 2)
    $this->popularity_score = 
        ($this->views_count * 1) + 
        ($this->orders_count * 10) + 
        (($this->rating ?? 0) * 2);
    
    $this->save(false, ['popularity_score']);
}

/**
 * Обновить popularity для всех товаров (cron)
 */
public static function updateAllPopularityScores()
{
    $products = static::find()->all();
    foreach ($products as $product) {
        $product->updatePopularityScore();
    }
}
```

#### Controller
```php
// CatalogController.php

protected function applyFilters($query)
{
    // ... существующий код ...
    
    // Сортировка
    $sortBy = $request->get('sort', 'popular');
    switch ($sortBy) {
        case 'popular':
            $query->orderBy(['popularity_score' => SORT_DESC]);
            break;
        // ... остальные сортировки ...
    }
    
    return $query;
}
```

---

## ✅ Чек-лист Внедрения

### Фаза 1: Карточки товаров (1-2 дня)
- [ ] Создать новую структуру HTML карточки
- [ ] Добавить CSS стили для hover-эффектов
- [ ] Реализовать Quick Actions
- [ ] Добавить функцию filterBySize()
- [ ] Добавить функцию quickBuy()
- [ ] Протестировать на mobile и desktop

### Фаза 2: Сравнение (2-3 дня)
- [ ] Создать sticky панель сравнения
- [ ] Реализовать localStorage для хранения
- [ ] Создать страницу сравнения
- [ ] Добавить таблицу характеристик
- [ ] Протестировать функционал

### Фаза 3: Персонализация (2-3 дня)
- [ ] Реализовать трекинг просмотров
- [ ] Создать блок "Недавно просмотренные"
- [ ] Добавить рекомендации на основе истории
- [ ] Оптимизировать запросы

### Фаза 4: Умная сортировка (1 день)
- [ ] Создать миграцию для popularity_score
- [ ] Реализовать формулу популярности
- [ ] Создать cron для обновления scores
- [ ] Обновить сортировку в контроллере

**Общее время**: 6-9 дней  
**Приоритет**: Начать с Фазы 1 (максимальный визуальный эффект)
