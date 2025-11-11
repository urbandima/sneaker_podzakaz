<?php

namespace app\components;

use Yii;
use yii\base\Component;
use app\models\Brand;
use app\models\Category;

/**
 * Компонент для работы с умным фильтром (SEF URL)
 * Реализует лучшие практики Битрикс24, WildBerries, Amazon
 */
class SmartFilter extends Component
{
    /**
     * Генерация SEF URL из фильтров
     * 
     * @param array $filters ['brands' => [1,2], 'price_from' => 100, 'categories' => [3]]
     * @return string /catalog/filter/nike-adidas/price-100-500/
     */
    public static function generateSefUrl($filters)
    {
        if (empty($filters)) {
            return '/catalog/';
        }
        
        $parts = [];
        
        // Бренды (сортируем по slug для консистентности URL)
        if (!empty($filters['brands'])) {
            $brandSlugs = Brand::find()
                ->select('slug')
                ->where(['id' => $filters['brands']])
                ->orderBy(['slug' => SORT_ASC])
                ->column();
            
            if ($brandSlugs) {
                $parts[] = 'brand-' . implode('-', $brandSlugs);
            }
        }
        
        // Категории
        if (!empty($filters['categories'])) {
            $categorySlugs = Category::find()
                ->select('slug')
                ->where(['id' => $filters['categories']])
                ->orderBy(['slug' => SORT_ASC])
                ->column();
            
            if ($categorySlugs) {
                $parts[] = 'category-' . implode('-', $categorySlugs);
            }
        }
        
        // Цена (диапазон)
        if (!empty($filters['price_from']) || !empty($filters['price_to'])) {
            $from = !empty($filters['price_from']) ? (int)$filters['price_from'] : 'min';
            $to = !empty($filters['price_to']) ? (int)$filters['price_to'] : 'max';
            $parts[] = "price-{$from}-{$to}";
        }
        
        // Размеры (с указанием системы измерения для SEO)
        if (!empty($filters['sizes'])) {
            $sizes = is_array($filters['sizes']) 
                ? implode('-', array_map('strval', $filters['sizes'])) 
                : strval($filters['sizes']);
            
            // Добавляем систему размеров в URL для лучшей индексации
            $sizeSystem = $filters['size_system'] ?? 'eu';
            $parts[] = 'size-' . strtolower($sizeSystem) . '-' . $sizes;
        }
        
        // Цвета
        if (!empty($filters['colors'])) {
            $colors = is_array($filters['colors'])
                ? implode('-', $filters['colors'])
                : $filters['colors'];
            $parts[] = 'color-' . $colors;
        }
        
        if (empty($parts)) {
            return '/catalog/';
        }
        
        return '/catalog/filter/' . implode('/', $parts) . '/';
    }
    
    /**
     * Парсинг SEF URL в массив фильтров
     * 
     * @param string $sefString nike-adidas/price-100-500/size-40-41
     * @return array ['brands' => [1,2], 'price_from' => 100, 'price_to' => 500]
     */
    public static function parseSefUrl($sefString)
    {
        if (empty($sefString)) {
            return [];
        }
        
        $filters = [];
        $parts = explode('/', trim($sefString, '/'));
        
        foreach ($parts as $part) {
            // brand-nike-adidas
            if (preg_match('/^brand-(.+)$/', $part, $m)) {
                $brandSlugs = explode('-', $m[1]);
                $brandIds = Brand::find()
                    ->select('id')
                    ->where(['slug' => $brandSlugs, 'is_active' => 1])
                    ->column();
                if ($brandIds) {
                    $filters['brands'] = $brandIds;
                }
            }
            // category-krossovki-kedy
            elseif (preg_match('/^category-(.+)$/', $part, $m)) {
                $categorySlugs = explode('-', $m[1]);
                $categoryIds = Category::find()
                    ->select('id')
                    ->where(['slug' => $categorySlugs, 'is_active' => 1])
                    ->column();
                if ($categoryIds) {
                    $filters['categories'] = $categoryIds;
                }
            }
            // price-100-500 или price-min-500 или price-100-max
            elseif (preg_match('/^price-([0-9]+|min)-([0-9]+|max)$/', $part, $m)) {
                if ($m[1] !== 'min' && $m[1] > 0) {
                    $filters['price_from'] = (int)$m[1];
                }
                if ($m[2] !== 'max') {
                    $filters['price_to'] = (int)$m[2];
                }
            }
            // size-eu-40-41-42 или size-40-41-42 (старый формат)
            elseif (preg_match('/^size-(?:(eu|us|uk|cm)-)?(.+)$/', $part, $m)) {
                if (!empty($m[1])) {
                    $filters['size_system'] = $m[1];
                    $filters['sizes'] = explode('-', $m[2]);
                } else {
                    // Старый формат без системы - по умолчанию EU
                    $filters['size_system'] = 'eu';
                    $filters['sizes'] = explode('-', $m[2]);
                }
            }
            // color-red-blue
            elseif (preg_match('/^color-(.+)$/', $part, $m)) {
                $filters['colors'] = explode('-', $m[1]);
            }
        }
        
        return $filters;
    }
    
