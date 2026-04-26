<?php

namespace app\backend\modules\procurement\services;

use Yii;
use app\backend\modules\procurement\models\Receiving;
use app\backend\modules\procurement\models\ReceivingHistory;
use app\backend\modules\procurement\models\ReceivingItem;
use app\backend\modules\procurement\models\Buyout;
use app\backend\modules\checkout\models\Order;
use app\backend\modules\catalog\models\ProductSize;

class ReceivingService
{
    /**
     * Create a Receiving record from an accepted Buyout.
     */
    public static function createFromBuyout(Buyout $buyout): Receiving
    {
        $receiving = new Receiving();
        $receiving->number      = Receiving::generateNumber();
        $receiving->buyout_id   = $buyout->id;
        $receiving->supplier_id = null;
        $receiving->status      = Receiving::STATUS_ARRIVED;
        $receiving->arrived_date = date('Y-m-d H:i:s');
        $receiving->expected_date = $buyout->arrived_at ?? date('Y-m-d H:i:s');

        if (!$receiving->save()) {
            throw new \RuntimeException('Cannot create receiving: ' . json_encode($receiving->errors));
        }

        // Add history entry
        $h = new ReceivingHistory();
        $h->receiving_id = $receiving->id;
        $h->from_status  = null;
        $h->to_status    = Receiving::STATUS_ARRIVED;
        $h->comment      = "Создана автоматически из выкупа #{$buyout->number}";
        $h->changed_by   = Yii::$app->user->id ?? null;
        $h->changed_at   = time();
        $h->save(false);

        // Add items from buyout
        if ($buyout->product_id) {
            $item = new ReceivingItem();
            $item->receiving_id     = $receiving->id;
            $item->product_id       = $buyout->product_id;
            $item->size_id          = null; // Buyout stores size as string, resolve if needed
            $item->qty_expected     = $buyout->qty ?? 1;
            $item->unit_cost_source = $buyout->unit_cost_source ?? 0;
            $item->source_currency  = $buyout->source_currency ?? 'CNY';
            $item->exchange_rate    = $buyout->exchange_rate ?? 1;
            $item->unit_cost_byn    = $buyout->unit_cost_byn ?? 0;
            $item->save(false);
        }

        $receiving->recalculateTotals();
        $receiving->save(false);

        return $receiving;
    }

    /**
     * Accept a receiving: update stock, close related orders.
     */
    public static function accept(Receiving $receiving): void
    {
        $items = $receiving->items;

        // Check if any items have partial arrival
        $isPartial = false;
        foreach ($items as $item) {
            if ($item->qty_arrived < $item->qty_expected) {
                $isPartial = true;
                break;
            }
        }

        $newStatus = $isPartial ? Receiving::STATUS_PARTIAL : Receiving::STATUS_ACCEPTED;

        // Update stock
        foreach ($items as $item) {
            $actualQty = $item->getActualQty();
            if ($actualQty <= 0) {
                continue;
            }

            if ($item->size_id) {
                ProductSize::updateAllCounters(['stock' => $actualQty], ['id' => $item->size_id]);
            } else {
                // No size variant — update product.stock_count directly
                \app\backend\modules\catalog\models\Product::updateAllCounters(
                    ['stock_count' => $actualQty],
                    ['id' => $item->product_id]
                );
            }
        }

        // Transition status
        $receiving->transitionTo($newStatus, 'Приёмка завершена');

        // Update related orders via buyout link
        if ($receiving->buyout_id) {
            static::updateRelatedOrders($receiving);
        }
    }

    private static function updateRelatedOrders(Receiving $receiving): void
    {
        $buyoutId = $receiving->buyout_id;
        if (!$buyoutId) {
            return;
        }

        $orderIds = \app\backend\modules\procurement\models\BuyoutOrderLink::find()
            ->select('order_id')
            ->where(['buyout_id' => $buyoutId])
            ->column();

        foreach ($orderIds as $orderId) {
            $order = Order::findOne($orderId);
            if ($order && !in_array($order->status, ['completed', 'cancelled', 'refunded'])) {
                $order->status = 'ready_to_ship';
                $order->save(false);
            }
        }
    }
}
