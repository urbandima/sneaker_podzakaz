<?php
namespace app\backend\modules\procurement\models;

use yii\db\ActiveRecord;

class Supplier extends ActiveRecord
{
    public static function tableName() { return 'supplier'; }

    public function rules()
    {
        return [
            [['name'], 'required'],
            [['name', 'contact_person', 'email'], 'string', 'max' => 255],
            [['phone'], 'string', 'max' => 50],
            [['country'], 'string', 'max' => 50],
            [['address', 'payment_terms', 'notes'], 'string'],
            [['is_active'], 'boolean'],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id'             => 'ID',
            'name'           => 'Название',
            'contact_person' => 'Контактное лицо',
            'phone'          => 'Телефон',
            'email'          => 'Email',
            'address'        => 'Адрес',
            'country'        => 'Страна',
            'payment_terms'  => 'Условия оплаты',
            'notes'          => 'Примечания',
            'is_active'      => 'Активен',
            'created_at'     => 'Создан',
        ];
    }

    public function getPurchaseOrders()
    {
        return $this->hasMany(PurchaseOrder::class, ['supplier_id' => 'id']);
    }
}
