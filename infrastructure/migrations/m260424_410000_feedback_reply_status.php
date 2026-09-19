<?php

use yii\db\Migration;

class m260424_410000_feedback_reply_status extends Migration
{
    public function up()
    {
        // На чистой инсталляции таблица {{%feedback}} на этот момент ещё не существует — её создаёт
        // более поздняя миграция m260427_300000_create_missing_finance_feedback_tables, и она уже
        // включает status/reply_text/replied_at/idx_feedback_status в исходную схему. Эта миграция
        // становится no-op в таком случае; если таблица всё же существует (более старое окружение
        // без этих колонок) — доносим их идемпотентно.
        $schema = $this->db->schema->getTableSchema('{{%feedback}}', true);
        if ($schema === null) {
            echo "    > skipped: {{%feedback}} ещё не создана, добавит m260427_300000_create_missing_finance_feedback_tables\n";
            return true;
        }

        if (!in_array('status', $schema->columnNames, true)) {
            $this->addColumn('{{%feedback}}', 'status',
                $this->string(20)->notNull()->defaultValue('new')->after('is_read'));
            $this->update('{{%feedback}}', ['status' => 'read'], ['is_read' => 1]);
            $this->update('{{%feedback}}', ['status' => 'new'], ['is_read' => 0]);
        }
        if (!in_array('reply_text', $schema->columnNames, true)) {
            $this->addColumn('{{%feedback}}', 'reply_text',
                $this->text()->null()->after('status'));
        }
        if (!in_array('replied_at', $schema->columnNames, true)) {
            $this->addColumn('{{%feedback}}', 'replied_at',
                $this->integer()->null()->after('reply_text'));
        }
        try {
            $this->createIndex('idx_feedback_status', '{{%feedback}}', 'status');
        } catch (\Exception $e) {
            // индекс уже создан вместе с таблицей
        }
    }

    public function down()
    {
        // Колонки/индекс принадлежат m260427_300000_create_missing_finance_feedback_tables,
        // если таблицу создавала она — откат делать нечего.
    }
}
