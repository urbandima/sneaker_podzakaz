# Архитектура каталога товаров - СНИКЕРХЭД

## 1. Общая архитектура системы

### 1.1 Структура каталога
```
Каталог товаров
├── По брендам
│   ├── NIKE
│   ├── ADIDAS
│   ├── PUMA
│   └── ...
├── По категориям
│   ├── Обувь
│   │   ├── Кроссовки
│   │   ├── Кеды
│   │   └── Ботинки
│   └── Одежда
│       ├── Футболки
│       ├── Толстовки
│       └── Брюки
└── Все товары
```

### 1.2 Схема базы данных

#### Таблица `category` (Категории)
```sql
CREATE TABLE category (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    slug VARCHAR(255) NOT NULL UNIQUE,
    parent_id INT NULL,
    description TEXT,
    image VARCHAR(255),
    sort_order INT DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    meta_title VARCHAR(255),
    meta_description TEXT,
    meta_keywords TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (parent_id) REFERENCES category(id) ON DELETE SET NULL,
    INDEX idx_slug (slug),
    INDEX idx_parent (parent_id),
    INDEX idx_active (is_active)
);
```

#### Таблица `brand` (Бренды)
```sql
CREATE TABLE brand (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    slug VARCHAR(255) NOT NULL UNIQUE,
    description TEXT,
    logo VARCHAR(255),
    sort_order INT DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    meta_title VARCHAR(255),
    meta_description TEXT,
    meta_keywords TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_slug (slug),
    INDEX idx_active (is_active)
);
```

#### Таблица `product` (Товары)
```sql
CREATE TABLE product (
    id INT PRIMARY KEY AUTO_INCREMENT,
    category_id INT NOT NULL,
    brand_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    slug VARCHAR(255) NOT NULL UNIQUE,
    description TEXT,
    price DECIMAL(10,2) NOT NULL,
    old_price DECIMAL(10,2),
    main_image VARCHAR(255),
    is_active TINYINT(1) DEFAULT 1,
    is_featured TINYINT(1) DEFAULT 0,
    stock_status ENUM('in_stock', 'out_of_stock', 'preorder') DEFAULT 'in_stock',
    views_count INT DEFAULT 0,
    meta_title VARCHAR(255),
    meta_description TEXT,
    meta_keywords TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES category(id) ON DELETE RESTRICT,
    FOREIGN KEY (brand_id) REFERENCES brand(id) ON DELETE RESTRICT,
    INDEX idx_slug (slug),
    INDEX idx_category (category_id),
    INDEX idx_brand (brand_id),
    INDEX idx_active (is_active),
    INDEX idx_featured (is_featured),
    INDEX idx_price (price)
);
```

#### Таблица `product_image` (Изображения товаров)
```sql
CREATE TABLE product_image (
    id INT PRIMARY KEY AUTO_INCREMENT,
    product_id INT NOT NULL,
    image_path VARCHAR(255) NOT NULL,
    sort_order INT DEFAULT 0,
    is_main TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES product(id) ON DELETE CASCADE,
    INDEX idx_product (product_id)
);
```

#### Таблица `product_size` (Размеры товаров)
```sql
CREATE TABLE product_size (
    id INT PRIMARY KEY AUTO_INCREMENT,
    product_id INT NOT NULL,
    size VARCHAR(50) NOT NULL,
    stock INT DEFAULT 0,
    is_available TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES product(id) ON DELETE CASCADE,
    INDEX idx_product (product_id),
    INDEX idx_available (is_available)
);
```

#### Таблица `product_color` (Цвета товаров)
```sql
CREATE TABLE product_color (
    id INT PRIMARY KEY AUTO_INCREMENT,
    product_id INT NOT NULL,
    color_name VARCHAR(100) NOT NULL,
    color_hex VARCHAR(7),
    is_available TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES product(id) ON DELETE CASCADE,
    INDEX idx_product (product_id)
);
```

#### Таблица `product_favorite` (Избранные товары)
```sql
CREATE TABLE product_favorite (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    product_id INT NOT NULL,
    session_id VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES user(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES product(id) ON DELETE CASCADE,
    UNIQUE KEY unique_favorite (user_id, product_id),
    INDEX idx_user (user_id),
    INDEX idx_product (product_id),
    INDEX idx_session (session_id)
);
```

