# СНИКЕРХЭД (СникерКультура) — Комплексный аудит проекта

**Дата:** 18 апреля 2026  
**Платформа:** Yii2 (PHP), MySQL 8.0, Redis, Nginx, Docker  
**Сайт:** localhost:8080  

---

## ЧАСТЬ 1 — БАЗА ДАННЫХ

### 1.1 Инициализация БД

| # | Проблема | Приоритет |
|---|----------|-----------|
| DB-1 | **Каталог `config/mysql/init/` пуст** — docker-entrypoint-initdb.d не найдёт ни одного SQL-файла. Свежий `docker-compose up` создаст пустую БД без таблиц. | **CRITICAL** |
| DB-2 | Всего 10 миграций — схема создавалась вне системы миграций. Отсутствует единый `schema.sql` или начальная миграция с полной структурой. | **HIGH** |
| DB-3 | RBAC DbManager настроен (`yii\rbac\DbManager`), но нет миграции для таблиц `auth_item`, `auth_item_child`, `auth_assignment`, `auth_rule`. Если таблиц нет — авторизация по ролям упадёт. | **HIGH** |

### 1.2 Замечание

Прямое подключение к MySQL из sandbox невозможно (БД доступна только из Docker-сети). Проверка row count, пустых таблиц, справочников (delivery/payment methods, statuses, settings) требует выполнения из контейнера `sneakerhead-app`.

---

## ЧАСТЬ 2 — МАРШРУТЫ (ROUTES)

Определено ~180 URL-правил в `infrastructure/config/web.php`. Результаты проверки:

### 2.1 Отсутствующие контроллеры

| # | Маршрут | Ожидаемый контроллер | Приоритет |
|---|---------|---------------------|-----------|
| RT-1 | `api/characteristic` (REST API) | `api/controllers/CharacteristicController.php` — **не существует** | **HIGH** |
| RT-2 | `checkout/*` (модуль) | `backend/modules/checkout/controllers/CheckoutController.php` — **не существует** (checkout обрабатывается через `frontend/controllers/OrderController`) | **LOW** (роуты не ссылаются напрямую) |

### 2.2 Отсутствующие action-методы

| # | Маршрут | Контроллер | Метод | Приоритет |
|---|---------|-----------|-------|-----------|
| RT-3 | `account/auth` | AccountController | `actionAuth` — **не существует** | **HIGH** |

### 2.3 Несоответствие классов модулей (PSR-4)

Конфигурация `web.php` ссылается на классы, которые не могут быть найдены автозагрузчиком PSR-4:

| # | Конфиг ожидает | Файл на диске | Приоритет |
|---|---------------|---------------|-----------|
| RT-4 | `app\backend\modules\coupon\CouponModule` | `backend/modules/coupon/Module.php` (класс `CouponModule`, но файл `Module.php`) | **CRITICAL** |
| RT-5 | `app\backend\modules\loyalty\LoyaltyModule` | `backend/modules/loyalty/Module.php` | **CRITICAL** |
| RT-6 | `app\backend\modules\returns\ReturnModule` | `backend/modules/returns/Module.php` | **CRITICAL** |
| RT-7 | `app\backend\modules\notification\NotificationModule` | `backend/modules/notification/Module.php` | **CRITICAL** |

PSR-4 маппинг `app\ → ""` означает, что `app\backend\modules\coupon\CouponModule` ищет файл `backend/modules/coupon/CouponModule.php`. Файл `Module.php` не будет найден. Результат: **Fatal Error при обращении к любому функционалу этих модулей.**

**Исправление:** переименовать файлы (`Module.php` → `CouponModule.php` и т.д.) или добавить classmap в `composer.json`.

### 2.4 Все контроллеры и действия — OK

Остальные ~170 маршрутов корректно привязаны к существующим контроллерам и action-методам. В том числе: SiteController (6 actions), PageController (7), OrderController (7), CatalogController (14), CatalogApiController (5), CartController (8, через наследование), CompareController (5), AccountController (13), FavoriteController (3), FeedbackController (2), все 26 admin-контроллеров, WebhookController.

---

## ЧАСТЬ 3 — КОД

### 3.1 TODO / FIXME (в проектных файлах, без vendor/node_modules)

