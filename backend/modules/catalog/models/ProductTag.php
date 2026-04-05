<?php

/**
 * ProductTag — Модель тега товара
 * 
 * НАЗНАЧЕНИЕ:
 * Теги для группировки товаров по произвольным меткам:
 * "Новинка", "Хит продаж", "Скидка", "Эксклюзив" и т.д.
 * 
 * ОСНОВНЫЕ СВОЙСТВА:
 * - name: название тега
 * - slug: SEO-friendly URL
 * - color: HEX цвет для визуальной маркировки
 * - description: описание тега
 * - is_active: активность
 * - sort_order: порядок сортировки
 * 
 * СВЯЗИ:
 * - Product[] через ProductTagAssignment: товары с этим тегом
 * 
 * ПОВЕДЕНИЯ:
 * - TimestampBehavior (created_at, updated_at)
 * - SluggableBehavior (генерация slug из name)
 * 
 * ИСПОЛЬЗОВАНИЕ:
 * - ProductTagController (CRUD в админке)
 * - CatalogController (фильтрация по тегам)
 * - ProductCardWidget (отображение тегов на карточке)
 */

namespace app\backend\modules\catalog\models;

use Yii;
use yii\db\ActiveRecord;
use yii\behaviors\TimestampBehavior;
use yii\behaviors\SluggableBehavior;

/**
 * Модель ProductTag (Тег товара)
 *
 * @property int $id
 * @property string $name Название тега
 * @property string $slug SEO-friendly URL
 * @property string|null $color HEX цвет (#FF5733)
 * @property string|null $description Описание
 * @property int $is_active Активен ли тег
 * @property int $sort_order Порядок сортировки
 * @property int $created_at
 * @property int $updated_at
 * 
 * @property Product[] $products Товары с этим тегом
 * @property ProductTagAssignment[] $assignments
 */
class ProductTag extends ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%product_tag}}';
    }

    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        return [
            TimestampBehavior::class,
            [
                'class' => SluggableBehavior::class,
                'attribute' => 'name',
                'slugAttribute' => 'slug',
                'ensureUnique' => true,
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['name'], 'required'],
            [['name'], 'string', 'max' => 100],
            [['slug'], 'string', 'max' => 100],
            [['slug'], 'unique'],
            [['color'], 'string', 'max' => 7],
            [['color'], 'match', 'pattern' => '/^#[0-9A-Fa-f]{6}$/', 'message' => 'Цвет должен быть в формате HEX (#FF5733)'],
            [['description'], 'string'],
            [['is_active'], 'boolean'],
            [['is_active'], 'default', 'value' => true],
            [['sort_order'], 'integer'],
            [['sort_order'], 'default', 'value' => 0],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'name' => 'Название',
            'slug' => 'URL',
            'color' => 'Цвет',
            'description' => 'Описание',
            'is_active' => 'Активен',
            'sort_order' => 'Порядок',
            'created_at' => 'Создан',
            'updated_at' => 'Обновлен',
        ];
    }

    /**
     * Связь с товарами через assignment
     * @return \yii\db\ActiveQuery
     */
    public function getProducts()
    {
        return $this->hasMany(Product::class, ['id' => 'product_id'])
            ->viaTable('{{%product_tag_assignment}}', ['tag_id' => 'id']);
    }

    /**
     * Связь с assignment записями
     * @return \yii\db\ActiveQuery
     */
    public function getAssignments()
    {
        return $this->hasMany(ProductTagAssignment::class, ['tag_id' => 'id']);
    }

    /**
     * Получить активные теги
     * @return array
     */
    public static function getActiveTags()
    {
        return self::find()
            ->where(['is_active' => true])
            ->orderBy(['sort_order' => SORT_ASC, 'name' => SORT_ASC])
            ->all();
    }

    /**
     * Получить теги для dropdown
     * @return array
     */
    public static function getDropdownList()
    {
        return self::find()
            ->select(['name', 'id'])
            ->where(['is_active' => true])
            ->orderBy(['sort_order' => SORT_ASC, 'name' => SORT_ASC])
            ->indexBy('id')
            ->column();
    }

    /**
     * Получить тег по slug
     * @param string $slug
     * @return ProductTag|null
     */
    public static function findBySlug($slug)
    {
        return self::findOne(['slug' => $slug, 'is_active' => true]);
    }

    /**
     * Получить количество товаров с тегом
     * @return int
     */
    public function getProductsCount()
    {
        return $this->getProducts()->count();
    }

    /**
     * Проверка перед удалением
     * @return bool
     */
    public function beforeDelete()
    {
        if (!parent::beforeDelete()) {
            return false;
        }

        // Удаляем все связи
        ProductTagAssignment::deleteAll(['tag_id' => $this->id]);

        return true;
    }
}
