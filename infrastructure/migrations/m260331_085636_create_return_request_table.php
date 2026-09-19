<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%return_request}}`.
 */
class m260331_085636_create_return_request_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        // Таблица уже создаётся более ранней миграцией m250315_120100_create_return_tables со
        // схемой, которую реально использует app\backend\modules\returns\models\ReturnRequest
        // (items_json/admin_comment/refund_method/pickup_address/tracking_number и т.д.). Эта
        // миграция — дублирующая попытка создать ту же таблицу с другой, неиспользуемой схемой;
        // делаем её безопасным no-op, чтобы не ронять чистые инсталляции (CI, новые окружения).
        if ($this->db->schema->getTableSchema('{{%return_request}}', true) !== null) {
            echo "    > skipped: {{%return_request}} уже создана миграцией m250315_120100_create_return_tables\n";
            return true;
        }

        $this->createTable('{{%return_request}}', [
            'id' => $this->primaryKey(),
            'return_number' => $this->string(50)->notNull()->unique(),
            'order_id' => $this->integer()->notNull(),
            'customer_id' => $this->integer()->notNull(),
            'reason' => $this->string(50)->notNull(),
            'reason_description' => $this->text(),
            'refund_amount' => $this->decimal(10, 2)->notNull(),
            'status' => $this->string(50)->notNull()->defaultValue('pending'),
            'requested_at' => $this->integer()->notNull(),
            'processed_at' => $this->integer(),
            'processed_by' => $this->integer(),
            'admin_notes' => $this->text(),
            'created_at' => $this->integer()->notNull(),
            'updated_at' => $this->integer()->notNull(),
        ]);

        // Индексы
        $this->createIndex('idx-return_request-order_id', '{{%return_request}}', 'order_id');
        $this->createIndex('idx-return_request-customer_id', '{{%return_request}}', 'customer_id');
        $this->createIndex('idx-return_request-status', '{{%return_request}}', 'status');
        $this->createIndex('idx-return_request-requested_at', '{{%return_request}}', 'requested_at');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        // Откатываем, только если таблицу создала именно эта миграция (маркер — колонка
        // reason_description, которой нет в схеме m250315_120100_create_return_tables).
        $schema = $this->db->schema->getTableSchema('{{%return_request}}', true);
        if ($schema !== null && in_array('reason_description', $schema->columnNames, true)) {
            $this->dropTable('{{%return_request}}');
        } else {
            echo "    > skipped down: {{%return_request}} принадлежит другой миграции\n";
        }
    }
}
