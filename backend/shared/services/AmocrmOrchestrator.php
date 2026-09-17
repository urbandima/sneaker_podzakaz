<?php

namespace app\backend\shared\services;

use Yii;
use app\backend\shared\components\AmocrmClient;
use app\backend\modules\admin\services\AmocrmStatusMapper;
use app\backend\modules\admin\services\OrderFromLeadService;
use app\backend\modules\checkout\models\Order;
use app\backend\modules\checkout\models\OrderHistory;

/**
 * AmocrmOrchestrator — единая точка входа для AmoCRM-логики заказа (CMP-371, п.2.1).
 *
 * Сейчас та же логика размазана по трём местам: PluginController::syncOrderToAmo
 * (создание сделки), WebhookController::handleLeadStatus (входящий статус) и нигде
 * — push нашего статуса в AmoCRM. Этот класс собирает все три операции в одном
 * месте; AmocrmClient остаётся чистым HTTP-транспортом, AmocrmStatusMapper — чистым
 * мэппером статусов, ни один из их публичных контрактов не меняется.
 *
 * "Вычислительная" часть (buildLeadPayload/createDeal/pushStatus/resolveIncomingLeadStatus)
 * не трогает ActiveRecord и полностью покрыта unit-тестами через FakeAmocrmClient.
 * Тонкие *ForOrder-обёртки читают/пишут Order — они переезжают на реальные call sites
 * (PluginController, WebhookController) отдельным issue (CMP-386) за тем же флагом.
 *
 * Включается флагом AMOCRM_USE_ORCHESTRATOR=1 (env, фолбэк — settings[amocrm][use_orchestrator]),
 * по умолчанию выключен — старое поведение в контроллерах сохраняется до явного переключения.
 */
class AmocrmOrchestrator
{
    private AmocrmClient $client;
    private OrderFromLeadService $leadService;

    public function __construct(?AmocrmClient $client = null, ?OrderFromLeadService $leadService = null)
    {
        $this->client      = $client ?? Yii::$app->amocrm;
        $this->leadService = $leadService ?? new OrderFromLeadService();
    }

    public static function enabled(): bool
    {
        if (filter_var(env('AMOCRM_USE_ORCHESTRATOR', false), FILTER_VALIDATE_BOOLEAN)) {
            return true;
        }
        try {
            return filter_var(Yii::$app->settings->get('amocrm', 'use_orchestrator', '0'), FILTER_VALIDATE_BOOLEAN);
        } catch (\Throwable $e) {
            return false;
        }
    }

    // ── Pure / wire-level (unit-tested) ──────────────────────────────────

    /**
     * Build the AmoCRM lead payload for an order snapshot.
     * @param array{id:int,recipient_first_name?:?string,recipient_last_name?:?string,total_amount?:mixed,
     *     pipeline_id?:int,status_id?:int,responsible_user_id?:int} $order
     */
    public function buildLeadPayload(array $order): array
    {
        $recipientName = trim(($order['recipient_first_name'] ?? '') . ' ' . ($order['recipient_last_name'] ?? ''));
        $data = [
            'name'  => 'Заказ #' . $order['id'] . ($recipientName !== '' ? ' — ' . $recipientName : ''),
            'price' => (int)($order['total_amount'] ?? 0),
        ];
        if (!empty($order['pipeline_id'])) {
            $data['pipeline_id'] = (int)$order['pipeline_id'];
        }
        if (!empty($order['status_id'])) {
            $data['status_id'] = (int)$order['status_id'];
        }
        if (!empty($order['responsible_user_id'])) {
            $data['responsible_user_id'] = (int)$order['responsible_user_id'];
        }
        return $data;
    }

    /**
     * Create a lead (+ contact if missing, + note) in AmoCRM from an order snapshot.
     * Returns the AmoCRM lead id, or null if AmoCRM is not configured or the API call failed.
     */
    public function createDeal(array $orderSnapshot): ?int
    {
        if (!$this->client->isConfigured()) {
            return null;
        }

        $lead = $this->client->createLead($this->buildLeadPayload($orderSnapshot));
        if (!$lead || empty($lead['id'])) {
            return null;
        }

        $phone = $orderSnapshot['client_phone'] ?? '';
        if ($phone !== '' && !$this->client->findContactByPhone($phone)) {
            $recipientName = trim(
                ($orderSnapshot['recipient_first_name'] ?? '') . ' ' . ($orderSnapshot['recipient_last_name'] ?? '')
            );
            $this->client->createContact([
                'name'                 => $recipientName !== '' ? $recipientName : 'Покупатель',
                'custom_fields_values' => [[
                    'field_code' => 'PHONE',
                    'values'     => [['value' => $phone]],
                ]],
            ]);
        }

        $this->client->addNote(
            (int)$lead['id'],
            'Заказ из магазина. Сумма: ' . ($orderSnapshot['total_amount'] ?? 0)
                . ' BYN. Статус: ' . ($orderSnapshot['status'] ?? '')
        );

        return (int)$lead['id'];
    }

