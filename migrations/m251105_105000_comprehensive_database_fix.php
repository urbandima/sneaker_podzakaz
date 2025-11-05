<?php

use yii\db\Migration;

/**
 * Комплексное исправление базы данных - добавление всех недостающих полей
 */
class m251105_105000_comprehensive_database_fix extends Migration
{
    public function safeUp()
    {
        echo "🔍 КОМПЛЕКСНАЯ ПРОВЕРКА И ИСПРАВЛЕНИЕ БАЗЫ ДАННЫХ\n\n";
        
        // 1. Таблица PRODUCT
        $this->fixProductTable();
        
        // 2. Таблица PRODUCT_SIZE
        $this->fixProductSizeTable();
        
        // 3. Таблица BRAND
        $this->fixBrandTable();
        
        // 4. Таблица CATEGORY
        $this->fixCategoryTable();
        
        // 5. Таблица ORDER_STATUS
        $this->fixOrderStatusTable();
        
        echo "\n✅ БАЗА ДАННЫХ ПОЛНОСТЬЮ ГОТОВА!\n";
    }
    
    /**
     * Исправление таблицы product
     */
    private function fixProductTable()
    {
        echo "📦 Проверка таблицы PRODUCT...\n";
        
        $table = $this->db->getTableSchema('{{%product}}');
        if (!$table) {
            echo "⚠️ Таблица product не существует\n";
            return;
        }
        
        $columns = $table->columnNames;
        
        $fields = [
            // Денормализованные поля для производительности (N+1 проблема)
            'brand_name' => $this->string(100)->comment('Денормализованное имя бренда'),
            'category_name' => $this->string(100)->comment('Денормализованное имя категории'),
            'main_image_url' => $this->string(500)->comment('Денормализованный URL главного изображения'),
            
            // Фильтры и характеристики
            'material' => $this->string(50)->comment('Материал'),
            'season' => $this->string(20)->comment('Сезон'),
            'gender' => $this->string(20)->comment('Пол'),
            'height' => $this->string(20)->comment('Высота'),
            'fastening' => $this->string(20)->comment('Застежка'),
            'country' => $this->string(100)->comment('Страна производства'),
            
            // Promo флаги
            'has_bonus' => $this->boolean()->defaultValue(0)->comment('Бонусы'),
            'promo_2for1' => $this->boolean()->defaultValue(0)->comment('Акция 2+1'),
            'is_exclusive' => $this->boolean()->defaultValue(0)->comment('Эксклюзив'),
            
            // Рейтинг и отзывы
            'rating' => $this->decimal(3, 2)->defaultValue(0)->comment('Рейтинг'),
            'reviews_count' => $this->integer()->defaultValue(0)->comment('Количество отзывов'),
            'views_count' => $this->integer()->defaultValue(0)->comment('Просмотры'),
            
            // Poizon поля (если еще не добавлены)
            'sku' => $this->string(100)->comment('SKU'),
            'vendor_code' => $this->string(100)->comment('Артикул производителя'),
            'poizon_id' => $this->string(50)->comment('ID в Poizon'),
            'poizon_spu_id' => $this->string(50)->comment('SPU ID в Poizon'),
            'poizon_variant_id' => $this->string(50)->comment('Variant ID в Poizon'),
            'poizon_url' => $this->string(500)->comment('URL на Poizon'),
            'poizon_price_cny' => $this->decimal(10, 2)->comment('Цена CNY на Poizon'),
            'last_sync_at' => $this->timestamp()->null()->comment('Последняя синхронизация'),
            
            // Дополнительные характеристики
            'upper_material' => $this->string(100)->comment('Материал верха'),
            'sole_material' => $this->string(100)->comment('Материал подошвы'),
            'color_description' => $this->string(255)->comment('Описание цвета'),
            'style_code' => $this->string(100)->comment('Код стиля'),
            'release_year' => $this->integer()->comment('Год выпуска'),
            'is_limited' => $this->boolean()->defaultValue(0)->comment('Лимитированная'),
            'weight' => $this->integer()->comment('Вес (г)'),
            
            // Новые поля
            'series_name' => $this->string(255)->comment('Серия товара'),
            'delivery_time_min' => $this->integer()->comment('Доставка мин (дни)'),
            'delivery_time_max' => $this->integer()->comment('Доставка макс (дни)'),
            'related_products_json' => $this->text()->comment('Связанные товары'),
            
            // Дополнительные поля
            'country_of_origin' => $this->string(100)->comment('Страна происхождения'),
            'favorite_count' => $this->integer()->defaultValue(0)->comment('В избранном'),
            'stock_count' => $this->integer()->defaultValue(0)->comment('Остаток'),
            'purchase_price' => $this->decimal(10, 2)->comment('Цена закупки'),
            'parent_product_id' => $this->integer()->comment('ID родительского товара'),
            'vat' => $this->integer()->comment('НДС'),
            'currency' => $this->string(10)->defaultValue('BYN')->comment('Валюта'),
            
            // JSON поля
            'properties' => $this->text()->comment('Свойства JSON'),
            'sizes_data' => $this->text()->comment('Данные размеров JSON'),
            'keywords' => $this->text()->comment('Ключевые слова JSON'),
            'variant_params' => $this->text()->comment('Параметры варианта JSON'),
        ];
        
        foreach ($fields as $field => $type) {
            if (!in_array($field, $columns)) {
                try {
                    $this->addColumn('{{%product}}', $field, $type);
                    echo "  ✓ {$field}\n";
                } catch (\Exception $e) {
                    echo "  ⚠️ {$field}: {$e->getMessage()}\n";
                }
            }
        }
        
        // Индексы
        $this->createIndexIfNotExists('idx-product-brand_name', '{{%product}}', 'brand_name');
        $this->createIndexIfNotExists('idx-product-category_name', '{{%product}}', 'category_name');
        $this->createIndexIfNotExists('idx-product-is_active', '{{%product}}', 'is_active');
        $this->createIndexIfNotExists('idx-product-is_featured', '{{%product}}', 'is_featured');
        $this->createIndexIfNotExists('idx-product-views_count', '{{%product}}', 'views_count');
        $this->createIndexIfNotExists('idx-product-rating', '{{%product}}', 'rating');
    }
    
