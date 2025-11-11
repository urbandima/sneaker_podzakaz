<?php

namespace app\services\Sitemap\sections;

use XMLWriter;
use yii\db\Expression;
use app\models\Brand;
use app\models\Category;
use app\models\Product;
use app\models\ProductSize;
use app\models\FilterHistory;
use app\components\SmartFilter;

/**
 * Секция фильтрованных страниц для sitemap
 * Включает только комбинации с реальным ассортиментом
 */
class FilterSection extends AbstractSitemapSection
{
    private int $maxPages;
    private int $minResults;
    private string $changefreqHigh;
    private string $changefreqLow;

    private ?array $activeBrandIds = null;
    private ?array $activeCategoryIds = null;
    private array $assortmentCache = [];

    public function __construct(string $baseUrl)
    {
        parent::__construct($baseUrl);
        
        $config = \Yii::$app->params['sitemap'] ?? [];
        $this->maxPages = $config['filterMaxPages'] ?? 5000;
        $this->minResults = $config['filterMinResults'] ?? 5;
        $this->changefreqHigh = $config['changefreqHigh'] ?? 'daily';
        $this->changefreqLow = $config['changefreqLow'] ?? 'weekly';
    }

    public function write(XMLWriter $writer): int
    {
        $rows = FilterHistory::find()
            ->select([
                'filter_params',
                'results_max' => new Expression('MAX(results_count)'),
                'last_used' => new Expression('MAX(created_at)')
            ])
            ->groupBy('filter_params')
            ->having(['>=', new Expression('MAX(results_count)'), $this->minResults])
            ->orderBy(['last_used' => SORT_DESC])
            ->limit($this->maxPages)
            ->asArray()
            ->all();

        $count = 0;
        foreach ($rows as $row) {
            $filters = json_decode($row['filter_params'] ?? '', true);
            if (empty($filters) || !is_array($filters)) {
                continue;
            }

            $normalized = $this->normalizeFilters($filters);
            if (empty($normalized)) {
                continue;
            }

            $sefUrl = SmartFilter::generateSefUrl($normalized);
            if ($sefUrl === '/catalog/') {
                continue;
            }

            if (!$this->hasAssortment($normalized)) {
                continue;
            }

            $priority = $this->calculatePriority((int)$row['results_max']);
            $changefreq = ((int)$row['results_max'] >= 20) ? $this->changefreqHigh : $this->changefreqLow;

            $count += $this->writeUrl($writer, $sefUrl, [
                'priority' => $priority,
                'changefreq' => $changefreq,
                'lastmod' => $row['last_used'],
            ]);
        }

        return $count;
    }

    /**
     * Нормализация фильтров: проверка существования ID, очистка данных
     * 
     * @param array $filters Входные фильтры из истории
     * @return array Нормализованные фильтры
     */
    private function normalizeFilters(array $filters): array
    {
        $normalized = [];

        $this->appendNormalized($normalized, $this->normalizeBrands($filters));
        $this->appendNormalized($normalized, $this->normalizeCategories($filters));
        $this->appendNormalized($normalized, $this->normalizePriceRange($filters));
        $this->appendNormalized($normalized, $this->normalizeSizes($filters));
        $this->appendNormalized($normalized, $this->normalizeColors($filters));
        $this->appendNormalized($normalized, $this->normalizeStringFilters($filters));
        $this->appendNormalized($normalized, $this->normalizePromo($filters));
        $this->appendNormalized($normalized, $this->normalizeDiscounts($filters));
        $this->appendNormalized($normalized, $this->normalizeRating($filters));

        return $normalized;
    }

    private function appendNormalized(array &$target, array $values): void
    {
        foreach ($values as $key => $value) {
            $target[$key] = $value;
        }
    }

    private function normalizeBrands(array $filters): array
    {
        if (empty($filters['brands'])) {
            return [];
        }
        
        $brandIds = $this->filterIds($filters['brands'], $this->getBrandIds());
        return !empty($brandIds) ? ['brands' => $brandIds] : [];
    }

