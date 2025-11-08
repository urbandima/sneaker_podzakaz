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
use app\components\CacheManager;
use app\components\HttpCacheHeaders;

/**
 * Контроллер каталога товаров
 */
class CatalogController extends Controller
{
    public $layout = 'public';
    
    /**
     * Behaviors для HTTP кэширования
     */
    public function behaviors()
    {
        return array_merge(parent::behaviors(), [
            'httpCache' => [
                'class' => 'yii\filters\HttpCache',
                'only' => ['index', 'brand', 'category', 'product'],
                'lastModified' => function ($action, $params) {
                    // Время последнего изменения товаров
                    if ($action->id === 'product') {
                        $product = $this->findProduct(Yii::$app->request->get('slug'));
                        return $product ? $product->updated_at : time();
                    }
                    return CacheManager::get('catalog_last_modified') ?: time();
                },
                'etagSeed' => function ($action, $params) {
                    // Генерация ETag на основе параметров
                    return serialize([
                        'action' => $action->id,
                        'params' => Yii::$app->request->queryParams,
                        'user' => Yii::$app->user->id,
                    ]);
                },
            ],
        ]);
    }
    
    /**
     * Найти товар для behaviors
     */
    protected function findProduct($slug)
    {
        static $product = null;
        if ($product === null) {
            $product = Product::find()->where(['slug' => $slug])->one();
        }
        return $product;
    }

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
        // Установка HTTP Cache headers
        HttpCacheHeaders::setCatalogHeaders(Yii::$app->response);
        
        $query = $this->buildProductQuery();
        
        return $this->renderCatalogPage(
            query: $query,
            h1: 'Каталог товаров',
            metaTags: [
                'title' => 'Каталог товаров - Оригинальные кроссовки и одежда | СНИКЕРХЭД',
                'keywords' => 'купить кроссовки, оригинальная обувь, nike, adidas, интернет-магазин',
                'og:title' => 'Каталог товаров - СНИКЕРХЭД',
                'og:description' => 'Оригинальные товары из США и Европы',
                'og:type' => 'website',
                'og:url' => Yii::$app->request->absoluteUrl,
            ]
        );
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
     * ИСПРАВЛЕНО: формат данных соответствует ожиданиям фронтенда
     */
    public function actionSearch()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        
        // Кэшируемый API endpoint
        HttpCacheHeaders::setApiHeaders(Yii::$app->response, true, 300);
        
        $query = Yii::$app->request->get('q');
        
        if (!$query || mb_strlen($query) < 2) {
            return ['results' => []];
        }
        
        $products = Product::find()
            ->select(['id', 'name', 'slug', 'price', 'old_price', 'main_image', 'stock_status', 'is_featured'])
            ->with(['brand'])
            ->where(['is_active' => 1])
            ->andWhere(['!=', 'stock_status', Product::STOCK_OUT_OF_STOCK]) // Скрываем "нет в наличии"
            ->andWhere(['like', 'name', $query])
            ->limit(5)
            ->all();
        
