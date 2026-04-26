<?php
namespace app\backend\modules\procurement\models;

use yii\db\ActiveRecord;

class PurchaseOrder extends ActiveRecord
{
    const STATUS_DRAFT     = 'draft';
    const STATUS_ORDERED   = 'ordered';
    const STATUS_TRANSIT   = 'in_transit';
    const STATUS_RECEIVED  = 'received';
    const STATUS_CANCELLED = 'cancelled';

    const TYPE_STOCK      = 'stock';
    const TYPE_PREORDER   = 'preorder';
    const TYPE_COMMISSION = 'commission';

    public static function tableName() { return 'purchase_order'; }

    public function rules()
    {
        return [
            [['purchase_number', 'supplier_id'], 'required'],
            [['supplier_id', 'order_id', 'customer_id', 'created_by'], 'integer'],
            [['total_amount_cny', 'total_amount_byn', 'exchange_rate'], 'number'],
            [['ordered_at', 'received_at'], 'safe'],
            [['notes'], 'string'],
            [['purchase_number'], 'string', 'max' => 50],
            [['status'], 'string', 'max' => 30],
            [['order_type'], 'string', 'max' => 20],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id'               => 'ID',
            'purchase_number'  => 'Номер',
            'supplier_id'      => 'Поставщик',
            'status'           => 'Статус',
            'order_type'       => 'Тип',
            'total_amount_cny' => 'Сумма CNY',
            'total_amount_byn' => 'Сумма BYN',
            'exchange_rate'    => 'Курс',
            'order_id'         => 'Заказ клиента',
            'customer_id'      => 'Клиент',
            'notes'            => 'Примечания',
            'ordered_at'       => 'Дата заказа',
            'received_at'      => 'Дата получения',
            'created_at'       => 'Создан',
        ];
    }

    public static function getStatuses(): array
    {
        return [
            self::STATUS_DRAFT     => 'Черновик',
            self::STATUS_ORDERED   => 'Заказано',
            self::STATUS_TRANSIT   => 'В пути',
            self::STATUS_RECEIVED  => 'Получено',
            self::STATUS_CANCELLED => 'Отменено',
        ];
    }

    public static function getTypes(): array
    {
        return [
            self::TYPE_STOCK      => 'Наличие',
            self::TYPE_PREORDER   => 'Подзаказ',
            self::TYPE_COMMISSION => 'Комиссия',
        ];
    }

    public function getStatusLabel(): string
    {
        return static::getStatuses()[$this->status] ?? $this->status;
    }

    public function getTypeLabel(): string
    {
        return static::getTypes()[$this->order_type] ?? $this->order_type;
    }

    public function getStatusColor(): string
    {
        return [
            self::STATUS_DRAFT     => '#6b7280',
            self::STATUS_ORDERED   => '#2563eb',
            self::STATUS_TRANSIT   => '#d97706',
            self::STATUS_RECEIVED  => '#059669',
            self::STATUS_CANCELLED => '#dc2626',
        ][$this->status] ?? '#6b7280';
    }

    public function getSupplier()
    {
        return $this->hasOne(Supplier::class, ['id' => 'supplier_id']);
    }

    public function getItems()
    {
        return $this->hasMany(PurchaseOrderItem::class, ['purchase_order_id' => 'id']);
    }

    public function getSupplierReturns()
    {
        return $this->hasMany(SupplierReturn::class, ['purchase_order_id' => 'id']);
    }

    public function recalcTotals(): void
    {
        $this->total_amount_cny = (float) PurchaseOrderItem::find()
            ->where(['purchase_order_id' => $this->id])
            ->sum('price_cny * quantity') ?: 0;
        $this->total_amount_byn = (float) PurchaseOrderItem::find()
            ->where(['purchase_order_id' => $this->id])
            ->sum('price_byn * quantity') ?: 0;
    }

    public function getReceivedPercent(): int
    {
        $items = $this->items;
        if (!$items) return 0;
        $total    = array_sum(array_column($items, 'quantity'));
        $received = array_sum(array_column($items, 'received_quantity'));
        return $total > 0 ? (int)round($received / $total * 100) : 0;
    }

    public static function generateNumber(): string
    {
        $max = static::find()->max('id') ?? 0;
        return 'PO-' . date('Ym') . '-' . str_pad($max + 1, 4, '0', STR_PAD_LEFT);
    }
}
