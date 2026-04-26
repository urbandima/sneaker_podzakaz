<?php

namespace app\backend\shared\services;

use Yii;

class RevenueService
{
    public function getTotalRevenue(\DateTime $from, \DateTime $to, array $statuses = []): float
    {
        $query = (new \yii\db\Query())
            ->select(['SUM(o.total_amount)'])
            ->from('{{%order}} o')
            ->where(['>=', 'o.created_at', $from->getTimestamp()])
            ->andWhere(['<=', 'o.created_at', $to->getTimestamp()]);

        if (!empty($statuses)) {
            $query->andWhere(['o.status' => $statuses]);
        }

        return (float) ($query->scalar() ?? 0);
    }

    public function getRevenueByDay(\DateTime $from, \DateTime $to, array $statuses = []): array
    {
        $query = (new \yii\db\Query())
            ->select([
                "DATE(FROM_UNIXTIME(o.created_at)) AS day",
                "SUM(o.total_amount) AS revenue",
                "COUNT(*) AS orders",
            ])
            ->from('{{%order}} o')
            ->where(['>=', 'o.created_at', $from->getTimestamp()])
            ->andWhere(['<=', 'o.created_at', $to->getTimestamp()])
            ->groupBy('day')
            ->orderBy('day');

        if (!empty($statuses)) {
            $query->andWhere(['o.status' => $statuses]);
        }

        $rows = $query->all();
        $result = [];
        foreach ($rows as $row) {
            $result[$row['day']] = [
                'revenue' => (float) $row['revenue'],
                'orders'  => (int)   $row['orders'],
            ];
        }
        return $result;
    }

    public function getRevenueByMonth(int $year, array $statuses = []): array
    {
        $query = (new \yii\db\Query())
            ->select([
                "MONTH(FROM_UNIXTIME(o.created_at)) AS month",
                "SUM(o.total_amount) AS revenue",
                "COUNT(*) AS orders",
            ])
            ->from('{{%order}} o')
            ->where([
                'AND',
                ['>=', 'o.created_at', mktime(0, 0, 0, 1,  1, $year)],
                ['<=', 'o.created_at', mktime(23, 59, 59, 12, 31, $year)],
            ])
            ->groupBy('month')
            ->orderBy('month');

        if (!empty($statuses)) {
            $query->andWhere(['o.status' => $statuses]);
        }

        $rows = $query->all();
        $result = [];
        foreach ($rows as $row) {
            $result[(int) $row['month']] = [
                'revenue' => (float) $row['revenue'],
                'orders'  => (int)   $row['orders'],
            ];
        }
        return $result;
    }
}
