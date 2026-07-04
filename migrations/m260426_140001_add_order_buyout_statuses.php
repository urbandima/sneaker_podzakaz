<?php

use yii\db\Migration;

/**
 * Добавляет статусы заказа, связанные с workflow выкупа
 */
class m260426_140001_add_order_buyout_statuses extends Migration
{
    private array $statuses = [
        ['key' => 'awaiting_buyout',        'label' => 'Ожидает выкупа',         'color' => '#9333ea', 'sort' => 35],
        ['key' => 'bought_at_source',        'label' => 'Выкуплен на источнике',  'color' => '#2563eb', 'sort' => 40],
        ['key' => 'in_transit_from_source',  'label' => 'В пути от источника',    'color' => '#d97706', 'sort' => 45],
        ['key' => 'arrived_at_warehouse',    'label' => 'Прибыл на склад',        'color' => '#7c3aed', 'sort' => 50],
        ['key' => 'ready_to_ship',           'label' => 'Готов к отправке',       'color' => '#059669', 'sort' => 55],
    ];

    public function safeUp()
    {
        foreach ($this->statuses as $s) {
            $exists = $this->db->createCommand(
                'SELECT COUNT(*) FROM {{%order_status}} WHERE `key` = :k',
                [':k' => $s['key']]
            )->queryScalar();

            if (!$exists) {
                $this->insert('{{%order_status}}', [
                    'key'               => $s['key'],
                    'label'             => $s['label'],
                    'color'             => $s['color'],
                    'sort'              => $s['sort'],
                    'is_active'         => 1,
                    'is_system'         => 1,
                    'logist_available'  => 0,
                ]);
            }
        }
    }

    public function safeDown()
    {
        $keys = array_column($this->statuses, 'key');
        $this->delete('{{%order_status}}', ['key' => $keys]);
    }
}
