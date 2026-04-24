<?php

namespace app\backend\modules\automation\services;

use Yii;
use yii\base\Component;
use app\backend\modules\automation\models\AutomationTrigger;
use app\backend\modules\automation\models\AutomationLog;
use app\backend\modules\automation\models\SmsTemplate;

/**
 * AutomationEngine — ядро системы триггеров и автоматизации.
 *
 * Использование:
 *   Yii::$app->automation->fireEvent('order.created', ['order' => $order, 'customer' => $customer]);
 */
class AutomationEngine extends Component
{
    /**
     * Запускает все активные триггеры для данного события.
     * Возвращает массив результатов выполнения.
     */
    public function fireEvent(string $eventCode, array $context = []): array
    {
        try {
            $triggers = AutomationTrigger::find()
                ->where(['event_code' => $eventCode, 'is_active' => 1])
                ->orderBy(['priority' => SORT_ASC])
                ->all();
        } catch (\Throwable $e) {
            Yii::error('AutomationEngine: ошибка загрузки триггеров для ' . $eventCode . ': ' . $e->getMessage(), 'automation');
            return [];
        }

        $results = [];
        foreach ($triggers as $trigger) {
            try {
                $startMs = round(microtime(true) * 1000);

                if (!$this->checkConditions($trigger->conditions, $context)) {
                    continue;
                }

                $actionResults = $this->executeActions($trigger->actions, $context);
                $elapsed = round(microtime(true) * 1000) - $startMs;

                // Update counters
                AutomationTrigger::updateAllCounters(['execution_count' => 1], ['id' => $trigger->id]);
                AutomationTrigger::updateAll(
                    ['last_executed_at' => date('Y-m-d H:i:s')],
                    ['id' => $trigger->id]
                );

                // Write log
                $log = new AutomationLog();
                $log->trigger_id        = $trigger->id;
                $log->event_code        = $eventCode;
                $log->order_id          = isset($context['order']) ? ($context['order']->id ?? null) : null;
                $log->customer_id       = isset($context['customer'])
                    ? ($context['customer']->id ?? null)
                    : (isset($context['order']) ? ($context['order']->customer_id ?? null) : null);
                $log->conditions_met    = 1;
                $log->actions_executed  = json_encode($actionResults, JSON_UNESCAPED_UNICODE);
                $log->execution_time_ms = $elapsed;
                $log->save(false);

                $results[] = ['trigger' => $trigger->name, 'actions' => $actionResults];

            } catch (\Throwable $e) {
                Yii::error('AutomationEngine: ошибка выполнения триггера #' . $trigger->id . ': ' . $e->getMessage(), 'automation');
            }
        }

        return $results;
    }

    // ─── Conditions ──────────────────────────────────────────────────────────

    private function checkConditions($conditionsJson, array $context): bool
    {
        $conditions = is_string($conditionsJson)
            ? (json_decode($conditionsJson, true) ?: [])
            : (is_array($conditionsJson) ? $conditionsJson : []);

        if (empty($conditions)) return true;

        foreach ($conditions as $cond) {
            $fieldValue = $this->resolveField($cond['field'] ?? '', $context);
            if (!$this->evaluateCondition($fieldValue, $cond['operator'] ?? 'equals', $cond['value'] ?? null)) {
                return false;
            }
        }
        return true;
    }

    private function resolveField(string $field, array $context): mixed
    {
        // Support dot-notation: "order.status" → $context['order']->status
        $parts = explode('.', $field, 2);
        $root  = $parts[0];
        $attr  = $parts[1] ?? null;

        $obj = $context[$root] ?? null;
        if ($obj === null) return null;

        if ($attr === null) return $obj;

        if (is_object($obj)) {
            return $obj->$attr ?? null;
        }
        if (is_array($obj)) {
            return $obj[$attr] ?? null;
        }
        return null;
    }

    private function evaluateCondition(mixed $value, string $operator, mixed $expected): bool
    {
        switch ($operator) {
            case 'equals':
                return (string)$value === (string)$expected;
            case 'not_equals':
                return (string)$value !== (string)$expected;
            case 'greater_than':
                return is_numeric($value) && (float)$value > (float)$expected;
            case 'less_than':
                return is_numeric($value) && (float)$value < (float)$expected;
            case 'contains':
                return str_contains((string)$value, (string)$expected);
            case 'is_empty':
                return empty($value) || $value === null;
            case 'is_not_empty':
                return !empty($value) && $value !== null;
            case 'in_list':
                $list = is_array($expected) ? $expected : explode(',', (string)$expected);
                return in_array((string)$value, array_map('trim', $list), true);
            default:
                return true;
        }
    }

