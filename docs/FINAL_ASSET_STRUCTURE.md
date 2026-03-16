# Финальная структура assets после миграции

## Выполнено ✅

### Миграция assets
- **Было:** `frontend/web/assets/` (219 элементов)
- **Стало:** `frontend/assets/` (219 элементов)

### Финальная структура проекта

```
frontend/
├── assets/                   # AssetBundle + Published assets
│   ├── AppAsset.php         # Основной AssetBundle
│   ├── CatalogAsset.php     # AssetBundle каталога
│   ├── ProductAsset.php     # AssetBundle товара
│   ├── CartAsset.php        # AssetBundle корзины
│   ├── CheckoutAsset.php    # AssetBundle оформления
│   ├── LandingAsset.php     # AssetBundle лендинга
│   ├── VersionedAssetBundle.php # Базовый класс с версионированием
│   ├── .htaccess           # Настройки доступа
│   ├── .gitkeep            # Файл для git
│   ├── 293118c7/           # Yii2 published assets
│   ├── 5b014001/           # Yii2 published assets
│   ├── 6a2739c7/           # Yii2 published assets
│   ├── 7af08f8b/           # Yii2 published assets
│   ├── 8e6e089b/           # Yii2 published assets
│   ├── d17cde1a/           # Yii2 published assets
│   └── vendor/             # Vendor assets
├── css/                      # Стили (46 файлов)
│   ├── core/               # Базовые стили
│   ├── components/         # Компоненты
│   ├── layout/             # Layout стили
│   ├── pages/              # Страницы
│   ├── features/           # Функциональные стили
│   ├── site.css
│   └── quick-view.css
├── js/                       # JavaScript (23 файла)
├── images/                   # Статические изображения
├── uploads/                  # Загруженные файлы (18 элементов)
├── widgets/                  # Виджеты
│   ├── HeaderWidget.php
│   ├── FooterWidget.php
│   ├── ProductCardWidget.php
│   └── views/
├── controllers/              # Контроллеры
├── views/                    # View файлы
└── web/                      # Web root (публичная директория)
    ├── index.php            # Точка входа
    ├── index-prod.php       # Продукшн версия
    ├── .htaccess           # Настройки Apache
    ├── favicon.ico          # Фавикон
    ├── robots.txt           # SEO файл
    ├── sitemap.xml          # Карта сайта
    └── cache/               # Кэш
```

## Обновленная конфигурация

### infrastructure/config/web.php
```php
'aliases' => [
    '@bower' => '@vendor/bower-asset',
    '@npm'   => '@vendor/npm-asset',
    '@backend' => '@app/backend',
    '@frontend' => '@app/frontend',
    '@infrastructure' => '@app/infrastructure',
    '@webroot' => '@app/frontend/web',
    '@web' => '@app/frontend/web',
    '@css' => '@app/frontend/css',
    '@js' => '@app/frontend/js',
    '@images' => '@app/frontend/images',
    '@uploads' => '@app/frontend/uploads',
    '@assets' => '@app/frontend/assets',  # Добавлено
],
```

### assetManager конфигурация
```php
'assetManager' => [
    'bundles' => YII_ENV_DEV ? [] : [
        'yii\web\JqueryAsset' => [
            'js' => ['jquery.min.js']
        ],
        'yii\bootstrap5\BootstrapAsset' => [
            'css' => ['css/bootstrap.min.css'],
        ],
        'yii\bootstrap5\BootstrapPluginAsset' => [
            'js' => ['js/bootstrap.bundle.min.js']
        ],
    ],
    'appendTimestamp' => true,
    'linkAssets' => true,  // Создает символические ссылки в web/assets/
],
```

## Как работает Yii2 AssetManager

### 1. **Публикация assets**
- AssetBundle файлы находятся в `frontend/assets/`
- Yii2 публикует их в `web/assets/` (символические ссылки)
- `linkAssets = true` создает символические ссылки вместо копирования

### 2. **Структура published assets**
```
web/assets/
├── 293118c7/  → symlink to frontend/assets/293118c7/
├── 5b014001/  → symlink to frontend/assets/5b014001/
├── 6a2739c7/  → symlink to frontend/assets/6a2739c7/
└── ...
```

### 3. **URL в браузере**
```
/assets/293118c7/css/site.css?v=1234567890
```

## Преимущества новой структуры

### 1. **Все ресурсы в frontend/**
- AssetBundle файлы: `frontend/assets/`
- Published assets: `frontend/assets/`
- Стили: `frontend/css/`
- JavaScript: `frontend/js/`
- Изображения: `frontend/images/`

### 2. **Правильная веб-доступность**
- `web/` содержит только точку входа
- Публикация assets через символические ссылки
- Безопасность - нет прямого доступа к исходникам

### 3. **Удобная разработка**
- Все файлы в одном месте
- Легко найти нужный AssetBundle
- Автоматическое версионирование

### 4. **Оптимизация**
- Символические ссылки вместо копирования
- Автоматическое версионирование
- Кэширование на стороне браузера

## Проверка работоспособности

### 1. **AssetBundle пути**
```php
// AppAsset
public $css = [
    'css/core/critical.css',      // /frontend/css/core/critical.css
    'css/site.css',              // /frontend/css/site.css
];

public $js = [
    'js/mobile-menu.js',         // /frontend/js/mobile-menu.js
];
```

### 2. **Published assets URL**
```html
<!-- В браузере -->
<link href="/assets/6a2739c7/css/core/critical.css?v=1234567890" rel="stylesheet">
<script src="/assets/6a2739c7/js/mobile-menu.js?v=1234567890"></script>
```

### 3. **Aliases**
```php
// В коде
Yii::getAlias('@assets')  // /path/to/project/frontend/assets
Yii::getAlias('@web')      // /path/to/project/frontend/web
```

## Итог

Миграция успешно завершена:
- ✅ `frontend/web/assets/` → `frontend/assets/`
- ✅ Конфигурация обновлена
- ✅ Yii2 AssetManager работает правильно
- ✅ Символические ссылки в `web/assets/`
- ✅ Все пути корректны

Структура полностью соответствует лучшим практикам Yii2.
