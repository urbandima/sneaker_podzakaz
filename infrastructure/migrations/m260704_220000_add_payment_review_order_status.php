<?php

use yii\db\Migration;

/**
 * AUDIT-34: новый статус для заказов с загруженным, но не подтверждённым
 * менеджером подтверждением оплаты (вместо автоматического перевода в paid).
 */
class m260704_220000_add_payment_review_order_status extends Migration
{
    private string $key = 'payment_review';

    public function safeUp()
    {
        $exists = $this->db->createCommand(
            'SELECT COUNT(*) FROM {{%order_status}} WHERE `key` = :k',
            [':k' => $this->key]
        )->queryScalar();

        if (!$exists) {
            $newSort = $this->db->createCommand(
                "SELECT COALESCE(MIN(sort), 1) FROM {{%order_status}} WHERE `key` = 'paid'"
            )->queryScalar();

            $this->insert('{{%order_status}}', [
                'key'              => $this->key,
                'label'            => 'На проверке оплаты',
                'color'            => '#f59e0b',
                'sort'             => (int)$newSort,
                'is_active'        => 1,
                'is_system'        => 1,
                'logist_available' => 0,
            ]);
        }
    }

    public function safeDown()
    {
        $this->delete('{{%order_status}}', ['key' => $this->key]);
    }
}