        $results = [];
        foreach ($products as $product) {
            $results[] = [
                'id' => $product->id,
                'name' => $product->name,
                'slug' => $product->slug,
                'brand' => [
                    'name' => $product->brand->name ?? '',
                ],
                'price' => $product->price,
                'oldPrice' => $product->old_price,
                'discount' => $product->getDiscountPercent(),
                'url' => '/catalog/product/' . $product->slug,
                'mainImage' => $product->getMainImageUrl(),
                'stockStatus' => $product->stock_status ?? 'out_of_stock',
                'isFeatured' => (bool)$product->is_featured,
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
     * Построение базового запроса для товаров (DRY принцип)
     * ОПТИМИЗИРОВАНО: Eager loading для устранения N+1 запросов
     * 
     * @param array $whereConditions Дополнительные условия WHERE (например, ['brand_id' => 5])
     * @return \yii\db\ActiveQuery
     */
    protected function buildProductQuery(array $whereConditions = [])
    {
        $query = Product::find()
            ->with([
                // ОПТИМИЗАЦИЯ: Загружаем sizes для диапазона цен и отображения в карточках
                'sizes' => function($query) {
                    $query->select(['id', 'product_id', 'size', 'price_byn', 'is_available', 'eu_size', 'us_size', 'uk_size', 'cm_size'])
                          ->where(['is_available' => 1])
                          ->orderBy(['size' => SORT_ASC]);
                },
                // ОПТИМИЗАЦИЯ: Загружаем colors для отображения в карточках
                'colors' => function($query) {
                    $query->select(['id', 'product_id', 'name', 'hex']);
                },
                // ОПТИМИЗАЦИЯ: Загружаем первые 2 изображения для hover-эффекта (устраняет N+1)
                'images' => function($query) {
                    $query->select(['id', 'product_id', 'image', 'is_main', 'sort_order'])
                          ->orderBy(['is_main' => SORT_DESC, 'sort_order' => SORT_ASC])
                          ->limit(2);
                }
            ])
            ->select([
                'id', 
                'name', 
                'slug', 
                'brand_id',        // Для связи with(['brand']) если понадобится
                'brand_name',      // Денормализованное поле (устраняет N+1)
                'category_name',   // Денормализованное поле
                'main_image_url',  // Денормализованное поле
                'price', 
                'old_price', 
                'stock_status',
                'is_featured',
                'rating',
                'reviews_count',
                'created_at'       // Для бейджа "NEW"
            ])
            ->where(['is_active' => 1])
            ->andWhere(['!=', 'stock_status', Product::STOCK_OUT_OF_STOCK]); // Скрываем "нет в наличии"
        
        // Применяем дополнительные условия (brand_id, category_id и т.д.)
        if (!empty($whereConditions)) {
            $query->andWhere($whereConditions);
        }
        
        return $query;
    }
    
    /**
     * Универсальный метод рендеринга страницы каталога (DRY принцип)
     * Устраняет дублирование кода в actionIndex, actionBrand, actionCategory
     * 
     * @param \yii\db\ActiveQuery $query Запрос товаров
     * @param string $h1 Заголовок H1 страницы
     * @param array $metaTags SEO мета-теги
     * @param array $filterConditions Условия для фильтров (например, ['brand_id' => 5])
     * @return string
     */
    protected function renderCatalogPage($query, string $h1, array $metaTags = [], array $filterConditions = [])
    {
        // Применяем фильтры пользователя
        $query = $this->applyFilters($query);
        
        // Пагинация с кэшированным COUNT
        $pagination = new Pagination([
            'defaultPageSize' => 24,
            'totalCount' => $this->getCachedCount($query),
        ]);
        
        // Получаем товары
        $products = $query
            ->offset($pagination->offset)
            ->limit($pagination->limit)
            ->all();
        
        // ДИАГНОСТИКА: Логируем количество товаров
        if (YII_ENV_DEV) {
            \Yii::info(sprintf(
                'Catalog: loaded %d products (offset: %d, limit: %d, total: %d)',
                count($products),
                $pagination->offset,
                $pagination->limit,
                $pagination->totalCount
            ), 'catalog_performance');
        }
        
        // Получаем данные для фильтров
        $filters = $this->getFiltersData($filterConditions);
        
        // Устанавливаем SEO meta-теги
        if (isset($metaTags['title'])) {
            $this->view->title = $metaTags['title'];
        }
        
        // Регистрируем остальные мета-теги
        $this->registerMetaTags($metaTags);
        
        // Получаем текущие фильтры из запроса
        $request = Yii::$app->request;
        $currentFilters = [
            'brands' => $request->get('brands') ? explode(',', $request->get('brands')) : [],
            'categories' => $request->get('categories') ? explode(',', $request->get('categories')) : [],
            'price_from' => $request->get('price_from'),
            'price_to' => $request->get('price_to'),
        ];
        
        // Формируем активные фильтры для отображения тегов
        $activeFilters = $this->getActiveFilters($currentFilters);
        
        // Получаем текущую систему размеров из запроса
        $currentSizeSystem = $request->get('size_system', 'eu');
        
        // Рендерим view
        return $this->render('index', [
            'products' => $products,
            'pagination' => $pagination,
            'h1' => $h1,
            'filters' => $filters,
            'currentFilters' => $currentFilters,
            'activeFilters' => $activeFilters,
            'currentSizeSystem' => $currentSizeSystem,
        ]);
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

        $query = $this->buildProductQuery(['brand_id' => $brand->id]);
        
        $metaTags = [
            'title' => $brand->getMetaTitle(),
            'description' => $brand->getMetaDescription(),
            'keywords' => $brand->name . ', оригинальные товары, купить',
            'og:title' => $brand->getMetaTitle(),
            'og:description' => $brand->getMetaDescription(),
            'og:type' => 'website',
            'og:url' => Yii::$app->request->absoluteUrl,
        ];
        
        if ($brand->logo) {
            $metaTags['og:image'] = Yii::$app->request->hostInfo . $brand->logo;
        }
        
        return $this->renderCatalogPage(
            query: $query,
            h1: $brand->name,
            metaTags: $metaTags,
            filterConditions: ['brand_id' => $brand->id]
        );
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

        $query = $this->buildProductQuery(['category_id' => $categoryIds]);
        
        return $this->renderCatalogPage(
            query: $query,
            h1: $category->name,
            metaTags: [
                'title' => $category->getMetaTitle(),
                'description' => $category->getMetaDescription(),
                'keywords' => $category->name . ', купить, оригинал',
                'og:title' => $category->getMetaTitle(),
                'og:description' => $category->getMetaDescription(),
                'og:type' => 'website',
                'og:url' => Yii::$app->request->absoluteUrl,
            ],
            filterConditions: ['category_id' => $categoryIds]
        );
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
        
        // Установка HTTP Cache headers для страницы товара
        HttpCacheHeaders::setProductHeaders(
            Yii::$app->response,
            $product->id,
            $product->updated_at
        );

        // Увеличиваем счетчик просмотров
        $product->incrementViews();

        // Похожие товары - увеличиваем лимит для показа большего количества
        $similarProducts = $product->getSimilarProducts(12);

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

        $userId = Yii::$app->user->isGuest ? null : Yii::$app->user->id;
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

        $userId = Yii::$app->user->isGuest ? null : Yii::$app->user->id;
        $sessionId = Yii::$app->session->id;

        ProductFavorite::remove($productId, $userId, $sessionId);

        return [
            'success' => true,
            'message' => 'Товар удален из избранного',
            'count' => ProductFavorite::getCount($userId, $sessionId),
        ];
    }

    /**
     * Получить количество избранных товаров (AJAX)
     * ДОБАВЛЕНО: для корректной работы счетчика
     */
    public function actionFavoritesCount()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $userId = Yii::$app->user->isGuest ? null : Yii::$app->user->id;
        $sessionId = Yii::$app->session->id;

        return [
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
        
        // Фильтр по размерам с учетом выбранной системы измерения
        if ($sizes = $request->get('sizes')) {
            $sizeArray = is_array($sizes) ? $sizes : explode(',', $sizes);
            $sizeSystem = $request->get('size_system', 'eu'); // Получаем систему измерения (по умолчанию EU)
            
            // Определяем поле для фильтрации в зависимости от системы
            $sizeField = match($sizeSystem) {
                'us' => 'us_size',
                'uk' => 'uk_size',
                'cm' => 'cm_size',
                default => 'eu_size'
            };
            
            // ИСПРАВЛЕНО: Используем подзапрос вместо JOIN, чтобы избежать дубликатов
            $query->andWhere([
                'id' => \app\models\ProductSize::find()
                    ->select('product_id')
                    ->where([$sizeField => $sizeArray])
                    ->andWhere(['is_available' => 1])
            ]);
        }
        
        // Фильтр по цветам
        // ПРИМЕЧАНИЕ: Требует создания связи many-to-many с таблицей product_colors
        // Реализация будет добавлена после миграции схемы БД для цветов
        /*
        if ($colors = $request->get('colors')) {
            $colorArray = is_array($colors) ? $colors : explode(',', $colors);
            $query->joinWith('colors')->andWhere(['product_color.hex' => $colorArray]);
        }
        */
        
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
                        // ПРИМЕЧАНИЕ: Требует добавления поля free_delivery в таблицу products
                        // $query->andWhere(['free_delivery' => 1]);
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
     * ОПТИМИЗИРОВАНО: Используем CacheManager с тегами
     */
    protected function getCachedCount($query)
    {
        $filterParams = Yii::$app->request->queryParams;
        
        return CacheManager::getCatalogCount($filterParams, function() use ($query) {
            return $query->count();
        });
    }

    /**
     * Получение данных для фильтров с учетом текущих фильтров (УМНЫЙ ФИЛЬТР + КЭШ)
     * ОПТИМИЗИРОВАНО: Используем CacheManager с тегами для инвалидации
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
        
        // Кэшируем результат через CacheManager
        $params = [
            'base' => $baseCondition,
            'filters' => $currentFilters
        ];
        
        return CacheManager::getFiltersData($params, function() use ($currentFilters, $baseCondition) {
        
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
            ->where(['is_active' => 1])
            ->andWhere(['!=', 'stock_status', Product::STOCK_OUT_OF_STOCK]); // Скрываем "нет в наличии"
        
        if (!empty($currentFilters['brands'])) {
            $priceQuery->andWhere(['brand_id' => $currentFilters['brands']]);
        }
        if (!empty($currentFilters['categories'])) {
            $priceQuery->andWhere(['category_id' => $currentFilters['categories']]);
        }
        
        $priceRange = $priceQuery->asArray()->one();

        // Получаем доступные размеры
        $sizes = $this->getAvailableSizes($currentFilters);
        
        return [
            'brands' => $brands,
            'categories' => $categories,
            'sizes' => $sizes,
            'priceRange' => [
                'min' => (float)($priceRange['min'] ?? 0),
                'max' => (float)($priceRange['max'] ?? 1000),
            ],
        ];
        });
    }

    /**
     * Инвалидация кэша фильтров
     * ОПТИМИЗИРОВАНО: Используем CacheManager с tagged caching
     */
    public static function invalidateFiltersCache()
    {
        CacheManager::invalidateFilters();
    }
    
    /**
     * Инвалидация всего кэша каталога
     */
    public static function invalidateCatalogCache()
    {
        CacheManager::invalidateCatalog();
    }

    /**
     * Проверка - в избранном ли товар
     */
    protected function checkIsFavorite($productId)
    {
        $userId = Yii::$app->user->isGuest ? null : Yii::$app->user->id;
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
            ->where(['is_active' => 1])
            ->andWhere(['!=', 'stock_status', Product::STOCK_OUT_OF_STOCK]); // Скрываем "нет в наличии"
        
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
            $baseQuery = Product::find()
                ->where(['is_active' => 1])
                ->andWhere(['!=', 'stock_status', Product::STOCK_OUT_OF_STOCK]); // Скрываем "нет в наличии"
            
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
     * Получение всех доступных размеров по всем системам измерения
     * 
     * @param array $currentFilters Текущие фильтры для умного сужения
     * @return array Массив доступных размеров с количеством товаров для каждой системы
     */
    protected function getAvailableSizes($currentFilters = [])
    {
        // Кэшируем результат
        $cacheKey = 'available_sizes_all_' . md5(serialize($currentFilters));
        
        return Yii::$app->cache->getOrSet($cacheKey, function() use ($currentFilters) {
            $result = [
                'eu' => [],
                'us' => [],
                'uk' => [],
                'cm' => []
            ];
            
            // Базовый запрос
            $baseQuery = \app\models\ProductSize::find()
                ->innerJoin('product', 'product.id = product_size.product_id')
                ->where([
                    'product.is_active' => 1,
                    'product_size.is_available' => 1
                ])
                ->andWhere(['!=', 'product.stock_status', Product::STOCK_OUT_OF_STOCK]);
            
            // Применяем текущие фильтры (умное сужение)
            if (!empty($currentFilters['brands'])) {
                $baseQuery->andWhere(['product.brand_id' => $currentFilters['brands']]);
            }
            if (!empty($currentFilters['categories'])) {
                $baseQuery->andWhere(['product.category_id' => $currentFilters['categories']]);
            }
            if (!empty($currentFilters['price_from'])) {
                $baseQuery->andWhere(['>=', 'product.price', $currentFilters['price_from']]);
            }
            if (!empty($currentFilters['price_to'])) {
                $baseQuery->andWhere(['<=', 'product.price', $currentFilters['price_to']]);
            }
            
            // Получаем размеры EU
            $euQuery = clone $baseQuery;
            $result['eu'] = $euQuery
                ->select([
                    'product_size.eu_size as size',
                    'COUNT(DISTINCT product_size.product_id) as count'
                ])
                ->andWhere(['IS NOT', 'product_size.eu_size', null])
                ->groupBy(['product_size.eu_size'])
                ->having(['>', 'count', 0])
                ->asArray()
                ->all();
            
            // Получаем размеры US
            $usQuery = clone $baseQuery;
            $result['us'] = $usQuery
                ->select([
                    'product_size.us_size as size',
                    'COUNT(DISTINCT product_size.product_id) as count'
                ])
                ->andWhere(['IS NOT', 'product_size.us_size', null])
                ->groupBy(['product_size.us_size'])
                ->having(['>', 'count', 0])
                ->asArray()
                ->all();
            
            // Получаем размеры UK
            $ukQuery = clone $baseQuery;
            $result['uk'] = $ukQuery
                ->select([
                    'product_size.uk_size as size',
                    'COUNT(DISTINCT product_size.product_id) as count'
                ])
                ->andWhere(['IS NOT', 'product_size.uk_size', null])
                ->groupBy(['product_size.uk_size'])
                ->having(['>', 'count', 0])
                ->asArray()
                ->all();
            
            // Получаем размеры CM
            $cmQuery = clone $baseQuery;
            $result['cm'] = $cmQuery
                ->select([
                    'product_size.cm_size as size',
                    'COUNT(DISTINCT product_size.product_id) as count'
                ])
                ->andWhere(['IS NOT', 'product_size.cm_size', null])
                // ИСПРАВЛЕНИЕ: Фильтруем некорректные размеры (нормальный диапазон 20-35 см)
                ->andWhere(['>=', 'product_size.cm_size', 20])
                ->andWhere(['<=', 'product_size.cm_size', 35])
                ->groupBy(['product_size.cm_size'])
                ->having(['>', 'count', 0])
                ->asArray()
                ->all();
            
            // Сортируем размеры численно для каждой системы
            foreach ($result as $system => &$sizes) {
                usort($sizes, function($a, $b) {
                    return (float)$a['size'] - (float)$b['size'];
                });
            }
            
            return $result;
        }, 1800); // 30 минут кэш
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
     * ИСПРАВЛЕНО: Читаем параметры из POST вместо GET
     */
    public function actionFilter()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $request = Yii::$app->request;
        
        // ИСПРАВЛЕНО: Временно сохраняем POST параметры в $_GET для applyFilters()
        // Это нужно, потому что applyFilters() использует $request->get()
        $brands = $request->post('brands');
        $categories = $request->post('categories');
        $sizes = $request->post('sizes');
        $sizeSystem = $request->post('sizeSystem', 'eu');
        $priceFrom = $request->post('price_from');
        $priceTo = $request->post('price_to');
        $sort = $request->post('sort', 'popular');
        $page = (int)$request->post('page', 1);
        $perPage = (int)$request->post('perPage', 24);
        
        // Декодируем JSON параметры
        if ($brands && is_string($brands)) {
            $brands = json_decode($brands, true);
        }
        if ($categories && is_string($categories)) {
            $categories = json_decode($categories, true);
        }
        if ($sizes && is_string($sizes)) {
            $sizes = json_decode($sizes, true);
        }
        
        // Временно добавляем в GET для совместимости с applyFilters()
        $_GET['brands'] = $brands;
        $_GET['categories'] = $categories;
        $_GET['sizes'] = $sizes;
        $_GET['size_system'] = $sizeSystem;
        $_GET['price_from'] = $priceFrom;
        $_GET['price_to'] = $priceTo;
        $_GET['sort'] = $sort;
        
        // Применяем фильтры (ОПТИМИЗИРОВАНО: только нужные поля)
        $query = Product::find()
            ->select([
                'id', 'name', 'slug', 'brand_id', 'brand_name', 'category_name',
                'main_image_url', 'price', 'old_price', 'stock_status',
                'is_featured', 'rating', 'reviews_count', 'views_count', 'created_at'
            ])
            ->where(['is_active' => 1])
            ->andWhere(['!=', 'stock_status', Product::STOCK_OUT_OF_STOCK]);
        
        $query = $this->applyFilters($query);
        
        // Применяем сортировку
        switch ($sort) {
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
                break;
        }
        
        // Пагинация (ОПТИМИЗИРОВАНО: count без лишних данных)
        $countQuery = clone $query;
        $totalCount = $countQuery->count();
        
        $pagination = new Pagination([
            'defaultPageSize' => $perPage,
            'totalCount' => $totalCount,
            'page' => $page - 1,
        ]);
        
        // ИСПРАВЛЕНО: Убрали asArray() - view ожидает объекты
        $products = $query
            ->with(['brand' => function($q) {
                $q->select(['id', 'name', 'slug']);
            }])
            ->offset($pagination->offset)
            ->limit($pagination->limit)
            ->all();
        
        // Текущие фильтры
        $currentFilters = [
            'brands' => $brands ?: [],
            'categories' => $categories ?: [],
            'sizes' => $sizes ?: [],
            'size_system' => $sizeSystem,
            'price_from' => $priceFrom,
            'price_to' => $priceTo,
        ];
        
        // Обновленные данные фильтров (умное сужение)
        $filters = $this->getFiltersData();
        
        // Рендерим только список товаров
        $html = $this->renderPartial('_products', [
            'products' => $products,
        ]);
        
        return [
            'success' => true,
            'products' => array_map(function($product) {
                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'slug' => $product->slug,
                    'price' => $product->price,
                    'brand' => $product->brand_name,
                ];
            }, $products),
            'html' => $html,
            'filters' => $filters,
            'pagination' => [
                'total' => $pagination->totalCount,
                'currentPage' => $page,
                'totalPages' => $pagination->pageCount,
                'perPage' => $perPage,
            ],
        ];
    }
    
    /**
     * УДАЛЕНО (Проблема #17): Дублирующий QuickView API
     * Используйте actionQuickView() вместо этого
     * 
     * Если нужен JSON вместо HTML - можно расширить actionQuickView
     */

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
            ->andWhere(['!=', 'stock_status', Product::STOCK_OUT_OF_STOCK]) // Скрываем "нет в наличии"
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
            ->where(['is_active' => 1])
            ->andWhere(['!=', 'stock_status', Product::STOCK_OUT_OF_STOCK]); // Скрываем "нет в наличии"

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

    /**
     * Быстрый заказ в 1 клик
     * Принимает JSON из fetch() запроса
     */
    public function actionQuickOrder()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        if (!Yii::$app->request->isPost) {
            return ['success' => false, 'message' => 'Метод не поддерживается'];
        }

        // Читаем JSON из body (fetch отправляет JSON, а не form-data)
        $rawBody = Yii::$app->request->getRawBody();
        $data = json_decode($rawBody, true);
        
        // Если JSON не распарсился, попробуем form-data (обратная совместимость)
        if (!$data) {
            $data = Yii::$app->request->post();
        }
        
        // Валидация данных
        if (empty($data['product_id']) || empty($data['name']) || empty($data['phone'])) {
            return [
                'success' => false,
                'message' => 'Пожалуйста, заполните все обязательные поля'
            ];
        }

        // Проверяем существование товара
        $product = Product::findOne($data['product_id']);
        if (!$product) {
            return ['success' => false, 'message' => 'Товар не найден'];
        }

        // Получаем информацию о размере если указан
        $sizeInfo = '';
        if (!empty($data['size'])) {
            $sizeInfo = " (Размер: {$data['size']})";
        }

        // Формируем данные для отправки менеджеру
        $message = "📱 БЫСТРЫЙ ЗАКАЗ\n\n";
        $message .= "👤 Клиент: " . $data['name'] . "\n";
        $message .= "📞 Телефон: " . $data['phone'] . "\n\n";
        $message .= "🛍 Товар: " . $product->brand_name . ' ' . $product->name . $sizeInfo . "\n";
        $message .= "💰 Цена: " . Yii::$app->formatter->asCurrency($product->price, 'BYN') . "\n";
        
        if (!empty($data['comment'])) {
            $message .= "\n💬 Комментарий: " . $data['comment'] . "\n";
        }

        $message .= "\n🔗 Ссылка: " . \yii\helpers\Url::to(['catalog/product', 'slug' => $product->slug], true);

        // Отправляем уведомление менеджеру через email
        try {
            Yii::$app->mailer->compose()
                ->setFrom([Yii::$app->params['senderEmail'] => Yii::$app->params['senderName']])
                ->setTo(Yii::$app->params['adminEmail'])
                ->setSubject('⚡ Быстрый заказ: ' . $product->name)
                ->setTextBody($message)
                ->send();

            // Можно также отправить в Telegram если настроено
            // $this->sendToTelegram($message);

            return [
                'success' => true,
                'message' => 'Заказ оформлен! Менеджер свяжется с вами в ближайшее время.'
            ];
        } catch (\Exception $e) {
            Yii::error('Quick order email error: ' . $e->getMessage(), __METHOD__);
            return [
                'success' => false,
                'message' => 'Произошла ошибка при отправке заказа. Пожалуйста, позвоните нам.'
            ];
        }
    }

    /**
     * Отправка в Telegram (опционально)
     */
    private function sendToTelegram($message)
    {
        // Если настроен Telegram bot token и chat_id
        $botToken = Yii::$app->params['telegramBotToken'] ?? null;
        $chatId = Yii::$app->params['telegramChatId'] ?? null;

        if ($botToken && $chatId) {
            $url = "https://api.telegram.org/bot{$botToken}/sendMessage";
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
                'chat_id' => $chatId,
                'text' => $message,
                'parse_mode' => 'HTML'
            ]));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 5);
            curl_exec($ch);
            curl_close($ch);
        }
    }
}