    // ─── Actions ─────────────────────────────────────────────────────────────

    private function executeActions($actionsJson, array $context): array
    {
        $actions = is_string($actionsJson)
            ? (json_decode($actionsJson, true) ?: [])
            : (is_array($actionsJson) ? $actionsJson : []);

        $results = [];
        foreach ($actions as $action) {
            $results[] = $this->executeAction($action, $context);
        }
        return $results;
    }

    private function executeAction(array $action, array $context): array
    {
        $type = $action['type'] ?? 'unknown';
        try {
            switch ($type) {
                case 'send_sms':      return $this->actionSendSms($action, $context);
                case 'send_telegram': return $this->actionSendTelegram($action, $context);
                case 'sync_moysklad': return $this->actionSyncMoysklad($context);
                case 'change_status': return $this->actionChangeStatus($action, $context);
                case 'earn_bonus':    return $this->actionEarnBonus($action, $context);
                case 'create_customer': return $this->actionCreateCustomer($context);
                case 'send_to_dp':    return $this->actionSendToDP($context);
                case 'notify_admin':  return $this->actionNotifyAdmin($action, $context);
                case 'assign_tag':    return $this->actionAssignTag($action, $context);
                case 'webhook':       return $this->actionWebhook($action, $context);
                default:
                    return ['type' => $type, 'status' => 'unknown_action'];
            }
        } catch (\Throwable $e) {
            Yii::error('AutomationEngine: ошибка действия ' . $type . ': ' . $e->getMessage(), 'automation');
            return ['type' => $type, 'status' => 'error', 'message' => $e->getMessage()];
        }
    }

    // ─── Action implementations ───────────────────────────────────────────────

    private function actionSendSms(array $action, array $context): array
    {
        if (!Yii::$app->has('sms')) {
            return ['type' => 'send_sms', 'status' => 'skip', 'message' => 'SMS компонент не зарегистрирован'];
        }

        $order    = $context['order']    ?? null;
        $customer = $context['customer'] ?? null;

        // Resolve phone
        $to = $action['to'] ?? 'client';
        if ($to === 'client') {
            $phone = $order ? ($order->client_phone ?? null) : ($customer->phone ?? null);
        } elseif ($to === 'admin') {
            $phone = Yii::$app->params['adminPhone'] ?? null;
        } else {
            $phone = $to; // custom phone number
        }

        if (empty($phone)) {
            return ['type' => 'send_sms', 'status' => 'skip', 'message' => 'Телефон не найден'];
        }

        // Build template variables
        $vars = $this->buildTemplateVars($context);

        // Render message
        $templateCode = $action['template'] ?? null;
        if ($templateCode) {
            $tpl = SmsTemplate::findByCode($templateCode);
            $message = $tpl ? $tpl->render($vars) : ($action['message'] ?? 'Уведомление о заказе');
        } else {
            $message = $action['message'] ?? 'Уведомление о заказе';
            foreach ($vars as $k => $v) {
                $message = str_replace('{' . $k . '}', (string)$v, $message);
            }
        }

        $ok = Yii::$app->sms->send($phone, $message);

        return [
            'type'    => 'send_sms',
            'status'  => $ok ? 'success' : 'error',
            'phone'   => $phone,
            'message' => mb_substr($message, 0, 80),
        ];
    }

    private function actionChangeStatus(array $action, array $context): array
    {
        $order = $context['order'] ?? null;
        if (!$order) {
            return ['type' => 'change_status', 'status' => 'skip', 'message' => 'Заказ не найден в контексте'];
        }

        $newStatus  = $action['value'] ?? $action['new_status'] ?? null;
        if (!$newStatus) {
            return ['type' => 'change_status', 'status' => 'skip', 'message' => 'Новый статус не указан'];
        }

        $oldStatus    = $order->status;
        $order->status = $newStatus;
        $order->save(false);

        return ['type' => 'change_status', 'status' => 'success', 'old' => $oldStatus, 'new' => $newStatus];
    }

