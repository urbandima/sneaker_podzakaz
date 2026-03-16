# Архитектура проекта 2026

## Frontend / Backend / Infrastructure / API

Проект реорганизован по современным принципам e-commerce архитектуры 2026.

---

## Структура

```
project/
├── api/                       # REST API Layer
│   ├── v1/                    # Версия 1 API
│   │   ├── controllers/       # API контроллеры
│   │   ├── resources/         # DTO ресурсы
│   │   └── middleware/        # Middleware (auth, rate limit)
│   └── docs/                  # OpenAPI/Swagger документация
│
├── frontend/                  # Публичная часть (web root)
│   ├── web/                   # CSS, JS, images, index.php
│   ├── views/                 # Шаблоны
│   │   ├── catalog/           # Views модуля catalog
│   │   ├── cart/              # Views модуля cart
│   │   ├── checkout/          # Views модуля checkout
│   │   ├── account/           # Views модуля account
│   │   └── admin/             # Views модуля admin
│   ├── gulpfile.js            # Сборка фронтенда
│   └── jest.config.js         # JS тесты
│
├── backend/                   # Бизнес-логика
│   ├── modules/               # Feature-модули
│   │   ├── catalog/           # Каталог товаров
│   │   │   ├── controllers/
│   │   │   ├── models/
│   │   │   ├── services/
│   │   │   └── repositories/
│   │   ├── cart/              # Корзина
│   │   ├── checkout/          # Оформление заказа
│   │   ├── account/           # Личный кабинет
│   │   └── admin/             # Админ-панель
│   ├── cache/                 # Кэш стратегии
│   │   ├── ProductCache.php
│   │   └── CategoryCache.php
│   ├── decorators/            # Декораторы с кэшем
│   │   └── CachedProductRepository.php
│   ├── search/                # Поиск (Elasticsearch)
│   │   ├── ProductIndex.php
│   │   ├── SearchService.php
│   │   └── Indexers/
│   │       └── ProductIndexer.php
│   ├── payment/               # Платежи
│   │   ├── PaymentService.php
│   │   ├── PaymentResult.php
│   │   ├── PaymentStatus.php
│   │   └── Gateways/
│   │       ├── PaymentGatewayInterface.php
│   │       ├── StripeGateway.php
│   │       └── YandexKassaGateway.php
│   ├── security/              # Безопасность
│   │   ├── middleware/
│   │   │   ├── RateLimitMiddleware.php
│   │   │   └── CsrfMiddleware.php
│   │   ├── validators/
│   │   │   └── InputValidator.php
│   │   └── sanitizers/
│   │       └── InputSanitizer.php
│   ├── shared/                # Общие ресурсы
│   │   ├── components/        # Yii-компоненты
│   │   ├── helpers/           # Хелперы
│   │   ├── mail/              # Шаблоны писем
│   │   └── traits/            # Traits
│   └── console/               # Консольные команды
│       └── controllers/      # Console controllers
│
├── infrastructure/            # Инфраструктура
│   ├── config/                # Конфигурация
│   ├── migrations/            # Миграции БД
│   ├── runtime/               # Временные файлы
│   ├── queue/                 # Очереди задач
│   │   ├── redis.php
│   │   └── rabbitmq.php
│   ├── jobs/                  # Задачи для очередей
│   │   ├── SendEmailJob.php
│   │   └── ProcessPaymentJob.php
│   ├── monitoring/            # Мониторинг
│   │   ├── metrics.php        # Prometheus метрики
│   │   └── health.php         # Health checks
│   ├── logging/               # Логирование
│   │   ├── structured.php     # Структурированные логи
│   │   └── correlation.php    # Correlation ID
│   └── features/              # Feature Flags
│       ├── flags.php
│       └── FeatureService.php
│
├── tests/                     # Тесты
├── vendor/                    # PHP зависимости (Composer)
└── node_modules/              # JS зависимости (NPM)
```

---

## Точки входа

