<?php

namespace app\backend\modules\admin\services;

use Yii;

/**
 * Общая низкоуровневая инфраструктура записи логов действий.
 *
 * Используется AdminLogService (таблица `admin_log`, ручные вызовы из
 * BaseAdminController) и ActivityLogService (таблица `activity_log`,
 * авто-запись через LogBehavior на моделях Order/Product/Customer/...).
 *
 * ВАЖНО: таблицы `admin_log` и `activity_log` остаются раздельными —
 * у них разная схема (см. миграции m260503_140000_complete_admin_log_table
 * и m260427_000002_activity_log_full) и разная семантика part of записи
 * (admin_log хранит человекочитаемое `description` + отдельные
 * `old_values`/`new_values`; activity_log хранит структурированный diff
 * в одном JSON-поле `changes` + `source`/`user_role`, которых нет в
 * admin_log). Объединение схем — вопрос отдельного решения по CMP-79/CMP-81.
 *
 * Этот трейт консолидирует только то, что у обоих сервисов дублировалось
 * буквально: безопасное получение IP/User-Agent текущего запроса (должно
 * не падать в CLI/cron) и обёртку "выполнить запись, подавив и залогировав
 * любое исключение, чтобы сбой логирования не ронял основной запрос".
 *
 * Это готовит почву для будущего единого LogWriterInterface, если решение
 * по CMP-79/CMP-81 будет — свести обе таблицы воедино.
 */
trait LogContextTrait
{
    /**
     * Безопасно получить IP и User-Agent текущего запроса.
     * Никогда не бросает исключение: в CLI/cron компонент `request`
     * может отсутствовать или вести себя иначе.
     *
     * @return array{ip: ?string, userAgent: ?string}
     */
    protected static function currentRequestMeta(): array
    {
        try {
            $request = Yii::$app->request;
            return [
                'ip'        => $request->userIP ?? null,
                'userAgent' => (string)($request->userAgent ?? ''),
            ];
        } catch (\Throwable $e) {
            return ['ip' => null, 'userAgent' => null];
        }
    }

    /**
     * Выполнить запись лога, подавив любое исключение (сбой логирования
     * не должен ронять основной запрос/действие) и сообщив о нём через
     * Yii::error с категорией — именем вызывающего класса.
     */
    protected static function safeLogWrite(callable $writer, string $failureMessage): void
    {
        try {
            $writer();
        } catch (\Throwable $e) {
            Yii::error($failureMessage . ': ' . $e->getMessage(), static::class);
        }
    }
}
