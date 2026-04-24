<?php
namespace app\backend\modules\finance\models;

use Yii;
use yii\db\ActiveRecord;

class Payment extends ActiveRecord
{
    const STATUS_PENDING   = 'pending';
    const STATUS_CONFIRMED = 'confirmed';
    const STATUS_CANCELLED = 'cancelled';
    const STATUS_REFUNDED  = 'refunded';

    const METHOD_BANK_TRANSFER = 'bank_transfer';
    const METHOD_ACQUIRING     = 'acquiring';
    const METHOD_CASH          = 'cash';
    const METHOD_ERIP          = 'erip';

    public static function tableName() { return 'payment'; }

    public function rules()
    {
        return [
            [['amount'], 'required'],
            [['amount', 'amount_original'], 'number'],
            [['order_id', 'customer_id', 'confirmed_by'], 'integer'],
            [['confirmed_at'], 'safe'],
            [['description'], 'string'],
            [['currency'], 'string', 'max' => 3],
            [['payment_method', 'status'], 'string', 'max' => 50],
            [['bank_reference'], 'string', 'max' => 100],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id'             => 'ID',
            'order_id'       => 'Заказ',
            'customer_id'    => 'Клиент',
            'amount'         => 'Сумма',
            'currency'       => 'Валюта',
            'payment_method' => 'Способ оплаты',
            'status'         => 'Статус',
            'confirmed_by'   => 'Подтвердил',
            'confirmed_at'   => 'Дата подтверждения',
            'bank_reference' => 'Референс банка',
            'description'    => 'Описание',
            'created_at'     => 'Дата создания',
        ];
    }

    public static function getStatuses(): array
    {
        return [
            self::STATUS_PENDING   => 'Ожидает',
            self::STATUS_CONFIRMED => 'Подтверждён',
            self::STATUS_CANCELLED => 'Отменён',
            self::STATUS_REFUNDED  => 'Возврат',
        ];
    }

    public static function getMethods(): array
    {
        return [
            self::METHOD_BANK_TRANSFER => 'Банковский перевод',
            self::METHOD_ACQUIRING     => 'Эквайринг',
            self::METHOD_CASH          => 'Наличные',
            self::METHOD_ERIP          => 'ЕРИП',
        ];
    }

    public function getStatusLabel(): string
    {
        return static::getStatuses()[$this->status] ?? $this->status;
    }

    public function getMethodLabel(): string
    {
        return static::getMethods()[$this->payment_method] ?? ($this->payment_method ?? '—');
    }

    public function getStatusColor(): string
    {
        return [
            self::STATUS_PENDING   => '#d97706',
            self::STATUS_CONFIRMED => '#059669',
            self::STATUS_CANCELLED => '#6b7280',
            self::STATUS_REFUNDED  => '#dc2626',
        ][$this->status] ?? '#6b7280';
    }

    public function getOrder()
    {
        return $this->hasOne(\app\backend\modules\checkout\models\Order::class, ['id' => 'order_id']);
    }
}
