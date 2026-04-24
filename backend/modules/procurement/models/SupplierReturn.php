<?php
namespace app\backend\modules\procurement\models;

use yii\db\ActiveRecord;

class SupplierReturn extends ActiveRecord
{
    const STATUS_DRAFT    = 'draft';
    const STATUS_SENT     = 'sent';
    const STATUS_ACCEPTED = 'accepted';
    const STATUS_REFUNDED = 'refunded';

    const REASON_DEFECT      = 'defect';
    const REASON_WRONG_ITEM  = 'wrong_item';
    const REASON_WRONG_SIZE  = 'wrong_size';
    const REASON_DAMAGED     = 'damaged';
    const REASON_OTHER       = 'other';

    public static function tableName() { return 'supplier_return'; }

    public function rules()
    {
        return [
            [['return_number', 'purchase_order_id', 'supplier_id'], 'required'],
            [['purchase_order_id', 'supplier_id', 'created_by'], 'integer'],
            [['total_amount'], 'number'],
            [['return_number'], 'string', 'max' => 50],
            [['status', 'reason'], 'string', 'max' => 50],
            [['notes'], 'string'],
            [['created_at', 'updated_at'], 'safe'],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id'                => 'ID',
            'return_number'     => 'Номер возврата',
            'purchase_order_id' => 'Закупка',
            'supplier_id'       => 'Поставщик',
            'status'            => 'Статус',
            'reason'            => 'Причина',
            'total_amount'      => 'Сумма',
            'notes'             => 'Примечания',
            'created_at'        => 'Создан',
        ];
    }

    public static function getStatuses(): array
    {
        return [
            self::STATUS_DRAFT    => 'Черновик',
            self::STATUS_SENT     => 'Отправлено поставщику',
            self::STATUS_ACCEPTED => 'Принято поставщиком',
            self::STATUS_REFUNDED => 'Деньги возвращены',
        ];
    }

    public static function getReasons(): array
    {
        return [
            self::REASON_DEFECT     => 'Брак',
            self::REASON_WRONG_ITEM => 'Не тот товар',
            self::REASON_WRONG_SIZE => 'Не тот размер',
            self::REASON_DAMAGED    => 'Повреждения',
            self::REASON_OTHER      => 'Другое',
        ];
    }

    public function getStatusLabel(): string
    {
        return static::getStatuses()[$this->status] ?? $this->status;
    }

    public function getReasonLabel(): string
    {
        return static::getReasons()[$this->reason] ?? ($this->reason ?? '—');
    }

    public function getStatusColor(): string
    {
        return [
            self::STATUS_DRAFT    => '#6b7280',
            self::STATUS_SENT     => '#2563eb',
            self::STATUS_ACCEPTED => '#d97706',
            self::STATUS_REFUNDED => '#059669',
        ][$this->status] ?? '#6b7280';
    }

    public function getPurchaseOrder()
    {
        return $this->hasOne(PurchaseOrder::class, ['id' => 'purchase_order_id']);
    }

    public function getSupplier()
    {
        return $this->hasOne(Supplier::class, ['id' => 'supplier_id']);
    }

    public function getItems()
    {
        return $this->hasMany(SupplierReturnItem::class, ['supplier_return_id' => 'id']);
    }

    public function recalcTotal(): void
    {
        $this->total_amount = (float) SupplierReturnItem::find()
            ->where(['supplier_return_id' => $this->id])
            ->sum('price_byn * quantity') ?: 0;
    }

    public static function generateNumber(): string
    {
        $max = static::find()->max('id') ?? 0;
        return 'SR-' . date('Ym') . '-' . str_pad($max + 1, 4, '0', STR_PAD_LEFT);
    }
}
