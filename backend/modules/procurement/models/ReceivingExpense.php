<?php

namespace app\backend\modules\procurement\models;

use yii\db\ActiveRecord;

class ReceivingExpense extends ActiveRecord
{
    const TYPE_CUSTOMS   = 'customs';
    const TYPE_SHIPPING  = 'shipping';
    const TYPE_INSURANCE = 'insurance';
    const TYPE_PACKAGING = 'packaging';
    const TYPE_OTHER     = 'other';

    const DIST_EQUAL    = 'equal';
    const DIST_BY_QTY   = 'by_qty';
    const DIST_BY_VALUE = 'by_value';
    const DIST_MANUAL   = 'manual';

    public static function tableName(): string
    {
        return '{{%receiving_expense}}';
    }

    public function rules(): array
    {
        return [
            [['receiving_id', 'amount'], 'required'],
            [['receiving_id'], 'integer'],
            [['amount', 'exchange_rate', 'amount_byn'], 'number', 'min' => 0],
            [['type'], 'in', 'range' => array_keys(self::getTypes())],
            [['distribution_method'], 'in', 'range' => array_keys(self::getDistributionMethods())],
            [['currency'], 'string', 'max' => 3],
            [['notes'], 'string'],
        ];
    }

    public function attributeLabels(): array
    {
        return [
            'id'                  => 'ID',
            'receiving_id'        => 'Приёмка',
            'type'                => 'Тип',
            'amount'              => 'Сумма',
            'currency'            => 'Валюта',
            'exchange_rate'       => 'Курс',
            'amount_byn'          => 'Сумма (BYN)',
            'distribution_method' => 'Распределение',
            'notes'               => 'Примечания',
        ];
    }

    public static function getTypes(): array
    {
        return [
            self::TYPE_CUSTOMS   => 'Таможня',
            self::TYPE_SHIPPING  => 'Доставка',
            self::TYPE_INSURANCE => 'Страховка',
            self::TYPE_PACKAGING => 'Упаковка',
            self::TYPE_OTHER     => 'Прочее',
        ];
    }

    public static function getDistributionMethods(): array
    {
        return [
            self::DIST_EQUAL    => 'Поровну',
            self::DIST_BY_QTY   => 'По количеству',
            self::DIST_BY_VALUE => 'По стоимости',
            self::DIST_MANUAL   => 'Вручную',
        ];
    }

    public function getTypeLabel(): string
    {
        return self::getTypes()[$this->type] ?? $this->type;
    }

    public function getDistributionLabel(): string
    {
        return self::getDistributionMethods()[$this->distribution_method] ?? $this->distribution_method;
    }

    public function getReceiving()
    {
        return $this->hasOne(Receiving::class, ['id' => 'receiving_id']);
    }

    public function beforeSave($insert): bool
    {
        if (!parent::beforeSave($insert)) {
            return false;
        }
        $this->amount_byn = round($this->amount * ($this->exchange_rate ?: 1), 2);
        return true;
    }
}
