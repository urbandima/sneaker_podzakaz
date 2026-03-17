<?php

namespace app\backend\modules\admin\models\import;

use Yii;
use yii\db\ActiveRecord;

/**
 * ImportProductPrice — Модель истории цен из разных источников
 * 
 * @property int $id
 * @property int|null $product_id ID товара
 * @property int $source_id ID источника
 * @property string $external_sku SKU товара в источнике
 * @property float $price_original Цена в оригинальной валюте
 * @property float|null $price_byn Цена в BYN
 * @property string $currency_code Код валюты
 * @property float|null $exchange_rate Курс конвертации
 * @property string|null $size Размер товара
 * @property bool $is_available Доступен ли товар
 * @property string|null $url URL товара в источнике
 * @property string $parsed_at Время парсинга
 * 
 * @property \app\backend\modules\catalog\models\Product $product Товар
 * @property ImportSource $source Источник
 */
class ImportProductPrice extends ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%import_product_price}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['source_id', 'external_sku', 'price_original', 'currency_code'], 'required'],
            [['product_id', 'source_id'], 'integer'],
            [['price_original', 'price_byn', 'exchange_rate'], 'number', 'min' => 0],
            [['exchange_rate'], 'number', 'min' => 0],
            [['is_available'], 'boolean'],
            [['is_available'], 'default', 'value' => true],
            [['external_sku'], 'string', 'max' => 100],
            [['currency_code'], 'string', 'max' => 3],
            [['size'], 'string', 'max' => 50],
            [['url'], 'string', 'max' => 500],
            [['url'], 'url'],
            [['parsed_at'], 'safe'],
            [['product_id'], 'exist', 'targetClass' => '\app\backend\modules\catalog\models\Product', 'targetAttribute' => 'id'],
            [['source_id'], 'exist', 'targetClass' => ImportSource::class, 'targetAttribute' => 'id'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'product_id' => 'Товар',
            'source_id' => 'Источник',
            'external_sku' => 'SKU в источнике',
            'price_original' => 'Цена (оригинал)',
            'price_byn' => 'Цена (BYN)',
            'currency_code' => 'Валюта',
            'exchange_rate' => 'Курс',
            'size' => 'Размер',
            'is_available' => 'Доступен',
            'url' => 'URL',
            'parsed_at' => 'Время парсинга',
        ];
    }

    /**
     * Товар
     */
    public function getProduct()
    {
        return $this->hasOne('\app\backend\modules\catalog\models\Product', ['id' => 'product_id']);
    }

    /**
     * Источник
     */
    public function getSource()
    {
        return $this->hasOne(ImportSource::class, ['id' => 'source_id']);
    }

    /**
     * Найти или создать запись о цене
     * @param int $sourceId
     * @param string $sku
     * @param string|null $size
     * @return ImportProductPrice|null
     */
    public static function findOrCreate($sourceId, $sku, $size = null)
    {
        $query = self::find()
            ->where(['source_id' => $sourceId, 'external_sku' => $sku]);
        
        if ($size) {
            $query->andWhere(['size' => $size]);
        } else {
            $query->andWhere(['size' => null]);
        }
        
        return $query->one();
    }

    /**
     * Обновить цену товара
     * @param int $sourceId
     * @param string $sku
     * @param float $price
     * @param string $currency
     * @param float $priceByn
     * @param float $rate
     * @param string|null $size
     * @param bool $available
     * @param string|null $url
     * @param int|null $productId
     * @return ImportProductPrice
     */
    public static function updatePrice($sourceId, $sku, $price, $currency, $priceByn, $rate, $size = null, $available = true, $url = null, $productId = null)
    {
        $record = self::findOrCreate($sourceId, $sku, $size);
        
        if (!$record) {
            $record = new self([
                'source_id' => $sourceId,
                'external_sku' => $sku,
            ]);
        }
        
        $record->product_id = $productId;
        $record->price_original = $price;
        $record->price_byn = $priceByn;
        $record->currency_code = $currency;
        $record->exchange_rate = $rate;
        $record->size = $size;
        $record->is_available = $available;
        $record->url = $url;
        $record->parsed_at = date('Y-m-d H:i:s');
        
        $record->save(false);
        
        return $record;
    }

    /**
     * Получить все цены для товара (из всех источников)
     * @param string $sku
     * @param string|null $size
     * @return ImportProductPrice[]
     */
    public static function getAllPrices($sku, $size = null)
    {
        $query = self::find()
            ->where(['external_sku' => $sku])
            ->with('source');
        
        if ($size) {
            $query->andWhere(['size' => $size]);
        } else {
            $query->andWhere(['size' => null]);
        }
        
        return $query->orderBy(['price_byn' => SORT_ASC])->all();
    }

    /**
     * Получить лучшую цену для товара
     * @param string $sku
     * @param string|null $size
     * @return ImportProductPrice|null
     */
    public static function getBestPrice($sku, $size = null)
    {
        $prices = self::getAllPrices($sku, $size);
        
        if (empty($prices)) {
            return null;
        }
        
        // Фильтруем только доступные
        $available = array_filter($prices, function($p) {
            return $p->is_available && $p->price_byn > 0;
        });
        
        if (empty($available)) {
            return null;
        }
        
        // Возвращаем первую (с минимальной ценой)
        return reset($available);
    }

    /**
     * Получить историю цен для товара
     * @param string $sku
     * @param int $limit
     * @return ImportProductPrice[]
     */
    public static function getPriceHistory($sku, $limit = 30)
    {
        return self::find()
            ->where(['external_sku' => $sku])
            ->with('source')
            ->orderBy(['parsed_at' => SORT_DESC])
            ->limit($limit)
            ->all();
    }

    /**
     * Очистить старые записи цен
     * @param int $daysOld Удалять записи старше N дней
     * @return int Количество удаленных записей
     */
    public static function cleanOldPrices($daysOld = 30)
    {
        $date = date('Y-m-d H:i:s', strtotime("-{$daysOld} days"));
        
        return self::deleteAll(['<', 'parsed_at', $date]);
    }
}
