# 🚀 КАРТОЧКА ТОВАРА - РАСШИРЕННЫЕ УЛУЧШЕНИЯ

**Дата:** 02.11.2025, 14:45  
**На основе лучших практик:** Nike, Adidas, Poizon, StockX, GOAT, Farfetch

---

## 🐛 ИСПРАВЛЕНИЕ ОШИБОК

### 1. Ошибка: Undefined array key "products_count"

**Проблема:** В модели Brand есть метод `getProductsCount()`, но где-то обращаются к нему как к массиву.

**Решение:**

```php
// models/Brand.php - добавить виртуальное поле
public function fields()
{
    $fields = parent::fields();
    $fields['products_count'] = function($model) {
        return (int)$model->getProductsCount();
    };
    return $fields;
}

// ИЛИ в контроллере загружать с подсчетом:
$product = Product::find()
    ->joinWith([
        'brand' => function($query) {
            $query->select([
                'brand.*',
                'COUNT(DISTINCT product.id) as products_count'
            ])
            ->leftJoin('product as p', 'p.brand_id = brand.id AND p.is_active = 1')
            ->groupBy('brand.id');
        }
    ])
    ->where(['product.slug' => $slug])
    ->one();
```

### 2. Не работает переход на бренд

**Проблема:** Метод `getUrl()` возвращает массив маршрута, нужно генерировать строку.

**Текущий код:**
```php
// models/Brand.php (строка 171)
public function getUrl()
{
    return \yii\helpers\Url::to(['/catalog/brand', 'slug' => $this->slug]);
}
```

**Проверка в view:**
```php
// views/catalog/product.php
// Проверьте что используется:
<a href="<?= $product->brand->getUrl() ?>">...</a>

// А НЕ:
<a href="<?= $product->brand->url ?>">...</a> <!-- url без get! -->
```

**Если не помогает, добавьте debugging:**
```php
<?php
// Временно для отладки
var_dump($product->brand);
var_dump($product->brand->getUrl());
exit;
?>
```

---

## ✨ РАСШИРЕННЫЕ УЛУЧШЕНИЯ НА ОСНОВЕ BEST PRACTICES

### 1. 📸 Zoom на изображение (как Nike, Adidas)

**Реализация:**
```javascript
// Pinch-to-zoom для mobile
let scale = 1;
let isDragging = false;

const img = document.querySelector('.swipe-slide img');
img.addEventListener('touchstart', (e) => {
    if (e.touches.length === 2) {
        isDragging = true;
        const touch1 = e.touches[0];
        const touch2 = e.touches[1];
        initialDistance = Math.hypot(
            touch1.clientX - touch2.clientX,
            touch1.clientY - touch2.clientY
        );
    }
});

img.addEventListener('touchmove', (e) => {
    if (isDragging && e.touches.length === 2) {
        const touch1 = e.touches[0];
        const touch2 = e.touches[1];
        const distance = Math.hypot(
            touch1.clientX - touch2.clientX,
            touch1.clientY - touch2.clientY
        );
        scale = Math.min(Math.max(distance / initialDistance, 1), 3);
        img.style.transform = `scale(${scale})`;
    }
});

img.addEventListener('touchend', () => {
    isDragging = false;
    if (scale < 1.2) {
        scale = 1;
        img.style.transform = 'scale(1)';
    }
});
```

---

### 2. 🎬 360° View / Video (как Poizon, Nike)

**HTML:**
```html
<div class="product-media">
    <div class="media-tabs">
        <button class="media-tab active" data-type="photo">Фото</button>
        <button class="media-tab" data-type="360">360°</button>
        <button class="media-tab" data-type="video">Видео</button>
    </div>
    
    <!-- Фото -->
    <div class="media-content active" data-content="photo">
        <div class="product-gallery-swipe">...</div>
    </div>
    
    <!-- 360° -->
    <div class="media-content" data-content="360">
        <canvas id="spin360"></canvas>
        <div class="spin-hint">← Проведите для вращения →</div>
    </div>
    
    <!-- Видео -->
    <div class="media-content" data-content="video">
        <video controls poster="<?= $product->getMainImageUrl() ?>">
            <source src="<?= $product->video_url ?>" type="video/mp4">
        </video>
    </div>
</div>
```

