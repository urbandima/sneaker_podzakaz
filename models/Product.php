<?php

namespace app\models;

use Yii;
use yii\db\ActiveRecord;
use yii\behaviors\TimestampBehavior;
use yii\behaviors\SluggableBehavior;

/**
 * Модель Product (Товар)
 *
 * @property int $id
 * @property int $category_id
 * @property int $brand_id
 * @property string $name Название товара
 * @property string $slug SEO-friendly URL
 * @property string|null $description Описание
 * @property float $price Цена
 * @property float|null $old_price Старая цена (для отображения скидки)
 * @property string|null $main_image Главное изображение
 * @property int $is_active Активен ли товар
 * @property int $is_featured Товар в избранном/хите
 * @property string $stock_status Статус наличия (in_stock, out_of_stock, preorder)
 * @property int $views_count Количество просмотров
 * @property string|null $meta_title SEO заголовок
 * @property string|null $meta_description SEO описание
 * @property string|null $meta_keywords SEO ключевые слова
 * @property int $created_at
 * @property int $updated_at
 * 
 * NEW FILTER FIELDS:
 * @property string|null $material Материал
 * @property string|null $season Сезон
 * @property string|null $gender Пол
 * @property string|null $height Высота
 * @property string|null $fastening Застежка
 * @property string|null $country Страна производства
 * @property int $has_bonus Бонусы
 * @property int $promo_2for1 Акция 2+1
 * @property int $is_exclusive Эксклюзив
 * @property float $rating Рейтинг
 * @property int $reviews_count Количество отзывов
 * 
 * POIZON INTEGRATION FIELDS:
 * @property string|null $sku Уникальный SKU товара
 * @property string|null $poizon_id ID товара в Poizon
 * @property string|null $poizon_spu_id SPU ID в Poizon
 * @property string|null $poizon_url URL товара на Poizon
 * @property float|null $poizon_price_cny Цена в CNY на Poizon
 * @property string|null $last_sync_at Последняя синхронизация
 * @property string|null $upper_material Материал верха
 * @property string|null $sole_material Материал подошвы
 * @property string|null $color_description Описание цвета
 * @property string|null $style_code Код стиля/модели
 * @property int|null $release_year Год выпуска
 * @property int $is_limited Лимитированная модель
 * @property int|null $weight Вес в граммах
 * @property string|null $series_name Название серии
 * @property int|null $delivery_time_min Минимальный срок доставки
 * @property int|null $delivery_time_max Максимальный срок доставки
 * @property string|null $related_products_json Связанные товары JSON
 * 
 * @property Category $category
 * @property Brand $brand
 * @property ProductImage[] $images
 * @property ProductSize[] $sizes
 * @property ProductColor[] $colors
 * @property ProductFavorite[] $favorites
 * @property Style[] $styles
 * @property Technology[] $technologies
 * @property ProductReview[] $reviews
 */
class Product extends ActiveRecord
{
    const STOCK_IN_STOCK = 'in_stock';
    const STOCK_OUT_OF_STOCK = 'out_of_stock';
    const STOCK_PREORDER = 'preorder';

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'product';
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
     * Перед сохранением - автозаполнение денормализованных полей
     */
    public function beforeSave($insert)
    {
        if (parent::beforeSave($insert)) {
            // Автозаполнение денормализованных полей для решения N+1 проблемы
            if ($this->brand_id && !$this->brand_name) {
                $brand = Brand::findOne($this->brand_id);
                if ($brand) {
                    $this->brand_name = $brand->name;
                }
            }
            
            if ($this->category_id && !$this->category_name) {
                $category = Category::findOne($this->category_id);
                if ($category) {
                    $this->category_name = $category->name;
                }
            }
            
            if ($this->main_image && !$this->main_image_url) {
                $this->main_image_url = $this->main_image;
            }
            
            return true;
        }
        return false;
    }

    /**
     * После сохранения - инвалидация кэша
     */
    public function afterSave($insert, $changedAttributes)
    {
        parent::afterSave($insert, $changedAttributes);
        
        // Обновляем денормализованные поля если изменились связанные данные
        $needsUpdate = false;
        if (isset($changedAttributes['brand_id'])) {
            $brand = $this->brand;
            if ($brand) {
                $this->updateAttributes(['brand_name' => $brand->name]);
                $needsUpdate = true;
            }
        }
        
        if (isset($changedAttributes['category_id'])) {
            $category = $this->category;
            if ($category) {
                $this->updateAttributes(['category_name' => $category->name]);
                $needsUpdate = true;
            }
        }
        
        if (isset($changedAttributes['main_image'])) {
            $this->updateAttributes(['main_image_url' => $this->main_image]);
            $needsUpdate = true;
        }
        
        $this->invalidateCatalogCache();
    }

