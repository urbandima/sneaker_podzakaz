<?php

namespace app\services\Catalog;

use Yii;
use app\models\Brand;
use app\models\Category;
use app\models\Product;
use app\models\ProductSize;
use app\models\ProductColor;
use app\models\Characteristic;
use app\models\CharacteristicValue;
use app\models\ProductCharacteristicValue;
use app\components\CacheManager;

/**
 * FilterBuilder - Сервис построения динамических фильтров каталога
 * 
 * Функции:
 * - Построение всех типов фильтров (бренды, категории, характеристики)
 * - Умное сужение: подсчёт товаров с учётом активных фильтров
 * - Кэширование через CacheManager
 * - Поддержка различных типов характеристик (select, multiselect, number, boolean)
 * - Интеграция с текущей системой фильтрации
 * 
 * @author СНИКЕРХЭД Development Team
 */
class FilterBuilder
{
    /**
     * Основной метод: построение всех фильтров с кэшированием
     * 
     * @param array $currentFilters Активные фильтры для умного сужения
     * @param array $baseConditions Базовые условия запроса (например, ['brand_id' => 5])
     * @return array Структура всех фильтров
     */
    public static function buildFilters(array $currentFilters = [], array $baseConditions = []): array
    {
        $cacheKey = 'filter_builder_' . md5(serialize([
            'filters' => $currentFilters,
            'base' => $baseConditions
        ]));
        
        return CacheManager::getOrSet($cacheKey, function() use ($currentFilters, $baseConditions) {
            return [
                // Основные фильтры
                'brands' => self::buildBrandsFilter($currentFilters, $baseConditions),
                'categories' => self::buildCategoriesFilter($currentFilters, $baseConditions),
                'priceRange' => self::buildPriceRange($currentFilters, $baseConditions),
                'sizes' => self::buildSizesFilter($currentFilters, $baseConditions),
                
                // Статичные фильтры (работают с полями таблицы product)
                'discount' => self::buildDiscountFilter(),
                'rating' => self::buildRatingFilter(),
                'conditions' => self::buildConditionsFilter(),
                
                // Динамические характеристики
                'characteristics' => self::buildCharacteristicsFilters($currentFilters, $baseConditions),
                
                // Цвета (из product_color)
                'colors' => self::buildColorsFilter($currentFilters, $baseConditions),
            ];
        }, CacheManager::TTL_MEDIUM, [CacheManager::TAG_FILTERS]);
    }
    
    /**
     * Построение фильтра брендов с количеством товаров
     */
    protected static function buildBrandsFilter(array $currentFilters, array $baseConditions): array
    {
        $query = Brand::find()
            ->select(['brand.id', 'brand.name', 'brand.slug', 'COUNT(DISTINCT product.id) as count'])
            ->innerJoin('product', 'product.brand_id = brand.id AND product.is_active = 1 AND product.stock_status != "' . Product::STOCK_OUT_OF_STOCK . '"')
            ->where(['brand.is_active' => 1]);
        
        // Применяем другие фильтры (кроме брендов)
        self::applyFiltersToQuery($query, $currentFilters, $baseConditions, ['brands']);
        
        return $query
            ->groupBy(['brand.id'])
            ->orderBy(['brand.name' => SORT_ASC])
            ->asArray()
            ->all();
    }
    
    /**
     * Построение фильтра категорий с количеством товаров
     */
    protected static function buildCategoriesFilter(array $currentFilters, array $baseConditions): array
    {
        $query = Category::find()
            ->select(['category.id', 'category.name', 'category.slug', 'COUNT(DISTINCT product.id) as count'])
            ->innerJoin('product', 'product.category_id = category.id AND product.is_active = 1 AND product.stock_status != "' . Product::STOCK_OUT_OF_STOCK . '"')
            ->where(['category.is_active' => 1, 'category.parent_id' => null]);
        
        // Применяем другие фильтры (кроме категорий)
        self::applyFiltersToQuery($query, $currentFilters, $baseConditions, ['categories']);
        
        return $query
            ->groupBy(['category.id'])
            ->orderBy(['category.name' => SORT_ASC])
            ->asArray()
            ->all();
    }
    
