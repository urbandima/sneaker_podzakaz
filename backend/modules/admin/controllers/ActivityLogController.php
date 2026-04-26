<?php

namespace app\backend\modules\admin\controllers;

use Yii;
use yii\data\Pagination;
use app\backend\modules\admin\models\User;

class ActivityLogController extends BaseAdminController
{
    protected bool $adminOnly = true;

    // ── Фильтры / список допустимых значений ─────────────────────────────────

    private const PERIOD_TODAY = 'today';
    private const PERIOD_WEEK  = 'week';
    private const PERIOD_MONTH = 'month';
    private const PERIOD_ALL   = 'all';

    public static function getTargetTypes(): array
    {
        return [
            'Order'     => 'Заказ',
            'Customer'  => 'Покупатель',
            'Product'   => 'Товар',
            'Buyout'    => 'Выкуп',
            'Receiving' => 'Приёмка',
            'User'      => 'Пользователь',
        ];
    }

    public static function getActions(): array
    {
        return [
            'created'        => 'Создание',
            'updated'        => 'Изменение',
            'deleted'        => 'Удаление',
            'status_changed' => 'Смена статуса',
            'login'          => 'Вход',
            'logout'         => 'Выход',
        ];
    }

    public static function getSources(): array
    {
        return [
            'web'     => 'Web',
            'api'     => 'API',
            'cli'     => 'CLI',
            'cron'    => 'Cron',
            'webhook' => 'Webhook',
            'system'  => 'System',
        ];
    }

    // ── Actions ───────────────────────────────────────────────────────────────

    public function actionIndex()
    {
        $req = Yii::$app->request;

        // Период
        $period    = $req->get('period', self::PERIOD_TODAY);
        $startDate = $req->get('start_date', '');
        $endDate   = $req->get('end_date', '');

        // Остальные фильтры
        $userId     = (int)$req->get('user_id', 0);
        $targetType = $req->get('target_type', '');
        $action     = $req->get('action', '');
        $source     = $req->get('source', '');
        $search     = trim($req->get('search', ''));

        $query = $this->buildQuery($period, $startDate, $endDate, $userId, $targetType, $action, $source, $search);

        $total      = $query->count();
        $pagination = new Pagination([
            'totalCount' => $total,
            'pageSize'   => 50,
            'pageSizeParam' => false,
        ]);

        $logs = $query
            ->orderBy(['created_at' => SORT_DESC])
            ->offset($pagination->offset)
            ->limit($pagination->limit)
            ->all();

        // Список администраторов для фильтра
        $adminUsers = User::find()
            ->select(['id', 'username'])
            ->orderBy('username')
            ->asArray()
            ->all();

        return $this->render('index', [
            'logs'        => $logs,
            'pagination'  => $pagination,
            'adminUsers'  => $adminUsers,
            'totalCount'  => $total,
            'filterValues' => [
                'period'      => $period,
                'start_date'  => $startDate,
                'end_date'    => $endDate,
                'user_id'     => $userId,
                'target_type' => $targetType,
                'action'      => $action,
                'source'      => $source,
                'search'      => $search,
            ],
            'targetTypes' => self::getTargetTypes(),
            'actions'     => self::getActions(),
            'sources'     => self::getSources(),
        ]);
    }

    public function actionExportCsv()
    {
        $req = Yii::$app->request;

        $period     = $req->get('period', self::PERIOD_TODAY);
        $startDate  = $req->get('start_date', '');
        $endDate    = $req->get('end_date', '');
        $userId     = (int)$req->get('user_id', 0);
        $targetType = $req->get('target_type', '');
        $action     = $req->get('action', '');
        $source     = $req->get('source', '');
        $search     = trim($req->get('search', ''));

        $rows = $this->buildQuery($period, $startDate, $endDate, $userId, $targetType, $action, $source, $search)
            ->orderBy(['created_at' => SORT_DESC])
            ->limit(10000)
            ->asArray()
            ->all();

        $filename = 'activity_log_' . date('Ymd_His') . '.csv';

        $response = Yii::$app->response;
        $response->format = \yii\web\Response::FORMAT_RAW;
        $response->headers->set('Content-Type', 'text/csv; charset=utf-8');
        $response->headers->set('Content-Disposition', 'attachment; filename="' . $filename . '"');

        ob_start();
        $fh = fopen('php://output', 'w');
        fputs($fh, "\xEF\xBB\xBF"); // UTF-8 BOM для Excel

        fputcsv($fh, ['ID', 'Дата', 'Пользователь', 'Роль', 'Действие', 'Тип', 'ID объекта', 'Объект', 'Изменения', 'Источник', 'IP'], ';');

        foreach ($rows as $row) {
            fputcsv($fh, [
                $row['id'],
                date('d.m.Y H:i:s', $row['created_at']),
                $row['user_name'] ?? '',
                $row['user_role'] ?? '',
                $row['action'],
                $row['target_type'],
                $row['target_id'] ?? '',
                $row['target_label'] ?? '',
                $row['changes'] ?? '',
                $row['source'],
                $row['ip'] ?? '',
            ], ';');
        }

        fclose($fh);
        $response->content = ob_get_clean();
        return $response;
    }

    /**
     * CLI: удаление логов старше 90 дней.
     * php yii activity-log/cleanup
     */
    public function actionCleanup()
    {
        $cutoff  = time() - 90 * 86400;
        $deleted = Yii::$app->db->createCommand()
            ->delete('activity_log', 'created_at < :ts', [':ts' => $cutoff])
            ->execute();

        echo "Удалено $deleted записей старше 90 дней.\n";
    }

    // ── Query builder ─────────────────────────────────────────────────────────

    private function buildQuery(
        string $period,
        string $startDate,
        string $endDate,
        int    $userId,
        string $targetType,
        string $action,
        string $source,
        string $search
    ): \yii\db\Query {
        $query = (new \yii\db\Query())
            ->from('activity_log');

        // Период
        if ($period === self::PERIOD_TODAY) {
            $query->andWhere(['>=', 'created_at', strtotime('today')]);
        } elseif ($period === self::PERIOD_WEEK) {
            $query->andWhere(['>=', 'created_at', strtotime('-7 days')]);
        } elseif ($period === self::PERIOD_MONTH) {
            $query->andWhere(['>=', 'created_at', strtotime('-30 days')]);
        } elseif ($period === 'custom') {
            if ($startDate) {
                $query->andWhere(['>=', 'created_at', strtotime($startDate . ' 00:00:00')]);
            }
            if ($endDate) {
                $query->andWhere(['<=', 'created_at', strtotime($endDate . ' 23:59:59')]);
            }
        }

        if ($userId > 0) {
            $query->andWhere(['user_id' => $userId]);
        }
        if ($targetType) {
            $query->andWhere(['target_type' => $targetType]);
        }
        if ($action) {
            $query->andWhere(['action' => $action]);
        }
        if ($source) {
            $query->andWhere(['source' => $source]);
        }
        if ($search !== '') {
            $query->andWhere(['like', 'target_label', $search]);
        }

        return $query;
    }
}