    private function actionEarnBonus(array $action, array $context): array
    {
        $order    = $context['order']    ?? null;
        $customer = $context['customer'] ?? null;

        $customerId = $customer->id ?? $order->customer_id ?? null;
        if (!$customerId) {
            return ['type' => 'earn_bonus', 'status' => 'skip', 'message' => 'Клиент не найден'];
        }

        $points      = (int)($action['points'] ?? 0);
        $description = $action['description'] ?? 'Автоматическое начисление бонусов';

        if ($points <= 0) {
            return ['type' => 'earn_bonus', 'status' => 'skip', 'message' => 'Количество баллов не указано'];
        }

        try {
            $loyaltyService = new \app\backend\modules\loyalty\services\LoyaltyService();
            $loyaltyService->earnPoints($customerId, $points, $description, $order->id ?? null);
            return ['type' => 'earn_bonus', 'status' => 'success', 'points' => $points, 'customer_id' => $customerId];
        } catch (\Throwable $e) {
            return ['type' => 'earn_bonus', 'status' => 'error', 'message' => $e->getMessage()];
        }
    }

    private function actionCreateCustomer(array $context): array
    {
        $order = $context['order'] ?? null;
        if (!$order || $order->customer_id) {
            return ['type' => 'create_customer', 'status' => 'skip', 'message' => 'Клиент уже существует'];
        }

        try {
            $CustomerClass = \app\backend\modules\account\models\Customer::class;
            $customer = null;

            if (!empty($order->client_email)) {
                $customer = $CustomerClass::find()->where(['email' => $order->client_email])->one();
            }
            if (!$customer && !empty($order->client_phone)) {
                $customer = $CustomerClass::find()->where(['phone' => $order->client_phone])->one();
            }

            if (!$customer) {
                $customer = new $CustomerClass();
                $customer->email      = $order->client_email ?: null;
                $customer->phone      = $order->client_phone ?: null;
                $customer->first_name = $order->client_name  ?: null;
                $customer->created_at = time();
                $customer->updated_at = time();
                $customer->save(false);
            }

            $order->customer_id = $customer->id;
            $order->save(false);

            return ['type' => 'create_customer', 'status' => 'success', 'customer_id' => $customer->id];
        } catch (\Throwable $e) {
            return ['type' => 'create_customer', 'status' => 'error', 'message' => $e->getMessage()];
        }
    }

    private function actionSendToDP(array $context): array
    {
        $order = $context['order'] ?? null;
        if (!$order) {
            return ['type' => 'send_to_dp', 'status' => 'skip', 'message' => 'Заказ не найден'];
        }

        try {
            $response = Yii::$app->dobropost->createShipment($order);
            return ['type' => 'send_to_dp', 'status' => 'success', 'dp_id' => $response['id'] ?? null];
        } catch (\Throwable $e) {
            return ['type' => 'send_to_dp', 'status' => 'error', 'message' => $e->getMessage()];
        }
    }

    private function actionNotifyAdmin(array $action, array $context): array
    {
        $vars    = $this->buildTemplateVars($context);
        $message = $action['message'] ?? 'Автоматическое уведомление';
        foreach ($vars as $k => $v) {
            $message = str_replace('{' . $k . '}', (string)$v, $message);
        }

        Yii::info('[Automation notify_admin] ' . $message, 'automation');
        // Could write to DB notification table in the future
        return ['type' => 'notify_admin', 'status' => 'success', 'message' => mb_substr($message, 0, 200)];
    }

    private function actionAssignTag(array $action, array $context): array
    {
        $order    = $context['order']    ?? null;
        $customer = $context['customer'] ?? null;
        $customerId = $customer->id ?? $order->customer_id ?? null;

        if (!$customerId) {
            return ['type' => 'assign_tag', 'status' => 'skip', 'message' => 'Клиент не найден'];
        }

        $tag = $action['tag_name'] ?? $action['tag'] ?? null;
        if (!$tag) {
            return ['type' => 'assign_tag', 'status' => 'skip', 'message' => 'Тег не указан'];
        }

        // Store tag in customer notes or tags JSON field if available
        try {
            $CustomerClass = \app\backend\modules\account\models\Customer::class;
            $c = $CustomerClass::findOne($customerId);
            if ($c && $c->hasAttribute('tags')) {
                $tags   = json_decode($c->tags ?? '[]', true) ?: [];
                if (!in_array($tag, $tags, true)) {
                    $tags[] = $tag;
                    $c->tags = json_encode($tags, JSON_UNESCAPED_UNICODE);
                    $c->save(false);
                }
            }
            return ['type' => 'assign_tag', 'status' => 'success', 'tag' => $tag, 'customer_id' => $customerId];
        } catch (\Throwable $e) {
            return ['type' => 'assign_tag', 'status' => 'error', 'message' => $e->getMessage()];
        }
    }