    /**
     * Построение диапазона цен
     */
    protected static function buildPriceRange(array $currentFilters, array $baseConditions): array
    {
        $query = Product::find()
            ->select(['MIN(price) as min', 'MAX(price) as max'])
            ->where(['is_active' => 1])
            ->andWhere(['!=', 'stock_status', Product::STOCK_OUT_OF_STOCK]);
        
        // Применяем фильтры (кроме цены)
        self::applyFiltersToQuery($query, $currentFilters, $baseConditions, ['price_from', 'price_to']);
        
        $result = $query->asArray()->one();
        
        return [
            'min' => (float)($result['min'] ?? 0),
            'max' => (float)($result['max'] ?? 10000),
        ];
    }
    
    /**
     * Построение фильтра размеров (все системы)
     */
    protected static function buildSizesFilter(array $currentFilters, array $baseConditions): array
    {
        $result = [
            'eu' => [],
            'us' => [],
            'uk' => [],
            'cm' => []
        ];
        
        // Базовый запрос
        $baseQuery = ProductSize::find()
            ->innerJoin('product', 'product.id = product_size.product_id')
            ->where([
                'product.is_active' => 1,
                'product_size.is_available' => 1
            ])
            ->andWhere(['!=', 'product.stock_status', Product::STOCK_OUT_OF_STOCK]);
        
        // Применяем фильтры (кроме размеров)
        self::applyFiltersToQuery($baseQuery, $currentFilters, $baseConditions, ['sizes', 'size_system']);
        
        // Размеры EU
        $euQuery = clone $baseQuery;
        $result['eu'] = $euQuery
            ->select(['product_size.eu_size as size', 'COUNT(DISTINCT product_size.product_id) as count'])
            ->andWhere(['IS NOT', 'product_size.eu_size', null])
            ->andWhere(['>=', 'CAST(product_size.eu_size AS DECIMAL)', 20])
            ->andWhere(['<=', 'CAST(product_size.eu_size AS DECIMAL)', 50])
            ->groupBy(['product_size.eu_size'])
            ->having(['>', 'count', 0])
            ->orderBy(['CAST(product_size.eu_size AS DECIMAL)' => SORT_ASC])
            ->asArray()
            ->all();
        
        // Размеры US
        $usQuery = clone $baseQuery;
        $result['us'] = $usQuery
            ->select(['product_size.us_size as size', 'COUNT(DISTINCT product_size.product_id) as count'])
            ->andWhere(['IS NOT', 'product_size.us_size', null])
            ->andWhere(['>=', 'CAST(product_size.us_size AS DECIMAL)', 3])
            ->andWhere(['<=', 'CAST(product_size.us_size AS DECIMAL)', 18])
            ->groupBy(['product_size.us_size'])
            ->having(['>', 'count', 0])
            ->orderBy(['CAST(product_size.us_size AS DECIMAL)' => SORT_ASC])
            ->asArray()
            ->all();
        
        // Размеры UK
        $ukQuery = clone $baseQuery;
        $result['uk'] = $ukQuery
            ->select(['product_size.uk_size as size', 'COUNT(DISTINCT product_size.product_id) as count'])
            ->andWhere(['IS NOT', 'product_size.uk_size', null])
            ->andWhere(['>=', 'CAST(product_size.uk_size AS DECIMAL)', 2])
            ->andWhere(['<=', 'CAST(product_size.uk_size AS DECIMAL)', 15])
            ->groupBy(['product_size.uk_size'])
            ->having(['>', 'count', 0])
            ->orderBy(['CAST(product_size.uk_size AS DECIMAL)' => SORT_ASC])
            ->asArray()
            ->all();
        
        // Размеры CM
        $cmQuery = clone $baseQuery;
        $result['cm'] = $cmQuery
            ->select(['product_size.cm_size as size', 'COUNT(DISTINCT product_size.product_id) as count'])
            ->andWhere(['IS NOT', 'product_size.cm_size', null])
            ->andWhere(['>=', 'CAST(product_size.cm_size AS DECIMAL)', 20])
            ->andWhere(['<=', 'CAST(product_size.cm_size AS DECIMAL)', 35])
            ->groupBy(['product_size.cm_size'])
            ->having(['>', 'count', 0])
            ->orderBy(['CAST(product_size.cm_size AS DECIMAL)' => SORT_ASC])
            ->asArray()
            ->all();
        
        return $result;
    }
    
    /**
     * Построение фильтра скидок (статичный)
     */
    protected static function buildDiscountFilter(): array
    {
        return [
            ['value' => 'any', 'label' => 'Товары со скидкой'],
            ['value' => '0-30', 'label' => 'До 30%'],
            ['value' => '30-50', 'label' => '30% - 50%'],
            ['value' => '50-100', 'label' => 'Более 50%'],
        ];
    }
    
