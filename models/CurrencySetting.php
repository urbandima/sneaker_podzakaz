<?php

namespace app\models;

use Yii;
use yii\db\ActiveRecord;

/**
 * Модель CurrencySetting (Настройки валют)
 *
 * @property int $id
 * @property string $currency_code
 * @property string $currency_symbol
 * @property float $exchange_rate
 * @property int $is_base
 * @property int $is_active
 * @property float $markup_percent
 * @property float $delivery_fee
 * @property string $updated_at
 */
class CurrencySetting extends ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'currency_setting';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['currency_code', 'currency_symbol', 'exchange_rate'], 'required'],
            [['exchange_rate', 'markup_percent', 'delivery_fee'], 'number'],
            [['is_base', 'is_active'], 'boolean'],
            [['currency_code'], 'string', 'max' => 3],
            [['currency_symbol'], 'string', 'max' => 10],
            [['currency_code'], 'unique'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'currency_code' => 'Код валюты',
            'currency_symbol' => 'Символ',
            'exchange_rate' => 'Курс обмена',
            'is_base' => 'Базовая валюта',
            'is_active' => 'Активна',
            'markup_percent' => 'Наценка (%)',
            'delivery_fee' => 'Стоимость доставки',
            'updated_at' => 'Обновлено',
        ];
    }
    
    /**
     * Получить базовую валюту
     */
    public static function getBaseCurrency()
    {
        return self::findOne(['is_base' => 1]);
    }
    
    /**
     * Получить валюту по коду
     */
    public static function getByCurrencyCode($code)
    {
        return self::findOne(['currency_code' => $code, 'is_active' => 1]);
    }
    
    /**
     * Округлить цену до "красивого" значения, заканчивающегося на 9
     * Примеры: 365.48 → 359, 419.84 → 419, 324.71 → 319
     * 
     * @param float $price Исходная цена
     * @return int Округленная цена
     */
    private static function roundToPrettyPrice($price)
    {
        $floored = floor($price);
        $result = floor($floored / 10) * 10 + 9;
        
        // Если результат больше исходной цены, отнимаем 10
        if ($result > $floored) {
            $result -= 10;
        }
        
        return $result;
    }
    
    /**
     * Конвертировать из CNY в целевую валюту с учетом наценки и доставки
     * Формула: (price_cny * exchange_rate * (1 + markup_percent/100)) + delivery_fee
     * Округление до "красивых" цен: 399, 409, 419, 429...
     * 
     * @param float $priceCny Цена в юанях
     * @param string $targetCurrency Код целевой валюты (BYN, RUB, USD)
     * @return float Цена в целевой валюте
     */
    public static function convertFromCny($priceCny, $targetCurrency = 'BYN')
    {
        $currency = self::getByCurrencyCode($targetCurrency);
        
        if (!$currency) {
            // Фоллбэк на дефолтную формулу
            if ($targetCurrency === 'BYN') {
                // Старая формула: (CNY * 0.45 * 1.5) + 40, округление до красивых цен
                $price = ($priceCny * 0.45 * 1.5) + 40;
                return self::roundToPrettyPrice($price);
            }
            return $priceCny;
        }
        
        // Новая формула с настройками из БД
        $basePrice = $priceCny * $currency->exchange_rate;
        $withMarkup = $basePrice * (1 + $currency->markup_percent / 100);
        $finalPrice = $withMarkup + $currency->delivery_fee;
        
        // Округление до "красивых" цен (399, 409, 419...)
        return self::roundToPrettyPrice($finalPrice);
    }
    
    /**
     * Получить все активные валюты
     */
    public static function getActiveCurrencies()
    {
        return self::find()
            ->where(['is_active' => 1])
            ->orderBy(['is_base' => SORT_DESC, 'currency_code' => SORT_ASC])
            ->all();
    }
}
