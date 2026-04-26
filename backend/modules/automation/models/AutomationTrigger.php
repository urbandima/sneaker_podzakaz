<?php

namespace app\backend\modules\automation\models;

use Yii;
use yii\db\ActiveRecord;
use yii\behaviors\TimestampBehavior;

/**
 * @property int    $id
 * @property string $name
 * @property string $description
 * @property string $event_code
 * @property string $conditions   JSON
 * @property string $actions      JSON
 * @property int    $is_active
 * @property int    $priority
 * @property int    $execution_count
 * @property string $last_executed_at
 * @property int    $created_at
 * @property int    $updated_at
 *
 * @property AutomationLog[] $logs
 */
class AutomationTrigger extends ActiveRecord
{
    public static function tableName(): string
    {
        return '{{%automation_trigger}}';
    }

    public function behaviors(): array
    {
        return [TimestampBehavior::class];
    }

    public function rules(): array
    {
        return [
            [['name', 'event_code', 'actions'], 'required'],
            [['name'], 'string', 'max' => 255],
            [['description'], 'string'],
            [['event_code'], 'string', 'max' => 100],
            [['conditions', 'actions'], 'string'],
            [['is_active'], 'boolean'],
            [['priority', 'execution_count'], 'integer'],
            [['last_executed_at'], 'safe'],
            [['conditions'], 'validateSmsConditions'],
        ];
    }

    public function validateSmsConditions(string $attribute): void
    {
        $acts = $this->getActionsArray();
        $hasSms = false;
        foreach ($acts as $act) {
            $type = $act['type'] ?? $act['action_type'] ?? '';
            if (in_array($type, ['sms', 'telegram_message', 'send_sms'], true)) {
                $hasSms = true;
                break;
            }
        }
        if ($hasSms && empty($this->getConditionsArray())) {
            $this->addError($attribute, 'SMS/Telegram-триггер без условий разошлёт сообщение всем. Добавьте хотя бы одно условие.');
        }
    }

    public function attributeLabels(): array
    {
        return [
            'id'              => 'ID',
            'name'            => 'Название',
            'description'     => 'Описание',
            'event_code'      => 'Событие',
            'conditions'      => 'Условия',
            'actions'         => 'Действия',
            'is_active'       => 'Активен',
            'priority'        => 'Приоритет',
            'execution_count' => 'Выполнений',
            'last_executed_at'=> 'Последнее выполнение',
            'created_at'      => 'Создан',
            'updated_at'      => 'Обновлён',
        ];
    }

    public function getConditionsArray(): array
    {
        if (empty($this->conditions)) return [];
        if (is_array($this->conditions)) return $this->conditions;
        $decoded = json_decode($this->conditions, true);
        return is_array($decoded) ? $decoded : [];
    }

    public function getActionsArray(): array
    {
        if (empty($this->actions)) return [];
        if (is_array($this->actions)) return $this->actions;
        $decoded = json_decode($this->actions, true);
        return is_array($decoded) ? $decoded : [];
    }

    public function getLogs(): \yii\db\ActiveQuery
    {
        return $this->hasMany(AutomationLog::class, ['trigger_id' => 'id'])
            ->orderBy(['created_at' => SORT_DESC]);
    }

    /** Human-readable event label */
    public function getEventLabel(): string
    {
        return self::getEventCodes()[$this->event_code] ?? $this->event_code;
    }

    public static function getEventCodes(): array
    {
        return [
            'order.created'           => 'Заказ создан',
            'order.status_changed'    => 'Статус заказа изменён',
            'order.payment_uploaded'  => 'Скриншот оплаты загружен',
            'order.payment_confirmed' => 'Оплата подтверждена',
            'order.passport_submitted'=> 'Паспортные данные заполнены',
            'order.sent_to_dp'        => 'Заказ отправлен в Таможня:ДП',
            'order.dp_status_changed' => 'Статус ДП изменён',
            'order.local_track_added' => 'Добавлен локальный трек',
            'order.delivered'         => 'Заказ доставлен',
            'customer.created'        => 'Клиент создан',
            'customer.bonus_earned'   => 'Начислены бонусы',
            'product.low_stock'       => 'Мало товара на складе',
        ];
    }

    public static function getOperators(): array
    {
        return [
            'equals'       => 'равно (=)',
            'not_equals'   => 'не равно (≠)',
            'greater_than' => 'больше (>)',
            'less_than'    => 'меньше (<)',
            'contains'     => 'содержит',
            'is_empty'     => 'пусто',
            'is_not_empty' => 'не пусто',
            'in_list'      => 'в списке',
        ];
    }

    public static function getActionTypes(): array
    {
        return [
            'send_sms'       => 'Отправить SMS',
            'send_email'     => 'Отправить Email',
            'send_telegram'  => 'Отправить в Telegram',
            'sync_moysklad'  => 'Синхронизировать с МойСклад',
            'change_status'  => 'Изменить статус заказа',
            'assign_tag'     => 'Присвоить тег клиенту',
            'earn_bonus'     => 'Начислить бонусы',
            'create_customer'=> 'Создать клиента',
            'send_to_dp'     => 'Отправить в Таможня:ДП',
            'notify_admin'   => 'Уведомить администратора',
            'webhook'        => 'Вызвать Webhook',
        ];
    }
}
