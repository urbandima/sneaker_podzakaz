<?php

use yii\db\Migration;

/**
 * CMP-414: удаление таблиц неканонической ("продвинутой") системы импорта.
 *
 * m260317_220000_create_advanced_import_system и m260317_230000_create_import_notification_table
 * создали import_source/import_task/import_product_price/import_category_map/import_notification
 * и currency_rate — независимую систему для многопоточного парсинга Lamoda/Zalando/StockX/Dewu.
 * Код, который её обслуживал (ImportController/ImportService/DewuParser и т.д.), был мёртвым
 * дублем: модель ImportLog в этой системе требовала task_id, которого нет и не может быть
 * в единственной физической таблице import_log (у неё batch_id, FK на import_batch) — любое
 * сохранение лога падало с UnknownPropertyException. Канонической по продуктовому решению
 * (см. CMP-414) осталась система ImportBatch/ImportLog (app\backend\modules\catalog\models),
 * обслуживающая прямой API-импорт из Poizon.
 *
 * Старые миграции m260317_220000/m260317_230000 не редактируются и не удаляются (уже
 * применены на части окружений) — эта миграция обратно выполняет их safeDown().
 */
class m260922_110000_drop_advanced_import_system extends Migration
{
    public function safeUp()
    {
        $this->dropTable('{{%currency_rate}}');

        $this->dropForeignKey('fk-import_notification-task_id', '{{%import_notification}}');
        $this->dropTable('{{%import_notification}}');

        $this->dropForeignKey('fk-product-best_price_source_id', '{{%product}}');
        $this->dropForeignKey('fk-product-import_source_id', '{{%product}}');
        $this->dropColumn('{{%product}}', 'last_import_at');
        $this->dropColumn('{{%product}}', 'best_price_source_id');
        $this->dropColumn('{{%product}}', 'external_sku');
        $this->dropColumn('{{%product}}', 'import_source_id');

        $this->dropTable('{{%import_category_map}}');
        $this->dropTable('{{%import_product_price}}');
        $this->dropTable('{{%import_task}}');
        $this->dropTable('{{%import_source}}');
    }