    /**
     * После удаления - инвалидация кэша
     */
    public function afterDelete()
    {
        parent::afterDelete();
        $this->invalidateCatalogCache();
    }

    /**
     * Инвалидация кэша каталога (универсальная для FileCache и Redis)
     */
    protected function invalidateCatalogCache()
    {
        $cache = Yii::$app->cache;
        if (!$cache) {
            return;
        }
        
        // Используем tagged cache (работает и с FileCache, и с Redis)
        \yii\caching\TagDependency::invalidate($cache, [
            'catalog',           // Все данные каталога
            'catalog-filters',   // Фильтры
            'catalog-products',  // Товары
        ]);
        
        // Дополнительно для FileCache: очищаем известные ключи
        if ($cache instanceof \yii\caching\FileCache) {
            $cache->delete('filters_data_v2');
            $cache->delete('catalog_count');
        }
        
        Yii::info('Cache invalidated: catalog, filters, products', 'cache');
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['name', 'category_id', 'brand_id', 'price'], 'required'],
            [['category_id', 'brand_id', 'views_count', 'reviews_count'], 'integer'],
            [['price', 'old_price', 'rating'], 'number', 'min' => 0],
            [['rating'], 'number', 'max' => 5],
            [['name', 'slug', 'main_image', 'meta_title'], 'string', 'max' => 255],
            [['slug'], 'unique'],
            [['description', 'meta_description', 'meta_keywords'], 'string'],
            [['is_active', 'is_featured', 'has_bonus', 'promo_2for1', 'is_exclusive'], 'boolean'],
            [['is_active'], 'default', 'value' => 1],
            [['is_featured', 'has_bonus', 'promo_2for1', 'is_exclusive'], 'default', 'value' => 0],
            [['views_count', 'reviews_count'], 'default', 'value' => 0],
            [['rating'], 'default', 'value' => 0],
            [['stock_status'], 'string'],
            [['stock_status'], 'in', 'range' => [self::STOCK_IN_STOCK, self::STOCK_OUT_OF_STOCK, self::STOCK_PREORDER]],
            [['stock_status'], 'default', 'value' => self::STOCK_IN_STOCK],
            
            // NEW FILTER FIELDS
            [['material', 'season', 'gender', 'height', 'fastening', 'country'], 'string', 'max' => 50],
            [['material'], 'in', 'range' => ['leather', 'textile', 'synthetic', 'suede', 'mesh', 'canvas']],
            [['season'], 'in', 'range' => ['summer', 'winter', 'demi', 'all']],
            [['gender'], 'in', 'range' => ['male', 'female', 'unisex']],
            [['height'], 'in', 'range' => ['low', 'mid', 'high']],
            [['fastening'], 'in', 'range' => ['laces', 'velcro', 'zipper', 'slip_on']],
            
            // POIZON INTEGRATION FIELDS
            [['sku', 'style_code', 'vendor_code'], 'string', 'max' => 100],
            // poizon_id и poizon_variant_id могут приходить как числа - конвертируем в строки
            [['poizon_id', 'poizon_spu_id', 'poizon_variant_id'], 'filter', 'filter' => function($value) {
                return $value !== null ? (string)$value : null;
            }],
            [['poizon_id', 'poizon_spu_id', 'poizon_variant_id'], 'string', 'max' => 100],
            [['sku'], 'unique'],
            [['poizon_url'], 'string', 'max' => 500],
            [['poizon_price_cny', 'purchase_price'], 'number', 'min' => 0],
            [['last_sync_at'], 'safe'],
            [['upper_material', 'sole_material', 'color_description', 'series_name'], 'string', 'max' => 255],
            [['release_year', 'weight', 'favorite_count', 'stock_count', 'delivery_time_min', 'delivery_time_max', 'parent_product_id'], 'integer'],
            [['related_products_json'], 'safe'],
            [['is_limited'], 'boolean'],
            [['is_limited'], 'default', 'value' => 0],
            [['country_of_origin'], 'string', 'max' => 100],
            [['properties', 'sizes_data', 'keywords', 'variant_params'], 'safe'],
            
            [['category_id'], 'exist', 'targetClass' => Category::class, 'targetAttribute' => 'id'],
            [['brand_id'], 'exist', 'targetClass' => Brand::class, 'targetAttribute' => 'id'],
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
            'brand_id' => 'Бренд',
            'name' => 'Название товара',
            'slug' => 'URL (slug)',
            'description' => 'Описание',
            'price' => 'Цена',
            'old_price' => 'Старая цена',
            'main_image' => 'Главное изображение',
            'is_active' => 'Активен',
            'is_featured' => 'Хит продаж',
            'stock_status' => 'Статус наличия',
            'views_count' => 'Просмотров',
            'meta_title' => 'SEO заголовок',
            'meta_description' => 'SEO описание',
            'meta_keywords' => 'SEO ключевые слова',
            'created_at' => 'Создан',
            'updated_at' => 'Обновлен',
            
