<?php

namespace app\backend\modules\admin\services;

use Yii;
use app\backend\modules\admin\models\AdminLog;

/**
 * Сервис логирования операций администратора
 */
class AdminLogService
{
    /**
     * Записать действие в лог
     */
    public static function log(
        string $action,
        string $entityType,
        ?int $entityId = null,
        ?string $entityName = null,
        ?string $description = null,
        ?array $oldValues = null,
        ?array $newValues = null
    ): void {
        $user = Yii::$app->user;
        
        if ($user->isGuest) {
            return;
        }

        try {
            $log = new AdminLog();
            $log->user_id = $user->id;
            $log->username = $user->identity->username ?? 'unknown';
            $log->action = $action;
            $log->entity_type = $entityType;
            $log->entity_id = $entityId;
            $log->entity_name = $entityName;
            $log->description = $description;
            $log->old_values = $oldValues ? json_encode($oldValues, JSON_UNESCAPED_UNICODE) : null;
            $log->new_values = $newValues ? json_encode($newValues, JSON_UNESCAPED_UNICODE) : null;
            $log->ip_address = Yii::$app->request->userIP;
            $log->user_agent = substr(Yii::$app->request->userAgent ?? '', 0, 500);
            $log->save(false);
        } catch (\Throwable $e) {
            Yii::error('Failed to save admin log: ' . $e->getMessage());
        }
    }

    /**
     * Логировать создание
     */
    public static function logCreate(string $entityType, int $entityId, string $entityName, ?array $data = null): void
    {
        self::log(
            AdminLog::ACTION_CREATE,
            $entityType,
            $entityId,
            $entityName,
            "Создан {$entityType}: {$entityName}",
            null,
            $data
        );
    }

    /**
     * Логировать обновление
     */
    public static function logUpdate(string $entityType, int $entityId, string $entityName, array $oldValues, array $newValues): void
    {
        // Определяем, что именно изменилось
        $changes = [];
        foreach ($newValues as $key => $value) {
            if (isset($oldValues[$key]) && $oldValues[$key] != $value) {
                $changes[] = "{$key}: {$oldValues[$key]} → {$value}";
            }
        }
        
        $description = !empty($changes) 
            ? "Изменен {$entityType}: " . implode(', ', $changes)
            : "Изменен {$entityType}: {$entityName}";

        self::log(
            AdminLog::ACTION_UPDATE,
            $entityType,
            $entityId,
            $entityName,
            $description,
            $oldValues,
            $newValues
        );
    }

    /**
     * Логировать удаление
     */
    public static function logDelete(string $entityType, int $entityId, string $entityName, ?array $data = null): void
    {
        self::log(
            AdminLog::ACTION_DELETE,
            $entityType,
            $entityId,
            $entityName,
            "Удален {$entityType}: {$entityName}",
            $data,
            null
        );
    }

    /**
     * Логировать просмотр (только для важных сущностей)
     */
    public static function logView(string $entityType, int $entityId, string $entityName): void
    {
        // Логируем не каждый просмотр, чтобы не засорять базу
        // Можно добавить логику прореживания
        self::log(
            AdminLog::ACTION_VIEW,
            $entityType,
            $entityId,
            $entityName,
            "Просмотр {$entityType}: {$entityName}",
            null,
            null
        );
    }

    /**
     * Логировать вход
     */
    public static function logLogin(): void
    {
        self::log(
            AdminLog::ACTION_LOGIN,
            AdminLog::ENTITY_USER,
            Yii::$app->user->id,
            Yii::$app->user->identity->username ?? 'unknown',
            'Вход в систему',
            null,
            null
        );
    }

    /**
     * Логировать выход
     */
    public static function logLogout(): void
    {
        self::log(
            AdminLog::ACTION_LOGOUT,
            AdminLog::ENTITY_USER,
            Yii::$app->user->id,
            Yii::$app->user->identity->username ?? 'unknown',
            'Выход из системы',
            null,
            null
        );
    }

    /**
     * Логировать экспорт
     */
    public static function logExport(string $entityType, int $count, ?string $format = null): void
    {
        self::log(
            AdminLog::ACTION_EXPORT,
            $entityType,
            null,
            null,
            "Экспорт {$entityType}: {$count} записей" . ($format ? " ({$format})" : ''),
            null,
            ['count' => $count, 'format' => $format]
        );
    }

    /**
     * Логировать импорт
     */
    public static function logImport(string $entityType, int $successCount, int $errorCount = 0): void
    {
        $description = "Импорт {$entityType}: {$successCount} успешно";
        if ($errorCount > 0) {
            $description .= ", {$errorCount} ошибок";
        }
        
        self::log(
            AdminLog::ACTION_IMPORT,
            $entityType,
            null,
            null,
            $description,
            null,
            ['success' => $successCount, 'errors' => $errorCount]
        );
    }

    /**
     * Логировать смену статуса
     */
    public static function logStatusChange(string $entityType, int $entityId, string $entityName, string $oldStatus, string $newStatus): void
    {
        self::log(
            AdminLog::ACTION_STATUS_CHANGE,
            $entityType,
            $entityId,
            $entityName,
            "Смена статуса: {$oldStatus} → {$newStatus}",
            ['status' => $oldStatus],
            ['status' => $newStatus]
        );
    }

    /**
     * Логировать массовое действие
     */
    public static function logBulkAction(string $action, string $entityType, int $count, ?array $params = null): void
    {
        $actionLabels = [
            'delete' => 'удалено',
            'update' => 'обновлено',
            'activate' => 'активировано',
            'deactivate' => 'деактивировано',
            'export' => 'экспортировано',
        ];
        
        $label = $actionLabels[$action] ?? $action;
        
        self::log(
            AdminLog::ACTION_BULK_ACTION,
            $entityType,
            null,
            null,
            "Массовое действие: {$label} {$count} {$entityType}",
            null,
            array_merge(['action' => $action, 'count' => $count], $params ?? [])
        );
    }

    /**
     * Получить статистику действий за период
     */
    public static function getStats(int $days = 7): array
    {
        $from = date('Y-m-d H:i:s', strtotime("-{$days} days"));

        $logs = AdminLog::find()
            ->where(['>=', 'created_at', $from])
            ->all();
        
        $stats = [
            'total' => count($logs),
            'by_action' => [],
            'by_entity' => [],
            'by_user' => [],
        ];
        
        foreach ($logs as $log) {
            $stats['by_action'][$log->action] = ($stats['by_action'][$log->action] ?? 0) + 1;
            $stats['by_entity'][$log->entity_type] = ($stats['by_entity'][$log->entity_type] ?? 0) + 1;
            $stats['by_user'][$log->username] = ($stats['by_user'][$log->username] ?? 0) + 1;
        }
        
        return $stats;
    }
}
