<?php

namespace app\backend\console\controllers;

use Yii;
use yii\console\Controller;
use yii\console\ExitCode;
use app\backend\modules\checkout\models\Order;

/**
 * МойСклад sync commands.
 *
 * Usage:
 *   php yii moysklad-sync/sync [--since=60]
 *   php yii moysklad-sync/push-all [--limit=50]
 *   php yii moysklad-sync/setup-webhook
 *   php yii moysklad-sync/push-order <order_id>
 */
class MoySkladSyncController extends Controller
{
    public $defaultAction = 'sync';

    /**
     * Pull orders updated in last N minutes from МойСклад → update our DB.
     * Designed to run from cron every 5–15 minutes.
     */
    public function actionSync(int $since = 60): int
    {
        $this->stdout("МойСклад: periodic sync (last {$since} min)\n");

        try {
            $result = Yii::$app->moysklad->periodicSync($since);
        } catch (\Throwable $e) {
            $this->stderr("ОШИБКА: " . $e->getMessage() . "\n");
            return ExitCode::UNSPECIFIED_ERROR;
        }

        $this->stdout(
            "  Обновлено: {$result['synced']}, новых связей: {$result['new_links']}, " .
            "обработано: {$result['processed']}, всего в МС: {$result['ms_total']}\n"
        );
        $this->stdout("  Синхронизация с: {$result['since']}\n");

        foreach ($result['log'] as $entry) {
            if (($entry['status'] ?? '') === 'updated') {
                $this->stdout("  [upd] #{$entry['our_id']} ← МС {$entry['ms_id']} ({$entry['name']})\n");
            }
        }

        $this->stdout("Готово.\n");
        return ExitCode::OK;
    }

    /**
     * Push unsynced orders (no moysklad_id) to МойСклад.
     */
    public function actionPushAll(int $limit = 50): int
    {
        $this->stdout("МойСклад: push all unsynced (limit={$limit})\n");

        try {
            $result = Yii::$app->moysklad->pushAllOrders($limit);
        } catch (\Throwable $e) {
            $this->stderr("ОШИБКА: " . $e->getMessage() . "\n");
            return ExitCode::UNSPECIFIED_ERROR;
        }

        $this->stdout("  Отправлено: {$result['pushed']}, ошибок: {$result['errors']}, всего: {$result['total']}\n");

        foreach ($result['log'] as $entry) {
            $mark = ($entry['status'] === 'pushed') ? '+' : '!';
            $msg  = $entry['message'] ?? '';
            $this->stdout("  [{$mark}] #{$entry['id']} {$entry['number']}" . ($msg ? " — {$msg}" : '') . "\n");
        }

        return ExitCode::OK;
    }

    /**
     * Push a single order by ID to МойСклад.
     */
    public function actionPushOrder(int $orderId): int
    {
        $this->stdout("МойСклад: push order #{$orderId}\n");

        try {
            $result = Yii::$app->moysklad->pushOrderToMS($orderId);
            $msId   = $result['id'] ?? '?';
            $this->stdout("  Отправлен. МС ID: {$msId}\n");
        } catch (\Throwable $e) {
            $this->stderr("ОШИБКА: " . $e->getMessage() . "\n");
            return ExitCode::UNSPECIFIED_ERROR;
        }

        return ExitCode::OK;
    }

    /**
     * Register a webhook with МойСклад so it notifies us on order changes.
     */
    public function actionSetupWebhook(): int
    {
        $this->stdout("МойСклад: регистрация вебхука\n");

        $status = Yii::$app->moysklad->getWebhookStatus();
        if ($status === 'registered') {
            $this->stdout("  Вебхук уже зарегистрирован.\n");
            return ExitCode::OK;
        }

        $webhookUrl = Yii::$app->urlManager->createAbsoluteUrl(['/admin/moysklad/webhook']);
        $this->stdout("  URL: {$webhookUrl}\n");

        try {
            $result = Yii::$app->moysklad->registerWebhook($webhookUrl);
            $this->stdout("  Зарегистрирован. ID: " . ($result['id'] ?? '?') . "\n");
        } catch (\Throwable $e) {
            $this->stderr("ОШИБКА: " . $e->getMessage() . "\n");
            return ExitCode::UNSPECIFIED_ERROR;
        }

        return ExitCode::OK;
    }

    /**
     * Show current sync status: webhook status, last sync time, order counts.
     */
    public function actionStatus(): int
    {
        $this->stdout("МойСклад: статус синхронизации\n");

        $lastSync = Yii::$app->settings->get('moysklad', 'last_sync_at', '—');
        $total    = Order::find()->count();
        $synced   = Order::find()->where(['IS NOT', 'moysklad_id', null])->count();
        $status   = Yii::$app->moysklad->getWebhookStatus();

        $this->stdout("  Последняя синхронизация: {$lastSync}\n");
        $this->stdout("  Заказов: всего={$total}, синхронизировано={$synced}, не синхронизировано=" . ($total - $synced) . "\n");
        $this->stdout("  Вебхук: {$status}\n");

        return ExitCode::OK;
    }
}
