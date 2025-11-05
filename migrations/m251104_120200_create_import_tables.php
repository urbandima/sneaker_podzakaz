<?php

use yii\db\Migration;

/**
 * Создание таблиц для логирования импорта из Poizon
 */
class m251104_120200_create_import_tables extends Migration
{
    public function safeUp()
    {
        // Таблица батчей импорта (каждый запуск импорта)
        $this->createTable('{{%import_batch}}', [
            'id' => $this->primaryKey(),
            'source' => $this->string(50)->notNull()->defaultValue('poizon')->comment('Источник: poizon, manual, api'),
            'type' => $this->string(50)->notNull()->defaultValue('full')->comment('Тип: full (полный), update (обновление), sizes (только размеры)'),
            'status' => $this->string(20)->notNull()->defaultValue('pending')->comment('pending, processing, completed, failed'),
            'started_at' => $this->timestamp()->null()->comment('Время старта'),
            'finished_at' => $this->timestamp()->null()->comment('Время завершения'),
            'duration_seconds' => $this->integer()->comment('Длительность в секундах'),
            
            // Метрики
            'total_items' => $this->integer()->defaultValue(0)->comment('Всего товаров обработано'),
            'created_count' => $this->integer()->defaultValue(0)->comment('Создано новых'),
            'updated_count' => $this->integer()->defaultValue(0)->comment('Обновлено существующих'),
            'skipped_count' => $this->integer()->defaultValue(0)->comment('Пропущено'),
            'error_count' => $this->integer()->defaultValue(0)->comment('Ошибок'),
            
            // Дополнительная информация
            'config' => $this->text()->comment('JSON с конфигурацией импорта'),
            'summary' => $this->text()->comment('JSON с итоговой статистикой'),
            'error_message' => $this->text()->comment('Сообщение об ошибке (если status=failed)'),
            
            'created_by' => $this->integer()->comment('Кто запустил импорт (user_id или NULL для cron)'),
            'created_at' => $this->timestamp()->defaultExpression('CURRENT_TIMESTAMP'),
        ]);

        $this->createIndex('idx-import_batch-status', '{{%import_batch}}', 'status');
        $this->createIndex('idx-import_batch-source', '{{%import_batch}}', 'source');
        $this->createIndex('idx-import_batch-started_at', '{{%import_batch}}', 'started_at');

        // Таблица детальных логов импорта (каждый товар)
        $this->createTable('{{%import_log}}', [
            'id' => $this->primaryKey(),
            'batch_id' => $this->integer()->notNull()->comment('ID батча импорта'),
            'product_id' => $this->integer()->comment('ID товара (если создан/обновлен)'),
            'action' => $this->string(20)->notNull()->comment('created, updated, skipped, error'),
            'level' => $this->string(20)->notNull()->defaultValue('info')->comment('info, warning, error'),
            
            // Данные товара
            'sku' => $this->string(100)->comment('SKU товара'),
            'poizon_id' => $this->string(50)->comment('ID в Poizon'),
            'product_name' => $this->string(255)->comment('Название товара'),
            
            // Детали
            'message' => $this->text()->comment('Сообщение лога'),
            'data' => $this->text()->comment('JSON с дополнительными данными'),
            'error_details' => $this->text()->comment('Детали ошибки'),
            
            'created_at' => $this->timestamp()->defaultExpression('CURRENT_TIMESTAMP'),
        ]);

        $this->createIndex('idx-import_log-batch_id', '{{%import_log}}', 'batch_id');
        $this->createIndex('idx-import_log-product_id', '{{%import_log}}', 'product_id');
        $this->createIndex('idx-import_log-action', '{{%import_log}}', 'action');
        $this->createIndex('idx-import_log-level', '{{%import_log}}', 'level');
        $this->createIndex('idx-import_log-sku', '{{%import_log}}', 'sku');

        $this->addForeignKey(
            'fk-import_log-batch_id',
            '{{%import_log}}',
            'batch_id',
            '{{%import_batch}}',
            'id',
            'CASCADE'
        );

        $this->addForeignKey(
            'fk-import_log-product_id',
            '{{%import_log}}',
            'product_id',
            '{{%product}}',
            'id',
            'SET NULL'
        );

        $this->addForeignKey(
            'fk-import_batch-created_by',
            '{{%import_batch}}',
            'created_by',
            '{{%user}}',
            'id',
            'SET NULL'
        );
    }

    public function safeDown()
    {
        $this->dropForeignKey('fk-import_batch-created_by', '{{%import_batch}}');
        $this->dropForeignKey('fk-import_log-product_id', '{{%import_log}}');
        $this->dropForeignKey('fk-import_log-batch_id', '{{%import_log}}');
        
        $this->dropTable('{{%import_log}}');
        $this->dropTable('{{%import_batch}}');
    }
}
