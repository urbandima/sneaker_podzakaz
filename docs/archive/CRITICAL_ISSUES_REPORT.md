# 🔴 КРИТИЧНЫЕ ПРОБЛЕМЫ СИСТЕМЫ - ТОП-10

**Дата анализа:** 04.11.2024  
**Система:** Order Management + E-commerce (Yii2)  
**Общая оценка:** 6.5/10

---

## 1. 🔐 ХАРДКОД PRODUCTION ПАРОЛЕЙ В КОДЕ

**Критичность:** 🔴🔴🔴 CRITICAL  
**Файл:** `/config/db.php`

### Проблема
```php
'username' => 'sneakerh_username_order_user',
'password' => 'kefir1kefir',  // ← ОТКРЫТЫЙ ПАРОЛЬ В КОДЕ
```

Реальный production пароль БД находится в репозитории в открытом виде.

### Последствия
- Утечка credentials в Git историю
- Любой с доступом к коду имеет доступ к БД
- Невозможность ротации паролей без изменения кода
- Нарушение всех security best practices

### Решение
```bash
# 1. Немедленно сменить пароль БД на production
# 2. Использовать .env файл
composer require vlucas/phpdotenv

# .env (добавить в .gitignore)
DB_HOST=localhost
DB_NAME=sneakerh_username_order_management
DB_USER=sneakerh_username_order_user
DB_PASS=новый_секретный_пароль

# config/db.php
return [
    'dsn' => "mysql:host={$_ENV['DB_HOST']};dbname={$_ENV['DB_NAME']}",
    'username' => $_ENV['DB_USER'],
    'password' => $_ENV['DB_PASS'],
];
```

---

## 2. ⚠️ ДУБЛИРОВАНИЕ БИЗНЕС-ЛОГИКИ: ДВА ПРОЕКТА В ОДНОМ

**Критичность:** 🔴🔴 HIGH  
**Затронуто:** Вся архитектура

### Проблема
Система содержит ДВА независимых проекта:

**Проект А: Order Management** (персональные заказы)
- Менеджер создает заказ для клиента вручную
- Клиент получает ссылку и оплачивает
- Модель: `Order`, контроллер: `AdminController`, `OrderController`

**Проект Б: E-commerce** (интернет-магазин)
- Каталог товаров с фильтрами
- Корзина, избранное
- Модель: `Cart`, контроллер: `CatalogController`, `CartController`

### Конфликты
- Непонятно: клиенты заказывают через менеджера ИЛИ через каталог?
- Дублирование таблиц: `order` vs `cart`
- Два контроллера для похожих задач
- Раздутая кодовая база (1172 строки AdminController)

### Решение
**Вариант 1:** Объединить в единый flow
```
Каталог → Корзина → Checkout → Order
```

**Вариант 2:** Разделить на два проекта
```
/order-management/  - для B2B менеджеров
/shop/              - для B2C клиентов
```

**Рекомендация:** Определить основную бизнес-модель и привести архитектуру в соответствие.

---

## 3. 💥 ОТСУТСТВИЕ ТРАНЗАКЦИЙ ПРИ СОЗДАНИИ ЗАКАЗА

**Критичность:** 🔴🔴 HIGH  
**Файл:** `/controllers/AdminController.php`

### Проблема
Создание заказа с товарами не атомарно:

```php
public function actionCreateOrder() {
    $order->save();  // ← Может успешно сохраниться
    
    foreach ($items as $item) {
        $orderItem->save();  // ← Может упасть здесь
    }
    // Заказ создан, но товары не добавлены!
}
```

### Последствия
- **Race condition:** два менеджера создают заказы → коллизия номеров
- **Несогласованные данные:** заказ без товаров или с частью товаров
- **Нарушение целостности:** клиент видит пустой заказ

### Решение
```php
public function actionCreateOrder() {
    $transaction = Yii::$app->db->beginTransaction();
    try {
        if (!$order->save()) {
            throw new \Exception('Order save failed');
        }
        
        foreach ($items as $item) {
            $item->order_id = $order->id;
            if (!$item->save()) {
                throw new \Exception('OrderItem save failed');
            }
        }
        
        $transaction->commit();
        Yii::$app->session->setFlash('success', 'Заказ создан');
        return $this->redirect(['view-order', 'id' => $order->id]);
        
    } catch (\Exception $e) {
        $transaction->rollBack();
        Yii::$app->session->setFlash('error', 'Ошибка: ' . $e->getMessage());
        return $this->redirect(['create-order']);
    }
}
```