    private function normalizeCategories(array $filters): array
    {
        if (empty($filters['categories'])) {
            return [];
        }
        
        $categoryIds = $this->filterIds($filters['categories'], $this->getCategoryIds());
        return !empty($categoryIds) ? ['categories' => $categoryIds] : [];
    }

    private function normalizePriceRange(array $filters): array
    {
        $result = [];

        if (!empty($filters['price_from'])) {
            $result['price_from'] = (int)$filters['price_from'];
        }
        if (!empty($filters['price_to'])) {
            $result['price_to'] = (int)$filters['price_to'];
        }
        return $result;
    }

    private function normalizeSizes(array $filters): array
    {
        if (empty($filters['sizes'])) {
            return [];
        }
        
        $result = [];
        $sizes = is_array($filters['sizes']) ? $filters['sizes'] : explode(',', (string)$filters['sizes']);
        $sizes = array_filter(array_map('trim', $sizes));
        
        if (!empty($sizes)) {
            $result['sizes'] = array_values(array_unique($sizes));
            
            // Добавляем систему размеров если указана
            if (!empty($filters['size_system']) && in_array($filters['size_system'], ['eu', 'us', 'uk', 'cm'])) {
                $result['size_system'] = $filters['size_system'];
            }
        }
        
        return $result;
    }

    private function normalizeColors(array $filters): array
    {
        if (empty($filters['colors'])) {
            return [];
        }

        $colors = is_array($filters['colors']) ? $filters['colors'] : explode(',', (string)$filters['colors']);
        $colors = array_filter(array_map('trim', $colors));
        return $colors ? ['colors' => array_values(array_unique($colors))] : [];
    }

    private function normalizeStringFilters(array $filters): array
    {
        $result = [];
        foreach (['material', 'season', 'gender', 'height', 'fastening', 'country'] as $key) {
            if (empty($filters[$key])) {
                continue;
            }
            
            $value = is_array($filters[$key]) 
                ? array_values(array_unique(array_filter(array_map('trim', $filters[$key]))))
                : array_filter(array_map('trim', explode(',', (string)$filters[$key])));
                
            if (!empty($value)) {
                $result[$key] = $value;
            }
        }
        return $result;
    }

    private function normalizePromo(array $filters): array
    {
        if (empty($filters['promo'])) {
            return [];
        }
        
        $promo = is_array($filters['promo']) ? $filters['promo'] : explode(',', (string)$filters['promo']);
        $promo = array_filter(array_map('trim', $promo));
        return !empty($promo) ? ['promo' => array_values(array_unique($promo))] : [];
    }

    private function normalizeDiscounts(array $filters): array
    {
        $result = [];
        
        if (!empty($filters['discount_range'])) {
            $range = is_array($filters['discount_range']) ? $filters['discount_range'] : explode(',', (string)$filters['discount_range']);
            $range = array_filter(array_map('trim', $range));
            if (!empty($range)) {
                $result['discount_range'] = $range;
            }
        }
        
        if (!empty($filters['discount_any'])) {
            $result['discount_any'] = true;
        }

        return $result;
    }

    private function normalizeRating(array $filters): array
    {
        if (!isset($filters['rating']) || $filters['rating'] === '') {
            return [];
        }

        return ['rating' => (float)$filters['rating']];
    }

    private function filterIds($input, array $allowed): array
    {
        $list = is_array($input) ? $input : explode(',', (string)$input);
        $ids = [];
        foreach ($list as $value) {
            $value = (int)$value;
            if ($value > 0 && isset($allowed[$value])) {
                $ids[] = $value;
            }
        }

        return array_values(array_unique($ids));
    }

    private function getBrandIds(): array
    {
        if ($this->activeBrandIds === null) {
            $ids = Brand::find()
                ->select('id')
                ->where(['is_active' => 1])
                ->column();
            $this->activeBrandIds = array_fill_keys($ids, true);
        }
        return $this->activeBrandIds;
    }

