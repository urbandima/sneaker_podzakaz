<?php

namespace app\controllers;

use Yii;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\web\Response;
use yii\data\Pagination;
use app\models\Product;
use app\models\Category;
use app\models\Brand;
use app\models\ProductFavorite;
use app\models\CatalogInquiry;
use app\components\SmartFilter;

/**
 * Контроллер каталога товаров
 */
class CatalogController extends Controller
{
    public $layout = 'public';

    /**
     * Регистрация мета-тегов
     */
    protected function registerMetaTags($tags)
    {
        foreach ($tags as $name => $content) {
            if (strpos($name, 'og:') === 0 || strpos($name, 'product:') === 0 || strpos($name, 'twitter:') === 0) {
                $this->view->registerMetaTag(['property' => $name, 'content' => $content], $name);
            } else {
                $this->view->registerMetaTag(['name' => $name, 'content' => $content], $name);
            }
        }
        
        // Canonical URL
        $this->view->registerLinkTag(['rel' => 'canonical', 'href' => Yii::$app->request->absoluteUrl]);
    }

    /**
     * Главная страница каталога
     */
    public function actionIndex()
    {
        $query = Product::find()
            ->with([
                'brand', 
                'category', 
                'images' => function($q) {
                    $q->where(['is_main' => 1])->orWhere(['sort_order' => 0])->limit(1);
                },
                'colors', 
                'sizes'
            ])
            ->where(['is_active' => 1]);

        // Применение фильтров
        $query = $this->applyFilters($query);

        // Пагинация с кэшированным COUNT
        $pagination = new Pagination([
            'defaultPageSize' => 24,
            'totalCount' => $this->getCachedCount($query),
        ]);

        $products = $query
            ->offset($pagination->offset)
            ->limit($pagination->limit)
            ->all();

        // Получение данных для фильтров
        $filters = $this->getFiltersData();

        // SEO meta-теги
        $this->view->title = 'Каталог товаров - Оригинальные кроссовки и одежда | СНИКЕРХЭД';
        $this->registerMetaTags([
            'keywords' => 'купить кроссовки, оригинальная обувь, nike, adidas, интернет-магазин',
            'og:title' => 'Каталог товаров - СНИКЕРХЭД',
            'og:description' => 'Оригинальные товары из США и Европы',
            'og:type' => 'website',
            'og:url' => Yii::$app->request->absoluteUrl,
        ]);
        
        // FIX: Объявляем переменную request
        $request = Yii::$app->request;
        
        // Текущие выбранные фильтры
        $currentFilters = [
            'brands' => $request->get('brands') ? explode(',', $request->get('brands')) : [],
            'categories' => $request->get('categories') ? explode(',', $request->get('categories')) : [],
            'price_from' => $request->get('price_from'),
            'price_to' => $request->get('price_to'),
        ];

        // Активные фильтры (для отображения тегов)
        $activeFilters = $this->getActiveFilters($currentFilters);

        return $this->render('index', [
            'products' => $products,
            'pagination' => $pagination,
            'filters' => $filters,
            'currentFilters' => $currentFilters,
            'activeFilters' => $activeFilters,
        ]);
    }
    
    /**
     * Страница всех брендов
     */
    public function actionBrands()
    {
        $brands = Brand::find()
            ->where(['is_active' => 1])
            ->orderBy(['name' => SORT_ASC])
            ->all();
        
        return $this->render('brands', [
            'brands' => $brands,
        ]);
    }
    
    /**
     * Избранное
     */
    public function actionFavorites()
    {
        $userId = Yii::$app->user->isGuest ? null : Yii::$app->user->id;
        $sessionId = Yii::$app->session->id;
        
        $favorites = ProductFavorite::getFavorites($userId, $sessionId);
        
        return $this->render('favorites', [
            'favorites' => $favorites,
        ]);
    }
    
    /**
     * Live поиск (AJAX)
     */
    public function actionSearch()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        
        $query = Yii::$app->request->get('q');
        
        if (!$query || mb_strlen($query) < 2) {
            return ['results' => []];
        }
        
        $products = Product::find()
            ->select(['id', 'name', 'slug', 'price', 'old_price', 'main_image'])
            ->with(['brand'])
            ->where(['is_active' => 1])
            ->andWhere(['like', 'name', $query])
            ->limit(10)
            ->all();
        
        $results = [];
        foreach ($products as $product) {
            $results[] = [
                'id' => $product->id,
                'name' => $product->name,
                'brand' => $product->brand->name ?? '',
                'price' => $product->price,
                'old_price' => $product->old_price,
                'discount' => $product->getDiscountPercent(),
                'url' => '/catalog/product/' . $product->slug,
                'image' => $product->getMainImageUrl(),
            ];
        }
        
