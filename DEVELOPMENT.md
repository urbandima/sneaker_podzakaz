# 🛠️ РУКОВОДСТВО РАЗРАБОТЧИКА

Полное руководство по разработке и поддержке проекта СНИКЕРХЭД.

---

## 📋 СОДЕРЖАНИЕ

1. [Быстрый старт](#быстрый-старт)
2. [Структура проекта](#структура-проекта)
3. [Архитектура](#архитектура)
4. [Работа с кодом](#работа-с-кодом)
5. [Тестирование](#тестирование)
6. [Оптимизация](#оптимизация)
7. [Troubleshooting](#troubleshooting)

---

## 🚀 БЫСТРЫЙ СТАРТ

### Требования
- PHP 8.0+
- MySQL 5.7+ / MariaDB 10.3+
- Composer 2.0+
- Node.js 16+ (опционально, для сборки ассетов)

### Установка

```bash
# 1. Клонировать репозиторий
git clone https://github.com/your-username/sneaker-head.git
cd sneaker-head

# 2. Установить зависимости
composer install

# 3. Создать конфигурацию БД
cp config/db-example.php config/db.php
# Отредактировать config/db.php с вашими данными

# 4. Применить миграции
php yii migrate

# 5. Запустить локальный сервер
php yii serve --port=8080
```

Откройте http://localhost:8080

### Тестовые аккаунты
- **Admin:** admin / admin123
- **Manager:** manager / manager123

---

## 📁 СТРУКТУРА ПРОЕКТА

```
splitwise/
├── assets/                 # Asset bundles (CSS/JS)
│   ├── AppAsset.php       # Основной asset bundle
│   └── CatalogAsset.php   # Asset bundle каталога
│
├── commands/              # Console команды
│   ├── ImportController.php      # Импорт товаров
│   ├── PoizonImportController.php # Импорт из Poizon
│   └── AssetController.php       # Оптимизация ассетов
│
├── components/            # Переиспользуемые компоненты
│   ├── AssetOptimizer.php        # Оптимизация CSS/JS
│   ├── CacheManager.php          # Управление кешем
│   ├── CurrencyService.php       # Работа с валютами
│   ├── SchemaOrgGenerator.php    # Schema.org разметка
│   └── SitemapNotifier.php       # Уведомления о sitemap
│
├── config/                # Конфигурация
│   ├── web.php           # Конфиг веб-приложения
│   ├── console.php       # Конфиг консоли
│   ├── db.php            # Конфиг БД (не в git)
│   └── params.php        # Параметры приложения
│
├── controllers/           # Контроллеры
│   ├── admin/            # Админ-панель
│   │   ├── BaseAdminController.php
│   │   ├── ProductController.php
│   │   ├── OrderController.php
│   │   └── ...
│   ├── CatalogController.php     # Каталог товаров
│   ├── CartController.php        # Корзина
│   └── SiteController.php        # Главная, контакты
│
├── models/                # Модели данных
│   ├── Product.php       # Товар
│   ├── Brand.php         # Бренд
│   ├── Category.php      # Категория
│   ├── Order.php         # Заказ
│   ├── OrderItem.php     # Позиция заказа
│   └── User.php          # Пользователь
│
├── repositories/          # Репозитории (Repository Pattern)
│   └── ProductRepository.php     # Репозиторий товаров
│
├── services/              # Сервисы (бизнес-логика)
│   └── Sitemap/
│       └── SitemapGenerator.php  # Генерация sitemap
│
├── views/                 # Представления
│   ├── catalog/          # Каталог
│   │   ├── index.php            # Список товаров
│   │   ├── product.php          # Страница товара
│   │   ├── favorites.php        # Избранное
│   │   └── _product_card.php    # Карточка товара (partial)
│   ├── cart/             # Корзина
│   ├── admin/            # Админ-панель
│   └── layouts/          # Шаблоны
│       ├── main.php             # Основной layout
│       └── public.php           # Публичный layout
│
├── web/                   # Публичная директория
│   ├── css/              # Стили
│   │   ├── catalog-card.css     # Карточки товаров (основной)
│   │   ├── mobile-first.css     # Базовые mobile-first стили
│   │   ├── critical.css         # Critical CSS для inline
│   │   └── ...
│   ├── js/               # JavaScript
│   │   ├── catalog.js           # Логика каталога
│   │   ├── cart.js              # Логика корзины
│   │   ├── favorites.js         # Избранное
│   │   └── ...
│   ├── images/           # Изображения
│   ├── cache/            # Кеш изображений
│   └── index.php         # Entry point
│
├── migrations/            # Миграции БД
├── docs/                  # Документация
└── PROJECT_TASKS.md       # Задачи проекта
```

---

## 🏗️ АРХИТЕКТУРА

### MVC Pattern

#### Models (Модели)
Отвечают за работу с данными и бизнес-логику.

```php
// models/Product.php
class Product extends ActiveRecord
{
    public function rules() { /* валидация */ }
    public function getBrand() { /* связь с Brand */ }
    public function getCategory() { /* связь с Category */ }
}
```

#### Controllers (Контроллеры)
Обрабатывают запросы и возвращают ответы.

```php
// controllers/CatalogController.php
class CatalogController extends Controller
{
    public function actionIndex() {
        // Получить данные из репозитория
        // Передать в view
        return $this->render('index', ['products' => $products]);
    }
}
```

#### Views (Представления)
Отображают данные пользователю.

```php
// views/catalog/index.php
foreach ($products as $product) {
    echo $this->render('_product_card', ['product' => $product]);
}
```

---

### Repository Pattern

Абстракция работы с БД для упрощения тестирования и поддержки.

```php
// repositories/ProductRepository.php
class ProductRepository
{
    public function findActiveProducts($limit = 20) {
        return Product::find()
            ->where(['is_active' => 1])
            ->limit($limit)
            ->all();
    }
    
    public function findSimilarProducts($productId, $limit = 4) {
        // Сложная логика поиска похожих товаров
    }
}
```

**Использование:**
```php
$repository = new ProductRepository();
$products = $repository->findActiveProducts(20);
```

---

### Service Layer

Бизнес-логика вынесена в сервисы.

```php
// services/Sitemap/SitemapGenerator.php
class SitemapGenerator
{
    public function generate() {
        // Генерация sitemap.xml
    }
}
```

---

### Components (Компоненты)

Переиспользуемые компоненты для общих задач.

#### AssetOptimizer
Оптимизация загрузки CSS/JS.

```php
AssetOptimizer::optimizeCatalogPage($this);
```

#### CacheManager
Управление кешем.

```php
$cache = new CacheManager();
$cache->set('key', $data, 3600);
$data = $cache->get('key');
```

#### CurrencyService
Работа с валютами и курсами.

```php
$currencyService = new CurrencyService();
$priceInBYN = $currencyService->convert($priceUSD, 'USD', 'BYN');
```

---

## 💻 РАБОТА С КОДОМ

### Стандарты кодирования

Следуем **PSR-12** и best practices Yii2.

#### Именование
- **Классы:** PascalCase (`ProductController`, `OrderItem`)
- **Методы:** camelCase (`actionIndex`, `findActiveProducts`)
- **Переменные:** camelCase (`$productId`, `$isActive`)
- **Константы:** UPPER_SNAKE_CASE (`MAX_UPLOAD_SIZE`)

#### Комментарии
```php
/**
 * Находит активные товары с фильтрацией
 * 
 * @param array $filters Массив фильтров
 * @param int $limit Лимит результатов
 * @return Product[] Массив товаров
 */
public function findActiveProducts($filters = [], $limit = 20)
{
    // Реализация
}
```

---

### Работа с БД

#### ActiveRecord (для простых запросов)
```php
// Найти по ID
$product = Product::findOne($id);

// Найти с условием
$products = Product::find()
    ->where(['is_active' => 1])
    ->andWhere(['>', 'price', 0])
    ->orderBy(['created_at' => SORT_DESC])
    ->limit(20)
    ->all();
```

#### Query Builder (для сложных запросов)
```php
$query = (new \yii\db\Query())
    ->select(['p.*', 'b.name as brand_name'])
    ->from(['p' => 'products'])
    ->leftJoin(['b' => 'brands'], 'p.brand_id = b.id')
    ->where(['p.is_active' => 1])
    ->orderBy(['p.created_at' => SORT_DESC]);

$products = $query->all();
```

#### Repository (рекомендуется)
```php
$repository = new ProductRepository();
$products = $repository->findActiveProducts();
```

---

### Кеширование

#### Кеш данных
```php
$cache = Yii::$app->cache;

// Получить или создать
$data = $cache->getOrSet('products-list', function() {
    return Product::find()->all();
}, 3600); // 1 час

// Инвалидация
$cache->delete('products-list');
```

#### Кеш фрагментов (в views)
```php
<?php if ($this->beginCache('product-list', ['duration' => 3600])) { ?>
    <!-- Кешируемый контент -->
    <?php foreach ($products as $product): ?>
        ...
    <?php endforeach; ?>
<?php $this->endCache(); } ?>
```

---

### Миграции

#### Создание миграции
```bash
php yii migrate/create create_products_table
```

#### Структура миграции
```php
class m250109_120000_create_products_table extends Migration
{
    public function safeUp()
    {
        $this->createTable('products', [
            'id' => $this->primaryKey(),
            'name' => $this->string(255)->notNull(),
            'price' => $this->decimal(10, 2)->notNull(),
            'is_active' => $this->boolean()->defaultValue(1),
            'created_at' => $this->integer()->notNull(),
        ]);
        
        $this->createIndex('idx-products-is_active', 'products', 'is_active');
    }
    
    public function safeDown()
    {
        $this->dropTable('products');
    }
}
```

#### Применение миграций
```bash
# Применить все новые миграции
php yii migrate

# Откатить последнюю миграцию
php yii migrate/down

# Откатить 3 последние миграции
php yii migrate/down 3
```

---

## 🧪 ТЕСТИРОВАНИЕ

### Ручное тестирование

#### Чеклист для каталога
- [ ] Загрузка страницы < 2 секунд
- [ ] Фильтры работают без перезагрузки
- [ ] Карточки товаров отображаются корректно
- [ ] Адаптивность на мобильных устройствах
- [ ] Lazy loading изображений работает

#### Чеклист для страницы товара
- [ ] Все изображения загружаются
- [ ] Размеры отображаются корректно
- [ ] Кнопка "В корзину" работает
- [ ] Похожие товары показываются
- [ ] Schema.org разметка присутствует

---

### Тестирование производительности

#### Проверка времени загрузки
```bash
# С помощью curl
time curl -s http://localhost:8080/catalog > /dev/null

# Детальная информация
curl -w "@curl-format.txt" -o /dev/null -s http://localhost:8080/catalog
```

#### Проверка запросов к БД
Включите debug панель Yii2 в `config/web.php`:
```php
'bootstrap' => ['debug'],
'modules' => [
    'debug' => [
        'class' => 'yii\debug\Module',
    ],
],
```

Откройте http://localhost:8080/debug

---

## ⚡ ОПТИМИЗАЦИЯ

### CSS Оптимизация

#### Структура CSS файлов
- **`critical.css`** (6.3KB) - inline в `<head>` для быстрого рендера
- **`catalog-card.css`** (15KB) - основные стили карточек
- **`mobile-first.css`** (16KB) - базовые стили
- **`product-page.css`** (70KB) - стили страницы товара

#### Правила
1. **Не дублируйте стили** между файлами
2. **Critical CSS** должен быть < 10KB
3. **Используйте CSS-переменные** для повторяющихся значений
4. **Минифицируйте** перед деплоем

---

### JavaScript Оптимизация

#### Удаление отладочного кода
```bash
# Найти все console.log
grep -r "console.log" web/js/

# Удалить перед деплоем
```

#### Минификация
```bash
# Использовать uglify-js или terser
npm install -g terser
terser web/js/catalog.js -o web/js/catalog.min.js -c -m
```

---

### Оптимизация изображений

#### WebP конвертация
```bash
php yii webp/convert
```

#### Lazy Loading
Все изображения используют `loading="lazy"`:
```html
<img src="image.jpg" loading="lazy" alt="Product">
```

---

### Кеширование

#### Стратегия кеширования
1. **Список товаров** - 1 час
2. **Страница товара** - 30 минут
3. **Фильтры** - 1 час
4. **Sitemap** - 24 часа

#### Инвалидация кеша
```bash
# Очистить весь кеш
php yii cache/flush-all

# Очистить кеш каталога
php yii cache/flush catalog
```

---

## 🔧 TROUBLESHOOTING

### Проблема: Товары не отображаются

**Проверка:**
```bash
# Проверить наличие товаров в БД
mysql -u user -p database -e "SELECT COUNT(*) FROM products WHERE is_active=1;"

# Проверить кеш
php yii cache/flush-all
```

---

### Проблема: Медленная загрузка каталога

**Решение:**
1. Проверить индексы в БД
2. Включить кеширование
3. Оптимизировать запросы (использовать eager loading)

```php
// Плохо (N+1 проблема)
$products = Product::find()->all();
foreach ($products as $product) {
    echo $product->brand->name; // Дополнительный запрос для каждого товара
}

// Хорошо (eager loading)
$products = Product::find()->with('brand')->all();
foreach ($products as $product) {
    echo $product->brand->name; // Без дополнительных запросов
}
```

---

### Проблема: Ошибки CSS/JS после обновления

**Решение:**
```bash
# Очистить кеш браузера
# Добавить версию к файлам
$this->registerCssFile('@web/css/catalog-card.css?v=2.1');
```

---

## 📚 ДОПОЛНИТЕЛЬНЫЕ РЕСУРСЫ

### Документация
- [Yii2 Guide](https://www.yiiframework.com/doc/guide/2.0/en)
- [Yii2 API](https://www.yiiframework.com/doc/api/2.0)
- [PHP Manual](https://www.php.net/manual/en/)

### Полезные команды
```bash
# Список всех команд
php yii help

# Очистка кеша
php yii cache/flush-all

# Генерация sitemap
php yii sitemap/generate

# Импорт товаров
php yii import/poizon

# Оптимизация изображений
php yii webp/convert
```

---

## 🤝 CONTRIBUTING

### Workflow
1. Создать feature branch: `git checkout -b feature/new-feature`
2. Внести изменения
3. Commit: `git commit -m "Add new feature"`
4. Push: `git push origin feature/new-feature`
5. Создать Pull Request

### Commit Messages
```
feat: добавлена фильтрация по цвету
fix: исправлена ошибка в корзине
docs: обновлена документация
refactor: рефакторинг ProductRepository
perf: оптимизация запросов к БД
```

---

**Последнее обновление:** 9 ноября 2025  
**Версия:** 2.0