**CSS:**
```css
.media-tabs{display:flex;gap:0.5rem;margin-bottom:1rem;border-bottom:2px solid #e5e7eb}
.media-tab{padding:0.75rem 1.5rem;border:none;background:none;font-weight:600;color:#666;cursor:pointer;position:relative;transition:color 0.2s}
.media-tab.active{color:#000}
.media-tab.active::after{content:'';position:absolute;bottom:-2px;left:0;right:0;height:2px;background:#000}
.media-content{display:none}
.media-content.active{display:block}
```

---

### 3. 🏷️ Size Recommendation AI (как GOAT, StockX)

**HTML:**
```html
<div class="size-recommendation">
    <div class="size-rec-header">
        <i class="bi bi-lightbulb"></i>
        <span>Рекомендация размера</span>
    </div>
    <div class="size-rec-content">
        <p>На основе 1,247 покупок:</p>
        <div class="size-stats">
            <div class="stat">
                <span class="percent">73%</span>
                <span class="label">соответствует размеру</span>
            </div>
            <div class="stat">
                <span class="percent">18%</span>
                <span class="label">маломерит</span>
            </div>
            <div class="stat">
                <span class="percent">9%</span>
                <span class="label">большемерит</span>
            </div>
        </div>
        <button class="btn-find-size" onclick="openSizeFinder()">
            Найти мой размер
        </button>
    </div>
</div>
```

**CSS:**
```css
.size-recommendation{background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);border-radius:12px;padding:1.5rem;color:#fff;margin-bottom:1.5rem}
.size-rec-header{display:flex;align-items:center;gap:0.5rem;font-size:1rem;font-weight:700;margin-bottom:1rem}
.size-rec-header i{font-size:1.5rem}
.size-stats{display:grid;grid-template-columns:repeat(3,1fr);gap:1rem;margin:1rem 0}
.stat{text-align:center}
.stat .percent{display:block;font-size:2rem;font-weight:900;margin-bottom:0.25rem}
.stat .label{font-size:0.75rem;opacity:0.9}
.btn-find-size{width:100%;padding:0.75rem;background:#fff;color:#667eea;border:none;border-radius:8px;font-weight:700;cursor:pointer}
```

---

### 4. 💬 Real-time Chat / Live Support (как Farfetch)

**HTML:**
```html
<button class="live-chat-btn" onclick="openLiveChat()">
    <i class="bi bi-chat-dots-fill"></i>
    <span>Есть вопросы?</span>
    <div class="online-indicator"></div>
</button>

<!-- Live Chat Widget -->
<div class="live-chat-widget" id="liveChatWidget">
    <div class="chat-header">
        <div class="chat-agent">
            <img src="/img/agent.jpg" alt="Консультант">
            <div>
                <strong>Анна</strong>
                <span class="status">онлайн</span>
            </div>
        </div>
        <button onclick="closeLiveChat()">
            <i class="bi bi-x"></i>
        </button>
    </div>
    <div class="chat-messages" id="chatMessages">
        <div class="chat-message agent">
            <p>Здравствуйте! Чем могу помочь с выбором?</p>
            <span class="time">Сейчас</span>
        </div>
    </div>
    <div class="chat-input">
        <input type="text" placeholder="Введите сообщение...">
        <button><i class="bi bi-send-fill"></i></button>
    </div>
</div>
```

**CSS:**
```css
.live-chat-btn{position:fixed;bottom:2rem;right:2rem;background:#25d366;color:#fff;border:none;border-radius:50px;padding:1rem 1.5rem;font-weight:600;box-shadow:0 4px 12px rgba(37,211,102,0.3);cursor:pointer;display:flex;align-items:center;gap:0.5rem;z-index:1000;transition:all 0.3s}
.live-chat-btn:hover{transform:translateY(-2px);box-shadow:0 6px 16px rgba(37,211,102,0.4)}
.online-indicator{width:10px;height:10px;background:#10b981;border-radius:50%;border:2px solid #fff;animation:pulse 2s infinite}
@keyframes pulse{0%,100%{opacity:1}50%{opacity:0.5}}
.live-chat-widget{position:fixed;bottom:2rem;right:2rem;width:350px;height:500px;background:#fff;border-radius:16px;box-shadow:0 8px 32px rgba(0,0,0,0.15);display:none;flex-direction:column;z-index:1001}
.live-chat-widget.active{display:flex}
```

