<?php

use yii\db\Migration;

/**
 * Обновление статусов заказов для нового workflow:
 * 
 * Новый → Оплачен → Подтвержден и оплачен → Заказано → 
 * Ожидается на международном складе → В международной доставке → 
 * Заказ на складе → Заказ отправлен в доставку → Заказ выдан
 */
class m260412_150000_update_order_statuses extends Migration
{
    public function safeUp()
    {
        // 1. Переносим заказы со старыми статусами на новые
        // created → new
        $this->update('{{%order}}', ['status' => 'new'], ['status' => 'created']);
        // received → at_warehouse (ближе всего по смыслу)
        $this->update('{{%order}}', ['status' => 'at_warehouse'], ['status' => 'received']);
        // issued → delivered
        $this->update('{{%order}}', ['status' => 'delivered'], ['status' => 'issued']);
        // processing → awaiting_warehouse
        $this->update('{{%order}}', ['status' => 'awaiting_warehouse'], ['status' => 'processing']);
        // shipped → international_delivery
        $this->update('{{%order}}', ['status' => 'international_delivery'], ['status' => 'shipped']);
        // confirmed → confirmed_and_paid
        $this->update('{{%order}}', ['status' => 'confirmed_and_paid'], ['status' => 'confirmed']);

        // 2. Обновляем историю заказов (старые статусы в комментариях)
        $this->update('{{%order_history}}', ['old_status' => 'new'], ['old_status' => 'created']);
        $this->update('{{%order_history}}', ['new_status' => 'new'], ['new_status' => 'created']);
        $this->update('{{%order_history}}', ['old_status' => 'at_warehouse'], ['old_status' => 'received']);
        $this->update('{{%order_history}}', ['new_status' => 'at_warehouse'], ['new_status' => 'received']);
        $this->update('{{%order_history}}', ['old_status' => 'delivered'], ['old_status' => 'issued']);
        $this->update('{{%order_history}}', ['new_status' => 'delivered'], ['new_status' => 'issued']);
        $this->update('{{%order_history}}', ['old_status' => 'awaiting_warehouse'], ['old_status' => 'processing']);
        $this->update('{{%order_history}}', ['new_status' => 'awaiting_warehouse'], ['new_status' => 'processing']);
        $this->update('{{%order_history}}', ['old_status' => 'international_delivery'], ['old_status' => 'shipped']);
        $this->update('{{%order_history}}', ['new_status' => 'international_delivery'], ['new_status' => 'shipped']);
        $this->update('{{%order_history}}', ['old_status' => 'confirmed_and_paid'], ['old_status' => 'confirmed']);
        $this->update('{{%order_history}}', ['new_status' => 'confirmed_and_paid'], ['new_status' => 'confirmed']);

        // 3. Очищаем таблицу order_status и заполняем новыми статусами
        $this->delete('{{%order_status}}');

        $statuses = [
            ['key' => 'new', 'label' => 'Новый', 'sort' => 0, 'logist_available' => 0, 'is_active' => 1],
            ['key' => 'paid', 'label' => 'Оплачен', 'sort' => 1, 'logist_available' => 0, 'is_active' => 1],
            ['key' => 'confirmed_and_paid', 'label' => 'Подтвержден и оплачен', 'sort' => 2, 'logist_available' => 0, 'is_active' => 1],
            ['key' => 'ordered', 'label' => 'Заказано', 'sort' => 3, 'logist_available' => 1, 'is_active' => 1],
            ['key' => 'awaiting_warehouse', 'label' => 'Ожидается на международном складе', 'sort' => 4, 'logist_available' => 1, 'is_active' => 1],
            ['key' => 'international_delivery', 'label' => 'В международной доставке', 'sort' => 5, 'logist_available' => 1, 'is_active' => 1],
            ['key' => 'at_warehouse', 'label' => 'Заказ на складе', 'sort' => 6, 'logist_available' => 1, 'is_active' => 1],
            ['key' => 'local_delivery', 'label' => 'Заказ отправлен в доставку', 'sort' => 7, 'logist_available' => 1, 'is_active' => 1],
            ['key' => 'delivered', 'label' => 'Заказ выдан', 'sort' => 8, 'logist_available' => 1, 'is_active' => 1],
            ['key' => 'canceled', 'label' => 'Отменен', 'sort' => 9, 'logist_available' => 0, 'is_active' => 1],
        ];

        foreach ($statuses as $status) {
            $this->insert('{{%order_status}}', $status);
        }

        echo "  > Статусы заказов обновлены. 10 статусов вместо 6.\n";
    }

