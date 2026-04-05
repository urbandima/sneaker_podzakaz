<?php

/**
 * ProductTagAssignment — Связующая модель товара и тега
 * 
 * НАЗНАЧЕНИЕ:
 * Many-to-many связь между Product и ProductTag.
 * Один товар может иметь множество тегов, один тег может быть у многих товаров.
 * 
 * СВЯЗИ:
 * - Product: товар
 * - ProductTag: тег
 * 
 * ИСПОЛЬЗОВАНИЕ:
 * - Не используется напрямую, работа через Product::getTags() и ProductTag::getProducts()
 * - Массовое назначение тегов в ProductTagController::actionAssign()
 */

namespace app\backend\modules\catalog\models;

use Yii;
use yii\db\ActiveRecord;

/**
 * Модель ProductTagAssignment (Связь товара и тега)
 *
 * @property int $id
 * @property int $product_id ID товара
 * @property int $tag_id ID тега
 * @property int $created_at
 * 
 * @property Product $product
 * @property ProductTag $tag
 */
class ProductTagAssignment extends ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%product_tag_assignment}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['product_id', 'tag_id'], 'required'],
            [['product_id', 'tag_id'], 'integer'],
            [['product_id', 'tag_id'], 'unique', 'targetAttribute' => ['product_id', 'tag_id']],
            [
                'product_id',
                'exist',
                'skipOnError' => true,
                'targetClass' => Product::class,
                'targetAttribute' => ['product_id' => 'id']
            ],
            [
                'tag_id',
                'exist',
                'skipOnError' => true,
                'targetClass' => ProductTag::class,
                'targetAttribute' => ['tag_id' => 'id']
            ],
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
            'tag_id' => 'Тег',
            'created_at' => 'Создан',
        ];
    }

    /**
     * Связь с товаром
     * @return \yii\db\ActiveQuery
     */
    public function getProduct()
    {
        return $this->hasOne(Product::class, ['id' => 'product_id']);
    }

    /**
     * Связь с тегом
     * @return \yii\db\ActiveQuery
     */
    public function getTag()
    {
        return $this->hasOne(ProductTag::class, ['id' => 'tag_id']);
    }

    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        return [
            [
                'class' => \yii\behaviors\TimestampBehavior::class,
                'createdAtAttribute' => 'created_at',
                'updatedAtAttribute' => false,
            ],
        ];
    }

    /**
     * Массовое назначение тегов товару
     * @param int $productId
     * @param array $tagIds
     * @return bool
     */
    public static function assignTags($productId, array $tagIds)
    {
        // Удаляем существующие связи
        self::deleteAll(['product_id' => $productId]);

        // Создаем новые связи
        foreach ($tagIds as $tagId) {
            $assignment = new self();
            $assignment->product_id = $productId;
            $assignment->tag_id = $tagId;
            if (!$assignment->save()) {
                return false;
            }
        }

        return true;
    }

    /**
     * Получить ID тегов товара
     * @param int $productId
     * @return array
     */
    public static function getTagIdsByProduct($productId)
    {
        return self::find()
            ->select(['tag_id'])
            ->where(['product_id' => $productId])
            ->column();
    }

    /**
     * Получить ID товаров по тегу
     * @param int $tagId
     * @return array
     */
    public static function getProductIdsByTag($tagId)
    {
        return self::find()
            ->select(['product_id'])
            ->where(['tag_id' => $tagId])
            ->column();
    }
}
