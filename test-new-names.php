<?php
/**
 * Тестирование новых названий без сохранения
 */

defined('YII_DEBUG') or define('YII_DEBUG', true);
defined('YII_ENV') or define('YII_ENV', 'dev');

require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/vendor/yiisoft/yii2/Yii.php';
require __DIR__ . '/config/bootstrap.php';

$config = require __DIR__ . '/config/console.php';
$application = new yii\console\Application($config);

use app\models\Product;

echo "=== ТЕСТ НОВЫХ НАЗВАНИЙ (БЕЗ СОХРАНЕНИЯ) ===\n\n";

$products = Product::find()
    ->with('brand')
    ->where(['is_active' => 1])
    ->limit(10)
    ->all();

foreach ($products as $product) {
    $newName = $product->getDisplayTitle();
    echo "ID {$product->id}:\n";
    echo "  ТЕКУЩЕЕ: {$product->name}\n";
    echo "  НОВОЕ: {$newName}\n";
    echo "  ---\n";
}

echo "\n=== КОНЕЦ ТЕСТА ===\n";
