<?php

namespace app\backend\modules\checkout\models;

use Yii;
use yii\db\ActiveRecord;
use yii\behaviors\TimestampBehavior;
use app\backend\modules\admin\models\User;

class OrderHistory extends ActiveRecord
{
    public static function tableName()
    {
        return 'order_history';
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
            [['order_id'], 'required'],
            [['order_id', 'changed_by'], 'integer'],
            [['old_status', 'new_status'], 'string', 'max' => 50],
            [['action', 'field_name'], 'string', 'max' => 100],
            [['user_name', 'user_role'], 'string', 'max' => 255],
            [['ip_address'], 'string', 'max' => 45],
            [['comment', 'old_value', 'new_value'], 'string'],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id'         => 'ID',
            'order_id'   => 'Заказ',
            'action'     => 'Действие',
            'field_name' => 'Поле',
            'old_status' => 'Старый статус',
            'new_status' => 'Новый статус',
            'old_value'  => 'Старое значение',
            'new_value'  => 'Новое значение',
            'comment'    => 'Комментарий',
            'changed_by' => 'Изменил (ID)',
            'user_name'  => 'Изменил',
            'ip_address' => 'IP',
            'created_at' => 'Дата',
        ];
    }

    /**
     * Universal helper to record any order change.
     */
    public static function log(
        int $orderId,
        string $action,
        ?string $fieldName,
        mixed $oldValue,
        mixed $newValue,
        ?string $comment = null
    ): void {
        $user = Yii::$app->user;

        $h = new self();
        $h->order_id   = $orderId;
        $h->action     = $action;
        $h->field_name = $fieldName;
        $h->old_value  = is_array($oldValue) ? json_encode($oldValue, JSON_UNESCAPED_UNICODE) : (string)$oldValue;
        $h->new_value  = is_array($newValue) ? json_encode($newValue, JSON_UNESCAPED_UNICODE) : (string)$newValue;
        $h->changed_by = $user->isGuest ? null : $user->id;
        $h->user_name  = $user->isGuest ? null : ($user->identity->username ?? null);
        $h->ip_address = Yii::$app->request->userIP ?? null;
        $h->comment    = $comment;

        if (!$user->isGuest) {
            try {
                $roles = Yii::$app->authManager->getRolesByUser($user->id);
                $h->user_role = $roles ? implode(',', array_keys($roles)) : ($user->identity->role ?? null);
            } catch (\Throwable $e) {
                $h->user_role = $user->identity->role ?? null;
            }
        }

        if ($action === 'status_changed') {
            $h->old_status = (string)$oldValue;
            $h->new_status = (string)$newValue;
        }

        $h->save(false);
    }

    public function getOldStatusLabel(): string
    {
        if (!$this->old_status) {
            return 'Создан';
        }
        $statuses = Yii::$app->settings->getStatuses();
        return $statuses[$this->old_status] ?? $this->old_status;
    }

    public function getNewStatusLabel(): string
    {
        if (!$this->new_status) return '—';
        $statuses = Yii::$app->settings->getStatuses();
        return $statuses[$this->new_status] ?? $this->new_status;
    }

    public function getActionLabel(): string
    {
        $labels = [
            'status_changed' => 'Смена статуса',
            'field_changed'  => 'Изменение поля',
            'note_added'     => 'Заметка',
            'item_added'     => 'Товар добавлен',
            'item_removed'   => 'Товар удалён',
            'item_updated'   => 'Товар изменён',
            'sent_to_dp'     => 'Отправлено в ТД',
            'created'        => 'Создан',
        ];
        return $labels[$this->action ?? 'status_changed'] ?? ($this->action ?? 'Изменение');
    }

    public function getOrder()
    {
        return $this->hasOne(Order::class, ['id' => 'order_id']);
    }

    public function getChanger()
    {
        return $this->hasOne(User::class, ['id' => 'changed_by']);
    }
}
