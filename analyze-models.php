<?php
/**
 * Анализ текущих названий товаров для выделения моделей
 */

defined('YII_DEBUG') or define('YII_DEBUG', true);
defined('YII_ENV') or define('YII_ENV', 'dev');

require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/vendor/yiisoft/yii2/Yii.php';
require __DIR__ . '/config/bootstrap.php';

$config = require __DIR__ . '/config/console.php';
$application = new yii\console\Application($config);

use app\models\Product;

echo "=== АНАЛИЗ МОДЕЛЕЙ ТОВАРОВ ===\n\n";

$products = Product::find()
    ->with('brand')
    ->where(['is_active' => 1])
    ->all();

echo "Всего товаров: " . count($products) . "\n\n";
echo str_repeat('=', 80) . "\n";

// Словарь транслитерации для исправления
$translitMap = [
    'Данк' => 'Dunk',
    'данк' => 'Dunk',
    'Найк' => 'Nike',
    'найк' => 'Nike',
    'Эйр' => 'Air',
    'эйр' => 'Air',
    'Форс' => 'Force',
    'форс' => 'Force',
    'Зум' => 'Zoom',
    'зум' => 'Zoom',
];

foreach ($products as $product) {
    echo "ID: {$product->id}\n";
    echo "Бренд: " . ($product->brand ? $product->brand->name : 'НЕТ') . "\n";
    echo "Название: {$product->name}\n";
    echo "Артикул: " . ($product->vendor_code ?: $product->style_code ?: 'НЕТ') . "\n";
    
    // Извлекаем модель из названия
    $name = $product->name;
    
    // Убираем бренд
    if ($product->brand) {
        $brandName = $product->brand->name;
        $name = preg_replace('/^' . preg_quote($brandName, '/') . '\s+/ui', '', $name);
    }
    
    // Убираем артикул
    $articleCode = $product->vendor_code ?: $product->style_code;
    if ($articleCode) {
        $name = str_replace($articleCode, '', $name);
    }
    
    // Убираем лишние пробелы
    $name = trim(preg_replace('/\s+/', ' ', $name));
    
    // Берем первые 2-4 слова как модель
    $words = explode(' ', $name);
    $modelWords = array_slice($words, 0, 4);
    $extractedModel = implode(' ', $modelWords);
    
    // Применяем транслитерацию
    $translatedModel = $extractedModel;
    foreach ($translitMap as $from => $to) {
        $translatedModel = str_replace($from, $to, $translatedModel);
    }
    
    echo "Извлеченная модель (текущая): {$extractedModel}\n";
    echo "Модель на английском: {$translatedModel}\n";
    echo str_repeat('-', 80) . "\n\n";
}

echo "\n=== КОНЕЦ АНАЛИЗА ===\n";
