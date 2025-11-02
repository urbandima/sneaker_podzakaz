<?php

namespace app\models;

use Yii;
use yii\db\ActiveRecord;

/**
 * Модель ProductImage (Изображение товара)
 *
 * @property int $id
 * @property int $product_id
 * @property string $image Путь к изображению
 * @property int $sort_order Порядок сортировки
 * @property int $is_main Главное изображение
 * @property string $created_at
 * 
 * @property Product $product
 */
class ProductImage extends ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'product_image';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['product_id', 'image'], 'required'],
            [['product_id', 'sort_order'], 'integer'],
            [['is_main'], 'boolean'],
            [['is_main'], 'default', 'value' => 0],
            [['sort_order'], 'default', 'value' => 0],
            [['image'], 'string', 'max' => 255],
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
            'image' => 'Путь к изображению',
            'sort_order' => 'Порядок',
            'is_main' => 'Главное изображение',
            'created_at' => 'Создано',
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
     * Получить URL изображения
     */
    public function getUrl()
    {
        return $this->image;
    }

    /**
     * Установить как главное изображение
     */
    public function setAsMain()
    {
        // Снять флаг "главное" у других изображений этого товара
        static::updateAll(
            ['is_main' => 0],
            ['product_id' => $this->product_id]
        );
        
        // Установить флаг для текущего
        $this->is_main = 1;
        return $this->save(false);
    }
}