| # | Файл | Содержание | Приоритет |
|---|------|-----------|-----------|
| CD-1 | `backend/modules/catalog/models/ProductReview.php` | «TODO: Создать полную реализацию модели» — модель-заглушка | **HIGH** |
| CD-2 | `backend/modules/catalog/models/AnalyticsEvent.php` | «TODO: Создать полную реализацию модели» — работает через raw SQL с fallback | **MEDIUM** |
| CD-3 | `backend/modules/admin/controllers/SettingsController.php` | «TODO: передавать X-CSRF-Token в заголовках fetch-запросов» — CSRF отключен | **HIGH** |
| CD-4 | `backend/modules/admin/controllers/CustomerController.php` | «TODO: перейти на передачу CSRF-токена через заголовок» — CSRF отключен | **HIGH** |
| CD-5 | `backend/modules/cart/controllers/CartController.php` | «TODO: передавать X-CSRF-Token» — CSRF отключен для всех AJAX | **HIGH** |
| CD-6 | `backend/modules/marketing/services/UpsellService.php` | «TODO: добавить отслеживание кликов и конверсий» | **LOW** |
| CD-7 | `backend/modules/account/views/account/wishlist.php` | «TODO: Здесь будет отображение избранных товаров» — пустой блок | **MEDIUM** |
| CD-8 | `console/controllers/ImportController.php` | «TODO: Реализовать отправку уведомлений» | **LOW** |

### 3.2 Дублирование моделей

| # | Модель | Дубль | Приоритет |
|---|--------|-------|-----------|
| CD-9 | `backend/modules/account/models/Customer.php` (393 строки) | `backend/modules/catalog/models/Customer.php` (10 строк — заглушка) | **MEDIUM** |
| CD-10 | `backend/modules/checkout/models/DeliveryTracking.php` (214 строк) | `backend/modules/catalog/models/DeliveryTracking.php` (10 строк — заглушка) | **MEDIUM** |
| CD-11 | `backend/modules/catalog/models/Tariff.php` (136 строк) | `backend/modules/admin/models/Tariff.php` (10 строк — заглушка) | **MEDIUM** |
| CD-12 | `backend/modules/catalog/models/TariffCalculation.php` (132 строки) | `backend/modules/admin/models/TariffCalculation.php` (10 строк — заглушка) | **MEDIUM** |
| CD-13 | `backend/modules/catalog/models/CustomerLoginForm.php` | `backend/modules/account/models/CustomerLoginForm.php` | **MEDIUM** |
| CD-14 | `backend/modules/catalog/models/CustomerRegisterForm.php` | `backend/modules/account/models/CustomerRegisterForm.php` | **MEDIUM** |
| CD-15 | `backend/modules/catalog/models/CustomerSocialAccount.php` | `backend/modules/account/models/CustomerSocialAccount.php` | **MEDIUM** |

### 3.3 Безопасность

| # | Проблема | Файл | Приоритет |
|---|----------|------|-----------|
| CD-16 | **`exec()` для запуска фонового импорта** — хотя input экранирован через `escapeshellarg`, паттерн `exec(...) > /dev/null &` рискован. Лучше использовать Yii2 Queue. | `frontend/controllers/AdminImportController.php:71` | **HIGH** |
| CD-17 | **`shell_exec()` для git rev-parse** — не критично, но `@` подавляет ошибки | `backend/shared/components/CdnHelper.php:272` | **LOW** |
| CD-18 | **CSRF отключен глобально** в `PluginController`, `WebhookController`, `CatalogApiController`, `TelegramBotController` | Несколько контроллеров | **HIGH** |
| CD-19 | **Hardcoded fallback cookie key** `'dev-only-key-change-in-production'` — хотя защищён условием `YII_ENV_DEV`, лучше убрать | `infrastructure/config/web.php:101` | **MEDIUM** |

### 3.4 Некорректные пути view-файлов

| # | Контроллер | Рендерит | Ожидаемый путь | Фактически | Приоритет |
|---|-----------|----------|----------------|------------|-----------|
| CD-20 | `OrderController::actionSuccess` | `render('success')` | `frontend/views/order/success.php` | **Не существует** (есть `frontend/views/checkout/success.php`) | **CRITICAL** |
| CD-21 | `OrderController` (upload-payment) | `render('order/track')` | `frontend/views/order/order/track.php` | **Не существует** (есть `frontend/views/order/track.php`) | **HIGH** |

---

