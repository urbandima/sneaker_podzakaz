<?php

/**
 * AiChatController — Phase 3 webhook endpoint (CMP-159).
 *
 * POST /webhook/amocrm/ai-chat
 *   Receives AmoCRM chat events, delegates to AiChatService,
 *   returns 200 always (AmoCRM retries on any non-200).
 *
 * Auth: optional shared secret via X-Amocrm-Hook-Secret header
 *       or ?secret=xxx query param (set AMOCRM_HOOK_SECRET in .env).
 */

namespace app\api\controllers;

use Yii;
use yii\web\Controller;
use yii\web\Response;
use app\backend\modules\admin\services\AiChatService;

class AiChatController extends Controller
{
    public $enableCsrfValidation = false;

    public function beforeAction($action)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        Yii::info(
            sprintf('[AiChat webhook] %s %s | IP: %s | Body(200): %s',
                Yii::$app->request->method,
                Yii::$app->request->url,
                Yii::$app->request->userIP,
                mb_substr(Yii::$app->request->getRawBody(), 0, 200)
            ),
            'ai_chat'
        );

        return parent::beforeAction($action);
    }

    /**
     * POST /webhook/amocrm/ai-chat
     *
     * Accepts AmoCRM webhook payload (form-encoded or JSON).
     * Always returns HTTP 200 to prevent AmoCRM retries.
     */
    public function actionIncoming()
    {
        Yii::$app->response->statusCode = 200;

        if (!Yii::$app->request->isPost) {
            return ['ok' => true, 'skipped' => 'not_post'];
        }

        // Validate optional hook secret
        $secret = env('AMOCRM_HOOK_SECRET') ?: '';
        if ($secret) {
            $incoming = Yii::$app->request->headers->get('X-Amocrm-Hook-Secret', '')
                     ?: Yii::$app->request->get('secret', '');
            if (!hash_equals($secret, $incoming)) {
                Yii::warning('[AiChat webhook] Invalid hook secret from ' . Yii::$app->request->userIP, 'ai_chat');
                // Still return 200 to silence AmoCRM retries, but skip processing
                return ['ok' => true, 'skipped' => 'invalid_secret'];
            }
        }

        // Parse payload — AmoCRM sends form-encoded; also handle JSON (testing)
        $raw     = Yii::$app->request->getRawBody();
        $payload = Yii::$app->request->post();
        if (empty($payload) && $raw) {
            $payload = json_decode($raw, true) ?? [];
        }

        if (empty($payload)) {
            return ['ok' => true, 'skipped' => 'empty_payload'];
        }

        try {
            $service = new AiChatService();
            $result  = $service->handleWebhook($payload);
        } catch (\Throwable $e) {
            Yii::error('[AiChat webhook] unhandled: ' . $e->getMessage(), 'ai_chat');
            $result = ['success' => false, 'error' => $e->getMessage()];
        }

        return array_merge(['ok' => true], $result);
    }

    /**
     * GET /webhook/amocrm/ai-chat/status
     * Health-check: returns service configuration status.
     */
    public function actionStatus()
    {
        $anthropicKey = env('ANTHROPIC_API_KEY') ?: '';
        $amoOk        = Yii::$app->amocrm->isConfigured();
        $msOk         = Yii::$app->moyskladClient->isConfigured();

        return [
            'ok'        => true,
            'anthropic' => $anthropicKey ? 'configured' : 'missing ANTHROPIC_API_KEY',
            'amocrm'    => $amoOk   ? 'configured' : 'not configured',
            'moysklad'  => $msOk    ? 'configured' : 'not configured',
        ];
    }
}
