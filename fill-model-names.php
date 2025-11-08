<?php
/**
 * Заполнение поля model_name для всех товаров
 * Извлекает модель из названия и переводит на английский
 */

defined('YII_DEBUG') or define('YII_DEBUG', true);
defined('YII_ENV') or define('YII_ENV', 'dev');

require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/vendor/yiisoft/yii2/Yii.php';
require __DIR__ . '/config/bootstrap.php';

$config = require __DIR__ . '/config/console.php';
$application = new yii\console\Application($config);

use app\models\Product;

echo "=== ЗАПОЛНЕНИЕ MODEL_NAME (АНГЛИЙСКИЕ НАЗВАНИЯ МОДЕЛЕЙ) ===\n\n";

// Расширенный словарь транслитерации
$translitMap = [
    // Основные слова
    'Данк' => 'Dunk',
    'данк' => 'Dunk',
    'Лоу' => 'Low',
    'лоу' => 'Low',
    'Хай' => 'High',
    'хай' => 'High',
    'Мид' => 'Mid',
    'мид' => 'Mid',
    
    // Бренды (если попали в название модели)
    'Найк' => 'Nike',
    'найк' => 'Nike',
    'Эйр' => 'Air',
    'эйр' => 'Air',
    'Форс' => 'Force',
    'форс' => 'Force',
    'Зум' => 'Zoom',
    'зум' => 'Zoom',
    
    // Дополнительные слова
    'Кроссовки' => '',
    'кроссовки' => '',
    'рюкзак' => 'Bag',
    'Грязный' => 'Dirty',
    'грязный' => 'Dirty',
    
    // Артефакты
    'ФЗББ' => '',
    'фзбб' => '',
    'GS' => 'GS',
    'CNY' => 'CNY',
    'LE' => 'LE',
    'vibe' => 'Vibe',
    'Disrupt' => 'Disrupt',
];

$products = Product::find()
    ->with('brand')
    ->where(['is_active' => 1])
    ->all();

$totalCount = count($products);
echo "Найдено товаров: {$totalCount}\n";
echo str_repeat('-', 80) . "\n\n";

$updated = 0;
$skipped = 0;
$errors = 0;

foreach ($products as $product) {
    $name = $product->name;
    $brandName = $product->brand ? $product->brand->name : '';
    $articleCode = $product->vendor_code ?: $product->style_code;
    
    // Шаг 1: Убираем бренд из начала
    if ($brandName) {
        $name = preg_replace('/^' . preg_quote($brandName, '/') . '\s+/ui', '', $name);
    }
    
    // Шаг 2: Убираем артикул и всё после него
    if ($articleCode) {
        // Ищем позицию артикула
        $pos = strpos($name, $articleCode);
        if ($pos !== false) {
            $name = substr($name, 0, $pos);
        }
    }
    
    // Шаг 3: Убираем лишние пробелы и знаки
    $name = trim(preg_replace('/\s+/', ' ', $name));
    $name = trim($name, ' ,-:');
    
    // Шаг 4: Применяем транслитерацию
    $translatedModel = $name;
    foreach ($translitMap as $from => $to) {
        if ($to === '') {
            // Просто удаляем слово
            $translatedModel = preg_replace('/\b' . preg_quote($from, '/') . '\b\s*/ui', '', $translatedModel);
        } else {
            // Заменяем слово
            $translatedModel = preg_replace('/\b' . preg_quote($from, '/') . '\b/ui', $to, $translatedModel);
        }
    }
    
    // Шаг 5: Очистка финальная
    $translatedModel = trim(preg_replace('/\s+/', ' ', $translatedModel));
    
    // Шаг 6: Оставляем только первые 2-4 слова
    $words = explode(' ', $translatedModel);
    $words = array_filter($words); // Убираем пустые
    $modelWords = array_slice($words, 0, 4);
    $finalModel = implode(' ', $modelWords);
    
    // Если модель пустая - берем оригинальное название без бренда
    if (empty($finalModel)) {
        $finalModel = $translatedModel ?: 'Model';
    }
    
    echo "[UPDATE] ID {$product->id}\n";
    echo "  Исходное название: {$product->name}\n";
    echo "  Бренд: {$brandName}\n";
    echo "  Артикул: {$articleCode}\n";
    echo "  Модель (англ): {$finalModel}\n";
    
    // Сохраняем
    $product->model_name = $finalModel;
    
    if ($product->save(false)) {
        echo "  ✅ Сохранено\n\n";
        $updated++;
    } else {
        echo "  ❌ ОШИБКА: " . json_encode($product->errors) . "\n\n";
        $errors++;
    }
}

echo str_repeat('=', 80) . "\n";
echo "ИТОГИ:\n";
echo "  Всего товаров: {$totalCount}\n";
echo "  Обновлено: {$updated}\n";
echo "  Ошибок: {$errors}\n";
echo str_repeat('=', 80) . "\n";

// Показываем примеры
echo "\nПримеры обновленных товаров:\n";
$examples = Product::find()
    ->with('brand')
    ->where(['is_active' => 1])
    ->limit(10)
    ->all();

foreach ($examples as $p) {
    $brand = $p->brand ? $p->brand->name : 'NO BRAND';
    $model = $p->model_name ?: 'NO MODEL';
    $article = $p->vendor_code ?: $p->style_code ?: 'NO ARTICLE';
    echo "  • {$brand} {$model} {$article}\n";
}

echo "\n=== ЗАПОЛНЕНИЕ ЗАВЕРШЕНО ===\n";
