<?php

namespace app\backend\modules\admin\models\import;

use Yii;
use yii\db\ActiveRecord;
use app\backend\modules\catalog\models\Category;

/**
 * ImportCategoryMap — Модель маппинга категорий
 * 
 * @property int $id
 * @property int $source_id ID источника
 * @property string $source_category_name Название категории в источнике
 * @property string|null $source_category_url URL категории в источнике
 * @property int|null $category_id ID категории в нашей системе
 * @property bool $is_auto_mapped Автоматическое сопоставление
 * @property int $priority Приоритет (обувь = высокий)
 * @property string $created_at
 * @property string $updated_at
 * 
 * @property ImportSource $source Источник
 * @property \app\backend\modules\catalog\models\Category $category Категория
 */
class ImportCategoryMap extends ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%import_category_map}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['source_id', 'source_category_name'], 'required'],
            [['source_id', 'category_id', 'priority'], 'integer'],
            [['priority'], 'default', 'value' => 0],
            [['is_auto_mapped'], 'boolean'],
            [['is_auto_mapped'], 'default', 'value' => false],
            [['source_category_name'], 'string', 'max' => 255],
            [['source_category_url'], 'string', 'max' => 500],
            [['source_category_url'], 'url'],
            [['created_at', 'updated_at'], 'safe'],
            [['source_id'], 'exist', 'targetClass' => ImportSource::class, 'targetAttribute' => 'id'],
            [['category_id'], 'exist', 'targetClass' => Category::class, 'targetAttribute' => 'id'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'source_id' => 'Источник',
            'source_category_name' => 'Категория в источнике',
            'source_category_url' => 'URL категории',
            'category_id' => 'Наша категория',
            'is_auto_mapped' => 'Авто-маппинг',
            'priority' => 'Приоритет',
            'created_at' => 'Создан',
            'updated_at' => 'Обновлен',
        ];
    }

    /**
     * Источник
     */
    public function getSource()
    {
        return $this->hasOne(ImportSource::class, ['id' => 'source_id']);
    }

    /**
     * Категория
     */
    public function getCategory()
    {
        return $this->hasOne(Category::class, ['id' => 'category_id']);
    }

    /**
     * Найти маппинг по названию категории
     * @param int $sourceId
     * @param string $categoryName
     * @return ImportCategoryMap|null
     */
    public static function findByCategoryName($sourceId, $categoryName)
    {
        return static::find()
            ->where(['source_id' => $sourceId])
            ->andWhere(['like', 'source_category_name', $categoryName, false])
            ->one();
    }

    /**
     * Автоматическое сопоставление категории
     * @param int $sourceId
     * @param string $sourceCategoryName
     * @return int|null ID категории
     */
    public static function autoMapCategory($sourceId, $sourceCategoryName)
    {
        // Ключевые слова для категорий (приоритет обувь)
        $categoryKeywords = [
            // Обувь (высокий приоритет)
            'кроссовки' => ['кроссовки', 'sneakers', 'кеды', 'ботинки', 'обувь', 'shoes', 'boots'],
            'кеды' => ['кеды', 'canvas', 'espadrilles'],
            
            // Одежда
            'футболки' => ['футболка', 't-shirt', 'shirt', 'поло'],
            'худи' => ['худи', 'hoodie', 'sweatshirt', 'свитшот'],
            'куртки' => ['куртка', 'jacket', 'coat', 'пальто', 'windbreaker'],
            'штаны' => ['штаны', 'pants', 'jeans', 'джинсы', 'trousers'],
            
            // Аксессуары
            'сумки' => ['сумка', 'bag', 'рюкзак', 'backpack'],
            'шапки' => ['шапка', 'hat', 'cap', 'beanie', 'кепка'],
            'носки' => ['носки', 'socks'],
        ];

        $sourceCategoryLower = mb_strtolower($sourceCategoryName, 'UTF-8');

        foreach ($categoryKeywords as $categoryName => $keywords) {
            foreach ($keywords as $keyword) {
                if (strpos($sourceCategoryLower, mb_strtolower($keyword, 'UTF-8')) !== false) {
                    // Ищем категорию по названию
                    $category = \app\backend\modules\catalog\models\Category::find()
                        ->where(['like', 'name', $categoryName, false])
                        ->one();
                    
                    if ($category) {
                        // Создаем маппинг
                        $map = new self([
                            'source_id' => $sourceId,
                            'source_category_name' => $sourceCategoryName,
                            'category_id' => $category->id,
                            'is_auto_mapped' => true,
                            'priority' => ($categoryName === 'кроссовки' || $categoryName === 'кеды') ? 10 : 5,
                        ]);
                        $map->save(false);
                        
                        return $category->id;
                    }
                }
            }
        }

        return null;
    }

    /**
     * Получить все маппинги для источника
     * @param int $sourceId
     * @return ImportCategoryMap[]
     */
    public static function getSourceMappings($sourceId)
    {
        return static::find()
            ->where(['source_id' => $sourceId])
            ->with('category')
            ->orderBy(['priority' => SORT_DESC, 'source_category_name' => SORT_ASC])
            ->all();
    }

    /**
     * Создать или обновить маппинг
     * @param int $sourceId
     * @param string $sourceCategoryName
     * @param int $categoryId
     * @param string|null $sourceCategoryUrl
     * @return ImportCategoryMap
     */
    public static function createOrUpdate($sourceId, $sourceCategoryName, $categoryId, $sourceCategoryUrl = null)
    {
        $map = self::findByCategoryName($sourceId, $sourceCategoryName);
        
        if (!$map) {
            $map = new self([
                'source_id' => $sourceId,
                'source_category_name' => $sourceCategoryName,
            ]);
        }
        
        $map->category_id = $categoryId;
        $map->source_category_url = $sourceCategoryUrl;
        $map->is_auto_mapped = false;
        $map->save(false);
        
        return $map;
    }
}