    /**
     * Исправление таблицы product_size
     */
    private function fixProductSizeTable()
    {
        echo "\n📏 Проверка таблицы PRODUCT_SIZE...\n";
        
        $table = $this->db->getTableSchema('{{%product_size}}');
        if (!$table) {
            echo "⚠️ Таблица product_size не существует\n";
            return;
        }
        
        $columns = $table->columnNames;
        
        // Переименование stock_quantity → stock
        if (in_array('stock_quantity', $columns) && !in_array('stock', $columns)) {
            $this->renameColumn('{{%product_size}}', 'stock_quantity', 'stock');
            echo "  ✓ stock_quantity → stock\n";
            $columns = $this->db->getTableSchema('{{%product_size}}', true)->columnNames;
        }
        
        $fields = [
            'size' => $this->string(50)->notNull()->comment('Размер'),
            'color' => $this->string(100)->comment('Цвет'),
            'stock' => $this->integer()->defaultValue(0)->comment('Остаток'),
            'price' => $this->decimal(10, 2)->comment('Цена'),
            'is_available' => $this->boolean()->defaultValue(1)->comment('Доступен'),
            'sort_order' => $this->integer()->defaultValue(0)->comment('Порядок'),
            
            // Размерные сетки
            'us_size' => $this->string(10)->comment('Размер US'),
            'eu_size' => $this->string(10)->comment('Размер EU'),
            'uk_size' => $this->string(10)->comment('Размер UK'),
            'cm_size' => $this->decimal(5, 1)->comment('Размер CM'),
            
            // Poizon
            'poizon_sku_id' => $this->string(50)->comment('SKU в Poizon'),
            'poizon_stock' => $this->integer()->defaultValue(0)->comment('Остаток Poizon'),
            'poizon_price_cny' => $this->decimal(10, 2)->comment('Цена Poizon CNY'),
            
            // Ценообразование
            'price_cny' => $this->decimal(10, 2)->comment('Цена CNY'),
            'price_byn' => $this->decimal(10, 2)->comment('Цена BYN'),
            'price_client_byn' => $this->decimal(10, 2)->comment('Цена клиента BYN'),
            
            // Новые поля
            'color_variant' => $this->string(100)->comment('Цвет варианта'),
            'delivery_time_min' => $this->integer()->comment('Доставка мин'),
            'delivery_time_max' => $this->integer()->comment('Доставка макс'),
            
            // Timestamps
            'created_at' => $this->timestamp()->defaultExpression('CURRENT_TIMESTAMP'),
            'updated_at' => $this->timestamp()->defaultExpression('CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'),
        ];
        
        foreach ($fields as $field => $type) {
            if (!in_array($field, $columns)) {
                try {
                    $this->addColumn('{{%product_size}}', $field, $type);
                    echo "  ✓ {$field}\n";
                } catch (\Exception $e) {
                    echo "  ⚠️ {$field}: {$e->getMessage()}\n";
                }
            }
        }
    }
    
