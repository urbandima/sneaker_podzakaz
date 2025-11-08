<?php
/**
 * Тест загрузки данных для каталога
 */

define('YII_DEBUG', true);
define('YII_ENV', 'dev');

require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/vendor/yiisoft/yii2/Yii.php';
require __DIR__ . '/config/bootstrap.php';

$config = require __DIR__ . '/config/web.php';
$app = new yii\web\Application($config);

echo "=== ДИАГНОСТИКА КАТАЛОГА ===\n\n";

// 1. Проверка количества товаров
$totalProducts = \app\models\Product::find()
    ->where(['is_active' => 1])
    ->andWhere(['!=', 'stock_status', \app\models\Product::STOCK_OUT_OF_STOCK])
    ->count();

echo "1. Активных товаров в БД: $totalProducts\n\n";

// 2. Проверка загрузки с отношениями (как в контроллере)
$query = \app\models\Product::find()
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
    ->where([
        'is_active' => 1,
    ])
    ->andWhere(['!=', 'stock_status', \app\models\Product::STOCK_OUT_OF_STOCK])
    ->limit(3);

$products = $query->all();

echo "2. Загружено товаров с отношениями: " . count($products) . "\n\n";

// 3. Детальная проверка первых 3 товаров
foreach ($products as $i => $product) {
    echo "Товар #" . ($i + 1) . ":\n";
    echo "  ID: {$product->id}\n";
    echo "  Название: {$product->name}\n";
    echo "  Slug: {$product->slug}\n";
    echo "  Brand ID: {$product->brand_id}\n";
    echo "  Brand name (денормализ.): {$product->brand_name}\n";
    echo "  Brand через relation: " . ($product->brand ? $product->brand->name : 'NULL') . "\n";
    echo "  Цена: {$product->price} BYN\n";
    echo "  Main image URL (денормализ.): {$product->main_image_url}\n";
    echo "  getMainImageUrl(): " . $product->getMainImageUrl() . "\n";
    echo "  Sizes загружены: " . (count($product->sizes)) . "\n";
    echo "  Colors загружены: " . (count($product->colors)) . "\n";
    echo "  getUrl(): " . $product->getUrl() . "\n";
    echo "  getDisplayTitle(): " . $product->getDisplayTitle() . "\n";
    echo "  hasDiscount(): " . ($product->hasDiscount() ? 'да' : 'нет') . "\n";
    echo "  getPriceRange(): " . json_encode($product->getPriceRange()) . "\n";
    echo "\n";
}

// 4. Проверка рендеринга карточки
echo "4. Проверка рендеринга карточки (первый товар):\n\n";
if (!empty($products)) {
    $product = $products[0];
    
    // Симуляция того, что делает _products.php
    echo "HTML:\n";
    echo '<div class="product product-card modern-card">' . "\n";
    echo '  <a href="' . $product->getUrl() . '">' . "\n";
    echo '    <div class="product-image-wrapper">' . "\n";
    echo '      <img src="' . $product->getMainImageUrl() . '" alt="' . htmlspecialchars($product->name) . '" class="product-image primary">' . "\n";
    echo '    </div>' . "\n";
    echo '    <div class="info product-card-body">' . "\n";
    if ($product->brand) {
        echo '      <span class="product-card-brand">' . htmlspecialchars($product->brand->name) . '</span>' . "\n";
    }
    echo '      <h3 class="product-card-name">' . htmlspecialchars($product->getDisplayTitle()) . '</h3>' . "\n";
    echo '      <div class="price product-card-price">' . "\n";
    echo '        <span class="current product-card-price-current">' . Yii::$app->formatter->asCurrency($product->price, 'BYN') . '</span>' . "\n";
    echo '      </div>' . "\n";
    echo '    </div>' . "\n";
    echo '  </a>' . "\n";
    echo '</div>' . "\n\n";
}

// 5. Проверка связей
echo "5. Проверка связей первого товара:\n";
if (!empty($products)) {
    $product = $products[0];
    echo "  Brand relation populated: " . ($product->isRelationPopulated('brand') ? 'да' : 'нет') . "\n";
    echo "  Sizes relation populated: " . ($product->isRelationPopulated('sizes') ? 'да' : 'нет') . "\n";
    echo "  Colors relation populated: " . ($product->isRelationPopulated('colors') ? 'да' : 'нет') . "\n";
}

echo "\n=== ДИАГНОСТИКА ЗАВЕРШЕНА ===\n";
