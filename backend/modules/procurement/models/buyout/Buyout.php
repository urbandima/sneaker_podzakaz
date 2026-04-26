<?php

namespace app\backend\modules\procurement\models\buyout;

use Yii;
use yii\db\ActiveRecord;
use yii\behaviors\TimestampBehavior;

/**
 * Buyout — модель выкупа товара у поставщика/маркетплейса
 *
 * Жизненный цикл выкупа отображается на статусы заказа через ORDER_STATUS_MAP:
 * - pending         → ordered           (товар заказан на площадке)
 * - purchased       → ordered           (оплачен, ждём отправки)
 * - in_transit      → international_delivery  (товар в пути из Китая)
 * - arrived         → at_warehouse      (прибыл на склад)
 * - accepted        → local_delivery    (передан в локальную доставку)
 * - issued          → issued            (выдан клиенту)
 * - cancelled       → cancelled
 */
class Buyout extends ActiveRecord
{
    // Buyout internal statuses
    const STATUS_PENDING   = 'pending';
    const STATUS_PURCHASED = 'purchased';
    const STATUS_IN_TRANSIT = 'in_transit';
    const STATUS_ARRIVED   = 'arrived';
    const STATUS_ACCEPTED  = 'accepted';
    const STATUS_ISSUED    = 'issued';
    const STATUS_CANCELLED = 'cancelled';

    /**
     * Maps buyout status → linked Order.status.
     * Aligned to actual order_status values in the DB and params.php.
     */
    const ORDER_STATUS_MAP = [
        self::STATUS_PENDING    => 'ordered',
        self::STATUS_PURCHASED  => 'ordered',
        self::STATUS_IN_TRANSIT => 'international_delivery',
        self::STATUS_ARRIVED    => 'at_warehouse',
        self::STATUS_ACCEPTED   => 'local_delivery',
        self::STATUS_ISSUED     => 'issued',
        self::STATUS_CANCELLED  => 'cancelled',
    ];

    public static function tableName(): string
    {
        return '{{%buyout}}';
    }

    public function behaviors(): array
    {
        return [TimestampBehavior::class];
    }

    public function rules(): array
    {
        return [
            [['status'], 'in', 'range' => array_keys(self::ORDER_STATUS_MAP)],
            [['status'], 'default', 'value' => self::STATUS_PENDING],
            [['order_id', 'created_by'], 'integer'],
            [['amount', 'cny_amount'], 'number'],
            [['source_url', 'article', 'note'], 'string'],
            [['source_url'], 'url'],
        ];
    }

    public function attributeLabels(): array
    {
        return [
            'id'         => 'ID',
            'status'     => 'Статус',
            'order_id'   => 'Заказ',
            'source_url' => 'Ссылка на товар',
            'article'    => 'Артикул',
            'amount'     => 'Сумма (BYN)',
            'cny_amount' => 'Сумма (CNY)',
            'note'       => 'Примечание',
            'created_at' => 'Создан',
            'updated_at' => 'Обновлён',
        ];
    }

    public static function getStatusLabels(): array
    {
        return [
            self::STATUS_PENDING    => 'Ожидает',
            self::STATUS_PURCHASED  => 'Куплен',
            self::STATUS_IN_TRANSIT => 'В пути',
            self::STATUS_ARRIVED    => 'На складе',
            self::STATUS_ACCEPTED   => 'В доставке',
            self::STATUS_ISSUED     => 'Выдан',
            self::STATUS_CANCELLED  => 'Отменён',
        ];
    }

    public function getStatusLabel(): string
    {
        return self::getStatusLabels()[$this->status] ?? $this->status;
    }

    /**
     * Returns the linked Order status that corresponds to this buyout's current status.
     */
    public function getLinkedOrderStatus(): string
    {
        return self::ORDER_STATUS_MAP[$this->status] ?? 'ordered';
    }

    /**
     * Syncs the linked order's status to match this buyout's stage.
     */
    public function syncOrderStatus(): bool
    {
        if (!$this->order_id) {
            return false;
        }
        $orderStatus = $this->getLinkedOrderStatus();
        return (bool) Yii::$app->db->createCommand(
            'UPDATE {{%order}} SET status = :status WHERE id = :id',
            [':status' => $orderStatus, ':id' => $this->order_id]
        )->execute();
    }

    public function getOrder()
    {
        return $this->hasOne(\app\backend\modules\checkout\models\Order::class, ['id' => 'order_id']);
    }
}