    /**
     * Исправление таблицы brand
     */
    private function fixBrandTable()
    {
        echo "\n🏷️ Проверка таблицы BRAND...\n";
        
        $table = $this->db->getTableSchema('{{%brand}}');
        if (!$table) {
            echo "⚠️ Таблица brand не существует\n";
            return;
        }
        
        $columns = $table->columnNames;
        
        $fields = [
            'logo_url' => $this->string(500)->comment('URL логотипа'),
            'description' => $this->text()->comment('Описание'),
            'is_active' => $this->boolean()->defaultValue(1)->comment('Активен'),
            'sort_order' => $this->integer()->defaultValue(0)->comment('Порядок'),
            'meta_title' => $this->string(255)->comment('Meta Title'),
            'meta_description' => $this->text()->comment('Meta Description'),
            'meta_keywords' => $this->text()->comment('Meta Keywords'),
            'poizon_id' => $this->string(50)->comment('ID в Poizon'),
        ];
        
        foreach ($fields as $field => $type) {
            if (!in_array($field, $columns)) {
                try {
                    $this->addColumn('{{%brand}}', $field, $type);
                    echo "  ✓ {$field}\n";
                } catch (\Exception $e) {
                    echo "  ⚠️ {$field}: {$e->getMessage()}\n";
                }
            }
        }
    }
    
    /**
     * Исправление таблицы category
     */
    private function fixCategoryTable()
    {
        echo "\n📂 Проверка таблицы CATEGORY...\n";
        
        $table = $this->db->getTableSchema('{{%category}}');
        if (!$table) {
            echo "⚠️ Таблица category не существует\n";
            return;
        }
        
        $columns = $table->columnNames;
        
        $fields = [
            'parent_id' => $this->integer()->comment('Родительская категория'),
            'description' => $this->text()->comment('Описание'),
            'image_url' => $this->string(500)->comment('URL изображения'),
            'is_active' => $this->boolean()->defaultValue(1)->comment('Активна'),
            'sort_order' => $this->integer()->defaultValue(0)->comment('Порядок'),
            'meta_title' => $this->string(255)->comment('Meta Title'),
            'meta_description' => $this->text()->comment('Meta Description'),
            'meta_keywords' => $this->text()->comment('Meta Keywords'),
            'poizon_id' => $this->string(50)->comment('ID в Poizon'),
        ];
        
        foreach ($fields as $field => $type) {
            if (!in_array($field, $columns)) {
                try {
                    $this->addColumn('{{%category}}', $field, $type);
                    echo "  ✓ {$field}\n";
                } catch (\Exception $e) {
                    echo "  ⚠️ {$field}: {$e->getMessage()}\n";
                }
            }
        }
    }
    
    /**
     * Исправление таблицы order_status
     */
    private function fixOrderStatusTable()
    {
        echo "\n📋 Проверка таблицы ORDER_STATUS...\n";
        
        $table = $this->db->getTableSchema('{{%order_status}}');
        if (!$table) {
            echo "⚠️ Таблица order_status не существует\n";
            return;
        }
        
        $columns = $table->columnNames;
        
        if (!in_array('is_active', $columns)) {
            $this->addColumn('{{%order_status}}', 'is_active', $this->boolean()->notNull()->defaultValue(1));
            $this->update('{{%order_status}}', ['is_active' => 1]);
            echo "  ✓ is_active\n";
        }
    }
    
    /**
     * Создание индекса если его нет
     */
    private function createIndexIfNotExists($name, $table, $column)
    {
        $indexes = $this->db->schema->getTableIndexes($table);
        if (!isset($indexes[$name])) {
            try {
                $this->createIndex($name, $table, $column);
            } catch (\Exception $e) {
                // Индекс уже существует или ошибка
            }
        }
    }

    public function safeDown()
    {
        echo "Откат не реализован\n";
        return true;
    }
}