    private function actionWebhook(array $action, array $context): array
    {
        $url    = $action['url']    ?? null;
        $method = strtoupper($action['method'] ?? 'POST');
        $body   = $action['body']   ?? '';

        if (!$url) {
            return ['type' => 'webhook', 'status' => 'skip', 'message' => 'URL не указан'];
        }

        // Replace template vars in body
        $vars = $this->buildTemplateVars($context);
        foreach ($vars as $k => $v) {
            $body = str_replace('{' . $k . '}', (string)$v, $body);
        }

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 10,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_CUSTOMREQUEST  => $method,
            CURLOPT_POSTFIELDS     => $body,
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
        ]);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return [
            'type'      => 'webhook',
            'status'    => ($httpCode >= 200 && $httpCode < 300) ? 'success' : 'error',
            'http_code' => $httpCode,
            'url'       => $url,
        ];
    }

    private function actionSyncMoysklad(array $context): array
    {
        $order = $context['order'] ?? null;
        if (!$order) {
            return ['type' => 'sync_moysklad', 'status' => 'skip', 'message' => 'Заказ не найден в контексте'];
        }
        if (!Yii::$app->has('moysklad')) {
            return ['type' => 'sync_moysklad', 'status' => 'skip', 'message' => 'МойСклад не зарегистрирован'];
        }
        try {
            Yii::$app->moysklad->pushOrder($order);
            return ['type' => 'sync_moysklad', 'status' => 'success', 'order_id' => $order->id];
        } catch (\Throwable $e) {
            return ['type' => 'sync_moysklad', 'status' => 'error', 'message' => $e->getMessage()];
        }
    }

    private function actionSendTelegram(array $action, array $context): array
    {
        $token = Yii::$app->settings->get('telegram', 'bot_token', '');
        if (empty($token)) {
            return ['type' => 'send_telegram', 'status' => 'skip', 'message' => 'Telegram bot_token не настроен'];
        }

        $vars    = $this->buildTemplateVars($context);
        $message = $action['message'] ?? 'Автоматическое уведомление';
        foreach ($vars as $k => $v) {
            $message = str_replace('{' . $k . '}', (string)$v, $message);
        }

        $ok = \app\backend\shared\services\TelegramService::send($message);
        return ['type' => 'send_telegram', 'status' => $ok ? 'success' : 'error'];
    }

    // ─── Helpers ─────────────────────────────────────────────────────────────

    private function buildTemplateVars(array $context): array
    {
        $vars = [];
        $order    = $context['order']    ?? null;
        $customer = $context['customer'] ?? null;

        if ($order) {
            $vars['order_number']   = $order->order_number  ?? $order->id ?? '';
            $vars['total']          = number_format((float)($order->total_amount ?? 0), 2);
            $vars['client_name']    = $order->client_name   ?? '';
            $vars['client_phone']   = $order->client_phone  ?? '';
            $vars['status']         = $order->status        ?? '';
            $vars['new_status']     = $context['new_status'] ?? $order->status ?? '';
            $vars['old_status']     = $context['old_status'] ?? '';
            $vars['dp_track']       = $order->dp_track_number ?? '';
            $vars['dp_status']      = $order->dp_status       ?? '';
            $vars['passport_link']  = 'https://sneaker-head.by/account/orders/' . ($order->token ?? $order->id ?? '');
            $vars['order_id']       = $order->id ?? '';
        }

        if ($customer) {
            $vars['customer_name']  = trim(($customer->first_name ?? '') . ' ' . ($customer->last_name ?? ''));
            $vars['customer_phone'] = $customer->phone ?? '';
            $vars['customer_email'] = $customer->email ?? '';
            $vars['bonus_balance']  = $customer->bonus_balance ?? 0;
        }

        $vars['points'] = $context['points'] ?? 0;

        return $vars;
    }
}
