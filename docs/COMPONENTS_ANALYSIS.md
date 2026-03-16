# Анализ компонентов и выявление проблем

## Структура компонентов

### Layouts
```
frontend/views/layouts/
├── main.php          # Основной layout
└── public.php        # Публичный layout
```

### Partials
```
frontend/views/partials/
├── footer.php        # Footer
├── header.php        # Header
└── ...
```

### Catalog Components
```
frontend/views/catalog/
├── index.php         # Главная страница каталога
├── _products.php     # Partial для списка товаров
├── _product_card.php # Partial для карточки товара
├── _characteristic_filter.php # Partial для фильтра характеристик
├── favorites.php     # Избранное
└── history.php       # История просмотров
```

## Выявленные проблемы

### 1. Layouts

#### Проблема: Дублирование подключения CSS в layouts/main.php
```php
// ❌ НЕПРАВИЛЬНО: Дублирование CSS
<?= Html::cssFile('@web/css/dark-mode.css', ['depends' => [AppAsset::class]]) ?>
<?= Html::cssFile('@web/css/accessibility.css', ['depends' => [AppAsset::class]]) ?>
<?= Html::cssFile('@web/css/micro-interactions.css', ['depends' => [AppAsset::class]]) ?>
```

**Решение:** Удалить эти строки - они уже подключены в AppAsset

#### Проблема: Использование старых путей
```php
// ❌ Старый путь
<img src="/images/logo.png" alt="...">
```

**Решение:** Использовать новый путь `/frontend/images/logo.png`

### 2. Partials

#### Проблема: Footer использует старые пути
```php
// ❌ Старый путь
<img src="/images/logo-white.png" alt="СНИКЕРХЭД" class="footer-logo">
```

**Решение:** Обновить путь

#### Проблема: Нет единого стиля для partials
- Некоторые partials используют snake_case (_product_card.php)
- Некоторые используют camelCase
- Нет единых правил именования

### 3. Catalog Components

#### Проблема: _products.php зависит от глобального состояния
```php
// ❌ НЕПРАВИЛЬНО: Зависимость от $_GET
$selectedSizesParam = Yii::$app->request->get('sizes');
$currentSizeSystem = Yii::$app->request->get('size_system', ProductCardHelper::DEFAULT_SIZE_SYSTEM);
```

**Решение:** Передавать параметры явно через render()

#### Проблема: _characteristic_filter.php смешивает логику и представление
```php
// ❌ Логика в view
$valueId = $value['id'];
$isChecked = is_array($currentValues) && in_array($valueId, $currentValues);
```

**Решение:** Вынести логику в Helper или Controller

#### Проблема: Нет компонента для фильтров
- Фильтры рендерятся прямо в index.php
- Нет отдельного компонента FilterWidget

### 4. Product Cards

#### Проблема: Смешивание inline стилей и CSS
```php
// ❌ Inline стили в catalog/index.php
<div style="width: 100%;">
    <main class="content" style="max-width: 100%;">
```

**Решение:** Удалить все inline стили, использовать только CSS классы

#### Проблема: Нет компонента ProductCardWidget
- Карточки рендерятся через partial
- Нет возможности переиспользовать в других местах

### 5. Формы

#### Проблема: Нет единого стиля для форм
- Формы в checkout, cart, catalog используют разные стили
- Нет компонента ActiveFormWidget

### 6. Модальные окна

#### Проблема: Модальные окна рендерятся inline
- Search Modal в layout/main.php
- Quick View Modal в catalog
- Нет компонента ModalWidget

### 7. Header

#### Проблема: Дублирование header
- Есть header в layout/main.php
- Есть ecom-header в другом layout
- Нет единого компонента HeaderWidget

### 8. Пагинация

#### Проблема: Использование Yii2 LinkPager без кастомизации
```php
// ❌ Стандартный LinkPager
<?= LinkPager::widget([
    'pagination' => $pagination,
    'prevPageLabel' => '<i class="bi bi-chevron-left"></i>',
    'nextPageLabel' => '<i class="bi bi-chevron-right"></i>',
]) ?>
```

**Решение:** Создать PaginationWidget с кастомным дизайном

## Рекомендации по рефакторингу

### 1. Создать компоненты (Widgets)

```
frontend/widgets/
├── HeaderWidget.php          # Header
├── FooterWidget.php          # Footer
├── ProductCardWidget.php     # Карточка товара
├── FilterWidget.php          # Фильтры каталога
├── PaginationWidget.php      # Пагинация
├── ModalWidget.php           # Модальные окна
├── SearchWidget.php          # Поиск
└── ActiveFormWidget.php      # Формы
```

### 2. Унифицировать partials

```
frontend/views/partials/
├── header.php
├── footer.php
├── breadcrumbs.php
├── meta-tags.php
└── scripts.php
```

### 3. Удалить inline стили

```php
// ❌ НЕПРАВИЛЬНО
<div style="width: 100%;">

// ✅ ПРАВИЛЬНО
<div class="full-width">
```

### 4. Использовать BEM命名

```css
/* ❌ Старый стиль */
.product-card { }
.product-image { }

/* ✅ BEM */
.product-card { }
.product-card__image { }
.product-card--featured { }
```

### 5. Создать Helper классы

```
frontend/helpers/
├── ProductCardHelper.php     # Уже есть
├── FilterHelper.php          # Новый
├── PaginationHelper.php      # Новый
└── MetaHelper.php           # Новый
```

## Приоритеты

### Высокий приоритет
1. ✅ Удалить inline стили из view файлов
2. ✅ Обновить пути к изображениям
3. ✅ Объединить CSS файлы
4. ✅ Создать HeaderWidget и FooterWidget

### Средний приоритет
1. Создать ProductCardWidget
2. Создать FilterWidget
3. Создать PaginationWidget
4. Унифицировать partials

### Низкий приоритет
1. Создать ModalWidget
2. Создать SearchWidget
3. Создать ActiveFormWidget

## Итог

**Критичные проблемы:**
1. Дублирование CSS в layouts
2. Inline стили в view файлах
3. Зависимость от глобального состояния в partials
4. Отсутствие компонентов для переиспользования

**Рекомендуемые действия:**
1. Обновить layouts/main.php - удалить дублирование CSS
2. Создать базовые Widgets (Header, Footer, ProductCard)
3. Удалить все inline стили
4. Обновить пути к изображениям
5. Создать Helper классы для бизнес-логики
