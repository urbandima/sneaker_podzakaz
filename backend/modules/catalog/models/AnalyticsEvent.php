<?php

/**
 * AnalyticsEvent — Временная заглушка для модели аналитики
 * 
 * НАЗНАЧЕНИЕ:
 * Временная реализация для предотвращения ошибок.
 * TODO: Создать полную реализацию модели.
 */
namespace app\backend\modules\catalog\models;

use yii\db\ActiveRecord;

class AnalyticsEvent extends ActiveRecord
{
    public static function tableName()
    {
        return '{{%analytics_event}}';
    }
    
    public static function getConversionStats($dateFrom, $dateTo)
    {
        // Временная заглушка
        return [
            'conversion_rate' => 0.05,
            'total_visitors' => 1000,
            'total_conversions' => 50,
        ];
    }

    public static function getTrafficSources($dateFrom, $dateTo)
    {
        // Временная заглушка
        return [
            'direct' => ['count' => 300, 'percentage' => 30],
            'organic' => ['count' => 400, 'percentage' => 40],
            'social' => ['count' => 200, 'percentage' => 20],
            'referral' => ['count' => 100, 'percentage' => 10],
        ];
    }

    public static function getPopularProducts($limit, $dateFrom, $dateTo)
    {
        // Временная заглушка
        return [
            ['product_id' => 1, 'product_name' => 'Nike Air Jordan 1', 'views' => 150, 'orders' => 20],
            ['product_id' => 2, 'product_name' => 'Nike Dunk Low', 'views' => 120, 'orders' => 15],
            ['product_id' => 3, 'product_name' => 'Nike Air Max 90', 'views' => 100, 'orders' => 12],
        ];
    }
}