    /**
     * Построение фильтра рейтинга (статичный)
     */
    protected static function buildRatingFilter(): array
    {
        return [
            ['value' => 4, 'label' => '4★ и выше'],
            ['value' => 3, 'label' => '3★ и выше'],
        ];
    }
    
    /**
     * Построение фильтра условий (статичный)
     */
    protected static function buildConditionsFilter(): array
    {
        return [
            ['value' => 'new', 'label' => 'Новинки', 'icon' => 'bi-stars'],
            ['value' => 'hit', 'label' => 'Хиты продаж', 'icon' => 'bi-fire'],
            ['value' => 'in_stock', 'label' => 'В наличии', 'icon' => 'bi-check-circle-fill'],
        ];
    }
    
    /**
     * Построение динамических характеристик из БД
     * ОПТИМИЗИРОВАНО: Batch запрос вместо N+1
     * ИСПРАВЛЕНО: Гибридный подход для характеристик, которые есть в product (gender, season, material)
     */
    protected static function buildCharacteristicsFilters(array $currentFilters, array $baseConditions): array
    {
        // Получаем активные характеристики для фильтров
        $characteristics = Characteristic::find()
            ->where(['is_active' => 1, 'is_filter' => 1])
            ->orderBy(['sort_order' => SORT_ASC])
            ->asArray()
            ->all();
        
        if (empty($characteristics)) {
            return [];
        }
        
        $charIds = array_column($characteristics, 'id');
        
        // ОПТИМИЗАЦИЯ: Один запрос для всех значений всех характеристик
        $productIdsQuery = Product::find()
            ->select('id')
            ->where(['is_active' => 1])
            ->andWhere(['!=', 'stock_status', Product::STOCK_OUT_OF_STOCK]);
        
        // Применяем фильтры для умного сужения
        self::applyFiltersToQuery($productIdsQuery, $currentFilters, $baseConditions, []);
        
        // ИСПРАВЛЕНО: Используем подзапрос через IN вместо rawSql для корректного подсчета
        $productIds = $productIdsQuery->select('id')->column();
        
        // Если нет товаров после фильтрации - возвращаем пустые значения
        if (empty($productIds)) {
            $allValues = [];
        } else {
            // Получаем все значения с количеством товаров за один запрос
            $allValues = CharacteristicValue::find()
                ->select([
                    'cv.id',
                    'cv.characteristic_id',
                    'cv.value',
                    'cv.slug',
                    'cv.sort_order',
                    'COUNT(DISTINCT pcv.product_id) as count'
                ])
                ->from(['cv' => '{{%characteristic_value}}'])
                ->leftJoin(
                    ['pcv' => '{{%product_characteristic_value}}'],
                    'pcv.characteristic_value_id = cv.id AND pcv.product_id IN (' . implode(',', $productIds) . ')'
                )
                ->where(['cv.characteristic_id' => $charIds, 'cv.is_active' => 1])
                ->groupBy(['cv.id'])
                ->orderBy(['cv.sort_order' => SORT_ASC])
                ->asArray()
                ->all();
        }
        
        // Группируем значения по characteristic_id
        $valuesByChar = [];
        foreach ($allValues as $value) {
            $valuesByChar[$value['characteristic_id']][] = $value;
        }
        
        // Формируем результат
        $result = [];
        foreach ($characteristics as $characteristic) {
            // ИСПРАВЛЕНО: Проверяем, есть ли эта характеристика как поле в product
            $isProductField = in_array($characteristic['key'], ['gender', 'season', 'material', 'height', 'fastening', 'country']);
            
            if ($isProductField) {
                // Используем данные из таблицы product напрямую
                $filterData = self::buildProductFieldFilter($characteristic, $currentFilters, $baseConditions);
            } else {
                // Используем стандартную логику через product_characteristic_value
                $filterData = [
                    'id' => $characteristic['id'],
                    'key' => $characteristic['key'],
                    'name' => $characteristic['name'],
                    'type' => $characteristic['type'],
                    'values' => [],
                ];
                
                // Для select/multiselect берём значения из batch запроса
                if (in_array($characteristic['type'], [Characteristic::TYPE_SELECT, Characteristic::TYPE_MULTISELECT])) {
                    $filterData['values'] = $valuesByChar[$characteristic['id']] ?? [];
                }
                
                // Для number получаем диапазон (отдельный запрос, но их мало)
                if ($characteristic['type'] === Characteristic::TYPE_NUMBER) {
                    $filterData['range'] = self::getCharacteristicNumberRange(
                        $characteristic['id'],
                        $currentFilters,
                        $baseConditions
                    );
                }
            }
            
            $result[] = $filterData;
        }
        
        return $result;
    }
    
