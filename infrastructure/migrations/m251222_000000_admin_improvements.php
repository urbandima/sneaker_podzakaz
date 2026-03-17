<?php

use yii\db\Migration;

/**
 * Глобальное улучшение админки - добавление всех необходимых таблиц и полей
 */
class m251222_000000_admin_improvements extends Migration
{
    public function safeUp()
    {
        // 1. Система остатков на складе
        try {
            $this->createTable('{{%product_stock}}', [
                'id' => $this->primaryKey(),
                'product_id' => $this->integer()->notNull(),
                'size_id' => $this->integer(),
                'quantity' => $this->integer()->notNull()->defaultValue(0),
                'reserved' => $this->integer()->notNull()->defaultValue(0),
                'warehouse' => $this->string(100)->defaultValue('main'),
                'min_quantity' => $this->integer()->defaultValue(0),
                'last_restock_at' => $this->dateTime(),
                'created_at' => $this->dateTime()->defaultExpression('CURRENT_TIMESTAMP'),
                'updated_at' => $this->dateTime()->defaultExpression('CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'),
            ]);
            
            $this->addForeignKey(
                'fk_product_stock_product',
                '{{%product_stock}}',
                'product_id',
                '{{%product}}',
                'id',
                'CASCADE'
            );
            
            $this->createIndex('idx_product_stock_product', '{{%product_stock}}', 'product_id');
            $this->createIndex('idx_product_stock_size', '{{%product_stock}}', 'size_id');
        } catch (\Exception $e) {
            echo "⚠ Таблица product_stock уже существует\n";
        }

        // 2. Таблица связанных товаров
        try {
            $this->createTable('{{%product_related}}', [
                'id' => $this->primaryKey(),
                'product_id' => $this->integer()->notNull(),
                'related_product_id' => $this->integer()->notNull(),
                'relation_type' => $this->string(50)->defaultValue('similar'),
                'sort_order' => $this->integer()->defaultValue(0),
                'created_at' => $this->dateTime()->defaultExpression('CURRENT_TIMESTAMP'),
            ]);
            
            $this->addForeignKey(
                'fk_product_related_product',
                '{{%product_related}}',
                'product_id',
                '{{%product}}',
                'id',
                'CASCADE'
            );
            
            $this->addForeignKey(
                'fk_product_related_related',
                '{{%product_related}}',
                'related_product_id',
                '{{%product}}',
                'id',
                'CASCADE'
            );
            
            $this->createIndex('idx_product_related_product', '{{%product_related}}', 'product_id');
        } catch (\Exception $e) {
            echo "⚠ Таблица product_related уже существует\n";
        }

        // 3. Таблица тарифов и комиссий
        $this->createTable('{{%tariff}}', [
            'id' => $this->primaryKey(),
            'name' => $this->string(100)->notNull(),
            'description' => $this->text(),
            'commission_percent' => $this->decimal(5, 2)->defaultValue(0),
            'commission_fixed' => $this->decimal(10, 2)->defaultValue(0),
            'delivery_cost_per_kg' => $this->decimal(10, 2)->defaultValue(0),
            'insurance_percent' => $this->decimal(5, 2)->defaultValue(0),
            'min_order_amount' => $this->decimal(10, 2)->defaultValue(0),
            'currency' => $this->string(3)->defaultValue('CNY'),
            'exchange_rate' => $this->decimal(10, 4)->defaultValue(1),
            'is_active' => $this->boolean()->defaultValue(true),
            'sort_order' => $this->integer()->defaultValue(0),
            'created_at' => $this->dateTime()->defaultExpression('CURRENT_TIMESTAMP'),
            'updated_at' => $this->dateTime()->defaultExpression('CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'),
        ]);

        // Вставляем дефолтные тарифы
        $this->insert('{{%tariff}}', [
            'name' => 'Стандарт',
            'description' => 'Стандартный тариф для предзаказов',
            'commission_percent' => 10.00,
            'commission_fixed' => 0,
            'delivery_cost_per_kg' => 15.00,
            'insurance_percent' => 2.00,
            'currency' => 'CNY',
            'exchange_rate' => 12.50,
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $this->insert('{{%tariff}}', [
            'name' => 'Экспресс',
            'description' => 'Быстрая доставка с повышенной комиссией',
            'commission_percent' => 15.00,
            'commission_fixed' => 0,
            'delivery_cost_per_kg' => 25.00,
            'insurance_percent' => 3.00,
            'currency' => 'CNY',
            'exchange_rate' => 12.50,
            'is_active' => true,
            'sort_order' => 2,
        ]);

        $this->insert('{{%tariff}}', [
            'name' => 'Эконом',
            'description' => 'Экономичный тариф с длинной доставкой',
            'commission_percent' => 7.00,
            'commission_fixed' => 0,
            'delivery_cost_per_kg' => 10.00,
            'insurance_percent' => 1.00,
            'currency' => 'CNY',
            'exchange_rate' => 12.50,
            'is_active' => true,
            'sort_order' => 3,
        ]);

        // 4. Таблица отслеживания доставок
        $this->createTable('{{%delivery_tracking}}', [
            'id' => $this->primaryKey(),
            'order_id' => $this->integer()->notNull(),
            'tracking_number' => $this->string(100),
            'carrier' => $this->string(100),
            'status' => $this->string(50)->defaultValue('pending'),
            'status_description' => $this->string(255),
            'location' => $this->string(255),
            'estimated_delivery' => $this->date(),
            'actual_delivery' => $this->date(),
            'events_json' => $this->text(),
            'last_check_at' => $this->dateTime(),
            'created_at' => $this->dateTime()->defaultExpression('CURRENT_TIMESTAMP'),
            'updated_at' => $this->dateTime()->defaultExpression('CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'),
        ]);

        $this->addForeignKey(
            'fk_delivery_tracking_order',
            '{{%delivery_tracking}}',
            'order_id',
            '{{%order}}',
            'id',
            'CASCADE'
        );
        
        $this->createIndex('idx_delivery_tracking_order', '{{%delivery_tracking}}', 'order_id');
        $this->createIndex('idx_delivery_tracking_number', '{{%delivery_tracking}}', 'tracking_number');

        // 5. Таблица отзывов товаров
        $this->createTable('{{%product_review}}', [
            'id' => $this->primaryKey(),
            'product_id' => $this->integer()->notNull(),
            'user_id' => $this->integer(),
            'order_id' => $this->integer(),
            'author_name' => $this->string(100),
            'author_email' => $this->string(100),
            'rating' => $this->tinyInteger()->notNull()->defaultValue(5),
            'title' => $this->string(255),
            'content' => $this->text(),
            'pros' => $this->text(),
            'cons' => $this->text(),
            'photos_json' => $this->text(),
            'is_verified' => $this->boolean()->defaultValue(false),
            'is_published' => $this->boolean()->defaultValue(false),
            'is_featured' => $this->boolean()->defaultValue(false),
            'admin_response' => $this->text(),
            'admin_response_at' => $this->dateTime(),
            'helpful_count' => $this->integer()->defaultValue(0),
            'created_at' => $this->dateTime()->defaultExpression('CURRENT_TIMESTAMP'),
            'updated_at' => $this->dateTime()->defaultExpression('CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'),
        ]);

        $this->addForeignKey(
            'fk_product_review_product',
            '{{%product_review}}',
            'product_id',
            '{{%product}}',
            'id',
            'CASCADE'
        );
        
        $this->createIndex('idx_product_review_product', '{{%product_review}}', 'product_id');
        $this->createIndex('idx_product_review_rating', '{{%product_review}}', 'rating');
        $this->createIndex('idx_product_review_published', '{{%product_review}}', 'is_published');

        // 6. Таблица аналитики и конверсии
        $this->createTable('{{%analytics_event}}', [
            'id' => $this->primaryKey(),
            'event_type' => $this->string(50)->notNull(),
            'entity_type' => $this->string(50),
            'entity_id' => $this->integer(),
            'user_id' => $this->integer(),
            'session_id' => $this->string(100),
            'source' => $this->string(100),
            'utm_source' => $this->string(100),
            'utm_medium' => $this->string(100),
            'utm_campaign' => $this->string(100),
            'device_type' => $this->string(50),
            'browser' => $this->string(100),
            'ip_address' => $this->string(45),
            'country' => $this->string(2),
            'city' => $this->string(100),
            'referrer' => $this->string(500),
            'page_url' => $this->string(500),
            'meta_json' => $this->text(),
            'created_at' => $this->dateTime()->defaultExpression('CURRENT_TIMESTAMP'),
        ]);
        
        $this->createIndex('idx_analytics_event_type', '{{%analytics_event}}', 'event_type');
        $this->createIndex('idx_analytics_entity', '{{%analytics_event}}', ['entity_type', 'entity_id']);
        $this->createIndex('idx_analytics_created', '{{%analytics_event}}', 'created_at');
        $this->createIndex('idx_analytics_session', '{{%analytics_event}}', 'session_id');

        // 7. Таблица ежедневной статистики для быстрых отчетов
        // Создаем таблицу daily_stats с составным первичным ключом
        $this->createTable('{{%daily_stats}}', [
            'date' => $this->date()->notNull(),
            'stat_type' => $this->string(50)->notNull(),
            'value' => $this->decimal(15, 2)->defaultValue(0),
            'count' => $this->integer()->defaultValue(0),
            'meta_json' => $this->text(),
            'created_at' => $this->dateTime()->defaultExpression('CURRENT_TIMESTAMP'),
        ]);
        
        $this->addPrimaryKey('pk_daily_stats_unique', '{{%daily_stats}}', ['date', 'stat_type']);
        $this->createIndex('idx_daily_stats_date', '{{%daily_stats}}', 'date');
        $this->createIndex('idx_daily_stats_type', '{{%daily_stats}}', 'stat_type');

        // 8. Добавляем SEO поля в product если их нет
        $tableSchema = Yii::$app->db->schema->getTableSchema('{{%product}}');
        
        if (!isset($tableSchema->columns['seo_title'])) {
            $this->addColumn('{{%product}}', 'seo_title', $this->string(255));
        }
        if (!isset($tableSchema->columns['seo_description'])) {
            $this->addColumn('{{%product}}', 'seo_description', $this->text());
        }
        if (!isset($tableSchema->columns['seo_keywords'])) {
            $this->addColumn('{{%product}}', 'seo_keywords', $this->string(500));
        }
        if (!isset($tableSchema->columns['seo_h1'])) {
            $this->addColumn('{{%product}}', 'seo_h1', $this->string(255));
        }
        if (!isset($tableSchema->columns['canonical_url'])) {
            $this->addColumn('{{%product}}', 'canonical_url', $this->string(500));
        }

        // Добавляем поле tariff_id в order если его нет
        $orderSchema = Yii::$app->db->schema->getTableSchema('{{%order}}');
        if (!isset($orderSchema->columns['tariff_id'])) {
            $this->addColumn('{{%order}}', 'tariff_id', $this->integer());
        }
        if (!isset($orderSchema->columns['commission_amount'])) {
            $this->addColumn('{{%order}}', 'commission_amount', $this->decimal(10, 2)->defaultValue(0));
        }
        if (!isset($orderSchema->columns['delivery_cost'])) {
            $this->addColumn('{{%order}}', 'delivery_cost', $this->decimal(10, 2)->defaultValue(0));
        }
        if (!isset($orderSchema->columns['insurance_amount'])) {
            $this->addColumn('{{%order}}', 'insurance_amount', $this->decimal(10, 2)->defaultValue(0));
        }

        return true;
    }

    public function safeDown()
    {
        $this->dropTable('{{%daily_stats}}');
        $this->dropTable('{{%analytics_event}}');
        $this->dropTable('{{%product_review}}');
        $this->dropTable('{{%delivery_tracking}}');
        $this->dropTable('{{%tariff}}');
        $this->dropTable('{{%product_related}}');
        $this->dropTable('{{%product_stock}}');
        
        return true;
    }
}
