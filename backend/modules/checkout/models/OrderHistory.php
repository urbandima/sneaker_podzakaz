<?php

/**
 * OrderHistory — Модель истории изменений заказа
 * 
 * НАЗНАЧЕНИЕ:
 * Логирование всех изменений статуса заказа: кто изменил,
 * какой статус был, какой стал, комментарий.
 * 
 * ОСНОВНЫЕ СВОЙСТВА:
 * - order_id: ID заказа
 * - old_status: предыдущий статус
 * - new_status: новый статус
 * - changed_by: ID пользователя, изменившего статус
 * - comment: комментарий к изменению
 * 
 * СВЯЗИ:
 * - Order (принадлежит заказу)
 * - User (изменивший пользователь)
 * 
 * ИСПОЛЬЗОВАНИЕ:
 * - OrderController/admin (просмотр истории заказа)
 * - Автоматическое создание при смене статуса
 */
namespace app\backend\modules\checkout\models;

use Yii;
use yii\db\ActiveRecord;
use yii\behaviors\TimestampBehavior;
use app\backend\modules\admin\models\User;

class OrderHistory extends ActiveRecord
{
    public static function tableName()
    {
        return '{{%order_history}}';
    }

    public function behaviors()
    {
        return [
            [
                'class' => TimestampBehavior::class,
                'updatedAtAttribute' => false,
            ],
        ];
    }

    public function rules()
    {
        return [
            [['order_id', 'new_status'], 'required'],
            [['order_id', 'changed_by'], 'integer'],
            [['old_status', 'new_status'], 'string', 'max' => 50],
            [['comment'], 'string'],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'order_id' => 'Заказ',
            'old_status' => 'Старый статус',
            'new_status' => 'Новый статус',
            'comment' => 'Комментарий',
            'changed_by' => 'Изменил',
            'created_at' => 'Дата изменения',
        ];
    }

    public function getOldStatusLabel()
    {
        if (!$this->old_status) {
            return 'Создан';
        }
        $statuses = Yii::$app->settings->getStatuses();
        return $statuses[$this->old_status] ?? $this->old_status;
    }

    public function getNewStatusLabel()
    {
        $statuses = Yii::$app->settings->getStatuses();
        return $statuses[$this->new_status] ?? $this->new_status;
    }

    // Relations
    public function getOrder()
    {
        return $this->hasOne(Order::class, ['id' => 'order_id']);
    }

    public function getChanger()
    {
        return $this->hasOne(User::class, ['id' => 'changed_by']);
    }
}
