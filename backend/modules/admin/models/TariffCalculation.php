<?php

namespace app\backend\modules\admin\models;

use yii\db\ActiveRecord;
use yii\behaviors\TimestampBehavior;

/**
 * TariffCalculation — Модель истории расчетов по тарифам
 * 
 * НАЗНАЧЕНИЕ:
 * Хранение истории расчетов стоимости по тарифам для анализа
 * и отслеживания изменений цен.
 * 
 * ПОЛЯ:
 * - id: Уникальный идентификатор
 * - tariff_id: ID тарифа
 * - base_price: Базовая цена
 * - calculated_price: Рассчитанная цена
 * - markup_amount: Сумма наценки
 * - markup_percent: Процент наценки
 * - currency: Валюта
 * - created_at: Дата создания
 */
class TariffCalculation extends ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%tariff_calculation}}';
    }

    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        return [
            [
                'class' => TimestampBehavior::class,
                'createdAtAttribute' => 'created_at',
                'updatedAtAttribute' => false,
                'value' => new \yii\db\Expression('NOW()'),
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['tariff_id', 'base_price', 'calculated_price'], 'required'],
            [['tariff_id'], 'integer'],
            [['base_price', 'calculated_price', 'markup_amount', 'markup_percent'], 'number'],
            [['currency'], 'string', 'max' => 3],
            [['created_at'], 'safe'],
            [['tariff_id'], 'exist', 'skipOnError' => true, 'targetClass' => Tariff::class, 'targetAttribute' => ['tariff_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'tariff_id' => 'Тариф',
            'base_price' => 'Базовая цена',
            'calculated_price' => 'Итоговая цена',
            'markup_amount' => 'Сумма наценки',
            'markup_percent' => 'Процент наценки',
            'currency' => 'Валюта',
            'created_at' => 'Дата расчета',
        ];
    }

    /**
     * Связь с тарифом
     */
    public function getTariff()
    {
        return $this->hasOne(Tariff::class, ['id' => 'tariff_id']);
    }

    /**
     * Получить последние расчеты
     * 
     * @param int $limit Количество записей
     * @return array
     */
    public static function getRecent($limit = 30)
    {
        return self::find()
            ->with(['tariff'])
            ->orderBy(['created_at' => SORT_DESC])
            ->limit($limit)
            ->all();
    }

    /**
     * Создать запись о расчете
     * 
     * @param int $tariffId ID тарифа
     * @param float $basePrice Базовая цена
     * @param float $calculatedPrice Рассчитанная цена
     * @param float $markupAmount Сумма наценки
     * @param float $markupPercent Процент наценки
     * @param string $currency Валюта
     * @return bool
     */
    public static function createCalculation($tariffId, $basePrice, $calculatedPrice, $markupAmount, $markupPercent, $currency = 'RUB')
    {
        $calculation = new self([
            'tariff_id' => $tariffId,
            'base_price' => $basePrice,
            'calculated_price' => $calculatedPrice,
            'markup_amount' => $markupAmount,
            'markup_percent' => $markupPercent,
            'currency' => $currency,
        ]);

        return $calculation->save();
    }
}
