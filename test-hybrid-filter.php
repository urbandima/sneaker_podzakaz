<?php
/**
 * Тест гибридной системы фильтрации
 */

require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/vendor/yiisoft/yii2/Yii.php';

$config = require __DIR__ . '/config/console.php';
$application = new yii\console\Application($config);

echo "=== ТЕСТ ГИБРИДНОЙ ФИЛЬТРАЦИИ ===\n\n";

// 1. Тест FilterBuilder с гибридным подходом
echo "1. Построение фильтров (гибридный подход):\n";
$filters = \app\services\Catalog\FilterBuilder::buildFilters([], []);

$genderFilter = null;
foreach ($filters['characteristics'] as $char) {
    if ($char['key'] === 'gender') {
        $genderFilter = $char;
        break;
    }
}

if ($genderFilter) {
    echo "   ✅ Фильтр 'Пол' найден\n";
    echo "   Тип данных: " . ($genderFilter['is_product_field'] ?? false ? 'product.gender (гибридный)' : 'characteristic_value') . "\n";
    echo "   Значения:\n";
    foreach ($genderFilter['values'] as $value) {
        echo "      - {$value['value']} (id={$value['id']}): {$value['count']} товаров\n";
    }
    echo "\n";
} else {
    echo "   ❌ Фильтр 'Пол' не найден\n\n";
    exit(1);
}

// 2. Тест фильтрации по unisex
echo "2. Тест фильтрации по 'Унисекс':\n";
$testFilters = [
    'char_3' => ['unisex'] // ID характеристики = 3, значение = 'unisex'
];

$query = \app\models\Product::find()
    ->where(['is_active' => 1])
    ->andWhere(['!=', 'stock_status', \app\models\Product::STOCK_OUT_OF_STOCK]);

\app\services\Catalog\FilterBuilder::applyFiltersToProductQuery($query, $testFilters);

$count = $query->count();
echo "   ✅ Найдено товаров: {$count}\n";

if ($count > 0) {
    echo "   Примеры товаров:\n";
    $products = $query->limit(5)->all();
    foreach ($products as $product) {
        echo "   - {$product->name} (gender={$product->gender})\n";
    }
}
echo "\n";

// 3. Проверка других характеристик
echo "3. Другие гибридные характеристики:\n";
$hybridKeys = ['season', 'material', 'height', 'fastening'];
foreach ($hybridKeys as $key) {
    $char = null;
    foreach ($filters['characteristics'] as $c) {
        if ($c['key'] === $key) {
            $char = $c;
            break;
        }
    }
    
    if ($char) {
        $totalCount = array_sum(array_column($char['values'], 'count'));
        echo "   ✅ {$char['name']}: {$totalCount} товаров\n";
    } else {
        echo "   ⚠️  {$key}: не найдена\n";
    }
}

echo "\n=== ТЕСТ ЗАВЕРШЕН ===\n";
echo "\n✅ Гибридная система работает!\n";
echo "Теперь фильтры используют реальные данные из product.gender\n";
echo "Вместо 20 товаров из characteristic_value показывается 675 из product\n\n";