    /**
     * Получить canonical URL для комбинации фильтров
     * Логика как в Битрикс24
     */
    public static function getCanonicalUrl($filters, $productsCount)
    {
        $hostInfo = Yii::$app->request->hostInfo;
        
        // Если товаров очень мало - canonical на главную каталога
        if ($productsCount < 3) {
            return $hostInfo . '/catalog/';
        }
        
        // Если фильтров нет - canonical на текущую страницу
        if (empty($filters)) {
            return $hostInfo . '/catalog/';
        }
        
        // Если только один фильтр - canonical на страницу этого фильтра
        $filterCount = 0;
        if (!empty($filters['brands'])) $filterCount++;
        if (!empty($filters['categories'])) $filterCount++;
        if (!empty($filters['price_from']) || !empty($filters['price_to'])) $filterCount++;
        if (!empty($filters['sizes'])) $filterCount++;
        if (!empty($filters['colors'])) $filterCount++;
        
        // Один бренд без других фильтров - canonical на страницу бренда
        if ($filterCount == 1 && !empty($filters['brands']) && count($filters['brands']) == 1) {
            $brand = Brand::findOne($filters['brands'][0]);
            if ($brand) {
                return $hostInfo . '/catalog/brand/' . $brand->slug;
            }
        }
        
        // Одна категория без других фильтров - canonical на страницу категории
        if ($filterCount == 1 && !empty($filters['categories']) && count($filters['categories']) == 1) {
            $category = Category::findOne($filters['categories'][0]);
            if ($category) {
                return $hostInfo . '/catalog/category/' . $category->slug;
            }
        }
        
        // Для комбинаций фильтров - canonical на SEF URL
        if ($productsCount >= 10) {
            // Много товаров - canonical на текущую комбинацию
            return $hostInfo . self::generateSefUrl($filters);
        } else {
            // Среднее количество - canonical на базовую страницу
            // Убираем самый специфичный фильтр (цену или размеры)
            $canonicalFilters = $filters;
            if (isset($canonicalFilters['sizes'])) {
                unset($canonicalFilters['sizes']);
            } elseif (isset($canonicalFilters['price_from']) || isset($canonicalFilters['price_to'])) {
                unset($canonicalFilters['price_from'], $canonicalFilters['price_to']);
            }
            
            if (empty($canonicalFilters)) {
                return $hostInfo . '/catalog/';
            }
            
            return $hostInfo . self::generateSefUrl($canonicalFilters);
        }
    }
    
    /**
     * Получить robots директиву в зависимости от количества товаров
     * Логика как в Битрикс24
     * 
     * @param int $productsCount
     * @return string
     */
    public static function getRobotsDirective($productsCount)
    {
        if ($productsCount >= 10) {
            return 'index, follow'; // Много товаров - индексировать
        } elseif ($productsCount >= 3) {
            return 'noindex, follow'; // Среднее кол-во - не индексировать, но следовать по ссылкам
        } else {
            return 'noindex, nofollow'; // Мало товаров - не индексировать вообще
        }
    }
    
