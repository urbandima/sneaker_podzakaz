<?php

namespace app\backend\modules\admin\services;

use Yii;
use yii\db\ActiveRecord;

/**
 * Сервис логирования действий администраторов.
 *
 * Пишет напрямую через createCommand()->insert() — без AR-модели,
 * чтобы избежать рекурсии при вызове из поведений моделей.
 *
 * Низкоуровневая часть записи (получение IP/UA, безопасное выполнение
 * с перехватом исключений) вынесена в LogContextTrait — общую точку
 * с AdminLogService (см. docblock трейта; полное объединение таблиц
 * admin_log/activity_log — вопрос отдельного решения по CMP-79/CMP-81).
 */
class ActivityLogService
{
    use LogContextTrait;

    /**
     * Записать произвольное событие в activity_log.
     */
    public static function log(
        string $action,
        string $targetType,
        $targetId,
        string $targetLabel,
        array  $changes = [],
        string $source  = 'web'
    ): void {
        $userId   = null;
        $userName = null;
        $userRole = null;

        // Безопасное получение данных пользователя (нет в CLI/cron)
        try {
            $app = Yii::$app;
            if (!$app->user->isGuest) {
                $userId   = $app->user->id;
                $identity = $app->user->identity;
                $userName = $identity->username ?? ($identity->email ?? null);
                $userRole = $identity->role ?? null;
            }
        } catch (\Throwable $e) {
            // CLI / cron — пропускаем
        }

        $meta = self::currentRequestMeta();

        self::safeLogWrite(function () use (
            $userId,
            $userName,
            $userRole,
            $action,
            $targetType,
            $targetId,
            $targetLabel,
            $changes,
            $source,
            $meta
        ) {
            Yii::$app->db->createCommand()->insert('activity_log', [
                'user_id'      => $userId,
                'user_name'    => $userName,
                'user_role'    => $userRole,
                'action'       => $action,
                'target_type'  => $targetType,
                'target_id'    => $targetId ?: null,
                'target_label' => $targetLabel ?: null,
                'changes'      => !empty($changes) ? json_encode($changes, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : null,
                'source'       => $source,
                'ip'           => $meta['ip'],
                'user_agent'   => $meta['userAgent'] !== null ? mb_substr($meta['userAgent'], 0, 255) : null,
                'created_at'   => time(),
            ])->execute();
        }, 'ActivityLogService::log failed');
    }

    /**
     * Логировать обновление AR-модели.
     * $oldAttrs — снимок атрибутов ДО сохранения (из $event->changedAttributes).
     */
    public static function logChange(
        ActiveRecord $model,
        array        $oldAttrs,
        ?string      $targetType  = null,
        ?string      $targetLabel = null
    ): void {
        $currentAttrs = $model->attributes;
        $changes = [];

        // Исключаем служебные поля из diff
        $skip = ['updated_at', 'created_at', 'password_hash', 'auth_key', 'token', 'remember_token'];

        foreach ($oldAttrs as $field => $oldVal) {
            if (in_array($field, $skip, true)) {
                continue;
            }
            $newVal = $currentAttrs[$field] ?? null;
            if ((string)$oldVal !== (string)$newVal) {
                $changes[$field] = ['old' => $oldVal, 'new' => $newVal];
            }
        }

        if (empty($changes)) {
            return; // нечего логировать
        }

        $type  = $targetType  ?? self::resolveType($model);
        $label = $targetLabel ?? self::resolveLabel($model);

        self::log('updated', $type, $model->getPrimaryKey(), $label, $changes);
    }

    /**
     * Логировать создание.
     */
    public static function logCreate(
        ActiveRecord $model,
        ?string      $targetType  = null,
        ?string      $targetLabel = null
    ): void {
        $type  = $targetType  ?? self::resolveType($model);
        $label = $targetLabel ?? self::resolveLabel($model);
        self::log('created', $type, $model->getPrimaryKey(), $label);
    }

    /**
     * Логировать удаление.
     */
    public static function logDelete(
        ActiveRecord $model,
        ?string      $targetType  = null,
        ?string      $targetLabel = null
    ): void {
        $type  = $targetType  ?? self::resolveType($model);
        $label = $targetLabel ?? self::resolveLabel($model);
        self::log('deleted', $type, $model->getPrimaryKey(), $label);
    }

    /**
     * Логировать вход пользователя.
     */
    public static function logLogin(string $username): void
    {
        self::log('login', 'User', null, $username);
    }

    /**
     * Логировать выход пользователя.
     */
    public static function logLogout(string $username): void
    {
        self::log('logout', 'User', null, $username);
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private static function resolveType(ActiveRecord $model): string
    {
        // Берём короткое имя класса: App\Modules\Checkout\Models\Order → Order
        $parts = explode('\\', get_class($model));
        return end($parts);
    }

    private static function resolveLabel(ActiveRecord $model): string
    {
        foreach (['order_number', 'number', 'name', 'email', 'username', 'title'] as $attr) {
            if (isset($model->$attr) && $model->$attr !== '' && $model->$attr !== null) {
                return (string)$model->$attr;
            }
        }
        return '#' . $model->getPrimaryKey();
    }
}
