<?php

namespace app\backend\modules\admin\services;

use Yii;

/**
 * AmocrmStatusMapper — bidirectional mapping between our Order statuses and AmoCRM lead statuses.
 *
 * AmoCRM status IDs are configured in settings[amocrm][status_map_*] or in amocrm_field_mapping.
 * Default map uses pipeline status names when IDs are not yet configured.
 */
class AmocrmStatusMapper
{
    // Our order status → AmoCRM status name hint (used for display and fallback)
    private static array $orderToAmoName = [
        'new'                    => 'Новый',
        'confirmed'              => 'Подтверждён',
        'awaiting_payment'       => 'Ожидает оплаты',
        'paid'                   => 'Оплачен',
        'awaiting_buyout'        => 'Ожидает выкупа',
        'bought_at_source'       => 'Выкуплен на источнике',
        'in_transit_from_source' => 'В пути от источника',
        'arrived_at_warehouse'   => 'Прибыл на склад',
        'ready_to_ship'          => 'Готов к отправке',
        'shipped'                => 'Отправлен',
        'delivered'              => 'Доставлен',
        'cancelled'              => 'Отменён',
        'refunded'               => 'Возврат',
    ];

    // AmoCRM status name (lower) → our order status
    private static array $amoNameToOrder = [
        'новый'                  => 'new',
        'подтверждён'            => 'confirmed',
        'подтвержден'            => 'confirmed',
        'ожидает оплаты'         => 'awaiting_payment',
        'оплачен'                => 'paid',
        'купили'                 => 'paid',
        'готов к выкупу'         => 'awaiting_buyout',
        'выкуплен'               => 'bought_at_source',
        'в пути'                 => 'in_transit_from_source',
        'на складе'              => 'arrived_at_warehouse',
        'готов к отправке'       => 'ready_to_ship',
        'отправлен'              => 'shipped',
        'доставлен'              => 'delivered',
        'выдан'                  => 'delivered',
        'отменён'                => 'cancelled',
        'отменен'                => 'cancelled',
        'возврат'                => 'refunded',
    ];

    /**
     * Get the AmoCRM pipeline status ID for our order status.
     * First checks settings table for explicit ID mapping.
     */
    public static function toAmocrmStatusId(string $orderStatus): ?int
    {
        $key = 'status_id_' . $orderStatus;
        $id  = (int) Yii::$app->settings->get('amocrm', $key, 0);
        return $id > 0 ? $id : null;
    }

    /**
     * Convert AmoCRM status ID to our order status.
     * Checks settings-configured mapping first, then falls back to name mapping.
     */
    public static function fromAmocrmStatusId(int $amoStatusId, ?string $amoStatusName = null): ?string
    {
        // Check explicit ID mapping stored in settings
        $all = Yii::$app->settings->getSection('amocrm') ?? [];
        foreach ($all as $key => $val) {
            if (str_starts_with($key, 'status_id_') && (int)$val === $amoStatusId) {
                return substr($key, strlen('status_id_'));
            }
        }

        // Check amocrm_field_mapping table for status mappings
        try {
            $row = Yii::$app->db->createCommand(
                'SELECT local_field FROM amocrm_field_mapping WHERE entity_type=\'lead\' AND amocrm_field_id=:id AND local_field LIKE \'status:%\'',
                [':id' => $amoStatusId]
            )->queryOne();
            if ($row) {
                return substr($row['local_field'], 7); // strip 'status:' prefix
            }
        } catch (\Throwable $e) {}

        // Fallback to name-based lookup
        if ($amoStatusName) {
            $normalized = mb_strtolower(trim($amoStatusName));
            return self::$amoNameToOrder[$normalized] ?? null;
        }

        return null;
    }

    /**
     * Get the display name for our order status as it should appear in AmoCRM.
     */
    public static function toAmocrmName(string $orderStatus): string
    {
        return self::$orderToAmoName[$orderStatus] ?? $orderStatus;
    }

    /**
     * Determine if a webhook lead status change should trigger order creation.
     * Returns true when the status name matches known "ready to order" patterns.
     */
    public static function shouldCreateOrder(int $statusId, ?string $statusName): bool
    {
        // Check settings for the "create order" trigger status ID
        $triggerId = (int) Yii::$app->settings->get('amocrm', 'create_order_status_id', 0);
        if ($triggerId && $statusId === $triggerId) {
            return true;
        }

        if ($statusName) {
            $lower = mb_strtolower(trim($statusName));
            $triggers = ['купили', 'готов к выкупу', 'создать заказ', 'оформить заказ', 'в работе'];
            foreach ($triggers as $t) {
                if (str_contains($lower, $t)) return true;
            }
        }

        return false;
    }
}
