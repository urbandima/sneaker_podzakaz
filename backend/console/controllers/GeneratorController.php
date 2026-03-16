<?php

namespace app\console\controllers;

use Yii;
use yii\console\Controller;
use yii\console\ExitCode;
use app\backend\modules\catalog\models\Product;
use app\backend\modules\catalog\models\Brand;
use app\backend\modules\catalog\models\Category;

/**
 * Генератор тестовых товаров
 */
class GeneratorController extends Controller
{
    // База для генерации
    private $brands = ['Nike', 'Adidas', 'New Balance', 'Puma', 'Reebok', 'Asics', 'Vans', 'Converse'];
    private $models = [
        'Nike' => ['Air Max 90', 'Air Force 1', 'Dunk Low', 'Blazer', 'Cortez', 'Air Jordan 1', 'React', 'Pegasus', 'Zoom'],
        'Adidas' => ['Superstar', 'Stan Smith', 'Samba', 'Gazelle', 'Ultraboost', 'NMD', 'Forum', 'Yeezy 350', 'Campus'],
        'New Balance' => ['530', '550', '574', '990', '2002R', '9060', '1906R'],
        'Puma' => ['Suede', 'RS-X', 'Clyde', 'Future Rider', 'Slipstream'],
        'Reebok' => ['Classic Leather', 'Club C', 'Pump', 'Zig Kinetica'],
        'Asics' => ['Gel-Lyte III', 'Gel-Kayano', 'Gel-1130', 'GT-2160'],
        'Vans' => ['Old Skool', 'Sk8-Hi', 'Authentic', 'Era', 'Slip-On'],
        'Converse' => ['Chuck Taylor', 'One Star', 'Pro Leather', 'Run Star'],
    ];
    private $colors = [
        'White', 'Black', 'Grey', 'Navy', 'Red', 'Blue', 'Green', 'Beige', 'Brown', 'Pink',
        'Triple White', 'Triple Black', 'Black White', 'White Black', 'Sail', 'Cream',
        'Panda', 'Wolf Grey', 'University Blue', 'Chicago', 'Bred', 'Royal'
    ];
    private $seasons = ['2023', '2024', '2025'];
    
    /**
     * Генерирует тестовые товары
     * Usage: php yii generator/create <count>
     */
    public function actionCreate($count = 100)
    {
        $this->stdout("🔄 Генерация {$count} тестовых товаров...\n\n");

        // Получаем категорию
        $category = Category::findOne(['slug' => 'sneakers']);
        if (!$category) {
            $category = new Category();
            $category->name = 'Кроссовки';
            $category->slug = 'sneakers';
            $category->save();
        }

        $created = 0;
        $skipped = 0;

        for ($i = 0; $i < $count; $i++) {
            try {
                // Генерируем случайный товар
                $productData = $this->generateProduct();
                
                // Проверяем существующий
                $existing = Product::findOne(['slug' => $productData['slug']]);
                if ($existing) {
                    $skipped++;
                    continue;
                }

                // Получаем/создаём бренд
                $brand = $this->getOrCreateBrand($productData['brand']);

                // Создаём товар
                $product = new Product();
                $product->name = $productData['name'];
                $product->slug = $productData['slug'];
                $product->brand_id = $brand->id;
                $product->category_id = $category->id;
                $product->price = $productData['price'];
                $product->old_price = $productData['old_price'];
                $product->stock_status = 'in_stock';
                $product->description = $productData['description'];
                $product->main_image = $productData['image'];
                $product->gender = $productData['gender'];
                $product->is_active = 1;
                $product->is_featured = $productData['is_featured'];
                $product->rating = $productData['rating'];
                $product->reviews_count = $productData['reviews_count'];
                $product->created_at = time() - rand(0, 86400 * 30); // Последние 30 дней
                $product->updated_at = time();

                if ($product->save()) {
                    $created++;
                    $this->stdout("✅ [{$created}/{$count}] {$product->name} ({$product->price} BYN)\n");
                } else {
                    $skipped++;
                }

            } catch (\Exception $e) {
                $skipped++;
                $this->stderr("❌ Ошибка: {$e->getMessage()}\n");
            }
        }

        $this->stdout("\n" . str_repeat("=", 50) . "\n");
        $this->stdout("📊 РЕЗУЛЬТАТЫ:\n");
        $this->stdout("✅ Создано: {$created}\n");
        $this->stdout("⏭️  Пропущено: {$skipped}\n");
        $this->stdout(str_repeat("=", 50) . "\n");

        return ExitCode::OK;
    }

