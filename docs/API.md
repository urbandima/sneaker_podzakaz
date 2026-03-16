# СНИКЕРХЭД API Documentation

## Обзор

REST API для интернет-магазина СНИКЕРХЭД. Все endpoints возвращают JSON.

**Base URL:** `https://sneaker-head.by`

---

## Аутентификация

Большинство публичных endpoints не требуют аутентификации. Для admin endpoints необходима сессионная авторизация.

---

## Каталог

### Получить список товаров

```
GET /catalog
```

**Query Parameters:**

| Параметр | Тип | Описание |
|----------|-----|----------|
| `brands` | string/array | ID брендов (через запятую или массив) |
| `categories` | string/array | ID категорий |
| `sizes` | string/array | Размеры |
| `size_system` | string | Система размеров: `eu`, `us`, `uk`, `cm` (default: `eu`) |
| `price_from` | number | Минимальная цена |
| `price_to` | number | Максимальная цена |
| `colors` | string/array | ID цветов |
| `char_{id}` | string/array | Значения характеристики |
| `sort` | string | Сортировка: `popular`, `new`, `price_asc`, `price_desc`, `rating`, `discount` |
| `page` | integer | Номер страницы (default: 1) |
| `search` | string | Поисковый запрос |

**Response:** HTML страница каталога

---

### AJAX Фильтрация

```
POST /catalog/filter
Content-Type: application/x-www-form-urlencoded
```

**Body Parameters:**

| Параметр | Тип | Описание |
|----------|-----|----------|
| `brands` | JSON array | ID брендов |
| `categories` | JSON array | ID категорий |
| `sizes` | JSON array | Размеры |
| `sizeSystem` | string | Система размеров |
| `price_from` | number | Минимальная цена |
| `price_to` | number | Максимальная цена |
| `colors` | JSON array | ID цветов |
| `sort` | string | Сортировка |
| `page` | integer | Номер страницы |
| `perPage` | integer | Товаров на странице (default: 24) |

**Response:**

```json
{
  "success": true,
  "html": "<div class='product-card'>...</div>",
  "activeFiltersHtml": "<div class='active-filters'>...</div>",
  "activeFilters": [
    {"type": "brand", "label": "Nike", "value": "1", "removeUrl": "/catalog"}
  ],
  "paginationHtml": "<ul class='pagination'>...</ul>",
  "filters": {
    "brands": [...],
    "categories": [...],
    "priceRange": {"min": 100, "max": 1000},
    "sizes": {"eu": [...], "us": [...], "uk": [...], "cm": [...]},
    "colors": [...]
  },
  "pagination": {
    "total": 150,
    "currentPage": 1,
    "totalPages": 7,
    "perPage": 24
  }
}
```

---

### Бесконечная прокрутка

```
GET /catalog/load-more
```

**Query Parameters:**

| Параметр | Тип | Описание |
|----------|-----|----------|
| `page` | integer | Номер страницы |
| + все параметры фильтрации | | |

**Response:**

```json
{
  "success": true,
  "html": "<div class='product-card'>...</div>",
  "hasMore": true,
  "currentPage": 2,
  "totalPages": 7,
  "totalCount": 150
}
```

---

### Получить товары по ID

```
GET /catalog/products-by-ids
```

**Query Parameters:**

| Параметр | Тип | Описание |
|----------|-----|----------|
| `ids` | string/array | ID товаров (через запятую) |

**Response:**

```json
[
  {
    "id": 1,
    "name": "Nike Air Max 90",
    "brand": "Nike",
    "price": "350,00 BYN",
    "image": "/images/products/1.jpg",
    "url": "/product/nike-air-max-90"
  }
]
```

---

### Получить список брендов

```
GET /catalog/get-brands
```

**Response:**

```json
[
  {
    "id": 1,
    "name": "Nike",
    "slug": "nike",
    "products_count": 150
  },
  {
    "id": 2,
    "name": "Adidas",
    "slug": "adidas",
    "products_count": 120
  }
]
```

---

### Quick View товара

```
GET /catalog/quick-view
```

**Query Parameters:**

| Параметр | Тип | Описание |
|----------|-----|----------|
| `id` | integer | ID товара |

**Response:**

```json
{
  "success": true,
  "html": "<div class='quick-view-content'>...</div>",
  "product": {
    "id": 1,
    "name": "Nike Air Max 90",
    "price": 350,
    "sizes": [...]
  }
}
```

---

### Быстрый заказ

```
POST /catalog/quick-order
Content-Type: application/json
```

**Body:**

```json
{
  "product_id": 1,
  "name": "Иван Иванов",
  "phone": "+375291234567",
  "size": "42 EU",
  "comment": "Позвоните после 18:00"
}
```

**Response:**

```json
{
  "success": true,
  "message": "Заказ оформлен! Менеджер свяжется с вами в ближайшее время."
}
```

**Errors:**

```json
{
  "success": false,
  "message": "Пожалуйста, заполните все обязательные поля"
}
```

---

## Избранное

### Добавить в избранное

```
POST /favorite/add
Content-Type: application/json
```

**Body:**

```json
{
  "product_id": 1
}
```

**Response:**

```json
{
  "success": true,
  "count": 5
}
```

---

### Удалить из избранного

```
POST /favorite/remove
Content-Type: application/json
```

**Body:**

```json
{
  "product_id": 1
}
```

**Response:**

```json
{
  "success": true,
  "count": 4
}
```

---

### Получить количество избранного

```
GET /favorite/count
```

**Response:**

```json
{
  "count": 5
}
```

---

## Корзина

### Добавить в корзину

```
POST /cart/add
Content-Type: application/json
```

**Body:**

```json
{
  "product_id": 1,
  "size_id": 10,
  "quantity": 1
}
```

**Response:**

```json
{
  "success": true,
  "count": 3,
  "total": "750,00 BYN"
}
```

---

### Обновить количество

```
POST /cart/update
Content-Type: application/json
```

**Body:**

```json
{
  "item_id": 5,
  "quantity": 2
}
```

**Response:**

```json
{
  "success": true,
  "count": 4,
  "total": "1100,00 BYN",
  "item_total": "700,00 BYN"
}
```

---

### Удалить из корзины

```
POST /cart/remove
Content-Type: application/json
```

**Body:**

```json
{
  "item_id": 5
}
```

**Response:**

```json
{
  "success": true,
  "count": 2,
  "total": "400,00 BYN"
}
```

---

### Получить количество товаров в корзине

```
GET /cart/count
```

**Response:**

```json
{
  "count": 3
}
```

---

## Поиск

### Автодополнение поиска

```
GET /catalog/search
```

**Query Parameters:**

| Параметр | Тип | Описание |
|----------|-----|----------|
| `search` | string | Поисковый запрос (мин. 2 символа) |

**Response:** HTML страница с результатами поиска

---

## Коды ошибок

| Код | Описание |
|-----|----------|
| 200 | Успешный запрос |
| 400 | Неверные параметры запроса |
| 401 | Требуется авторизация |
| 403 | Доступ запрещен |
| 404 | Ресурс не найден |
| 429 | Слишком много запросов (rate limiting) |
| 500 | Внутренняя ошибка сервера |

---

## Rate Limiting

- **API endpoints:** 10 req/s (burst: 20)
- **Login:** 5 req/min (burst: 3)
- **General:** 30 req/s (burst: 100)

При превышении лимита возвращается HTTP 429.

---

## CORS

API поддерживает CORS для следующих origins:
- `https://sneaker-head.by`
- `https://www.sneaker-head.by`

---

## Версионирование

Текущая версия API: **v1** (implicit)

Будущие версии будут доступны по пути `/api/v2/...`
