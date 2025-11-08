<?php
/**
 * ФИНАЛЬНАЯ ДИАГНОСТИКА: Почему товары не видны
 */

defined('YII_DEBUG') or define('YII_DEBUG', true);
defined('YII_ENV') or define('YII_ENV', 'dev');

require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/vendor/yiisoft/yii2/Yii.php';
require __DIR__ . '/config/bootstrap.php';

$config = require __DIR__ . '/config/console.php';
$application = new yii\console\Application($config);

use app\models\Product;

echo "\n";
echo "╔════════════════════════════════════════════════════════════╗\n";
echo "║  ФИНАЛЬНАЯ ДИАГНОСТИКА: ПРОБЛЕМА С ОТОБРАЖЕНИЕМ ТОВАРОВ   ║\n";
echo "╚════════════════════════════════════════════════════════════╝\n\n";

// 1. Проверка БД
echo "📊 ШАГ 1: ПРОВЕРКА БАЗЫ ДАННЫХ\n";
echo str_repeat("─", 60) . "\n";

$totalCount = Product::find()->count();
$activeCount = Product::find()->where(['is_active' => 1])->count();
$visibleCount = Product::find()
    ->where(['is_active' => 1])
    ->andWhere(['!=', 'stock_status', 'out_of_stock'])
    ->count();

echo "Всего товаров в БД: {$totalCount}\n";
echo "Активных товаров: {$activeCount}\n";
echo "Видимых товаров (не out_of_stock): {$visibleCount}\n";

if ($visibleCount == 0) {
    echo "❌ ПРОБЛЕМА: Нет товаров для отображения!\n";
    echo "   Все товары имеют stock_status = 'out_of_stock'\n";
    exit(1);
} else {
    echo "✅ В БД есть {$visibleCount} товаров для отображения\n";
}

// 2. Проверка запроса контроллера
echo "\n📝 ШАГ 2: ПРОВЕРКА ЗАПРОСА КОНТРОЛЛЕРА\n";
echo str_repeat("─", 60) . "\n";

$query = Product::find()
    ->with(['brand', 'sizes', 'colors'])
    ->select([
        'id', 
        'name', 
        'slug', 
        'brand_id',
        'brand_name',
        'category_name',
        'main_image_url',
        'price', 
        'old_price', 
        'stock_status',
        'is_featured',
        'rating',
        'reviews_count'
    ])
    ->where(['is_active' => 1])
    ->andWhere(['!=', 'stock_status', 'out_of_stock'])
    ->limit(3);

$products = $query->all();
echo "Товаров возвращено контроллером: " . count($products) . "\n";

if (empty($products)) {
    echo "❌ ПРОБЛЕМА: Контроллер не возвращает товары!\n";
    exit(1);
} else {
    echo "✅ Контроллер возвращает товары\n";
}

// 3. Проверка данных товаров
echo "\n🔍 ШАГ 3: ПРОВЕРКА ДАННЫХ ТОВАРОВ\n";
echo str_repeat("─", 60) . "\n";

$hasErrors = false;
foreach ($products as $i => $p) {
    echo "\nТовар #" . ($i + 1) . ":\n";
    echo "  ID: {$p->id}\n";
    echo "  Name: {$p->name}\n";
    echo "  Slug: " . ($p->slug ?: "❌ ПУСТО!") . "\n";
    echo "  Brand ID: {$p->brand_id}\n";
    echo "  Brand relation: " . ($p->brand ? "✅ {$p->brand->name}" : "❌ NULL") . "\n";
    echo "  Brand name (denorm): {$p->brand_name}\n";
    echo "  Image URL: " . ($p->main_image_url ?: "❌ ПУСТО!") . "\n";
    echo "  Price: {$p->price}\n";
    echo "  Sizes: " . count($p->sizes) . "\n";
    
    // Проверяем обязательные поля
    if (empty($p->slug)) {
        echo "  ⚠️  WARNING: Пустой slug!\n";
        $hasErrors = true;
    }
    if (empty($p->main_image_url)) {
        echo "  ⚠️  WARNING: Нет изображения!\n";
        $hasErrors = true;
    }
    if (!$p->brand) {
        echo "  ⚠️  WARNING: Не загружен бренд!\n";
        $hasErrors = true;
    }
}

