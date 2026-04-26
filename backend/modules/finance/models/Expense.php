<?php
namespace app\backend\modules\finance\models;

use Yii;
use yii\db\ActiveRecord;

class Expense extends ActiveRecord
{
    const CAT_PURCHASE        = 'purchase';
    const CAT_DELIVERY_CHINA  = 'delivery_china';
    const CAT_CUSTOMS         = 'customs';
    const CAT_DELIVERY_LOCAL  = 'delivery_local';
    const CAT_RENT            = 'rent';
    const CAT_SALARY          = 'salary';
    const CAT_OTHER           = 'other';

    public static function tableName() { return 'expense'; }

    public function rules()
    {
        return [
            [['category', 'amount'], 'required'],
            [['amount', 'amount_original', 'exchange_rate'], 'number'],
            [['order_id', 'supplier_id', 'created_by'], 'integer'],
            [['description'], 'string'],
            [['category'], 'string', 'max' => 50],
            [['currency', 'currency_original'], 'string', 'max' => 3],
            [['document_number'], 'string', 'max' => 100],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id'               => 'ID',
            'category'         => 'Категория',
            'order_id'         => 'Заказ',
            'supplier_id'      => 'Поставщик',
            'amount'           => 'Сумма (BYN)',
            'currency'         => 'Валюта',
            'amount_original'  => 'Сумма (ориг.)',
            'currency_original'=> 'Валюта (ориг.)',
            'exchange_rate'    => 'Курс',
            'description'      => 'Описание',
            'document_number'  => 'Номер документа',
            'created_by'       => 'Создал',
            'created_at'       => 'Дата',
        ];
    }

    public static function getCategories(): array
    {
        return [
            self::CAT_PURCHASE       => 'Закупка товара',
            self::CAT_DELIVERY_CHINA => 'Доставка из Китая',
            self::CAT_CUSTOMS        => 'Таможня',
            self::CAT_DELIVERY_LOCAL => 'Доставка по РБ',
            self::CAT_RENT           => 'Аренда',
            self::CAT_SALARY         => 'Зарплата',
            self::CAT_OTHER          => 'Прочие',
        ];
    }

    public function getCategoryLabel(): string
    {
        return static::getCategories()[$this->category] ?? $this->category;
    }

    public function getCategoryColor(): string
    {
        return [
            self::CAT_PURCHASE       => '#dc2626',
            self::CAT_DELIVERY_CHINA => '#d97706',
            self::CAT_CUSTOMS        => '#7c3aed',
            self::CAT_DELIVERY_LOCAL => '#0891b2',
            self::CAT_RENT           => '#6b7280',
            self::CAT_SALARY         => '#059669',
            self::CAT_OTHER          => '#9ca3af',
        ][$this->category] ?? '#6b7280';
    }
}
