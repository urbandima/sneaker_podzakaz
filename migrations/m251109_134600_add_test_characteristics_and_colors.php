<?php

use yii\db\Migration;

/**
 * Добавление тестовых характеристик, значений и цветов для динамических фильтров
 */
class m251109_134600_add_test_characteristics_and_colors extends Migration
{
    public function safeUp()
    {
        // 1. Добавляем характеристики для фильтров
        $this->batchInsert('{{%characteristic}}', ['key', 'name', 'type', 'is_filter', 'is_active', 'sort_order'], [
            ['material', 'Материал', 'multiselect', 1, 1, 1],
            ['season', 'Сезон', 'select', 1, 1, 2],
            ['gender', 'Пол', 'select', 1, 1, 3],
            ['height', 'Высота', 'select', 1, 1, 4],
            ['fastening', 'Тип застежки', 'multiselect', 1, 1, 5],
            ['country_origin', 'Страна производства', 'select', 1, 1, 6],
        ]);

        // 2. Получаем ID характеристик
        $materialId = $this->db->createCommand("SELECT id FROM {{%characteristic}} WHERE `key`='material'")->queryScalar();
        $seasonId = $this->db->createCommand("SELECT id FROM {{%characteristic}} WHERE `key`='season'")->queryScalar();
        $genderId = $this->db->createCommand("SELECT id FROM {{%characteristic}} WHERE `key`='gender'")->queryScalar();
        $heightId = $this->db->createCommand("SELECT id FROM {{%characteristic}} WHERE `key`='height'")->queryScalar();
        $fasteningId = $this->db->createCommand("SELECT id FROM {{%characteristic}} WHERE `key`='fastening'")->queryScalar();
        $countryId = $this->db->createCommand("SELECT id FROM {{%characteristic}} WHERE `key`='country_origin'")->queryScalar();

        // 3. Добавляем значения для Материала
        $this->batchInsert('{{%characteristic_value}}', ['characteristic_id', 'value', 'slug', 'sort_order', 'is_active'], [
            [$materialId, 'Кожа', 'leather', 1, 1],
            [$materialId, 'Замша', 'suede', 2, 1],
            [$materialId, 'Текстиль', 'textile', 3, 1],
            [$materialId, 'Синтетика', 'synthetic', 4, 1],
            [$materialId, 'Сетка', 'mesh', 5, 1],
            [$materialId, 'Канвас', 'canvas', 6, 1],
        ]);

        // 4. Значения для Сезона
        $this->batchInsert('{{%characteristic_value}}', ['characteristic_id', 'value', 'slug', 'sort_order', 'is_active'], [
            [$seasonId, 'Лето', 'summer', 1, 1],
            [$seasonId, 'Зима', 'winter', 2, 1],
            [$seasonId, 'Демисезон', 'demi', 3, 1],
            [$seasonId, 'Всесезон', 'all-season', 4, 1],
        ]);

        // 5. Значения для Пола
        $this->batchInsert('{{%characteristic_value}}', ['characteristic_id', 'value', 'slug', 'sort_order', 'is_active'], [
            [$genderId, 'Мужской', 'male', 1, 1],
            [$genderId, 'Женский', 'female', 2, 1],
            [$genderId, 'Унисекс', 'unisex', 3, 1],
        ]);

        // 6. Значения для Высоты
        $this->batchInsert('{{%characteristic_value}}', ['characteristic_id', 'value', 'slug', 'sort_order', 'is_active'], [
            [$heightId, 'Низкие', 'low', 1, 1],
            [$heightId, 'Средние', 'mid', 2, 1],
            [$heightId, 'Высокие', 'high', 3, 1],
        ]);

        // 7. Значения для Застежки
        $this->batchInsert('{{%characteristic_value}}', ['characteristic_id', 'value', 'slug', 'sort_order', 'is_active'], [
            [$fasteningId, 'Шнурки', 'laces', 1, 1],
            [$fasteningId, 'Липучки', 'velcro', 2, 1],
            [$fasteningId, 'Молния', 'zipper', 3, 1],
            [$fasteningId, 'Slip-on', 'slip-on', 4, 1],
        ]);

        // 8. Значения для Страны
        $this->batchInsert('{{%characteristic_value}}', ['characteristic_id', 'value', 'slug', 'sort_order', 'is_active'], [
            [$countryId, 'Вьетнам', 'vietnam', 1, 1],
            [$countryId, 'Китай', 'china', 2, 1],
            [$countryId, 'Индонезия', 'indonesia', 3, 1],
            [$countryId, 'США', 'usa', 4, 1],
        ]);

        // 9. Добавляем цвета товаров (для первых 20 товаров)
        $products = $this->db->createCommand("SELECT id FROM {{%product}} WHERE is_active=1 LIMIT 20")->queryColumn();
        
        $colors = [
            ['Черный', '#000000'],
            ['Белый', '#FFFFFF'],
            ['Красный', '#EF4444'],
            ['Синий', '#3B82F6'],
            ['Зеленый', '#10B981'],
            ['Серый', '#6B7280'],
            ['Коричневый', '#92400E'],
            ['Желтый', '#F59E0B'],
        ];

        foreach ($products as $index => $productId) {
            // Каждому товару 1-2 цвета
            $color1 = $colors[$index % count($colors)];
            $this->insert('{{%product_color}}', [
                'product_id' => $productId,
                'name' => $color1[0],
                'hex' => $color1[1],
            ]);
            
            // Добавляем второй цвет для половины товаров
            if ($index % 2 === 0 && $index + 1 < count($colors)) {
                $color2 = $colors[($index + 1) % count($colors)];
                $this->insert('{{%product_color}}', [
                    'product_id' => $productId,
                    'name' => $color2[0],
                    'hex' => $color2[1],
                ]);
            }
        }

        // 10. Связываем товары с характеристиками
        // Получаем ID значений
        $leatherId = $this->db->createCommand("SELECT id FROM {{%characteristic_value}} WHERE slug='leather'")->queryScalar();
        $suededId = $this->db->createCommand("SELECT id FROM {{%characteristic_value}} WHERE slug='suede'")->queryScalar();
        $textileId = $this->db->createCommand("SELECT id FROM {{%characteristic_value}} WHERE slug='textile'")->queryScalar();
        $summerId = $this->db->createCommand("SELECT id FROM {{%characteristic_value}} WHERE slug='summer'")->queryScalar();
        $winterId = $this->db->createCommand("SELECT id FROM {{%characteristic_value}} WHERE slug='winter'")->queryScalar();
        $maleId = $this->db->createCommand("SELECT id FROM {{%characteristic_value}} WHERE slug='male'")->queryScalar();
        $femaleId = $this->db->createCommand("SELECT id FROM {{%characteristic_value}} WHERE slug='female'")->queryScalar();
        $lowId = $this->db->createCommand("SELECT id FROM {{%characteristic_value}} WHERE slug='low'")->queryScalar();
        $midId = $this->db->createCommand("SELECT id FROM {{%characteristic_value}} WHERE slug='mid'")->queryScalar();
        $lacesId = $this->db->createCommand("SELECT id FROM {{%characteristic_value}} WHERE slug='laces'")->queryScalar();
        $vietnamId = $this->db->createCommand("SELECT id FROM {{%characteristic_value}} WHERE slug='vietnam'")->queryScalar();
        $chinaId = $this->db->createCommand("SELECT id FROM {{%characteristic_value}} WHERE slug='china'")->queryScalar();

        // Распределяем характеристики по товарам
        $valuesByProduct = [
            // Материал, Сезон, Пол, Высота, Застежка, Страна
            [$leatherId, $summerId, $maleId, $lowId, $lacesId, $vietnamId],
            [$suededId, $winterId, $femaleId, $midId, $lacesId, $chinaId],
            [$textileId, $summerId, $maleId, $lowId, $lacesId, $vietnamId],
            [$leatherId, $winterId, $maleId, $midId, $lacesId, $chinaId],
            [$suededId, $summerId, $femaleId, $lowId, $lacesId, $vietnamId],
        ];

        foreach ($products as $index => $productId) {
            $values = $valuesByProduct[$index % count($valuesByProduct)];
            
            // Материал
            $this->insert('{{%product_characteristic_value}}', [
                'product_id' => $productId,
                'characteristic_id' => $materialId,
                'characteristic_value_id' => $values[0],
            ]);
            
            // Сезон
            $this->insert('{{%product_characteristic_value}}', [
                'product_id' => $productId,
                'characteristic_id' => $seasonId,
                'characteristic_value_id' => $values[1],
            ]);
            
            // Пол
            $this->insert('{{%product_characteristic_value}}', [
                'product_id' => $productId,
                'characteristic_id' => $genderId,
                'characteristic_value_id' => $values[2],
            ]);
            
            // Высота
            $this->insert('{{%product_characteristic_value}}', [
                'product_id' => $productId,
                'characteristic_id' => $heightId,
                'characteristic_value_id' => $values[3],
            ]);
            
            // Застежка
            $this->insert('{{%product_characteristic_value}}', [
                'product_id' => $productId,
                'characteristic_id' => $fasteningId,
                'characteristic_value_id' => $values[4],
            ]);
            
            // Страна
            $this->insert('{{%product_characteristic_value}}', [
                'product_id' => $productId,
                'characteristic_id' => $countryId,
                'characteristic_value_id' => $values[5],
            ]);
        }

        echo "✅ Добавлено:\n";
        echo "   - 6 характеристик\n";
        echo "   - 21 значение характеристик\n";
        echo "   - " . count($products) . " товаров с характеристиками\n";
        echo "   - ~30 цветов товаров\n";
    }

    public function safeDown()
    {
        $this->delete('{{%product_characteristic_value}}');
        $this->delete('{{%product_color}}');
        $this->delete('{{%characteristic_value}}');
        $this->delete('{{%characteristic}}');
        
        echo "Тестовые данные удалены\n";
    }
}
