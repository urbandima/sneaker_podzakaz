<?php

use yii\db\Migration;

/**
 * Создание таблиц для размерных сеток
 */
class m251104_230000_create_size_grid_tables extends Migration
{
    public function safeUp()
    {
        // Таблица размерных сеток
        $this->createTable('{{%size_grid}}', [
            'id' => $this->primaryKey(),
            'brand_id' => $this->integer()->null()->comment('Бренд (null = универсальная)'),
            'gender' => $this->string(20)->notNull()->comment('Пол: male, female, unisex, kids'),
            'name' => $this->string(255)->notNull()->comment('Название сетки'),
            'description' => $this->text()->null()->comment('Описание'),
            'is_active' => $this->boolean()->defaultValue(1)->comment('Активна ли сетка'),
            'created_at' => $this->timestamp()->defaultExpression('CURRENT_TIMESTAMP'),
            'updated_at' => $this->timestamp()->defaultExpression('CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'),
        ]);

        $this->createIndex('idx-size_grid-brand_id', '{{%size_grid}}', 'brand_id');
        $this->createIndex('idx-size_grid-gender', '{{%size_grid}}', 'gender');
        $this->createIndex('idx-size_grid-is_active', '{{%size_grid}}', 'is_active');

        $this->addForeignKey(
            'fk-size_grid-brand_id',
            '{{%size_grid}}',
            'brand_id',
            '{{%brand}}',
            'id',
            'SET NULL',
            'CASCADE'
        );

        // Таблица элементов размерных сеток
        $this->createTable('{{%size_grid_item}}', [
            'id' => $this->primaryKey(),
            'size_grid_id' => $this->integer()->notNull()->comment('ID сетки'),
            'us_size' => $this->string(20)->null()->comment('Размер US'),
            'eu_size' => $this->string(20)->null()->comment('Размер EU'),
            'uk_size' => $this->string(20)->null()->comment('Размер UK'),
            'cm_size' => $this->decimal(5, 1)->null()->comment('Размер в CM'),
            'size' => $this->string(20)->notNull()->comment('Общее обозначение размера'),
            'sort_order' => $this->integer()->defaultValue(0)->comment('Порядок сортировки'),
        ]);

        $this->createIndex('idx-size_grid_item-size_grid_id', '{{%size_grid_item}}', 'size_grid_id');
        $this->createIndex('idx-size_grid_item-sort_order', '{{%size_grid_item}}', 'sort_order');

        $this->addForeignKey(
            'fk-size_grid_item-size_grid_id',
            '{{%size_grid_item}}',
            'size_grid_id',
            '{{%size_grid}}',
            'id',
            'CASCADE',
            'CASCADE'
        );

        // Добавляем поле price к product_size
        $this->addColumn('{{%product_size}}', 'price', $this->decimal(10, 2)->null()->comment('Цена для этого размера')->after('stock'));

        echo "✓ Созданы таблицы size_grid и size_grid_item\n";
        echo "✓ Добавлено поле price в product_size\n";

        // Создаем базовые сетки
        $this->insertDefaultSizeGrids();
    }

    private function insertDefaultSizeGrids()
    {
        // Универсальная мужская сетка
        $this->insert('{{%size_grid}}', [
            'brand_id' => null,
            'gender' => 'male',
            'name' => 'Стандартная мужская сетка',
            'description' => 'Универсальная размерная сетка для мужской обуви',
            'is_active' => 1,
        ]);
        $maleSizeGridId = $this->db->getLastInsertID();

        $maleSizes = [
            ['6', '38.5', '5.5', 24.0, '6 US'],
            ['6.5', '39', '6', 24.5, '6.5 US'],
            ['7', '40', '6', 25.0, '7 US'],
            ['7.5', '40.5', '6.5', 25.5, '7.5 US'],
            ['8', '41', '7', 26.0, '8 US'],
            ['8.5', '42', '7.5', 26.5, '8.5 US'],
            ['9', '42.5', '8', 27.0, '9 US'],
            ['9.5', '43', '8.5', 27.5, '9.5 US'],
            ['10', '44', '9', 28.0, '10 US'],
            ['10.5', '44.5', '9.5', 28.5, '10.5 US'],
            ['11', '45', '10', 29.0, '11 US'],
            ['11.5', '45.5', '10.5', 29.5, '11.5 US'],
            ['12', '46', '11', 30.0, '12 US'],
            ['13', '47.5', '12', 31.0, '13 US'],
        ];

        foreach ($maleSizes as $index => $size) {
            $this->insert('{{%size_grid_item}}', [
                'size_grid_id' => $maleSizeGridId,
                'us_size' => $size[0],
                'eu_size' => $size[1],
                'uk_size' => $size[2],
                'cm_size' => $size[3],
                'size' => $size[4],
                'sort_order' => $index,
            ]);
        }

        // Универсальная женская сетка
        $this->insert('{{%size_grid}}', [
            'brand_id' => null,
            'gender' => 'female',
            'name' => 'Стандартная женская сетка',
            'description' => 'Универсальная размерная сетка для женской обуви',
            'is_active' => 1,
        ]);
        $femaleSizeGridId = $this->db->getLastInsertID();

        $femaleSizes = [
            ['5', '35.5', '2.5', 22.0, '5 US'],
            ['5.5', '36', '3', 22.5, '5.5 US'],
            ['6', '36.5', '3.5', 23.0, '6 US'],
            ['6.5', '37', '4', 23.5, '6.5 US'],
            ['7', '37.5', '4.5', 24.0, '7 US'],
            ['7.5', '38', '5', 24.5, '7.5 US'],
            ['8', '38.5', '5.5', 25.0, '8 US'],
            ['8.5', '39', '6', 25.5, '8.5 US'],
            ['9', '40', '6.5', 26.0, '9 US'],
            ['9.5', '40.5', '7', 26.5, '9.5 US'],
            ['10', '41', '7.5', 27.0, '10 US'],
        ];

        foreach ($femaleSizes as $index => $size) {
            $this->insert('{{%size_grid_item}}', [
                'size_grid_id' => $femaleSizeGridId,
                'us_size' => $size[0],
                'eu_size' => $size[1],
                'uk_size' => $size[2],
                'cm_size' => $size[3],
                'size' => $size[4],
                'sort_order' => $index,
            ]);
        }

        echo "✓ Созданы базовые размерные сетки\n";
    }

    public function safeDown()
    {
        $this->dropColumn('{{%product_size}}', 'price');
        
        $this->dropForeignKey('fk-size_grid_item-size_grid_id', '{{%size_grid_item}}');
        $this->dropTable('{{%size_grid_item}}');

        $this->dropForeignKey('fk-size_grid-brand_id', '{{%size_grid}}');
        $this->dropTable('{{%size_grid}}');
    }
}