    /**
     * Построение фильтра для характеристики, которая хранится как поле в product
     * УПРОЩЕНО: Все характеристики теперь в полях product (gender, season, material, height, fastening)
     */
    protected static function buildProductFieldFilter(array $characteristic, array $currentFilters, array $baseConditions): array
    {
        $fieldName = $characteristic['key'];
        
        // Базовый запрос товаров с учетом фильтров
        $query = Product::find()
            ->select([$fieldName, 'COUNT(*) as count'])
            ->where(['is_active' => 1])
            ->andWhere(['!=', 'stock_status', Product::STOCK_OUT_OF_STOCK])
            ->andWhere(['IS NOT', $fieldName, null])
            ->andWhere(['!=', $fieldName, '']);
        
        // Применяем фильтры (кроме текущей характеристики)
        $excludeKeys = ['char_' . $characteristic['id']];
        self::applyFiltersToQuery($query, $currentFilters, $baseConditions, $excludeKeys);
        
        $stats = $query
            ->groupBy([$fieldName])
            ->having(['>', 'count', 0])
            ->asArray()
            ->all();
        
        // Маппинг значений на человекочитаемые названия
        $valueLabels = self::getProductFieldLabels($fieldName);
        
        // Добавляем все возможные значения
        $allPossibleValues = array_keys($valueLabels);
        $statsByValue = [];
        foreach ($stats as $stat) {
            $statsByValue[$stat[$fieldName]] = (int)$stat['count'];
        }
        
        // Формируем values в упрощенном формате
        $values = [];
        foreach ($allPossibleValues as $value) {
            $count = $statsByValue[$value] ?? 0;
            
            $values[] = [
                'id' => $value, // Строковое значение: 'male', 'female', 'unisex'
                'value' => $valueLabels[$value],
                'slug' => $value,
                'count' => $count,
            ];
        }
        
        return [
            'id' => $characteristic['id'],
            'key' => $characteristic['key'],
            'name' => $characteristic['name'],
            'type' => $characteristic['type'],
            'values' => $values,
            'is_product_field' => true, // Флаг для фронтенда
        ];
    }
    
    /**
     * Получить человекочитаемые названия для значений полей product
     */
    protected static function getProductFieldLabels(string $fieldName): array
    {
        $labels = [
            'gender' => [
                'male' => 'Мужской',
                'female' => 'Женский',
                'unisex' => 'Унисекс',
            ],
            'season' => [
                'summer' => 'Лето',
                'winter' => 'Зима',
                'demi' => 'Демисезон',
                'all' => 'Всесезонные',
            ],
            'material' => [
                'leather' => 'Кожа',
                'textile' => 'Текстиль',
                'synthetic' => 'Синтетика',
                'suede' => 'Замша',
                'mesh' => 'Сетка',
                'canvas' => 'Канвас',
            ],
            'height' => [
                'low' => 'Низкие',
                'mid' => 'Средние',
                'high' => 'Высокие',
            ],
            'fastening' => [
                'laces' => 'Шнурки',
                'velcro' => 'Липучки',
                'zipper' => 'Молния',
                'slip_on' => 'Без застежки',
            ],
        ];
        
        return $labels[$fieldName] ?? [];
    }
    
    /**
     * Получение значений характеристики с количеством товаров
     */
    protected static function getCharacteristicValuesWithCounts(
        int $characteristicId,
        array $currentFilters,
        array $baseConditions
    ): array {
        // Подзапрос для получения product_id с учётом фильтров
        $productIdsQuery = Product::find()
            ->select('id')
            ->where(['is_active' => 1])
            ->andWhere(['!=', 'stock_status', Product::STOCK_OUT_OF_STOCK]);
        
        // Применяем фильтры (кроме этой характеристики)
        $excludeKeys = ['char_' . $characteristicId];
        self::applyFiltersToQuery($productIdsQuery, $currentFilters, $baseConditions, $excludeKeys);
        
        // Запрос значений с количеством
        $values = CharacteristicValue::find()
            ->select([
                'characteristic_value.id',
                'characteristic_value.value',
                'characteristic_value.slug',
                'COUNT(DISTINCT pcv.product_id) as count'
            ])
            ->leftJoin(
                'product_characteristic_value pcv',
                'pcv.characteristic_value_id = characteristic_value.id AND pcv.product_id IN (' . $productIdsQuery->createCommand()->rawSql . ')'
            )
            ->where([
                'characteristic_value.characteristic_id' => $characteristicId,
                'characteristic_value.is_active' => 1
            ])
            ->groupBy(['characteristic_value.id'])
            ->orderBy(['characteristic_value.sort_order' => SORT_ASC])
            ->asArray()
            ->all();
        
        return $values;
    }
    
