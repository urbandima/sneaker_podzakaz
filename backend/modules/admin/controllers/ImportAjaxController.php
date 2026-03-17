<?php

namespace app\backend\modules\admin\controllers;

use Yii;
use yii\web\Controller;
use yii\web\Response;
use yii\filters\VerbFilter;
use yii\filters\AccessControl;
use app\backend\modules\admin\models\import\ImportTask;
use app\backend\modules\admin\models\import\ImportLog;
use app\backend\modules\admin\models\import\ImportSource;

/**
 * ImportAjaxController — AJAX запросы для импорта
 * 
 * Actions:
 * - status: Статус задачи
 * - progress: Прогресс выполнения
 * - stop: Остановка задачи
 * - logs: Логи задачи в реальном времени
 */
class ImportAjaxController extends Controller
{
    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    [
                        'allow' => true,
                        'roles' => ['admin', 'manager'],
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'stop' => ['POST'],
                ],
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function beforeAction($action)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        return parent::beforeAction($action);
    }

    /**
     * Получить статус задачи
     * @param int $taskId
     * @return array
     */
    public function actionStatus($taskId)
    {
        $task = ImportTask::findOne($taskId);
        
        if (!$task) {
            return [
                'success' => false,
                'error' => 'Задача не найдена',
            ];
        }

        return [
            'success' => true,
            'task' => [
                'id' => $task->id,
                'status' => $task->status,
                'status_label' => $task->getStatusLabel(),
                'progress' => $task->getProgress(),
                'total_products' => $task->total_products,
                'processed_products' => $task->processed_products,
                'imported_count' => $task->imported_count,
                'updated_count' => $task->updated_count,
                'failed_count' => $task->failed_count,
                'duplicate_count' => $task->duplicate_count,
                'started_at' => $task->started_at,
                'finished_at' => $task->finished_at,
                'duration' => $task->getDurationFormatted(),
                'error_message' => $task->error_message,
            ],
            'source' => [
                'id' => $task->source->id,
                'name' => $task->source->name,
                'code' => $task->source->code,
            ],
        ];
    }

    /**
     * Получить прогресс выполнения
     * @param int $taskId
     * @return array
     */
    public function actionProgress($taskId)
    {
        $task = ImportTask::findOne($taskId);
        
        if (!$task) {
            return [
                'success' => false,
                'error' => 'Задача не найдена',
            ];
        }

        return [
            'success' => true,
            'progress' => $task->getProgress(),
            'status' => $task->status,
            'processed' => $task->processed_products,
            'total' => $task->total_products,
            'imported' => $task->imported_count,
            'updated' => $task->updated_count,
            'failed' => $task->failed_count,
            'duplicates' => $task->duplicate_count,
            'is_running' => $task->status === ImportTask::STATUS_RUNNING,
            'is_completed' => in_array($task->status, [ImportTask::STATUS_COMPLETED, ImportTask::STATUS_FAILED, ImportTask::STATUS_CANCELLED]),
        ];
    }

    /**
     * Остановить задачу
     * @param int $taskId
     * @return array
     */
    public function actionStop($taskId)
    {
        $task = ImportTask::findOne($taskId);
        
        if (!$task) {
            return [
                'success' => false,
                'error' => 'Задача не найдена',
            ];
        }

        if ($task->status !== ImportTask::STATUS_RUNNING) {
            return [
                'success' => false,
                'error' => 'Задачу нельзя остановить (статус: ' . $task->getStatusLabel() . ')',
            ];
        }

        $task->cancel();

        return [
            'success' => true,
            'message' => 'Задача остановлена',
            'task' => [
                'id' => $task->id,
                'status' => $task->status,
                'status_label' => $task->getStatusLabel(),
            ],
        ];
    }

    /**
     * Получить логи задачи в реальном времени
     * @param int $taskId
     * @param int $lastId Последний ID лога (для подгрузки новых)
     * @return array
     */
    public function actionLogs($taskId, $lastId = 0)
    {
        $task = ImportTask::findOne($taskId);
        
        if (!$task) {
            return [
                'success' => false,
                'error' => 'Задача не найдена',
            ];
        }

        $query = ImportLog::find()
            ->where(['task_id' => $taskId])
            ->orderBy(['created_at' => SORT_DESC, 'id' => SORT_DESC]);

        if ($lastId > 0) {
            $query->andWhere(['>', 'id', $lastId]);
        }

        $logs = $query->limit(50)->all();

        $result = [];
        foreach ($logs as $log) {
            $result[] = [
                'id' => $log->id,
                'action' => $log->action,
                'action_label' => $log->getActionLabel(),
                'level' => $log->level,
                'level_label' => $log->getLevelLabel(),
                'sku' => $log->sku,
                'product_name' => $log->product_name,
                'message' => $log->message,
                'created_at' => $log->created_at,
                'time' => date('H:i:s', strtotime($log->created_at)),
            ];
        }

        return [
            'success' => true,
            'logs' => $result,
            'last_id' => !empty($logs) ? $logs[0]->id : $lastId,
            'task_status' => $task->status,
        ];
    }

    /**
     * Получить все запущенные задачи
     * @return array
     */
    public function actionRunningTasks()
    {
        $tasks = ImportTask::getRunningTasks();

        $result = [];
        foreach ($tasks as $task) {
            $result[] = [
                'id' => $task->id,
                'source_name' => $task->source->name,
                'progress' => $task->getProgress(),
                'processed' => $task->processed_products,
                'total' => $task->total_products,
                'started_at' => $task->started_at,
                'duration' => $task->getDurationFormatted(),
            ];
        }

        return [
            'success' => true,
            'tasks' => $result,
            'count' => count($result),
        ];
    }

    /**
     * Проверить статус источника
     * @param int $sourceId
     * @return array
     */
    public function actionSourceStatus($sourceId)
    {
        $source = ImportSource::findOne($sourceId);
        
        if (!$source) {
            return [
                'success' => false,
                'error' => 'Источник не найден',
            ];
        }

        // Последняя задача
        $lastTask = ImportTask::find()
            ->where(['source_id' => $sourceId])
            ->orderBy(['created_at' => SORT_DESC])
            ->one();

        return [
            'success' => true,
            'source' => [
                'id' => $source->id,
                'name' => $source->name,
                'code' => $source->code,
                'is_active' => $source->is_active,
                'last_run' => $source->last_run_at,
                'successful_runs' => $source->successful_runs,
                'failed_runs' => $source->failed_runs,
                'total_products' => $source->total_products_parsed,
            ],
            'last_task' => $lastTask ? [
                'id' => $lastTask->id,
                'status' => $lastTask->status,
                'status_label' => $lastTask->getStatusLabel(),
                'created_at' => $lastTask->created_at,
            ] : null,
        ];
    }

    /**
     * Получить статистику за период
     * @param string $period Период (today, week, month)
     * @return array
     */
    public function actionStats($period = 'today')
    {
        $dateCondition = match($period) {
            'today' => 'DATE(created_at) = CURDATE()',
            'week' => 'created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)',
            'month' => 'created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)',
            default => 'DATE(created_at) = CURDATE()',
        };

        $stats = Yii::$app->db->createCommand(
            "SELECT 
                COUNT(*) as tasks,
                SUM(imported_count) as imported,
                SUM(updated_count) as updated,
                SUM(failed_count) as errors,
                SUM(duplicate_count) as duplicates
            FROM {{%import_task}}
            WHERE {$dateCondition}"
        )->queryOne();

        return [
            'success' => true,
            'period' => $period,
            'stats' => [
                'tasks' => (int) ($stats['tasks'] ?? 0),
                'imported' => (int) ($stats['imported'] ?? 0),
                'updated' => (int) ($stats['updated'] ?? 0),
                'errors' => (int) ($stats['errors'] ?? 0),
                'duplicates' => (int) ($stats['duplicates'] ?? 0),
            ],
        ];
    }

    /**
     * Отметить уведомление как прочитанное
     * @param int $id ID уведомления
     * @return array
     */
    public function actionMarkNotificationRead($id)
    {
        $notification = \app\backend\modules\admin\models\import\ImportNotification::findOne($id);
        
        if (!$notification) {
            return [
                'success' => false,
                'error' => 'Уведомление не найдено',
            ];
        }

        $notification->markAsRead();

        return [
            'success' => true,
            'unread_count' => \app\backend\modules\admin\models\import\ImportNotification::getUnreadCount(),
        ];
    }

    /**
     * Отметить все уведомления как прочитанные
     * @return array
     */
    public function actionMarkAllNotificationsRead()
    {
        $count = \app\backend\modules\admin\models\import\ImportNotification::markAllAsRead();

        return [
            'success' => true,
            'marked_count' => $count,
        ];
    }

    /**
     * Получить непрочитанные уведомления
     * @return array
     */
    public function actionNotifications()
    {
        $notifications = \app\backend\modules\admin\models\import\ImportNotification::getUnread(10);

        $result = [];
        foreach ($notifications as $notification) {
            $result[] = [
                'id' => $notification->id,
                'type' => $notification->type,
                'icon' => $notification->getIcon(),
                'css_class' => $notification->getCssClass(),
                'title' => $notification->title,
                'message' => $notification->message,
                'created_at' => $notification->created_at,
                'time_ago' => Yii::$app->formatter->asRelativeTime($notification->created_at),
            ];
        }

        return [
            'success' => true,
            'notifications' => $result,
            'unread_count' => \app\backend\modules\admin\models\import\ImportNotification::getUnreadCount(),
        ];
    }
}
