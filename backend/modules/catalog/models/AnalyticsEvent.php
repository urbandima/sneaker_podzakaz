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
}