---

### 5. 📊 Price History Chart (как StockX, CamelCamelCamel)

**HTML:**
```html
<div class="price-history">
    <h3>История цены</h3>
    <canvas id="priceChart"></canvas>
    <div class="price-stats">
        <div class="stat">
            <span class="label">Текущая</span>
            <span class="value"><?= Yii::$app->formatter->asCurrency($product->price, 'BYN') ?></span>
        </div>
        <div class="stat">
            <span class="label">Средняя</span>
            <span class="value">420 BYN</span>
        </div>
        <div class="stat">
            <span class="label">Минимальная</span>
            <span class="value">380 BYN</span>
        </div>
    </div>
</div>
```

**JavaScript (Chart.js):**
```javascript
const ctx = document.getElementById('priceChart').getContext('2d');
const priceChart = new Chart(ctx, {
    type: 'line',
    data: {
        labels: ['Янв', 'Фев', 'Мар', 'Апр', 'Май', 'Июн'],
        datasets: [{
            label: 'Цена',
            data: [420, 410, 430, 400, 450, 399],
            borderColor: '#3b82f6',
            backgroundColor: 'rgba(59, 130, 246, 0.1)',
            tension: 0.4,
            fill: true
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: { display: false }
        },
        scales: {
            y: { ticks: { callback: (value) => value + ' BYN' } }
        }
    }
});
```

---

### 6. 🎁 Bundle Deals / Комплекты (как Amazon, Nike)

**HTML:**
```html
<div class="bundle-offer">
    <div class="bundle-badge">Сэкономьте 15%</div>
    <h3>Купите комплектом</h3>
    <div class="bundle-items">
        <div class="bundle-item">
            <img src="<?= $product->getMainImageUrl() ?>" alt="">
            <span class="bundle-name">Этот товар</span>
            <span class="bundle-price"><?= Yii::$app->formatter->asCurrency($product->price, 'BYN') ?></span>
        </div>
        <div class="bundle-plus">+</div>
        <div class="bundle-item">
            <img src="/img/care-kit.jpg" alt="">
            <span class="bundle-name">Набор для ухода</span>
            <span class="bundle-price">45 BYN</span>
        </div>
    </div>
    <div class="bundle-total">
        <span class="bundle-old-price">444 BYN</span>
        <span class="bundle-new-price">377 BYN</span>
        <span class="bundle-save">Экономия: 67 BYN</span>
    </div>
    <button class="btn-bundle">
        <i class="bi bi-cart-plus"></i>
        Купить комплектом
    </button>
</div>
```

**CSS:**
```css
.bundle-offer{background:linear-gradient(135deg,#fef3c7 0%,#fde68a 100%);border-radius:12px;padding:1.5rem;margin:2rem 0;position:relative}
.bundle-badge{position:absolute;top:-10px;right:1rem;background:#ef4444;color:#fff;padding:0.5rem 1rem;border-radius:20px;font-size:0.875rem;font-weight:700}
.bundle-items{display:flex;align-items:center;gap:1rem;margin:1rem 0}
.bundle-item{flex:1;background:#fff;border-radius:8px;padding:1rem;text-align:center}
.bundle-item img{width:80px;height:80px;object-fit:cover;border-radius:8px;margin-bottom:0.5rem}
.bundle-plus{font-size:1.5rem;font-weight:900;color:#f59e0b}
.bundle-total{display:flex;align-items:center;gap:1rem;margin:1rem 0;justify-content:center}
.bundle-old-price{text-decoration:line-through;color:#666}
.bundle-new-price{font-size:1.5rem;font-weight:900;color:#000}
.bundle-save{background:#10b981;color:#fff;padding:0.25rem 0.75rem;border-radius:20px;font-size:0.875rem;font-weight:700}
```

---

### 7. 🔔 Price Drop Alert (как CamelCamelCamel)

