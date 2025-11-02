<?php

namespace app\models;

use Yii;
use yii\db\ActiveRecord;
use yii\behaviors\TimestampBehavior;

/**
 * Модель ProductSize (Размер товара)
 *
 * @property int $id
 * @property int $product_id
 * @property string $size Размер (40, 41, 42, S, M, L, XL и т.д.)
 * @property int $stock Количество на складе
 * @property int $is_available Доступен для заказа
 * @property string $created_at
 * @property string $updated_at
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
            [['product_id', 'stock'], 'integer'],
            [['stock'], 'default', 'value' => 0],
            [['is_available'], 'boolean'],
            [['is_available'], 'default', 'value' => 1],
            [['size'], 'string', 'max' => 50],
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
            'product_id' => 'Товар',
            'size' => 'Размер',
            'stock' => 'Остаток на складе',
            'is_available' => 'Доступен',
            'created_at' => 'Создан',
            'updated_at' => 'Обновлен',
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
}
