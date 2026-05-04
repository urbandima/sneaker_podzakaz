<?php

namespace app\backend\modules\admin\services;

use Yii;

/**
 * AiChatService — Phase 3 of CMP-159.
 * Handles incoming AmoCRM chat webhooks, calls Claude Sonnet 4.6,
 * executes tools (search_catalog, get_order_status, escalate_to_manager),
 * and logs the AI response as an AmoCRM note.
 *
 * Claude API docs: https://docs.anthropic.com/en/api/messages
 * Model: claude-sonnet-4-6, max_tokens: 300, temperature: 0.3
 */
class AiChatService
{
    private const CLAUDE_API  = 'https://api.anthropic.com/v1/messages';
    private const MODEL       = 'claude-sonnet-4-6';
    private const MAX_TOKENS  = 300;
    private const TEMPERATURE = 0.3;
    private const MAX_TOOL_LOOPS = 3;

    private const SYSTEM_PROMPT = <<<'PROMPT'
Ты менеджер интернет-магазина оригинальной обуви СНІКЕРХЭД (sneaker-head.by).

ЗАДАЧА: Помочь клиенту получить нужный товар или информацию.

СТИЛЬ: Дружелюбный, краткий, на «вы». Не более 3–4 предложений. Без лишних слов.

ЗАПРЕЩЕНО:
- Обещать то, что не можешь проверить
- Обсуждать скидки (это к менеджеру)
- Утверждать о наличии без проверки в каталоге
- Давать советы по политике или праву

ИНСТРУМЕНТЫ (всегда используй перед ответом):
- search_catalog: проверить наличие и цену товара
- get_order_status: узнать статус заказа клиента
- escalate_to_manager: передать живому менеджеру

ЕСЛИ НЕ ЗНАЕШЬ — передай менеджеру, не выдумывай.
PROMPT;

    private static array $toolDefs = [
        [
            'name'        => 'search_catalog',
            'description' => 'Поиск товара в каталоге МойСклад. Возвращает наличие и цену. Используй перед ответом на вопросы о товаре.',
            'input_schema' => [
                'type'       => 'object',
                'properties' => [
                    'query' => ['type' => 'string', 'description' => 'Название товара или артикул'],
                ],
                'required' => ['query'],
            ],
        ],
        [
            'name'        => 'get_order_status',
            'description' => 'Получить статус заказа клиента по номеру телефона из AmoCRM.',
            'input_schema' => [
                'type'       => 'object',
                'properties' => [
                    'phone' => ['type' => 'string', 'description' => 'Номер телефона клиента'],
                ],
                'required' => ['phone'],
            ],
        ],
        [
            'name'        => 'escalate_to_manager',
            'description' => 'Передать разговор живому менеджеру. Используй при сложных вопросах, жалобах, возвратах, или если инструменты не дали ответа.',
            'input_schema' => [
                'type'       => 'object',
                'properties' => [
                    'reason' => ['type' => 'string', 'description' => 'Причина передачи менеджеру (кратко)'],
                ],
                'required' => ['reason'],
            ],
        ],
    ];

    /**
     * Process a webhook event payload from AmoCRM.
     * Returns ['success' => bool, 'response_text' => string, 'lead_id' => int|null, 'escalated' => bool].
     */
    public function handleWebhook(array $payload): array
    {
        $t0 = microtime(true);

        [$leadId, $messageText, $clientPhone] = $this->extractFromPayload($payload);

        if (!$messageText) {
            Yii::warning('[AiChat] handleWebhook: no message text extracted', 'ai_chat');
            return ['success' => false, 'response_text' => '', 'lead_id' => $leadId, 'escalated' => false];
        }

        Yii::info(sprintf('[AiChat] lead=%s phone=%s msg=%s', $leadId, $clientPhone, mb_substr($messageText, 0, 80)), 'ai_chat');

        try {
            [$responseText, $escalated] = $this->callClaude($messageText, $clientPhone, $leadId);
        } catch (\Throwable $e) {
            Yii::error('[AiChat] Claude error: ' . $e->getMessage(), 'ai_chat');
            $responseText = 'Передаю вашу заявку менеджеру. Он свяжется в ближайшее время.';
            $escalated    = true;
        }

        // Log response as AmoCRM note
        if ($leadId) {
            $this->logToAmocrm($leadId, $messageText, $responseText);
        }

        // If escalated, update lead status to "2" (менеджер должен связаться)
        if ($escalated && $leadId) {
            $this->escalateLeadStatus($leadId);
        }

        $ms = (int)((microtime(true) - $t0) * 1000);
        Yii::info(sprintf('[AiChat] done lead=%s ms=%d escalated=%s', $leadId, $ms, $escalated ? 'yes' : 'no'), 'ai_chat');

        // Phase 4: write structured monitoring log to DB
        $this->writeDbLog([
            'lead_id'       => $leadId,
            'pipeline_id'   => $this->extractPipelineId($payload),
            'client_phone'  => $clientPhone,
            'message_text'  => $messageText,
            'response_text' => $responseText,
            'escalated'     => $escalated,
            'response_ms'   => $ms,
            'success'       => true,
        ]);

        return [
            'success'       => true,
            'response_text' => $responseText,
            'lead_id'       => $leadId,
            'escalated'     => $escalated,
            'ms'            => $ms,
        ];
    }

