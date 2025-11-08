<?php
/**
 * DEBUG: Что передается в view каталога
 */

defined('YII_DEBUG') or define('YII_DEBUG', true);
defined('YII_ENV') or define('YII_ENV', 'dev');

require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/vendor/yiisoft/yii2/Yii.php';
require __DIR__ . '/config/bootstrap.php';

$config = require __DIR__ . '/config/web.php';
$application = new yii\web\Application($config);

use app\models\Product;
use app\models\Brand;
use app\models\Category;
use yii\data\Pagination;

echo "=== DEBUG: CATALOG CONTROLLER DATA ===\n\n";

// Повторяем логику из CatalogController::actionIndex()
$query = Product::find()
    ->with([
        'brand',
        'sizes' => function($query) {
            $query->select(['id', 'product_id', 'size', 'price_byn', 'is_available', 'eu_size', 'us_size', 'uk_size', 'cm_size'])
                  ->where(['is_available' => 1])
                  ->orderBy(['size' => SORT_ASC]);
        },
        'colors' => function($query) {
            $query->select(['id', 'product_id', 'name', 'hex']);
        }
    ])
    ->select([
        'id', 
        'name', 
        'slug', 
        'brand_id',        // ВАЖНО: нужен для связи with(['brand'])
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

$totalCount = $query->count();
echo "Total count: {$totalCount}\n\n";

$pagination = new Pagination([
    'defaultPageSize' => 24,
    'totalCount' => $totalCount,
]);

$products = $query
    ->offset($pagination->offset)
    ->limit($pagination->limit)
    ->all();

echo "Products loaded: " . count($products) . "\n";
echo "Pagination offset: {$pagination->offset}\n";
echo "Pagination limit: {$pagination->limit}\n";
echo "Pagination totalCount: {$pagination->totalCount}\n";
echo "Pagination pageCount: {$pagination->pageCount}\n\n";

// Проверим передается ли $products в view
echo "Sample products data:\n";
foreach (array_slice($products, 0, 3) as $p) {
    echo "  - ID: {$p->id}, Name: {$p->name}\n";
    echo "    Brand ID: {$p->brand_id}\n";
    echo "    Brand object: " . ($p->brand ? 'YES' : 'NULL') . "\n";
    if ($p->brand) {
        echo "    Brand name (relation): {$p->brand->name}\n";
    }
    echo "    Brand name (denorm): {$p->brand_name}\n";
    echo "    Image URL: {$p->main_image_url}\n";
    echo "    Price: {$p->price}\n";
    echo "    Sizes: " . count($p->sizes) . "\n";
    echo "    Colors: " . count($p->colors) . "\n\n";
}

// Симулируем рендер _products.php
echo "\n=== SIMULATING _products.php RENDER ===\n\n";

if (empty($products)) {
    echo "EMPTY MESSAGE WOULD BE SHOWN\n";
} else {
    echo "FOREACH WOULD RENDER " . count($products) . " PRODUCTS\n";
    
    foreach (array_slice($products, 0, 3) as $product) {
        echo "\n--- Product Card ---\n";
        echo "URL: " . $product->getUrl() . "\n";
        echo "Image: " . $product->getMainImageUrl() . "\n";
        echo "Name: " . htmlspecialchars($product->name) . "\n";
        if ($product->brand) {
            echo "Brand: " . htmlspecialchars($product->brand->name) . "\n";
        }
        echo "Price: " . Yii::$app->formatter->asCurrency($product->price, 'BYN') . "\n";
        echo "Has sizes: " . (!empty($product->sizes) ? 'YES (' . count($product->sizes) . ')' : 'NO') . "\n";
        echo "Has colors: " . (!empty($product->colors) ? 'YES (' . count($product->colors) . ')' : 'NO') . "\n";
    }
}

echo "\n=== END DEBUG ===\n";
