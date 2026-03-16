<?php

use yii\db\Migration;

/**
 * Создание комплексной системы справочников характеристик товаров
 * 
 * Структура:
 * - characteristic - справочник типов характеристик (материал, сезон, пол и т.д.)
 * - characteristic_value - справочник значений характеристик (кожа, замша, зима и т.д.)
 * - product_characteristic_value - связь товаров со значениями характеристик (many-to-many)
 */
class m251105_065000_create_characteristics_system extends Migration
{
    public function safeUp()
    {
        // 1. Таблица типов характеристик (материал, сезон, пол и т.д.)
        $this->createTable('{{%characteristic}}', [
            'id' => $this->primaryKey(),
            'key' => $this->string(100)->notNull()->unique()->comment('Ключ характеристики (latin)'),
            'name' => $this->string(255)->notNull()->comment('Название характеристики'),
            'type' => "ENUM('select', 'multiselect', 'text', 'number', 'boolean') DEFAULT 'select' COMMENT 'Тип характеристики'",
            'is_filter' => $this->boolean()->defaultValue(0)->comment('Использовать в фильтрах'),
            'is_required' => $this->boolean()->defaultValue(0)->comment('Обязательная характеристика'),
            'sort_order' => $this->integer()->defaultValue(0)->comment('Порядок сортировки'),
            'is_active' => $this->boolean()->defaultValue(1)->comment('Активна'),
            'created_at' => $this->timestamp()->defaultExpression('CURRENT_TIMESTAMP'),
            'updated_at' => $this->timestamp()->defaultExpression('CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'),
        ]);

        $this->createIndex('idx_characteristic_key', '{{%characteristic}}', 'key');
        $this->createIndex('idx_characteristic_active', '{{%characteristic}}', 'is_active');
        $this->createIndex('idx_characteristic_filter', '{{%characteristic}}', 'is_filter');

        // 2. Таблица значений характеристик
        $this->createTable('{{%characteristic_value}}', [
            'id' => $this->primaryKey(),
            'characteristic_id' => $this->integer()->notNull()->comment('ID характеристики'),
            'value' => $this->string(255)->notNull()->comment('Значение'),
            'slug' => $this->string(255)->notNull()->comment('URL slug'),
            'sort_order' => $this->integer()->defaultValue(0)->comment('Порядок сортировки'),
            'is_active' => $this->boolean()->defaultValue(1)->comment('Активно'),
            'created_at' => $this->timestamp()->defaultExpression('CURRENT_TIMESTAMP'),
            'updated_at' => $this->timestamp()->defaultExpression('CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'),
        ]);

        $this->createIndex('idx_characteristic_value_char_id', '{{%characteristic_value}}', 'characteristic_id');
        $this->createIndex('idx_characteristic_value_slug', '{{%characteristic_value}}', 'slug');
        $this->createIndex('idx_characteristic_value_active', '{{%characteristic_value}}', 'is_active');
        
        $this->addForeignKey(
            'fk_characteristic_value_characteristic',
            '{{%characteristic_value}}',
            'characteristic_id',
            '{{%characteristic}}',
            'id',
            'CASCADE'
        );

        // 3. Таблица связи товаров с характеристиками (many-to-many)
        $this->createTable('{{%product_characteristic_value}}', [
            'id' => $this->primaryKey(),
            'product_id' => $this->integer()->notNull()->comment('ID товара'),
            'characteristic_id' => $this->integer()->notNull()->comment('ID характеристики'),
            'characteristic_value_id' => $this->integer()->null()->comment('ID значения (для select/multiselect)'),
            'value_text' => $this->text()->null()->comment('Текстовое значение (для text/number)'),
            'value_number' => $this->decimal(10, 2)->null()->comment('Числовое значение (для number)'),
            'value_boolean' => $this->boolean()->null()->comment('Булево значение (для boolean)'),
            'created_at' => $this->timestamp()->defaultExpression('CURRENT_TIMESTAMP'),
        ]);

        $this->createIndex('idx_pcv_product', '{{%product_characteristic_value}}', 'product_id');
        $this->createIndex('idx_pcv_characteristic', '{{%product_characteristic_value}}', 'characteristic_id');
        $this->createIndex('idx_pcv_value', '{{%product_characteristic_value}}', 'characteristic_value_id');
        $this->createIndex('idx_pcv_unique', '{{%product_characteristic_value}}', ['product_id', 'characteristic_id', 'characteristic_value_id'], true);

        $this->addForeignKey(
            'fk_pcv_product',
            '{{%product_characteristic_value}}',
            'product_id',
            '{{%product}}',
            'id',
            'CASCADE'
        );

        $this->addForeignKey(
            'fk_pcv_characteristic',
            '{{%product_characteristic_value}}',
            'characteristic_id',
            '{{%characteristic}}',
            'id',
            'CASCADE'
        );

        $this->addForeignKey(
            'fk_pcv_characteristic_value',
            '{{%product_characteristic_value}}',
            'characteristic_value_id',
            '{{%characteristic_value}}',
            'id',
            'CASCADE'
        );

        // 4. Заполняем базовые характеристики
        $this->batchInsert('{{%characteristic}}', ['key', 'name', 'type', 'is_filter', 'sort_order'], [
            // Материалы
            ['material', 'Материал', 'select', 1, 1],
            ['upper_material', 'Материал верха', 'select', 1, 2],
            ['sole_material', 'Материал подошвы', 'select', 1, 3],
            
            // Характеристики
            ['season', 'Сезон', 'select', 1, 10],
            ['gender', 'Пол', 'select', 1, 11],
            ['fastening', 'Тип закрытия', 'select', 1, 12],
            ['height', 'Высота', 'select', 1, 13],
            ['toe_style', 'Тип носка', 'select', 0, 14],
            ['heel_type', 'Тип каблука', 'select', 0, 15],
            
            // Цвет и стиль
            ['color', 'Цвет', 'select', 1, 20],
            ['style_code', 'Код стиля', 'text', 0, 21],
            
            // Дополнительно
            ['country_of_origin', 'Страна производства', 'select', 1, 30],
            ['release_year', 'Год выпуска', 'number', 0, 31],
            ['weight', 'Вес (грамм)', 'number', 0, 32],
            
            // Функциональность
            ['functionality', 'Функциональность', 'multiselect', 0, 40],
        ]);

        // 5. Заполняем базовые значения
        // Материалы
        $this->insertCharacteristicValues('material', [
            'Кожа' => 'leather',
            'Замша' => 'suede',
            'Текстиль' => 'textile',
            'Синтетика' => 'synthetic',
            'Сетка' => 'mesh',
            'Холст' => 'canvas',
            'Нубук' => 'nubuck',
        ]);

        $this->insertCharacteristicValues('upper_material', [
            'Кожа' => 'leather',
            'Замша' => 'suede',
            'Текстиль' => 'textile',
            'Синтетика' => 'synthetic',
            'Сетка' => 'mesh',
        ]);

        $this->insertCharacteristicValues('sole_material', [
            'Резина' => 'rubber',
            'EVA' => 'eva',
            'Полиуретан' => 'polyurethane',
            'TPU' => 'tpu',
        ]);

        // Сезон
        $this->insertCharacteristicValues('season', [
            'Лето' => 'summer',
            'Зима' => 'winter',
            'Демисезон' => 'demi',
            'Всесезонные' => 'all',
        ]);

        // Пол
        $this->insertCharacteristicValues('gender', [
            'Мужские' => 'male',
            'Женские' => 'female',
            'Унисекс' => 'unisex',
            'Детские' => 'kids',
        ]);

        // Тип закрытия
        $this->insertCharacteristicValues('fastening', [
            'Шнуровка' => 'laces',
            'Липучка' => 'velcro',
            'Молния' => 'zipper',
            'Слип-он' => 'slip_on',
        ]);

        // Высота
        $this->insertCharacteristicValues('height', [
            'Низкие' => 'low',
            'Средние' => 'mid',
            'Высокие' => 'high',
        ]);

        // Цвета
        $this->insertCharacteristicValues('color', [
            'Белый' => 'white',
            'Черный' => 'black',
            'Серый' => 'gray',
            'Красный' => 'red',
            'Синий' => 'blue',
            'Зеленый' => 'green',
            'Желтый' => 'yellow',
            'Коричневый' => 'brown',
            'Бежевый' => 'beige',
            'Розовый' => 'pink',
            'Фиолетовый' => 'purple',
            'Оранжевый' => 'orange',
            'Мультиколор' => 'multicolor',
        ]);

        // Страны производства
        $this->insertCharacteristicValues('country_of_origin', [
            'Китай' => 'china',
            'Вьетнам' => 'vietnam',
            'Индонезия' => 'indonesia',
            'Италия' => 'italy',
            'США' => 'usa',
            'Португалия' => 'portugal',
        ]);

        // Функциональность
        $this->insertCharacteristicValues('functionality', [
            'Дышащий' => 'breathable',
            'Водонепроницаемый' => 'waterproof',
            'Износостойкий' => 'durable',
            'Амортизация' => 'cushioning',
            'Легкий' => 'lightweight',
        ]);

        return true;
    }

