<?php

namespace app\controllers\traits;

use Yii;
use app\models\Product;
use app\services\Catalog\FilterBuilder;
use app\components\CacheManager;

/**
 * Trait CatalogFiltersTrait
 * Методы для работы с фильтрами каталога
 */
trait CatalogFiltersTrait
{
    /**
     * Применить фильтры к запросу товаров
     * 
     * @param \yii\db\ActiveQuery $query
     * @return \yii\db\ActiveQuery
     */
    protected function applyFilters($query)
    {
        $request = Yii::$app->request;
        
        // Бренды
        $brands = $request->get('brands');
        if ($brands) {
            $brandIds = is_array($brands) ? $brands : explode(',', $brands);
            $query->andWhere(['product.brand_id' => $brandIds]);
        }
        
        // Категории
        $categories = $request->get('categories');
        if ($categories) {
            $categoryIds = is_array($categories) ? $categories : explode(',', $categories);
            $query->andWhere(['product.category_id' => $categoryIds]);
        }
        
        // Цена
        $priceFrom = $request->get('price_from');
        $priceTo = $request->get('price_to');
        if ($priceFrom) {
            $query->andWhere(['>=', 'product.price', (float)$priceFrom]);
        }
        if ($priceTo) {
            $query->andWhere(['<=', 'product.price', (float)$priceTo]);
        }
        
        // Размеры
        $sizes = $request->get('sizes');
        $sizeSystem = $request->get('size_system', 'eu');
        if ($sizes) {
            $sizeValues = is_array($sizes) ? $sizes : explode(',', $sizes);
            $sizeColumn = $this->getSizeColumn($sizeSystem);
            
            $query->innerJoin('product_size ps_filter', 'ps_filter.product_id = product.id')
                  ->andWhere(['ps_filter.is_available' => 1])
                  ->andWhere(["ps_filter.{$sizeColumn}" => $sizeValues]);
        }
        
        // Цвета
        $colors = $request->get('colors');
        if ($colors) {
            $colorIds = is_array($colors) ? $colors : explode(',', $colors);
            $query->innerJoin('product_color pc_filter', 'pc_filter.product_id = product.id')
                  ->andWhere(['pc_filter.color_id' => $colorIds]);
        }
        
        // Динамические характеристики (char_*)
        foreach ($request->queryParams as $key => $value) {
            if (strpos($key, 'char_') === 0 && !empty($value)) {
                $charId = (int)substr($key, 5);
                $valueIds = is_array($value) ? $value : explode(',', $value);
                
                $alias = "pcv_{$charId}";
                $query->innerJoin(
                    "product_characteristic_value {$alias}",
                    "{$alias}.product_id = product.id"
                )->andWhere([
                    "{$alias}.characteristic_id" => $charId,
                    "{$alias}.characteristic_value_id" => $valueIds,
                ]);
            }
        }
        
        // Скидки
        if ($request->get('discount_any')) {
            $query->andWhere(['>', 'product.old_price', 0])
                  ->andWhere('product.old_price > product.price');
        }
        
        // Рейтинг
        $rating = $request->get('rating');
        if ($rating) {
            $query->andWhere(['>=', 'product.rating', (float)$rating]);
        }
        
        // Условия (новинки, хиты, в наличии)
        $conditions = $request->get('conditions');
        if ($conditions) {
            $conditionsList = is_array($conditions) ? $conditions : explode(',', $conditions);
            foreach ($conditionsList as $condition) {
                switch ($condition) {
                    case 'new':
                        $query->andWhere(['>=', 'product.created_at', strtotime('-30 days')]);
                        break;
                    case 'hit':
                        $query->andWhere(['product.is_featured' => 1]);
                        break;
                    case 'in_stock':
                        $query->andWhere(['product.stock_status' => Product::STOCK_IN_STOCK]);
                        break;
                }
            }
        }
        
        return $query;
    }

    /**
     * Получить данные фильтров
     * 
     * @param array $currentFiltersOrBaseCondition
     * @return array
     */
    protected function getFiltersData($currentFiltersOrBaseCondition = [])
    {
        $isCurrentFilters = isset($currentFiltersOrBaseCondition['brands']) || 
                           isset($currentFiltersOrBaseCondition['categories']) ||
                           isset($currentFiltersOrBaseCondition['sizes']) ||
                           isset($currentFiltersOrBaseCondition['price_from']);
        
        if ($isCurrentFilters) {
            $currentFilters = $currentFiltersOrBaseCondition;
            $baseCondition = [];
        } else {
            $baseCondition = $currentFiltersOrBaseCondition;
            $currentFilters = $this->getCurrentFiltersFromRequest();
        }
        
        return FilterBuilder::buildFilters($currentFilters, $baseCondition);
    }

    /**
     * Получить текущие фильтры из запроса
     * 
     * @return array
     */
    protected function getCurrentFiltersFromRequest(): array
    {
        $request = Yii::$app->request;
        
        $currentFilters = [
            'brands' => $this->parseArrayParam($request->get('brands')),
            'categories' => $this->parseArrayParam($request->get('categories')),
            'sizes' => $this->parseArrayParam($request->get('sizes')),
            'size_system' => $request->get('size_system', 'eu'),
            'price_from' => $request->get('price_from'),
            'price_to' => $request->get('price_to'),
            'colors' => $this->parseArrayParam($request->get('colors')),
        ];
        
        // Характеристики
        foreach ($request->queryParams as $key => $value) {
            if (strpos($key, 'char_') === 0 && !empty($value)) {
                $currentFilters[$key] = $this->parseArrayParam($value);
            }
        }
        
        return $currentFilters;
    }

    /**
     * Парсинг параметра в массив
     * 
     * @param mixed $value
     * @return array
     */
    protected function parseArrayParam($value): array
    {
        if (empty($value)) {
            return [];
        }
        return is_array($value) ? $value : explode(',', $value);
    }

    /**
     * Получить колонку размера по системе
     * 
     * @param string $system
     * @return string
     */
    protected function getSizeColumn(string $system): string
    {
        $columns = [
            'eu' => 'eu_size',
            'us' => 'us_size',
            'uk' => 'uk_size',
            'cm' => 'cm_size',
        ];
        return $columns[$system] ?? 'eu_size';
    }

    /**
     * Получение доступных размеров
     * @deprecated Используйте FilterBuilder::buildFilters()['sizes']
     */
    protected function getAvailableSizes($currentFilters = [])
    {
        $filters = FilterBuilder::buildFilters($currentFilters, []);
        return $filters['sizes'] ?? ['eu' => [], 'us' => [], 'uk' => [], 'cm' => []];
    }
}
