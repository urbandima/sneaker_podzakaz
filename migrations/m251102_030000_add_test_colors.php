<?php

use yii\db\Migration;

/**
 * Добавление тестовых цветов к товарам
 */
class m251102_030000_add_test_colors extends Migration
{
    public function safeUp()
    {
        // Удаляем старые цвета если есть
        $this->delete('{{%product_color}}');
        
        // Добавляем цвета к товарам
        $colors = [
            // Nike Air Max 90 - 3 цвета
            ['product_id' => 1, 'color_name' => 'Белый', 'color_hex' => '#FFFFFF', 'is_available' => 1],
            ['product_id' => 1, 'color_name' => 'Черный', 'color_hex' => '#000000', 'is_available' => 1],
            ['product_id' => 1, 'color_name' => 'Красный', 'color_hex' => '#DC2626', 'is_available' => 1],
            
            // Adidas Superstar - 4 цвета
            ['product_id' => 2, 'color_name' => 'Белый', 'color_hex' => '#FFFFFF', 'is_available' => 1],
            ['product_id' => 2, 'color_name' => 'Черный', 'color_hex' => '#000000', 'is_available' => 1],
            ['product_id' => 2, 'color_name' => 'Синий', 'color_hex' => '#2563EB', 'is_available' => 1],
            ['product_id' => 2, 'color_name' => 'Зеленый', 'color_hex' => '#16A34A', 'is_available' => 0],
            
            // Puma Suede - 5 цветов
            ['product_id' => 3, 'color_name' => 'Черный', 'color_hex' => '#000000', 'is_available' => 1],
            ['product_id' => 3, 'color_name' => 'Красный', 'color_hex' => '#DC2626', 'is_available' => 1],
            ['product_id' => 3, 'color_name' => 'Синий', 'color_hex' => '#2563EB', 'is_available' => 1],
            ['product_id' => 3, 'color_name' => 'Серый', 'color_hex' => '#6B7280', 'is_available' => 1],
            ['product_id' => 3, 'color_name' => 'Коричневый', 'color_hex' => '#92400E', 'is_available' => 1],
            
            // New Balance 574 - 6 цветов
            ['product_id' => 4, 'color_name' => 'Серый', 'color_hex' => '#6B7280', 'is_available' => 1],
            ['product_id' => 4, 'color_name' => 'Синий', 'color_hex' => '#2563EB', 'is_available' => 1],
            ['product_id' => 4, 'color_name' => 'Зеленый', 'color_hex' => '#16A34A', 'is_available' => 1],
            ['product_id' => 4, 'color_name' => 'Красный', 'color_hex' => '#DC2626', 'is_available' => 1],
            ['product_id' => 4, 'color_name' => 'Черный', 'color_hex' => '#000000', 'is_available' => 1],
            ['product_id' => 4, 'color_name' => 'Белый', 'color_hex' => '#FFFFFF', 'is_available' => 0],
            
            // Футболки - 4 цвета
            ['product_id' => 5, 'color_name' => 'Белый', 'color_hex' => '#FFFFFF', 'is_available' => 1],
            ['product_id' => 5, 'color_name' => 'Черный', 'color_hex' => '#000000', 'is_available' => 1],
            ['product_id' => 5, 'color_name' => 'Серый', 'color_hex' => '#6B7280', 'is_available' => 1],
            ['product_id' => 5, 'color_name' => 'Синий', 'color_hex' => '#2563EB', 'is_available' => 1],
            
            // Толстовка - 3 цвета
            ['product_id' => 6, 'color_name' => 'Черный', 'color_hex' => '#000000', 'is_available' => 1],
            ['product_id' => 6, 'color_name' => 'Серый', 'color_hex' => '#6B7280', 'is_available' => 1],
            ['product_id' => 6, 'color_name' => 'Синий', 'color_hex' => '#2563EB', 'is_available' => 1],
            
            // Куртка - 2 цвета
            ['product_id' => 7, 'color_name' => 'Черный', 'color_hex' => '#000000', 'is_available' => 1],
            ['product_id' => 7, 'color_name' => 'Синий', 'color_hex' => '#2563EB', 'is_available' => 1],
            
            // Штаны - 3 цвета
            ['product_id' => 8, 'color_name' => 'Черный', 'color_hex' => '#000000', 'is_available' => 1],
            ['product_id' => 8, 'color_name' => 'Серый', 'color_hex' => '#6B7280', 'is_available' => 1],
            ['product_id' => 8, 'color_name' => 'Синий', 'color_hex' => '#2563EB', 'is_available' => 1],
        ];
        
        foreach ($colors as $colorData) {
            $this->insert('{{%product_color}}', [
                'product_id' => $colorData['product_id'],
                'name' => $colorData['color_name'],
                'hex' => $colorData['color_hex'],
            ]);
        }
        
        echo "✅ Добавлено " . count($colors) . " цветов для товаров\n";
    }

    public function safeDown()
    {
        $this->delete('{{%product_color}}');
    }
}
