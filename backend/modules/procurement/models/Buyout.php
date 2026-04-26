<?php

namespace app\backend\modules\procurement\models;

use Yii;
use yii\db\ActiveRecord;
use yii\behaviors\TimestampBehavior;
use yii\db\Expression;
use app\backend\modules\checkout\models\Order;
use app\backend\modules\procurement\services\BuyoutStatusSyncService;
use app\backend\modules\admin\behaviors\LogBehavior;

class Buyout extends ActiveRecord
{
    // ── Sources ───────────────────────────────────────────────────────────────
    const SOURCE_POIZON   = 'poizon';
    const SOURCE_LAMODA   = 'lamoda';
    const SOURCE_ALIEXPRESS = 'aliexpress';
    const SOURCE_SUPPLIER = 'supplier';
    const SOURCE_MANUAL   = 'manual';

    // ── Statuses ──────────────────────────────────────────────────────────────
    const STATUS_DRAFT      = 'draft';
    const STATUS_ORDERED    = 'ordered';
    const STATUS_IN_TRANSIT = 'in_transit';
    const STATUS_ARRIVED    = 'arrived';
    const STATUS_ACCEPTED   = 'accepted';
    const STATUS_CANCELLED  = 'cancelled';
    const STATUS_REFUNDED   = 'refunded';

    // Mapping: buyout status → order status
    const ORDER_STATUS_MAP = [
        self::STATUS_ORDERED    => 'bought_at_source',
        self::STATUS_IN_TRANSIT => 'in_transit_from_source',
        self::STATUS_ARRIVED    => 'arrived_at_warehouse',
        self::STATUS_ACCEPTED   => 'ready_to_ship',
        self::STATUS_CANCELLED  => 'awaiting_buyout',
    ];

    public static function tableName(): string
    {
        return '{{%buyout}}';
    }

    public function behaviors(): array
    {
        return [
            [
                'class'              => TimestampBehavior::class,
                'createdAtAttribute' => 'created_at',
                'updatedAtAttribute' => 'updated_at',
                'value'              => new Expression('NOW()'),
            ],
            [
                'class'          => LogBehavior::class,
                'targetType'     => 'Buyout',
                'labelAttribute' => 'external_id',
            ],
        ];
    }

    public function rules(): array
    {
        return [
            [['source', 'qty'], 'required'],
            [['source'], 'in', 'range' => array_keys(self::getSources())],
            [['status'], 'in', 'range' => array_keys(self::getStatuses())],
            [['qty'], 'integer', 'min' => 1],
            [['product_id', 'buyer_user_id', 'receiving_id'], 'integer'],
            [['unit_cost_source', 'exchange_rate', 'unit_cost_byn', 'shipping_cost', 'fees', 'total_cost_byn'], 'number'],
            [['ordered_at', 'arrived_at', 'accepted_at'], 'safe'],
            [['product_snapshot'], 'safe'],
            [['notes'], 'string'],
            [['source_url', 'receipt_url'], 'string', 'max' => 1024],
            [['external_id', 'tracking_number', 'carrier'], 'string', 'max' => 255],
            [['size'], 'string', 'max' => 30],
            [['source_currency'], 'string', 'max' => 3],
        ];
    }

    public function attributeLabels(): array
    {
        return [
            'id'               => 'ID',
            'source'           => 'Источник',
            'source_url'       => 'URL источника',
            'external_id'      => 'ID на источнике',
            'product_id'       => 'Товар',
            'product_snapshot' => 'Снапшот товара',
            'size'             => 'Размер',
            'qty'              => 'Кол-во',
            'unit_cost_source' => 'Цена ед. (валюта)',
            'source_currency'  => 'Валюта',
            'exchange_rate'    => 'Курс',
            'unit_cost_byn'    => 'Цена ед. (BYN)',
            'shipping_cost'    => 'Доставка (BYN)',
            'fees'             => 'Комиссии/пошлины (BYN)',
            'total_cost_byn'   => 'Итого (BYN)',
            'status'           => 'Статус',
            'ordered_at'       => 'Дата заказа',
            'arrived_at'       => 'Дата прибытия',
            'accepted_at'      => 'Дата приёмки',
            'buyer_user_id'    => 'Закупщик',
            'notes'            => 'Заметки',
            'receipt_url'      => 'Чек/инвойс',
            'tracking_number'  => 'Трек-номер',
            'carrier'          => 'Перевозчик',
            'receiving_id'     => 'Приёмка',
            'created_at'       => 'Создан',
            'updated_at'       => 'Обновлён',
        ];
    }

    // ── Events ────────────────────────────────────────────────────────────────

    public function afterSave($insert, $changedAttributes)
    {
        parent::afterSave($insert, $changedAttributes);

        if (!$insert && isset($changedAttributes['status'])) {
            $oldStatus = $changedAttributes['status'];
            $newStatus = $this->status;

            // Write history
            $history = new BuyoutHistory();
            $history->buyout_id = $this->id;
            $history->user_id   = Yii::$app->user->id ?? null;
            $history->action    = 'status_changed';
            $history->old_value = ['status' => $oldStatus];
            $history->new_value = ['status' => $newStatus];
            $history->save(false);

            // Sync linked order statuses
            (new BuyoutStatusSyncService())->syncOnStatusChange($this, $oldStatus, $newStatus);
        }
    }