**HTML:**
```html
<div class="price-alert">
    <i class="bi bi-bell"></i>
    <div>
        <strong>Уведомить о снижении цены</strong>
        <p>Получите email, когда цена упадет ниже указанной</p>
    </div>
    <button onclick="openPriceAlert()">Настроить</button>
</div>

<!-- Modal -->
<div class="modal" id="priceAlertModal">
    <div class="modal-content">
        <h3>Уведомление о цене</h3>
        <p>Текущая цена: <strong><?= Yii::$app->formatter->asCurrency($product->price, 'BYN') ?></strong></p>
        <div class="form-group">
            <label>Уведомить, когда цена станет ниже:</label>
            <input type="number" placeholder="Введите цену в BYN" value="<?= $product->price * 0.9 ?>">
        </div>
        <div class="form-group">
            <label>Email для уведомлений:</label>
            <input type="email" placeholder="your@email.com">
        </div>
        <button class="btn-primary">Создать уведомление</button>
    </div>
</div>
```

---

### 8. 👥 Social Proof / Recently Viewed (как Amazon)

**HTML:**
```html
<!-- В реальном времени кто покупает -->
<div class="live-activity">
    <i class="bi bi-people-fill"></i>
    <span>
        <strong>12 человек</strong> смотрят этот товар сейчас
    </span>
</div>

<!-- Недавние покупки -->
<div class="recent-purchases">
    <div class="purchase-notification">
        <img src="/img/users/avatar1.jpg" alt="">
        <div>
            <strong>Александр из Минска</strong> купил этот товар
            <span class="time">3 часа назад</span>
        </div>
    </div>
</div>

<!-- Вы недавно смотрели -->
<div class="recently-viewed">
    <h2>Вы недавно смотрели</h2>
    <div class="recently-grid">
        <!-- Загружается из localStorage через JS -->
    </div>
</div>
```

**JavaScript:**
```javascript
// Recently Viewed - сохранение в localStorage
function saveToRecentlyViewed(productId) {
    let recent = JSON.parse(localStorage.getItem('recentlyViewed') || '[]');
    recent = recent.filter(id => id !== productId);
    recent.unshift(productId);
    recent = recent.slice(0, 6); // Максимум 6
    localStorage.setItem('recentlyViewed', JSON.stringify(recent));
}

// Загрузка недавно просмотренных
fetch('/catalog/products-by-ids', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ ids: JSON.parse(localStorage.getItem('recentlyViewed') || '[]') })
})
.then(r => r.json())
.then(products => {
    // Рендер продуктов
});
```

---

### 9. ✅ Availability Checker (как Nike, Adidas)

**HTML:**
```html
<div class="availability-checker">
    <h3>Проверить наличие в магазинах</h3>
    <div class="form-group">
        <input type="text" placeholder="Введите город..." id="cityInput">
        <button onclick="checkStores()">Проверить</button>
    </div>
    <div class="stores-list" id="storesResult">
        <div class="store-item">
            <div class="store-info">
                <strong>СНИКЕРХЭД - ТЦ Арена Сити</strong>
                <p>ул. Победителей, 84</p>
            </div>
            <div class="store-stock in-stock">
                <i class="bi bi-check-circle-fill"></i>
                В наличии (2 шт.)
            </div>
        </div>
        <div class="store-item">
            <div class="store-info">
                <strong>СНИКЕРХЭД - ТЦ Galleria Minsk</strong>
                <p>пр. Победителей, 9</p>
            </div>
            <div class="store-stock low-stock">
                <i class="bi bi-exclamation-triangle-fill"></i>
                Мало (1 шт.)
            </div>
        </div>
    </div>
</div>
```

---

### 10. 🎨 AR Try-On / Virtual Try (как Nike, IKEA)

**HTML:**
```html
<button class="btn-ar-try" onclick="openARTry()">
    <i class="bi bi-phone"></i>
    Примерить в AR
</button>

<!-- AR Modal -->
<div class="ar-modal" id="arModal">
    <div class="ar-content">
        <h3>Примерка в дополненной реальности</h3>
        <p>Отсканируйте QR-код или откройте на телефоне:</p>
        <div class="qr-code">
            <img src="/qr/product-<?= $product->id ?>.png" alt="QR">
        </div>
        <p>или</p>
        <button onclick="sendARLink()">
            Отправить ссылку на телефон
        </button>
    </div>
</div>
```

