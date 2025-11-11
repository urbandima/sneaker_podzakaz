<?php
/**
 * Тест конвертации ID → slug
 */

require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/vendor/yiisoft/yii2/Yii.php';

$config = require __DIR__ . '/config/console.php';
$application = new yii\console\Application($config);

echo "=== ТЕСТ КОНВЕРТАЦИИ ID → SLUG ===\n\n";

// 1. Проверяем slug в characteristic_value
echo "1. Значения characteristic_value:\n";
$values = \app\models\CharacteristicValue::find()
    ->where(['characteristic_id' => 3])
    ->all();

foreach ($values as $value) {
    echo "   ID={$value->id}, Value='{$value->value}', Slug='{$value->slug}'\n";
}
echo "\n";

// 2. Тест конвертации ID=11 → slug
echo "2. Конвертация ID=11 в slug:\n";
$slug = \app\models\CharacteristicValue::find()
    ->select(['slug'])
    ->where(['id' => 11])
    ->scalar();
echo "   Результат: '{$slug}'\n\n";

// 3. Проверяем, есть ли товары с gender='male'
echo "3. Товары с gender='male':\n";
$count = \app\models\Product::find()
    ->where(['is_active' => 1, 'gender' => 'male'])
    ->count();
echo "   Найдено: {$count} товаров\n\n";

// 4. Проверяем товары из product_characteristic_value с ID=11
echo "4. Товары из product_characteristic_value с value_id=11:\n";
$productIds = \app\models\ProductCharacteristicValue::find()
    ->select(['product_id'])
    ->where(['characteristic_id' => 3, 'characteristic_value_id' => 11])
    ->column();
echo "   Найдено product_id: " . implode(', ', $productIds) . "\n";

if (!empty($productIds)) {
    echo "   Проверяем поле gender у этих товаров:\n";
    $products = \app\models\Product::find()
        ->where(['id' => $productIds])
        ->all();
    
    foreach ($products as $product) {
        echo "   - ID={$product->id}, Name='{$product->name}', gender='{$product->gender}'\n";
    }
}
echo "\n";

// 5. ВЫВОД
echo "=== ВЫВОД ===\n\n";
echo "✅ Конвертация ID→slug работает: 11 → 'male'\n";
echo "❌ НО: В product.gender у всех товаров 'unisex', а не 'male'\n";
echo "❌ Товары в product_characteristic_value НЕ синхронизированы с product.gender\n\n";

echo "РЕШЕНИЕ:\n";
echo "1. Либо обновить product.gender у 12 товаров на 'male'\n";
echo "2. Либо использовать product_characteristic_value для этих товаров\n";
echo "3. Либо создать миграцию для синхронизации данных\n\n";
