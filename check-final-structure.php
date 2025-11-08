<?php
/**
 * Проверка финальной структуры товаров: Бренд, Модель, Артикул
 */

defined('YII_DEBUG') or define('YII_DEBUG', true);
defined('YII_ENV') or define('YII_ENV', 'dev');

require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/vendor/yiisoft/yii2/Yii.php';
require __DIR__ . '/config/bootstrap.php';

$config = require __DIR__ . '/config/console.php';
$application = new yii\console\Application($config);

use app\models\Product;

echo "=== ПРОВЕРКА СТРУКТУРЫ: БРЕНД + МОДЕЛЬ + АРТИКУЛ ===\n\n";

$products = Product::find()
    ->with('brand')
    ->where(['is_active' => 1])
    ->limit(15)
    ->all();

echo "Первые 15 товаров:\n";
echo str_repeat('=', 100) . "\n\n";

foreach ($products as $product) {
    $brand = $product->brand ? $product->brand->name : '❌ НЕТ БРЕНДА';
    $model = $product->model_name ?: '❌ НЕТ МОДЕЛИ';
    $article = $product->vendor_code ?: $product->style_code ?: '❌ НЕТ АРТИКУЛА';
    
    $displayTitle = $product->getDisplayTitle();
    
    echo "ID: {$product->id}\n";
    echo "  БРЕНД:    {$brand}\n";
    echo "  МОДЕЛЬ:   {$model}\n";
    echo "  АРТИКУЛ:  {$article}\n";
    echo "  ЗАГОЛОВОК: {$displayTitle}\n";
    echo str_repeat('-', 100) . "\n\n";
}

// Статистика
echo "\n" . str_repeat('=', 100) . "\n";
echo "СТАТИСТИКА:\n\n";

$totalProducts = Product::find()->where(['is_active' => 1])->count();
$withBrand = Product::find()->where(['is_active' => 1])->andWhere(['not', ['brand_id' => null]])->count();
$withModel = Product::find()->where(['is_active' => 1])->andWhere(['not', ['model_name' => null]])->count();
$withArticle = Product::find()
    ->where(['is_active' => 1])
    ->andWhere(['or', 
        ['not', ['vendor_code' => null]], 
        ['not', ['style_code' => null]]
    ])
    ->count();

echo "Всего товаров: {$totalProducts}\n";
echo "С брендом: {$withBrand} (" . round($withBrand/$totalProducts*100, 1) . "%)\n";
echo "С моделью (model_name): {$withModel} (" . round($withModel/$totalProducts*100, 1) . "%)\n";
echo "С артикулом: {$withArticle} (" . round($withArticle/$totalProducts*100, 1) . "%)\n";

if ($withBrand === $totalProducts && $withModel === $totalProducts && $withArticle === $totalProducts) {
    echo "\n✅ ВСЕ ТОВАРЫ ИМЕЮТ ПОЛНУЮ СТРУКТУРУ: БРЕНД + МОДЕЛЬ + АРТИКУЛ\n";
} else {
    echo "\n⚠️  Некоторые товары не имеют полной структуры\n";
}

echo str_repeat('=', 100) . "\n";

echo "\n=== КОНЕЦ ПРОВЕРКИ ===\n";
