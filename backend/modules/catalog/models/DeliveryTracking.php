<?php

namespace app\models;

use Yii;
use yii\db\ActiveRecord;

/**
 * Модель отслеживания доставок
 *
 * @property int $id
 * @property int $order_id
 * @property string|null $tracking_number
 * @property string|null $carrier
 * @property string $status
 * @property string|null $status_description
 * @property string|null $location
 * @property string|null $estimated_delivery
 * @property string|null $actual_delivery
 * @property string|null $events_json
 * @property string|null $last_check_at
 * @property string $created_at
 * @property string $updated_at
 *
 * @property Order $order
 */
class DeliveryTracking extends ActiveRecord
{
    const STATUS_PENDING = 'pending';
    const STATUS_PICKED_UP = 'picked_up';
    const STATUS_IN_TRANSIT = 'in_transit';
    const STATUS_CUSTOMS = 'customs';
    const STATUS_OUT_FOR_DELIVERY = 'out_for_delivery';
    const STATUS_DELIVERED = 'delivered';
    const STATUS_FAILED = 'failed';
    const STATUS_RETURNED = 'returned';

    public static function tableName()
    {
        return '{{%delivery_tracking}}';
    }

    public function rules()
    {
        return [
            [['order_id'], 'required'],
            [['order_id'], 'integer'],
            [['events_json'], 'string'],
            [['estimated_delivery', 'actual_delivery', 'last_check_at', 'created_at', 'updated_at'], 'safe'],
            [['tracking_number', 'carrier'], 'string', 'max' => 100],
            [['status'], 'string', 'max' => 50],
            [['status_description', 'location'], 'string', 'max' => 255],
            [['status'], 'default', 'value' => self::STATUS_PENDING],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'order_id' => 'Заказ',
            'tracking_number' => 'Трек-номер',
            'carrier' => 'Перевозчик',
            'status' => 'Статус',
            'status_description' => 'Описание статуса',
            'location' => 'Местоположение',
            'estimated_delivery' => 'Ожидаемая доставка',
            'actual_delivery' => 'Фактическая доставка',
            'last_check_at' => 'Последняя проверка',
        ];
    }

    public function getOrder()
    {
        return $this->hasOne(Order::class, ['id' => 'order_id']);
    }

    /**
     * Получить события трекинга
     */
    public function getEvents()
    {
        if (empty($this->events_json)) {
            return [];
        }
        return json_decode($this->events_json, true) ?: [];
    }

    /**
     * Добавить событие трекинга
     */
    public function addEvent($status, $description, $location = null)
    {
        $events = $this->getEvents();
        $events[] = [
            'status' => $status,
            'description' => $description,
            'location' => $location,
            'timestamp' => date('Y-m-d H:i:s'),
        ];
        $this->events_json = json_encode($events, JSON_UNESCAPED_UNICODE);
        $this->status = $status;
        $this->status_description = $description;
        $this->location = $location;
        $this->last_check_at = date('Y-m-d H:i:s');
        
        if ($status === self::STATUS_DELIVERED) {
            $this->actual_delivery = date('Y-m-d');
        }
        
        return $this->save();
    }

    /**
     * Список статусов
     */
    public static function getStatusList()
    {
        return [
            self::STATUS_PENDING => 'Ожидание',
            self::STATUS_PICKED_UP => 'Забран',
            self::STATUS_IN_TRANSIT => 'В пути',
            self::STATUS_CUSTOMS => 'Таможня',
            self::STATUS_OUT_FOR_DELIVERY => 'Доставляется',
            self::STATUS_DELIVERED => 'Доставлен',
            self::STATUS_FAILED => 'Ошибка доставки',
            self::STATUS_RETURNED => 'Возврат',
        ];
    }

    /**
     * Получить название статуса
     */
    public function getStatusName()
    {
        return self::getStatusList()[$this->status] ?? $this->status;
    }

    /**
     * CSS класс для статуса
     */
    public function getStatusClass()
    {
        $classes = [
            self::STATUS_PENDING => 'secondary',
            self::STATUS_PICKED_UP => 'info',
            self::STATUS_IN_TRANSIT => 'primary',
            self::STATUS_CUSTOMS => 'warning',
            self::STATUS_OUT_FOR_DELIVERY => 'info',
            self::STATUS_DELIVERED => 'success',
            self::STATUS_FAILED => 'danger',
            self::STATUS_RETURNED => 'dark',
        ];
        return $classes[$this->status] ?? 'secondary';
    }

    /**
     * Список перевозчиков
     */
    public static function getCarrierList()
    {
        return [
            'china_post' => 'China Post',
            'ems' => 'EMS',
            'sf_express' => 'SF Express',
            'yto' => 'YTO Express',
            'zto' => 'ZTO Express',
            'sto' => 'STO Express',
            'jd' => 'JD Logistics',
            'cainiao' => 'Cainiao',
            'dhl' => 'DHL',
            'fedex' => 'FedEx',
            'ups' => 'UPS',
            'other' => 'Другой',
        ];
    }
}
