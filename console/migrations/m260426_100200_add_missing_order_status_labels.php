<?php

use yii\db\Migration;

class m260426_100200_add_missing_order_status_labels extends Migration
{
    private $statuses = [
        'created'              => ['Создан', '#6b7280', 0],
        'imported'             => ['Импортирован', '#9ca3af', 0],
        'return'               => ['Возврат', '#dc2626', 1],
        'returned'             => ['Возвращён', '#dc2626', 0],
        'trash'                => ['Удалён', '#6b7280', 0],
        'awaiting_buyout'      => ['Ожидает выкупа', '#d97706', 1],
        'bought_at_source'     => ['Выкуплен на источнике', '#7c3aed', 1],
        'in_transit_from_source' => ['В пути от источника', '#0891b2', 1],
        'arrived_at_warehouse' => ['Прибыл на склад', '#059669', 1],
        'ready_to_ship'        => ['Готов к отправке', '#059669', 1],
    ];

    public function safeUp()
    {
        $existing = $this->db->createCommand('SELECT `key` FROM {{%order_status}}')->queryColumn();

        foreach ($this->statuses as $key => [$label, $color, $active]) {
            if (in_array($key, $existing)) {
                $this->update('{{%order_status}}', ['label' => $label], ['key' => $key]);
            } else {
                $this->insert('{{%order_status}}', [
                    'key'       => $key,
                    'label'     => $label,
                    'color'     => $color,
                    'is_active' => $active,
                    'sort'      => 99,
                ]);
            }
        }
    }

    public function safeDown()
    {
        // Labels are non-destructive to revert
    }
}
