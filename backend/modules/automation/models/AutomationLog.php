<?php

namespace app\backend\modules\automation\models;

use yii\db\ActiveRecord;

/**
 * @property int    $id
 * @property int    $trigger_id
 * @property string $event_code
 * @property int    $order_id
 * @property int    $customer_id
 * @property int    $conditions_met
 * @property string $actions_executed  JSON
 * @property int    $execution_time_ms
 * @property string $created_at
 *
 * @property AutomationTrigger $trigger
 */
class AutomationLog extends ActiveRecord
{
    public static function tableName(): string
    {
        return '{{%automation_log}}';
    }

    public function rules(): array
    {
        return [
            [['trigger_id'], 'required'],
            [['trigger_id', 'order_id', 'customer_id', 'execution_time_ms'], 'integer'],
            [['conditions_met'], 'boolean'],
            [['event_code'], 'string', 'max' => 100],
            [['actions_executed'], 'string'],
            [['created_at'], 'safe'],
        ];
    }

    public function getTrigger(): \yii\db\ActiveQuery
    {
        return $this->hasOne(AutomationTrigger::class, ['id' => 'trigger_id']);
    }

    public function getActionsArray(): array
    {
        if (empty($this->actions_executed)) return [];
        $decoded = json_decode($this->actions_executed, true);
        return is_array($decoded) ? $decoded : [];
    }

    public function getStatusSummary(): string
    {
        $actions = $this->getActionsArray();
        if (empty($actions)) return '—';
        $statuses = array_column($actions, 'status');
        if (in_array('error', $statuses)) return 'ошибка';
        if (in_array('success', $statuses)) return 'успех';
        return 'выполнено';
    }
}
