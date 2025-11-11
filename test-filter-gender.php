<?php
/**
 * Тестовый скрипт для проверки работы фильтра по полу
 * Запуск: php test-filter-gender.php
 */

require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/vendor/yiisoft/yii2/Yii.php';

$config = require __DIR__ . '/config/console.php';
$application = new yii\console\Application($config);

echo "=== ТЕСТ ФИЛЬТРАЦИИ ПО ПОЛУ ===\n\n";

// 1. Проверяем наличие характеристики "Пол"
echo "1. Поиск характеристики 'Пол'...\n";
$genderChar = \app\models\Characteristic::find()
    ->where(['key' => 'gender', 'is_active' => 1])
    ->one();

if (!$genderChar) {
    echo "   ❌ Характеристика 'gender' не найдена!\n";
    echo "   Создайте её в админ-панели:\n";
    echo "   - key: gender\n";
    echo "   - name: Пол\n";
    echo "   - type: multiselect\n";
    echo "   - is_filter: 1\n\n";
    exit(1);
}

echo "   ✅ Найдена: ID={$genderChar->id}, Name={$genderChar->name}\n\n";

// 2. Проверяем значения характеристики
echo "2. Значения характеристики 'Пол':\n";
$genderValues = \app\models\CharacteristicValue::find()
    ->where(['characteristic_id' => $genderChar->id, 'is_active' => 1])
    ->all();

if (empty($genderValues)) {
    echo "   ❌ Нет значений для характеристики 'Пол'!\n";
    echo "   Добавьте значения: Мужской, Женский, Унисекс\n\n";
    exit(1);
}

foreach ($genderValues as $value) {
    echo "   - ID={$value->id}, Value={$value->value}\n";
}
echo "\n";

// 3. Проверяем товары с характеристикой "Пол"
echo "3. Товары с характеристикой 'Пол':\n";
$productsWithGender = \app\models\ProductCharacteristicValue::find()
    ->select(['product_id', 'characteristic_value_id', 'COUNT(*) as cnt'])
    ->where(['characteristic_id' => $genderChar->id])
    ->groupBy(['product_id', 'characteristic_value_id'])
    ->asArray()
    ->all();

if (empty($productsWithGender)) {
    echo "   ⚠️  Нет товаров с характеристикой 'Пол'!\n";
    echo "   Добавьте характеристику товарам через админ-панель\n\n";
} else {
    echo "   ✅ Найдено " . count($productsWithGender) . " связей товар-пол\n\n";
}

// 4. Тестируем FilterBuilder
echo "4. Тест FilterBuilder::buildFilters():\n";
$filters = \app\services\Catalog\FilterBuilder::buildFilters([], []);

if (!isset($filters['characteristics'])) {
    echo "   ❌ Характеристики не возвращаются в фильтрах!\n\n";
    exit(1);
}

echo "   ✅ Характеристики найдены: " . count($filters['characteristics']) . " шт.\n";

// Ищем характеристику "Пол"
$genderFilter = null;
foreach ($filters['characteristics'] as $char) {
    if ($char['key'] === 'gender') {
        $genderFilter = $char;
        break;
    }
}

if (!$genderFilter) {
    echo "   ⚠️  Характеристика 'Пол' не включена в фильтры (is_filter=0?)\n\n";
} else {
    echo "   ✅ Характеристика 'Пол' в фильтрах:\n";
    echo "      ID: {$genderFilter['id']}\n";
    echo "      Name: {$genderFilter['name']}\n";
    echo "      Type: {$genderFilter['type']}\n";
    echo "      Значения:\n";
    
    if (empty($genderFilter['values'])) {
        echo "      ⚠️  Нет значений с подсчетом товаров\n";
    } else {
        foreach ($genderFilter['values'] as $value) {
            $count = $value['count'] ?? 0;
            echo "      - {$value['value']}: {$count} товаров\n";
        }
    }
    echo "\n";
}

// 5. Тест фильтрации с выбранным полом
echo "5. Тест фильтрации с выбранным полом:\n";
if (!empty($genderValues)) {
    $firstGenderValue = $genderValues[0];
    $testFilters = [
        'char_' . $genderChar->id => [$firstGenderValue->id]
    ];
    
    echo "   Фильтруем по: {$firstGenderValue->value} (ID={$firstGenderValue->id})\n";
    
    $query = \app\models\Product::find()
        ->where(['is_active' => 1])
        ->andWhere(['!=', 'stock_status', \app\models\Product::STOCK_OUT_OF_STOCK]);
    
    \app\services\Catalog\FilterBuilder::applyFiltersToProductQuery($query, $testFilters);
    
    $count = $query->count();
    echo "   ✅ Найдено товаров: {$count}\n\n";
    
    if ($count > 0) {
        echo "   Примеры товаров:\n";
        $products = $query->limit(3)->all();
        foreach ($products as $product) {
            echo "   - {$product->name} (ID={$product->id})\n";
        }
    }
}

echo "\n=== ТЕСТ ЗАВЕРШЕН ===\n";
echo "\nСледующие шаги:\n";
echo "1. Откройте /catalog в браузере\n";
echo "2. Откройте консоль (F12)\n";
echo "3. Выберите фильтр 'Пол'\n";
echo "4. Проверьте логи в консоли: '📊 Обновление характеристики: Пол'\n";
echo "5. Убедитесь, что счетчики обновляются корректно\n\n";
