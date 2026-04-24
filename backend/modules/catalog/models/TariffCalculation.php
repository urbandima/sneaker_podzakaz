<?php

namespace app\backend\modules\catalog\models;

use Yii;
use yii\db\ActiveRecord;
use yii\behaviors\TimestampBehavior;

/**
 * Модель истории расчетов тарифов
 *
 * @property int $id
 * @property int $tariff_id
 * @property string $tariff_name
 * @property float $price_cny
 * @property float $weight_kg
 * @property float $commission_cny
 * @property float $delivery_cost_cny
 * @property float $insurance_cny
 * @property float $total_cny
 * @property float $exchange_rate
 * @property float $total_local
 * @property string $currency
 * @property int|null $user_id
 * @property string|null $note
 * @property int $created_at
 *
 * @property Tariff $tariff
 * @property User $user
 */
class TariffCalculation extends ActiveRecord
{
    public static function tableName()
    {
        return '{{%tariff_calculation}}';
    }

    public function behaviors()
    {
        return [
            [
                'class' => TimestampBehavior::class,
                'updatedAtAttribute' => false,
            ],
        ];
    }

    public function rules()
    {
        return [
            [['tariff_id', 'tariff_name', 'price_cny', 'commission_cny', 'delivery_cost_cny', 'insurance_cny', 'total_cny', 'exchange_rate', 'total_local'], 'required'],
            [['tariff_id', 'user_id', 'created_at'], 'integer'],
            [['price_cny', 'weight_kg', 'commission_cny', 'delivery_cost_cny', 'insurance_cny', 'total_cny', 'exchange_rate', 'total_local'], 'number'],
            [['note'], 'string'],
            [['tariff_name'], 'string', 'max' => 100],
            [['currency'], 'string', 'max' => 3],
            [['weight_kg'], 'default', 'value' => 0.5],
            [['currency'], 'default', 'value' => 'BYN'],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'tariff_id' => 'Тариф',
            'tariff_name' => 'Название тарифа',
            'price_cny' => 'Цена товара (¥)',
            'weight_kg' => 'Вес (кг)',
            'commission_cny' => 'Комиссия (¥)',
            'delivery_cost_cny' => 'Доставка (¥)',
            'insurance_cny' => 'Страховка (¥)',
            'total_cny' => 'Итого (¥)',
            'exchange_rate' => 'Курс',
            'total_local' => 'Итого (BYN)',
            'currency' => 'Валюта',
            'user_id' => 'Пользователь',
            'note' => 'Примечание',
            'created_at' => 'Дата расчета',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getTariff()
    {
        return $this->hasOne(Tariff::class, ['id' => 'tariff_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getUser()
    {
        return $this->hasOne(User::class, ['id' => 'user_id']);
    }

    /**
     * Создать запись из результата расчета
     */
    public static function createFromCalculation(Tariff $tariff, array $calculation, $priceCny, $weightKg, $note = null)
    {
        $model = new self();
        $model->tariff_id = $tariff->id;
        $model->tariff_name = $tariff->name;
        $model->price_cny = $priceCny;
        $model->weight_kg = $weightKg;
        $model->commission_cny = $calculation['commission_cny'];
        $model->delivery_cost_cny = $calculation['delivery_cost_cny'];
        $model->insurance_cny = $calculation['insurance_cny'];
        $model->total_cny = $calculation['total_cny'];
        $model->exchange_rate = $calculation['exchange_rate'];
        $model->total_local = $calculation['total_local'];
        $model->currency = $calculation['currency'] ?? 'BYN';
        $model->user_id = Yii::$app->user->isGuest ? null : Yii::$app->user->id;
        $model->note = $note;

        return $model->save() ? $model : null;
    }

    /**
     * Получить последние расчеты
     */
    public static function getRecent($limit = 20)
    {
        return self::find()
            ->orderBy(['created_at' => SORT_DESC])
            ->limit($limit)
            ->all();
    }
}
