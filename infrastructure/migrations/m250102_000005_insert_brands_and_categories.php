<?php

use yii\db\Migration;

/**
 * Добавление брендов и категорий для тестовых товаров
 */
class m250102_000005_insert_brands_and_categories extends Migration
{
    public function safeUp()
    {
        // Добавляем бренды
        $brands = [
            ['name' => 'Nike', 'slug' => 'nike', 'is_active' => 1, 'created_at' => time()],
            ['name' => 'Adidas', 'slug' => 'adidas', 'is_active' => 1, 'created_at' => time()],
            ['name' => 'New Balance', 'slug' => 'new-balance', 'is_active' => 1, 'created_at' => time()],
            ['name' => 'Puma', 'slug' => 'puma', 'is_active' => 1, 'created_at' => time()],
            ['name' => 'Converse', 'slug' => 'converse', 'is_active' => 1, 'created_at' => time()],
            ['name' => 'Vans', 'slug' => 'vans', 'is_active' => 1, 'created_at' => time()],
            ['name' => 'Reebok', 'slug' => 'reebok', 'is_active' => 1, 'created_at' => time()],
            ['name' => 'Asics', 'slug' => 'asics', 'is_active' => 1, 'created_at' => time()],
        ];
        
        foreach ($brands as $brand) {
            // Проверяем, не существует ли уже
            $exists = $this->db->createCommand(
                'SELECT id FROM {{%brand}} WHERE slug = :slug',
                [':slug' => $brand['slug']]
            )->queryScalar();
            
            if (!$exists) {
                $this->insert('{{%brand}}', $brand);
            }
        }
        
        // Добавляем категории
        $categories = [
            ['name' => 'Кроссовки', 'slug' => 'sneakers', 'parent_id' => null, 'is_active' => 1, 'created_at' => time()],
            ['name' => 'Ботинки', 'slug' => 'boots', 'parent_id' => null, 'is_active' => 1, 'created_at' => time()],
            ['name' => 'Кеды', 'slug' => 'keds', 'parent_id' => null, 'is_active' => 1, 'created_at' => time()],
            ['name' => 'Слипоны', 'slug' => 'slip-ons', 'parent_id' => null, 'is_active' => 1, 'created_at' => time()],
        ];
        
        foreach ($categories as $category) {
            $exists = $this->db->createCommand(
                'SELECT id FROM {{%category}} WHERE slug = :slug',
                [':slug' => $category['slug']]
            )->queryScalar();
            
            if (!$exists) {
                $this->insert('{{%category}}', $category);
            }
        }
        
        echo "✓ Добавлены бренды и категории\n";
    }

    public function safeDown()
    {
        $this->delete('{{%brand}}', ['slug' => [
            'nike', 'adidas', 'new-balance', 'puma', 
            'converse', 'vans', 'reebok', 'asics'
        ]]);
        
        $this->delete('{{%category}}', ['slug' => [
            'sneakers', 'boots', 'keds', 'slip-ons'
        ]]);
    }
}
