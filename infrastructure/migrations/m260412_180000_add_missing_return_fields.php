<?php

use yii\db\Migration;

/**
 * Добавляет отсутствующие поля в таблицу return_request
 */
class m260412_180000_add_missing_return_fields extends Migration
{
    public function safeUp()
    {
        // Поля уже могли быть добавлены создающей миграцией m250315_120100_create_return_tables —
        // добавляем только отсутствующие, чтобы не ронять чистые инсталляции (CI, новые окружения).
        $columns = [
            'refund_method' => $this->string(255)->null(),
            'refund_transaction' => $this->string(255)->null(),
            'pickup_address' => $this->string(500)->null(),
            'pickup_date' => $this->date()->null(),
            'tracking_number' => $this->string(255)->null(),
            'completed_at' => $this->dateTime()->null(),
            'comment' => $this->text()->null(),
            'admin_comment' => $this->text()->null(),
        ];
        $existing = $this->db->schema->getTableSchema('{{%return_request}}', true)->columnNames;
        foreach ($columns as $name => $type) {
            if (!in_array($name, $existing, true)) {
                $this->addColumn('{{%return_request}}', $name, $type);
            } else {
                echo "    > skipped: {{%return_request}}.$name уже существует\n";
            }
        }
    }

    public function safeDown()
    {
        $this->dropColumn('{{%return_request}}', 'refund_method');
        $this->dropColumn('{{%return_request}}', 'refund_transaction');
        $this->dropColumn('{{%return_request}}', 'pickup_address');
        $this->dropColumn('{{%return_request}}', 'pickup_date');
        $this->dropColumn('{{%return_request}}', 'tracking_number');
        $this->dropColumn('{{%return_request}}', 'completed_at');
        $this->dropColumn('{{%return_request}}', 'comment');
        $this->dropColumn('{{%return_request}}', 'admin_comment');
    }
}