#### Таблица `filter_history` (История фильтрации)
```sql
CREATE TABLE filter_history (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    session_id VARCHAR(255),
    filter_params TEXT,
    results_count INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES user(id) ON DELETE CASCADE,
    INDEX idx_user (user_id),
    INDEX idx_session (session_id)
);
```

#### Таблица `catalog_inquiry` (Заявки из каталога)
```sql
CREATE TABLE catalog_inquiry (
    id INT PRIMARY KEY AUTO_INCREMENT,
    product_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    phone VARCHAR(50) NOT NULL,
    email VARCHAR(255),
    message TEXT,
    size VARCHAR(50),
    color VARCHAR(100),
    status ENUM('new', 'processing', 'completed', 'cancelled') DEFAULT 'new',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES product(id) ON DELETE RESTRICT,
    INDEX idx_product (product_id),
    INDEX idx_status (status),
    INDEX idx_created (created_at)
);
```

## 2. JSON структура данных

### 2.1 Структура товара (Product JSON)
```json
{
  "id": 1,
  "name": "Nike Air Max 90",
  "slug": "nike-air-max-90",
  "brand": {
    "id": 1,
    "name": "NIKE",
    "slug": "nike"
  },
  "category": {
    "id": 5,
    "name": "Кроссовки",
    "slug": "krossovki",
    "parent": {
      "id": 1,
      "name": "Обувь",
      "slug": "obuv"
    }
  },
  "price": 189.99,
  "oldPrice": 249.99,
  "discount": 24,
  "mainImage": "/uploads/products/nike-air-max-90-main.jpg",
  "images": [
    "/uploads/products/nike-air-max-90-1.jpg",
    "/uploads/products/nike-air-max-90-2.jpg",
    "/uploads/products/nike-air-max-90-3.jpg"
  ],
  "sizes": [
    {"size": "40", "available": true, "stock": 5},
    {"size": "41", "available": true, "stock": 3},
    {"size": "42", "available": false, "stock": 0}
  ],
  "colors": [
    {"name": "Белый", "hex": "#FFFFFF", "available": true},
    {"name": "Черный", "hex": "#000000", "available": true}
  ],
  "description": "Классические кроссовки Nike Air Max 90...",
  "stockStatus": "in_stock",
  "isFeatured": true,
  "isFavorite": false,
  "viewsCount": 245
}
```

### 2.2 Структура фильтра (Filter JSON)
```json
{
  "brands": [1, 5, 8],
  "categories": [5],
  "priceFrom": 50,
  "priceTo": 300,
  "sizes": ["40", "41", "42"],
  "colors": ["#FFFFFF", "#000000"],
  "stockStatus": "in_stock",
  "sortBy": "price_asc",
  "page": 1,
  "perPage": 24,
  "viewMode": "grid"
}
```

### 2.3 Структура ответа каталога (Catalog Response JSON)
```json
{
  "success": true,
  "products": [...],
  "pagination": {
    "total": 156,
    "page": 1,
    "perPage": 24,
    "pages": 7
  },
  "filters": {
    "brands": [
      {"id": 1, "name": "NIKE", "count": 45},
      {"id": 2, "name": "ADIDAS", "count": 38}
    ],
    "categories": [
      {"id": 1, "name": "Обувь", "count": 89},
      {"id": 2, "name": "Одежда", "count": 67}
    ],
    "priceRange": {
      "min": 29.99,
      "max": 599.99
    },
    "sizes": [
      {"size": "40", "count": 23},
      {"size": "41", "count": 34}
    ],
    "colors": [
      {"name": "Белый", "hex": "#FFFFFF", "count": 56},
      {"name": "Черный", "hex": "#000000", "count": 78}
    ]
  },
  "appliedFilters": {
    "brands": [1, 5],
    "priceFrom": 50,
    "priceTo": 300
  }
}
```

## 3. SEO-оптимизация

### 3.1 ЧПУ URL структура
```
/ - Главная
/catalog - Все товары
/catalog/brand/nike - Товары бренда Nike
/catalog/category/obuv - Категория "Обувь"
/catalog/category/obuv/krossovki - Подкатегория "Кроссовки"
/catalog/product/nike-air-max-90 - Карточка товара
/catalog?brand=nike&price_from=100&price_to=300 - Фильтрованный каталог
```

