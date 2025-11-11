#!/usr/bin/env php
<?php
/**
 * Тестовый скрипт для проверки работоспособности ProductRepository
 * 
 * Проверяет:
 * 1. Создание экземпляра репозитория
 * 2. Базовые методы поиска
 * 3. Метод получения похожих товаров
 * 4. Производительность запросов
 */

define('YII_DEBUG', true);
define('YII_ENV', 'dev');

require(__DIR__ . '/vendor/autoload.php');
require(__DIR__ . '/Yii.php');

$config = require(__DIR__ . '/config/console.php');
$application = new yii\console\Application($config);

use app\repositories\ProductRepository;
use app\models\Product;

echo "\n" . str_repeat("=", 80) . "\n";
echo "ТЕСТИРОВАНИЕ ProductRepository\n";
echo str_repeat("=", 80) . "\n\n";

$repo = new ProductRepository();
$errors = [];
$warnings = [];
$success = 0;

// =============================================================================
// ТЕСТ 1: Создание репозитория
// =============================================================================
echo "📋 ТЕСТ 1: Создание экземпляра ProductRepository\n";
if ($repo instanceof ProductRepository) {
    echo "  ✅ ProductRepository успешно создан\n";
    $success++;
} else {
    echo "  ❌ Ошибка создания ProductRepository\n";
    $errors[] = "Не удалось создать экземпляр ProductRepository";
}

// =============================================================================
// ТЕСТ 2: Базовый запрос
// =============================================================================
echo "\n📋 ТЕСТ 2: Базовый запрос (createQuery)\n";
try {
    $query = $repo->createQuery(true);
    echo "  ✅ Базовый запрос создан\n";
    
    $count = $query->count();
    echo "  ℹ️  Найдено активных товаров: {$count}\n";
    
    if ($count > 0) {
        $success++;
    } else {
        $warnings[] = "В БД нет активных товаров";
    }
} catch (\Exception $e) {
    echo "  ❌ Ошибка создания базового запроса: " . $e->getMessage() . "\n";
    $errors[] = "createQuery() - " . $e->getMessage();
}

// =============================================================================
// ТЕСТ 3: Поиск первого товара по ID
// =============================================================================
echo "\n📋 ТЕСТ 3: Поиск товара по ID (findById)\n";
try {
    $firstProduct = Product::find()->where(['is_active' => 1])->one();
    
    if ($firstProduct) {
        $product = $repo->findById($firstProduct->id);
        
        if ($product && $product->id === $firstProduct->id) {
            echo "  ✅ Товар найден по ID: {$product->id}\n";
            echo "  ℹ️  Название: {$product->name}\n";
            $success++;
        } else {
            echo "  ❌ Товар не найден по ID\n";
            $errors[] = "findById() вернул null или неверный товар";
        }
    } else {
        echo "  ⚠️  В БД нет товаров для тестирования\n";
        $warnings[] = "Нет товаров в БД";
    }
} catch (\Exception $e) {
    echo "  ❌ Ошибка поиска по ID: " . $e->getMessage() . "\n";
    $errors[] = "findById() - " . $e->getMessage();
}

// =============================================================================
// ТЕСТ 4: Поиск товара по slug
// =============================================================================
echo "\n📋 ТЕСТ 4: Поиск товара по slug (findBySlug)\n";
try {
    $firstProduct = Product::find()->where(['is_active' => 1])->one();
    
    if ($firstProduct && $firstProduct->slug) {
        $product = $repo->findBySlug($firstProduct->slug);
        
        if ($product && $product->slug === $firstProduct->slug) {
            echo "  ✅ Товар найден по slug: {$product->slug}\n";
            echo "  ℹ️  Название: {$product->name}\n";
            $success++;
        } else {
            echo "  ❌ Товар не найден по slug\n";
            $errors[] = "findBySlug() вернул null или неверный товар";
        }
    } else {
        echo "  ⚠️  Нет товара с slug для тестирования\n";
        $warnings[] = "Нет товаров с slug";
    }
} catch (\Exception $e) {
    echo "  ❌ Ошибка поиска по slug: " . $e->getMessage() . "\n";
    $errors[] = "findBySlug() - " . $e->getMessage();
}

