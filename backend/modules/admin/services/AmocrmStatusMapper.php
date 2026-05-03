<?php

namespace app\backend\modules\admin\services;

use Yii;

/**
 * AmocrmStatusMapper — bidirectional mapping between our Order statuses / tracks and AmoCRM lead statuses.
 *
 * Primary store: amocrm_status_mapping table.
 * Fallback: settings[amocrm][status_id_*] and name-based heuristics.
 */
class AmocrmStatusMapper
{
    // Default name hints for display
    private static array $orderToAmoName = [
        'new'                    => 'Новый',
        'paid'                   => 'Оплачен',
        'confirmed_and_paid'     => 'Подтверждён и оплачен',
        'ordered'                => 'Заказано у поставщика',
        'awaiting_buyout'        => 'Ожидает выкупа',
        'bought_at_source'       => 'Выкуплен на источнике',
        'in_transit'             => 'В пути',
        'at_warehouse'           => 'На складе',
        'ready_to_ship'          => 'Готов к отправке',
        'local_delivery'         => 'В доставке',
        'shipped'                => 'Отправлен',
        'delivered'              => 'Доставлен',
        'cancelled'              => 'Отменён',
        'refunded'               => 'Возврат',
    ];

    private static array $amoNameToOrder = [
        'новый'              => 'new',
        'оплачен'            => 'paid',
        'купили'             => 'paid',
        'выкуплен'           => 'bought_at_source',
        'в пути'             => 'in_transit',
        'на складе'          => 'at_warehouse',
        'готов к отправке'   => 'ready_to_ship',
        'отправлен'          => 'shipped',
        'доставлен'          => 'delivered',
        'выдан'              => 'delivered',
        'отменён'            => 'cancelled',
        'отменен'            => 'cancelled',
        'возврат'            => 'refunded',
    ];