### 3.2 Meta-теги
```php
// Главная каталога
title: "Каталог товаров - Оригинальные кроссовки и одежда | СНИКЕРХЭД"
description: "Широкий выбор оригинальной обувь и одежды из США и Европы. Nike, Adidas, Puma и другие бренды. Гарантия качества, доставка по Беларуси."
keywords: "купить кроссовки, оригинальная обувь, nike, adidas, интернет-магазин"

// Бренд
title: "NIKE - Оригинальные товары | СНИКЕРХЭД"
description: "Оригинальные кроссовки и одежда NIKE с доставкой из США и Европы. Гарантия подлинности."

// Категория
title: "Кроссовки - Оригинальная обувь | СНИКЕРХЭД"
description: "Купить оригинальные кроссовки известных брендов. Широкий выбор моделей, размеров и цветов."

// Товар
title: "{product_name} - Купить оригинал | СНИКЕРХЭД"
description: "{product_name} - Оригинал от {brand_name}. Цена: {price} BYN. Доставка по Беларуси."
```

### 3.3 Schema.org разметка
```json
{
  "@context": "https://schema.org",
  "@type": "Product",
  "name": "Nike Air Max 90",
  "image": "https://sneaker-head.by/uploads/products/nike-air-max-90.jpg",
  "description": "Классические кроссовки Nike Air Max 90",
  "brand": {
    "@type": "Brand",
    "name": "NIKE"
  },
  "offers": {
    "@type": "Offer",
    "price": "189.99",
    "priceCurrency": "BYN",
    "availability": "https://schema.org/InStock"
  }
}
```

## 4. UX/UI Спецификация

### 4.1 Компоненты интерфейса

#### Header каталога
- Навигация: Главная / Каталог / По брендам / По категориям
- Поиск с автодополнением
- Иконка избранного (с счетчиком)
- Иконка корзины/заявок

#### Боковая панель фильтров (Sidebar)
- Категории (дерево с чекбоксами)
- Бренды (чекбоксы)
- Цена (слайдер диапазона)
- Размер (чекбоксы)
- Цвет (цветные кружки)
- Наличие (чекбокс)
- Кнопки: "Применить" / "Сбросить"

#### Панель управления
- Сортировка: По популярности / По цене / По новизне
- Вид: Сетка (2/3/4 колонки) / Список
- Количество на странице: 24 / 48 / 96
- Активные фильтры (теги с возможностью удаления)

#### Карточка товара (Grid View)
- Изображение (hover - вторая картинка)
- Бейдж "NEW" / "SALE" / "HIT"
- Иконка избранного (угол)
- Название товара
- Бренд
- Цена (старая зачеркнутая + новая)
- Процент скидки
- Кнопка "Заказать"
- Быстрый просмотр (иконка глаза)

#### Карточка товара (List View)
- Изображение слева
- Информация справа (название, бренд, описание краткое)
- Цена и кнопки справа
- Доступные размеры (иконки)

### 4.2 Mobile-first breakpoints
```css
/* Mobile (базовый) */
@media (min-width: 320px) {
  .products-grid { grid-template-columns: 1fr; }
  .filters-sidebar { position: fixed; transform: translateX(-100%); }
}

/* Small tablets */
@media (min-width: 576px) {
  .products-grid { grid-template-columns: repeat(2, 1fr); }
}

/* Tablets */
@media (min-width: 768px) {
  .products-grid { grid-template-columns: repeat(3, 1fr); }
  .filters-sidebar { position: static; transform: none; }
}

/* Desktop */
@media (min-width: 1024px) {
  .products-grid { grid-template-columns: repeat(4, 1fr); }
}

/* Large Desktop */
@media (min-width: 1440px) {
  .products-grid { grid-template-columns: repeat(5, 1fr); }
}
```

## 5. Функционал фильтрации

### 5.1 Умный фильтр (100/100)

#### Особенности:
- **AJAX фильтрация** - мгновенный отклик без перезагрузки страницы
- **Композиция фильтров** - комбинирование нескольких условий
- **История фильтрации** - возврат к предыдущим выборам
- **URL синхронизация** - фильтры в URL для SEO и шеринга
- **Счетчики** - количество товаров для каждого варианта фильтра
- **Умная подсказка** - показ релевантных фильтров
- **Сохранение** - запоминание настроек пользователя

