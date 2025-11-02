<?php

namespace app\models;

use Yii;
use yii\db\ActiveRecord;
use yii\behaviors\TimestampBehavior;
use yii\behaviors\SluggableBehavior;

/**
 * Модель Category (Категория товаров)
 *
 * @property int $id
 * @property string $name Название категории
 * @property string $slug SEO-friendly URL
 * @property int|null $parent_id ID родительской категории
 * @property string|null $description Описание
 * @property string|null $image Изображение категории
 * @property int $sort_order Порядок сортировки
 * @property int $is_active Активна ли категория
 * @property string|null $meta_title SEO заголовок
 * @property string|null $meta_description SEO описание
 * @property string|null $meta_keywords SEO ключевые слова
 * @property int $created_at
 * @property int $updated_at
 * 
 * @property Category $parent Родительская категория
 * @property Category[] $children Дочерние категории
 * @property Product[] $products
 */
class Category extends ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'category';
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
            [
                'class' => SluggableBehavior::class,
                'attribute' => 'name',
                'slugAttribute' => 'slug',
                'immutable' => false,
                'ensureUnique' => true,
            ],
        ];
    }

    /**
     * После сохранения/удаления - инвалидация кэша
     */
    public function afterSave($insert, $changedAttributes)
    {
        parent::afterSave($insert, $changedAttributes);
        $this->invalidateCatalogCache();
    }

    public function afterDelete()
    {
        parent::afterDelete();
        $this->invalidateCatalogCache();
    }

    protected function invalidateCatalogCache()
    {
        $cache = Yii::$app->cache;
        if ($cache) {
            $cache->delete('catalog_filters_' . md5(serialize(['is_active' => 1])));
        }
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['name'], 'required'],
            [['name'], 'string', 'max' => 255],
            [['slug'], 'string', 'max' => 255],
            [['slug'], 'unique'],
            [['parent_id', 'sort_order'], 'integer'],
            [['description', 'meta_description', 'meta_keywords'], 'string'],
            [['image'], 'string', 'max' => 255],
            [['is_active'], 'boolean'],
            [['is_active'], 'default', 'value' => 1],
            [['sort_order'], 'default', 'value' => 0],
            [['meta_title'], 'string', 'max' => 255],
            [['parent_id'], 'exist', 'targetClass' => Category::class, 'targetAttribute' => 'id'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'name' => 'Название категории',
            'slug' => 'URL (slug)',
            'parent_id' => 'Родительская категория',
            'description' => 'Описание',
            'image' => 'Изображение',
            'sort_order' => 'Порядок сортировки',
            'is_active' => 'Активна',
            'meta_title' => 'SEO заголовок',
            'meta_description' => 'SEO описание',
            'meta_keywords' => 'SEO ключевые слова',
            'created_at' => 'Создана',
            'updated_at' => 'Обновлена',
        ];
    }

    /**
     * Родительская категория
     */
    public function getParent()
    {
        return $this->hasOne(Category::class, ['id' => 'parent_id']);
    }

    /**
     * Дочерние категории
     */
    public function getChildren()
    {
        return $this->hasMany(Category::class, ['parent_id' => 'id'])
            ->where(['is_active' => 1])
            ->orderBy(['sort_order' => SORT_ASC, 'name' => SORT_ASC]);
    }

    /**
     * Все товары категории
     */
    public function getProducts()
    {
        return $this->hasMany(Product::class, ['category_id' => 'id']);
    }

    /**
     * Активные товары категории
     */
    public function getActiveProducts()
    {
        return $this->hasMany(Product::class, ['category_id' => 'id'])
            ->where(['is_active' => 1]);
    }

    /**
     * Получить все активные корневые категории
     */
    public static function getRootCategories()
    {
        return static::find()
            ->where(['parent_id' => null, 'is_active' => 1])
            ->orderBy(['sort_order' => SORT_ASC, 'name' => SORT_ASC])
            ->all();
    }

    /**
     * Получить категорию по slug
     */
    public static function findBySlug($slug)
    {
        return static::findOne(['slug' => $slug, 'is_active' => 1]);
    }

    /**
     * Проверка - корневая ли категория
     */
    public function isRoot()
    {
        return $this->parent_id === null;
    }

    /**
     * Получить хлебные крошки (breadcrumbs)
     */
    public function getBreadcrumbs()
    {
        $breadcrumbs = [];
        $category = $this;
        
        while ($category) {
            array_unshift($breadcrumbs, $category);
            $category = $category->parent;
        }
        
        return $breadcrumbs;
    }

    /**
     * Получить все ID дочерних категорий (рекурсивно)
     */
    public function getChildrenIds()
    {
        $ids = [$this->id];
        
        foreach ($this->children as $child) {
            $ids = array_merge($ids, $child->getChildrenIds());
        }
        
        return $ids;
    }

    /**
     * Получить URL категории
     */
    public function getUrl()
    {
        return \yii\helpers\Url::to(['/catalog/category', 'slug' => $this->slug]);
    }

    /**
     * Получить SEO заголовок
     */
    public function getMetaTitle()
    {
        return $this->meta_title ?: $this->name . ' - Оригинальные товары | СНИКЕРХЭД';
    }

    /**
     * Получить SEO описание
     */
    public function getMetaDescription()
    {
        return $this->meta_description ?: 'Купить оригинальные ' . mb_strtolower($this->name) . ' известных брендов. Широкий выбор, доставка по Беларуси.';
    }

    /**
     * Количество товаров в категории (включая подкатегории)
     */
    public function getTotalProductsCount()
    {
        $categoryIds = $this->getChildrenIds();
        
        return Product::find()
            ->where(['category_id' => $categoryIds, 'is_active' => 1])
            ->count();
    }
}