    public function safeDown()
    {
        $this->createTable('{{%import_source}}', [
            'id' => $this->primaryKey(),
            'name' => $this->string(100)->notNull()->comment('Название источника'),
            'code' => $this->string(50)->notNull()->unique()->comment('Код: lamoda, dewu, zalando, stockx'),
            'base_url' => $this->string(255)->notNull()->comment('Базовый URL сайта'),
            'is_active' => $this->boolean()->defaultValue(true)->comment('Активен ли источник'),
            'parse_delay_min' => $this->integer()->defaultValue(2)->comment('Минимальная задержка между запросами (сек)'),
            'parse_delay_max' => $this->integer()->defaultValue(5)->comment('Максимальная задержка между запросами (сек)'),
            'proxy_enabled' => $this->boolean()->defaultValue(false)->comment('Использовать прокси'),
            'proxy_list' => $this->text()->comment('JSON список прокси'),
            'proxy_rotation' => $this->string(20)->defaultValue('random')->comment('Тип ротации: random, sequential'),
            'captcha_service' => $this->string(50)->defaultValue('2captcha')->comment('Сервис CAPTCHA: 2captcha, anticaptcha, capmonster'),
            'captcha_api_key' => $this->string(255)->comment('API ключ для CAPTCHA сервиса'),
            'captcha_fallback_service' => $this->string(50)->comment('Резервный сервис CAPTCHA'),
            'captcha_fallback_api_key' => $this->string(255)->comment('API ключ резервного сервиса'),
            'currency_code' => $this->string(3)->notNull()->defaultValue('BYN')->comment('Код валюты источника'),
            'settings' => $this->text()->comment('JSON с дополнительными настройками парсинга'),
            'last_run_at' => $this->timestamp()->null()->comment('Последний запуск'),
            'total_products_parsed' => $this->integer()->defaultValue(0)->comment('Всего товаров спарсено'),
            'successful_runs' => $this->integer()->defaultValue(0)->comment('Успешных запусков'),
            'failed_runs' => $this->integer()->defaultValue(0)->comment('Неудачных запусков'),
            'created_at' => $this->timestamp()->defaultExpression('CURRENT_TIMESTAMP'),
            'updated_at' => $this->timestamp()->defaultExpression('CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'),
        ]);
        $this->createIndex('idx-import_source-code', '{{%import_source}}', 'code', true);
        $this->createIndex('idx-import_source-is_active', '{{%import_source}}', 'is_active');

        $this->createTable('{{%import_task}}', [
            'id' => $this->primaryKey(),
            'source_id' => $this->integer()->notNull()->comment('ID источника'),
            'status' => $this->string(20)->notNull()->defaultValue('pending')->comment('pending, running, completed, failed, cancelled'),
            'total_products' => $this->integer()->defaultValue(0)->comment('Всего товаров в задаче'),
            'processed_products' => $this->integer()->defaultValue(0)->comment('Обработано товаров'),
            'imported_count' => $this->integer()->defaultValue(0)->comment('Импортировано новых'),
            'updated_count' => $this->integer()->defaultValue(0)->comment('Обновлено существующих'),
            'failed_count' => $this->integer()->defaultValue(0)->comment('Ошибок'),
            'duplicate_count' => $this->integer()->defaultValue(0)->comment('Дубликатов найдено'),
            'started_at' => $this->timestamp()->null()->comment('Время старта'),
            'finished_at' => $this->timestamp()->null()->comment('Время завершения'),
            'duration_seconds' => $this->integer()->comment('Длительность в секундах'),
            'config' => $this->text()->comment('JSON с параметрами запуска (категории, фильтры)'),
            'error_message' => $this->text()->comment('Сообщение об ошибке'),
            'created_by' => $this->integer()->comment('Кто запустил (user_id или NULL для cron)'),
            'created_at' => $this->timestamp()->defaultExpression('CURRENT_TIMESTAMP'),
        ]);
        $this->createIndex('idx-import_task-source_id', '{{%import_task}}', 'source_id');
        $this->createIndex('idx-import_task-status', '{{%import_task}}', 'status');
        $this->createIndex('idx-import_task-started_at', '{{%import_task}}', 'started_at');
        $this->addForeignKey('fk-import_task-source_id', '{{%import_task}}', 'source_id', '{{%import_source}}', 'id', 'CASCADE');

        $this->createTable('{{%import_product_price}}', [
            'id' => $this->primaryKey(),
            'product_id' => $this->integer()->comment('ID товара (если сопоставлен)'),
            'source_id' => $this->integer()->notNull()->comment('ID источника'),
            'external_sku' => $this->string(100)->notNull()->comment('SKU товара в источнике'),
            'price_original' => $this->decimal(10, 2)->notNull()->comment('Цена в оригинальной валюте'),
            'price_byn' => $this->decimal(10, 2)->comment('Цена в BYN'),
            'currency_code' => $this->string(3)->notNull()->comment('Код валюты'),
            'exchange_rate' => $this->decimal(10, 6)->comment('Курс конвертации'),
            'size' => $this->string(50)->comment('Размер товара'),
            'is_available' => $this->boolean()->defaultValue(true)->comment('Доступен ли товар'),
            'url' => $this->string(500)->comment('URL товара в источнике'),
            'parsed_at' => $this->timestamp()->defaultExpression('CURRENT_TIMESTAMP')->comment('Время парсинга'),
        ]);
        $this->createIndex('idx-import_product_price-product_id', '{{%import_product_price}}', 'product_id');
        $this->createIndex('idx-import_product_price-source_id', '{{%import_product_price}}', 'source_id');
        $this->createIndex('idx-import_product_price-external_sku', '{{%import_product_price}}', 'external_sku');
        $this->createIndex('idx-import_product_price-parsed_at', '{{%import_product_price}}', 'parsed_at');
        $this->createIndex('idx-import_product_price-sku_size', '{{%import_product_price}}', ['external_sku', 'size']);
        $this->addForeignKey('fk-import_product_price-product_id', '{{%import_product_price}}', 'product_id', '{{%product}}', 'id', 'CASCADE');
        $this->addForeignKey('fk-import_product_price-source_id', '{{%import_product_price}}', 'source_id', '{{%import_source}}', 'id', 'CASCADE');

        $this->createTable('{{%import_category_map}}', [
            'id' => $this->primaryKey(),
            'source_id' => $this->integer()->notNull()->comment('ID источника'),
            'source_category_name' => $this->string(255)->notNull()->comment('Название категории в источнике'),
            'source_category_url' => $this->string(500)->comment('URL категории в источнике'),
            'category_id' => $this->integer()->comment('ID категории в нашей системе'),
            'is_auto_mapped' => $this->boolean()->defaultValue(false)->comment('Автоматическое сопоставление'),
            'priority' => $this->integer()->defaultValue(0)->comment('Приоритет (обувь = высокий)'),
            'created_at' => $this->timestamp()->defaultExpression('CURRENT_TIMESTAMP'),
            'updated_at' => $this->timestamp()->defaultExpression('CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'),
        ]);
        $this->createIndex('idx-import_category_map-source_id', '{{%import_category_map}}', 'source_id');
        $this->createIndex('idx-import_category_map-category_id', '{{%import_category_map}}', 'category_id');
        $this->addForeignKey('fk-import_category_map-source_id', '{{%import_category_map}}', 'source_id', '{{%import_source}}', 'id', 'CASCADE');
        $this->addForeignKey('fk-import_category_map-category_id', '{{%import_category_map}}', 'category_id', '{{%category}}', 'id', 'SET NULL');

        $this->addColumn('{{%product}}', 'import_source_id', $this->integer()->comment('ID источника импорта'));
        $this->addColumn('{{%product}}', 'external_sku', $this->string(100)->comment('SKU товара в источнике'));
        $this->addColumn('{{%product}}', 'best_price_source_id', $this->integer()->comment('ID источника с лучшей ценой'));
        $this->addColumn('{{%product}}', 'last_import_at', $this->timestamp()->null()->comment('Последний импорт'));
        $this->createIndex('idx-product-import_source_id', '{{%product}}', 'import_source_id');
        $this->createIndex('idx-product-external_sku', '{{%product}}', 'external_sku');
        $this->createIndex('idx-product-best_price_source_id', '{{%product}}', 'best_price_source_id');
        $this->addForeignKey('fk-product-import_source_id', '{{%product}}', 'import_source_id', '{{%import_source}}', 'id', 'SET NULL');
        $this->addForeignKey('fk-product-best_price_source_id', '{{%product}}', 'best_price_source_id', '{{%import_source}}', 'id', 'SET NULL');

        $this->createTable('{{%currency_rate}}', [
            'id' => $this->primaryKey(),
            'currency_code' => $this->string(3)->notNull()->unique()->comment('Код валюты (USD, EUR, CNY)'),
            'rate_to_byn' => $this->decimal(10, 6)->notNull()->comment('Курс к BYN'),
            'rate_date' => $this->date()->notNull()->comment('Дата курса'),
            'created_at' => $this->timestamp()->defaultExpression('CURRENT_TIMESTAMP'),
            'updated_at' => $this->timestamp()->defaultExpression('CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'),
        ]);
        $this->createIndex('idx-currency_rate-currency_code', '{{%currency_rate}}', 'currency_code', true);
        $this->createIndex('idx-currency_rate-rate_date', '{{%currency_rate}}', 'rate_date');

        $this->createTable('{{%import_notification}}', [
            'id' => $this->primaryKey(),
            'task_id' => $this->integer()->notNull()->comment('ID задачи импорта'),
            'type' => $this->string(20)->notNull()->comment('Тип: success, error, warning, info'),
            'title' => $this->string(255)->notNull()->comment('Заголовок'),
            'message' => $this->text()->notNull()->comment('Сообщение'),
            'is_read' => $this->boolean()->defaultValue(false)->comment('Прочитано'),
            'created_at' => $this->timestamp()->defaultExpression('CURRENT_TIMESTAMP'),
        ]);
        $this->createIndex('idx-import_notification-task_id', '{{%import_notification}}', 'task_id');
        $this->createIndex('idx-import_notification-is_read', '{{%import_notification}}', 'is_read');
        $this->createIndex('idx-import_notification-created_at', '{{%import_notification}}', 'created_at');
        $this->addForeignKey('fk-import_notification-task_id', '{{%import_notification}}', 'task_id', '{{%import_task}}', 'id', 'CASCADE');
    }
}
