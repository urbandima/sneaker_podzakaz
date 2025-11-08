<?php

namespace app\models;

use Yii;
use yii\db\ActiveRecord;
use yii\behaviors\TimestampBehavior;

/**
 * Модель ProductSizeImage (Изображения вариантов размеров)
 *
 * @property int $id
 * @property int $product_size_id
 * @property string $image_url
 * @property int $sort_order
 * @property int $is_main
 * @property string $created_at
 * @property string $updated_at
 * 
 * @property ProductSize $productSize
 */
class ProductSizeImage extends ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'product_size_image';
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
            [['product_size_id', 'image_url'], 'required'],
            [['product_size_id', 'sort_order'], 'integer'],
            [['is_main'], 'boolean'],
            [['image_url'], 'string', 'max' => 500],
            [['image_url'], 'url'],
            [['product_size_id'], 'exist', 'targetClass' => ProductSize::class, 'targetAttribute' => 'id'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'product_size_id' => 'Размер',
            'image_url' => 'URL изображения',
            'sort_order' => 'Порядок',
            'is_main' => 'Главное',
            'created_at' => 'Создано',
            'updated_at' => 'Обновлено',
        ];
    }

    /**
     * Размер
     */
    public function getProductSize()
    {
        return $this->hasOne(ProductSize::class, ['id' => 'product_size_id']);
    }
    
    /**
     * Получить главное изображение варианта
     */
    public static function getMainImage($productSizeId)
    {
        return self::find()
            ->where(['product_size_id' => $productSizeId, 'is_main' => 1])
            ->orderBy(['sort_order' => SORT_ASC])
            ->one();
    }
    
    /**
     * Получить все изображения варианта
     */
    public static function getImages($productSizeId)
    {
        return self::find()
            ->where(['product_size_id' => $productSizeId])
            ->orderBy(['sort_order' => SORT_ASC, 'id' => SORT_ASC])
            ->all();
    }
}
