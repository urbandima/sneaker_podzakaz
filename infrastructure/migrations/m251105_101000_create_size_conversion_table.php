<?php

use yii\db\Migration;

/**
 * Создание таблицы для конвертации размеров обуви
 * Универсальная таблица соответствия EU/US/UK/CM/CHN размеров
 */
class m251105_101000_create_size_conversion_table extends Migration
{
    public function safeUp()
    {
        $this->createTable('{{%size_conversion}}', [
            'id' => $this->primaryKey(),
            'eu_size' => $this->string(10)->notNull()->comment('Европейский размер'),
            'us_size' => $this->string(10)->comment('Американский размер'),
            'uk_size' => $this->string(10)->comment('Британский размер'),
            'cm_size' => $this->decimal(4, 1)->comment('Длина стопы в см'),
            'chn_size' => $this->string(10)->comment('Китайский размер'),
            'gender' => $this->string(20)->notNull()->defaultValue('unisex')->comment('Пол: male, female, unisex, kids'),
            'category' => $this->string(50)->notNull()->defaultValue('sneakers')->comment('Категория обуви'),
            'created_at' => $this->timestamp()->defaultExpression('CURRENT_TIMESTAMP'),
            'updated_at' => $this->timestamp()->defaultExpression('CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'),
        ]);

        // Уникальный индекс для предотвращения дубликатов
        $this->createIndex(
            'idx-size_conversion-unique',
            '{{%size_conversion}}',
            ['eu_size', 'gender', 'category'],
            true
        );
        
        // Индексы для быстрого поиска
        $this->createIndex('idx-size_conversion-us', '{{%size_conversion}}', 'us_size');
        $this->createIndex('idx-size_conversion-uk', '{{%size_conversion}}', 'uk_size');
        $this->createIndex('idx-size_conversion-cm', '{{%size_conversion}}', 'cm_size');

        echo "✓ Создана таблица size_conversion\n";
    }

    public function safeDown()
    {
        $this->dropTable('{{%size_conversion}}');
        echo "✓ Удалена таблица size_conversion\n";
    }
}
