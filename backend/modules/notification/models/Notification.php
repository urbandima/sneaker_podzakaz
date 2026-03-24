<?php

namespace app\backend\modules\notification\models;

use Yii;
use yii\db\ActiveRecord;

/**
 * Notification — Модель уведомления
 * 
 * @property int $id
 * @property int $customer_id
 * @property string $type
 * @property string $title
 * @property string $message
 * @property string $data
 * @property int $is_read
 * @property string $created_at
 * @property string $read_at
 */
class Notification extends ActiveRecord
{
    public static function tableName()
    {
        return '{{%notification}}';
    }

    public function rules()
    {
        return [
            [['customer_id', 'type', 'title', 'message'], 'required'],
            [['customer_id', 'is_read'], 'integer'],
            [['message', 'data'], 'string'],
            [['created_at', 'read_at'], 'safe'],
            [['type'], 'string', 'max' => 50],
            [['title'], 'string', 'max' => 255],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'customer_id' => 'Покупатель',
            'type' => 'Тип',
            'title' => 'Заголовок',
            'message' => 'Сообщение',
            'data' => 'Данные',
            'is_read' => 'Прочитано',
            'created_at' => 'Создано',
            'read_at' => 'Прочитано',
        ];
    }

    public function getCustomer()
    {
        return $this->hasOne(\app\backend\modules\account\models\Customer::class, ['id' => 'customer_id']);
    }

    /**
     * Получить данные как массив
     */
    public function getDataArray(): array
    {
        return $this->data ? json_decode($this->data, true) : [];
    }
}
