# Документация структуры проекта

## Обзор проекта

Проект представляет собой e-commerce платформу для продажи кроссовок и обуви с импортом из Poizon (Dewu). Архитектура построена по современным принципам 2026 года с разделением на Frontend, Backend, API и Infrastructure.

---

## Структура проекта

```
splitwise/
├── api/                          # REST API Layer
│   ├── v1/                       # Версия 1 API
│   │   ├── controllers/          # API контроллеры
│   │   ├── middleware/           # Middleware (auth, rate limit)
│   │   └── resources/            # DTO ресурсы
│   └── docs/                     # OpenAPI/Swagger документация
│
├── backend/                      # Бизнес-логика
│   ├── cache/                    # Кэш стратегии
│   ├── commands/                 # Консольные команды
│   ├── decorators/               # Декораторы с кэшем
│   ├── modules/                  # Feature-модули
│   ├── payment/                  # Платежи
│   ├── search/                   # Поиск (Elasticsearch)
│   ├── security/                 # Безопасность
│   └── shared/                   # Общие ресурсы
│
├── frontend/                     # Публичная часть (web root)
│   ├── views/                    # Шаблоны
│   ├── web/                      # CSS, JS, images, index.php
│   ├── gulpfile.js               # Сборка фронтенда
│   └── jest.config.js            # JS тесты
│
├── infrastructure/               # Инфраструктура
│   ├── config/                   # Конфигурация
│   ├── migrations/               # Миграции БД
│   ├── runtime/                  # Временные файлы
│   ├── queue/                    # Очереди задач
│   ├── jobs/                     # Задачи для очередей
│   ├── monitoring/               # Мониторинг
│   ├── logging/                  # Логирование
│   └── features/                 # Feature Flags
│
├── tests/                        # Тесты
├── vendor/                       # PHP зависимости (Composer)
└── node_modules/                 # JS зависимости (NPM)
```

---

## API Layer (`api/`)

### Назначение
REST API для мобильных приложений, интеграций, headless commerce.

### Компоненты

#### `api/v1/controllers/`
- **CartController.php** - Управление корзиной (добавление, удаление, обновление товаров)
- **ProductController.php** - Получение товаров, фильтрация, поиск
- **OrderController.php** - Создание и управление заказами

#### `api/v1/middleware/`
- **AuthMiddleware.php** - Аутентификация API запросов
- **RateLimitMiddleware.php** - Ограничение частоты запросов

#### `api/v1/resources/`
- **OrderResource.php** - DTO для заказа (форматирование данных для API)
- **ProductResource.php** - DTO для товара (форматирование данных для API)

---

## Backend (`backend/`)

### Назначение
Бизнес-логика приложения, модули, сервисы, компоненты.

---

### `backend/cache/` - Кэш стратегии

#### `CategoryCache.php`
**Назначение:** Специфичный кэш для категорий

**Функции:**
- `getTree()` - Получить дерево категорий
- `setTree()` - Сохранить дерево категорий
- `getBySlug()` - Получить категорию по slug
- `setBySlug()` - Сохранить категорию
- `flush()` - Очистить кэш категорий

**TTL:** 24 часа (86400 сек)

#### `ProductCache.php`
**Назначение:** Специфичный кэш для товаров

**Функции:**
- `get()` - Получить товар из кэша
- `set()` - Сохранить товар в кэш
- `delete()` - Удалить товар из кэша
- `getList()` - Получить список товаров из кэша
- `setList()` - Сохранить список товаров в кэш
- `flush()` - Очистить весь кэш товаров

**TTL:** 1 час (3600 сек)

---

### `backend/commands/` - Консольные команды

#### `AssetController.php`
**Назначение:** Управление ассетами (CSS, JS, изображения)

**Действия:**
- Компиляция ассетов
- Оптимизация изображений
- Генерация WebP

#### `CharacteristicController.php`
**Назначение:** Управление характеристиками товаров

**Действия:**
- Импорт характеристик
- Генерация характеристик из данных Poizon
- Очистка неиспользуемых характеристик

#### `CleanSizesController.php`
**Назначение:** Очистка дубликатов размеров

**Действия:**
- Удаление дубликатов размеров
- Объединение размеров

#### `GeneratorController.php`
**Назначение:** Генерация тестовых данных