    /**
     * Получение диапазона для числовой характеристики
     */
    protected static function getCharacteristicNumberRange(
        int $characteristicId,
        array $currentFilters,
        array $baseConditions
    ): array {
        $query = ProductCharacteristicValue::find()
            ->select(['MIN(value_number) as min', 'MAX(value_number) as max'])
            ->innerJoin('product', 'product.id = product_characteristic_value.product_id')
            ->where([
                'product_characteristic_value.characteristic_id' => $characteristicId,
                'product.is_active' => 1
            ])
            ->andWhere(['IS NOT', 'product_characteristic_value.value_number', null])
            ->andWhere(['!=', 'product.stock_status', Product::STOCK_OUT_OF_STOCK]);
        
        // Применяем фильтры
        $excludeKeys = ['char_' . $characteristicId];
        self::applyFiltersToQuery($query, $currentFilters, $baseConditions, $excludeKeys);
        
        $result = $query->asArray()->one();
        
        return [
            'min' => (float)($result['min'] ?? 0),
            'max' => (float)($result['max'] ?? 100),
        ];
    }
    
    /**
     * Построение фильтра цветов из product_color
     */
    protected static function buildColorsFilter(array $currentFilters, array $baseConditions): array
    {
        $query = ProductColor::find()
            ->select([
                'product_color.name as name',
                'product_color.hex as hex',
                'COUNT(DISTINCT product_color.product_id) as count'
            ])
            ->innerJoin('product', 'product.id = product_color.product_id')
            ->where([
                'product.is_active' => 1
            ])
            ->andWhere(['!=', 'product.stock_status', Product::STOCK_OUT_OF_STOCK]);
        
        // Применяем фильтры (кроме цветов)
        self::applyFiltersToQuery($query, $currentFilters, $baseConditions, ['colors']);
        
        return $query
            ->groupBy(['product_color.name', 'product_color.hex'])
            ->having(['>', 'count', 0])
            ->orderBy(['count' => SORT_DESC])
            ->asArray()
            ->all();
    }
    
