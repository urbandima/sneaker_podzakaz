<?php

use yii\db\Migration;

/**
 * Улучшения безопасности и архитектуры:
 * 1. Composite unique index для (vendor_code, brand_id) - предотвращение дубликатов
 * 2. Таблица product_size_images для изображений вариантов
 * 3. Таблица currency_settings для мультивалютности
 */
class m251106_070100_security_and_architecture_improvements extends Migration
{
    public function safeUp()
    {
        // 1. БЕЗОПАСНОСТЬ: Composite unique для vendor_code + brand_id
        $this->createIndex(
            'idx_unique_vendor_code_brand',
            '{{%product}}',
            ['vendor_code', 'brand_id'],
            true // unique
        );
        
        echo "✅ Создан уникальный индекс для (vendor_code, brand_id)\n";
        
        // 2. АРХИТЕКТУРА: Таблица для изображений вариантов размеров
        $this->createTable('{{%product_size_image}}', [
            'id' => $this->primaryKey(),
            'product_size_id' => $this->integer()->notNull()->comment('ID размера'),
            'image_url' => $this->string(500)->notNull()->comment('URL изображения'),
            'sort_order' => $this->integer()->defaultValue(0)->comment('Порядок сортировки'),
            'is_main' => $this->boolean()->defaultValue(0)->comment('Главное изображение варианта'),
            'created_at' => $this->timestamp()->defaultExpression('CURRENT_TIMESTAMP'),
            'updated_at' => $this->timestamp()->defaultExpression('CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'),
        ], 'ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
        
        $this->createIndex('idx_product_size_id', '{{%product_size_image}}', 'product_size_id');
        $this->addForeignKey(
            'fk_product_size_image_size',
            '{{%product_size_image}}',
            'product_size_id',
            '{{%product_size}}',
            'id',
            'CASCADE',
            'CASCADE'
        );
        
        echo "✅ Создана таблица product_size_image\n";
        
        // 3. МУЛЬТИВАЛЮТНОСТЬ: Таблица настроек валют
        $this->createTable('{{%currency_setting}}', [
            'id' => $this->primaryKey(),
            'currency_code' => $this->string(3)->notNull()->comment('Код валюты (BYN, CNY, RUB, USD)'),
            'currency_symbol' => $this->string(10)->notNull()->comment('Символ (₽, ¥, $)'),
            'exchange_rate' => $this->decimal(10, 4)->notNull()->defaultValue(1)->comment('Курс к базовой валюте'),
            'is_base' => $this->boolean()->defaultValue(0)->comment('Базовая валюта'),
            'is_active' => $this->boolean()->defaultValue(1)->comment('Активна'),
            'markup_percent' => $this->decimal(5, 2)->defaultValue(0)->comment('Наценка в %'),
            'delivery_fee' => $this->decimal(10, 2)->defaultValue(0)->comment('Фиксированная доставка'),
            'updated_at' => $this->timestamp()->defaultExpression('CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'),
        ], 'ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
        
        $this->createIndex('idx_currency_code', '{{%currency_setting}}', 'currency_code', true);
        
        // Заполняем дефолтные настройки
        $this->batchInsert('{{%currency_setting}}', 
            ['currency_code', 'currency_symbol', 'exchange_rate', 'is_base', 'markup_percent', 'delivery_fee'],
            [
                ['BYN', '₽', 1.0000, 1, 0, 0],           // Базовая валюта
                ['CNY', '¥', 0.4500, 0, 50, 40],         // Юань: курс ~0.45, наценка 50%, доставка 40 BYN
                ['RUB', '₽', 0.0350, 0, 30, 0],          // Рубль: курс ~0.035, наценка 30%
                ['USD', '$', 3.2000, 0, 20, 0],          // Доллар: курс ~3.2, наценка 20%
            ]
        );
        
        echo "✅ Создана таблица currency_setting с дефолтными настройками\n";
        
        // 4. Добавляем поле validated_url в product для безопасности
        $this->addColumn('{{%product}}', 'validated_url', $this->boolean()->defaultValue(0)->after('poizon_url')->comment('URL прошел валидацию'));
        
        echo "✅ Добавлено поле validated_url в product\n";
    }

    public function safeDown()
    {
        $this->dropColumn('{{%product}}', 'validated_url');
        $this->dropTable('{{%currency_setting}}');
        $this->dropTable('{{%product_size_image}}');
        $this->dropIndex('idx_unique_vendor_code_brand', '{{%product}}');
    }
}