**Действия:**
- Генерация товаров
- Генерация категорий
- Генерация брендов

#### `ImportController.php`
**Назначение:** Импорт данных из внешних источников

**Действия:**
- Импорт товаров
- Импорт категорий
- Импорт брендов

#### `ParserController.php`
**Назначение:** Парсинг данных с сайтов

**Действия:**
- Парсинг Poizon
- Парсинг других сайтов

#### `PoizonImportController.php`
**Назначение:** Импорт товаров из Poizon (Dewu)

**Действия:**
- Импорт списка товаров
- Парсинг страниц товаров
- Обновление цен и остатков

#### `PoizonImportJsonController.php`
**Назначение:** Импорт товаров из Poizon через JSON API

**Действия:**
- Импорт из JSON
- Обработка больших объемов данных

#### `RoundPricesController.php`
**Назначение:** Округление цен

**Действия:**
- Округление цен до красивых чисел
- Обновление цен в БД

#### `SitemapController.php`
**Назначение:** Генерация sitemap.xml

**Действия:**
- Генерация sitemap для товаров
- Генерация sitemap для категорий
- Генерация sitemap для брендов

#### `TestController.php`
**Назначение:** Тестирование функционала

**Действия:**
- Тестирование кэша
- Тестирование API
- Тестирование импорта

#### `WebpController.php`
**Назначение:** Конвертация изображений в WebP

**Действия:**
- Конвертация всех изображений
- Оптимизация WebP

---

### `backend/decorators/` - Декораторы

#### `CachedProductRepository.php`
**Назначение:** Декоратор репозитория товаров с кэшем

**Функции:**
- `find()` - Получить товар (с кэшем)
- `findByCategory()` - Получить список товаров (с кэшем)
- `save()` - Сохранить товар (очистить кэш)

**Паттерн:** Decorator Pattern

---

### `backend/modules/` - Feature-модули

#### `catalog/` - Каталог товаров

**Назначение:** Управление каталогом товаров, фильтрация, поиск, карточка товара

**Структура:**
- `controllers/CatalogController.php` - Контроллер каталога
- `models/` - Модели (Product, Category, Brand, Characteristic и др.)
- `services/Catalog/FilterBuilder.php` - Построитель фильтров
- `repositories/ProductRepository.php` - Репозиторий товаров
- `assets/CatalogAsset.php` - Ассеты модуля

**Основные функции:**
- Каталог товаров с пагинацией
- Страница бренда
- Страница категории
- Карточка товара
- Live-поиск
- Быстрый просмотр
- Избранные товары

#### `cart/` - Корзина

**Назначение:** Управление корзиной покупок

**Структура:**
- `controllers/CartController.php` - Контроллер корзины
- `models/Cart.php` - Модель корзины
- `assets/CartAsset.php` - Ассеты модуля

**Основные функции:**
- Добавление товаров в корзину
- Удаление товаров из корзины
- Обновление количества
- Расчет итоговой суммы

#### `checkout/` - Оформление заказа

**Назначение:** Оформление заказа, оплата, доставка

**Структура:**
- `controllers/OrderController.php` - Контроллер заказа
- `models/` - Модели заказа

**Основные функции:**
- Создание заказа
- Выбор способа оплаты
- Выбор способа доставки
- Подтверждение заказа

#### `account/` - Личный кабинет

**Назначение:** Личный кабинет, авторизация, регистрация

**Структура:**
- `controllers/AccountController.php` - Контроллер аккаунта
- `models/` - Модели (Customer, CustomerLoginForm и др.)

**Основные функции:**
- Авторизация
- Регистрация
- Профиль пользователя
- История заказов
- Настройки

#### `admin/` - Админ-панель

**Назначение:** Админ-панель управления

**Структура:**
- `controllers/` - Контроллеры (Dashboard, Product, Order и др.)
- `models/` - Модели (User, CompanySettings и др.)

**Основные функции:**
- Управление товарами
- Управление заказами
- Управление пользователями
- Статистика
- Аналитика
- Импорт из Poizon

---

### `backend/payment/` - Платежи

**Назначение:** Абстракция платежных систем

#### `PaymentService.php`
**Назначение:** Унифицированный сервис платежей

**Функции:**
- Создание платежа
- Обработка вебхуков
- Проверка статуса платежа

