# Исправление ошибок на http://localhost:8080/catalog

## Проблема ✅

### Ошибка:
```
Invalid Configuration – yii\base\InvalidConfigException
The directory does not exist:
```

### Причина:
AssetBundle использовали неправильную конфигурацию:
- `basePath = '@webroot'` - но файлы находились в `frontend/`
- `VersionedAssetBundle` не поддерживал `sourcePath`

## Решение ✅

### 1. Исправление AssetBundle

**Было:**
```php
public $basePath = '@webroot';
public $baseUrl = '@web';
```

**Стало:**
```php
public $sourcePath = '@frontend';  // Источник файлов
public $baseUrl = '@web';
```

### 2. Исправление VersionedAssetBundle

**Было:**
```php
if ($this->baseUrl && $this->basePath) {
    $this->appendTimestamp();
}
```

**Стало:**
```php
if ($this->baseUrl && ($this->basePath || $this->sourcePath)) {
    $this->appendTimestamp();
}
```

### 3. Создание символической ссылки

```bash
cd frontend/web && ln -sf ../assets assets
```

## Исправленные файлы:

### AssetBundle
- `frontend/assets/AppAsset.php`
- `frontend/assets/CatalogAsset.php`
- `frontend/assets/ProductAsset.php`
- `frontend/assets/CartAsset.php`
- `frontend/assets/CheckoutAsset.php`
- `frontend/assets/LandingAsset.php`

### VersionedAssetBundle
- `frontend/assets/VersionedAssetBundle.php`

### JS файлы
- `CatalogAsset`: `js/catalog-filters.js` → `js/catalog.js`
- `ProductAsset`: `js/dist/product-bundle.min.js` → `js/product-page.js`

## Результат ✅

### Страницы работают:
- ✅ http://localhost:8080/ - Главная
- ✅ http://localhost:8080/catalog - Каталог
- ✅ http://localhost:8080/cart - Корзина

### CSS файлы загружаются:
```
/assets/baced7cc/css/core/critical.css?v=1773599529
/assets/baced7cc/css/core/critical-inline.css?v=1773599529
/assets/baced7cc/css/core/container-system.css?v=1773599529
/assets/baced7cc/css/core/design-tokens.css?v=1773599529
/assets/baced7cc/css/core/design-system.css?v=1773599529
/assets/baced7cc/css/components/header-adaptive.css?v=1773599526
/assets/baced7cc/css/components/mobile-menu.css?v=1773599526
/assets/baced7cc/css/components/mega-menu.css?v=1773599526
/assets/baced7cc/css/layout/public-layout.css?v=1773599530
```

### JS файлы загружаются:
```
/js/dark-mode.js
/assets/7af08f8b/jquery.js?v=1773545290
/assets/8e6e089b/yii.js?v=1773545290
/assets/baced7cc/js/mobile-menu.js?v=1773545291
/assets/baced7cc/js/catalog.js?v=1773545291
```

### Автоматическое версионирование работает:
- CSS файлы имеют `?v=timestamp`
- JS файлы имеют `?v=timestamp`
- Кэш браузера сбрасывается при изменении файлов

## Финальная структура ✅

```
frontend/
├── assets/                   # AssetBundle + Published assets
│   ├── AppAsset.php         # sourcePath = '@frontend'
│   ├── CatalogAsset.php     # sourcePath = '@frontend'
│   ├── ProductAsset.php     # sourcePath = '@frontend'
│   ├── CartAsset.php        # sourcePath = '@frontend'
│   ├── CheckoutAsset.php    # sourcePath = '@frontend'
│   ├── LandingAsset.php     # sourcePath = '@frontend'
│   └── VersionedAssetBundle.php # Исправлен для sourcePath
├── css/                      # Стили (46 файлов)
├── js/                       # JavaScript (23 файла)
├── images/                   # Изображения
├── uploads/                  # Загруженные файлы
├── widgets/                  # Виджеты
├── controllers/              # Контроллеры
├── views/                    # View файлы
└── web/                      # Web root
    ├── index.php
    ├── assets/               # Символическая ссылка на ../assets
    └── cache/
```

## Заключение

Все ошибки исправлены:
- ✅ AssetBundle конфигурация исправлена
- ✅ VersionedAssetBundle поддерживает sourcePath
- ✅ Символическая ссылка создана
- ✅ CSS и JS файлы загружаются
- ✅ Автоматическое версионирование работает
- ✅ Страницы отображаются корректно

Система готова к использованию!