#### Алгоритм работы:
```javascript
1. Пользователь выбирает фильтр
2. JavaScript собирает все активные фильтры
3. AJAX запрос на сервер (метод: POST /catalog/filter)
4. Сервер возвращает JSON с товарами и обновленными счетчиками
5. Обновление URL (history.pushState)
6. Отрисовка товаров без перезагрузки
7. Обновление счетчиков фильтров
8. Сохранение в историю фильтрации
9. Метрика времени отклика (<100ms цель)
```

### 5.2 История фильтрации
```javascript
// Сохранение в localStorage
const filterHistory = {
  filters: [...],
  timestamp: Date.now(),
  resultsCount: 45
};
localStorage.setItem('filterHistory', JSON.stringify(filterHistory));

// Кнопка "Вернуться к предыдущему поиску"
if (localStorage.getItem('filterHistory')) {
  showRestoreButton();
}
```

## 6. Интеграция с CRM

### 6.1 Автоматическое создание заявок

Когда пользователь нажимает "Заказать" в карточке товара:

1. Открывается модальное окно с формой
2. Поля: Имя, Телефон, Email, Размер, Цвет, Комментарий
3. После отправки создается запись в `catalog_inquiry`
4. Автоматически создается заказ в таблице `order`
5. Отправка уведомлений менеджерам
6. Отправка подтверждения клиенту

### 6.2 Связь с Order системой
```php
// Автоматическое создание заказа из заявки каталога
public function createOrderFromInquiry($inquiry) {
    $order = new Order();
    $order->customer_name = $inquiry->name;
    $order->customer_phone = $inquiry->phone;
    $order->customer_email = $inquiry->email;
    $order->source = 'catalog'; // Новое поле
    $order->source_id = $inquiry->id;
    $order->status = 'created';
    $order->save();
    
    // Создание позиции заказа
    $orderItem = new OrderItem();
    $orderItem->order_id = $order->id;
    $orderItem->product_name = $inquiry->product->name;
    $orderItem->quantity = 1;
    $orderItem->price = $inquiry->product->price;
    $orderItem->save();
    
    return $order;
}
```

## 7. Метрики качества

### 7.1 Производительность
- ⚡ Загрузка каталога: < 1 секунда
- ⚡ AJAX фильтрация: < 100 миллисекунд
- ⚡ Первый контентный рендер (FCP): < 1.5 секунды
- ⚡ Largest Contentful Paint (LCP): < 2.5 секунды
- ⚡ First Input Delay (FID): < 100 миллисекунд
- ⚡ Cumulative Layout Shift (CLS): < 0.1

### 7.2 UX метрики
- 📊 Количество кликов до покупки: < 3
- 📊 Конверсия фильтров: > 40%
- 📊 Bounce rate: < 40%
- 📊 Время на странице: > 2 минуты
- 📊 Процент мобильного трафика: 60-70%

### 7.3 SEO метрики
- 🔍 Google PageSpeed Score: > 90
- 🔍 Индексация страниц: 100%
- 🔍 Корректность Schema.org: 100%
- 🔍 Время индексации новых товаров: < 24 часа

## 8. Чек-лист тестирования

### 8.1 Функциональное тестирование
- [ ] Отображение всех товаров в каталоге
- [ ] Фильтрация по бренду (одиночный/множественный выбор)
- [ ] Фильтрация по категории (включая подкатегории)
- [ ] Фильтрация по цене (слайдер)
- [ ] Фильтрация по размеру
- [ ] Фильтрация по цвету
- [ ] Комбинирование фильтров
- [ ] Сброс всех фильтров
- [ ] Сортировка (цена, популярность, новизна)
- [ ] Пагинация (переход между страницами)
- [ ] Переключение вида (сетка/таблица)
- [ ] Добавление в избранное
- [ ] Удаление из избранного
- [ ] Просмотр избранного
- [ ] Быстрый просмотр товара
- [ ] Открытие карточки товара
- [ ] Отправка заявки из каталога
- [ ] История фильтрации (сохранение/восстановление)

