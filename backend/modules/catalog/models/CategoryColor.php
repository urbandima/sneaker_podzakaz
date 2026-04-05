<?php

/**
 * CategoryColor — Модель цвета категории
 * 
 * НАЗНАЧЕНИЕ:
 * Цветовая маркировка категорий для визуального выделения в меню,
 * навигации, карточках товаров.
 * 
 * ОСНОВНЫЕ СВОЙСТВА:
 * - category_id: ID категории
 * - color_code: HEX код цвета (#FF5733)
 * - label: подпись/название цвета
 * - is_active: активность
 * 
 * СВЯЗИ:
 * - Category: категория
 * 
 * ИСПОЛЬЗОВАНИЕ:
 * - CategoryController (выбор цвета в форме)
 * - MenuWidget (цветные метки в меню)
 * - Catalog views (индикация категории)
 */

namespace app\backend\modules\catalog\models;

use Yii;
use yii\db\ActiveRecord;
use yii\behaviors\TimestampBehavior;

/**
 * Модель CategoryColor (Цвет категории)
 *
 * @property int $id
 * @property int $category_id ID категории
 * @property string $color_code HEX цвет (#FF5733)
 * @property string|null $label Подпись к цвету
 * @property int $is_active Активен
 * @property int $created_at
 * @property int $updated_at
 * 
 * @property Category $category
 */
class CategoryColor extends ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%category_color}}';
    }

    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        return [
            TimestampBehavior::class,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['category_id', 'color_code'], 'required'],
            [['category_id', 'is_active'], 'integer'],
            [['is_active'], 'default', 'value' => true],
            [['color_code'], 'string', 'max' => 7],
            [['color_code'], 'match', 'pattern' => '/^#[0-9A-Fa-f]{6}$/', 'message' => 'Цвет должен быть в формате HEX (#FF5733)'],
            [['label'], 'string', 'max' => 50],
            [
                'category_id',
                'exist',
                'skipOnError' => true,
                'targetClass' => Category::class,
                'targetAttribute' => ['category_id' => 'id']
            ],
            [
                'category_id',
                'unique',
                'message' => 'Для этой категории уже задан цвет'
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
            'category_id' => 'Категория',
            'color_code' => 'Цвет (HEX)',
            'label' => 'Подпись',
            'is_active' => 'Активен',
            'created_at' => 'Создан',
            'updated_at' => 'Обновлен',
        ];
    }

    /**
     * Связь с категорией
     * @return \yii\db\ActiveQuery
     */
    public function getCategory()
    {
        return $this->hasOne(Category::class, ['id' => 'category_id']);
    }

    /**
     * Получить цвет категории
     * @param int $categoryId
     * @return CategoryColor|null
     */
    public static function findByCategory($categoryId)
    {
        return self::findOne(['category_id' => $categoryId, 'is_active' => true]);
    }

    /**
     * Получить HEX код цвета категории
     * @param int $categoryId
     * @return string|null
     */
    public static function getColorCode($categoryId)
    {
        $color = self::findByCategory($categoryId);
        return $color ? $color->color_code : null;
    }

    /**
     * Получить список категорий с цветами
     * @return array [category_id => color_code]
     */
    public static function getCategoriesColors()
    {
        return self::find()
            ->select(['color_code', 'category_id'])
            ->where(['is_active' => true])
            ->indexBy('category_id')
            ->column();
    }

    /**
     * Получить CSS стиль для цвета
     * @return string
     */
    public function getCssStyle()
    {
        return 'background-color: ' . $this->color_code . ';';
    }

    /**
     * Получить CSS класс для индикатора
     * @return string
     */
    public function getIndicatorHtml()
    {
        return sprintf(
            '<span class="category-color-indicator" style="background-color: %s;" title="%s"></span>',
            $this->color_code,
            $this->label ?: $this->category->name ?? ''
        );
    }

    /**
     * Сохранить или обновить цвет категории
     * @param int $categoryId
     * @param string $colorCode
     * @param string|null $label
     * @return bool
     */
    public static function saveCategoryColor($categoryId, $colorCode, $label = null)
    {
        $model = self::findOne(['category_id' => $categoryId]) ?: new self();
        $model->category_id = $categoryId;
        $model->color_code = $colorCode;
        $model->label = $label;
        $model->is_active = true;

        return $model->save();
    }

    /**
     * Удалить цвет категории
     * @param int $categoryId
     * @return int
     */
    public static function removeCategoryColor($categoryId)
    {
        return self::deleteAll(['category_id' => $categoryId]);
    }
}