    // ── Claude API (agentic tool-use loop) ─────────────────────────────────────

    /**
     * Call Claude Sonnet 4.6 with tools. Runs up to MAX_TOOL_LOOPS tool calls.
     * Returns [string $responseText, bool $escalated].
     */
    private function callClaude(string $userMessage, ?string $phone, ?int $leadId): array
    {
        $apiKey = env('ANTHROPIC_API_KEY') ?: '';
        if (!$apiKey) {
            throw new \RuntimeException('ANTHROPIC_API_KEY not set');
        }

        $messages  = [['role' => 'user', 'content' => $userMessage]];
        $escalated = false;

        for ($loop = 0; $loop < self::MAX_TOOL_LOOPS; $loop++) {
            $body = [
                'model'       => self::MODEL,
                'max_tokens'  => self::MAX_TOKENS,
                'temperature' => self::TEMPERATURE,
                'system'      => self::SYSTEM_PROMPT,
                'tools'       => self::$toolDefs,
                'messages'    => $messages,
            ];

            $response = $this->httpPost(self::CLAUDE_API, $body, [
                'x-api-key: ' . $apiKey,
                'anthropic-version: 2023-06-01',
            ]);

            $stopReason = $response['stop_reason'] ?? 'end_turn';
            $content    = $response['content'] ?? [];

            // Check for tool_use blocks
            $toolUseBlocks = array_filter($content, fn($b) => ($b['type'] ?? '') === 'tool_use');

            if (empty($toolUseBlocks)) {
                // No tool calls — extract text response
                foreach ($content as $block) {
                    if (($block['type'] ?? '') === 'text') {
                        return [trim($block['text'] ?? ''), $escalated];
                    }
                }
                return ['', $escalated];
            }

            // Execute tool calls and append results
            $assistantContent = $content;
            $toolResults      = [];

            foreach ($toolUseBlocks as $toolBlock) {
                $toolName  = $toolBlock['name']  ?? '';
                $toolInput = $toolBlock['input'] ?? [];
                $toolId    = $toolBlock['id']    ?? uniqid('tool_');

                $result = $this->executeTool($toolName, $toolInput, $phone, $leadId, $escalated);

                $toolResults[] = [
                    'type'        => 'tool_result',
                    'tool_use_id' => $toolId,
                    'content'     => json_encode($result, JSON_UNESCAPED_UNICODE),
                ];
            }

            // Append assistant turn + tool results to message history
            $messages[] = ['role' => 'assistant', 'content' => $assistantContent];
            $messages[] = ['role' => 'user',      'content' => $toolResults];
        }

        // Fallback if loop exhausted
        return ['Передаю вашу заявку менеджеру. Он свяжется в ближайшее время.', true];
    }

    // ── Tool implementations ────────────────────────────────────────────────────

    private function executeTool(string $name, array $input, ?string $phone, ?int $leadId, bool &$escalated): array
    {
        switch ($name) {
            case 'search_catalog':
                return $this->toolSearchCatalog($input['query'] ?? '');

            case 'get_order_status':
                $p = $input['phone'] ?? $phone ?? '';
                return $this->toolGetOrderStatus($p);

            case 'escalate_to_manager':
                $escalated = true;
                return $this->toolEscalate($input['reason'] ?? '', $leadId);

            default:
                return ['error' => 'Unknown tool: ' . $name];
        }
    }

