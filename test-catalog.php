<?php
/**
 * ТЕСТОВАЯ СТРАНИЦА: Простое отображение товаров
 */

defined('YII_DEBUG') or define('YII_DEBUG', true);
defined('YII_ENV') or define('YII_ENV', 'dev');

require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/vendor/yiisoft/yii2/Yii.php';
require __DIR__ . '/config/bootstrap.php';

$config = require __DIR__ . '/config/web.php';
$application = new yii\web\Application($config);

use app\models\Product;

// Получаем товары как в контроллере
$products = Product::find()
    ->with(['brand', 'sizes'])
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
    ->andWhere(['!=', 'stock_status', Product::STOCK_OUT_OF_STOCK])
    ->limit(12)
    ->all();
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Тест каталога</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; }
        .products { display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 20px; }
        .product { border: 1px solid #ddd; padding: 15px; border-radius: 8px; }
        .product img { width: 100%; height: auto; }
        .product h3 { margin: 10px 0; font-size: 16px; }
        .product .price { font-size: 18px; font-weight: bold; color: #000; }
        .error { background: #ffebee; color: #c62828; padding: 15px; border-radius: 4px; margin-bottom: 20px; }
    </style>
</head>
<body>
    <h1>Тест каталога - <?= count($products) ?> товаров</h1>
    
    <?php if (empty($products)): ?>
        <div class="error">
            ❌ ТОВАРЫ НЕ НАЙДЕНЫ!
        </div>
    <?php else: ?>
        <div class="products">
            <?php foreach ($products as $product): ?>
                <div class="product">
                    <img src="<?= htmlspecialchars($product->getMainImageUrl()) ?>" alt="<?= htmlspecialchars($product->name) ?>">
                    <div class="brand"><?= htmlspecialchars($product->brand_name) ?></div>
                    <h3><?= htmlspecialchars($product->name) ?></h3>
                    <div class="price"><?= Yii::$app->formatter->asCurrency($product->price, 'BYN') ?></div>
                    <div class="stock">Статус: <?= $product->stock_status ?></div>
                    <div class="sizes">Размеров: <?= count($product->sizes) ?></div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</body>
</html>
