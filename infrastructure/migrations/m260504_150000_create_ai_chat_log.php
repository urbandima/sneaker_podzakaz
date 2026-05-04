<?php

use yii\db\Migration;

/**
 * Phase 4 monitoring table (CMP-159).
 * Stores structured logs of every AI-handled chat interaction:
 * response time, tool calls, escalation flag, lead context.
 */
class m260504_150000_create_ai_chat_log extends Migration
{
    public function safeUp()
    {
        $this->createTable('{{%ai_chat_log}}', [
            'id'             => $this->primaryKey(),
            'lead_id'        => $this->integer()->null()->comment('AmoCRM lead ID'),
            'pipeline_id'    => $this->integer()->null()->comment('7426646=сайт, 8054662=Яндекс карт'),
            'client_phone'   => $this->string(30)->null(),
            'message_text'   => $this->text()->null()->comment('Incoming message from customer'),
            'response_text'  => $this->text()->null()->comment('AI response sent'),
            'tools_used'     => $this->string(255)->null()->comment('Comma-separated tool names used'),
            'tool_loops'     => $this->smallInteger()->notNull()->defaultValue(0),
            'escalated'      => $this->boolean()->notNull()->defaultValue(false),
            'response_ms'    => $this->integer()->null()->comment('Total response time ms'),
            'model'          => $this->string(50)->null()->defaultValue('claude-sonnet-4-6'),
            'success'        => $this->boolean()->notNull()->defaultValue(true),
            'error_message'  => $this->string(500)->null(),
            'created_at'     => $this->integer()->notNull(),
        ]);

        $this->createIndex('idx_ai_chat_log_lead',       '{{%ai_chat_log}}', 'lead_id');
        $this->createIndex('idx_ai_chat_log_pipeline',   '{{%ai_chat_log}}', 'pipeline_id');
        $this->createIndex('idx_ai_chat_log_created',    '{{%ai_chat_log}}', 'created_at');
        $this->createIndex('idx_ai_chat_log_escalated',  '{{%ai_chat_log}}', 'escalated');
    }

    public function safeDown()
    {
        $this->dropTable('{{%ai_chat_log}}');
    }
}
