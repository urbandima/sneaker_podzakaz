<?php

namespace app\backend\modules\procurement\models;

use yii\db\ActiveRecord;

class BuyoutHistory extends ActiveRecord
{
    public static function tableName(): string
    {
        return '{{%buyout_history}}';
    }

    public function rules(): array
    {
        return [
            [['buyout_id', 'action'], 'required'],
            [['buyout_id', 'user_id'], 'integer'],
            [['action'], 'string', 'max' => 100],
            [['old_value', 'new_value'], 'safe'],
        ];
    }

    public function attributeLabels(): array
    {
        return [
            'id'         => 'ID',
            'buyout_id'  => 'Выкуп',
            'user_id'    => 'Пользователь',
            'action'     => 'Действие',
            'old_value'  => 'Было',
            'new_value'  => 'Стало',
            'created_at' => 'Дата',
        ];
    }

    public static function getActionLabels(): array
    {
        return [
            'status_changed'  => 'Смена статуса',
            'order_linked'    => 'Привязан заказ',
            'order_unlinked'  => 'Отвязан заказ',
            'note_added'      => 'Добавлена заметка',
            'receipt_added'   => 'Загружен чек',
            'tracking_added'  => 'Добавлен трек',
            'receiving_created' => 'Создана приёмка',
        ];
    }

    public function getActionLabel(): string
    {
        return static::getActionLabels()[$this->action] ?? $this->action;
    }

    public function getBuyout()
    {
        return $this->hasOne(Buyout::class, ['id' => 'buyout_id']);
    }
}