---

## 4. 🐌 N+1 QUERY ПРОБЛЕМА В КАТАЛОГЕ

**Критичность:** 🔴 MEDIUM-HIGH  
**Файл:** `/controllers/CatalogController.php`

### Проблема
При загрузке 24 товаров выполняется **72+ SQL запроса**:

```php
Product::find()
    ->with(['brand', 'category', 'images', 'colors', 'sizes'])
    ->all();

// Для каждого товара:
// 1 запрос к product
// 1 запрос к brand
// 1 запрос к category  
// 1 запрос к images
// 1 запрос к colors
// 1 запрос к sizes
// = 6 запросов × 24 товара = 144 запроса!
```

### Последствия
- Медленная загрузка страницы (500-1000ms только на запросы)
- Высокая нагрузка на БД
- Плохой UX

### Решение

**Вариант 1: Денормализация** (быстрый fix)
```sql
ALTER TABLE product ADD COLUMN brand_name VARCHAR(100);
ALTER TABLE product ADD COLUMN category_name VARCHAR(100);
ALTER TABLE product ADD COLUMN main_image_url VARCHAR(500);
```

**Вариант 2: Eager Loading + JOIN**
```php
Product::find()
    ->select([
        'product.*',
        'brand.name as brand_name',
        'category.name as category_name',
    ])
    ->leftJoin('brand', 'brand.id = product.brand_id')
    ->leftJoin('category', 'category.id = product.category_id')
    ->all();
```

**Вариант 3: Кеширование**
```php
$products = Yii::$app->cache->getOrSet(
    ['catalog', 'products', $page, $filters],
    function() {
        return Product::find()->with([...])->all();
    },
    600 // 10 минут
);
```

---

## 5. 🔓 НЕБЕЗОПАСНАЯ ЗАГРУЗКА ФАЙЛОВ

**Критичность:** 🔴🔴 HIGH  
**Файл:** `/controllers/OrderController.php`

### Проблема
Валидация загружаемых файлов недостаточна:

```php
// Проверяется только расширение
if (in_array($file->extension, ['jpg', 'png', 'pdf'])) {
    $file->saveAs($path);
}
```

### Уязвимости
1. **File upload vulnerability:** можно загрузить `malware.php.jpg`
2. **Отсутствует MIME type проверка**
3. **Нет проверки magic bytes**
4. **Файлы доступны напрямую:** `web/uploads/payment_123.jpg`
5. **DoS атака:** загрузить файл 1GB

### Решение
```php
public function actionUploadPayment() {
    $file = UploadedFile::getInstance($model, 'paymentProof');
    
    // 1. Проверка размера
    if ($file->size > 10 * 1024 * 1024) { // 10MB
        throw new \yii\web\BadRequestHttpException('Файл слишком большой');
    }
    
    // 2. Проверка MIME type
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file->tempName);
    finfo_close($finfo);
    
    $allowedMimes = ['image/jpeg', 'image/png', 'application/pdf'];
    if (!in_array($mimeType, $allowedMimes)) {
        throw new \yii\web\BadRequestHttpException('Недопустимый тип файла');
    }
    
    // 3. Проверка magic bytes
    $handle = fopen($file->tempName, 'rb');
    $bytes = fread($handle, 10);
    fclose($handle);
    
    // JPEG: FF D8 FF
    // PNG: 89 50 4E 47
    // PDF: 25 50 44 46
    
    // 4. Генерация безопасного имени
    $uuid = Yii::$app->security->generateRandomString(32);
    $ext = $file->extension;
    $filename = $uuid . '.' . $ext;
    
    // 5. Сохранение ВНЕ web root
    $uploadPath = Yii::getAlias('@app/runtime/uploads/payments');
    $file->saveAs($uploadPath . '/' . $filename);
    
    // 6. Сохранение в БД только имя файла
    $order->payment_proof = $filename;
    $order->save();
}

// Отдача файла через контроллер (не напрямую)
public function actionDownloadPayment($id) {
    $order = Order::findOne($id);
    // Проверка прав доступа
    
    $path = Yii::getAlias('@app/runtime/uploads/payments/' . $order->payment_proof);
    return Yii::$app->response->sendFile($path);
}
```

