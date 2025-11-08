<?php
/**
 * ДИАГНОСТИКА: Проверка наличия товаров в каталоге
 */

// Используем Yii autoloader
defined('YII_DEBUG') or define('YII_DEBUG', true);
defined('YII_ENV') or define('YII_ENV', 'dev');

require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/vendor/yiisoft/yii2/Yii.php';
require __DIR__ . '/config/bootstrap.php';

$config = require __DIR__ . '/config/console.php';
$application = new yii\console\Application($config);

use app\models\Product;
use app\models\Brand;
use app\models\Category;

echo "=== ДИАГНОСТИКА КАТАЛОГА ===\n\n";

// 1. Общее количество товаров
$totalProducts = Product::find()->count();
echo "1. Всего товаров в БД: {$totalProducts}\n";

// 2. Активные товары
$activeProducts = Product::find()->where(['is_active' => 1])->count();
echo "2. Активные товары (is_active = 1): {$activeProducts}\n";

// 3. Товары в наличии
$inStockProducts = Product::find()
    ->where(['is_active' => 1])
    ->andWhere(['!=', 'stock_status', Product::STOCK_OUT_OF_STOCK])
    ->count();
echo "3. Активные товары в наличии (не out_of_stock): {$inStockProducts}\n";

// 4. Статусы stock_status
echo "\n4. Распределение по stock_status:\n";
$statuses = Product::find()
    ->select(['stock_status', 'COUNT(*) as count'])
    ->groupBy('stock_status')
    ->asArray()
    ->all();
foreach ($statuses as $status) {
    echo "   - {$status['stock_status']}: {$status['count']}\n";
}

// 5. Активные бренды с товарами
$brandsCount = Brand::find()
    ->where(['is_active' => 1])
    ->count();
echo "\n5. Активные бренды: {$brandsCount}\n";

// 6. Активные категории с товарами
$categoriesCount = Category::find()
    ->where(['is_active' => 1])
    ->count();
echo "6. Активные категории: {$categoriesCount}\n";

// 7. Примеры товаров
echo "\n7. Первые 5 активных товаров:\n";
$products = Product::find()
    ->where(['is_active' => 1])
    ->limit(5)
    ->all();

if (empty($products)) {
    echo "   ❌ НЕТ АКТИВНЫХ ТОВАРОВ!\n";
} else {
    foreach ($products as $p) {
        echo "   - ID: {$p->id}, Название: {$p->name}, Stock: {$p->stock_status}, Активен: {$p->is_active}\n";
    }
}

// 8. Проверка запроса из контроллера
echo "\n8. Запрос из CatalogController::actionIndex():\n";
$query = Product::find()
    ->select([
        'id', 
        'name', 
        'slug', 
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
    ->where([
        'is_active' => 1,
    ])
    ->andWhere(['!=', 'stock_status', Product::STOCK_OUT_OF_STOCK]);

$count = $query->count();
echo "   Количество товаров по запросу каталога: {$count}\n";

if ($count > 0) {
    echo "   ✅ Товары есть - проблема в другом месте!\n";
    
    // Проверим первый товар
    $firstProduct = $query->one();
    echo "\n   Первый товар:\n";
    echo "   - ID: {$firstProduct->id}\n";
    echo "   - Название: {$firstProduct->name}\n";
    echo "   - Brand: {$firstProduct->brand_name}\n";
    echo "   - Category: {$firstProduct->category_name}\n";
    echo "   - Price: {$firstProduct->price}\n";
    echo "   - Main Image: {$firstProduct->main_image_url}\n";
    echo "   - Stock: {$firstProduct->stock_status}\n";
} else {
    echo "   ❌ Товары НЕ ПРОХОДЯТ фильтр каталога!\n";
    
    // Проверяем причину
    echo "\n   Проверка причины:\n";
    
    $onlyActive = Product::find()->where(['is_active' => 1])->count();
    echo "   - Товары с is_active=1: {$onlyActive}\n";
    
    $withStockCheck = Product::find()
        ->where(['is_active' => 1])
        ->andWhere(['!=', 'stock_status', Product::STOCK_OUT_OF_STOCK])
        ->count();
    echo "   - Товары с is_active=1 И stock_status != 'out_of_stock': {$withStockCheck}\n";
    
    // Проверим какие stock_status у активных товаров
    echo "\n   Stock status активных товаров:\n";
    $activeStatuses = Product::find()
        ->select(['stock_status', 'COUNT(*) as count'])
        ->where(['is_active' => 1])
        ->groupBy('stock_status')
        ->asArray()
        ->all();
    foreach ($activeStatuses as $s) {
        echo "   - {$s['stock_status']}: {$s['count']}\n";
    }
}

// 9. Проверка связей товаров с брендами
echo "\n9. Проверка связей товаров с брендами:\n";
$products = Product::find()
    ->with(['brand', 'sizes', 'colors'])
    ->where(['is_active' => 1])
    ->limit(3)
    ->all();

foreach ($products as $p) {
    echo "   - ID: {$p->id}, Название: {$p->name}\n";
    echo "     Brand ID: {$p->brand_id}, Brand объект: " . ($p->brand ? 'ДА' : 'НЕТ') . "\n";
    if ($p->brand) {
        echo "     Brand name (relation): {$p->brand->name}\n";
    }
    echo "     Brand name (denorm): {$p->brand_name}\n";
    echo "     Sizes count: " . count($p->sizes) . "\n";
    echo "     Colors count: " . count($p->colors) . "\n";
    echo "     Main image URL: {$p->main_image_url}\n";
    echo "     getMainImageUrl(): " . $p->getMainImageUrl() . "\n\n";
}

echo "\n=== КОНЕЦ ДИАГНОСТИКИ ===\n";
