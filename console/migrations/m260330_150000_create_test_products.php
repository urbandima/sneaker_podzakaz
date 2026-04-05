<?php

use yii\db\Migration;

/**
 * Миграция для создания тестовых товаров с фото и характеристиками
 */
class m260330_150000_create_test_products extends Migration
{
    public function safeUp()
    {
        $brands = ['Nike', 'Adidas', 'Jordan', 'New Balance', 'Puma'];
        $models = ['Air Force 1', 'Dunk Low', 'Air Max 90', 'Jordan 1', 'Jordan 4', 'Ultra Boost', 'Samba', '550'];
        $colors = ['Белый', 'Черный', 'Серый', 'Красный', 'Синий'];
        $materials = ['Кожа', 'Замша', 'Сетка', 'Канвас'];
        $seasons = ['Лето', 'Зима', 'Демисезон'];
        $genders = ['Мужской', 'Женский', 'Унисекс'];

        // Создаем бренды если нет
        foreach ($brands as $brand) {
            $exists = $this->db->createCommand("SELECT id FROM {{%brand}} WHERE name = :name", [':name' => $brand])->queryScalar();
            if (!$exists) {
                $this->insert('{{%brand}}', [
                    'name' => $brand,
                    'slug' => strtolower($brand),
                    'description' => "Бренд {$brand}",
                    'created_at' => time(),
                    'updated_at' => time(),
                ]);
            }
        }

        // Создаем категорию если нет
        $categoryId = $this->db->createCommand("SELECT id FROM {{%category}} WHERE slug = 'krossovki' LIMIT 1")->queryScalar();
        if (!$categoryId) {
            $this->insert('{{%category}}', [
                'name' => 'Кроссовки',
                'slug' => 'krossovki',
                'description' => 'Категория кроссовок',
                'created_at' => time(),
                'updated_at' => time(),
            ]);
            $categoryId = $this->db->getLastInsertID();
        }

        // Создаем 8 тестовых товаров
        for ($i = 1; $i <= 8; $i++) {
            $brandName = $brands[array_rand($brands)];
            $modelName = $models[array_rand($models)];
            $color = $colors[array_rand($colors)];
            $brandId = $this->db->createCommand("SELECT id FROM {{%brand}} WHERE name = :name", [':name' => $brandName])->queryScalar();
            
            $price = rand(150, 800);
            $oldPrice = rand(0, 3) === 0 ? $price + rand(50, 200) : null;
            
            $this->insert('{{%product}}', [
                'name' => "{$brandName} {$modelName} {$color} - Тест {$i}",
                'slug' => "test-product-{$i}-" . time(),
                'description' => $this->generateDescription($brandName, $modelName, $color),
                'price' => $price,
                'old_price' => $oldPrice,
                'category_id' => $categoryId,
                'brand_id' => $brandId,
                'model_name' => $modelName,
                'main_image' => "https://placehold.co/600x400/3b82f6/ffffff?text={$brandName}+" . urlencode($modelName),
                'is_active' => 1,
                'is_featured' => rand(0, 3) === 0 ? 1 : 0,
                'stock_status' => ['in_stock', 'in_stock', 'in_stock', 'out_of_stock', 'preorder'][array_rand([0,1,2,3,4])],
                'material' => $materials[array_rand($materials)],
                'season' => $seasons[array_rand($seasons)],
                'gender' => $genders[array_rand($genders)],
                'views_count' => rand(0, 500),
                'rating' => rand(30, 50) / 10,
                'reviews_count' => rand(0, 20),
                'created_at' => time() - rand(0, 2592000),
                'updated_at' => time(),
            ]);
            
            $productId = $this->db->getLastInsertID();
            
            // Добавляем изображения
            $this->addProductImages($productId, $brandName, $modelName);
            
            // Добавляем размеры
            $this->addProductSizes($productId);
            
            echo "✓ Создан товар #{$productId}\n";
        }
        
        echo "\n✓ Создано 8 тестовых товаров с фото и характеристиками\n";
    }

    public function safeDown()
    {
        // Удаляем тестовые товары
        $this->db->createCommand("DELETE FROM {{%product}} WHERE name LIKE '%- Тест %'")->execute();
        echo "✓ Тестовые товары удалены\n";
    }
    
    private function generateDescription($brand, $model, $color)
    {
        return "Оригинальные кроссовки {$brand} {$model} в цвете {$color}. Премиальные материалы, оригинальная фурнитура, удобная посадка, стильный дизайн. Идеально подходят для повседневной носки.";
    }
    
    private function addProductImages($productId, $brand, $model)
    {
        $colors = ['3b82f6', '8b5cf6', 'ec4899', '10b981', 'f59e0b'];
        
        for ($i = 0; $i < 3; $i++) {
            $color = $colors[$i];
            $text = $i === 0 ? urlencode("{$brand} {$model}") : urlencode("Вид " . ($i + 1));
            
            $this->insert('{{%product_image}}', [
                'product_id' => $productId,
                'url' => "https://placehold.co/600x400/{$color}/ffffff?text={$text}",
                'alt' => "Фото " . ($i + 1),
                'position' => $i,
                'is_main' => $i === 0 ? 1 : 0,
                'created_at' => time(),
            ]);
        }
    }
    
    private function addProductSizes($productId)
    {
        $euSizes = [36, 37, 38, 39, 40, 41, 42, 43, 44, 45];
        $usSizes = [4, 5, 5.5, 6, 6.5, 7, 8, 9, 10, 11];
        $ukSizes = [3.5, 4, 4.5, 5, 5.5, 6, 7, 8, 9, 10];
        
        foreach ($euSizes as $i => $euSize) {
            $stock = rand(0, 15);
            $this->insert('{{%product_size}}', [
                'product_id' => $productId,
                'size_eu' => $euSize,
                'size_us' => $usSizes[$i] ?? null,
                'size_uk' => $ukSizes[$i] ?? null,
                'stock_quantity' => $stock,
                'is_available' => $stock > 0 ? 1 : 0,
                'sku' => "TEST-{$productId}-{$euSize}",
            ]);
        }
    }
}