    /**
     * Get AmoCRM [pipeline_id, status_id] for our order/track status.
     * Returns null if no mapping configured.
     */
    public static function toAmocrm(string $ourStatus, string $track = 'order'): ?array
    {
        try {
            $row = Yii::$app->db->createCommand(
                'SELECT amocrm_pipeline_id, amocrm_status_id FROM amocrm_status_mapping
                 WHERE our_status = :s AND our_track = :t AND direction IN (\'to_amocrm\', \'both\')
                 LIMIT 1',
                [':s' => $ourStatus, ':t' => $track]
            )->queryOne();

            if ($row) {
                return [(int)$row['amocrm_pipeline_id'], (int)$row['amocrm_status_id']];
            }
        } catch (\Throwable $e) {
            Yii::warning('[AmocrmStatusMapper] toAmocrm error: ' . $e->getMessage(), 'amocrm');
        }

        // Legacy settings fallback
        $id = (int) Yii::$app->settings->get('amocrm', 'status_id_' . $ourStatus, 0);
        if ($id > 0) {
            $pipelineId = (int) Yii::$app->settings->get('amocrm', 'pipeline_id', 0);
            return $pipelineId ? [$pipelineId, $id] : null;
        }

        return null;
    }

    /** @deprecated Use toAmocrm() */
    public static function toAmocrmStatusId(string $orderStatus): ?int
    {
        $result = self::toAmocrm($orderStatus);
        return $result ? $result[1] : null;
    }

    /**
     * Convert AmoCRM (pipeline_id, status_id) to our order status.
     *
     * If $pipelineId === 0 (legacy callsites that don't know the pipeline), the lookup
     * falls back to status_id only. Otherwise the pipeline filter is enforced — the same
     * status_id may exist in multiple pipelines with different meanings.
     */
    public static function fromAmocrm(int $pipelineId, int $statusId, ?string $statusName = null): ?string
    {
        try {
            if ($pipelineId > 0) {
                $row = Yii::$app->db->createCommand(
                    'SELECT our_status FROM amocrm_status_mapping
                     WHERE amocrm_pipeline_id = :p AND amocrm_status_id = :s AND direction IN (\'from_amocrm\', \'both\')
                     LIMIT 1',
                    [':p' => $pipelineId, ':s' => $statusId]
                )->queryOne();
            } else {
                // Legacy callsite: pipeline unknown, lookup by status_id only.
                $row = Yii::$app->db->createCommand(
                    'SELECT our_status FROM amocrm_status_mapping
                     WHERE amocrm_status_id = :s AND direction IN (\'from_amocrm\', \'both\')
                     LIMIT 1',
                    [':s' => $statusId]
                )->queryOne();
            }
            if ($row) return $row['our_status'];
        } catch (\Throwable $e) {
            Yii::warning('[AmocrmStatusMapper] fromAmocrm error: ' . $e->getMessage(), 'amocrm');
        }

        // Legacy settings fallback
        $all = Yii::$app->settings->getSection('amocrm') ?? [];
        foreach ($all as $key => $val) {
            if (str_starts_with($key, 'status_id_') && (int)$val === $statusId) {
                return substr($key, strlen('status_id_'));
            }
        }

        // Name-based heuristic
        if ($statusName) {
            return self::$amoNameToOrder[mb_strtolower(trim($statusName))] ?? null;
        }

        return null;
    }

    /** @deprecated Use fromAmocrm($pipelineId, $statusId, $name) */
    public static function fromAmocrmStatusId(int $amoStatusId, ?string $amoStatusName = null): ?string
    {
        return self::fromAmocrm(0, $amoStatusId, $amoStatusName);
    }

    public static function toAmocrmName(string $orderStatus): string
    {
        return self::$orderToAmoName[$orderStatus] ?? $orderStatus;
    }

    /**
     * All our statuses available for mapping (order statuses + track values).
     */
    public static function ourStatusList(): array
    {
        return [
            // Order statuses
            'order' => [
                'new', 'paid', 'confirmed_and_paid', 'ordered', 'awaiting_warehouse',
                'international_delivery', 'at_warehouse', 'local_delivery', 'shipped',
                'delivered', 'cancelled', 'refunded',
            ],
            // Payment track
            'payment' => ['not_paid', 'paid', 'refunded'],
            // Logistics track
            'logistics' => ['awaiting_buyout', 'bought_at_source', 'in_transit', 'at_warehouse'],
            // Delivery track
            'delivery' => ['ready_to_ship', 'shipping', 'delivered'],
        ];
    }

    /**
     * All saved mappings from DB.
     */
    public static function getAllMappings(): array
    {
        try {
            return Yii::$app->db->createCommand(
                'SELECT * FROM amocrm_status_mapping ORDER BY our_track, our_status'
            )->queryAll();
        } catch (\Throwable $e) {
            return [];
        }
    }

    /**
     * Replace all mappings atomically.
     *
     * Empty arrays are rejected to prevent accidental table wipe via a malformed POST.
     * Each row is validated before any DELETE happens — invalid input throws and the
     * existing mappings remain untouched.
     *
     * @throws \InvalidArgumentException on validation failure
     * @return int number of rows inserted
     */
    public static function saveMappings(array $mappings): int
    {
        if (empty($mappings)) {
            throw new \InvalidArgumentException('Empty mapping array rejected (use a dedicated clear operation if intentional).');
        }

        $validTracks     = ['order', 'payment', 'logistics', 'delivery'];
        $validDirections = ['to_amocrm', 'from_amocrm', 'both'];
        $allowedStatuses = self::ourStatusList();

        // Pre-validate everything first — fail fast before touching the DB.
        $clean = [];
        foreach ($mappings as $i => $m) {
            $ourStatus  = isset($m['our_status']) ? (string)$m['our_status'] : '';
            $ourTrack   = isset($m['our_track'])  ? (string)$m['our_track']  : 'order';
            $pipelineId = (int)($m['pipeline_id'] ?? 0);
            $statusId   = (int)($m['status_id'] ?? 0);
            $statusName = isset($m['status_name']) ? trim((string)$m['status_name']) : null;
            $direction  = isset($m['direction']) ? (string)$m['direction'] : 'both';

            if (!in_array($ourTrack, $validTracks, true)) {
                throw new \InvalidArgumentException("Row #$i: invalid track '$ourTrack'");
            }
            if (!in_array($direction, $validDirections, true)) {
                throw new \InvalidArgumentException("Row #$i: invalid direction '$direction'");
            }
            if ($ourStatus === '' || !in_array($ourStatus, $allowedStatuses[$ourTrack] ?? [], true)) {
                throw new \InvalidArgumentException("Row #$i: status '$ourStatus' not allowed for track '$ourTrack'");
            }
            if ($pipelineId <= 0 || $statusId <= 0) {
                throw new \InvalidArgumentException("Row #$i: pipeline_id and status_id must be positive integers");
            }
            if (mb_strlen($statusName ?? '') > 255) {
                throw new \InvalidArgumentException("Row #$i: status_name too long (max 255)");
            }

            $clean[] = [
                'our_status'         => $ourStatus,
                'our_track'          => $ourTrack,
                'amocrm_pipeline_id' => $pipelineId,
                'amocrm_status_id'   => $statusId,
                'amocrm_status_name' => $statusName !== '' ? $statusName : null,
                'direction'          => $direction,
            ];
        }

        $db = Yii::$app->db;
        // DELETE (not TRUNCATE) so the wipe + inserts are atomic — TRUNCATE implicitly commits in MySQL.
        $tx = $db->beginTransaction();
        try {
            $db->createCommand()->delete('amocrm_status_mapping')->execute();
            foreach ($clean as $row) {
                $db->createCommand()->insert('amocrm_status_mapping', $row)->execute();
            }
            $tx->commit();
        } catch (\Throwable $e) {
            $tx->rollBack();
            throw $e;
        }

        return count($clean);
    }

    /**
     * Trigger: should a webhook status change create an order?
     */
    public static function shouldCreateOrder(int $statusId, ?string $statusName): bool
    {
        $triggerId = (int) Yii::$app->settings->get('amocrm', 'create_order_status_id', 0);
        if ($triggerId && $statusId === $triggerId) return true;

        if ($statusName) {
            $lower = mb_strtolower(trim($statusName));
            foreach (['купили', 'создать заказ', 'оформить заказ', 'готов к выкупу', 'в работе'] as $t) {
                if (str_contains($lower, $t)) return true;
            }
        }
        return false;
    }
}
