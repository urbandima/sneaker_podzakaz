<?php
/**
 * Тест связей и логики фильтрации
 */

require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/vendor/yiisoft/yii2/Yii.php';

$config = require __DIR__ . '/config/console.php';
$application = new yii\console\Application($config);

echo "=== ПРОВЕРКА СВЯЗЕЙ И ЛОГИКИ ФИЛЬТРАЦИИ ===\n\n";

// 1. Проверяем характеристику "Пол" в БД
echo "1. Характеристика 'Пол' в БД:\n";
$genderChar = \app\models\Characteristic::find()
    ->where(['key' => 'gender'])
    ->one();

if (!$genderChar) {
    echo "   ❌ Характеристика 'gender' не найдена!\n\n";
    exit(1);
}

echo "   ✅ ID: {$genderChar->id}\n";
echo "   ✅ Key: {$genderChar->key}\n";
echo "   ✅ Name: {$genderChar->name}\n";
echo "   ✅ Type: {$genderChar->type}\n";
echo "   ✅ is_filter: {$genderChar->is_filter}\n\n";

// 2. Проверяем значения характеристики в characteristic_value
echo "2. Значения в characteristic_value:\n";
$charValues = \app\models\CharacteristicValue::find()
    ->where(['characteristic_id' => $genderChar->id, 'is_active' => 1])
    ->all();

foreach ($charValues as $value) {
    echo "   - ID={$value->id}, Value='{$value->value}', Slug='{$value->slug}'\n";
}
echo "\n";

// 3. Проверяем связи в product_characteristic_value
echo "3. Связи в product_characteristic_value:\n";
$pcvCount = \app\models\ProductCharacteristicValue::find()
    ->where(['characteristic_id' => $genderChar->id])
    ->count();
echo "   Всего связей: {$pcvCount}\n";

if ($pcvCount > 0) {
    $pcvStats = \app\models\ProductCharacteristicValue::find()
        ->select(['characteristic_value_id', 'COUNT(*) as count'])
        ->where(['characteristic_id' => $genderChar->id])
        ->groupBy(['characteristic_value_id'])
        ->asArray()
        ->all();
    
    foreach ($pcvStats as $stat) {
        $value = \app\models\CharacteristicValue::findOne($stat['characteristic_value_id']);
        if ($value) {
            echo "   - {$value->value}: {$stat['count']} связей\n";
        }
    }
}
echo "\n";

// 4. Проверяем поле product.gender
echo "4. Поле product.gender:\n";
$productGenderStats = \app\models\Product::find()
    ->select(['gender', 'COUNT(*) as count'])
    ->where(['is_active' => 1])
    ->andWhere(['!=', 'stock_status', 'out_of_stock'])
    ->groupBy(['gender'])
    ->asArray()
    ->all();

foreach ($productGenderStats as $stat) {
    $gender = $stat['gender'] ?: '(NULL)';
    echo "   - {$gender}: {$stat['count']} товаров\n";
}
echo "\n";

// 5. ПРОБЛЕМА: Несоответствие форматов
echo "5. ❌ ОБНАРУЖЕНА ПРОБЛЕМА:\n";
echo "   characteristic_value хранит: 'Мужской', 'Женский', 'Унисекс' (русские названия)\n";
echo "   product.gender хранит: 'male', 'female', 'unisex' (английские значения)\n";
echo "   Форматы НЕ СОВПАДАЮТ!\n\n";

// 6. Проверяем, как FilterBuilder обрабатывает это
echo "6. Тест FilterBuilder:\n";
$filters = \app\services\Catalog\FilterBuilder::buildFilters([], []);

$genderFilter = null;
foreach ($filters['characteristics'] as $char) {
    if ($char['key'] === 'gender') {
        $genderFilter = $char;
        break;
    }
}

if ($genderFilter) {
    echo "   Фильтр возвращает:\n";
    foreach ($genderFilter['values'] as $value) {
        echo "   - id='{$value['id']}', value='{$value['value']}', count={$value['count']}\n";
    }
    echo "\n";
}

// 7. Проверяем фильтрацию с разными значениями
echo "7. Тест фильтрации:\n";

// Тест 1: Фильтрация по 'unisex' (английское значение из product)
echo "   Тест 1: Фильтр по 'unisex' (из product.gender):\n";
$query1 = \app\models\Product::find()
    ->where(['is_active' => 1])
    ->andWhere(['!=', 'stock_status', 'out_of_stock']);

\app\services\Catalog\FilterBuilder::applyFiltersToProductQuery($query1, [
    'char_' . $genderChar->id => ['unisex']
]);
$count1 = $query1->count();
echo "      Найдено: {$count1} товаров\n";

// Тест 2: Фильтрация по ID=11 (Мужской из characteristic_value)
echo "   Тест 2: Фильтр по ID=11 ('Мужской' из characteristic_value):\n";
$query2 = \app\models\Product::find()
    ->where(['is_active' => 1])
    ->andWhere(['!=', 'stock_status', 'out_of_stock']);

\app\services\Catalog\FilterBuilder::applyFiltersToProductQuery($query2, [
    'char_' . $genderChar->id => [11]
]);
$count2 = $query2->count();
echo "      Найдено: {$count2} товаров\n";

// Тест 3: Фильтрация по 'male' (английское значение)
echo "   Тест 3: Фильтр по 'male' (английское значение):\n";
$query3 = \app\models\Product::find()
    ->where(['is_active' => 1])
    ->andWhere(['!=', 'stock_status', 'out_of_stock']);

\app\services\Catalog\FilterBuilder::applyFiltersToProductQuery($query3, [
    'char_' . $genderChar->id => ['male']
]);
$count3 = $query3->count();
echo "      Найдено: {$count3} товаров\n\n";

// 8. Проверяем реальные значения в product.gender
echo "8. Проверка реальных значений в product.gender:\n";
$distinctGenders = \app\models\Product::find()
    ->select(['gender'])
    ->distinct()
    ->where(['is_active' => 1])
    ->column();

echo "   Уникальные значения: " . implode(', ', array_map(function($g) {
    return "'{$g}'";
}, $distinctGenders)) . "\n\n";

// 9. ВЫВОД
echo "=== НАЙДЕННЫЕ ПРОБЛЕМЫ ===\n\n";
echo "❌ ПРОБЛЕМА 1: Несоответствие форматов данных\n";
echo "   - characteristic_value.value: 'Мужской', 'Женский', 'Унисекс'\n";
echo "   - product.gender: 'male', 'female', 'unisex'\n";
echo "   Решение: Нужен маппинг при фильтрации\n\n";

echo "❌ ПРОБЛЕМА 2: Фильтр возвращает английские значения как ID\n";
echo "   - value['id'] = 'unisex' (строка)\n";
echo "   - Но в characteristic_value ID - это числа (11, 12, 13)\n";
echo "   Решение: Нужно различать гибридные и обычные характеристики во view\n\n";

echo "❌ ПРОБЛЕМА 3: При фильтрации система ищет 'unisex' в product.gender\n";
echo "   - Это работает для гибридных полей\n";
echo "   - Но если пользователь выберет 'Мужской' (ID=11), система попытается\n";
echo "     найти product.gender = 11, что неправильно\n";
echo "   Решение: Нужна проверка типа значения (число vs строка)\n\n";

echo "=== РЕКОМЕНДАЦИИ ===\n\n";
echo "1. Добавить проверку типа значения в applyFiltersToQuery()\n";
echo "2. Если значение - число, искать в product_characteristic_value\n";
echo "3. Если значение - строка, искать в product.gender\n";
echo "4. Обновить view для корректного отображения\n\n";