    /**
     * Применение фильтров к запросу (с исключениями)
     * 
     * @param \yii\db\ActiveQuery $query
     * @param array $currentFilters
     * @param array $baseConditions
     * @param array $exclude Ключи фильтров, которые НЕ нужно применять
     */
    protected static function applyFiltersToQuery($query, array $currentFilters, array $baseConditions, array $exclude = []): void
    {
        // Базовые условия
        if (!empty($baseConditions)) {
            foreach ($baseConditions as $field => $value) {
                $query->andWhere(['product.' . $field => $value]);
            }
        }
        
        // Бренды
        if (!in_array('brands', $exclude) && !empty($currentFilters['brands'])) {
            $query->andWhere(['product.brand_id' => $currentFilters['brands']]);
        }
        
        // Категории
        if (!in_array('categories', $exclude) && !empty($currentFilters['categories'])) {
            $query->andWhere(['product.category_id' => $currentFilters['categories']]);
        }
        
        // Цена
        if (!in_array('price_from', $exclude) && isset($currentFilters['price_from']) && $currentFilters['price_from'] !== '' && $currentFilters['price_from'] !== null) {
            $query->andWhere(['>=', 'product.price', $currentFilters['price_from']]);
        }
        if (!in_array('price_to', $exclude) && isset($currentFilters['price_to']) && $currentFilters['price_to'] !== '' && $currentFilters['price_to'] !== null) {
            $query->andWhere(['<=', 'product.price', $currentFilters['price_to']]);
        }
        
        // Размеры
        if (!in_array('sizes', $exclude) && !in_array('size_system', $exclude) && !empty($currentFilters['sizes'])) {
            $sizeSystem = $currentFilters['size_system'] ?? 'eu';
            $sizeField = match($sizeSystem) {
                'us' => 'us_size',
                'uk' => 'uk_size',
                'cm' => 'cm_size',
                default => 'eu_size'
            };
            
            $query->andWhere([
                'product.id' => ProductSize::find()
                    ->select('product_id')
                    ->where([$sizeField => $currentFilters['sizes']])
                    ->andWhere(['is_available' => 1])
            ]);
        }
        
        // Цвета
        if (!in_array('colors', $exclude) && !empty($currentFilters['colors'])) {
            $query->andWhere([
                'product.id' => ProductColor::find()
                    ->select('product_id')
                    ->where(['hex' => $currentFilters['colors']])
            ]);
        }
        
        // Характеристики (формат: char_{id} => [value_ids])
        foreach ($currentFilters as $key => $value) {
            if (strpos($key, 'char_') === 0 && !in_array($key, $exclude)) {
                // Проверяем что value не пустой массив и не null
                if (is_array($value) && count($value) > 0) {
                    $charId = (int)str_replace('char_', '', $key);
                    
                    // УПРОЩЕНО: Проверяем, это поле product или characteristic_value
                    $characteristic = \app\models\Characteristic::findOne($charId);
                    if ($characteristic && in_array($characteristic->key, ['gender', 'season', 'material', 'height', 'fastening', 'country'])) {
                        // Поле product - используем строковые значения напрямую
                        $query->andWhere(['product.' . $characteristic->key => $value]);
                    } else {
                        // Фильтруем через product_characteristic_value
                        $query->andWhere([
                            'product.id' => ProductCharacteristicValue::find()
                                ->select('product_id')
                                ->where([
                                    'characteristic_id' => $charId,
                                    'characteristic_value_id' => $value
                                ])
                        ]);
                    }
                } elseif (!is_array($value) && !empty($value)) {
                    // Для single value (boolean, text)
                    $charId = (int)str_replace('char_', '', $key);
                    
                    // УПРОЩЕНО: Проверяем, это поле product или characteristic_value
                    $characteristic = \app\models\Characteristic::findOne($charId);
                    if ($characteristic && in_array($characteristic->key, ['gender', 'season', 'material', 'height', 'fastening', 'country'])) {
                        // Поле product - используем строковое значение напрямую
                        $query->andWhere(['product.' . $characteristic->key => $value]);
                    } else {
                        // Фильтруем через product_characteristic_value
                        $query->andWhere([
                            'product.id' => ProductCharacteristicValue::find()
                                ->select('product_id')
                                ->where([
                                    'characteristic_id' => $charId,
                                    'characteristic_value_id' => $value
                                ])
                        ]);
                    }
                }
            }
        }
    }
    
