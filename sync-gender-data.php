<?php
/**
 * Синхронизация данных product.gender с product_characteristic_value
 */

require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/vendor/yiisoft/yii2/Yii.php';

$config = require __DIR__ . '/config/console.php';
$application = new yii\console\Application($config);

echo "=== СИНХРОНИЗАЦИЯ ДАННЫХ GENDER ===\n\n";

// 1. Находим все товары с характеристикой "Пол"
echo "1. Поиск товаров с характеристикой 'Пол'...\n";
$genderCharId = 3;

$productCharValues = \app\models\ProductCharacteristicValue::find()
    ->where(['characteristic_id' => $genderCharId])
    ->all();

echo "   Найдено: " . count($productCharValues) . " связей\n\n";

// 2. Синхронизация
echo "2. Синхронизация product.gender:\n";
$updated = 0;
$skipped = 0;

foreach ($productCharValues as $pcv) {
    $product = \app\models\Product::findOne($pcv->product_id);
    if (!$product) {
        continue;
    }
    
    $charValue = \app\models\CharacteristicValue::findOne($pcv->characteristic_value_id);
    if (!$charValue) {
        continue;
    }
    
    // Используем slug как значение для product.gender
    $newGender = $charValue->slug; // 'male', 'female', 'unisex'
    
    if ($product->gender !== $newGender) {
        $oldGender = $product->gender ?: '(NULL)';
        echo "   Товар #{$product->id}: '{$oldGender}' → '{$newGender}' ({$charValue->value})\n";
        
        $product->gender = $newGender;
        if ($product->save(false)) {
            $updated++;
        }
    } else {
        $skipped++;
    }
}

echo "\n";
echo "✅ Обновлено: {$updated} товаров\n";
echo "⏭️  Пропущено (уже синхронизировано): {$skipped} товаров\n\n";

// 3. Проверка результата
echo "3. Проверка результата:\n";
$genderStats = \app\models\Product::find()
    ->select(['gender', 'COUNT(*) as count'])
    ->where(['is_active' => 1])
    ->groupBy(['gender'])
    ->asArray()
    ->all();

foreach ($genderStats as $stat) {
    $gender = $stat['gender'] ?: '(NULL)';
    echo "   - {$gender}: {$stat['count']} товаров\n";
}

echo "\n=== СИНХРОНИЗАЦИЯ ЗАВЕРШЕНА ===\n";