    /**
     * Search МойСклад assortment for product availability and price.
     */
    private function toolSearchCatalog(string $query): array
    {
        if (!$query) {
            return ['found' => false, 'message' => 'Пустой запрос'];
        }
        try {
            $data  = Yii::$app->moyskladClient->get('/entity/assortment', [
                'search' => $query,
                'limit'  => 5,
            ]);
            $rows  = $data['rows'] ?? [];
            if (empty($rows)) {
                return ['found' => false, 'message' => "Товар «{$query}» не найден в каталоге"];
            }
            $items = [];
            foreach (array_slice($rows, 0, 3) as $row) {
                $name  = $row['name'] ?? '';
                $price = isset($row['salePrices'][0]['value'])
                    ? round($row['salePrices'][0]['value'] / 100, 2) . ' BYN'
                    : 'цена не указана';
                $stock = $row['stock'] ?? null;
                $items[] = compact('name', 'price', 'stock');
            }
            return ['found' => true, 'items' => $items, 'total' => count($rows)];
        } catch (\Throwable $e) {
            Yii::warning('[AiChat] searchCatalog error: ' . $e->getMessage(), 'ai_chat');
            return ['found' => false, 'error' => 'МойСклад недоступен'];
        }
    }

    /**
     * Find lead by phone in AmoCRM and return its status.
     */
    private function toolGetOrderStatus(string $phone): array
    {
        if (!$phone) {
            return ['found' => false, 'message' => 'Телефон не указан'];
        }
        try {
            $contact = Yii::$app->amocrm->findContactByPhone($phone);
            if (!$contact) {
                return ['found' => false, 'message' => 'Заказ по номеру ' . $phone . ' не найден'];
            }
            $contactId = (int)$contact['id'];
            $leads = Yii::$app->amocrm->getLeads([
                'filter[contact_id]' => $contactId,
                'limit'              => 1,
                'order'              => 'created_at,desc',
            ]);
            $lead = $leads['_embedded']['leads'][0] ?? null;
            if (!$lead) {
                return ['found' => false, 'message' => 'Заказы не найдены'];
            }
            $statusId = (int)($lead['status_id'] ?? 0);
            $statuses = [
                61690518 => 'Неразобранное',
                61690522 => 'Первый контакт',
                61690526 => 'В обработке',
                142      => 'Успешно реализовано',
                143      => 'Закрыто и не реализовано',
            ];
            $statusName = $statuses[$statusId] ?? 'Статус ' . $statusId;
            return [
                'found'      => true,
                'lead_id'    => $lead['id'],
                'status'     => $statusName,
                'lead_name'  => $lead['name'] ?? '',
                'created_at' => $lead['created_at'] ? date('d.m.Y', $lead['created_at']) : '',
            ];
        } catch (\Throwable $e) {
            Yii::warning('[AiChat] getOrderStatus error: ' . $e->getMessage(), 'ai_chat');
            return ['found' => false, 'error' => 'AmoCRM недоступен'];
        }
    }

    /**
     * Mark for escalation — note is added in handleWebhook() after Claude finishes.
     */
    private function toolEscalate(string $reason, ?int $leadId): array
    {
        Yii::info('[AiChat] escalate: ' . $reason . ' lead=' . $leadId, 'ai_chat');
        return [
            'escalated' => true,
            'message'   => 'Менеджер уведомлён. Свяжется в течение 30 минут (пн–сб 9:00–21:00 МСК).',
            'reason'    => $reason,
        ];
    }

    // ── AmoCRM helpers ─────────────────────────────────────────────────────────

    /**
     * Log the AI interaction as a note on the lead.
     */
    private function logToAmocrm(int $leadId, string $userMessage, string $aiResponse): void
    {
        $text = "🤖 ИИ-ответ\n"
              . "Вопрос: " . mb_substr($userMessage, 0, 200) . "\n"
              . "Ответ: " . mb_substr($aiResponse, 0, 300);
        try {
            Yii::$app->amocrm->addNote($leadId, $text);
        } catch (\Throwable $e) {
            Yii::warning('[AiChat] addNote error: ' . $e->getMessage(), 'ai_chat');
        }
    }

