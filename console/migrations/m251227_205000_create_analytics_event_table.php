<?php

use yii\db\Migration;

/**
 * Создает таблицу analytics_event, если она отсутствует (idempotent fix для админской аналитики).
 */
class m251227_205000_create_analytics_event_table extends Migration
{
    public function safeUp(): void
    {
        $schema = $this->db->schema->getTableSchema('{{%analytics_event}}', true);
        if ($schema !== null) {
            return; // таблица уже существует
        }

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
        $this->createIndex('idx_analytics_event_entity', '{{%analytics_event}}', ['entity_type', 'entity_id']);
        $this->createIndex('idx_analytics_event_created', '{{%analytics_event}}', 'created_at');
        $this->createIndex('idx_analytics_event_session', '{{%analytics_event}}', 'session_id');
    }

    public function safeDown(): void
    {
        $schema = $this->db->schema->getTableSchema('{{%analytics_event}}', true);
        if ($schema !== null) {
            $this->dropTable('{{%analytics_event}}');
        }
    }
}