    // ── Relations ─────────────────────────────────────────────────────────────

    public function getOrderLinks()
    {
        return $this->hasMany(BuyoutOrderLink::class, ['buyout_id' => 'id']);
    }

    public function getOrders()
    {
        return $this->hasMany(Order::class, ['id' => 'order_id'])
            ->via('orderLinks');
    }

    public function getHistories()
    {
        return $this->hasMany(BuyoutHistory::class, ['buyout_id' => 'id'])
            ->orderBy(['created_at' => SORT_DESC]);
    }

    public function getBuyer()
    {
        return $this->hasOne(\app\backend\modules\account\models\User::class ?? \yii\web\IdentityInterface::class, ['id' => 'buyer_user_id']);
    }

    // ── Static helpers ────────────────────────────────────────────────────────

    public static function getSources(): array
    {
        return [
            self::SOURCE_POIZON    => 'Poizon',
            self::SOURCE_LAMODA    => 'Lamoda',
            self::SOURCE_ALIEXPRESS => 'AliExpress',
            self::SOURCE_SUPPLIER  => 'Поставщик',
            self::SOURCE_MANUAL    => 'Вручную',
        ];
    }

    public static function getStatuses(): array
    {
        return [
            self::STATUS_DRAFT      => 'Черновик',
            self::STATUS_ORDERED    => 'Заказан',
            self::STATUS_IN_TRANSIT => 'В пути',
            self::STATUS_ARRIVED    => 'Прибыл',
            self::STATUS_ACCEPTED   => 'Принят',
            self::STATUS_CANCELLED  => 'Отменён',
            self::STATUS_REFUNDED   => 'Возврат',
        ];
    }

    public function getSourceLabel(): string
    {
        return static::getSources()[$this->source] ?? $this->source;
    }

    public function getStatusLabel(): string
    {
        return static::getStatuses()[$this->status] ?? $this->status;
    }

    public function getStatusColor(): string
    {
        return [
            self::STATUS_DRAFT      => '#6b7280',
            self::STATUS_ORDERED    => '#2563eb',
            self::STATUS_IN_TRANSIT => '#d97706',
            self::STATUS_ARRIVED    => '#7c3aed',
            self::STATUS_ACCEPTED   => '#059669',
            self::STATUS_CANCELLED  => '#dc2626',
            self::STATUS_REFUNDED   => '#6b7280',
        ][$this->status] ?? '#6b7280';
    }

    public function getStatusBg(): string
    {
        return [
            self::STATUS_DRAFT      => '#f3f4f6',
            self::STATUS_ORDERED    => '#dbeafe',
            self::STATUS_IN_TRANSIT => '#fef3c7',
            self::STATUS_ARRIVED    => '#ede9fe',
            self::STATUS_ACCEPTED   => '#d1fae5',
            self::STATUS_CANCELLED  => '#fee2e2',
            self::STATUS_REFUNDED   => '#f3f4f6',
        ][$this->status] ?? '#f3f4f6';
    }

    /** Allowed next statuses from current */
    public function getAllowedTransitions(): array
    {
        $map = [
            self::STATUS_DRAFT      => [self::STATUS_ORDERED, self::STATUS_CANCELLED],
            self::STATUS_ORDERED    => [self::STATUS_IN_TRANSIT, self::STATUS_CANCELLED, self::STATUS_REFUNDED],
            self::STATUS_IN_TRANSIT => [self::STATUS_ARRIVED, self::STATUS_CANCELLED],
            self::STATUS_ARRIVED    => [self::STATUS_ACCEPTED, self::STATUS_CANCELLED],
            self::STATUS_ACCEPTED   => [],
            self::STATUS_CANCELLED  => [self::STATUS_REFUNDED],
            self::STATUS_REFUNDED   => [],
        ];
        return $map[$this->status] ?? [];
    }

    public function canTransitionTo(string $newStatus): bool
    {
        return in_array($newStatus, $this->getAllowedTransitions(), true);
    }

    /** Recalculate total_cost_byn */
    public function recalcTotal(): void
    {
        $unit = (float)($this->unit_cost_byn ?? 0);
        $qty  = (int)($this->qty ?? 1);
        $ship = (float)($this->shipping_cost ?? 0);
        $fees = (float)($this->fees ?? 0);
        $this->total_cost_byn = round($unit * $qty + $ship + $fees, 2);
    }

    /** Product name from snapshot or generic label */
    public function getProductName(): string
    {
        $snap = is_array($this->product_snapshot) ? $this->product_snapshot : (array)json_decode((string)$this->product_snapshot, true);
        return $snap['name'] ?? ('Выкуп #' . $this->id);
    }

    /** Product image from snapshot */
    public function getProductImage(): ?string
    {
        $snap = is_array($this->product_snapshot) ? $this->product_snapshot : (array)json_decode((string)$this->product_snapshot, true);
        return $snap['image'] ?? ($snap['images'][0] ?? null);
    }

    public static function generateNumber(): string
    {
        $max = static::find()->max('id') ?? 0;
        return 'BY-' . date('Ym') . '-' . str_pad($max + 1, 4, '0', STR_PAD_LEFT);
    }
}