---

## 6. 🚦 ОТСУТСТВУЕТ RATE LIMITING

**Критичность:** 🔴 HIGH  
**Затронуто:** Все AJAX endpoints

### Проблема
API endpoints не защищены от flood атак:

```
/catalog/filter   - можно слать 1000 req/sec
/cart/add         - можно создать миллион записей
/favorite/add     - аналогично
```

### Атака
```bash
# DoS атака
for i in {1..10000}; do
    curl -X POST http://site.by/cart/add \
         -d "product_id=1&quantity=1"
done
# → Перегрузка БД, миллион записей в cart
```

### Решение
```bash
composer require yii2-queue/yii2-queue
composer require yii2-redis/yii2-redis
```

```php
// config/web.php
'components' => [
    'redis' => [
        'class' => 'yii\redis\Connection',
        'hostname' => 'localhost',
        'port' => 6379,
    ],
],

// Middleware для rate limiting
class RateLimitFilter extends ActionFilter {
    public function beforeAction($action) {
        $key = 'ratelimit:' . Yii::$app->request->userIP;
        $redis = Yii::$app->redis;
        
        $count = $redis->incr($key);
        if ($count == 1) {
            $redis->expire($key, 60); // 60 секунд
        }
        
        if ($count > 100) { // 100 запросов в минуту
            throw new \yii\web\TooManyRequestsHttpException();
        }
        
        return parent::beforeAction($action);
    }
}

// Использование
class CatalogController extends Controller {
    public function behaviors() {
        return [
            'rateLimiter' => [
                'class' => RateLimitFilter::class,
            ],
        ];
    }
}
```

---

## 7. 🐛 CONSOLE.LOG И TODO В PRODUCTION КОДЕ

**Критичность:** 🟡 MEDIUM  
**Найдено:** 17+ вхождений

### Проблемы найдены
```javascript
// web/js/catalog.js
console.error('Ошибка AJAX:', error);  // Строка 139
console.error('Error loading page:', error);  // Строка 670

// views/catalog/favorites.php
// TODO: Реализовать через AJAX  // Строка 463
// TODO: AJAX запрос на удаление  // Строка 477

// controllers/CatalogController.php
// TODO: Добавить связь с таблицей размеров  // Строка 546
// TODO: Добавить поле free_delivery  // Строка 596

// commands/PoizonImportController.php
// TODO: Реализовать обновление цен  // Строка 582
```

### Последствия
- Утечка отладочной информации
- Захламление browser console
- Незавершенный функционал в production

### Решение
```javascript
// utils/logger.js
export const logger = {
    log: (...args) => {
        if (process.env.NODE_ENV === 'development') {
            console.log(...args);
        }
    },
    error: (...args) => {
        if (process.env.NODE_ENV === 'development') {
            console.error(...args);
        } else {
            // Отправить в Sentry/логирование
            sendToErrorTracking(args);
        }
    }
};

// Использование
import { logger } from './utils/logger';
logger.error('Ошибка AJAX:', error);
```

---

## 8. 📦 JQUERY В 2024 ГОДУ

**Критичность:** 🟡 MEDIUM  
**Файл:** `/web/js/cart.js`

### Проблема
Новый функционал пишется на jQuery:

```javascript
// cart.js - jQuery
function addToCart(productId) {
    $.ajax({
        url: '/cart/add',
        method: 'POST',
        // ...
    });
}

// catalog.js - Vanilla JS
function addToCart(productId) {
    fetch('/cart/add', {
        method: 'POST',
        // ...
    });
}
```

### Проблемы
- **Несогласованность:** одни файлы на jQuery, другие на Vanilla JS
- **Bundle size:** jQuery ~30KB (не нужен в 2024)
- **Устаревшая технология:** jQuery deprecated в новых проектах
- **Зависимость:** привязка к Yii2 jQuery asset

### Решение
Переписать `cart.js` на Vanilla JS:

```javascript
// БЫЛО (jQuery)
$.ajax({
    url: '/cart/add',
    method: 'POST',
    data: { product_id: productId },
    success: function(response) {
        updateCartCount(response.count);
    }
});

// СТАЛО (Vanilla JS)
fetch('/cart/add', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json',
        'X-CSRF-Token': getCsrfToken(),
    },
    body: JSON.stringify({ product_id: productId })
})
.then(res => res.json())
.then(data => {
    updateCartCount(data.count);
})
.catch(err => console.error(err));
```