    /**
     * Генерирует случайный товар
     */
    private function generateProduct()
    {
        $brandName = $this->brands[array_rand($this->brands)];
        $models = $this->models[$brandName] ?? ['Classic', 'Pro', 'Elite'];
        $model = $models[array_rand($models)];
        $color = $this->colors[array_rand($this->colors)];
        
        // Формируем название
        $name = "Кроссовки {$brandName} {$model} \"{$color}\"";
        
        // Добавляем Wmns для некоторых
        $gender = 'unisex';
        if (rand(0, 3) == 0) {
            $name .= ' Wmns';
            $gender = 'female';
        } elseif (rand(0, 3) == 1) {
            $gender = 'male';
        }

        // Генерируем slug
        $slug = $this->generateSlug($name);

        // Генерируем цену (100-500 BYN)
        $price = round(rand(100, 500) + rand(0, 99) / 100, 2);
        $oldPrice = null;
        
        // 40% товаров со скидкой
        if (rand(0, 100) < 40) {
            $oldPrice = round($price * rand(120, 150) / 100, 2);
        }

        // Описание
        $descriptions = [
            "Классические кроссовки {$brandName} {$model} в расцветке {$color}. Оригинальная модель из США.",
            "Стильные кроссовки {$brandName} {$model}. Подходят для повседневной носки и активного отдыха.",
            "{$brandName} {$model} - культовая модель с уникальным дизайном. 100% оригинал.",
            "Легендарные кроссовки {$brandName} {$model} {$color}. Отличное качество и комфорт.",
        ];
        $description = $descriptions[array_rand($descriptions)];

        // Placeholder image
        $image = '/uploads/products/placeholder.jpg';

        return [
            'name' => $name,
            'slug' => $slug,
            'brand' => $brandName,
            'price' => $price,
            'old_price' => $oldPrice,
            'description' => $description,
            'image' => $image,
            'gender' => $gender,
            'is_featured' => rand(0, 1),
            'rating' => round(rand(35, 50) / 10, 1), // 3.5-5.0
            'reviews_count' => rand(5, 500),
        ];
    }

    /**
     * Получает или создаёт бренд
     */
    private function getOrCreateBrand($brandName)
    {
        $brand = Brand::findOne(['name' => $brandName]);
        if (!$brand) {
            $brand = new Brand();
            $brand->name = $brandName;
            $brand->slug = strtolower($brandName);
            $brand->save();
            $this->stdout("✨ Создан бренд: {$brandName}\n");
        }
        return $brand;
    }

    /**
     * Генерирует slug
     */
    private function generateSlug($name)
    {
        $converter = [
            'а' => 'a', 'б' => 'b', 'в' => 'v', 'г' => 'g', 'д' => 'd',
            'е' => 'e', 'ё' => 'e', 'ж' => 'zh', 'з' => 'z', 'и' => 'i',
            'й' => 'y', 'к' => 'k', 'л' => 'l', 'м' => 'm', 'н' => 'n',
            'о' => 'o', 'п' => 'p', 'р' => 'r', 'с' => 's', 'т' => 't',
            'у' => 'u', 'ф' => 'f', 'х' => 'h', 'ц' => 'c', 'ч' => 'ch',
            'ш' => 'sh', 'щ' => 'sch', 'ь' => '', 'ы' => 'y', 'ъ' => '',
            'э' => 'e', 'ю' => 'yu', 'я' => 'ya',
        ];

        $slug = mb_strtolower($name);
        $slug = strtr($slug, $converter);
        $slug = preg_replace('/[^a-z0-9\s-]/', '', $slug);
        $slug = preg_replace('/[\s-]+/', '-', $slug);
        $slug = trim($slug, '-');
        $slug = substr($slug, 0, 100);
        
        // Добавляем случайность для уникальности
        $slug .= '-' . uniqid();
        
        return $slug;
    }
}