    private function getCategoryIds(): array
    {
        if ($this->activeCategoryIds === null) {
            $ids = Category::find()
                ->select('id')
                ->where(['is_active' => 1])
                ->column();
            $this->activeCategoryIds = array_fill_keys($ids, true);
        }
        return $this->activeCategoryIds;
    }

    private function calculatePriority(int $results): float
    {
        if ($results >= 100) {
            return 0.8;
        }
        if ($results >= 50) {
            return 0.7;
        }
        if ($results >= 20) {
            return 0.6;
        }
        return 0.5;
    }

    /**
     * Проверка наличия ассортимента для комбинации фильтров с кэшированием
     * 
     * @param array $filters Нормализованные фильтры
     * @return bool
     */
    private function hasAssortment(array $filters): bool
    {
        $cacheKey = md5(json_encode($filters));
        
        if (isset($this->assortmentCache[$cacheKey])) {
            return $this->assortmentCache[$cacheKey];
        }
        
        $result = $this->checkAssortmentQuery($filters);
        $this->assortmentCache[$cacheKey] = $result;
        
        return $result;
    }

    /**
     * Выполняет запрос проверки ассортимента
     */
    private function checkAssortmentQuery(array $filters): bool
    {
        $query = Product::find()->alias('p')->where(['p.is_active' => 1]);

        if (!empty($filters['brands'])) {
            $query->andWhere(['p.brand_id' => $filters['brands']]);
        }

        if (!empty($filters['categories'])) {
            $query->andWhere(['p.category_id' => $filters['categories']]);
        }

        if (isset($filters['price_from'])) {
            $query->andWhere(['>=', 'p.price', (int)$filters['price_from']]);
        }

        if (isset($filters['price_to'])) {
            $query->andWhere(['<=', 'p.price', (int)$filters['price_to']]);
        }

        if (!empty($filters['sizes'])) {
            $query->innerJoin(ProductSize::tableName() . ' ps', 'ps.product_id = p.id AND ps.is_available = 1')
                  ->andWhere(['ps.size' => $filters['sizes']]);
        }

        if (!empty($filters['colors'])) {
            $query->andWhere(['p.color_description' => $filters['colors']]);
        }

        foreach (['material', 'season', 'gender', 'height', 'fastening', 'country'] as $stringFilter) {
            if (!empty($filters[$stringFilter])) {
                $query->andWhere(['p.' . $stringFilter => $filters[$stringFilter]]);
            }
        }

        if (!empty($filters['rating'])) {
            $query->andWhere(['>=', 'p.rating', (float)$filters['rating']]);
        }

        if (!empty($filters['discount_any'])) {
            $query->andWhere(['>', 'p.old_price', 0]);
        }

        if (!empty($filters['discount_range'])) {
            $conditions = ['or'];
            foreach ($filters['discount_range'] as $range) {
                if (strpos($range, '-') === false) {
                    continue;
                }
                [$min, $max] = array_map('floatval', explode('-', $range));
                $conditions[] = [
                    'and',
                    ['>', 'p.old_price', 0],
                    ['>=', new Expression('(p.old_price - p.price) / p.old_price * 100'), $min],
                    ['<=', new Expression('(p.old_price - p.price) / p.old_price * 100'), $max],
                ];
            }
            if (count($conditions) > 1) {
                $query->andWhere($conditions);
            }
        }

        if (!empty($filters['promo'])) {
            foreach ($filters['promo'] as $promo) {
                switch ($promo) {
                    case 'sale':
                        $query->andWhere(['>', 'p.old_price', 0]);
                        break;
                    case 'bonus':
                        $query->andWhere(['p.has_bonus' => 1]);
                        break;
                    case '2for1':
                        $query->andWhere(['p.promo_2for1' => 1]);
                        break;
                    case 'exclusive':
                        $query->andWhere(['p.is_exclusive' => 1]);
                        break;
                }
            }
        }

        // Ограничиваем до 1 записи для быстрого exists()
        return $query->limit(1)->exists();
    }
}