#### `PaymentResult.php`
**Назначение:** Результат платежа

**Поля:**
- Статус платежа
- Транзакция
- Ошибки

#### `PaymentStatus.php`
**Назначение:** Статусы платежей

**Статусы:**
- PENDING
- COMPLETED
- FAILED
- REFUNDED

#### `Gateways/`
**Назначение:** Шлюзы платежных систем

- `PaymentGatewayInterface.php` - Интерфейс шлюза
- `StripeGateway.php` - Шлюз Stripe
- `YandexKassaGateway.php` - Шлюз Яндекс.Касса

---

### `backend/search/` - Поиск

**Назначение:** Интеграция с Elasticsearch для быстрого поиска

#### `ProductIndex.php`
**Назначение:** Индекс товаров в Elasticsearch

**Поля:**
- Название товара
- Описание
- Бренд
- Категория
- Характеристики

#### `SearchService.php`
**Назначение:** Сервис поиска

**Функции:**
- Поиск товаров
- Автокомплит
- Фасетный поиск

#### `Indexers/ProductIndexer.php`
**Назначение:** Индексатор товаров

**Функции:**
- Индексация товара
- Обновление индекса
- Удаление из индекса

---

### `backend/security/` - Безопасность

**Назначение:** Защита от атак

#### `middleware/`
- **RateLimitMiddleware.php** - Ограничение частоты запросов
- **CsrfMiddleware.php** - CSRF защита

#### `validators/`
- **InputValidator.php** - Валидация входных данных

#### `sanitizers/`
- **InputSanitizer.php** - Очистка данных (XSS, SQL Injection)

---

### `backend/shared/` - Общие ресурсы

**Назначение:** Общие компоненты, хелперы, трейты для всего проекта

---

#### `backend/shared/components/` - Yii-компоненты

**Все компоненты используют namespace `app\shared\components`**

##### `CacheManager.php`
**Назначение:** Централизованное управление кэшированием

**Функции:**
- Redis/File кэширование с автоопределением
- Tagged caching для массовой инвалидации
- Специализированные методы для фильтров, продуктов, счётчиков
- Статистика использования кэша
- Batch операции для производительности

**Префиксы ключей:**
- PREFIX_FILTERS: данные фильтров
- PREFIX_PRODUCTS: данные товаров
- PREFIX_CATALOG: данные каталога
- PREFIX_COUNT: счётчики
- PREFIX_SEARCH: результаты поиска

**Теги для инвалидации:**
- TAG_FILTERS: инвалидация фильтров
- TAG_PRODUCTS: инвалидация товаров
- TAG_CATALOG: инвалидация каталога
- TAG_BRANDS: инвалидация брендов
- TAG_CATEGORIES: инвалидация категорий

**TTL:**
- TTL_SHORT: 300 сек (5 минут)
- TTL_MEDIUM: 1800 сек (30 минут)
- TTL_LONG: 3600 сек (1 час)
- TTL_VERY_LONG: 86400 сек (24 часа)

##### `SmartFilter.php`
**Назначение:** Компонент умного фильтра с SEF URL

**Функции:**
- `generateSefUrl()` - генерация SEF URL из фильтров
- `parseSefUrl()` - парсинг SEF URL в параметры фильтров
- `generateMetaTitle()` - генерация мета-заголовка
- `generateMetaDescription()` - генерация мета-описания
- `generateH1()` - генерация H1 заголовка

**Формат URL:**
`/catalog/filter/brand-nike-adidas/price-100-500/size-40-42/`

**Использование:**
- CatalogController (фильтрация каталога)
- SEF URL для SEO
- Динамические мета-теги

##### `SitemapNotifier.php`
**Назначение:** Планирование регенерации sitemap

**Функции:**
- `scheduleRegeneration()` - пометить sitemap как требующий обновления
- `isPending()` - проверить, требуется ли регенерация
- `reset()` - сбросить флаг после генерации
- `getLastRun()` - получить время последней генерации

**Ключи кэша:**
- CACHE_KEY_PENDING: ключ флага ожидания
- CACHE_KEY_LAST_RUN: ключ времени последнего запуска
- CACHE_KEY_PENDING_SINCE: ключ времени установки флага

**TTL:** 24 часа (86400 сек)