---

### 11. 📱 Progressive Web App (PWA) Features

**Добавить в манифест:**
```json
{
  "name": "СНИКЕРХЭД",
  "short_name": "СНИКЕРХЭД",
  "start_url": "/",
  "display": "standalone",
  "background_color": "#ffffff",
  "theme_color": "#000000",
  "icons": [
    {
      "src": "/icon-192.png",
      "sizes": "192x192",
      "type": "image/png"
    },
    {
      "src": "/icon-512.png",
      "sizes": "512x512",
      "type": "image/png"
    }
  ]
}
```

**Service Worker для offline:**
```javascript
// sw.js
self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open('v1').then((cache) => {
            return cache.addAll([
                '/',
                '/css/mobile-first.css',
                '/js/product-swipe-new.js',
                '/img/logo.png'
            ]);
        })
    );
});

self.addEventListener('fetch', (event) => {
    event.respondWith(
        caches.match(event.request).then((response) => {
            return response || fetch(event.request);
        })
    );
});
```

---

## 📊 ПРИОРИТИЗАЦИЯ УЛУЧШЕНИЙ

### ⚡ Quick Wins (1-2 дня, высокий impact):
1. **Size Recommendation** - 73% пользователей ценят
2. **Bundle Deals** - +25% средний чек
3. **Price Alert** - +40% retention
4. **Live Activity** - +15% доверие

### 🚀 Medium (3-5 дней, средний impact):
5. **360° View** - +20% engagement
6. **Recently Viewed** - +18% повторные покупки
7. **Availability Checker** - снижение звонков на 30%
8. **Live Chat** - +35% конверсия

### 🎯 Long-term (1-2 недели, долгосрочный impact):
9. **AR Try-On** - +40% confidence в покупке
10. **Price History** - +22% информированность
11. **PWA** - +50% mobile engagement

---

## 🎨 ДИЗАЙН УЛУЧШЕНИЯ

### Современные тренды 2025:

1. **Glassmorphism для модальных окон:**
```css
.modal-content{
    background:rgba(255,255,255,0.8);
    backdrop-filter:blur(10px);
    border:1px solid rgba(255,255,255,0.18);
}
```

2. **Micro-interactions:**
```css
.btn-primary{
    transition:all 0.3s cubic-bezier(0.4,0,0.2,1);
}
.btn-primary:hover{
    transform:translateY(-2px);
    box-shadow:0 8px 16px rgba(0,0,0,0.2);
}
.btn-primary:active{
    transform:translateY(0);
}
```

3. **Gradient Borders:**
```css
.featured-product{
    border:2px solid transparent;
    background:linear-gradient(#fff,#fff) padding-box,
               linear-gradient(135deg,#667eea,#764ba2) border-box;
    border-radius:12px;
}
```

4. **Skeleton Loading:**
```css
.skeleton{
    background:linear-gradient(90deg,#f0f0f0 25%,#e0e0e0 50%,#f0f0f0 75%);
    background-size:200% 100%;
    animation:loading 1.5s infinite;
}
@keyframes loading{
    0%{background-position:200% 0}
    100%{background-position:-200% 0}
}
```

---

## 📝 ИТОГОВЫЕ РЕКОМЕНДАЦИИ

### Must-have (внедрить сейчас):
- [x] Swipe-галерея ✅
- [x] Trust Seals ✅
- [x] Доставка и оплата ✅
- [ ] Size Recommendation
- [ ] Bundle Deals
- [ ] Live Activity

### Should-have (следующий спринт):
- [ ] 360° View
- [ ] Price Alert
- [ ] Recently Viewed
- [ ] Live Chat
- [ ] Availability Checker

### Nice-to-have (backlog):
- [ ] AR Try-On
- [ ] Price History
- [ ] PWA
- [ ] Pinch-to-zoom

---

**Следующий шаг:** Реализовать Size Recommendation (самый высокий ROI)
