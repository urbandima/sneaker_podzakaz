<?php

namespace app\models;

use Yii;
use yii\db\ActiveRecord;

/**
 * Модель ProductColor (Цвет товара)
 *
 * @property int $id
 * @property int $product_id
 * @property string $color_name Название цвета (Белый, Черный и т.д.)
 * @property string|null $color_hex HEX код цвета (#FFFFFF)
 * @property int $is_available Доступен для заказа
 * @property string $created_at
 * 
 * @property Product $product
 */
class ProductColor extends ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'product_color';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['product_id', 'color_name'], 'required'],
            [['product_id'], 'integer'],
            [['is_available'], 'boolean'],
            [['is_available'], 'default', 'value' => 1],
            [['color_name'], 'string', 'max' => 100],
            [['color_hex'], 'string', 'max' => 7],
            [['color_hex'], 'match', 'pattern' => '/^#[0-9A-Fa-f]{6}$/', 'message' => 'Неверный формат HEX цвета'],
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
            'color_name' => 'Название цвета',
            'color_hex' => 'HEX код цвета',
            'is_available' => 'Доступен',
            'created_at' => 'Создан',
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
     * Алиасы для удобства
     */
    public function getHex()
    {
        return $this->color_hex;
    }
    
    public function getName()
    {
        return $this->color_name;
    }
}
