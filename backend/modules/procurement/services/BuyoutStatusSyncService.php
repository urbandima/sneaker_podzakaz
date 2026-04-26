<?php

namespace app\backend\modules\procurement\services;

use Yii;
use app\backend\modules\procurement\models\Buyout;
use app\backend\modules\procurement\models\BuyoutOrderLink;
use app\backend\modules\checkout\models\Order;

/**
 * Syncs linked Order statuses when a Buyout changes status.
 *
 * Mapping (buyout → order):
 *   ordered    → bought_at_source
 *   in_transit → in_transit_from_source
 *   arrived    → arrived_at_warehouse
 *   accepted   → ready_to_ship
 *   cancelled  → awaiting_buyout
 */
class BuyoutStatusSyncService
{
    public function syncOnStatusChange(Buyout $buyout, string $oldStatus, string $newStatus): void
    {
        if (!array_key_exists($newStatus, Buyout::ORDER_STATUS_MAP)) {
            return;
        }

        $targetOrderStatus = Buyout::ORDER_STATUS_MAP[$newStatus];

        $orderIds = BuyoutOrderLink::find()
            ->select('order_id')
            ->where(['buyout_id' => $buyout->id])
            ->column();

        if (empty($orderIds)) {
            return;
        }

        foreach ($orderIds as $orderId) {
            $order = Order::findOne($orderId);
            if (!$order) {
                continue;
            }

            $order->status = $targetOrderStatus;

            if ($order->save(false)) {
                // Write order history if model supports it
                if (method_exists($order, 'logStatusChange')) {
                    $order->logStatusChange($oldStatus, $targetOrderStatus, 'buyout_sync');
                }

                Yii::info(
                    "BuyoutStatusSync: buyout #{$buyout->id} → order #{$orderId} status set to {$targetOrderStatus}",
                    'buyout.sync'
                );
            } else {
                Yii::warning(
                    "BuyoutStatusSync: failed to update order #{$orderId}: " . json_encode($order->errors),
                    'buyout.sync'
                );
            }
        }
    }

    /**
     * Manually trigger sync for all linked orders of a buyout.
     */
    public function forceSync(Buyout $buyout): int
    {
        $targetOrderStatus = Buyout::ORDER_STATUS_MAP[$buyout->status] ?? null;
        if (!$targetOrderStatus) {
            return 0;
        }

        $orderIds = BuyoutOrderLink::find()
            ->select('order_id')
            ->where(['buyout_id' => $buyout->id])
            ->column();

        $updated = 0;
        foreach ($orderIds as $orderId) {
            $order = Order::findOne($orderId);
            if ($order && $order->status !== $targetOrderStatus) {
                $order->status = $targetOrderStatus;
                if ($order->save(false)) {
                    $updated++;
                }
            }
        }
        return $updated;
    }
}