    /**
     * Форматирование активных фильтров для отображения тегов
     * ЦЕНТРАЛИЗОВАННЫЙ метод - единственный источник логики
     * 
     * @param array $filters Активные фильтры
     * @param callable|null $urlGenerator Функция генерации URL (по умолчанию query string)
     * @return array [['type' => 'brand', 'label' => 'Nike', 'removeUrl' => '...']]
     */
    public static function formatActiveFilters(array $filters, ?callable $urlGenerator = null): array
    {
        $active = [];
        
        // Если не передан генератор URL, используем query string по умолчанию
        if ($urlGenerator === null) {
            $urlGenerator = function($params) {
                return self::buildQueryStringUrl($params);
            };
        }
        
        // Бренды
        if (!empty($filters['brands'])) {
            $brands = Brand::find()->where(['id' => $filters['brands']])->all();
            foreach ($brands as $brand) {
                $params = $filters;
                $params['brands'] = array_diff($params['brands'], [$brand->id]);
                if (empty($params['brands'])) {
                    unset($params['brands']);
                }
                $active[] = [
                    'type' => 'brand',
                    'label' => $brand->name,
                    'removeUrl' => $urlGenerator($params),
                ];
            }
        }
        
        // Категории
        if (!empty($filters['categories'])) {
            $categories = Category::find()->where(['id' => $filters['categories']])->all();
            foreach ($categories as $category) {
                $params = $filters;
                $params['categories'] = array_diff($params['categories'], [$category->id]);
                if (empty($params['categories'])) {
                    unset($params['categories']);
                }
                $active[] = [
                    'type' => 'category',
                    'label' => $category->name,
                    'removeUrl' => $urlGenerator($params),
                ];
            }
        }
        
        // Цена
        if (!empty($filters['price_from']) || !empty($filters['price_to'])) {
            $label = 'Цена: ';
            if (!empty($filters['price_from'])) $label .= 'от ' . $filters['price_from'] . ' ';
            if (!empty($filters['price_to'])) $label .= 'до ' . $filters['price_to'];
            
            $params = $filters;
            unset($params['price_from'], $params['price_to']);
            $active[] = [
                'type' => 'price',
                'label' => trim($label),
                'removeUrl' => $urlGenerator($params),
            ];
        }
        
        // Размеры
        if (!empty($filters['sizes'])) {
            $sizeSystem = $filters['size_system'] ?? 'eu';
            $sizeSystemLabel = strtoupper($sizeSystem);
            
            foreach ($filters['sizes'] as $size) {
                $params = $filters;
                $params['sizes'] = array_diff($params['sizes'], [$size]);
                if (empty($params['sizes'])) {
                    unset($params['sizes']);
                }
                $active[] = [
                    'type' => 'size',
                    'label' => "Размер {$sizeSystemLabel}: {$size}",
                    'removeUrl' => $urlGenerator($params),
                ];
            }
        }
        
        // Цвета
        if (!empty($filters['colors'])) {
            foreach ($filters['colors'] as $color) {
                $params = $filters;
                $params['colors'] = array_diff($params['colors'], [$color]);
                if (empty($params['colors'])) {
                    unset($params['colors']);
                }
                $active[] = [
                    'type' => 'color',
                    'label' => "Цвет: {$color}",
                    'removeUrl' => $urlGenerator($params),
                ];
            }
        }
        
        // Скидка
        if (!empty($filters['discount_any'])) {
            $params = $filters;
            unset($params['discount_any']);
            $active[] = [
                'type' => 'discount',
                'label' => 'Товары со скидкой',
                'removeUrl' => $urlGenerator($params),
            ];
        }
        
        if (!empty($filters['discount_range'])) {
            $ranges = is_array($filters['discount_range']) ? $filters['discount_range'] : explode(',', $filters['discount_range']);
            foreach ($ranges as $range) {
                $params = $filters;
                $params['discount_range'] = array_diff((array)$params['discount_range'], [$range]);
                if (empty($params['discount_range'])) {
                    unset($params['discount_range']);
                }
                $label = match($range) {
                    '0-30' => 'Скидка до 30%',
                    '30-50' => 'Скидка 30-50%',
                    '50-100' => 'Скидка более 50%',
                    default => 'Скидка'
                };
                $active[] = [
                    'type' => 'discount_range',
                    'label' => $label,
                    'removeUrl' => $urlGenerator($params),
                ];
            }
        }
        
        // Рейтинг
        if (!empty($filters['rating'])) {
            $params = $filters;
            unset($params['rating']);
            $active[] = [
                'type' => 'rating',
                'label' => 'Рейтинг ' . $filters['rating'] . '★ и выше',
                'removeUrl' => $urlGenerator($params),
            ];
        }
        
        // Условия
        if (!empty($filters['conditions'])) {
            $conditions = is_array($filters['conditions']) ? $filters['conditions'] : explode(',', $filters['conditions']);
            foreach ($conditions as $condition) {
                $params = $filters;
                $params['conditions'] = array_diff((array)$params['conditions'], [$condition]);
                if (empty($params['conditions'])) {
                    unset($params['conditions']);
                }
                $label = match($condition) {
                    'new' => 'Новинки',
                    'hit' => 'Хиты продаж',
                    'in_stock' => 'В наличии',
                    default => ucfirst($condition)
                };
                $active[] = [
                    'type' => 'condition',
                    'label' => $label,
                    'removeUrl' => $urlGenerator($params),
                ];
            }
        }
        
        // Характеристики (динамические) - УПРОЩЕНО: единая система
        foreach ($filters as $key => $value) {
            if (strpos($key, 'char_') === 0 && !empty($value)) {
                $charId = (int)str_replace('char_', '', $key);
                $characteristic = Characteristic::findOne($charId);
                if ($characteristic) {
                    $values = is_array($value) ? $value : [$value];
                    foreach ($values as $valueId) {
                        // Определяем отображаемое значение
                        if (in_array($characteristic->key, ['gender', 'season', 'material', 'height', 'fastening', 'country'])) {
                            // Поле product - используем маппинг labels
                            $labels = self::getProductFieldLabels($characteristic->key);
                            $displayValue = $labels[$valueId] ?? $valueId;
                        } else {
                            // Characteristic_value - получаем из БД
                            $charValue = CharacteristicValue::findOne($valueId);
                            $displayValue = $charValue ? $charValue->value : $valueId;
                        }
                        
                        $params = $filters;
                        if (is_array($params[$key])) {
                            $params[$key] = array_diff($params[$key], [$valueId]);
                            if (empty($params[$key])) {
                                unset($params[$key]);
                            }
                        } else {
                            unset($params[$key]);
                        }
                        
                        $active[] = [
                            'type' => 'characteristic',
                            'label' => $characteristic->name . ': ' . $displayValue,
                            'removeUrl' => $urlGenerator($params),
                        ];
                    }
                }
            }
        }
        
        return $active;
    }
    
