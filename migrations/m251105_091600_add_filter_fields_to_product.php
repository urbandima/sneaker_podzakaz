<?php

use yii\db\Migration;

/**
 * Добавление полей фильтрации к таблице product
 * Эти поля используются для фильтрации товаров в каталоге
 */
class m251105_091600_add_filter_fields_to_product extends Migration
{
    public function safeUp()
    {
        // Добавляем поля для фильтрации
        $this->addColumn('{{%product}}', 'material', $this->string(50)->comment('Материал (leather, textile, synthetic, suede, mesh, canvas)'));
        $this->addColumn('{{%product}}', 'season', $this->string(50)->comment('Сезон (summer, winter, demi, all)'));
        $this->addColumn('{{%product}}', 'gender', $this->string(50)->comment('Пол (male, female, unisex)'));
        $this->addColumn('{{%product}}', 'height', $this->string(50)->comment('Высота (low, mid, high)'));
        $this->addColumn('{{%product}}', 'fastening', $this->string(50)->comment('Застежка (laces, velcro, zipper, slip_on)'));
        $this->addColumn('{{%product}}', 'country', $this->string(100)->comment('Страна производства'));
        
        // Добавляем дополнительные поля для функционала
        $this->addColumn('{{%product}}', 'has_bonus', $this->boolean()->defaultValue(0)->comment('Есть бонусы'));
        $this->addColumn('{{%product}}', 'promo_2for1', $this->boolean()->defaultValue(0)->comment('Акция 2+1'));
        $this->addColumn('{{%product}}', 'is_exclusive', $this->boolean()->defaultValue(0)->comment('Эксклюзив'));
        $this->addColumn('{{%product}}', 'rating', $this->decimal(3, 2)->defaultValue(0)->comment('Рейтинг товара (0-5)'));
        $this->addColumn('{{%product}}', 'reviews_count', $this->integer()->defaultValue(0)->comment('Количество отзывов'));
        
        // Создаем индексы для ускорения фильтрации
        $this->createIndex('idx-product-material', '{{%product}}', 'material');
        $this->createIndex('idx-product-season', '{{%product}}', 'season');
        $this->createIndex('idx-product-gender', '{{%product}}', 'gender');
        $this->createIndex('idx-product-height', '{{%product}}', 'height');
        $this->createIndex('idx-product-fastening', '{{%product}}', 'fastening');
        $this->createIndex('idx-product-rating', '{{%product}}', 'rating');
        
        echo "✓ Добавлены поля фильтрации к таблице product\n";
        echo "✓ Созданы индексы для оптимизации производительности\n";
    }

    public function safeDown()
    {
        // Удаляем индексы
        $this->dropIndex('idx-product-rating', '{{%product}}');
        $this->dropIndex('idx-product-fastening', '{{%product}}');
        $this->dropIndex('idx-product-height', '{{%product}}');
        $this->dropIndex('idx-product-gender', '{{%product}}');
        $this->dropIndex('idx-product-season', '{{%product}}');
        $this->dropIndex('idx-product-material', '{{%product}}');
        
        // Удаляем поля
        $this->dropColumn('{{%product}}', 'reviews_count');
        $this->dropColumn('{{%product}}', 'rating');
        $this->dropColumn('{{%product}}', 'is_exclusive');
        $this->dropColumn('{{%product}}', 'promo_2for1');
        $this->dropColumn('{{%product}}', 'has_bonus');
        $this->dropColumn('{{%product}}', 'country');
        $this->dropColumn('{{%product}}', 'fastening');
        $this->dropColumn('{{%product}}', 'height');
        $this->dropColumn('{{%product}}', 'gender');
        $this->dropColumn('{{%product}}', 'season');
        $this->dropColumn('{{%product}}', 'material');
        
        echo "✓ Удалены поля фильтрации из таблицы product\n";
    }
}
