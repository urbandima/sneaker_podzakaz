<?php

/**
 * OrderStatus — Модель статуса заказа
 * 
 * НАЗНАЧЕНИЕ:
 * Справочник статусов заказов: названия, сортировка,
 * доступность для логистов. Настраиваемый список статусов.
 * 
 * ОСНОВНЫЕ СВОЙСТВА:
 * - key: ключ статуса (new, paid, processing, etc.)
 * - label: название для отображения
 * - sort: порядок сортировки
 * - logist_available: доступен для логистов
 * 
 * ИСПОЛЬЗОВАНИЕ:
 * - OrderController/admin (выбор статуса)
 * - Фильтрация по статусам
 * - Настройка workflow заказов
 * 
 * ОСОБЕННОСТИ:
 * - Настраиваемый справочник
 * - Контроль доступности для ролей
 */
namespace app\backend\modules\checkout\models;

use yii\db\ActiveRecord;

class OrderStatus extends ActiveRecord
{
    public static function tableName()
    {
        return '{{%order_status}}';
    }

    public function rules()
    {
        return [
            [['key', 'label'], 'required'],
            [['key'], 'string', 'max' => 50],
            [['label'], 'string', 'max' => 100],
            [['sort'], 'integer'],
            [['logist_available'], 'boolean'],
            [['key'], 'unique'],
        ];
    }
}