    /**
     * Вспомогательный метод для вставки значений характеристик
     */
    private function insertCharacteristicValues($characteristicKey, $values)
    {
        $characteristic = $this->db->createCommand(
            "SELECT id FROM {{%characteristic}} WHERE `key` = :key LIMIT 1"
        )->bindValue(':key', $characteristicKey)->queryOne();

        if (!$characteristic) {
            return;
        }

        $characteristicId = $characteristic['id'];
        $sortOrder = 0;
        $rows = [];

        foreach ($values as $name => $slug) {
            $rows[] = [$characteristicId, $name, $slug, $sortOrder++];
        }

        $this->batchInsert('{{%characteristic_value}}', 
            ['characteristic_id', 'value', 'slug', 'sort_order'], 
            $rows
        );
    }

    public function safeDown()
    {
        $this->dropForeignKey('fk_pcv_characteristic_value', '{{%product_characteristic_value}}');
        $this->dropForeignKey('fk_pcv_characteristic', '{{%product_characteristic_value}}');
        $this->dropForeignKey('fk_pcv_product', '{{%product_characteristic_value}}');
        $this->dropTable('{{%product_characteristic_value}}');

        $this->dropForeignKey('fk_characteristic_value_characteristic', '{{%characteristic_value}}');
        $this->dropTable('{{%characteristic_value}}');
        
        $this->dropTable('{{%characteristic}}');

        return true;
    }
}
