<?php
/**
 * Проверка реальных данных о поле товаров
 */

require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/vendor/yiisoft/yii2/Yii.php';

$config = require __DIR__ . '/config/console.php';
$application = new yii\console\Application($config);

echo "=== ПРОВЕРКА ДАННЫХ О ПОЛЕ ТОВАРОВ ===\n\n";

// 1. Проверяем поле gender в таблице product
echo "1. Данные из таблицы product (поле gender):\n";
$genderStats = \app\models\Product::find()
    ->select(['gender', 'COUNT(*) as count'])
    ->where(['is_active' => 1])
    ->andWhere(['!=', 'stock_status', 'out_of_stock'])
    ->groupBy(['gender'])
    ->asArray()
    ->all();

if (empty($genderStats)) {
    echo "   ⚠️  Поле gender пустое у всех товаров!\n\n";
} else {
    foreach ($genderStats as $stat) {
        $gender = $stat['gender'] ?: '(NULL)';
        $count = $stat['count'];
        echo "   - {$gender}: {$count} товаров\n";
    }
    echo "\n";
}

// 2. Проверяем характеристику "Пол" в product_characteristic_value
echo "2. Данные из product_characteristic_value:\n";
$charGender = \app\models\Characteristic::find()
    ->where(['key' => 'gender'])
    ->one();

if ($charGender) {
    echo "   Характеристика найдена: ID={$charGender->id}\n";
    
    $charStats = \app\models\ProductCharacteristicValue::find()
        ->select(['characteristic_value_id', 'COUNT(DISTINCT product_id) as count'])
        ->where(['characteristic_id' => $charGender->id])
        ->groupBy(['characteristic_value_id'])
        ->asArray()
        ->all();
    
    if (empty($charStats)) {
        echo "   ⚠️  Нет связей товар-характеристика!\n\n";
    } else {
        foreach ($charStats as $stat) {
            $value = \app\models\CharacteristicValue::findOne($stat['characteristic_value_id']);
            if ($value) {
                echo "   - {$value->value}: {$stat['count']} товаров\n";
            }
        }
        echo "\n";
    }
} else {
    echo "   ⚠️  Характеристика 'gender' не найдена\n\n";
}

// 3. Сравнение
echo "3. Рекомендация:\n";
if (!empty($genderStats) && !empty($genderStats[0]['gender'])) {
    echo "   ✅ Используйте поле product.gender для фильтрации\n";
    echo "   📝 Значения в БД:\n";
    foreach ($genderStats as $stat) {
        if ($stat['gender']) {
            echo "      - '{$stat['gender']}' → {$stat['count']} товаров\n";
        }
    }
    echo "\n   💡 Нужно создать гибридный фильтр, который:\n";
    echo "      1. Читает данные из product.gender\n";
    echo "      2. Отображает человекочитаемые названия\n";
    echo "      3. Фильтрует по product.gender напрямую\n";
} else {
    echo "   ⚠️  Поле product.gender не заполнено\n";
    echo "   Используйте product_characteristic_value\n";
}

echo "\n=== КОНЕЦ ПРОВЕРКИ ===\n";
