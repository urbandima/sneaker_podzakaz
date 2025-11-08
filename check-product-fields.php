<?php
/**
 * Проверка полей товаров для формирования заголовков
 */

defined('YII_DEBUG') or define('YII_DEBUG', true);
defined('YII_ENV') or define('YII_ENV', 'dev');

require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/vendor/yiisoft/yii2/Yii.php';
require __DIR__ . '/config/bootstrap.php';

$config = require __DIR__ . '/config/console.php';
$application = new yii\console\Application($config);

use app\models\Product;

echo "=== ПРОВЕРКА ПОЛЕЙ ДЛЯ ЗАГОЛОВКОВ ===\n\n";

$products = Product::find()
    ->with('brand')
    ->where(['is_active' => 1])
    ->limit(10)
    ->all();

echo "Проверяем первые 10 товаров:\n\n";

foreach ($products as $p) {
    echo "ID: {$p->id}\n";
    echo "  Текущее название: {$p->name}\n";
    echo "  Бренд: " . ($p->brand ? $p->brand->name : 'НЕТ') . "\n";
    echo "  style_code: " . ($p->style_code ?: 'ПУСТО') . "\n";
    echo "  vendor_code: " . ($p->vendor_code ?: 'ПУСТО') . "\n";
    echo "  sku: " . ($p->sku ?: 'ПУСТО') . "\n";
    echo "  poizon_id: " . ($p->poizon_id ?: 'ПУСТО') . "\n";
    echo "  poizon_spu_id: " . ($p->poizon_spu_id ?: 'ПУСТО') . "\n";
    echo "  ---\n";
}

echo "\n=== КОНЕЦ ПРОВЕРКИ ===\n";
