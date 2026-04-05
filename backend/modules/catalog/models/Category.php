<?php

/**
 * Category — Модель категории товаров
 * 
 * НАЗНАЧЕНИЕ:
 * Иерархические категории товаров: обувь, одежда, аксессуары и т.д.
 * Поддержка вложенности (дерево категорий).
 * 
 * ОСНОВНЫЕ СВОЙСТВА:
 * - name: название категории
 * - slug: SEO-friendly URL
 * - parent_id: ID родительской категории (для вложенности)
 * - description: описание
 * - image: изображение категории
 * - sort_order: порядок сортировки
 * - is_active: активность
 * - meta_title, meta_description, meta_keywords: SEO
 * 
 * СВЯЗИ:
 * - Category (parent): родительская категория
 * - Category[] (children): дочерние категории
 * - Product[]: товары категории
 * 
 * МЕТОДЫ:
 * - getChildrenIds(): ID всех дочерних категорий (для фильтрации)
 * - findBySlug(): поиск по slug
 * 
 * ПОВЕДЕНИЯ:
 * - TimestampBehavior (created_at, updated_at)
 * - SluggableBehavior (генерация slug из name)
 * 
 * ИСПОЛЬЗОВАНИЕ:
 * - CatalogController (страница категории /catalog/category/{slug})
 * - Фильтрация по категориям в каталоге
 * - Навигация (breadcrumbs, меню)
 */
namespace app\backend\modules\catalog\models;

use Yii;
use yii\db\ActiveRecord;
use yii\behaviors\TimestampBehavior;
use yii\behaviors\SluggableBehavior;
use app\backend\shared\components\SitemapNotifier;

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
 * @property string|null $color_code HEX цвет категории
 * 
 * @property Category $parent Родительская категория
 * @property Category[] $children Дочерние категории
 * @property Product[] $products
 * @property CategoryColor $color Цветовая маркировка
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
        SitemapNotifier::scheduleRegeneration();
    }

    public function afterDelete()
    {
        parent::afterDelete();
        $this->invalidateCatalogCache();
        SitemapNotifier::scheduleRegeneration();
    }

    protected function invalidateCatalogCache()
    {
        $cache = Yii::$app->cache;
        if ($cache) {
            $cache->delete('catalog_filters_' . md5(serialize(['is_active' => true])));
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
            [['parent_id', 'sort_order', 'poizon_id'], 'integer'],
            [['description'], 'string'],
            [['image'], 'string', 'max' => 255],
            [['is_active'], 'boolean'],
            [['is_active'], 'default', 'value' => true],
            [['sort_order'], 'default', 'value' => 0],
            // SEO поля - безопасные (могут не существовать в БД)
            [['meta_title', 'meta_description', 'meta_keywords'], 'safe'],
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
            ->where(['is_active' => true])
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
        return $this->getProducts()->where(['is_active' => true]);
    }

    /**
     * Получить все активные корневые категории
     */
    public static function getRootCategories()
    {
        return static::find()
            ->where(['parent_id' => null, 'is_active' => true])
            ->orderBy(['sort_order' => SORT_ASC, 'name' => SORT_ASC])
            ->all();
    }

    /**
     * Получить категорию по slug
     */
    public static function findBySlug($slug)
    {
        return static::findOne(['slug' => $slug, 'is_active' => true]);
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
            ->where(['category_id' => $categoryIds, 'is_active' => true])
            ->count();
    }

    /**
     * Цветовая маркировка категории
     * @return \yii\db\ActiveQuery
     */
    public function getColor()
    {
        return $this->hasOne(CategoryColor::class, ['category_id' => 'id'])
            ->where(['is_active' => true]);
    }

    /**
     * Получить HEX код цвета категории
     * @return string|null
     */
    public function getColorCode()
    {
        return $this->color_code ?: ($this->color ? $this->color->color_code : null);
    }

    /**
     * Получить HTML индикатор цвета
     * @return string
     */
    public function getColorIndicator()
    {
        $colorCode = $this->getColorCode();
        if (!$colorCode) {
            return '';
        }

        return sprintf(
            '<span class="category-color-indicator" style="background-color: %s;"></span>',
            $colorCode
        );
    }
}