## ЧАСТЬ 4 — СТРАНИЦЫ (HTTP)

Прямой HTTP-доступ из sandbox к `localhost:8080` невозможен (сайт работает в Docker-сети на машине пользователя). Проверка выполнена статическим анализом:

### 4.1 Страницы, которые гарантированно упадут

| # | URL | Причина | Приоритет |
|---|-----|---------|-----------|
| PG-1 | `/order/success/<token>` | View `order/success.php` не существует → **500 Internal Server Error** | **CRITICAL** |
| PG-2 | Любая страница, использующая модули coupon/loyalty/returns/notification | PSR-4 autoload не найдёт класс модуля → **Fatal Error** | **CRITICAL** |
| PG-3 | `/account/auth` | Action `actionAuth` не существует в AccountController → **404** | **HIGH** |
| PG-4 | `/api/characteristics` (REST) | Контроллер `api/controllers/CharacteristicController.php` не существует → **404** | **HIGH** |

### 4.2 Страницы с неполным функционалом

| # | URL | Проблема | Приоритет |
|---|-----|---------|-----------|
| PG-5 | `/account/wishlist` | Шаблон содержит TODO-заглушку вместо реального списка избранного | **MEDIUM** |
| PG-6 | `/catalog` (модуль) | Модуль catalog должен работать, но отсутствуют view-файлы в `backend/modules/catalog/views/` — используются `frontend/views/` через `setViewPath` | **LOW** (работает) |

---

## ЧАСТЬ 5 — ОТСУТСТВУЮЩИЕ ФУНКЦИИ И СВОДКА

### 5.1 Критические проблемы (требуют немедленного исправления)

1. **RT-4..7: PSR-4 mismatch для 4 модулей** — CouponModule, LoyaltyModule, ReturnModule, NotificationModule не загрузятся. Переименовать `Module.php` → `<ClassName>.php`.
2. **CD-20: Broken order success page** — после оплаты клиент увидит 500 ошибку. Исправить: `render('//checkout/success', ...)`.
3. **DB-1: Пустой init SQL** — развёртывание с нуля через Docker невозможно.

### 5.2 Высокий приоритет

4. **RT-1: REST API `/api/characteristics`** — контроллер не создан; API-потребители получат 404.
5. **RT-3: `/account/auth`** — маршрут ведёт в никуда (вероятно, предполагался для OAuth callback).
6. **CD-20, CD-21: Неверные view-пути** в OrderController.
7. **CD-3..5, CD-18: CSRF отключен** в 6+ контроллерах — нужно внедрить передачу `X-CSRF-Token` через JS.
8. **CD-16: `exec()` в AdminImportController** — заменить на Yii2 Queue (yii2-queue).
9. **DB-2: Нет полной схемы миграций** — невозможно воспроизвести БД без дампа.
10. **DB-3: RBAC таблицы** — нужна миграция `yii migrate --migrationPath=@yii/rbac/migrations`.
11. **CD-1: ProductReview — заглушка** — модель отзывов не реализована.

### 5.3 Средний приоритет

12. **CD-7: Wishlist пуст** — шаблон содержит TODO вместо списка товаров.
13. **CD-9..15: 7 дублей моделей** между модулями catalog/account/admin — заглушки в catalog нужно удалить, импорты перенаправить.
14. **CD-19: Hardcoded cookie key** для dev — убрать fallback, требовать `.env`.
15. **CD-2: AnalyticsEvent заглушка** — работает через raw SQL, нужна полноценная модель с миграцией.

### 5.4 Низкий приоритет

16. **CD-6: UpsellService** — нет отслеживания конверсий рекомендаций.
17. **CD-8: ImportController** — нет отправки уведомлений по результатам импорта.
18. **CD-17: shell_exec в CdnHelper** — некритично, но стоит обернуть в try/catch.

---

## Сводная таблица

| Приоритет | Кол-во | Примеры |
|-----------|--------|---------|
| **CRITICAL** | 5 | PSR-4 модулей (×4), broken success page, empty init SQL |
| **HIGH** | 7 | Missing API controller, missing action, CSRF disabled, exec(), RBAC, view paths |
| **MEDIUM** | 8 | Wishlist stub, model duplicates, AnalyticsEvent stub, hardcoded key |
| **LOW** | 3 | Upsell tracking, import notifications, shell_exec |
| **Всего** | **23** | |
