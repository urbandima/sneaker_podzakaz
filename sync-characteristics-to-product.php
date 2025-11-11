<?php
/**
 * Синхронизация характеристик из characteristic_value в поля product
 * 
 * ЦЕЛЬ: Унификация хранения характеристик
 * РЕШЕНИЕ: Оставить данные в product полях (675 товаров уже там)
 * ДЕЙСТВИЕ: Синхронизировать 20 товаров из product_characteristic_value
 */

require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/vendor/yiisoft/yii2/Yii.php';

$config = require __DIR__ . '/config/console.php';
$application = new yii\console\Application($config);

echo "=== СИНХРОНИЗАЦИЯ ХАРАКТЕРИСТИК ===\n\n";

// Маппинг characteristic_value.id -> product field value
$mappings = [
    'gender' => [
        11 => 'male',      // Мужской
        12 => 'female',    // Женский
        13 => 'unisex',    // Унисекс
    ],
    'season' => [
        14 => 'summer',    // Лето
        15 => 'winter',    // Зима
        16 => 'demi',      // Демисезон
        17 => 'all',       // Всесезонные
    ],
    'material' => [
        18 => 'leather',   // Кожа
        19 => 'textile',   // Текстиль
        20 => 'synthetic', // Синтетика
        21 => 'suede',     // Замша
        22 => 'mesh',      // Сетка
        23 => 'canvas',    // Канвас
    ],
    'height' => [
        24 => 'low',       // Низкие
        25 => 'mid',       // Средние
        26 => 'high',      // Высокие
    ],
    'fastening' => [
        27 => 'laces',     // Шнурки
        28 => 'velcro',    // Липучки
        29 => 'zipper',    // Молния
        30 => 'slip_on',   // Без застежки
    ],
];

// Получаем ID характеристик
$charIds = Yii::$app->db->createCommand('
    SELECT id, `key` FROM characteristic
    WHERE `key` IN ("gender", "season", "material", "height", "fastening")
')->queryAll();

$charMap = [];
foreach ($charIds as $char) {
    $charMap[$char['key']] = $char['id'];
}

$transaction = Yii::$app->db->beginTransaction();
try {
    $totalUpdated = 0;
    
    foreach ($charMap as $charKey => $charId) {
        echo "Обработка характеристики: {$charKey} (ID={$charId})\n";
        
        // Получаем товары с этой характеристикой
        $products = Yii::$app->db->createCommand('
            SELECT pcv.product_id, pcv.characteristic_value_id
            FROM product_characteristic_value pcv
            INNER JOIN product p ON p.id = pcv.product_id
            WHERE pcv.characteristic_id = :charId
              AND p.is_active = 1
        ', [':charId' => $charId])->queryAll();
        
        foreach ($products as $row) {
            $productId = $row['product_id'];
            $valueId = $row['characteristic_value_id'];
            
            // Получаем значение для product поля
            $productValue = $mappings[$charKey][$valueId] ?? null;
            
            if ($productValue) {
                // Обновляем поле в product
                Yii::$app->db->createCommand()->update('product', [
                    $charKey => $productValue
                ], ['id' => $productId])->execute();
                
                echo "   ✅ Product ID={$productId}: {$charKey} = '{$productValue}'\n";
                $totalUpdated++;
            } else {
                echo "   ⚠️  Product ID={$productId}: нет маппинга для value_id={$valueId}\n";
            }
        }
    }
    
    $transaction->commit();
    echo "\n✅ Синхронизация завершена!\n";
    echo "   Обновлено товаров: {$totalUpdated}\n\n";
    
} catch (\Exception $e) {
    $transaction->rollBack();
    echo "\n❌ ОШИБКА: {$e->getMessage()}\n";
    exit(1);
}

// Проверка результатов
echo "=== ПРОВЕРКА РЕЗУЛЬТАТОВ ===\n\n";

$stats = Yii::$app->db->createCommand('
    SELECT 
        COUNT(*) as total,
        COUNT(gender) as with_gender,
        COUNT(season) as with_season,
        COUNT(material) as with_material,
        COUNT(height) as with_height,
        COUNT(fastening) as with_fastening
    FROM product
    WHERE is_active = 1
')->queryOne();

echo "Активных товаров: {$stats['total']}\n";
echo "С полем gender: {$stats['with_gender']} (" . round($stats['with_gender']/$stats['total']*100, 1) . "%)\n";
echo "С полем season: {$stats['with_season']} (" . round($stats['with_season']/$stats['total']*100, 1) . "%)\n";
echo "С полем material: {$stats['with_material']} (" . round($stats['with_material']/$stats['total']*100, 1) . "%)\n";
echo "С полем height: {$stats['with_height']} (" . round($stats['with_height']/$stats['total']*100, 1) . "%)\n";
echo "С полем fastening: {$stats['with_fastening']} (" . round($stats['with_fastening']/$stats['total']*100, 1) . "%)\n";

echo "\n✅ Готово! Теперь все характеристики в полях product.\n";
