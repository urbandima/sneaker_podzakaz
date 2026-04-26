<?php
namespace app\backend\shared\services;

use Yii;
use yii\db\Query;

/**
 * Centralized revenue queries — single source of truth for all SUM(total_amount) calculations.
 *
 * Excluded statuses (Z40/Z43): cancelled, canceled, trash, return, imported, imported_invalid
 */
class RevenueService
{
    const EXCLUDED_STATUSES = ['cancelled', 'canceled', 'trash', 'return', 'imported', 'imported_invalid'];

    /**
     * Total revenue for a Unix-timestamp range.
     *
     * @param int|null $from  Unix timestamp (inclusive); null = no lower bound
     * @param int|null $to    Unix timestamp (exclusive); null = no upper bound
     * @return float
     */
    public static function getTotal(?int $from = null, ?int $to = null): float
    {
        $q = (new Query())
            ->select(['SUM(total_amount)'])
            ->from('`order`')
            ->where(['NOT IN', 'status', self::EXCLUDED_STATUSES]);

        if ($from !== null) $q->andWhere(['>=', 'created_at', $from]);
        if ($to   !== null) $q->andWhere(['<',  'created_at', $to]);

        return (float)($q->scalar() ?: 0);
    }

    /**
     * Revenue grouped by calendar day for the last N days.
     *
     * Returns array of ['date' => 'YYYY-MM-DD', 'revenue' => float]
     */
    public static function getByDay(int $days = 30): array
    {
        $from = strtotime("-{$days} days midnight");

        return (new Query())
            ->select([
                'DATE(FROM_UNIXTIME(created_at)) AS date',
                'COALESCE(SUM(total_amount), 0)  AS revenue',
            ])
            ->from('`order`')
            ->where(['NOT IN', 'status', self::EXCLUDED_STATUSES])
            ->andWhere(['>=', 'created_at', $from])
            ->groupBy('date')
            ->orderBy('date')
            ->all();
    }

    /**
     * Revenue grouped by month for a specific year.
     *
     * Returns array indexed by month number (1–12): ['revenue' => float]
     */
    public static function getByMonth(int $year): array
    {
        $rows = (new Query())
            ->select([
                'MONTH(FROM_UNIXTIME(created_at)) AS month',
                'SUM(total_amount)                AS revenue',
            ])
            ->from('`order`')
            ->where(['NOT IN', 'status', self::EXCLUDED_STATUSES])
            ->andWhere(['YEAR(FROM_UNIXTIME(created_at))' => $year])
            ->groupBy('month')
            ->indexBy('month')
            ->all();

        $result = [];
        for ($m = 1; $m <= 12; $m++) {
            $result[$m] = ['revenue' => (float)($rows[$m]['revenue'] ?? 0)];
        }
        return $result;
    }

    /**
     * Revenue for today (midnight to now).
     */
    public static function getToday(): float
    {
        return self::getTotal(strtotime('today'), strtotime('tomorrow'));
    }

    /**
     * Revenue for the current calendar month.
     */
    public static function getThisMonth(): float
    {
        return self::getTotal(strtotime('first day of this month midnight'));
    }
}
