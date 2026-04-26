<?php

namespace app\backend\modules\procurement\models;

use yii\db\ActiveRecord;

class ReceivingHistory extends ActiveRecord
{
    public static function tableName(): string
    {
        return '{{%receiving_history}}';
    }

    public function rules(): array
    {
        return [
            [['receiving_id', 'to_status', 'changed_at'], 'required'],
            [['receiving_id', 'changed_by', 'changed_at'], 'integer'],
            [['from_status', 'to_status'], 'string', 'max' => 32],
            [['comment'], 'string'],
        ];
    }

    public function getReceiving()
    {
        return $this->hasOne(Receiving::class, ['id' => 'receiving_id']);
    }

    public function getStatusLabel(): string
    {
        $statuses = Receiving::getStatuses();
        $from = $this->from_status ? ($statuses[$this->from_status] ?? $this->from_status) : '—';
        $to   = $statuses[$this->to_status] ?? $this->to_status;
        return "{$from} → {$to}";
    }
}