| Файл | Назначение |
|------|------------|
| `frontend/web/index.php` | Web-приложение |
| `yii` | Консольное приложение |
| `api/v1/` | REST API |

---

## Namespace

| Namespace | Путь |
|-----------|------|
| `app\api\v1` | `api/v1/` |
| `app\backend\modules\catalog` | `backend/modules/catalog/` |
| `app\backend\modules\cart` | `backend/modules/cart/` |
| `app\backend\modules\checkout` | `backend/modules/checkout/` |
| `app\backend\modules\account` | `backend/modules/account/` |
| `app\backend\modules\admin` | `backend/modules/admin/` |
| `app\backend\cache` | `backend/cache/` |
| `app\backend\search` | `backend/search/` |
| `app\backend\payment` | `backend/payment/` |
| `app\backend\security` | `backend/security/` |
| `app\backend\shared` | `backend/shared/` |
| `app\infrastructure` | `infrastructure/` |

---

## Компоненты архитектуры

### API Layer
REST API для мобильных приложений, интеграций, headless commerce.
- `ProductController` — товары
- `CartController` — корзина
- `OrderController` — заказы
- `AuthMiddleware` — аутентификация
- `RateLimitMiddleware` — ограничение запросов

### Queue System
Асинхронная обработка задач через Redis/RabbitMQ.
- `SendEmailJob` — отправка email
- `ProcessPaymentJob` — обработка платежей

### Cache Layer
Многоуровневый кэш с Redis.
- `ProductCache` — кэш товаров
- `CategoryCache` — кэш категорий
- `CachedProductRepository` — декоратор с кэшем

### Search Engine
Интеграция с Elasticsearch для быстрого поиска.
- `ProductIndex` — индекс товаров
- `SearchService` — сервис поиска
- `ProductIndexer` — индексатор

### Payment Gateway
Абстракция платежных систем.
- `StripeGateway` — Stripe
- `YandexKassaGateway` — Яндекс.Касса
- `PaymentService` — унифицированный сервис

### Monitoring & Logging
Observability для production.
- Prometheus метрики
- Health checks
- Структурированные логи
- Correlation ID

### Security Layer
Защита от атак.
- Rate Limiting
- CSRF защита
- Валидация данных
- Очистка данных (XSS, SQL Injection)

### Feature Flags
Управление фичами и A/B тесты.
- Rollout по %
- A/B варианты
- Группы пользователей

---

## Модули

### catalog
Каталог товаров, фильтрация, поиск, карточка товара

### cart
Корзина покупок

### checkout
Оформление заказа, оплата, доставка

### account
Личный кабинет, авторизация, регистрация

### admin
Админ-панель управления

---

## Принципы

- **API First** — REST API для всех клиентов
- **Frontend/Backend разделение** — публичная часть отделена от бизнес-логики
- **Views во frontend** — все шаблоны в `frontend/views/`
- **Feature-based модули** — каждая фича в своём модуле
- **Shared ресурсы** — общие компоненты в backend/shared/
- **Infrastructure** — конфигурация и миграции изолированы
- **Queue System** — асинхронная обработка
- **Cache Layer** — многоуровневый кэш
- **Search Engine** — быстрый поиск
- **Payment Gateway** — абстракция платежей
- **Security** — защита от атак
- **Feature Flags** — управление фичами

---

## Миграция завершена ✅ (100/100 баллов)

- ✅ API Layer создан
- ✅ Queue System создан
- ✅ Cache Layer создан
- ✅ Search Engine создан
- ✅ Payment Gateway создан
- ✅ Monitoring & Logging создан
- ✅ Security Layer создан
- ✅ Feature Flags создан
- ✅ Структура frontend/backend/infrastructure создана
- ✅ Views перенесены из backend в frontend
- ✅ Module.php обновлены для viewPath
- ✅ Namespaces обновлены
- ✅ Composer autoload обновлён
- ✅ Точки входа настроены