##### `TariffSetupService.php`
**Назначение:** Автоматическая настройка таблиц тарифов

**Функции:**
- `ensureSchema()` - проверка и создание всех сущностей
- `ensureTariffTable()` - создание таблицы тарифов
- `ensureOrderSupport()` - поддержка полей в заказах
- `seedDefaults()` - заполнение дефолтными значениями

**Особенности:**
- Автоматическое создание таблицы при отсутствии
- Добавление недостающих колонок
- Создание индексов
- Заполнение дефолтными тарифами
- Безопасное обновление схемы

##### `SchemaOrgGenerator.php`
**Назначение:** Генерация Schema.org микроразметки

**Функции:**
- `generateProduct()` - микроразметка товара
- `generateBreadcrumbList()` - хлебные крошки
- `generateOrganization()` - организация
- `generateItemList()` - список товаров

**Типы схем:**
- Product: товар
- Offer: предложение
- BreadcrumbList: хлебные крошки
- Organization: организация
- AggregateRating: рейтинг
- Review: отзывы
- ItemList: список товаров

**Использование:**
- CatalogController (карточка товара)
- CatalogSeoTrait (страницы каталога)
- SEO оптимизация

##### `CurrencyService.php`
**Назначение:** Управление валютными курсами

**Функции:**
- `getCnyToBynRate()` - получение курса CNY к BYN
- `convertCnyToByn()` - конвертация юаней в BYN
- `updateRate()` - обновление курса вручную
- `getRateFromApi()` - получение курса из внешнего API

**Настройки:**
- cnyToBynRate: курс по умолчанию (0.45 BYN за 1 CNY)
- cacheDuration: время жизни кэша
- retryAttempts: количество попыток API

**Использование:**
- TariffController (калькулятор цен)
- OrderController (расчёт стоимости)
- ProductController (конвертация цен)

##### `SentryErrorHandler.php`
**Назначение:** Интеграция с Sentry для отслеживания ошибок

**Функции:**
- Автоматическая отправка ошибок в Sentry
- Фильтрация 404, 403, 401 ошибок
- Добавление контекста пользователя
- Трекинг релизов
- Performance monitoring

**Настройка:**
- SENTRY_DSN: DSN проекта Sentry
- SENTRY_ENVIRONMENT: окружение (production, staging)
- SENTRY_TRACES_SAMPLE_RATE: частота сэмплирования

**Использование:**
- В config/web.php как errorHandler

##### `PoizonApiService.php`
**Назначение:** Интеграция с API Poizon (Dewu)

**Функции:**
- Получение списка товаров обуви
- Проверка наличия размеров
- Получение актуальных цен
- Конвертация размеров (US/EU/UK/CM)
- Импорт товаров в каталог

**Настройки:**
- apiUrl: URL API Poizon
- apiKey: API ключ
- timeout: таймаут запросов

**Использование:**
- PoizonController/admin (импорт товаров)
- Консольные команды импорта
- Синхронизация цен и остатков

**Особенности:**
- Поддержка сторонних сервисов парсинга
- Retry механизм при ошибках
- Кэширование ответов API

##### `SitemapAutoGenerator.php`
**Назначение:** Автоматическая генерация sitemap

**Функции:**
- Автоматическая генерация после изменения контента
- Проверка интервала для снижения нагрузки
- Интеграция с SitemapNotifier

**Использование:**
- В config/web.php как bootstrap компонент

**Особенности:**
- Отложенная генерация после запроса
- Проверка интервала для снижения нагрузки
- Интеграция с SitemapNotifier

##### `Settings.php`
**Назначение:** Централизованный доступ к настройкам системы

**Функции:**
- `getCompany()` - реквизиты компании (название, УНП, адрес, контакты)
- `getStatuses()` - список статусов заказов
- `getLogistStatuses()` - статусы, доступные логистам
- `get($key, $default)` - получение настройки по ключу

**Кэширование:**
- Реквизиты компании кэшируются в памяти
- Статусы кэшируются в памяти

**Использование:**
- Yii::$app->settings->getCompany()
- Yii::$app->settings->getStatuses()

**Особенности:**
- Singleton pattern для настроек
- Автоматическое кэширование

##### `CdnHelper.php`
**Назначение:** Хелпер для работы с CDN

