<?php
/**
 * Проверка и создание тестовых характеристик для фильтров
 */

require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/vendor/yiisoft/yii2/Yii.php';

$config = require __DIR__ . '/config/console.php';
$application = new yii\console\Application($config);

// Подключаемся к БД
$db = Yii::$app->db;

// 1. Проверяем характеристики
$charCount = $db->createCommand("SELECT COUNT(*) FROM characteristic WHERE is_active=1 AND is_filter=1")->queryScalar();
echo "✅ Активных характеристик-фильтров: {$charCount}\n";

if ($charCount == 0) {
    echo "⚠️  Характеристик нет, создаю тестовые...\n";
    
    // Создаём характеристики
    $db->createCommand()->batchInsert('characteristic', ['key', 'name', 'type', 'is_filter', 'is_active', 'sort_order', 'version'], [
        ['material', 'Материал', 'multiselect', 1, 1, 1, 1],
        ['season', 'Сезон', 'select', 1, 1, 2, 1],
        ['gender', 'Пол', 'select', 1, 1, 3, 1],
    ])->execute();
    
    echo "✅ Создано 3 характеристики\n";
}

// 2. Проверяем значения
$valueCount = $db->createCommand("SELECT COUNT(*) FROM characteristic_value WHERE is_active=1")->queryScalar();
echo "✅ Активных значений: {$valueCount}\n";

if ($valueCount == 0) {
    echo "⚠️  Значений нет, создаю...\n";
    
    $materialId = $db->createCommand("SELECT id FROM characteristic WHERE `key`='material' LIMIT 1")->queryScalar();
    $seasonId = $db->createCommand("SELECT id FROM characteristic WHERE `key`='season' LIMIT 1")->queryScalar();
    $genderId = $db->createCommand("SELECT id FROM characteristic WHERE `key`='gender' LIMIT 1")->queryScalar();
    
    if ($materialId) {
        $db->createCommand()->batchInsert('characteristic_value', ['characteristic_id', 'value', 'slug', 'sort_order', 'is_active'], [
            [$materialId, 'Кожа', 'leather', 1, 1],
            [$materialId, 'Замша', 'suede', 2, 1],
            [$materialId, 'Текстиль', 'textile', 3, 1],
        ])->execute();
    }
    
    if ($seasonId) {
        $db->createCommand()->batchInsert('characteristic_value', ['characteristic_id', 'value', 'slug', 'sort_order', 'is_active'], [
            [$seasonId, 'Лето', 'summer', 1, 1],
            [$seasonId, 'Зима', 'winter', 2, 1],
            [$seasonId, 'Демисезон', 'demi', 3, 1],
        ])->execute();
    }
    
    if ($genderId) {
        $db->createCommand()->batchInsert('characteristic_value', ['characteristic_id', 'value', 'slug', 'sort_order', 'is_active'], [
            [$genderId, 'Мужской', 'male', 1, 1],
            [$genderId, 'Женский', 'female', 2, 1],
            [$genderId, 'Унисекс', 'unisex', 3, 1],
        ])->execute();
    }
    
    echo "✅ Создано значений\n";
}

// 3. Проверяем связи товаров с характеристиками
$linkCount = $db->createCommand("SELECT COUNT(*) FROM product_characteristic_value")->queryScalar();
echo "✅ Связей товар-характеристика: {$linkCount}\n";

if ($linkCount < 10) {
    echo "⚠️  Связей мало, создаю для первых 10 товаров...\n";
    
    $products = $db->createCommand("SELECT id FROM product WHERE is_active=1 LIMIT 10")->queryColumn();
    $materialId = $db->createCommand("SELECT id FROM characteristic WHERE `key`='material' LIMIT 1")->queryScalar();
    $seasonId = $db->createCommand("SELECT id FROM characteristic WHERE `key`='season' LIMIT 1")->queryScalar();
    $genderId = $db->createCommand("SELECT id FROM characteristic WHERE `key`='gender' LIMIT 1")->queryScalar();
    
    $leatherId = $db->createCommand("SELECT id FROM characteristic_value WHERE slug='leather' LIMIT 1")->queryScalar();
    $suededId = $db->createCommand("SELECT id FROM characteristic_value WHERE slug='suede' LIMIT 1")->queryScalar();
    $summerId = $db->createCommand("SELECT id FROM characteristic_value WHERE slug='summer' LIMIT 1")->queryScalar();
    $winterId = $db->createCommand("SELECT id FROM characteristic_value WHERE slug='winter' LIMIT 1")->queryScalar();
    $maleId = $db->createCommand("SELECT id FROM characteristic_value WHERE slug='male' LIMIT 1")->queryScalar();
    $femaleId = $db->createCommand("SELECT id FROM characteristic_value WHERE slug='female' LIMIT 1")->queryScalar();
    
    foreach ($products as $index => $productId) {
        // Материал (чередуем кожа/замша)
        if ($materialId && ($leatherId || $suededId)) {
            $db->createCommand()->insert('product_characteristic_value', [
                'product_id' => $productId,
                'characteristic_id' => $materialId,
                'characteristic_value_id' => ($index % 2 == 0) ? $leatherId : $suededId,
            ])->execute();
        }
        
        // Сезон (чередуем лето/зима)
        if ($seasonId && ($summerId || $winterId)) {
            $db->createCommand()->insert('product_characteristic_value', [
                'product_id' => $productId,
                'characteristic_id' => $seasonId,
                'characteristic_value_id' => ($index % 2 == 0) ? $summerId : $winterId,
            ])->execute();
        }
        
        // Пол (чередуем мужской/женский)
        if ($genderId && ($maleId || $femaleId)) {
            $db->createCommand()->insert('product_characteristic_value', [
                'product_id' => $productId,
                'characteristic_id' => $genderId,
                'characteristic_value_id' => ($index % 2 == 0) ? $maleId : $femaleId,
            ])->execute();
        }
    }
    
    echo "✅ Создано связей для " . count($products) . " товаров\n";
}

echo "\n🎉 Проверка завершена! Фильтры готовы к работе.\n";
echo "Перейдите в каталог и проверьте фильтры.\n";
