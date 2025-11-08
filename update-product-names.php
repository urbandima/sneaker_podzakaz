<?php
/**
 * Скрипт обновления названий товаров в формате: Бренд + Модель + Артикул
 * Обновляет поле name для всех активных товаров
 */

defined('YII_DEBUG') or define('YII_DEBUG', true);
defined('YII_ENV') or define('YII_ENV', 'dev');

require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/vendor/yiisoft/yii2/Yii.php';
require __DIR__ . '/config/bootstrap.php';

$config = require __DIR__ . '/config/console.php';
$application = new yii\console\Application($config);

use app\models\Product;

echo "=== ОБНОВЛЕНИЕ НАЗВАНИЙ ТОВАРОВ ===\n\n";
echo "Формат: Бренд + Модель + Артикул\n";
echo "Например: Nike Dunk Low 355152-106\n\n";

// Получаем все активные товары
$products = Product::find()
    ->with('brand')
    ->where(['is_active' => 1])
    ->all();

$totalCount = count($products);
echo "Найдено товаров для обновления: {$totalCount}\n";
echo str_repeat('-', 70) . "\n\n";

$updated = 0;
$skipped = 0;
$errors = 0;

foreach ($products as $product) {
    $oldName = $product->name;
    $newName = $product->getDisplayTitle();
    
    // Если название не изменилось - пропускаем
    if ($oldName === $newName) {
        echo "[SKIP] ID {$product->id}: {$oldName}\n";
        echo "       → Название уже в правильном формате\n\n";
        $skipped++;
        continue;
    }
    
    // Обновляем
    echo "[UPDATE] ID {$product->id}\n";
    echo "  БЫЛО: {$oldName}\n";
    echo "  СТАЛО: {$newName}\n";
    
    $product->name = $newName;
    
    // Сохраняем без валидации, так как меняем только name
    if ($product->save(false)) {
        echo "  ✅ Успешно обновлено\n\n";
        $updated++;
    } else {
        echo "  ❌ ОШИБКА при сохранении: " . json_encode($product->errors) . "\n\n";
        $errors++;
    }
}

echo str_repeat('=', 70) . "\n";
echo "ИТОГИ:\n";
echo "  Всего товаров: {$totalCount}\n";
echo "  Обновлено: {$updated}\n";
echo "  Пропущено (без изменений): {$skipped}\n";
echo "  Ошибок: {$errors}\n";
echo str_repeat('=', 70) . "\n";

// Показываем примеры обновленных товаров
if ($updated > 0) {
    echo "\nПримеры обновленных товаров:\n";
    $examples = Product::find()
        ->with('brand')
        ->where(['is_active' => 1])
        ->limit(5)
        ->all();
    
    foreach ($examples as $p) {
        echo "  • {$p->name}\n";
    }
}

echo "\n=== ОБНОВЛЕНИЕ ЗАВЕРШЕНО ===\n";
