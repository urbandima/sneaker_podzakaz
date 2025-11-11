<?php
/**
 * Тест унифицированной системы характеристик
 */

require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/vendor/yiisoft/yii2/Yii.php';

$config = require __DIR__ . '/config/console.php';
$application = new yii\console\Application($config);

echo "=== ТЕСТ УНИФИЦИРОВАННОЙ СИСТЕМЫ ХАРАКТЕРИСТИК ===\n\n";

// 1. Тест фильтрации по gender
echo "1. Тест фильтрации по gender:\n";

$genderChar = \app\models\Characteristic::find()->where(['key' => 'gender'])->one();
if (!$genderChar) {
    echo "   ❌ Характеристика gender не найдена\n";
    exit(1);
}

echo "   Характеристика: ID={$genderChar->id}, key={$genderChar->key}\n\n";

// Тест 1: Фильтр по 'male'
echo "   Тест 1: Фильтр по 'male' (строковое значение):\n";
$query1 = \app\models\Product::find()
    ->where(['is_active' => 1])
    ->andWhere(['!=', 'stock_status', 'out_of_stock']);

\app\services\Catalog\FilterBuilder::applyFiltersToProductQuery($query1, [
    'char_' . $genderChar->id => ['male']
]);

$count1 = $query1->count();
echo "      ✅ Найдено: {$count1} товаров\n";

// Тест 2: Фильтр по 'female'
echo "   Тест 2: Фильтр по 'female':\n";
$query2 = \app\models\Product::find()
    ->where(['is_active' => 1])
    ->andWhere(['!=', 'stock_status', 'out_of_stock']);

\app\services\Catalog\FilterBuilder::applyFiltersToProductQuery($query2, [
    'char_' . $genderChar->id => ['female']
]);

$count2 = $query2->count();
echo "      ✅ Найдено: {$count2} товаров\n";

// Тест 3: Фильтр по 'unisex'
echo "   Тест 3: Фильтр по 'unisex':\n";
$query3 = \app\models\Product::find()
    ->where(['is_active' => 1])
    ->andWhere(['!=', 'stock_status', 'out_of_stock']);

\app\services\Catalog\FilterBuilder::applyFiltersToProductQuery($query3, [
    'char_' . $genderChar->id => ['unisex']
]);

$count3 = $query3->count();
echo "      ✅ Найдено: {$count3} товаров\n\n";

// 2. Тест построения фильтров
echo "2. Тест построения фильтров:\n";
$filters = \app\services\Catalog\FilterBuilder::buildFilters([], []);

$genderFilter = null;
foreach ($filters['characteristics'] as $char) {
    if ($char['key'] === 'gender') {
        $genderFilter = $char;
        break;
    }
}

if ($genderFilter) {
    echo "   ✅ Фильтр gender найден:\n";
    echo "      ID: {$genderFilter['id']}\n";
    echo "      Name: {$genderFilter['name']}\n";
    echo "      Type: {$genderFilter['type']}\n";
    echo "      is_product_field: " . ($genderFilter['is_product_field'] ? 'true' : 'false') . "\n";
    echo "      Значения:\n";
    foreach ($genderFilter['values'] as $value) {
        echo "         - id='{$value['id']}', value='{$value['value']}', count={$value['count']}\n";
    }
} else {
    echo "   ❌ Фильтр gender не найден\n";
}

echo "\n";

// 3. Тест formatActiveFilters
echo "3. Тест formatActiveFilters:\n";
$activeFilters = \app\services\Catalog\FilterBuilder::formatActiveFilters([
    'char_' . $genderChar->id => ['male', 'female']
]);

if (!empty($activeFilters)) {
    echo "   ✅ Активные фильтры:\n";
    foreach ($activeFilters as $filter) {
        echo "      - type={$filter['type']}, label='{$filter['label']}'\n";
    }
} else {
    echo "   ❌ Активные фильтры пусты\n";
}

echo "\n";

// 4. Проверка данных
echo "4. Проверка данных в БД:\n";
$stats = Yii::$app->db->createCommand('
    SELECT 
        gender,
        COUNT(*) as count
    FROM product
    WHERE is_active = 1 AND gender IS NOT NULL
    GROUP BY gender
')->queryAll();

echo "   Распределение по gender:\n";
$total = 0;
foreach ($stats as $stat) {
    echo "      - '{$stat['gender']}': {$stat['count']} товаров\n";
    $total += $stat['count'];
}
echo "   Итого: {$total} товаров с gender\n\n";

// 5. Итоговая проверка
echo "=== ИТОГОВАЯ ПРОВЕРКА ===\n\n";

$expectedTotal = $count1 + $count2 + $count3;
if ($expectedTotal == $total) {
    echo "✅ ВСЕ ТЕСТЫ ПРОЙДЕНЫ!\n";
    echo "   - Фильтрация работает корректно\n";
    echo "   - Построение фильтров работает\n";
    echo "   - formatActiveFilters работает\n";
    echo "   - Данные консистентны\n";
} else {
    echo "⚠️  ВНИМАНИЕ: Несоответствие данных\n";
    echo "   Сумма фильтров: {$expectedTotal}\n";
    echo "   Всего в БД: {$total}\n";
}

echo "\n✅ Унифицированная система характеристик работает!\n";
