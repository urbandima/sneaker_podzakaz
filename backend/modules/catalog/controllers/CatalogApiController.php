<?php

/**
 * CatalogApiController — API endpoints для каталога
 * 
 * НАЗНАЧЕНИЕ:
 * AJAX-запросы для фильтрации, поиска, получения товаров
 * Вынесено из CatalogController для разделения ответственности
 */
namespace app\backend\modules\catalog\controllers;

use Yii;
use yii\web\Controller;
use yii\web\Response;
use yii\data\Pagination;
use yii\filters\VerbFilter;
use yii\filters\ContentNegotiator;
use app\backend\modules\catalog\models\Product;
use app\backend\modules\catalog\models\Brand;
use app\backend\modules\catalog\services\Catalog\FilterBuilder;

class CatalogApiController extends Controller
{
    public $enableCsrfValidation = false;

    public function behaviors()
    {
        return [
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'filter' => ['POST'],
                    'load-more' => ['GET'],
                    'quick-view' => ['GET'],
                ],
            ],
            'contentNegotiator' => [
                'class' => ContentNegotiator::class,
                'formats' => [
                    'application/json' => Response::FORMAT_JSON,
                ],
            ],
        ];
    }

    /**
     * AJAX фильтрация товаров
     */
    public function actionFilter()
    {
        $request = Yii::$app->request;
        
        // Параметры пагинации
        $page = (int)$request->post('page', 1);
        $perPage = (int)$request->post('perPage', 24);
        
        // Собираем фильтры
        $filters = $this->collectFilters($request);
        
        // Базовый запрос
        $query = Product::find()
            ->select([
                'id', 'name', 'slug', 'brand_id', 'brand_name', 'category_name',
                'main_image_url', 'price', 'old_price', 'stock_status',
                'is_featured', 'rating', 'reviews_count', 'views_count', 'created_at'
            ])
            ->where(['product.is_active' => true])
            ->andWhere(['!=', 'product.stock_status', Product::STOCK_OUT_OF_STOCK]);
        
        // Применяем фильтры
        $query = FilterBuilder::applyFiltersToProductQuery($query, $filters);
        
        // Применяем сортировку
        $this->applySorting($query, $filters['sort'] ?? 'popular');
        
        // Пагинация
        $countQuery = clone $query;
        $totalCount = $countQuery->count();
        
        $pagination = new Pagination([
            'defaultPageSize' => $perPage,
            'totalCount' => $totalCount,
            'page' => $page - 1,
        ]);
        
        $products = $query
            ->with(['brand' => function($q) {
                $q->select(['id', 'name', 'slug']);
            }])
            ->offset($pagination->offset)
            ->limit($pagination->limit)
            ->all();
        
        // Рендерим HTML
        $html = $this->renderPartial('/catalog/_products', ['products' => $products]);
        
        return [
            'success' => true,
            'html' => $html,
            'pagination' => [
                'total' => $pagination->totalCount,
                'currentPage' => $page,
                'totalPages' => $pagination->pageCount,
                'perPage' => $perPage,
            ],
        ];
    }

    /**
     * Загрузка дополнительных товаров (infinite scroll)
     */
    public function actionLoadMore($page = 1)
    {
        $perPage = 24;
        
        $query = Product::find()
            ->where(['is_active' => true])
            ->andWhere(['!=', 'stock_status', Product::STOCK_OUT_OF_STOCK])
            ->orderBy(['views_count' => SORT_DESC]);
        
        $totalCount = $query->count();
        $totalPages = ceil($totalCount / $perPage);
        
        $products = $query
            ->offset(($page - 1) * $perPage)
            ->limit($perPage)
            ->all();
        
        $html = '';
        if (!empty($products)) {
            $html = $this->renderPartial('/catalog/_products', ['products' => $products]);
        }
        
        return [
            'success' => true,
            'html' => $html,
            'hasMore' => $page < $totalPages,
            'currentPage' => $page,
            'totalPages' => $totalPages,
        ];
    }

    /**
     * Быстрый просмотр товара (modal)
     */
    public function actionQuickView($id)
    {
        $product = Product::findOne($id);
        
        if (!$product) {
            return ['success' => false, 'message' => 'Товар не найден'];
        }
        
        $html = $this->renderPartial('/catalog/_quick_view', [
            'product' => $product,
        ]);
        
        return [
            'success' => true,
            'html' => $html,
            'product' => [
                'id' => $product->id,
                'name' => $product->name,
                'price' => $product->price,
                'slug' => $product->slug,
            ],
        ];
    }

    /**
     * Получить список брендов с количеством товаров
     */
    public function actionGetBrands()
    {
        $brands = Brand::find()
            ->select(['brand.id', 'brand.name', 'brand.slug', 'COUNT(product.id) as products_count'])
            ->leftJoin('product', 'product.brand_id = brand.id AND product.is_active = true')
            ->groupBy(['brand.id', 'brand.name', 'brand.slug'])
            ->having('COUNT(product.id) > 0')
            ->orderBy(['products_count' => SORT_DESC, 'brand.name' => SORT_ASC])
            ->asArray()
            ->all();
        
        return $brands;
    }

    /**
     * Получить товары по списку IDs
     */
    public function actionProductsByIds()
    {
        $ids = Yii::$app->request->get('ids');
        if (!$ids) {
            return [];
        }
        
        $ids = is_array($ids) ? $ids : explode(',', $ids);
        $ids = array_filter(array_map('intval', $ids));
        
        if (empty($ids)) {
            return [];
        }
        
        $products = Product::find()
            ->with(['brand'])
            ->where(['id' => $ids, 'is_active' => true])
            ->andWhere(['!=', 'stock_status', Product::STOCK_OUT_OF_STOCK])
            ->limit(20)
            ->all();
        
        $result = [];
        foreach ($products as $product) {
            $result[] = [
                'id' => $product->id,
                'name' => $product->name,
                'brand' => $product->brand->name ?? '',
                'price' => Yii::$app->formatter->asCurrency($product->price, 'BYN'),
                'image' => $product->getMainImageUrl(),
                'url' => $product->getUrl(),
            ];
        }
        
        return $result;
    }

    /**
     * Собрать фильтры из запроса
     */
    private function collectFilters($request): array
    {
        $filters = [
            'brands' => $this->parseJsonOrArray($request->post('brands')),
            'categories' => $this->parseJsonOrArray($request->post('categories')),
            'sizes' => $this->parseJsonOrArray($request->post('sizes')),
            'size_system' => $request->post('sizeSystem', 'eu'),
            'price_from' => $request->post('price_from'),
            'price_to' => $request->post('price_to'),
            'colors' => $this->parseJsonOrArray($request->post('colors')),
            'discount_any' => $request->post('discount_any'),
            'discount_range' => $this->parseJsonOrArray($request->post('discount_range')),
            'rating' => $request->post('rating'),
            'conditions' => $this->parseJsonOrArray($request->post('conditions')),
            'sort' => $request->post('sort', 'popular'),
        ];
        
        // Характеристики
        foreach ($request->post() as $key => $value) {
            if (strpos($key, 'char_') === 0 && !empty($value)) {
                $filters[$key] = $this->parseJsonOrArray($value);
            }
        }
        
        return $filters;
    }

    /**
     * Парсить JSON или массив
     */
    private function parseJsonOrArray($value): array
    {
        if (is_string($value)) {
            $decoded = json_decode($value, true);
            return $decoded ?: [];
        }
        return is_array($value) ? $value : [];
    }

    /**
     * Применить сортировку
     */
    private function applySorting($query, string $sortBy)
    {
        switch ($sortBy) {
            case 'price_asc':
                $query->orderBy(['price' => SORT_ASC]);
                break;
            case 'price_desc':
                $query->orderBy(['price' => SORT_DESC]);
                break;
            case 'new':
                $query->orderBy(['created_at' => SORT_DESC]);
                break;
            case 'rating':
                $query->orderBy(['rating' => SORT_DESC]);
                break;
            case 'discount':
                $query->andWhere(['>', 'old_price', 0])
                      ->orderBy(['(old_price - price) / old_price' => SORT_DESC]);
                break;
            case 'popular':
            default:
                $query->orderBy(['views_count' => SORT_DESC]);
        }
    }
}