### 8.2 UX тестирование
- [ ] Интуитивное расположение фильтров
- [ ] Видимость активных фильтров
- [ ] Удобство сброса отдельных фильтров
- [ ] Индикаторы загрузки при AJAX
- [ ] Плавные анимации и переходы
- [ ] Читаемость текста на всех экранах
- [ ] Удобство кнопок и элементов управления
- [ ] Контрастность и цветовая схема
- [ ] Feedback для действий пользователя

### 8.3 Mobile тестирование
- [ ] Адаптивность на iPhone (320px+)
- [ ] Адаптивность на Android (360px+)
- [ ] Адаптивность на планшетах (768px+)
- [ ] Мобильное меню фильтров (drawer)
- [ ] Тач-жесты (свайп, тап)
- [ ] Скорость загрузки на 3G
- [ ] Размер кнопок (минимум 44x44px)
- [ ] Читаемость текста без зума
- [ ] Корректность viewport настроек

### 8.4 SEO тестирование
- [ ] Корректность всех ЧПУ URL
- [ ] Уникальность title для каждой страницы
- [ ] Уникальность description для каждой страницы
- [ ] Наличие H1 на всех страницах
- [ ] Корректность структуры заголовков (H1-H6)
- [ ] Наличие alt у всех изображений
- [ ] Schema.org разметка (Product, BreadcrumbList)
- [ ] Canonical URL для дубликатов
- [ ] Sitemap.xml включает все товары
- [ ] Robots.txt настроен корректно
- [ ] Open Graph теги для соцсетей
- [ ] Twitter Card метаданные
- [ ] Скорость загрузки (PageSpeed)
- [ ] Mobile-friendly тест Google
- [ ] Индексация в Google Search Console

### 8.5 Производительность
- [ ] AJAX запросы < 100ms
- [ ] Загрузка страницы < 1s
- [ ] Оптимизация изображений (WebP, lazy load)
- [ ] Минификация CSS/JS
- [ ] Кэширование статики
- [ ] CDN для изображений
- [ ] Gzip/Brotli сжатие
- [ ] Database query оптимизация
- [ ] N+1 query проблемы устранены

### 8.6 Интеграция с CRM
- [ ] Заявка создается в catalog_inquiry
- [ ] Заказ автоматически создается в order
- [ ] Email уведомление менеджеру
- [ ] Email подтверждение клиенту
- [ ] Связь заявки с товаром сохраняется
- [ ] Размер и цвет передаются корректно
- [ ] Статус заявки обновляется
- [ ] История изменений логируется

## 9. Бизнес-фичи (дополнительно)

### 9.1 Рекомендательная система
- "Похожие товары" на основе категории и бренда
- "Часто покупают вместе"
- "Вам может понравиться" на основе просмотров

### 9.2 Персонализация
- Запоминание размера пользователя
- Рекомендации на основе истории
- Персональные скидки

### 9.3 Аналитика
- Популярные фильтры
- Конверсия по источникам
- Карта кликов (heatmap)
- Анализ отказов

### 9.4 Маркетинг
- Таймеры скидок
- Бейджи "SALE", "NEW", "HIT"
- Уведомления "Осталось 3 шт"
- Email-рассылки с персональными подборками

## 10. Технический стек

### Backend
- **PHP 7.4+** - Yii2 Framework
- **MySQL 5.7+** - Основная БД
- **Redis** - Кэширование фильтров и счетчиков
- **Elasticsearch** (опционально) - Полнотекстовый поиск

### Frontend
- **HTML5 + CSS3** - Семантическая верстка
- **JavaScript (ES6+)** - Без фреймворков, чистый JS
- **AJAX** - Асинхронные запросы
- **LocalStorage** - История фильтрации
- **Bootstrap 5** - Базовые стили и компоненты
- **TailwindCSS** (опционально) - Utility-first CSS

### Оптимизация
- **Lazy loading** - Отложенная загрузка изображений
- **WebP** - Современный формат изображений
- **CDN** - Cloudflare для статики
- **Minification** - Сжатие CSS/JS
- **Database indexing** - Индексы для быстрых запросов

---

**Статус**: Архитектурная документация готова ✅  
**Следующий шаг**: Создание моделей Yii2 и миграций БД  
**Дата**: 01.11.2025
