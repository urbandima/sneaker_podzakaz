<?php

namespace app\console\controllers;

use Yii;
use yii\console\Controller;
use yii\console\ExitCode;
use app\backend\modules\checkout\models\Order;
use app\backend\modules\admin\services\OrderFromLeadService;

/**
 * AmoCRM CLI utilities
 *
 * php yii amocrm/sync-lead <lead_id>    — create/update order from AMO lead
 * php yii amocrm/check-lead <lead_id>   — dump lead data (no order changes)
 * php yii amocrm/list-mapped [limit]    — list orders with amocrm_lead_id
 */
class AmocrmController extends Controller
{
    /**
     * Create or update a shop order from an AmoCRM lead.
     *
     * @param int $leadId  AmoCRM lead ID
     */
    public function actionSyncLead(int $leadId): int
    {
        if ($leadId <= 0) {
            $this->stderr("Usage: php yii amocrm/sync-lead <lead_id>\n");
            return ExitCode::USAGE;
        }

        $this->stdout("Syncing AmoCRM lead #$leadId...\n");

        try {
            $service  = new OrderFromLeadService();
            $existing = Order::findOne(['amocrm_lead_id' => $leadId]);

            if ($existing) {
                $this->stdout("Found existing order #{$existing->id} ({$existing->order_number}). Updating...\n");
                $lead = Yii::$app->amocrm->getLead($leadId, ['contacts', 'custom_fields_values']);
                if (!$lead) {
                    $this->stderr("Could not fetch lead #$leadId from AmoCRM\n");
                    return ExitCode::UNSPECIFIED_ERROR;
                }
                $service->updateOrderFromLead($existing, $lead);
                $existing->amocrm_last_sync_at = time();
                $existing->save(false);
                $this->stdout("Order #{$existing->id} updated.\n");
            } else {
                $order = $service->createFromLeadId($leadId);
                $this->stdout("Order #{$order->id} ({$order->order_number}) created from lead #$leadId.\n");
                $this->stdout("  Client : {$order->client_name}\n");
                $this->stdout("  Phone  : {$order->client_phone}\n");
                $this->stdout("  Total  : {$order->total_amount}\n");
                $this->stdout("  Status : {$order->status}\n");
            }

        } catch (\Throwable $e) {
            $this->stderr("ERROR: " . $e->getMessage() . "\n");
            $this->stderr($e->getTraceAsString() . "\n");
            return ExitCode::UNSPECIFIED_ERROR;
        }

        return ExitCode::OK;
    }

    /**
     * Dump AmoCRM lead data without making any changes.
     *
     * @param int $leadId  AmoCRM lead ID
     */
    public function actionCheckLead(int $leadId): int
    {
        if ($leadId <= 0) {
            $this->stderr("Usage: php yii amocrm/check-lead <lead_id>\n");
            return ExitCode::USAGE;
        }

        try {
            $lead = Yii::$app->amocrm->getLead($leadId, ['contacts', 'custom_fields_values']);
            if (!$lead) {
                $this->stderr("Lead #$leadId not found in AmoCRM\n");
                return ExitCode::UNSPECIFIED_ERROR;
            }
            $this->stdout(json_encode($lead, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n");

            $order = Order::findOne(['amocrm_lead_id' => $leadId]);
            if ($order) {
                $this->stdout("\nLinked order: #{$order->id} ({$order->order_number}), status={$order->status}\n");
            } else {
                $this->stdout("\nNo linked order found.\n");
            }
        } catch (\Throwable $e) {
            $this->stderr("ERROR: " . $e->getMessage() . "\n");
            return ExitCode::UNSPECIFIED_ERROR;
        }

        return ExitCode::OK;
    }

    /**
     * List orders that have an AmoCRM lead linked.
     *
     * @param int $limit  Max rows to display (default 20)
     */
    public function actionListMapped(int $limit = 20): int
    {
        $orders = Order::find()
            ->where(['not', ['amocrm_lead_id' => null]])
            ->orderBy(['created_at' => SORT_DESC])
            ->limit($limit)
            ->all();

        if (empty($orders)) {
            $this->stdout("No orders linked to AmoCRM leads.\n");
            return ExitCode::OK;
        }

        $this->stdout(sprintf("%-8s %-15s %-20s %-12s %s\n", 'ID', 'Order#', 'AMO lead_id', 'Status', 'Client'));
        $this->stdout(str_repeat('-', 75) . "\n");
        foreach ($orders as $o) {
            $this->stdout(sprintf(
                "%-8s %-15s %-20s %-12s %s\n",
                $o->id,
                $o->order_number,
                $o->amocrm_lead_id,
                $o->status,
                $o->client_name
            ));
        }
        $this->stdout("\nTotal: " . count($orders) . " order(s)\n");

        return ExitCode::OK;
    }
}
