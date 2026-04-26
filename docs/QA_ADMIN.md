# QA Admin Pages — Smoke Test
**Дата:** 2026-04-26  
**Исполнитель:** Claude Sonnet 4.6  
**Метод:** HTTP fetch + Chrome MCP (JS), без авторизации из браузера (сессия из предыдущей навигации)

---

## Сводная таблица

| URL | HTTP | Заголовок | Статус | Примечание |
|-----|------|-----------|--------|------------|
| `/admin` / `/admin/dashboard` | 200 | Панель управления | ✅ OK | Статистика, виджеты |
| `/admin/order` | 200 | Управление заказами | ✅ OK | Канбан + таблица, фильтры |
| `/admin/order/view?id=4669` | 200 | Заказ №WEB-… | ✅ OK | Все блоки, доставка, комментарий |
| `/admin/order/create` | 200 | Новый заказ | ✅ OK | Wizard |
| `/admin/catalog` | 200 | Каталог товаров | ✅ OK | 2769 товаров |
| `/admin/product/view?id=2711` | 200 | Puma Mayze Mid… | ✅ OK | Карточка товара |
| `/admin/product/create` | 200 | Новый товар | ✅ OK | Форма создания |
| `/admin/customer` | 200 | Покупатели | ✅ OK | |
| `/admin/user` | 200 | Пользователи | ✅ OK | 4 пользователя |
| `/admin/analytics` | 200 | Аналитика и отчёты | ✅ OK | |
| `/admin/finance` | 200 | Финансы | ✅ OK | |
| `/admin/finance/pl` | **200** | P&L — 2026 | ✅ **FIXED** | Было 500 (см. ниже) |
| `/admin/import` | 200 | Импорт | ✅ OK | |
| `/admin/marketing` | 200 | Маркетинг | ✅ OK | |
| `/admin/coupon` | 200 | Купоны | ✅ OK | |
| `/admin/return` | 200 | Возвраты | ✅ OK | |
| `/admin/review` | 200 | Отзывы | ✅ OK | |
| `/admin/feedback` | 200 | Обратная связь | ✅ OK | |
| `/admin/page` | 200 | Страницы | ✅ OK | |
| `/admin/plugin` | 200 | Плагины | ✅ OK | |
| `/admin/settings` | 200 | Настройки | ✅ OK | |
| `/admin/settings/shipping` | 200 | Настройки доставки | ✅ OK | |
| `/admin/settings/payment` | 200 | Способы оплаты | ✅ OK | |
| `/admin/settings/statuses` | 200 | Статусы заказов | ✅ OK | |
| `/admin/settings/seo` | 200 | SEO | ✅ OK | |
| `/admin/settings/integrations` | 200 | Плагины и интеграции | ✅ OK | |
| `/admin/activity-log` | 200 | — | ✅ OK | |
| `/admin/amocrm` | 200 | — | ✅ OK | |
| `/admin/order-source` | 200 | — | ✅ OK | |
| `/admin/order-status` | 200 | — | ✅ OK | |
| `/admin/sidebar-menu` | 200 | — | ✅ OK | |

---

## 404 — ожидаемо (несуществующие маршруты)

| URL | Примечание |
|-----|------------|
| `/admin/catalog/create` | Создание товаров через `/admin/product/create` |
| `/admin/catalog/view?id=N` | Просмотр через `/admin/product/view?id=N` |
| `/admin/settings/company` | Не реализован; компания через `/admin/settings` |
| `/admin/settings/general` | Не реализован |
| `/admin/settings/automation` | Не реализован |
| `/admin/reports` | Модуль не существует; используется `/admin/analytics` |

Ни один из 404-маршрутов не присутствует в навигационном меню — пользователи не попадут на них.

---

## Баг найден и исправлен

### `/admin/finance/pl` — 500 Internal Server Error

**Файл:** `backend/modules/admin/controllers/FinanceController.php`, строка 206  
**Коммит:** `0b68d3a`

**Причина:** Метод `actionPnl()` ссылался на `$excludedStatuses` — переменную, которая не объявлена в этом методе (объявлена как `self::REVENUE_EXCLUDED_STATUSES` в классе, но в методе не назначена локальная переменная).

**Исправление:**
```php
// было:
->where(['NOT IN', 'status', $excludedStatuses])

// стало:
->where(['NOT IN', 'status', self::REVENUE_EXCLUDED_STATUSES])
```

**Проверено:** страница P&L теперь возвращает 200 и заголовок "P&L — 2026 — Админ".

---

## Примечания

- PHP-исключений (500 с трейсом) не обнаружено ни на одной рабочей странице
- Все основные CRUD-маршруты доступны без ошибок
- Навигационное меню не содержит ссылок на 404-маршруты
- Тест проводился в браузере с активной admin-сессией (user_id=1)
