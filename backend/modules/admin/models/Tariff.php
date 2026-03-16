<?php

namespace app\backend\modules\admin\models;

use Yii;
use yii\db\ActiveRecord;

/**
 * Tariff model
 *
 * @property int $id
 * @property string $name Название тарифа
 * @property string $description Описание
 * @property float $commission Комиссия в %
 * @property float $base_rate Базовый курс
 * @property float $delivery_cost Стоимость доставки
 * @property int $is_active Активен
 * @property int $sort_order Порядок сортировки
 * @property string $created_at
 * @property string $updated_at
 */
class Tariff extends ActiveRecord
{
    const STATUS_INACTIVE = 0;
    const STATUS_ACTIVE = 1;

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%tariff}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['name', 'commission'], 'required'],
            [['name'], 'string', 'max' => 255],
            [['description'], 'string'],
            [['commission', 'base_rate', 'delivery_cost'], 'number', 'min' => 0],
            [['is_active', 'sort_order'], 'integer'],
            [['is_active'], 'default', 'value' => self::STATUS_ACTIVE],
            [['sort_order'], 'default', 'value' => 0],
            [['created_at', 'updated_at'], 'safe'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'name' => 'Название тарифа',
            'description' => 'Описание',
            'commission' => 'Комиссия (%)',
            'base_rate' => 'Базовый курс',
            'delivery_cost' => 'Стоимость доставки',
            'is_active' => 'Активен',
            'sort_order' => 'Порядок сортировки',
            'created_at' => 'Создан',
            'updated_at' => 'Обновлен',
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        return [
            'timestamp' => [
                'class' => 'yii\behaviors\TimestampBehavior',
                'createdAtAttribute' => 'created_at',
                'updatedAtAttribute' => 'updated_at',
                'value' => new \yii\db\Expression('NOW()'),
            ],
        ];
    }

    /**
     * Получить активные тарифы
     */
    public static function getActive()
    {
        return static::find()
            ->where(['is_active' => self::STATUS_ACTIVE])
            ->orderBy(['sort_order' => SORT_ASC, 'name' => SORT_ASC])
            ->all();
    }

    /**
     * Получить список тарифов для dropdown
     */
    public static function getList()
    {
        $tariffs = static::getActive();
        $list = [];
        foreach ($tariffs as $tariff) {
            $list[$tariff->id] = $tariff->name;
        }
        return $list;
    }

    /**
     * Рассчитать итоговую цену
     */
    public function calculatePrice($priceCny, $weightKg = 1.0)
    {
        $rate = $this->base_rate ?: 0.45; // Курс по умолчанию
        $commission = $this->commission ?: 10; // Комиссия по умолчанию
        $delivery = $this->delivery_cost ?: 15; // Доставка по умолчанию
        
        // Конвертация в BYN
        $priceByn = $priceCny * $rate;
        
        // Добавление комиссии
        $commissionAmount = $priceByn * ($commission / 100);
        
        // Добавление доставки
        $deliveryByn = $delivery * $weightKg;
        
        // Итоговая цена
        $total = $priceByn + $commissionAmount + $deliveryByn;
        
        return [
            'price_cny' => $priceCny,
            'price_byn' => $priceByn,
            'commission_percent' => $commission,
            'commission_amount' => $commissionAmount,
            'delivery_cost' => $deliveryByn,
            'total' => $total,
        ];
    }
}