    /**
     * Построение query string URL из фильтров
     * Вспомогательный метод для formatActiveFilters
     * 
     * @param array $filters
     * @return string
     */
    protected static function buildQueryStringUrl(array $filters): string
    {
        $params = [];
        if (!empty($filters['brands'])) $params['brands'] = implode(',', $filters['brands']);
        if (!empty($filters['categories'])) $params['categories'] = implode(',', $filters['categories']);
        if (!empty($filters['sizes'])) $params['sizes'] = implode(',', $filters['sizes']);
        if (!empty($filters['size_system'])) $params['size_system'] = $filters['size_system'];
        if (!empty($filters['price_from'])) $params['price_from'] = $filters['price_from'];
        if (!empty($filters['price_to'])) $params['price_to'] = $filters['price_to'];
        if (!empty($filters['colors'])) $params['colors'] = implode(',', $filters['colors']);
        if (!empty($filters['discount_any'])) $params['discount_any'] = 1;
        if (!empty($filters['discount_range'])) $params['discount_range'] = is_array($filters['discount_range']) ? implode(',', $filters['discount_range']) : $filters['discount_range'];
        if (!empty($filters['rating'])) $params['rating'] = $filters['rating'];
        if (!empty($filters['conditions'])) $params['conditions'] = is_array($filters['conditions']) ? implode(',', $filters['conditions']) : $filters['conditions'];
        
        // Характеристики
        foreach ($filters as $key => $value) {
            if (strpos($key, 'char_') === 0 && !empty($value)) {
                $params[$key] = is_array($value) ? implode(',', $value) : $value;
            }
        }
        
        return '/catalog' . (empty($params) ? '' : '?' . http_build_query($params));
    }
    
    /**
     * Применение фильтров к запросу товаров
     * Используется в CatalogController::applyFilters()
     * 
     * @param \yii\db\ActiveQuery $query
     * @param array $filters Фильтры из запроса
     */
    public static function applyFiltersToProductQuery($query, array $filters): void
    {
        // Используем основной метод с пустыми исключениями
        self::applyFiltersToQuery($query, $filters, [], []);
        
        // Дополнительные фильтры, специфичные для товаров
        
        // Скидка
        if (!empty($filters['discount_any'])) {
            $query->andWhere(['>', 'old_price', 0]);
        }
        
        if (!empty($filters['discount_range'])) {
            $ranges = is_array($filters['discount_range']) ? $filters['discount_range'] : explode(',', $filters['discount_range']);
            $discountConditions = ['or'];
            foreach ($ranges as $range) {
                list($min, $max) = explode('-', $range);
                $discountConditions[] = [
                    'and',
                    ['>', 'old_price', 0],
                    ['>=', 'ROUND((old_price - price) / old_price * 100)', $min],
                    ['<=', 'ROUND((old_price - price) / old_price * 100)', $max]
                ];
            }
            if (count($discountConditions) > 1) {
                $query->andWhere($discountConditions);
            }
        }
        
        // Рейтинг
        if (!empty($filters['rating'])) {
            $query->andWhere(['>=', 'rating', $filters['rating']]);
        }
        
        // Условия
        if (!empty($filters['conditions'])) {
            $conditions = is_array($filters['conditions']) ? $filters['conditions'] : explode(',', $filters['conditions']);
            foreach ($conditions as $condition) {
                switch ($condition) {
                    case 'new':
                        $query->andWhere(['>=', 'created_at', date('Y-m-d', strtotime('-30 days'))]);
                        break;
                    case 'hit':
                        $query->andWhere(['is_featured' => 1]);
                        break;
                    case 'in_stock':
                        $query->andWhere(['stock_status' => 'in_stock']);
                        break;
                }
            }
        }
    }
}
