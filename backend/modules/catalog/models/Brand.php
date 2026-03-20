<?php

/**
 * Brand — Модель бренда
 * 
 * НАЗНАЧЕНИЕ:
 * Бренды товаров: Nike, Adidas, Puma и т.д. Используются для
 * фильтрации, навигации и SEO-страниц брендов.
 * 
 * ОСНОВНЫЕ СВОЙСТВА:
 * - name: название бренда
 * - slug: SEO-friendly URL
 * - description: описание бренда
 * - logo, logo_url: логотип (локальный или из Poizon)
 * - sort_order: порядок сортировки
 * - is_active: активность бренда
 * - meta_title, meta_description, meta_keywords: SEO
 * 
 * СВЯЗИ:
 * - Product[] (товары бренда)
 * - SizeGrid[] (размерные сетки бренда)
 * 
 * ПОВЕДЕНИЯ:
 * - TimestampBehavior (created_at, updated_at)
 * - SluggableBehavior (генерация slug из name)
 * 
 * ИСПОЛЬЗОВАНИЕ:
 * - CatalogController (страница бренда /catalog/brand/{slug})
 * - ProductController/admin (привязка товара к бренду)
 * - Фильтрация в каталоге
 */
namespace app\backend\modules\catalog\models;

use Yii;
use yii\db\ActiveRecord;
use yii\behaviors\TimestampBehavior;
use yii\behaviors\SluggableBehavior;
use app\backend\shared\components\SitemapNotifier;

/**
 * Модель Brand (Бренд)
 *
 * @property int $id
 * @property string $name Название бренда
 * @property string $slug SEO-friendly URL
 * @property string|null $description Описание бренда
 * @property string|null $logo Путь к логотипу
 * @property string|null $logo_url URL логотипа (из Poizon)
 * @property int $sort_order Порядок сортировки
 * @property int $is_active Активен ли бренд
 * @property string|null $meta_title SEO заголовок
 * @property string|null $meta_description SEO описание
 * @property string|null $meta_keywords SEO ключевые слова
 * @property int $created_at
 * @property int $updated_at
 * 
 * @property Product[] $products
 */
class Brand extends ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'brand';
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
            [['description', 'meta_description', 'meta_keywords'], 'string'],
            [['logo'], 'string', 'max' => 255],
            [['logo_url'], 'string', 'max' => 500],
            [['sort_order', 'poizon_id'], 'integer'],
            [['is_active'], 'boolean'],
            [['is_active'], 'default', 'value' => true],
            [['sort_order'], 'default', 'value' => 0],
            [['meta_title'], 'string', 'max' => 255],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'name' => 'Название бренда',
            'slug' => 'URL (slug)',
            'description' => 'Описание',
            'logo' => 'Логотип',
            'logo_url' => 'URL логотипа',
            'sort_order' => 'Порядок сортировки',
            'is_active' => 'Активен',
            'meta_title' => 'SEO заголовок',
            'meta_description' => 'SEO описание',
            'meta_keywords' => 'SEO ключевые слова',
            'created_at' => 'Создан',
            'updated_at' => 'Обновлен',
        ];
    }

    /**
     * Связь с товарами
     */
    public function getProducts()
    {
        return $this->hasMany(Product::class, ['brand_id' => 'id']);
    }

    /**
     * Получить активные товары бренда
     */
    public function getActiveProducts()
    {
        return $this->hasMany(Product::class, ['brand_id' => 'id'])
            ->where(['is_active' => true]);
    }

    /**
     * Получить количество товаров бренда
     */
    public function getProductsCount()
    {
        return (int)$this->getProducts()->where(['is_active' => true])->count();
    }
    
    /**
     * Добавляем виртуальное поле для API и array access
     */
    public function fields()
    {
        $fields = parent::fields();
        $fields['products_count'] = function($model) {
            return $model->getProductsCount();
        };
        return $fields;
    }

    /**
     * Получить все активные бренды
     */
    public static function getActiveBrands()
    {
        return static::find()
            ->where(['is_active' => true])
            ->orderBy(['sort_order' => SORT_ASC, 'name' => SORT_ASC])
            ->all();
    }

    /**
     * Получить бренд по slug
     */
    public static function findBySlug($slug)
    {
        return static::findOne(['slug' => $slug, 'is_active' => true]);
    }

    /**
     * Получить URL бренда
     */
    public function getUrl()
    {
        return \yii\helpers\Url::to(['/catalog/brand', 'slug' => $this->slug]);
    }

    /**
     * Получить SEO заголовок (или название по умолчанию)
     */
    public function getMetaTitle()
    {
        return $this->meta_title ?: $this->name . ' - Оригинальные товары | СНИКЕРХЭД';
    }

    /**
     * Получить SEO описание (или генерировать по умолчанию)
     */
    public function getMetaDescription()
    {
        return $this->meta_description ?: 'Оригинальные кроссовки и одежда ' . $this->name . ' с доставкой из США и Европы. Гарантия подлинности.';
    }

    /**
     * Получить URL логотипа (приоритет logo_url, затем logo)
     */
    public function getLogoUrl()
    {
        if ($this->logo_url) {
            return $this->logo_url;
        }
        
        if ($this->logo) {
            // Если это уже полный URL
            if (strpos($this->logo, 'http') === 0) {
                return $this->logo;
            }
            // Иначе это локальный путь
            return \Yii::getAlias('@web') . '/' . ltrim($this->logo, '/');
        }
        
        // Дефолтный placeholder
        return \Yii::getAlias('@web') . '/images/no-brand-logo.png';
    }
}
