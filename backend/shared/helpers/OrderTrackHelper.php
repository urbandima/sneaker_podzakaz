<?php

namespace app\backend\shared\helpers;

/**
 * Three-track status model for orders.
 *
 * Tracks:
 *   payment  — Оплата:    new → paid → confirmed_and_paid
 *   logistics — Логистика: ordered → awaiting_buyout → awaiting_warehouse → international_delivery → at_warehouse
 *   delivery  — Доставка:  local_delivery → in_transit/shipped → delivered/completed/issued
 *
 * Terminal statuses (all tracks complete or cancelled): delivered, completed, issued, canceled, cancelled, refunded, return
 */
class OrderTrackHelper
{
    const TRACK_PAYMENT   = 'payment';
    const TRACK_LOGISTICS = 'logistics';
    const TRACK_DELIVERY  = 'delivery';

    private static array $tracks = [
        self::TRACK_PAYMENT => [
            'label'    => 'Оплата',
            'icon'     => 'bi-credit-card',
            'statuses' => ['new', 'paid', 'confirmed_and_paid'],
        ],
        self::TRACK_LOGISTICS => [
            'label'    => 'Логистика',
            'icon'     => 'bi-boxes',
            'statuses' => ['ordered', 'awaiting_buyout', 'awaiting_warehouse', 'international_delivery', 'at_warehouse'],
        ],
        self::TRACK_DELIVERY => [
            'label'    => 'Доставка',
            'icon'     => 'bi-truck',
            'statuses' => ['local_delivery', 'shipped', 'in_transit', 'delivered', 'completed', 'issued'],
        ],
    ];

    // SLA hours per status: how many hours before escalation alert
    private static array $slaHours = [
        'new'                   => 4,
        'paid'                  => 24,
        'confirmed_and_paid'    => 48,
        'ordered'               => 168,   // 7 days
        'awaiting_buyout'       => 72,    // 3 days
        'awaiting_warehouse'    => 336,   // 14 days
        'international_delivery' => 336,  // 14 days
        'at_warehouse'          => 72,    // 3 days
        'local_delivery'        => 72,    // 3 days
        'shipped'               => 120,   // 5 days
        'in_transit'            => 120,   // 5 days
    ];

    // Statuses requiring buyout gate before logistics can advance past awaiting_buyout
    const BUYOUT_REQUIRED_AFTER = 'ordered';
    const BUYOUT_COMPLETE_STATUS = 'awaiting_buyout';

    /**
     * Returns the track name for a given status, or null if terminal/unknown.
     */
    public static function trackForStatus(string $status): ?string
    {
        foreach (self::$tracks as $trackKey => $track) {
            if (in_array($status, $track['statuses'], true)) {
                return $trackKey;
            }
        }
        return null;
    }

    /**
     * Returns track progress info for each track given the current status.
     *
     * Each track entry:
     *   active   — this is the current track
     *   done     — this track is already complete
     *   pending  — this track hasn't started yet
     *   step     — index within track statuses (0-based), or count-1 for done
     *   total    — total steps in track
     */
    public static function getTrackProgress(string $currentStatus): array
    {
        $terminal = ['delivered', 'completed', 'issued', 'canceled', 'cancelled', 'refunded', 'return', 'trash'];
        $currentTrack = self::trackForStatus($currentStatus);
        $allTrackKeys = array_keys(self::$tracks);

        $progress = [];
        $reachedCurrent = false;

        foreach (self::$tracks as $trackKey => $track) {
            $step = array_search($currentStatus, $track['statuses'], true);
            if ($step !== false) {
                $reachedCurrent = true;
                $progress[$trackKey] = [
                    'state'  => 'active',
                    'step'   => $step,
                    'total'  => count($track['statuses']),
                    'label'  => $track['label'],
                    'icon'   => $track['icon'],
                ];
            } elseif (!$reachedCurrent) {
                $progress[$trackKey] = [
                    'state'  => 'done',
                    'step'   => count($track['statuses']) - 1,
                    'total'  => count($track['statuses']),
                    'label'  => $track['label'],
                    'icon'   => $track['icon'],
                ];
            } else {
                $progress[$trackKey] = [
                    'state'  => 'pending',
                    'step'   => 0,
                    'total'  => count($track['statuses']),
                    'label'  => $track['label'],
                    'icon'   => $track['icon'],
                ];
            }
        }

        // All terminal statuses: mark all tracks done
        if (in_array($currentStatus, $terminal, true) && !$currentTrack) {
            foreach ($progress as &$t) {
                $t['state'] = 'done';
                $t['step']  = $t['total'] - 1;
            }
        }

        return $progress;
    }

    /**
     * Returns the SLA deadline timestamp for an order, based on its current status and updated_at.
     * Returns null if no SLA is configured for this status.
     */
    public static function getSlaDeadline(string $status, int $updatedAt): ?int
    {
        if (!isset(self::$slaHours[$status])) {
            return null;
        }
        return $updatedAt + self::$slaHours[$status] * 3600;
    }

    /**
     * Returns SLA info:
     *   deadline   — unix timestamp of deadline
     *   overdue    — true if now > deadline
     *   hours_left — hours remaining (negative if overdue)
     */
    public static function getSlaInfo(string $status, int $updatedAt): ?array
    {
        $deadline = self::getSlaDeadline($status, $updatedAt);
        if ($deadline === null) {
            return null;
        }
        $now = time();
        return [
            'deadline'   => $deadline,
            'overdue'    => $now > $deadline,
            'hours_left' => (int)(($deadline - $now) / 3600),
        ];
    }

    /**
     * Returns whether an order needs a buyout step before advancing to the logistics track.
     * Used in admin view to show a warning.
     */
    public static function needsBuyout(string $currentStatus, array $statusHistory): bool
    {
        // If already past ordered status, check if awaiting_buyout appeared
        $logisticsTrack = self::$tracks[self::TRACK_LOGISTICS]['statuses'];
        $orderedIdx = array_search('ordered', $logisticsTrack, true);
        $buyoutIdx  = array_search('awaiting_buyout', $logisticsTrack, true);
        $currentIdx = array_search($currentStatus, $logisticsTrack, true);

        if ($currentIdx === false || $currentIdx <= $orderedIdx) {
            return false;
        }
        // Check if buyout step was ever in history
        return !in_array('awaiting_buyout', $statusHistory, true);
    }

    public static function getAllTracks(): array
    {
        return self::$tracks;
    }

    public static function getSlaHours(): array
    {
        return self::$slaHours;
    }
}