**Функции:**
- `getUrl()` - получить URL изображения через CDN
- `getUrlWithSize()` - получить URL изображения с размером
- `purgeCache()` - очистка кэша CDN

**Поддержка:**
- CloudFlare
- BunnyCDN
- AWS CloudFront

**Особенности:**
- Автоматическая конвертация WebP
- Responsive images
- Purge cache по API

##### `HttpCacheHeaders.php`
**Назначение:** Управление HTTP кэш-заголовками

**Функции:**
- `setCacheHeaders()` - установка Cache-Control заголовков
- `setETag()` - установка ETag
- `setLastModified()` - установка Last-Modified

**Профили кэширования:**
- PROFILE_PUBLIC_LONG: 1 год
- PROFILE_PUBLIC_MEDIUM: 1 месяц
- PROFILE_PUBLIC_SHORT: 1 час
- PROFILE_PRIVATE: private, no-cache

**Особенности:**
- Готовые профили кэширования
- Поддержка CDN
- Conditional requests (304 Not Modified)

##### `AssetOptimizer.php`
**Назначение:** Оптимизация загрузки CSS/JS

**Функции:**
- `optimizeCatalogPage()` - оптимизация страницы каталога
- `preloadFonts()` - предзагрузка шрифтов
- `preloadCriticalAssets()` - предзагрузка критических ресурсов

**Конфигурация:**
- CRITICAL_CSS_FILE: путь к критическому CSS
- DEFERRED_CSS: список отложенных CSS
- SCRIPTS_CONFIG: конфигурация JS файлов

**Использование:**
- AssetOptimizer::optimizeCatalogPage($this);
- AssetOptimizer::preloadFonts($this);

**Особенности:**
- First Paint оптимизация
- Core Web Vitals улучшение
- Lazy loading для некритичных ресурсов

---

#### `backend/shared/helpers/` - Хелперы

##### `ImageHelper.php`
**Назначение:** Хелпер для работы с изображениями

**Функции:**
- `resize()` - изменение размера изображения
- `crop()` - обрезка изображения
- `convertToWebP()` - конвертация в WebP
- `optimize()` - оптимизация изображения

##### `LazyLoadHelper.php`
**Назначение:** Хелпер для lazy loading

**Функции:**
- `generateAttributes()` - генерация атрибутов для lazy loading
- `generatePlaceholder()` - генерация placeholder

##### `ProductCardHelper.php`
**Назначение:** Хелпер для карточки товара

**Функции:**
- `generateCard()` - генерация HTML карточки товара
- `generatePrice()` - форматирование цены
- `generateDiscount()` - отображение скидки

##### `SizeConverter.php`
**Назначение:** Конвертация размеров

**Функции:**
- `usToEu()` - конвертация US в EU
- `ukToEu()` - конвертация UK в EU
- `cmToEu()` - конвертация CM в EU

---

#### `backend/shared/traits/` - Traits

##### `CatalogFiltersTrait.php`
**Назначение:** Методы фильтрации каталога

**Функции:**
- `applyFilters()` - применение фильтров к запросу
- `getFiltersData()` - получение данных для фильтров
- `getCachedCount()` - кэшированный COUNT запрос
- `shouldBypassCatalogCache()` - проверка обхода кэша
- `normalizeFilterList()` - нормализация списка фильтров

**Связи:**
- Product (модель товара)
- FilterBuilder (построитель фильтров)
- CacheManager (менеджер кэша)

**Использование:**
Подключить в CatalogController через "use CatalogFiltersTrait;"

**Особенности:**
- Делегирует логику в FilterBuilder для единого источника истины
- Кэширование COUNT запросов для производительности
- Поддержка фильтрации по характеристикам

##### `CatalogSeoTrait.php`
**Назначение:** SEO методы для каталога

**Функции:**
- `registerMetaTags()` - регистрация мета-тегов
- `generateFilteredDescription()` - генерация динамического описания
- `generateFilteredTitle()` - генерация динамического заголовка
- `getFirstProductImage()` - получение первого изображения товара
- `generateProductUTP()` - генерация УТП для соцсетей
- `registerSchemaItemList()` - регистрация Schema.org ItemList
- `registerSchemaBreadcrumbs()` - регистрация хлебных крошек
- `registerSchemaWebSite()` - регистрация Schema.org WebSite
- `registerPaginationLinks()` - регистрация rel prev/next