// =============================================================================
// ТЕСТ 5: Похожие товары (главный тест)
// =============================================================================
echo "\n📋 ТЕСТ 5: Получение похожих товаров (findSimilarProducts)\n";
try {
    $testProduct = Product::find()
        ->where(['is_active' => 1])
        ->with(['brand', 'category'])
        ->one();
    
    if ($testProduct) {
        echo "  ℹ️  Тестовый товар: {$testProduct->name} (ID: {$testProduct->id})\n";
        
        $startTime = microtime(true);
        $similarProducts = $repo->findSimilarProducts($testProduct, 12);
        $endTime = microtime(true);
        $executionTime = round(($endTime - $startTime) * 1000, 2);
        
        echo "  ✅ Метод findSimilarProducts выполнен за {$executionTime}ms\n";
        echo "  ℹ️  Найдено похожих товаров: " . count($similarProducts) . "\n";
        
        // Проверка уникальности
        $ids = [];
        $duplicates = false;
        foreach ($similarProducts as $prod) {
            if (in_array($prod->id, $ids)) {
                $duplicates = true;
                break;
            }
            $ids[] = $prod->id;
        }
        
        if (!$duplicates) {
            echo "  ✅ Все товары уникальны (нет дубликатов)\n";
        } else {
            echo "  ❌ Обнаружены дубликаты товаров\n";
            $errors[] = "findSimilarProducts() вернул дубликаты";
        }
        
        // Проверка, что текущий товар не включен
        $containsSelf = false;
        foreach ($similarProducts as $prod) {
            if ($prod->id === $testProduct->id) {
                $containsSelf = true;
                break;
            }
        }
        
        if (!$containsSelf) {
            echo "  ✅ Текущий товар не включен в похожие\n";
        } else {
            echo "  ❌ Текущий товар включен в список похожих\n";
            $errors[] = "findSimilarProducts() включил текущий товар";
        }
        
        if (!$duplicates && !$containsSelf) {
            $success++;
        }
        
        // Показать несколько похожих товаров
        if (count($similarProducts) > 0) {
            echo "  ℹ️  Примеры похожих товаров:\n";
            $limit = min(3, count($similarProducts));
            for ($i = 0; $i < $limit; $i++) {
                $prod = $similarProducts[$i];
                echo "     - {$prod->name} (ID: {$prod->id})\n";
            }
        }
        
    } else {
        echo "  ⚠️  Нет товаров для тестирования похожих товаров\n";
        $warnings[] = "Нет товаров для теста похожих товаров";
    }
} catch (\Exception $e) {
    echo "  ❌ Ошибка получения похожих товаров: " . $e->getMessage() . "\n";
    echo "  ℹ️  Stack trace:\n" . $e->getTraceAsString() . "\n";
    $errors[] = "findSimilarProducts() - " . $e->getMessage();
}

// =============================================================================
// ТЕСТ 6: Популярные товары
// =============================================================================
echo "\n📋 ТЕСТ 6: Получение популярных товаров (findPopular)\n";
try {
    $popular = $repo->findPopular(5);
    echo "  ✅ Получено популярных товаров: " . count($popular) . "\n";
    $success++;
} catch (\Exception $e) {
    echo "  ❌ Ошибка получения популярных товаров: " . $e->getMessage() . "\n";
    $errors[] = "findPopular() - " . $e->getMessage();
}

// =============================================================================
// ТЕСТ 7: Новинки
// =============================================================================
echo "\n📋 ТЕСТ 7: Получение новинок (findNew)\n";
try {
    $newProducts = $repo->findNew(5);
    echo "  ✅ Получено новинок: " . count($newProducts) . "\n";
    $success++;
} catch (\Exception $e) {
    echo "  ❌ Ошибка получения новинок: " . $e->getMessage() . "\n";
    $errors[] = "findNew() - " . $e->getMessage();
}

// =============================================================================
// ТЕСТ 8: Счетчики
// =============================================================================
echo "\n📋 ТЕСТ 8: Счетчики (countActive)\n";
try {
    $count = $repo->countActive();
    echo "  ✅ Количество активных товаров: {$count}\n";
    $success++;
} catch (\Exception $e) {
    echo "  ❌ Ошибка подсчета товаров: " . $e->getMessage() . "\n";
    $errors[] = "countActive() - " . $e->getMessage();
}

// =============================================================================
// ИТОГИ
// =============================================================================
echo "\n" . str_repeat("=", 80) . "\n";
echo "ИТОГИ ТЕСТИРОВАНИЯ\n";
echo str_repeat("=", 80) . "\n\n";

echo "✅ Успешных тестов: {$success}\n";
echo "❌ Ошибок: " . count($errors) . "\n";
echo "⚠️  Предупреждений: " . count($warnings) . "\n";

if (count($errors) > 0) {
    echo "\n🔴 СПИСОК ОШИБОК:\n";
    foreach ($errors as $i => $error) {
        echo "  " . ($i + 1) . ". {$error}\n";
    }
}

if (count($warnings) > 0) {
    echo "\n🟡 СПИСОК ПРЕДУПРЕЖДЕНИЙ:\n";
    foreach ($warnings as $i => $warning) {
        echo "  " . ($i + 1) . ". {$warning}\n";
    }
}

echo "\n";

if (count($errors) === 0) {
    echo "🎉 ВСЕ ТЕСТЫ ПРОЙДЕНЫ УСПЕШНО!\n";
    echo "ProductRepository готов к использованию в production.\n\n";
    exit(0);
} else {
    echo "❌ ТЕСТИРОВАНИЕ ПРОВАЛЕНО\n";
    echo "Необходимо исправить ошибки перед использованием.\n\n";
    exit(1);
}
