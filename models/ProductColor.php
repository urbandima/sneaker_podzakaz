<?php

namespace app\models;

use Yii;
use yii\db\ActiveRecord;

/**
 * Модель ProductColor (Цвет товара)
 *
 * @property int $id
 * @property int $product_id
 * @property string $name Название цвета (Белый, Черный и т.д.)
 * @property string|null $hex HEX код цвета (#FFFFFF)
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
            [['product_id', 'name'], 'required'],
            [['product_id'], 'integer'],
            [['name'], 'string', 'max' => 100],
            [['hex'], 'string', 'max' => 7],
            [['hex'], 'match', 'pattern' => '/^#[0-9A-Fa-f]{6}$/', 'message' => 'Неверный формат HEX цвета'],
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
            'name' => 'Название цвета',
            'hex' => 'HEX код цвета',
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
     * Алиасы для удобства (обратная совместимость)
     */
    public function getHex()
    {
        return $this->hex;
    }
    
    public function getName()
    {
        return $this->name;
    }
    
    public function getColorName()
    {
        return $this->name;
    }
    
    public function getColorHex()
    {
        return $this->hex;
    }
}
