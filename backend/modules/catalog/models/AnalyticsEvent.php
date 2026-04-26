<?php

/**
 * AnalyticsEvent — Временная заглушка для модели аналитики
 * 
 * НАЗНАЧЕНИЕ:
 * Временная реализация для предотвращения ошибок.
 * TODO: Создать полную реализацию модели.
 */
namespace app\backend\modules\catalog\models;

use Yii;
use yii\db\ActiveRecord;

class AnalyticsEvent extends ActiveRecord
{
    public static function tableName()
    {
        return '{{%analytics_event}}';
    }
    
    /**
     * Проверить доступность таблицы аналитики
     * 
     * @return bool
     */
    public static function isAvailable(): bool
    {
        try {
            // Проверяем существование таблицы
            $tableSchema = Yii::$app->db->schema->getTableSchema('{{%analytics_event}}');
            return $tableSchema !== null;
        } catch (\Exception $e) {
            return false;
        }
    }
    
    public static function getConversionStats($dateFrom, $dateTo)
    {
        if (!self::isAvailable()) {
            return ['conversion_rate' => 0, 'total_visitors' => 0, 'total_conversions' => 0];
        }
        try {
            $db = Yii::$app->db;
            $views  = (int)$db->createCommand(
                "SELECT COUNT(*) FROM analytics_event WHERE event_type IN ('view','product_view','page_view') AND DATE(created_at) BETWEEN :f AND :t",
                [':f' => $dateFrom, ':t' => $dateTo]
            )->queryScalar();
            $orders = (int)$db->createCommand(
                "SELECT COUNT(*) FROM analytics_event WHERE event_type IN ('order','order_created') AND DATE(created_at) BETWEEN :f AND :t",
                [':f' => $dateFrom, ':t' => $dateTo]
            )->queryScalar();
            return [
                'total_visitors' => $views,
                'total_conversions' => $orders,
                'conversion_rate' => $views > 0 ? round($orders / $views, 4) : 0,
            ];
        } catch (\Exception $e) {
            return ['conversion_rate' => 0, 'total_visitors' => 0, 'total_conversions' => 0];
        }
    }

    public static function getTrafficSources($dateFrom, $dateTo)
    {
        if (!self::isAvailable()) {
            return [];
        }
        try {
            $rows = Yii::$app->db->createCommand(
                "SELECT COALESCE(source, 'direct') as source, COUNT(*) as cnt
                 FROM analytics_event
                 WHERE event_type = 'page_view' AND DATE(created_at) BETWEEN :f AND :t
                 GROUP BY source ORDER BY cnt DESC LIMIT 10",
                [':f' => $dateFrom, ':t' => $dateTo]
            )->queryAll();
            $total = array_sum(array_column($rows, 'cnt')) ?: 1;
            $result = [];
            foreach ($rows as $row) {
                $result[$row['source']] = [
                    'count' => (int)$row['cnt'],
                    'percentage' => round($row['cnt'] / $total * 100, 1),
                ];
            }
            return $result;
        } catch (\Exception $e) {
            return [];
        }
    }

    public static function getPopularProducts($limit, $dateFrom, $dateTo)
    {
        try {
            // Try real analytics_event data first
            if (self::isAvailable()) {
                $rows = Yii::$app->db->createCommand(
                    "SELECT ae.product_id,
                            COALESCE(p.name, ae.product_id) as product_name,
                            SUM(CASE WHEN ae.event_type IN ('view','product_view') THEN 1 ELSE 0 END) AS views,
                            SUM(CASE WHEN ae.event_type = 'add_to_cart' THEN 1 ELSE 0 END) AS add_to_cart,
                            SUM(CASE WHEN ae.event_type IN ('order','order_created') THEN 1 ELSE 0 END) AS orders
                     FROM analytics_event ae
                     LEFT JOIN product p ON p.id = ae.product_id
                     WHERE ae.product_id IS NOT NULL
                       AND DATE(ae.created_at) BETWEEN :f AND :t
                     GROUP BY ae.product_id
                     ORDER BY views DESC
                     LIMIT :lim",
                    [':f' => $dateFrom, ':t' => $dateTo, ':lim' => $limit]
                )->queryAll();
                if (!empty($rows)) {
                    return $rows;
                }
            }
            // Fallback: top products by order count (Z78: price>0, FROM_UNIXTIME for unix ts)
            return Yii::$app->db->createCommand(
                "SELECT oi.product_name,
                        0 AS views, 0 AS add_to_cart,
                        COUNT(DISTINCT oi.order_id) AS orders,
                        SUM(oi.price * oi.quantity) AS total_revenue
                 FROM order_item oi
                 JOIN \`order\` o ON oi.order_id = o.id
                 WHERE DATE(FROM_UNIXTIME(o.created_at)) BETWEEN :f AND :t
                   AND oi.price IS NOT NULL AND oi.price > 0
                 GROUP BY oi.product_name
                 ORDER BY orders DESC
                 LIMIT :lim",
                [':f' => $dateFrom, ':t' => $dateTo, ':lim' => $limit]
            )->queryAll();
        } catch (\Exception $e) {
            return [];
        }
    }

    // A15: track an analytics event; silently no-ops if table doesn't exist
    public static function track(string $event, $productId = null, array $extra = []): void
    {
        if (!static::isAvailable()) {
            return;
        }
        try {
            $customerId = null;
            $sessionId  = null;
            if (!Yii::$app->user->isGuest) {
                $customerId = Yii::$app->user->id;
            } else {
                $sessionId = Yii::$app->session->id;
            }
            Yii::$app->db->createCommand()->insert('{{%analytics_event}}', [
                'event_type'  => $event,
                'entity_type' => $productId ? 'product' : null,
                'entity_id'   => $productId ?: null,
                'user_id'     => $customerId,
                'session_id'  => $sessionId,
                'meta_json'   => $extra ? json_encode($extra, JSON_UNESCAPED_UNICODE) : null,
                'created_at'  => date('Y-m-d H:i:s'),
            ])->execute();
        } catch (\Exception $e) {
            Yii::warning('AnalyticsEvent::track failed: ' . $e->getMessage(), 'analytics');
        }
    }
}