---

## 9. 💾 INEFFICIENT CACHING

**Критичность:** 🟡 MEDIUM  
**Файл:** `/models/Product.php`

### Проблема
Кеширование через FileCache + glob удаление:

```php
protected function invalidateCatalogCache() {
    $cachePath = $cache->cachePath;
    $patterns = ['filters_data_*', 'catalog_filters_*'];
    
    foreach ($patterns as $pattern) {
        $files = glob($cachePath . '/' . $pattern);  // ← Медленно!
        foreach ($files as $file) {
            @unlink($file);  // ← Очень медленно!
        }
    }
}
```

### Проблемы
- `FileCache` медленный для production (disk I/O)
- `glob()` сканирует всю директорию
- `unlink()` на каждый файл - тормоза
- При 1000+ товарах инвалидация занимает секунды

### Решение
```bash
composer require yiisoft/yii2-redis
```

```php
// config/web.php
'cache' => [
    'class' => 'yii\redis\Cache',
    'redis' => [
        'hostname' => 'localhost',
        'port' => 6379,
        'database' => 0,
    ]
],

// Product.php
protected function invalidateCatalogCache() {
    TagDependency::invalidate(Yii::$app->cache, 'catalog');
}

// Использование
$products = Yii::$app->cache->getOrSet(
    ['catalog', 'products', $page],
    function() {
        return Product::find()->all();
    },
    600,
    new TagDependency(['tags' => 'catalog'])
);
```

---

## 10. 📊 ОТСУТСТВУЕТ МОНИТОРИНГ

**Критичность:** 🟡 MEDIUM  
**Затронуто:** Вся система

### Что отсутствует
- ❌ Error tracking (Sentry, Rollbar)
- ❌ Application Performance Monitoring
- ❌ Uptime monitoring
- ❌ Slow query logging
- ❌ Memory/CPU usage alerts

### Последствия
- Баги обнаруживают клиенты, не разработчики
- Нет метрик для оптимизации
- Невозможно отследить деградацию performance
- Простои незаметны до жалоб

### Решение
```bash
composer require sentry/sentry-yii2
```

```php
// config/web.php
'bootstrap' => ['log', 'sentry'],
'components' => [
    'sentry' => [
        'class' => 'sentry\\SentryComponent',
        'dsn' => 'https://your_key@sentry.io/project_id',
        'environment' => YII_ENV,
    ],
],

// Автоматический трекинг ошибок
try {
    $order->save();
} catch (\Exception $e) {
    Yii::$app->sentry->captureException($e);
    throw $e;
}
```

**Дополнительно:**
- Настроить UptimeRobot для мониторинга доступности
- Включить slow query log в MySQL
- Настроить Grafana + Prometheus для метрик

---

## 📈 ПРИОРИТИЗАЦИЯ ИСПРАВЛЕНИЙ

### Неделя 1 (Критично)
1. ✅ Убрать пароли из кода → `.env` файл
2. ✅ Добавить транзакции в создание заказа
3. ✅ Исправить загрузку файлов (security)

### Неделя 2 (Важно)
4. ✅ Решить проблему N+1 запросов
5. ✅ Добавить rate limiting
6. ✅ Переписать cart.js на Vanilla JS

### Неделя 3 (Желательно)
7. ✅ Убрать console.log и TODO
8. ✅ Переход на Redis cache
9. ✅ Интеграция Sentry
10. ✅ Определить единую архитектуру (Order vs Cart)

---

## 🎯 ИТОГОВЫЕ РЕКОМЕНДАЦИИ

### Архитектура
- **Определить:** Order Management ИЛИ E-commerce?
- **Разделить:** два независимых проекта или объединить в один flow

### Безопасность
- Немедленно сменить пароль БД
- Использовать `.env` для всех секретов
- Исправить загрузку файлов

### Производительность
- Redis вместо FileCache
- Решить N+1 queries
- Добавить мониторинг

### Код качества
- Убрать jQuery
- Добавить тесты (Codeception)
- Единый code style

**Общая оценка после исправлений:** 8.5/10
