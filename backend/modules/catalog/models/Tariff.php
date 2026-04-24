<?php

namespace app\backend\modules\catalog\models;

use Yii;
use yii\db\ActiveRecord;

/**
 * Модель тарифов и комиссий для предзаказов
 *
 * @property int $id
 * @property string $name
 * @property string|null $description
 * @property float $commission_percent
 * @property float $commission_fixed
 * @property float $delivery_cost_per_kg
 * @property float $insurance_percent
 * @property float $min_order_amount
 * @property string $currency
 * @property float $exchange_rate
 * @property bool $is_active
 * @property int $sort_order
 * @property string $created_at
 * @property string $updated_at
 */
class Tariff extends ActiveRecord
{
    public static function tableName()
    {
        return '{{%tariff}}';
    }

    public function rules()
    {
        return [
            [['name'], 'required'],
            [['description'], 'string'],
            [['commission_percent', 'commission_fixed', 'delivery_cost_per_kg', 'insurance_percent', 'min_order_amount', 'exchange_rate'], 'number'],
            [['is_active'], 'boolean'],
            [['sort_order'], 'integer'],
            [['name'], 'string', 'max' => 100],
            [['currency'], 'string', 'max' => 3],
            [['commission_percent'], 'default', 'value' => 0],
            [['commission_fixed'], 'default', 'value' => 0],
            [['delivery_cost_per_kg'], 'default', 'value' => 0],
            [['insurance_percent'], 'default', 'value' => 0],
            [['min_order_amount'], 'default', 'value' => 0],
            [['exchange_rate'], 'default', 'value' => 1],
            [['currency'], 'default', 'value' => 'CNY'],
            [['is_active'], 'default', 'value' => true],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'name' => 'Название тарифа',
            'description' => 'Описание',
            'commission_percent' => 'Комиссия (%)',
            'commission_fixed' => 'Фикс. комиссия',
            'delivery_cost_per_kg' => 'Доставка за кг',
            'insurance_percent' => 'Страховка (%)',
            'min_order_amount' => 'Мин. сумма заказа',
            'currency' => 'Валюта',
            'exchange_rate' => 'Курс',
            'is_active' => 'Активен',
            'sort_order' => 'Сортировка',
        ];
    }

    /**
     * Расчет стоимости заказа
     */
    public function calculateOrderCost($productPriceCny, $weightKg = 0.5)
    {
        // Комиссия
        $commission = ($productPriceCny * $this->commission_percent / 100) + $this->commission_fixed;
        
        // Доставка
        $deliveryCost = $weightKg * $this->delivery_cost_per_kg;
        
        // Страховка
        $insurance = $productPriceCny * $this->insurance_percent / 100;
        
        // Итого в CNY
        $totalCny = $productPriceCny + $commission + $deliveryCost + $insurance;
        
        // Итого в BYN (или другой валюте)
        $totalLocal = $totalCny * $this->exchange_rate;
        
        return [
            'product_price_cny' => round($productPriceCny, 2),
            'commission_cny' => round($commission, 2),
            'delivery_cost_cny' => round($deliveryCost, 2),
            'insurance_cny' => round($insurance, 2),
            'total_cny' => round($totalCny, 2),
            'exchange_rate' => $this->exchange_rate,
            'total_local' => round($totalLocal, 2),
            'currency' => $this->currency,
            'breakdown' => [
                'Цена товара' => round($productPriceCny, 2) . ' ¥',
                'Комиссия (' . $this->commission_percent . '%)' => round($commission, 2) . ' ¥',
                'Доставка (' . $weightKg . ' кг)' => round($deliveryCost, 2) . ' ¥',
                'Страховка (' . $this->insurance_percent . '%)' => round($insurance, 2) . ' ¥',
                'Итого CNY' => round($totalCny, 2) . ' ¥',
                'Курс' => $this->exchange_rate,
                'Итого BYN' => round($totalLocal, 2) . ' BYN',
            ],
        ];
    }

    /**
     * Получить активные тарифы
     */
    public static function getActiveList()
    {
        return self::find()
            ->where(['is_active' => true])
            ->orderBy(['sort_order' => SORT_ASC])
            ->all();
    }

    /**
     * Получить список для dropdown
     */
    public static function getDropdownList()
    {
        return self::find()
            ->select(['name', 'id'])
            ->where(['is_active' => true])
            ->orderBy(['sort_order' => SORT_ASC])
            ->indexBy('id')
            ->column();
    }
}
