<?php

use yii\db\Migration;

/**
 * Добавление реальных фото к тестовым товарам
 */
class m251102_000000_add_product_images extends Migration
{
    public function safeUp()
    {
        // Обновляем товары с правильными изображениями (используем Unsplash)
        $products = [
            1 => 'https://images.unsplash.com/photo-1542291026-7eec264c27ff?w=800&q=80', // Nike кроссовки
            2 => 'https://images.unsplash.com/photo-1606107557195-0e29a4b5b4aa?w=800&q=80', // Adidas кроссовки
            3 => 'https://images.unsplash.com/photo-1608231387042-66d1773070a5?w=800&q=80', // Puma кроссовки
            4 => 'https://images.unsplash.com/photo-1556906781-9a412961c28c?w=800&q=80', // New Balance кроссовки
            5 => 'https://images.unsplash.com/photo-1521572163474-6864f9cf17ab?w=800&q=80', // Футболка
            6 => 'https://images.unsplash.com/photo-1556821840-3a63f95609a7?w=800&q=80', // Толстовка
            7 => 'https://images.unsplash.com/photo-1551488831-00ddcb6c6bd3?w=800&q=80', // Куртка
            8 => 'https://images.unsplash.com/photo-1624378439575-d8705ad7ae80?w=800&q=80', // Штаны
        ];

        foreach ($products as $productId => $imageUrl) {
            $this->update('{{%product}}', [
                'main_image' => $imageUrl
            ], ['id' => $productId]);
        }

        // Добавляем дополнительные изображения в product_image
        $additionalImages = [
            // Nike кроссовки
            ['product_id' => 1, 'image_path' => 'https://images.unsplash.com/photo-1542291026-7eec264c27ff?w=800&q=80', 'sort_order' => 1],
            ['product_id' => 1, 'image_path' => 'https://images.unsplash.com/photo-1595950653106-6c9ebd614d3a?w=800&q=80', 'sort_order' => 2],
            ['product_id' => 1, 'image_path' => 'https://images.unsplash.com/photo-1491553895911-0055eca6402d?w=800&q=80', 'sort_order' => 3],
            
            // Adidas кроссовки
            ['product_id' => 2, 'image_path' => 'https://images.unsplash.com/photo-1606107557195-0e29a4b5b4aa?w=800&q=80', 'sort_order' => 1],
            ['product_id' => 2, 'image_path' => 'https://images.unsplash.com/photo-1608231387042-66d1773070a5?w=800&q=80', 'sort_order' => 2],
            
            // Puma кроссовки
            ['product_id' => 3, 'image_path' => 'https://images.unsplash.com/photo-1608231387042-66d1773070a5?w=800&q=80', 'sort_order' => 1],
            ['product_id' => 3, 'image_path' => 'https://images.unsplash.com/photo-1460353581641-37baddab0fa2?w=800&q=80', 'sort_order' => 2],
            
            // New Balance кроссовки
            ['product_id' => 4, 'image_path' => 'https://images.unsplash.com/photo-1556906781-9a412961c28c?w=800&q=80', 'sort_order' => 1],
            ['product_id' => 4, 'image_path' => 'https://images.unsplash.com/photo-1543508282-6319a3e2621f?w=800&q=80', 'sort_order' => 2],
            
            // Футболка
            ['product_id' => 5, 'image_path' => 'https://images.unsplash.com/photo-1521572163474-6864f9cf17ab?w=800&q=80', 'sort_order' => 1],
            ['product_id' => 5, 'image_path' => 'https://images.unsplash.com/photo-1583743814966-8936f5b7be1a?w=800&q=80', 'sort_order' => 2],
            
            // Толстовка
            ['product_id' => 6, 'image_path' => 'https://images.unsplash.com/photo-1556821840-3a63f95609a7?w=800&q=80', 'sort_order' => 1],
            ['product_id' => 6, 'image_path' => 'https://images.unsplash.com/photo-1620799140408-edc6dcb6d633?w=800&q=80', 'sort_order' => 2],
            
            // Куртка
            ['product_id' => 7, 'image_path' => 'https://images.unsplash.com/photo-1551488831-00ddcb6c6bd3?w=800&q=80', 'sort_order' => 1],
            ['product_id' => 7, 'image_path' => 'https://images.unsplash.com/photo-1544923246-77d2c2d5357c?w=800&q=80', 'sort_order' => 2],
            
            // Штаны
            ['product_id' => 8, 'image_path' => 'https://images.unsplash.com/photo-1624378439575-d8705ad7ae80?w=800&q=80', 'sort_order' => 1],
            ['product_id' => 8, 'image_path' => 'https://images.unsplash.com/photo-1473966968600-fa801b869a1a?w=800&q=80', 'sort_order' => 2],
        ];

        foreach ($additionalImages as $imageData) {
            // Переименовываем image_path в image
            $this->insert('{{%product_image}}', [
                'product_id' => $imageData['product_id'],
                'image' => $imageData['image_path'],
                'is_main' => 0,
                'sort_order' => $imageData['sort_order'],
                'created_at' => time(),
            ]);
        }

        echo "✅ Добавлено изображений: " . count($additionalImages) . "\n";
    }

    public function safeDown()
    {
        // Удаляем добавленные изображения
        $this->delete('{{%product_image}}');
        
        // Очищаем main_image
        $this->update('{{%product}}', ['main_image' => null]);
    }
}