**Связи:**
- Brand (модель бренда)
- Category (модель категории)
- SmartFilter (компонент умного фильтра)

**Использование:**
Подключить в CatalogController через "use CatalogSeoTrait;"

**Особенности:**
- Canonical URL без trailing slash (SEO best practice)
- Динамические описания на основе активных фильтров
- Поддержка Open Graph и Twitter Cards
- JSON-LD для Google Shopping

---

#### `backend/shared/mail/` - Шаблоны писем

**Шаблоны:**
- `catalog-inquiry-customer.php` - письмо клиенту о запросе по товару
- `catalog-inquiry-manager.php` - письмо менеджеру о запросе по товару
- `order-created.php` - письмо о создании заказа
- `order-created-text.php` - текстовая версия письма о заказе
- `payment-uploaded.php` - письмо о загрузке чека оплаты
- `payment-uploaded-text.php` - текстовая версия письма об оплате
- `layouts/` - макеты писем

---

## Frontend (`frontend/`)

### Назначение
Публичная часть приложения (web root), шаблоны, ассеты.

### `frontend/views/` - Шаблоны

#### `catalog/` - Шаблоны каталога
- `index.php` - Главная страница каталога
- `product.php` - Карточка товара
- `favorites.php` - Избранные товары
- `history.php` - История просмотров
- `_characteristic_filter.php` - Фильтр по характеристикам
- `_active_filters.php` - Активные фильтры

#### `cart/` - Шаблоны корзины
- `index.php` - Страница корзины

#### `checkout/` - Шаблоны оформления заказа
- `index.php` - Страница оформления заказа

#### `account/` - Шаблоны личного кабинета
- `login.php` - Страница входа
- `register.php` - Страница регистрации
- `profile.php` - Профиль пользователя
- `orders.php` - История заказов
- `order-view.php` - Просмотр заказа
- `settings.php` - Настройки
- `forgot-password.php` - Восстановление пароля
- `_auth-style.php` - Стили авторизации

#### `admin/` - Шаблоны админ-панели
- `dashboard/index.php` - Дашборд
- `dashboard/settings.php` - Настройки админки
- `product/index.php` - Список товаров
- `product/edit.php` - Редактирование товара
- `product/view.php` - Просмотр товара
- `product/add-size.php` - Добавление размера
- `product/edit-size.php` - Редактирование размера
- `order/index.php` - Список заказов
- `order/create.php` - Создание заказа
- `order/update.php` - Редактирование заказа
- `order/view.php` - Просмотр заказа
- `order/view-new.php` - Новый просмотр заказа
- `order/_table_rows.php` - Строки таблицы заказов
- `order/_order_items.php` - Товары заказа
- `customer/index.php` - Список клиентов
- `customer/view.php` - Просмотр клиента
- `customer/update.php` - Редактирование клиента
- `poizon/index.php` - Импорт из Poizon
- `poizon/run.php` - Запуск импорта
- `poizon/view.php` - Просмотр импорта
- `poizon/view-log.php` - Лог импорта
- `poizon/errors.php` - Ошибки импорта
- `characteristic/index.php` - Список характеристик
- `characteristic/create.php` - Создание характеристики
- `characteristic/update.php` - Редактирование характеристики
- `characteristic/import.php` - Импорт характеристик
- `characteristic/guide.php` - Руководство по характеристикам
- `characteristic/size-create.php` - Создание размера
- `characteristic/size-update.php` - Редактирование размера
- `size-grid/index.php` - Сетка размеров
- `review/index.php` - Отзывы
- `statistics/index.php` - Статистика
- `analytics/index.php` - Аналитика
- `tariff/index.php` - Тарифы
- `tariff/form.php` - Форма тарифа
- `user/index.php` - Пользователи
- `user/create.php` - Создание пользователя
- `dev-tools/index.php` - Dev Tools
- `_admin-page.php` - Базовая страница админки
- `_order_items.php` - Товары заказа

### `frontend/web/` - Web root

