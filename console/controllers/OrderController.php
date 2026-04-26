<?php

namespace app\console\controllers;

use Yii;
use yii\console\Controller;
use yii\console\ExitCode;
use app\backend\modules\checkout\models\Order;

/**
 * Order management console commands.
 */
class OrderController extends Controller
{
    /**
     * Backfill three-track status fields (payment_status, logistics_status, delivery_status)
     * from the legacy `status` column for rows that still have NULL tracks.
     *
     * Usage: php yii order/migrate-statuses-to-tracks
     */
    public function actionMigrateStatusesToTracks(): int
    {
        $query = Order::find()
            ->where(['OR',
                ['payment_status'  => null],
                ['logistics_status' => null],
            ]);

        $total   = $query->count();
        $updated = 0;
        $errors  = 0;

        if ($total === 0) {
            $this->stdout("All orders already have track statuses. Nothing to do.\n");
            return ExitCode::OK;
        }

        $this->stdout("Found {$total} orders missing track statuses. Backfilling…\n");

        foreach ($query->each(200) as $order) {
            /** @var Order $order */
            $order->syncTracksFromStatus();
            if ($order->save(false)) {
                $updated++;
            } else {
                $errors++;
                Yii::warning(
                    'Failed to save tracks for order #' . $order->id . ': ' . json_encode($order->errors),
                    'console/order'
                );
            }

            if (($updated + $errors) % 500 === 0) {
                $this->stdout("  processed " . ($updated + $errors) . " / {$total}…\n");
            }
        }

        $this->stdout("Done. Updated: {$updated}, errors: {$errors}.\n");

        return $errors > 0 ? ExitCode::UNSPECIFIED_ERROR : ExitCode::OK;
    }
}
