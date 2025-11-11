<?php
/**
 * Анализ данных характеристик для решения проблемы гибридной системы
 */

require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/vendor/yiisoft/yii2/Yii.php';

$config = require __DIR__ . '/config/console.php';
$application = new yii\console\Application($config);

echo "=== АНАЛИЗ ДАННЫХ ХАРАКТЕРИСТИК ===\n\n";

// 1. Проверяем данные в product
echo "1. Данные в таблице product:\n";
$stats = Yii::$app->db->createCommand('
    SELECT 
        COUNT(*) as total,
        COUNT(gender) as with_gender,
        COUNT(season) as with_season,
        COUNT(material) as with_material,
        COUNT(height) as with_height,
        COUNT(fastening) as with_fastening,
        COUNT(country) as with_country
    FROM product
    WHERE is_active = 1
')->queryOne();

echo "   Всего активных товаров: {$stats['total']}\n";
echo "   С полем gender: {$stats['with_gender']}\n";
echo "   С полем season: {$stats['with_season']}\n";
echo "   С полем material: {$stats['with_material']}\n";
echo "   С полем height: {$stats['with_height']}\n";
echo "   С полем fastening: {$stats['with_fastening']}\n";
echo "   С полем country: {$stats['with_country']}\n\n";

// 2. Проверяем характеристики
echo "2. Характеристики в БД:\n";
$chars = Yii::$app->db->createCommand('
    SELECT id, `key`, name, type, is_filter
    FROM characteristic
    WHERE `key` IN ("gender", "season", "material", "height", "fastening", "country")
    ORDER BY `key`
')->queryAll();

$charMap = [];
foreach ($chars as $char) {
    echo "   - {$char['key']}: ID={$char['id']}, name={$char['name']}, is_filter={$char['is_filter']}\n";
    $charMap[$char['key']] = $char['id'];
}
echo "\n";

// 3. Проверяем связи в product_characteristic_value
echo "3. Связи в product_characteristic_value:\n";
foreach ($chars as $char) {
    $count = Yii::$app->db->createCommand('
        SELECT COUNT(*) FROM product_characteristic_value
        WHERE characteristic_id = :id
    ', [':id' => $char['id']])->queryScalar();
    echo "   - {$char['key']}: {$count} связей\n";
}
echo "\n";

// 4. Проверяем уникальные значения в product.gender
echo "4. Уникальные значения в product.gender:\n";
$genders = Yii::$app->db->createCommand('
    SELECT gender, COUNT(*) as count
    FROM product
    WHERE is_active = 1 AND gender IS NOT NULL
    GROUP BY gender
')->queryAll();

foreach ($genders as $g) {
    echo "   - '{$g['gender']}': {$g['count']} товаров\n";
}
echo "\n";

// 5. Проверяем значения характеристик
echo "5. Значения характеристик в characteristic_value:\n";
if (isset($charMap['gender'])) {
    $values = Yii::$app->db->createCommand('
        SELECT id, value, slug
        FROM characteristic_value
        WHERE characteristic_id = :id AND is_active = 1
        ORDER BY sort_order
    ', [':id' => $charMap['gender']])->queryAll();
    
    echo "   Gender:\n";
    foreach ($values as $v) {
        echo "      - ID={$v['id']}, value='{$v['value']}', slug='{$v['slug']}'\n";
    }
}
echo "\n";

// 6. РЕКОМЕНДАЦИЯ
echo "=== РЕКОМЕНДАЦИЯ ===\n\n";

$hasProductData = $stats['with_gender'] > 0 || $stats['with_season'] > 0 || $stats['with_material'] > 0;
$hasCharData = false;
foreach ($chars as $char) {
    $count = Yii::$app->db->createCommand('
        SELECT COUNT(*) FROM product_characteristic_value
        WHERE characteristic_id = :id
    ', [':id' => $char['id']])->queryScalar();
    if ($count > 0) {
        $hasCharData = true;
        break;
    }
}

if ($hasProductData && !$hasCharData) {
    echo "✅ РЕКОМЕНДАЦИЯ: Оставить данные в полях product\n";
    echo "   ПРИЧИНА: Все данные уже в product, нет данных в characteristic_value\n";
    echo "   ДЕЙСТВИЕ: Упростить FilterBuilder, убрать гибридную логику\n";
} elseif (!$hasProductData && $hasCharData) {
    echo "✅ РЕКОМЕНДАЦИЯ: Использовать только characteristic_value\n";
    echo "   ПРИЧИНА: Все данные в characteristic_value\n";
    echo "   ДЕЙСТВИЕ: Удалить поля из product\n";
} elseif ($hasProductData && $hasCharData) {
    echo "⚠️  РЕКОМЕНДАЦИЯ: Синхронизировать данные\n";
    echo "   ПРИЧИНА: Данные в обоих местах\n";
    echo "   ДЕЙСТВИЕ: Выбрать один источник и мигрировать\n";
} else {
    echo "❌ ПРОБЛЕМА: Нет данных ни в одном месте\n";
}