**Структура:**
- `assets/` - Скомпилированные ассеты
- `css/` - CSS файлы
- `js/` - JavaScript файлы
- `images/` - Изображения
- `uploads/` - Загруженные файлы
- `index.php` - Точка входа web-приложения
- `index-prod.php` - Продакшн версия
- `sitemap.xml` - Sitemap
- `robots.txt` - Robots.txt
- `.htaccess` - Конфигурация Apache

---

## Infrastructure (`infrastructure/`)

### Назначение
Инфраструктурный слой: конфигурация, миграции, очереди, мониторинг.

### `infrastructure/config/` - Конфигурация
- `bootstrap.php` - Bootstrap приложения
- `console.php` - Консольное приложение
- `db-local.php` - Локальная БД
- `web.php` - Web приложение
- `params.php` - Параметры
- `test.php` - Тестовая конфигурация
- `test-db.php` - Тестовая БД

### `infrastructure/migrations/` - Миграции БД
- Миграции для создания таблиц
- Миграции для обновления схем
- Миграции для заполнения данными

### `infrastructure/queue/` - Очереди задач
- `redis.php` - Конфигурация Redis
- `rabbitmq.php` - Конфигурация RabbitMQ

### `infrastructure/jobs/` - Задачи для очередей
- `SendEmailJob.php` - Отправка email
- `ProcessPaymentJob.php` - Обработка платежей

### `infrastructure/monitoring/` - Мониторинг
- `metrics.php` - Prometheus метрики
- `health.php` - Health checks

### `infrastructure/logging/` - Логирование
- `structured.php` - Структурированные логи
- `correlation.php` - Correlation ID

### `infrastructure/features/` - Feature Flags
- `flags.php` - Флаги фич
- `FeatureService.php` - Сервис фич

### `infrastructure/runtime/` - Временные файлы
- Логи
- Кэш
- Сессии

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
| `app\shared\components` | `backend/shared/components/` |
| `app\shared\helpers` | `backend/shared/helpers/` |
| `app\infrastructure` | `infrastructure/` |

---

## Точки входа

| Файл | Назначение |
|------|------------|
| `frontend/web/index.php` | Web-приложение |
| `yii` | Консольное приложение |
| `api/v1/` | REST API |

---

## Принципы архитектуры

- **API First** — REST API для всех клиентов
- **Frontend/Backend разделение** — публичная часть отделена от бизнес-логики
- **Views во frontend** — все шаблоны в `frontend/views/`
- **Feature-based модули** — каждая фича в своём модуле
- **Shared ресурсы** — общие компоненты в `backend/shared/`
- **Infrastructure** — конфигурация и миграции изолированы
- **Queue System** — асинхронная обработка
- **Cache Layer** — многоуровневый кэш
- **Search Engine** — быстрый поиск
- **Payment Gateway** — абстракция платежей
- **Security** — защита от атак
- **Feature Flags** — управление фичами

---

## Результаты аудита

### ✅ Исправленные проблемы вайбкодинга

1. **Удалены полные дубликаты файлов:**
   - `backend/modules/catalog/controllers/CatalogFiltersTrait.php` (266 строк)
   - `backend/modules/catalog/controllers/CatalogSeoTrait.php` (438 строк)

2. **Унифицированы namespace в компонентах:**
   - Все компоненты теперь используют `app\shared\components`
   - Обновлены все импорты в проекте

3. **Проверено кэширование:**
   - НЕ дублирование - это правильная слоистая архитектура
   - CacheManager - централизованный менеджер с тегами
   - CategoryCache/ProductCache - специфичные кэши для сущностей
   - CachedProductRepository - декоратор паттерн

4. **Проверены остальные папки:**
   - Дубликатов не обнаружено
   - Структура соответствует архитектуре

### 📊 Статистика проекта

- **Компоненты:** 13 компонентов в `backend/shared/components/`
- **Хелперы:** 4 хелпера в `backend/shared/helpers/`
- **Traits:** 2 трейта в `backend/shared/traits/`
- **Модули:** 5 модулей (account, admin, cart, catalog, checkout)
- **Консольные команды:** 12 команд
- **API контроллеры:** 3 контроллера
- **Админ контроллеры:** 15 контроллеров

---

## Заключение

Проект имеет чистую архитектуру без дублирования кода. Все компоненты правильно организованы и следуют современным принципам разработки. Namespace унифицированы, импорты обновлены, дубликаты удалены.