    /**
     * Move lead to status 61690526 (В обработке / "2") on escalation.
     */
    private function escalateLeadStatus(int $leadId): void
    {
        try {
            Yii::$app->amocrm->updateLead($leadId, [
                'status_id'   => 61690526,
                'pipeline_id' => 7426646,
            ]);
        } catch (\Throwable $e) {
            Yii::warning('[AiChat] escalateLeadStatus error: ' . $e->getMessage(), 'ai_chat');
        }
    }

    // ── Payload extraction ─────────────────────────────────────────────────────

    /**
     * Extract [leadId, messageText, phone] from AmoCRM webhook payload.
     * AmoCRM sends form-encoded data; Yii parses it into $_POST.
     * Supports: unsorted chat events and lead-status-changed events.
     */
    private function extractFromPayload(array $payload): array
    {
        $leadId  = null;
        $message = null;
        $phone   = null;

        // Unsorted chat event
        $unsorted = $payload['_embedded']['unsorted'] ?? $payload['unsorted'] ?? [];
        if (!empty($unsorted[0])) {
            $u       = $unsorted[0];
            $meta    = $u['metadata'] ?? [];
            $message = $meta['origin']['chat']['message']['text']
                    ?? $meta['from']['name'] ?? null;
            $phone   = $meta['from']['phone'] ?? $meta['from']['ref'] ?? null;
            // Try to get lead_id from linked entity
            $leadId = (int)($u['_embedded']['leads'][0]['id'] ?? 0) ?: null;
        }

        // Lead status / note event
        $leads = $payload['leads']['status'] ?? $payload['leads']['note'] ?? [];
        if (!empty($leads[0]) && !$leadId) {
            $leadId = (int)($leads[0]['id'] ?? 0) ?: null;
        }

        // Direct fields passed by controller
        if (!$message) {
            $message = $payload['message_text'] ?? null;
        }
        if (!$phone) {
            $phone = $payload['client_phone'] ?? null;
        }
        if (!$leadId && isset($payload['lead_id'])) {
            $leadId = (int)$payload['lead_id'] ?: null;
        }

        return [$leadId, $message, $phone];
    }

    // ── Phase 4 monitoring helpers ─────────────────────────────────────────────

    /**
     * Write a structured row to ai_chat_log for monitoring dashboards.
     */
    private function writeDbLog(array $data): void
    {
        try {
            Yii::$app->db->createCommand()->insert('{{%ai_chat_log}}', array_merge([
                'lead_id'      => null,
                'pipeline_id'  => null,
                'client_phone' => null,
                'message_text' => null,
                'response_text'=> null,
                'tools_used'   => null,
                'tool_loops'   => 0,
                'escalated'    => false,
                'response_ms'  => null,
                'model'        => self::MODEL,
                'success'      => true,
                'error_message'=> null,
                'created_at'   => time(),
            ], $data))->execute();
        } catch (\Throwable $e) {
            // Don't fail the request over logging — table may not exist yet
            Yii::warning('[AiChat] writeDbLog: ' . $e->getMessage(), 'ai_chat');
        }
    }

    /**
     * Extract pipeline_id from raw webhook payload.
     */
    private function extractPipelineId(array $payload): ?int
    {
        $unsorted = $payload['_embedded']['unsorted'] ?? $payload['unsorted'] ?? [];
        if (!empty($unsorted[0]['pipeline_id'])) {
            return (int)$unsorted[0]['pipeline_id'];
        }
        $leads = $payload['leads']['status'] ?? [];
        if (!empty($leads[0]['pipeline_id'])) {
            return (int)$leads[0]['pipeline_id'];
        }
        return null;
    }

    // ── HTTP helper ─────────────────────────────────────────────────────────────

    private function httpPost(string $url, array $body, array $extraHeaders = []): array
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode($body, JSON_UNESCAPED_UNICODE),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_HTTPHEADER     => array_merge(
                ['Content-Type: application/json', 'Accept: application/json'],
                $extraHeaders
            ),
        ]);
        $responseBody = curl_exec($ch);
        $code         = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlErr      = curl_error($ch);
        curl_close($ch);

        if ($responseBody === false || $code === 0) {
            throw new \RuntimeException('HTTP error: ' . $curlErr);
        }
        if ($code >= 400) {
            $err = json_decode($responseBody, true);
            $msg = $err['error']['message'] ?? mb_substr($responseBody, 0, 200);
            throw new \RuntimeException("Claude API {$code}: {$msg}");
        }

        return json_decode($responseBody, true) ?? [];
    }
}