            // NEW LABELS
            'material' => 'Материал',
            'season' => 'Сезон',
            'gender' => 'Пол',
            'height' => 'Высота',
            'fastening' => 'Застежка',
            'country' => 'Страна производства',
            'has_bonus' => 'Бонусы',
            'promo_2for1' => 'Акция 2+1',
            'is_exclusive' => 'Эксклюзив',
            'rating' => 'Рейтинг',
            'reviews_count' => 'Количество отзывов',
            
            // POIZON LABELS
            'sku' => 'SKU',
            'poizon_id' => 'Poizon ID',
            'poizon_spu_id' => 'Poizon SPU ID',
            'poizon_url' => 'Ссылка Poizon',
            'poizon_price_cny' => 'Цена CNY',
            'last_sync_at' => 'Последняя синхронизация',
            'upper_material' => 'Материал верха',
            'sole_material' => 'Материал подошвы',
            'color_description' => 'Описание цвета',
            'style_code' => 'Код модели',
            'release_year' => 'Год выпуска',
            'is_limited' => 'Лимитированная',
            'weight' => 'Вес (г)',
            'series_name' => 'Серия товара',
            'delivery_time_min' => 'Срок доставки (мин)',
            'delivery_time_max' => 'Срок доставки (макс)',
            'related_products_json' => 'Связанные товары',
        ];
    }

    /**
     * Категория товара
     */
    public function getCategory()
    {
        return $this->hasOne(Category::class, ['id' => 'category_id']);
    }

    /**
     * Бренд товара
     */
    public function getBrand()
    {
        return $this->hasOne(Brand::class, ['id' => 'brand_id']);
    }

    /**
     * Изображения товара
     */
    public function getImages()
    {
        return $this->hasMany(ProductImage::class, ['product_id' => 'id'])
            ->orderBy(['is_main' => SORT_DESC, 'sort_order' => SORT_ASC]);
    }
    
    /**
     * Характеристики товара
     */
    public function getCharacteristics()
    {
        return $this->hasMany(ProductCharacteristic::class, ['product_id' => 'id'])->orderBy(['sort_order' => SORT_ASC]);
    }
    
    /**
     * Все размеры товара
     */
    public function getSizes()
    {
        return $this->hasMany(ProductSize::class, ['product_id' => 'id'])
            ->orderBy(['sort_order' => SORT_ASC, 'size' => SORT_ASC]);
    }
    
    /**
     * Доступные размеры
     */
    public function getAvailableSizes()
    {
        return $this->hasMany(ProductSize::class, ['product_id' => 'id'])
            ->where(['is_available' => 1])
            ->orderBy(['size' => SORT_ASC]);
    }

    /**
     * Цвета товара
     */
    public function getColors()
    {
        return $this->hasMany(ProductColor::class, ['product_id' => 'id']);
    }

    /**
     * Избранное
     */
    public function getFavorites()
    {
        return $this->hasMany(ProductFavorite::class, ['product_id' => 'id']);
    }

    /**
     * Стили товара
     */
    public function getStyles()
    {
        return $this->hasMany(Style::class, ['id' => 'style_id'])
            ->viaTable('product_style', ['product_id' => 'id']);
    }

    /**
     * Технологии товара
     */
    public function getTechnologies()
    {
        return $this->hasMany(Technology::class, ['id' => 'technology_id'])
            ->viaTable('product_technology', ['product_id' => 'id']);
    }

    /**
     * Отзывы товара
     */
    public function getReviews()
    {
        return $this->hasMany(ProductReview::class, ['product_id' => 'id'])
            ->where(['is_approved' => 1])
            ->orderBy(['created_at' => SORT_DESC]);
    }

    /**
     * Получить скидку в процентах
     */
    public function getDiscountPercent()
    {
        if ($this->old_price && $this->old_price > $this->price) {
            return round((($this->old_price - $this->price) / $this->old_price) * 100);
        }
        return 0;
    }

    /**
     * Есть ли скидка
     */
    public function hasDiscount()
    {
        return $this->old_price && $this->old_price > $this->price;
    }

    /**
     * Получить статус наличия (текст)
     */
    public function getStockStatusLabel()
    {
        $labels = [
            self::STOCK_IN_STOCK => 'В наличии',
            self::STOCK_OUT_OF_STOCK => 'Нет в наличии',
            self::STOCK_PREORDER => 'Под заказ',
        ];
        return $labels[$this->stock_status] ?? 'Неизвестно';
    }

    /**
     * В наличии?
     */
    public function isInStock()
    {
        return $this->stock_status === self::STOCK_IN_STOCK;
    }

    /**
     * Увеличить счетчик просмотров
     */
    public function incrementViews()
    {
        $this->updateCounters(['views_count' => 1]);
    }

    /**
     * Получить URL товара
     */
    public function getUrl()
    {
        return \yii\helpers\Url::to(['/catalog/product', 'slug' => $this->slug]);
    }

    /**
     * Получить главное изображение или placeholder
     */
    public function getMainImageUrl()
    {
        if ($this->main_image) {
            // Если это внешний URL (http/https)
            if (strpos($this->main_image, 'http://') === 0 || strpos($this->main_image, 'https://') === 0) {
                return $this->main_image;
            }
            
            // Если это локальный файл
            if (file_exists(Yii::getAlias('@webroot') . '/' . $this->main_image)) {
                return Yii::$app->request->baseUrl . '/' . $this->main_image;
            }
        }
        
        // Placeholder через data URI (SVG)
        return 'data:image/svg+xml,%3Csvg xmlns="http://www.w3.org/2000/svg" width="400" height="400"%3E%3Crect width="400" height="400" fill="%23f9fafb"/%3E%3Ctext x="50%25" y="50%25" dominant-baseline="middle" text-anchor="middle" font-family="Arial,sans-serif" font-size="18" fill="%23666"%3E' . urlencode($this->name) . '%3C/text%3E%3C/svg%3E';
    }

    /**
     * Проверить - в избранном ли товар у пользователя
     */
    public function isFavoriteForUser($userId = null, $sessionId = null)
    {
        $query = ProductFavorite::find()->where(['product_id' => $this->id]);
        
        if ($userId) {
            $query->andWhere(['user_id' => $userId]);
        } elseif ($sessionId) {
            $query->andWhere(['session_id' => $sessionId]);
        }
        
        return $query->exists();
    }

    /**
     * Получить похожие товары (той же категории и бренда)
     */
    public function getSimilarProducts($limit = 4)
    {
        return static::find()
            ->where(['is_active' => 1])
            ->andWhere(['!=', 'id', $this->id])
            ->andWhere([
                'or',
                ['category_id' => $this->category_id],
                ['brand_id' => $this->brand_id],
            ])
            ->orderBy('RAND()')
            ->limit($limit)
            ->all();
    }

    /**
     * Получить товар по slug
     */
    public static function findBySlug($slug)
    {
        return static::findOne(['slug' => $slug, 'is_active' => 1]);
    }

    /**
     * Получить популярные товары
     */
    public static function getPopular($limit = 8)
    {
        return static::find()
            ->where(['is_active' => 1])
            ->orderBy(['views_count' => SORT_DESC])
            ->limit($limit)
            ->all();
    }

    /**
     * Получить новинки
     */
    public static function getNew($limit = 8)
    {
        return static::find()
            ->where(['is_active' => 1])
            ->orderBy(['created_at' => SORT_DESC])
            ->limit($limit)
            ->all();
    }

    /**
     * Получить хиты продаж
     */
    public static function getFeatured($limit = 8)
    {
        return static::find()
            ->where(['is_active' => 1, 'is_featured' => 1])
            ->orderBy(['views_count' => SORT_DESC])
            ->limit($limit)
            ->all();
    }

    /**
     * Получить SEO заголовок
     */
    public function getMetaTitle()
    {
        return $this->meta_title ?: $this->name . ' - Купить оригинал | СНИКЕРХЭД';
    }

    /**
     * Получить SEO описание
     */
    public function getMetaDescription()
    {
        if ($this->meta_description) {
            return $this->meta_description;
        }
        return $this->name . ' - Оригинал от ' . $this->brand->name . '. Цена: ' . $this->price . ' BYN. Доставка по Беларуси.';
    }

    /**
     * Получить JSON-LD разметку для Schema.org
     */
    public function getSchemaOrgJson()
    {
        return json_encode([
            '@context' => 'https://schema.org',
            '@type' => 'Product',
            'name' => $this->name,
            'image' => $this->getMainImageUrl(),
            'description' => strip_tags($this->description),
            'brand' => [
                '@type' => 'Brand',
                'name' => $this->brand->name,
            ],
            'offers' => [
                '@type' => 'Offer',
                'price' => $this->price,
                'priceCurrency' => 'BYN',
                'availability' => $this->isInStock() ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock',
            ],
        ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }
}