    public function safeDown()
    {
        // Возвращаем заказы на старые статусы
        $this->update('{{%order}}', ['status' => 'created'], ['status' => 'new']);
        $this->update('{{%order}}', ['status' => 'received'], ['status' => 'at_warehouse']);
        $this->update('{{%order}}', ['status' => 'issued'], ['status' => 'delivered']);
        $this->update('{{%order}}', ['status' => 'processing'], ['status' => 'awaiting_warehouse']);
        $this->update('{{%order}}', ['status' => 'shipped'], ['status' => 'international_delivery']);
        $this->update('{{%order}}', ['status' => 'confirmed'], ['status' => 'confirmed_and_paid']);

        // Восстанавливаем историю
        $this->update('{{%order_history}}', ['old_status' => 'created'], ['old_status' => 'new']);
        $this->update('{{%order_history}}', ['new_status' => 'created'], ['new_status' => 'new']);
        $this->update('{{%order_history}}', ['old_status' => 'received'], ['old_status' => 'at_warehouse']);
        $this->update('{{%order_history}}', ['new_status' => 'received'], ['new_status' => 'at_warehouse']);
        $this->update('{{%order_history}}', ['old_status' => 'issued'], ['old_status' => 'delivered']);
        $this->update('{{%order_history}}', ['new_status' => 'issued'], ['new_status' => 'delivered']);
        $this->update('{{%order_history}}', ['old_status' => 'processing'], ['old_status' => 'awaiting_warehouse']);
        $this->update('{{%order_history}}', ['new_status' => 'processing'], ['new_status' => 'awaiting_warehouse']);
        $this->update('{{%order_history}}', ['old_status' => 'shipped'], ['old_status' => 'international_delivery']);
        $this->update('{{%order_history}}', ['new_status' => 'shipped'], ['new_status' => 'international_delivery']);
        $this->update('{{%order_history}}', ['old_status' => 'confirmed'], ['old_status' => 'confirmed_and_paid']);
        $this->update('{{%order_history}}', ['new_status' => 'confirmed'], ['new_status' => 'confirmed_and_paid']);

        // Восстанавливаем старые статусы
        $this->delete('{{%order_status}}');

        $oldStatuses = [
            ['key' => 'created', 'label' => 'Заказ составлен', 'sort' => 0, 'logist_available' => 0, 'is_active' => 1],
            ['key' => 'paid', 'label' => 'Заказ оплачен', 'sort' => 1, 'logist_available' => 0, 'is_active' => 1],
            ['key' => 'ordered', 'label' => 'Заказан товар', 'sort' => 2, 'logist_available' => 1, 'is_active' => 1],
            ['key' => 'received', 'label' => 'Заказ получен', 'sort' => 3, 'logist_available' => 1, 'is_active' => 1],
            ['key' => 'issued', 'label' => 'Заказ выдан', 'sort' => 4, 'logist_available' => 1, 'is_active' => 1],
            ['key' => 'canceled', 'label' => 'Отменен', 'sort' => 5, 'logist_available' => 0, 'is_active' => 1],
        ];

        foreach ($oldStatuses as $status) {
            $this->insert('{{%order_status}}', $status);
        }
    }
}