    /**
     * Push our order status to the AmoCRM lead. Returns false (no-op) when no mapping
     * is configured for $ourStatus — the caller keeps its previous state either way.
     */
    public function pushStatus(int $leadId, string $ourStatus): bool
    {
        $mapped = AmocrmStatusMapper::toAmocrm($ourStatus);
        if (!$mapped) {
            return false;
        }
        [$pipelineId, $statusId] = $mapped;

        $result = $this->client->updateLead($leadId, [
            'pipeline_id' => $pipelineId,
            'status_id'   => $statusId,
        ]);

        return (bool)$result;
    }

    /**
     * Decide what to do with an incoming AmoCRM lead-status webhook event, given
     * whether a local order for this lead already exists. Pure decision — no I/O.
     *
     * @return array{action:string,newStatus:?string} action ∈ {'create','update','ignore'}
     */
    public function resolveIncomingLeadStatus(
        bool $orderExists,
        ?string $currentOrderStatus,
        int $statusId,
        int $pipelineId,
        ?string $statusName
    ): array {
        if (!$orderExists) {
            return AmocrmStatusMapper::shouldCreateOrder($statusId, $statusName)
                ? ['action' => 'create', 'newStatus' => null]
                : ['action' => 'ignore', 'newStatus' => null];
        }

        $newStatus = AmocrmStatusMapper::fromAmocrm($pipelineId, $statusId, $statusName);
        if ($newStatus && $newStatus !== $currentOrderStatus) {
            return ['action' => 'update', 'newStatus' => $newStatus];
        }

        return ['action' => 'ignore', 'newStatus' => null];
    }

    // ── Order-coupled adapters (wired into call sites in CMP-386) ───────

    public function createDealForOrder(Order $order): ?int
    {
        $s = Yii::$app->settings;
        $leadId = $this->createDeal([
            'id'                    => $order->id,
            'recipient_first_name'  => $order->recipient_first_name,
            'recipient_last_name'   => $order->recipient_last_name,
            'total_amount'          => $order->total_amount,
            'client_phone'          => $order->client_phone,
            'status'                => $order->status,
            'pipeline_id'           => (int)$s->get('amocrm', 'pipeline_id', 0),
            'status_id'             => (int)$s->get('amocrm', 'new_order_status_id', 0),
            'responsible_user_id'   => (int)$s->get('amocrm', 'responsible_user_id', 0),
        ]);

        if ($leadId) {
            $order->amocrm_lead_id      = $leadId;
            $order->amocrm_last_sync_at = time();
            $order->save(false);
        }

        return $leadId;
    }

    public function pushOrderStatus(Order $order): bool
    {
        if (!$order->amocrm_lead_id) {
            return false;
        }

        $ok = $this->pushStatus((int)$order->amocrm_lead_id, (string)$order->status);
        if ($ok) {
            $order->amocrm_last_sync_at = time();
            $order->save(false);
        }
        return $ok;
    }

    public function handleIncomingLeadStatus(int $leadId, int $statusId, int $pipelineId, ?string $statusName): ?Order
    {
        if (!$leadId) {
            return null;
        }

        $order    = Order::findOne(['amocrm_lead_id' => $leadId]);
        $decision = $this->resolveIncomingLeadStatus(
            $order !== null,
            $order->status ?? null,
            $statusId,
            $pipelineId,
            $statusName
        );

        if ($decision['action'] === 'create') {
            return $this->leadService->createFromLeadId($leadId);
        }

        if ($decision['action'] === 'update' && $order) {
            $oldStatus = $order->status;
            $order->status = $decision['newStatus'];
            $order->amocrm_last_sync_at = time();
            $order->save(false);
            OrderHistory::log(
                $order->id,
                'status_changed',
                null,
                $oldStatus,
                $decision['newStatus'],
                'AmoCRM webhook (orchestrator): pipeline=' . $pipelineId . ' status_id=' . $statusId
            );
        }

        return $order;
    }
}