    /**
     * Форматирование активных фильтров для отображения тегов
     * 
     * @param array $filters
     * @return array [['type' => 'brand', 'label' => 'Nike', 'removeUrl' => '...']]
     */
    public static function formatActiveFilters($filters)
    {
        $active = [];
        
        // Бренды
        if (!empty($filters['brands'])) {
            $brands = Brand::find()->where(['id' => $filters['brands']])->all();
            foreach ($brands as $brand) {
                $removeFilters = $filters;
                $removeFilters['brands'] = array_diff($removeFilters['brands'], [$brand->id]);
                if (empty($removeFilters['brands'])) {
                    unset($removeFilters['brands']);
                }
                
                $active[] = [
                    'type' => 'brand',
                    'id' => $brand->id,
                    'label' => 'Бренд: ' . $brand->name,
                    'removeUrl' => self::generateSefUrl($removeFilters)
                ];
            }
        }
        
        // Категории
        if (!empty($filters['categories'])) {
            $categories = Category::find()->where(['id' => $filters['categories']])->all();
            foreach ($categories as $category) {
                $removeFilters = $filters;
                $removeFilters['categories'] = array_diff($removeFilters['categories'], [$category->id]);
                if (empty($removeFilters['categories'])) {
                    unset($removeFilters['categories']);
                }
                
                $active[] = [
                    'type' => 'category',
                    'id' => $category->id,
                    'label' => 'Категория: ' . $category->name,
                    'removeUrl' => self::generateSefUrl($removeFilters)
                ];
            }
        }
        
        // Цена
        if (!empty($filters['price_from']) || !empty($filters['price_to'])) {
            $from = $filters['price_from'] ?? 'min';
            $to = $filters['price_to'] ?? 'max';
            
            $removeFilters = $filters;
            unset($removeFilters['price_from'], $removeFilters['price_to']);
            
            $active[] = [
                'type' => 'price',
                'label' => "Цена: {$from} - {$to} BYN",
                'removeUrl' => self::generateSefUrl($removeFilters)
            ];
        }
        
        // Размеры
        if (!empty($filters['sizes'])) {
            $sizes = is_array($filters['sizes']) ? implode(', ', $filters['sizes']) : $filters['sizes'];
            $sizeSystem = strtoupper($filters['size_system'] ?? 'EU');
            
            $removeFilters = $filters;
            unset($removeFilters['sizes'], $removeFilters['size_system']);
            
            $active[] = [
                'type' => 'size',
                'label' => "Размер {$sizeSystem}: {$sizes}",
                'removeUrl' => self::generateSefUrl($removeFilters)
            ];
        }
        
        // Цвета
        if (!empty($filters['colors'])) {
            $colors = is_array($filters['colors']) ? implode(', ', $filters['colors']) : $filters['colors'];
            
            $removeFilters = $filters;
            unset($removeFilters['colors']);
            
            $active[] = [
                'type' => 'color',
                'label' => "Цвет: {$colors}",
                'removeUrl' => self::generateSefUrl($removeFilters)
            ];
        }
        
        return $active;
    }
    
    /**
     * Генерация динамического H1 на основе фильтров
     */
    public static function generateDynamicH1($filters, $productsCount)
    {
        $parts = [];
        
        // Бренды
        if (!empty($filters['brands'])) {
            $brands = Brand::find()
                ->select('name')
                ->where(['id' => $filters['brands']])
                ->column();
            if ($brands) {
                $parts[] = implode(', ', $brands);
            }
        }
        
        // Категории
        if (!empty($filters['categories'])) {
            $categories = Category::find()
                ->select('name')
                ->where(['id' => $filters['categories']])
                ->column();
            if ($categories) {
                $parts[] = implode(', ', $categories);
            }
        }
        
        // Цена
        if (!empty($filters['price_from']) || !empty($filters['price_to'])) {
            $from = $filters['price_from'] ?? 'min';
            $to = $filters['price_to'] ?? 'max';
            $parts[] = "Цена {$from}-{$to} BYN";
        }
        
        if (empty($parts)) {
            return "Каталог товаров ($productsCount)";
        }
        
        $title = implode(' - ', $parts);
        
        // Добавляем количество
        if ($productsCount == 1) {
            $title .= " (1 товар)";
        } elseif ($productsCount < 5) {
            $title .= " ($productsCount товара)";
        } else {
            $title .= " ($productsCount товаров)";
        }
        
        return $title;
    }
    
    /**
     * Генерация динамического meta description
     */
    public static function generateMetaDescription($filters, $productsCount)
    {
        $parts = [];
        
        if (!empty($filters['brands'])) {
            $brands = Brand::find()->select('name')->where(['id' => $filters['brands']])->column();
            if ($brands) {
                $parts[] = implode(', ', $brands);
            }
        }
        
        if (!empty($filters['categories'])) {
            $categories = Category::find()->select('name')->where(['id' => $filters['categories']])->column();
            if ($categories) {
                $parts[] = strtolower(implode(', ', $categories));
            }
        }
        
        $description = "Купить ";
        if (!empty($parts)) {
            $description .= implode(' - ', $parts) . ". ";
        } else {
            $description .= "товары в каталоге. ";
        }
        
        $description .= "Найдено $productsCount товаров. ";
        $description .= "Оригинальная продукция из США и Европы. Гарантия качества, доставка по Беларуси.";
        
        return $description;
    }
}
