<?php

namespace app\models;

use Yii;
use yii\db\ActiveRecord;
use yii\behaviors\TimestampBehavior;

/**
 * Модель ProductSize (Размер товара)
 *
 * @property int $id
 * @property int $sort_order Порядок сортировки
 * @property int $product_id
 * @property string $size Размер (40, 41, 42, S, M, L, XL и т.д.)
 * @property string|null $color Цвет варианта
 * @property int $stock Количество на складе
 * @property int $is_available Доступен для заказа
 * @property float|null $price Цена размера (если отличается от общей)
 * @property string $created_at
 * @property string $updated_at
 * 
 * SIZE GRIDS:
 * @property string|null $us_size Размер US
 * @property string|null $eu_size Размер EU
 * @property string|null $uk_size Размер UK
 * @property float|null $cm_size Размер в CM
 * 
 * PRICING:
 * @property float|null $price_cny Цена в юанях (CNY)
 * @property float|null $price_byn Цена в BYN (для магазина)
 * @property float|null $price_client_byn Цена для клиента в BYN
 * 
 * POIZON FIELDS:
 * @property string|null $poizon_sku_id SKU ID в Poizon
 * @property int $poizon_stock Остаток на Poizon
 * @property float|null $poizon_price_cny Цена размера на Poizon
 * 
 * NEW FIELDS:
 * @property string|null $color_variant Цвет конкретного варианта
 * @property int|null $delivery_time_min Минимальный срок доставки
 * @property int|null $delivery_time_max Максимальный срок доставки
 * @property string|null $variant_vendor_code Артикул варианта (vendorCode из children)
 * @property string|null $images_json JSON массив изображений варианта
 * 
 * @property Product $product
 */
class ProductSize extends ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'product_size';
    }

    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        return [
            [
                'class' => TimestampBehavior::class,
                'value' => function() { return date('Y-m-d H:i:s'); },
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['product_id', 'size'], 'required'],
            [['product_id', 'stock', 'poizon_stock', 'sort_order'], 'integer'],
            [['stock', 'poizon_stock', 'sort_order'], 'default', 'value' => 0],
            [['is_available'], 'boolean'],
            [['is_available'], 'default', 'value' => 1],
            [['size', 'us_size', 'eu_size', 'uk_size', 'poizon_sku_id', 'color_variant'], 'string', 'max' => 50],
            [['color', 'color_variant'], 'string', 'max' => 100],
            [['variant_vendor_code'], 'string', 'max' => 100],
            [['images_json'], 'string'],
            [['delivery_time_min', 'delivery_time_max'], 'integer'],
            [['cm_size', 'poizon_price_cny', 'price', 'price_cny', 'price_byn', 'price_client_byn'], 'number'],
            [['price', 'price_cny', 'price_byn', 'price_client_byn'], 'number', 'min' => 0],
            [['product_id'], 'exist', 'targetClass' => Product::class, 'targetAttribute' => 'id'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'sort_order' => 'Порядок',
            'product_id' => 'Товар',
            'size' => 'Размер',
            'color' => 'Цвет',
            'stock' => 'Остаток на складе',
            'is_available' => 'Доступен',
            'created_at' => 'Создан',
            'updated_at' => 'Обновлен',
            
            // SIZE GRIDS
            'us_size' => 'US',
            'eu_size' => 'EU',
            'uk_size' => 'UK',
            'cm_size' => 'CM',
            
            // PRICING
            'price' => 'Цена (если отличается)',
            'price_cny' => 'Цена ¥',
            'price_byn' => 'Цена BYN',
            'price_client_byn' => 'Цена для клиента',
            
            // POIZON
            'poizon_sku_id' => 'Poizon SKU',
            'poizon_stock' => 'Остаток Poizon',
            'poizon_price_cny' => 'Poizon ¥',
            
            // NEW
            'color_variant' => 'Цвет варианта',
            'delivery_time_min' => 'Доставка (мин)',
            'delivery_time_max' => 'Доставка (макс)',
            'variant_vendor_code' => 'Артикул варианта',
            'images_json' => 'Изображения варианта (JSON)',
        ];
    }

    /**
     * Товар
     */
    public function getProduct()
    {
        return $this->hasOne(Product::class, ['id' => 'product_id']);
    }

    /**
     * В наличии?
     */
    public function inStock()
    {
        return $this->stock > 0;
    }
    
    /**
     * Получить цену в BYN (автоматический расчет)
     * Формула: (price_cny * курс * 1.5) + 40 BYN
     * С округлением до красивых значений (X99)
     * 
     * @return float|null
     */
    public function getPriceByn()
    {
        // Приоритет 1: price_byn из БД (уже округленная)
        if ($this->price_byn) {
            return $this->price_byn;
        }
        
        // Приоритет 2: расчет из price_cny через CurrencySetting
        if ($this->price_cny) {
            return \app\models\CurrencySetting::convertFromCny($this->price_cny, 'BYN');
        }
        
        // Приоритет 3: цена товара (с округлением)
        if ($this->product && $this->product->price) {
            return $this->roundToPrettyPrice($this->product->price);
        }
        
        return null;
    }
    
    /**
     * Округлить цену до "красивого" значения, заканчивающегося на 9
     * 
     * @param float $price
     * @return int
     */
    private function roundToPrettyPrice($price)
    {
        $floored = floor($price);
        $result = floor($floored / 10) * 10 + 9;
        
        if ($result > $floored) {
            $result -= 10;
        }
        
        return $result;
    }
    
    /**
     * Получить цену в ¥ (приоритет собственной цены, потом Poizon)
     * 
     * @return float|null
     */
    public function getPriceCny()
    {
        return $this->price_cny ?? $this->poizon_price_cny ?? null;
    }
    
    /**
     * Автоматически обновить цену BYN на основе цены CNY
     * Вызывается перед сохранением
     */
    public function beforeSave($insert)
    {
        if (parent::beforeSave($insert)) {
            // Автоматически рассчитываем price_byn если есть price_cny
            if ($this->price_cny && !$this->price_byn) {
                $this->price_byn = $this->getPriceByn();
            }
            
            // Цена уже округляется в CurrencySetting::convertFromCny()
            // Дополнительное округление не требуется
            
            return true;
        }
        return false;
    }
}