// 4. Проверка HTML
echo "\n\n🌐 ШАГ 4: ПРОВЕРКА HTML СТРАНИЦЫ\n";
echo str_repeat("─", 60) . "\n";

$html = @file_get_contents('http://localhost:8080/catalog/');
if ($html === false) {
    echo "❌ ОШИБКА: Не удалось загрузить страницу каталога\n";
    echo "   URL: http://localhost:8080/catalog/\n";
    echo "   Проверьте что сервер запущен!\n";
    exit(1);
}

$productCardsCount = substr_count($html, 'class="product product-card');
echo "Карточек товаров в HTML: {$productCardsCount}\n";

if ($productCardsCount == 0) {
    echo "❌ ПРОБЛЕМА: В HTML нет карточек товаров!\n";
    echo "   _products.php не рендерится или пустой\n";
    $hasErrors = true;
} else {
    echo "✅ В HTML есть {$productCardsCount} карточек товаров\n";
}

// Проверяем загружается ли CSS
if (strpos($html, 'catalog-card.css') !== false) {
    echo "✅ CSS файл catalog-card.css подключен\n";
} else {
    echo "❌ CSS файл catalog-card.css НЕ подключен!\n";
    $hasErrors = true;
}

// Проверяем загружается ли JS
if (strpos($html, 'catalog.js') !== false) {
    echo "✅ JS файл catalog.js подключен\n";
} else {
    echo "❌ JS файл catalog.js НЕ подключен!\n";
    $hasErrors = true;
}

// 5. Итоговый диагноз
echo "\n\n" . str_repeat("═", 60) . "\n";
echo "📋 ИТОГОВЫЙ ДИАГНОЗ\n";
echo str_repeat("═", 60) . "\n";

if (!$hasErrors && $productCardsCount > 0) {
    echo "✅ БЭК РАБОТАЕТ КОРРЕКТНО!\n\n";
    echo "Товары загружаются из БД и рендерятся в HTML.\n";
    echo "Карточек в HTML: {$productCardsCount}\n\n";
    echo "🔍 ПРОБЛЕМА СКОРЕЕ ВСЕГО В ФРОНТЕНДЕ:\n";
    echo "   1. CSS не загружается или содержит ошибки\n";
    echo "   2. JavaScript скрывает товары\n";
    echo "   3. Браузер кэширует старую версию\n\n";
    echo "🛠️  РЕШЕНИЕ:\n";
    echo "   1. Откройте http://localhost:8080/catalog/ в браузере\n";
    echo "   2. Откройте DevTools (F12)\n";
    echo "   3. Проверьте консоль на ошибки JavaScript\n";
    echo "   4. Проверьте Network → загрузился ли catalog-card.css\n";
    echo "   5. Откройте Elements → найдите .product-card\n";
    echo "   6. Проверьте Computed стили → display, visibility, opacity\n\n";
    echo "📌 ТЕСТОВЫЕ СТРАНИЦЫ:\n";
    echo "   • http://localhost:8080/catalog-test-minimal.html\n";
    echo "   • http://localhost:8080/test-catalog-visual.html\n\n";
} else {
    echo "❌ ОБНАРУЖЕНЫ ПРОБЛЕМЫ В БЭКЕНДЕ!\n\n";
    if ($productCardsCount == 0) {
        echo "Товары не рендерятся в HTML.\n";
        echo "Проверьте файл: /views/catalog/_products.php\n\n";
    }
    if ($hasErrors) {
        echo "У некоторых товаров отсутствуют обязательные данные.\n";
        echo "Проверьте данные в БД и модели Product.\n\n";
    }
}

echo str_repeat("═", 60) . "\n\n";