        return ['results' => $results];
    }
    
    /**
     * Quick View (AJAX) - быстрый просмотр товара
     */
    public function actionQuickView($id)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        
        $product = Product::find()
            ->with(['brand', 'category', 'sizes', 'colors', 'images'])
            ->where(['id' => $id, 'is_active' => 1])
            ->one();
        
        if (!$product) {
            return ['success' => false, 'message' => 'Товар не найден'];
        }
        
        return [
            'success' => true,
            'html' => $this->renderAjax('_quick_view', ['product' => $product]),
        ];
    }
    
    /**
     * Получение активных фильтров для отображения
     */
    protected function getActiveFilters($currentFilters)
    {
        $active = [];
        
        // Бренды
        if (!empty($currentFilters['brands'])) {
            $brands = Brand::find()->where(['id' => $currentFilters['brands']])->all();
            foreach ($brands as $brand) {
                $params = $currentFilters;
                $params['brands'] = array_diff($params['brands'], [$brand->id]);
                $active[] = [
                    'label' => $brand->name,
                    'removeUrl' => $this->buildFilterUrl($params),
                ];
            }
        }
        
        // Категории
        if (!empty($currentFilters['categories'])) {
            $categories = Category::find()->where(['id' => $currentFilters['categories']])->all();
            foreach ($categories as $category) {
                $params = $currentFilters;
                $params['categories'] = array_diff($params['categories'], [$category->id]);
                $active[] = [
                    'label' => $category->name,
                    'removeUrl' => $this->buildFilterUrl($params),
                ];
            }
        }
        
        // Цена
        if (!empty($currentFilters['price_from']) || !empty($currentFilters['price_to'])) {
            $label = 'Цена: ';
            if (!empty($currentFilters['price_from'])) $label .= 'от ' . $currentFilters['price_from'] . ' ';
            if (!empty($currentFilters['price_to'])) $label .= 'до ' . $currentFilters['price_to'];
            
            $params = $currentFilters;
            unset($params['price_from'], $params['price_to']);
            $active[] = [
                'label' => trim($label),
                'removeUrl' => $this->buildFilterUrl($params),
            ];
        }
        
        return $active;
    }
    
    /**
     * Построение URL с фильтрами
     */
    protected function buildFilterUrl($filters)
    {
        $params = [];
        if (!empty($filters['brands'])) $params['brands'] = implode(',', $filters['brands']);
        if (!empty($filters['categories'])) $params['categories'] = implode(',', $filters['categories']);
        if (!empty($filters['price_from'])) $params['price_from'] = $filters['price_from'];
        if (!empty($filters['price_to'])) $params['price_to'] = $filters['price_to'];
        
        return '/catalog' . (empty($params) ? '' : '?' . http_build_query($params));
    }

    /**
     * Каталог по бренду
     */
    public function actionBrand($slug)
    {
        $brand = Brand::findBySlug($slug);
        
        if (!$brand) {
            return $this->renderError(404, 'Бренд не найден');
        }

        $query = Product::find()
            ->with([
                'brand', 
                'category',
                'images' => function($q) {
                    $q->where(['is_main' => 1])->orWhere(['sort_order' => 0])->limit(1);
                },
            ])
            ->where(['brand_id' => $brand->id, 'is_active' => 1]);

        $query = $this->applyFilters($query);

        $pagination = new Pagination([
            'defaultPageSize' => 24,
            'totalCount' => $this->getCachedCount($query),
        ]);

        $products = $query
            ->offset($pagination->offset)
            ->limit($pagination->limit)
            ->all();

        $filters = $this->getFiltersData(['brand_id' => $brand->id]);

        // SEO
        $this->view->title = $brand->getMetaTitle();
        $this->view->registerMetaTag(['name' => 'description', 'content' => $brand->getMetaDescription()]);
        $this->view->registerMetaTag(['name' => 'keywords', 'content' => $brand->name . ', оригинальные товары, купить']);
        $this->view->registerMetaTag(['property' => 'og:title', 'content' => $brand->getMetaTitle()]);
        $this->view->registerMetaTag(['property' => 'og:description', 'content' => $brand->getMetaDescription()]);
        $this->view->registerMetaTag(['property' => 'og:type', 'content' => 'website']);
        $this->view->registerMetaTag(['property' => 'og:url', 'content' => Yii::$app->request->absoluteUrl]);
        if ($brand->logo) {
            $this->view->registerMetaTag(['property' => 'og:image', 'content' => Yii::$app->request->hostInfo . $brand->logo]);
        }

        return $this->render('brand', [
            'brand' => $brand,
            'products' => $products,
            'pagination' => $pagination,
            'filters' => $filters,
        ]);
    }

    /**
     * Каталог по категории
     */
    public function actionCategory($slug)
    {
        $category = Category::findBySlug($slug);
        
        if (!$category) {
            return $this->renderError(404, 'Категория не найдена');
        }

        // Получаем ID категории и всех дочерних
        $categoryIds = $category->getChildrenIds();

        $query = Product::find()
            ->with([
                'brand', 
                'category',
                'images' => function($q) {
                    $q->where(['is_main' => 1])->orWhere(['sort_order' => 0])->limit(1);
                },
            ])
            ->where(['category_id' => $categoryIds, 'is_active' => 1]);

        $query = $this->applyFilters($query);

        $pagination = new Pagination([
            'defaultPageSize' => 24,
            'totalCount' => $this->getCachedCount($query),
        ]);

        $products = $query
            ->offset($pagination->offset)
            ->limit($pagination->limit)
            ->all();

        $filters = $this->getFiltersData(['category_id' => $categoryIds]);

        // SEO
        $this->view->title = $category->getMetaTitle();
        $this->registerMetaTags([
            'description' => $category->getMetaDescription(),
            'keywords' => $category->name . ', купить, оригинал',
            'og:title' => $category->getMetaTitle(),
            'og:description' => $category->getMetaDescription(),
            'og:type' => 'website',
            'og:url' => Yii::$app->request->absoluteUrl,
        ]);

        return $this->render('category', [
            'category' => $category,
            'products' => $products,
            'pagination' => $pagination,
            'filters' => $filters,
        ]);
    }

    /**
     * Карточка товара
     */
    public function actionProduct($slug)
    {
        $product = Product::find()
            ->with(['brand', 'category', 'images', 'sizes', 'colors'])
            ->where(['slug' => $slug, 'is_active' => 1])
            ->one();

        if (!$product) {
            return $this->renderError(404, 'Товар не найден');
        }

        // Увеличиваем счетчик просмотров
        $product->incrementViews();

        // Похожие товары
        $similarProducts = $product->getSimilarProducts(4);

        // Проверка - в избранном ли
        $isFavorite = $this->checkIsFavorite($product->id);

        // SEO
        $this->view->title = $product->getMetaTitle();
        $this->registerMetaTags([
            'description' => $product->getMetaDescription(),
            'keywords' => $product->name . ', ' . $product->brand->name . ', купить, оригинал',
            'og:title' => $product->getMetaTitle(),
            'og:description' => $product->getMetaDescription(),
            'og:type' => 'product',
            'og:url' => Yii::$app->request->absoluteUrl,
            'og:image' => $product->getMainImageUrl(),
            'product:price:amount' => $product->price,
            'product:price:currency' => 'BYN',
            'twitter:card' => 'summary_large_image',
            'twitter:title' => $product->name,
            'twitter:description' => $product->getMetaDescription(),
            'twitter:image' => $product->getMainImageUrl(),
        ]);

        return $this->render('product', [
            'product' => $product,
            'similarProducts' => $similarProducts,
            'isFavorite' => $isFavorite,
        ]);
    }


    /**
     * Добавить в избранное
     */
    public function actionAddFavorite()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $productId = Yii::$app->request->post('productId');
        
        if (!$productId) {
            return ['success' => false, 'message' => 'ID товара не указан'];
        }

        $userId = Yii::$app->user->id;
        $sessionId = Yii::$app->session->id;

        if (ProductFavorite::add($productId, $userId, $sessionId)) {
            return [
                'success' => true,
                'message' => 'Товар добавлен в избранное',
                'count' => ProductFavorite::getCount($userId, $sessionId),
            ];
        }

        return ['success' => false, 'message' => 'Товар уже в избранном'];
    }

    /**
     * Удалить из избранного
     */
    public function actionRemoveFavorite()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $productId = Yii::$app->request->post('productId');
        
        if (!$productId) {
            return ['success' => false, 'message' => 'ID товара не указан'];
        }

        $userId = Yii::$app->user->id;
        $sessionId = Yii::$app->session->id;

        ProductFavorite::remove($productId, $userId, $sessionId);

        return [
            'success' => true,
            'message' => 'Товар удален из избранного',
            'count' => ProductFavorite::getCount($userId, $sessionId),
        ];
    }

    /**
     * Создание заявки из каталога
     */
    public function actionCreateInquiry()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $inquiry = new CatalogInquiry();
        
        if ($inquiry->load(Yii::$app->request->post(), '') && $inquiry->validate()) {
            if ($inquiry->save()) {
                // Создаем заказ автоматически
                $order = $inquiry->createOrder();
                
                return [
                    'success' => true,
                    'message' => 'Ваша заявка принята! Мы свяжемся с вами в ближайшее время.',
                    'inquiryId' => $inquiry->id,
                    'orderId' => $order ? $order->id : null,
                ];
            }
        }

        return [
            'success' => false,
            'message' => 'Ошибка при создании заявки',
            'errors' => $inquiry->errors,
        ];
    }

    /**
     * Применение фильтров к запросу
     */
    protected function applyFilters($query)
    {
        $request = Yii::$app->request;

        // Фильтр по бренду
        if ($brands = $request->get('brands')) {
            $brandIds = is_array($brands) ? $brands : explode(',', $brands);
            $query->andWhere(['brand_id' => $brandIds]);
        }

        // Фильтр по категории
        if ($categories = $request->get('categories')) {
            $categoryIds = is_array($categories) ? $categories : explode(',', $categories);
            $query->andWhere(['category_id' => $categoryIds]);
        }

        // Фильтр по цене
        if ($priceFrom = $request->get('price_from')) {
            $query->andWhere(['>=', 'price', $priceFrom]);
        }
        if ($priceTo = $request->get('price_to')) {
            $query->andWhere(['<=', 'price', $priceTo]);
        }

        // Фильтр по наличию
        if ($stockStatus = $request->get('stock_status')) {
            $query->andWhere(['stock_status' => $stockStatus]);
        }

        // Поиск
        if ($search = $request->get('search')) {
            $query->andWhere(['like', 'name', $search]);
        }
        
        // Фильтр по размерам
        if ($sizes = $request->get('sizes')) {
            $sizeArray = is_array($sizes) ? $sizes : explode(',', $sizes);
            // TODO: Добавить связь с таблицей размеров
            // $query->joinWith('sizes')->andWhere(['product_size.size' => $sizeArray]);
        }
        
        // Фильтр по цветам
        if ($colors = $request->get('colors')) {
            $colorArray = is_array($colors) ? $colors : explode(',', $colors);
            // TODO: Добавить связь с таблицей цветов
            // $query->joinWith('colors')->andWhere(['product_color.hex' => $colorArray]);
        }
        
        // Фильтр по скидке
        if ($request->get('discount_any')) {
            $query->andWhere(['>', 'old_price', 0]);
        }
        
        if ($discountRange = $request->get('discount_range')) {
            $ranges = is_array($discountRange) ? $discountRange : explode(',', $discountRange);
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
        
        // Фильтр по рейтингу
        if ($rating = $request->get('rating')) {
            $query->andWhere(['>=', 'rating', $rating]);
        }
        
        // Фильтр по условиям
        if ($conditions = $request->get('conditions')) {
            $condArray = is_array($conditions) ? $conditions : explode(',', $conditions);
            foreach ($condArray as $condition) {
                switch ($condition) {
                    case 'new':
                        $query->andWhere(['>=', 'created_at', date('Y-m-d', strtotime('-30 days'))]);
                        break;
                    case 'hit':
                        $query->andWhere(['is_featured' => 1]);
                        break;
                    case 'free_delivery':
                        // TODO: Добавить поле free_delivery
                        break;
                    case 'in_stock':
                        $query->andWhere(['stock_status' => 'in_stock']);
                        break;
                }
            }
        }
        
        // ========== НОВЫЕ ФИЛЬТРЫ ==========
        
        // Фильтр по материалу
        if ($material = $request->get('material')) {
            $materials = is_array($material) ? $material : explode(',', $material);
            $query->andWhere(['material' => $materials]);
        }
        
        // Фильтр по сезону
        if ($season = $request->get('season')) {
            $seasons = is_array($season) ? $season : explode(',', $season);
            $query->andWhere(['season' => $seasons]);
        }
        
        // Фильтр по полу
        if ($gender = $request->get('gender')) {
            $query->andWhere(['gender' => $gender]);
        }
        
        // Фильтр по стилю (many-to-many)
        if ($style = $request->get('style')) {
            $styles = is_array($style) ? $style : explode(',', $style);
            $query->joinWith('styles')
                  ->andWhere(['style.slug' => $styles]);
        }
        
        // Фильтр по технологиям (many-to-many)
        if ($tech = $request->get('tech')) {
            $techs = is_array($tech) ? $tech : explode(',', $tech);
            $query->joinWith('technologies')
                  ->andWhere(['technology.slug' => $techs]);
        }
        
        // Фильтр по высоте
        if ($height = $request->get('height')) {
            $query->andWhere(['height' => $height]);
        }
        
        // Фильтр по застежке
        if ($fastening = $request->get('fastening')) {
            $fastenings = is_array($fastening) ? $fastening : explode(',', $fastening);
            $query->andWhere(['fastening' => $fastenings]);
        }
        
        // Фильтр по стране производства
        if ($country = $request->get('country')) {
            $countries = is_array($country) ? $country : explode(',', $country);
            $query->andWhere(['country' => $countries]);
        }
        
        // Фильтр по акциям
        if ($promo = $request->get('promo')) {
            $promos = is_array($promo) ? $promo : explode(',', $promo);
            foreach ($promos as $p) {
                switch ($p) {
                    case 'sale':
                        $query->andWhere(['>', 'old_price', 0]);
                        break;
                    case 'bonus':
                        $query->andWhere(['has_bonus' => 1]);
                        break;
                    case '2for1':
                        $query->andWhere(['promo_2for1' => 1]);
                        break;
                    case 'exclusive':
                        $query->andWhere(['is_exclusive' => 1]);
                        break;
                }
            }
        }

        // Сортировка
        $sortBy = $request->get('sort', 'popular');
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
                $query->addSelect(['discount_percent' => 'CASE WHEN old_price > 0 THEN ROUND((old_price - price) / old_price * 100) ELSE 0 END']);
                $query->orderBy(['discount_percent' => SORT_DESC]);
                break;
            case 'popular':
            default:
                $query->orderBy(['views_count' => SORT_DESC]);
                break;
        }

        return $query;
    }

    /**
     * Кэшированный COUNT для пагинации (оптимизация)
     */
    protected function getCachedCount($query)
    {
        $filterParams = Yii::$app->request->queryParams;
        $cacheKey = 'catalog_count_' . md5(serialize($filterParams));
        
        return Yii::$app->cache->getOrSet($cacheKey, function() use ($query) {
            return $query->count();
        }, 300); // 5 минут
    }

    /**
     * Получение данных для фильтров с учетом текущих фильтров (УМНЫЙ ФИЛЬТР + КЭШ)
     */
    protected function getFiltersData($baseCondition = [])
    {
        $request = Yii::$app->request;
        $currentFilters = [
            'brands' => $request->get('brands') ? explode(',', $request->get('brands')) : [],
            'categories' => $request->get('categories') ? explode(',', $request->get('categories')) : [],
            'price_from' => $request->get('price_from'),
            'price_to' => $request->get('price_to'),
        ];
        
        // Кэшируем результат
        $cacheKey = 'filters_data_v2_' . md5(serialize([
            'base' => $baseCondition,
            'filters' => $currentFilters
        ]));
        
        return Yii::$app->cache->getOrSet($cacheKey, function() use ($currentFilters, $baseCondition) {
        
        // Бренды с количеством товаров (с учетом других фильтров)
        $brandsQuery = Brand::find()
            ->select(['brand.id', 'brand.name', 'brand.slug', 'COUNT(DISTINCT product.id) as count'])
            ->leftJoin('product', 'product.brand_id = brand.id AND product.is_active = 1')
            ->where(['brand.is_active' => 1]);
        
        // Применяем фильтры кроме брендов
        if (!empty($currentFilters['categories'])) {
            $brandsQuery->andWhere(['product.category_id' => $currentFilters['categories']]);
        }
        if (!empty($currentFilters['price_from'])) {
            $brandsQuery->andWhere(['>=', 'product.price', $currentFilters['price_from']]);
        }
        if (!empty($currentFilters['price_to'])) {
            $brandsQuery->andWhere(['<=', 'product.price', $currentFilters['price_to']]);
        }
        
        $brands = $brandsQuery
            ->groupBy(['brand.id'])
            ->orderBy(['brand.name' => SORT_ASC])
            ->asArray()
            ->all();

        // Категории с количеством товаров (с учетом других фильтров)
        $categoriesQuery = Category::find()
            ->select(['category.id', 'category.name', 'category.slug', 'COUNT(DISTINCT product.id) as count'])
            ->leftJoin('product', 'product.category_id = category.id AND product.is_active = 1')
            ->where(['category.is_active' => 1, 'category.parent_id' => null]);
        
        // Применяем фильтры кроме категорий
        if (!empty($currentFilters['brands'])) {
            $categoriesQuery->andWhere(['product.brand_id' => $currentFilters['brands']]);
        }
        if (!empty($currentFilters['price_from'])) {
            $categoriesQuery->andWhere(['>=', 'product.price', $currentFilters['price_from']]);
        }
        if (!empty($currentFilters['price_to'])) {
            $categoriesQuery->andWhere(['<=', 'product.price', $currentFilters['price_to']]);
        }
        
        $categories = $categoriesQuery
            ->groupBy(['category.id'])
            ->orderBy(['category.name' => SORT_ASC])
            ->asArray()
            ->all();

        // Диапазон цен (с учетом текущих фильтров)
        $priceQuery = Product::find()
            ->select(['MIN(price) as min', 'MAX(price) as max'])
            ->where(['is_active' => 1]);
        
        if (!empty($currentFilters['brands'])) {
            $priceQuery->andWhere(['brand_id' => $currentFilters['brands']]);
        }
        if (!empty($currentFilters['categories'])) {
            $priceQuery->andWhere(['category_id' => $currentFilters['categories']]);
        }
        
        $priceRange = $priceQuery->asArray()->one();

        return [
            'brands' => $brands,
            'categories' => $categories,
            'priceRange' => [
                'min' => (float)($priceRange['min'] ?? 0),
                'max' => (float)($priceRange['max'] ?? 1000),
            ],
        ];
        }, 1800); // 30 минут кэш
    }

    /**
     * Инвалидация кэша фильтров
     */
    public static function invalidateFiltersCache()
    {
        $cache = Yii::$app->cache;
        $pattern = 'catalog_filters_';
        
        // Удаляем все ключи кэша фильтров
        if ($cache instanceof \yii\caching\FileCache) {
            $cachePath = $cache->cachePath;
            $files = glob($cachePath . '/' . $pattern . '*');
            foreach ($files as $file) {
                @unlink($file);
            }
        }
        
        // Альтернатива - flush всего кэша (если используется Redis/Memcached)
        // $cache->flush();
    }

    /**
     * Проверка - в избранном ли товар
     */
    protected function checkIsFavorite($productId)
    {
        $userId = Yii::$app->user->id;
        $sessionId = Yii::$app->session->id;

        return ProductFavorite::find()
            ->where(['product_id' => $productId])
            ->andWhere($userId ? ['user_id' => $userId] : ['session_id' => $sessionId])
            ->exists();
    }

    /**
     * Рендер страницы ошибки
     */
    protected function renderError($statusCode, $message)
    {
        Yii::$app->response->statusCode = $statusCode;
        return $this->render('error', [
            'statusCode' => $statusCode,
            'message' => $message,
        ]);
    }
    
    /**
     * SEF фильтрация (умный фильтр)
     */
    public function actionFilterSef($filters = '')
    {
        // Парсим SEF URL
        $parsedFilters = SmartFilter::parseSefUrl($filters);
        
        // Применяем фильтры
        $query = Product::find()
            ->with(['brand', 'category'])
            ->where(['is_active' => 1]);
        
        $query = $this->applyParsedFilters($query, $parsedFilters);
        
        $totalCount = $query->count();
        
        // Пагинация
        $pagination = new Pagination([
            'defaultPageSize' => 24,
            'totalCount' => $totalCount,
        ]);
        
        $products = $query
            ->offset($pagination->offset)
            ->limit($pagination->limit)
            ->all();
        
        // Получение доступных фильтров (динамическое сужение)
        $availableFilters = $this->getAvailableFilters($parsedFilters);
        
        // Активные фильтры для тегов
        $activeFilters = SmartFilter::formatActiveFilters($parsedFilters);
        
        // Динамический H1
        $h1 = SmartFilter::generateDynamicH1($parsedFilters, $totalCount);
        
        // SEO
        $canonicalUrl = SmartFilter::getCanonicalUrl($parsedFilters, $totalCount);
        $robotsDirective = SmartFilter::getRobotsDirective($totalCount);
        $metaDescription = SmartFilter::generateMetaDescription($parsedFilters, $totalCount);
        
        $this->view->title = $h1 . ' | СНИКЕРХЭД';
        $this->view->registerLinkTag(['rel' => 'canonical', 'href' => $canonicalUrl]);
        $this->view->registerMetaTag(['name' => 'robots', 'content' => $robotsDirective]);
        $this->registerMetaTags([
            'description' => $metaDescription,
            'og:title' => $h1,
            'og:description' => $metaDescription,
            'og:type' => 'website',
            'og:url' => Yii::$app->request->absoluteUrl,
        ]);
        
        // Schema.org ItemList
        $this->registerSchemaItemList($products, $totalCount);
        
        // Pagination links
        if ($pagination->pageCount > 1) {
            $this->registerPaginationLinks($pagination->page + 1, $pagination->pageCount, $parsedFilters);
        }
        
        return $this->render('index', [
            'products' => $products,
            'pagination' => $pagination,
            'filters' => $availableFilters,
            'activeFilters' => $activeFilters,
            'currentFilters' => $parsedFilters,
            'h1' => $h1,
        ]);
    }
    
    /**
     * Применение распарсенных фильтров к запросу
     */
    protected function applyParsedFilters($query, $filters)
    {
        if (!empty($filters['brands'])) {
            $query->andWhere(['brand_id' => $filters['brands']]);
        }
        
        if (!empty($filters['categories'])) {
            $query->andWhere(['category_id' => $filters['categories']]);
        }
        
        if (isset($filters['price_from']) && $filters['price_from'] > 0) {
            $query->andWhere(['>=', 'price', $filters['price_from']]);
        }
        
        if (isset($filters['price_to'])) {
            $query->andWhere(['<=', 'price', $filters['price_to']]);
        }
        
        if (!empty($filters['sizes'])) {
            $query->joinWith('sizes')
                ->andWhere(['product_size.size' => $filters['sizes']])
                ->andWhere(['product_size.is_available' => 1]);
        }
        
        if (!empty($filters['colors'])) {
            $query->joinWith('colors')
                ->andWhere(['product_color.color_name' => $filters['colors']])
                ->andWhere(['product_color.is_available' => 1]);
        }
        
        return $query;
    }
    
    /**
     * Получение доступных фильтров с динамическим сужением
     */
    protected function getAvailableFilters($currentFilters = [])
    {
        $cacheKey = 'available_filters_' . md5(serialize($currentFilters));
        $cacheDuration = 1800; // 30 минут
        
        return Yii::$app->cache->getOrSet($cacheKey, function() use ($currentFilters) {
            $baseQuery = Product::find()->where(['is_active' => 1]);
            
            // Доступные бренды (без учета фильтра по брендам)
            $brandFilters = $currentFilters;
            unset($brandFilters['brands']);
            $brandQuery = clone $baseQuery;
            $brandQuery = $this->applyParsedFilters($brandQuery, $brandFilters);
            
            $availableBrands = Brand::find()
                ->select(['brand.id', 'brand.name', 'brand.slug', 'COUNT(DISTINCT product.id) as count'])
                ->innerJoin('product', 'product.brand_id = brand.id')
                ->where(['brand.is_active' => 1])
                ->andWhere($brandQuery->where)
                ->groupBy('brand.id')
                ->having(['>', 'count', 0])
                ->orderBy(['brand.name' => SORT_ASC])
                ->asArray()
                ->all();
            
            // Доступные категории
            $categoryFilters = $currentFilters;
            unset($categoryFilters['categories']);
            $categoryQuery = clone $baseQuery;
            $categoryQuery = $this->applyParsedFilters($categoryQuery, $categoryFilters);
            
            $availableCategories = Category::find()
                ->select(['category.id', 'category.name', 'category.slug', 'COUNT(DISTINCT product.id) as count'])
                ->innerJoin('product', 'product.category_id = category.id')
                ->where(['category.is_active' => 1])
                ->andWhere($categoryQuery->where)
                ->groupBy('category.id')
                ->having(['>', 'count', 0])
                ->orderBy(['category.name' => SORT_ASC])
                ->asArray()
                ->all();
            
            // Диапазон цен
            $priceQuery = clone $baseQuery;
            $priceQuery = $this->applyParsedFilters($priceQuery, $currentFilters);
            
            $priceRange = $priceQuery
                ->select(['MIN(price) as min', 'MAX(price) as max'])
                ->asArray()
                ->one();
            
            return [
                'brands' => $availableBrands,
                'categories' => $availableCategories,
                'priceRange' => [
                    'min' => (float)($priceRange['min'] ?? 0),
                    'max' => (float)($priceRange['max'] ?? 1000),
                ],
            ];
        }, $cacheDuration);
    }
    
    /**
     * Регистрация Schema.org ItemList
     */
    protected function registerSchemaItemList($products, $totalCount)
    {
        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'ItemList',
            'numberOfItems' => $totalCount,
            'itemListElement' => []
        ];
        
        foreach ($products as $index => $product) {
            $schema['itemListElement'][] = [
                '@type' => 'ListItem',
                'position' => $index + 1,
                'item' => [
                    '@type' => 'Product',
                    'name' => $product->name,
                    'url' => Yii::$app->request->hostInfo . $product->getUrl(),
                    'image' => Yii::$app->request->hostInfo . $product->getMainImageUrl(),
                    'brand' => [
                        '@type' => 'Brand',
                        'name' => $product->brand->name
                    ],
                    'offers' => [
                        '@type' => 'Offer',
                        'price' => $product->price,
                        'priceCurrency' => 'BYN',
                        'availability' => $product->stock_status === 'in_stock' 
                            ? 'https://schema.org/InStock' 
                            : 'https://schema.org/OutOfStock',
                        'url' => Yii::$app->request->hostInfo . $product->getUrl()
                    ]
                ]
            ];
        }
        
        $this->view->registerMetaTag([
            'name' => 'application/ld+json',
            'content' => json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)
        ], 'schema-itemlist');
    }
    
    /**
     * Регистрация rel prev/next для пагинации
     */
    protected function registerPaginationLinks($currentPage, $totalPages, $filters)
    {
        $baseUrl = SmartFilter::generateSefUrl($filters);
        
        if ($currentPage > 1) {
            $prevUrl = $baseUrl . '?page=' . ($currentPage - 1);
            $this->view->registerLinkTag([
                'rel' => 'prev',
                'href' => Yii::$app->request->hostInfo . $prevUrl
            ]);
        }
        
        if ($currentPage < $totalPages) {
            $nextUrl = $baseUrl . '?page=' . ($currentPage + 1);
            $this->view->registerLinkTag([
                'rel' => 'next',
                'href' => Yii::$app->request->hostInfo . $nextUrl
            ]);
        }
    }
    
    /**
     * AJAX фильтрация товаров (без перезагрузки)
     */
    public function actionFilter()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $request = Yii::$app->request;
        
        // Применяем фильтры
        $query = Product::find()
            ->with(['brand', 'category', 'colors', 'sizes'])
            ->where(['is_active' => 1]);
        
        $query = $this->applyFilters($query);
        
        // Пагинация
        $page = $request->get('page', 1);
        $perPage = 24;
        
        $pagination = new Pagination([
            'defaultPageSize' => $perPage,
            'totalCount' => $query->count(),
            'page' => $page - 1,
        ]);
        
        $products = $query
            ->offset($pagination->offset)
            ->limit($pagination->limit)
            ->all();
        
        // Текущие фильтры
        $currentFilters = [
            'brands' => $request->get('brands') ? explode(',', $request->get('brands')) : [],
            'categories' => $request->get('categories') ? explode(',', $request->get('categories')) : [],
            'price_from' => $request->get('price_from'),
            'price_to' => $request->get('price_to'),
        ];
        
        // Обновленные данные фильтров (умное сужение)
        $filters = $this->getFiltersData();
        
        // Рендерим только список товаров
        $html = $this->renderPartial('_products', [
            'products' => $products,
        ]);
        
        return [
            'success' => true,
            'html' => $html,
            'filters' => $filters,
            'totalCount' => $pagination->totalCount,
            'currentPage' => $page,
            'totalPages' => $pagination->pageCount,
        ];
    }
    
    /**
     * Quick View - API для модального окна
     */
    public function actionProductQuick($id)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        
        $product = Product::find()
            ->with(['brand', 'category', 'images', 'sizes'])
            ->where(['id' => $id, 'is_active' => 1])
            ->one();
        
        if (!$product) {
            return ['error' => 'Product not found'];
        }
        
        $images = [$product->getMainImageUrl()];
        foreach ($product->images as $img) {
            $images[] = $img->getUrl();
        }
        
        $sizes = [];
        if ($product->sizes) {
            foreach ($product->sizes as $size) {
                if ($size->is_available) {
                    $sizes[] = $size->size;
                }
            }
        }
        
        $priceHtml = '';
        if ($product->hasDiscount()) {
            $priceHtml .= '<span style="font-size:1rem;color:#9ca3af;text-decoration:line-through;margin-right:0.5rem">' . 
                         Yii::$app->formatter->asCurrency($product->old_price, 'BYN') . '</span>';
            $priceHtml .= '<span style="background:#ef4444;color:#fff;padding:0.25rem 0.5rem;border-radius:4px;font-size:0.75rem;font-weight:700;margin-right:0.5rem">-' . 
                         $product->getDiscountPercent() . '%</span>';
        }
        $priceHtml .= '<span style="font-size:1.75rem;font-weight:900;color:#000">' . 
                     Yii::$app->formatter->asCurrency($product->price, 'BYN') . '</span>';
        
        return [
            'image' => $product->getMainImageUrl(),
            'images' => $images,
            'brand' => $product->brand->name,
            'name' => $product->name,
            'price' => $priceHtml,
            'sizes' => $sizes,
            'url' => $product->getUrl(),
        ];
    }

    /**
     * Получить товары по списку IDs (для истории просмотров)
     */
    public function actionProductsByIds()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $ids = Yii::$app->request->get('ids');
        if (!$ids) {
            return [];
        }

        // Преобразуем в массив
        $ids = is_array($ids) ? $ids : explode(',', $ids);
        $ids = array_filter(array_map('intval', $ids));

        if (empty($ids)) {
            return [];
        }

        // Получаем товары
        $products = Product::find()
            ->with(['brand'])
            ->where(['id' => $ids, 'is_active' => 1])
            ->limit(20)
            ->all();

        // Форматируем для фронтенда
        $result = [];
        foreach ($products as $product) {
            $result[] = [
                'id' => $product->id,
                'name' => $product->name,
                'brand' => $product->brand->name,
                'price' => Yii::$app->formatter->asCurrency($product->price, 'BYN'),
                'image' => $product->getMainImageUrl(),
                'url' => $product->getUrl(),
            ];
        }

        return $result;
    }

    /**
     * Получить список брендов с количеством товаров
     */
    public function actionGetBrands()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $brands = Brand::find()
            ->select(['brand.id', 'brand.name', 'brand.slug', 'COUNT(product.id) as products_count'])
            ->leftJoin('product', 'product.brand_id = brand.id AND product.is_active = 1')
            ->groupBy(['brand.id', 'brand.name', 'brand.slug'])
            ->having('COUNT(product.id) > 0')
            ->orderBy(['products_count' => SORT_DESC, 'brand.name' => SORT_ASC])
            ->asArray()
            ->all();

        return $brands;
    }

    /**
     * Загрузка дополнительных товаров (для infinite scroll)
     */
    public function actionLoadMore()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        
        $request = Yii::$app->request;
        
        // Получаем номер страницы
        $page = (int)$request->get('page', 1);
        $perPage = 24;
        
        // Строим query с фильтрами
        $query = Product::find()
            ->with([
                'brand', 
                'category', 
                'images' => function($q) {
                    $q->where(['is_main' => 1])->orWhere(['sort_order' => 0])->limit(1);
                },
                'colors', 
                'sizes'
            ])
            ->where(['is_active' => 1]);

        // Применяем фильтры
        $query = $this->applyFilters($query);
        
        // Подсчитываем общее количество
        $totalCount = $query->count();
        $totalPages = ceil($totalCount / $perPage);
        
        // Получаем товары для текущей страницы
        $products = $query
            ->offset(($page - 1) * $perPage)
            ->limit($perPage)
            ->all();
        
        // Рендерим HTML товаров
        $html = '';
        if (!empty($products)) {
            $html = $this->renderPartial('_products', ['products' => $products]);
        }
        
        return [
            'success' => true,
            'html' => $html,
            'hasMore' => $page < $totalPages,
            'currentPage' => $page,
            'totalPages' => $totalPages,
            'totalCount' => $totalCount,
        ];
    }

    /**
     * История просмотров
     */
    public function actionHistory()
    {
        return $this->render('history');
    }
}
