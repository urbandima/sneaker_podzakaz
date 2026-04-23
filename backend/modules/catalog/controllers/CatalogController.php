<?php

/**
 * CatalogController — Контроллер каталога товаров
 * 
 * НАЗНАЧЕНИЕ:
 * Основной контроллер для отображения каталога товаров, карточек товаров,
 * фильтрации, поиска и работы с избранным.
 * 
 * ФУНКЦИИ:
 * - Каталог товаров с пагинацией (index)
 * - Страница бренда со всеми товарами бренда (brand)
 * - Страница категории со всеми товарами категории (category)
 * - Карточка товара (product)
 * - Live-поиск товаров (search)
 * - Быстрый просмотр товара в модальном окне (quick-view)
 * - Страница всех брендов (brands)
 * - Избранные товары (favorites)
 * - Добавление/удаление из избранного (add-favorite, remove-favorite)
 * - Запрос по товару (inquiry)
 * 
 * СВЯЗИ:
 * - Product (модель товара)
 * - Category (модель категории)
 * - Brand (модель бренда)
 * - ProductFavorite (модель избранного)
 * - CatalogFiltersTrait (трейт фильтрации)
 * - CatalogSeoTrait (трейт SEO)
 * - ProductRepository (репозиторий товаров)
 * - FilterBuilder (построитель фильтров)
 * 
 * ОПТИМИЗАЦИИ:
 * - HTTP-кэширование через ETag и Last-Modified
 * - Eager loading для устранения N+1 запросов
 * - Денормализованные поля в таблице products (brand_name, category_name)
 * - Кэширование COUNT запросов
 * 
 * РЕФАКТОРИНГ 2025:
 * - Фильтры вынесены в CatalogFiltersTrait
 * - SEO методы вынесены в CatalogSeoTrait
 * - Удалены дублирующие методы (~400 строк)
 */
namespace app\backend\modules\catalog\controllers;

use Yii;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\web\Response;
use yii\data\Pagination;
use yii\caching\TagDependency;
use app\backend\modules\catalog\models\Product;
use app\backend\modules\catalog\models\Category;
use app\backend\modules\catalog\models\Brand;
use app\backend\modules\catalog\models\ProductFavorite;
use app\backend\modules\catalog\models\CatalogInquiry;
use app\backend\shared\components\SmartFilter;
use app\backend\shared\components\CacheManager;
use app\backend\shared\components\HttpCacheHeaders;
use app\backend\modules\catalog\repositories\ProductRepository;
use app\backend\modules\account\models\Customer;
use app\backend\modules\catalog\services\Catalog\FilterBuilder;
use app\backend\shared\traits\CatalogFiltersTrait;
use app\backend\shared\traits\CatalogSeoTrait;

class CatalogController extends Controller
{
    use CatalogFiltersTrait;
    use CatalogSeoTrait;
    
    public $layout = 'main'; // Единый layout frontend
    
    /** @var ProductRepository */
    private $productRepository;
    
    /**
     * Инициализация контроллера
     */
    public function init()
    {
        parent::init();
        $this->productRepository = new ProductRepository();
    }
    
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
     * Найти товар для behaviors (кэш привязан к slug)
     */
    protected function findProduct($slug)
    {
        static $cache = [];
        if (!array_key_exists($slug, $cache)) {
            $cache[$slug] = $this->productRepository->findBySlug($slug);
        }
        return $cache[$slug];
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
        
        // Canonical URL - всегда без trailing slash (SEO best practice)
        $canonicalUrl = Yii::$app->request->absoluteUrl;
        $parsedUrl = parse_url($canonicalUrl);
        $path = $parsedUrl['path'] ?? '/';
        
        // ИСПРАВЛЕНО: Убираем trailing slash из пути, сохраняя query параметры
        if ($path !== '/' && substr($path, -1) === '/') {
            // Убираем слеш только из пути
            $cleanPath = rtrim($path, '/');
            $canonicalUrl = $parsedUrl['scheme'] . '://' . $parsedUrl['host'] . $cleanPath;
            
            // Добавляем query параметры обратно
            if (!empty($parsedUrl['query'])) {
                $canonicalUrl .= '?' . $parsedUrl['query'];
            }
        }
        
        $this->view->registerLinkTag(['rel' => 'canonical', 'href' => $canonicalUrl]);
    }

    /**
     * Главная страница каталога
     */
    public function actionIndex()
    {
        // Полный функционал каталога
        $request = Yii::$app->request;
        $pageSize = $this->module->pageSize ?? 24;
        
        // Получаем параметры фильтрации
        $filters = $request->get('filters', []);
        $currentSizeSystem = $request->get('size_system', 'eu');
        
        // Нормализуем фильтры
        $currentFilters = $this->normalizeFilterList($filters);
        
        // Строим запрос
        $query = Product::find()
            ->with(['brand', 'characteristicValues'])
            ->where(['is_active' => true]);
        
        // ИСПРАВЛЕНО: Передаём фильтры напрямую вместо мутации $_GET
        $query = $this->applyFilters($query, [
            'filters' => $filters,
            'size_system' => $currentSizeSystem,
        ]);
        
        // Получаем данные для фильтров
        $filters = $this->getFiltersData();
        
        // Пагинация
        $pagination = new Pagination([
            'totalCount' => $query->count(),
            'pageSize' => $pageSize,
            'pageParam' => 'page',
            'pageSizeParam' => 'per-page',
        ]);
        
        // Получаем товары
        $products = $query
            ->offset($pagination->offset)
            ->limit($pagination->limit)
            ->all();
        
        // Заголовок страницы
        $h1 = 'Каталог товаров';
        if (!empty($currentFilters['brand'])) {
            $brand = Brand::findOne(['slug' => $currentFilters['brand'][0]]);
            $h1 = $brand ? $brand->name : 'Каталог товаров';
        }
        
        // SEO
        $this->registerMetaTags([
            'title' => $h1 . ' | СНИКЕРХЭД',
            'description' => 'Оригинальные кроссовки из США и Европы',
            'keywords' => 'кроссовки, бренды, оригинал',
        ]);
        
        if (YII_ENV_PROD) {
            $this->registerSchemaWebSite();
        }
        
        // Рендерим view
        return $this->render('index', [
            'products' => $products,
            'pagination' => $pagination,
            'h1' => $h1,
            'filters' => $filters,
            'currentFilters' => $currentFilters,
            'activeFilters' => $currentFilters, // Используем currentFilters вместо activeFilters
            'currentSizeSystem' => $currentSizeSystem,
        ]);
    }
    
    /**
     * Страница всех брендов
     */
    public function actionBrands()
    {
        $brands = Brand::find()
            ->where(['is_active' => true])
            ->orderBy(['name' => SORT_ASC])
            ->all();
        
        // Подсчитываем товары для каждого бренда
        foreach ($brands as $brand) {
            $brand->products_count = Product::find()
                ->where(['brand_id' => $brand->id, 'is_active' => true])
                ->andWhere(['!=', 'stock_status', Product::STOCK_OUT_OF_STOCK])
                ->count();
        }
        
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

        $customer = null;
        $customerId = Yii::$app->session->get('customer_id');
        if ($customerId) {
            $customer = Customer::findOne($customerId);
        }

        return $this->render('favorites', [
            'favorites' => $favorites,
            'customer' => $customer,
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
            ->where(['is_active' => true])
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
            ->where(['id' => $id, 'is_active' => true])
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
                          ->where(['is_available' => true])
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
            ->where(['is_active' => true])
            ->andWhere(['!=', 'stock_status', Product::STOCK_OUT_OF_STOCK]); // Скрываем "нет в наличии"
        
        // Применяем дополнительные условия (brand_id, category_id и т.д.)
        if (!empty($whereConditions)) {
            $query->andWhere($whereConditions);
        }
        
        return $query;
    }

    /**
     * Определение системы размеров, если она не указана явно
     */
    protected function detectSizeSystem(array $sizes, ?string $preferred = null): string
    {
        $preferred = $preferred ? strtolower($preferred) : null;
        $validSystems = ['eu', 'us', 'uk', 'cm'];

        if ($preferred && in_array($preferred, $validSystems, true)) {
            return $preferred;
        }

        // Поддержка "умного" значения auto/smart или отсутствия параметра
        foreach ($sizes as $size) {
            $value = str_replace(',', '.', trim((string)$size));
            if ($value === '') {
                continue;
            }

            $numeric = (float)$value;

            if (strpos($value, '.') !== false && $numeric >= 20 && $numeric <= 35) {
                return 'cm';
            }

            if ($numeric >= 30 && $numeric <= 50) {
                return 'eu';
            }

            if ($numeric >= 3 && $numeric <= 18) {
                return 'us';
            }

            if ($numeric >= 2 && $numeric <= 15) {
                return 'uk';
            }
        }

        return 'eu';
    }

    /**
     * Нормализация значений размеров
     */
    protected function normalizeSizeValues(array $sizes): array
    {
        $normalized = array_map(static function($size) {
            $normalizedValue = str_replace(',', '.', trim((string)$size));
            return $normalizedValue;
        }, $sizes);

        $normalized = array_filter($normalized, static function($size) {
            return $size !== '';
        });

        return array_values(array_unique($normalized));
    }

    /**
     * Построение условий фильтра по размерам с поддержкой обратной совместимости
     */
    protected function buildSizeConditions(array $sizes, string $sizeSystem): array
    {
        $field = $this->resolveSizeField($sizeSystem);

        if ($field === 'cm_size') {
            $preparedSizes = array_map(static function($size) {
                return (float)$size;
            }, $sizes);
        } else {
            $preparedSizes = $sizes;
        }

        $primaryCondition = ['product_size.' . $field => $preparedSizes];

        // Поддерживаем старое поле size, если данные ещё не мигрированы
        if ($field !== 'size' && $field === 'eu_size') {
            return ['or', $primaryCondition, ['product_size.size' => $preparedSizes]];
        }

        return $primaryCondition;
    }

    /**
     * Получение поля таблицы для конкретной системы размеров
     */
    protected function resolveSizeField(string $sizeSystem): string
    {
        switch ($sizeSystem) {
            case 'us':
                return 'us_size';
            case 'uk':
                return 'uk_size';
            case 'cm':
                return 'cm_size';
            default:
                return 'eu_size';
        }
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
        
        $bypassCache = $this->shouldBypassCatalogCache();

        // Пагинация с кэшированным COUNT
        $countQuery = clone $query;
        $totalCount = $bypassCache
            ? $countQuery->count()
            : $this->getCachedCount($countQuery);
        
        // ДИАГНОСТИКА (только в dev)
        if (YII_ENV_DEV) {
            \Yii::info(sprintf(
                'actionIndex: totalCount=%d, defaultPageSize=24, expectedPages=%d',
                $totalCount,
                ceil($totalCount / 24)
            ), 'catalog_pagination');
        }
        
        $pagination = new Pagination([
            'defaultPageSize' => 4,
            'totalCount' => $totalCount,
        ]);
        
        // Получаем товары (без query cache — eager loading изображений ломается в FileCache)
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
        $currentSizeSystem = $request->get('size_system', 'eu');
        
        $currentFilters = [
            'brands' => $this->normalizeFilterList($request->get('brands')),
            'categories' => $this->normalizeFilterList($request->get('categories')),
            'sizes' => $this->normalizeFilterList($request->get('sizes')),
            'size_system' => $currentSizeSystem,
            'price_from' => $request->get('price_from'),
            'price_to' => $request->get('price_to'),
        ];
        
        // Формируем активные фильтры для отображения тегов
        // РЕФАКТОРИНГ: Используем централизованный метод из FilterBuilder
        $activeFilters = FilterBuilder::formatActiveFilters($currentFilters);
        
        // Регистрируем Schema.org микроразметку
        if (!empty($products)) {
            // ItemList с расширенной информацией о товарах
            $this->registerSchemaItemList($products, $totalCount, $currentFilters);
            
            // BreadcrumbList с учетом фильтров
            $breadcrumbs = isset($metaTags['breadcrumbs']) ? $metaTags['breadcrumbs'] : [];
            $this->registerSchemaBreadcrumbs($breadcrumbs, $currentFilters);
            
            // WebSite schema (только для главной страницы каталога)
            if ($request->pathInfo === 'catalog' || $request->pathInfo === 'catalog/index') {
                $this->registerSchemaWebSite();
            }
        }
        
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
     * Приводит параметр фильтра (строка/список) к массиву значений.
     */
    private function normalizeFilterList($value): array
    {
        if ($value === null || $value === '') {
            return [];
        }

        $values = is_array($value)
            ? $value
            : explode(',', (string)$value);

        return array_values(array_filter(
            array_map(static fn($item) => trim((string)$item), $values),
            static fn($item) => $item !== ''
        ));
    }
    
    /**
     * УДАЛЕНО: getActiveFilters() - 168 строк
     * ПРИЧИНА: Дублирование логики
     * ЗАМЕНА: FilterBuilder::formatActiveFilters()
     * 
     * Метод перенесен в FilterBuilder для централизации логики форматирования
     * активных фильтров. Теперь используется единый источник истины.
     */
    
    /**
     * УДАЛЕНО: buildFilterUrl() - 24 строки
     * ПРИЧИНА: Дублирование логики
     * ЗАМЕНА: FilterBuilder::buildQueryStringUrl() (protected метод)
     * 
     * Генерация URL теперь происходит через FilterBuilder::formatActiveFilters()
     * с возможностью передачи кастомного генератора URL.
     */

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
        
        // Получаем активные фильтры для динамического описания
        $request = Yii::$app->request;
        $currentFilters = [
            'brands' => [$brand->id],
            'categories' => $request->get('categories') ? explode(',', $request->get('categories')) : [],
            'price_from' => $request->get('price_from'),
            'price_to' => $request->get('price_to'),
        ];
        
        // Генерируем динамическое описание
        $description = $this->generateFilteredDescription(
            $currentFilters, 
            $brand->getMetaDescription()
        );
        
        $title = $this->generateFilteredTitle($currentFilters, $brand->name);
        
        // Приоритет для изображения: логотип бренда -> первый товар -> дефолт
        $ogImage = $brand->getLogoUrl();
        if (!$ogImage || strpos($ogImage, 'no-brand-logo') !== false) {
            $ogImage = $this->getFirstProductImage($query) ?: Yii::$app->request->hostInfo . '/images/og-default.jpg';
        } else {
            // Если логотип относительный путь, делаем абсолютным
            if (strpos($ogImage, 'http') !== 0) {
                $ogImage = Yii::$app->request->hostInfo . $ogImage;
            }
        }
        
        $metaTags = [
            'title' => $title . ' | СНИКЕРХЭД',
            'description' => $description,
            'keywords' => $brand->name . ', оригинальные товары, купить',
            'og:title' => $title,
            'og:description' => $description,
            'og:image' => $ogImage,
            'og:url' => Yii::$app->request->absoluteUrl,
            'og:type' => 'product.group',
            'og:site_name' => 'СНИКЕРХЭД',
            'twitter:card' => 'summary_large_image',
            'twitter:title' => $title,
            'twitter:description' => $description,
            'twitter:image' => $ogImage,
            // Breadcrumbs для Schema.org
            'breadcrumbs' => [
                ['name' => $brand->name, 'url' => '/catalog/brand/' . $brand->slug]
            ],
        ];
        
        return $this->renderCatalogPage(
            $query,
            $brand->name,
            $metaTags,
            ['brand_id' => $brand->id]
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
        
        // Получаем активные фильтры для динамического описания
        $request = Yii::$app->request;
        $currentFilters = [
            'brands' => $request->get('brands') ? explode(',', $request->get('brands')) : [],
            'categories' => $categoryIds,
            'price_from' => $request->get('price_from'),
            'price_to' => $request->get('price_to'),
        ];
        
        // Генерируем динамическое описание
        $description = $this->generateFilteredDescription(
            $currentFilters,
            $category->getMetaDescription()
        );
        
        $title = $this->generateFilteredTitle($currentFilters, $category->name);
        
        // Приоритет для изображения: изображение категории -> первый товар -> дефолт
        $ogImage = null;
        if ($category->image) {
            $ogImage = strpos($category->image, 'http') === 0 
                ? $category->image 
                : Yii::$app->request->hostInfo . '/' . ltrim($category->image, '/');
        }
        
        if (!$ogImage) {
            $ogImage = $this->getFirstProductImage($query) ?: Yii::$app->request->hostInfo . '/images/og-default.jpg';
        }
        
        return $this->renderCatalogPage(
            $query,
            $category->name,
            [
                'title' => $title . ' | СНИКЕРХЭД',
                'description' => $description,
                'keywords' => $category->name . ', купить, оригинал',
                'og:title' => $title,
                'og:description' => $description,
                'og:image' => $ogImage,
                'og:url' => Yii::$app->request->absoluteUrl,
                'og:type' => 'product.group',
                'og:site_name' => 'СНИКЕРХЭД',
                'twitter:card' => 'summary_large_image',
                'twitter:title' => $title,
                'twitter:description' => $description,
                'twitter:image' => $ogImage,
                // Breadcrumbs для Schema.org
                'breadcrumbs' => [
                    ['name' => $category->name, 'url' => '/catalog/category/' . $category->slug]
                ],
            ],
            ['category_id' => $categoryIds]
        );
    }

    /**
     * Карточка товара
     */
    public function actionProduct($slug)
    {
        $product = $this->productRepository->findBySlug($slug, true);

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

        // Похожие товары — используем ProductRepository::findSimilarProducts()
        $similarProducts = $this->productRepository->findSimilarProducts($product, 4);

        // Проверка — в избранном ли (через модель ProductFavorite)
        $isFavorite = ProductFavorite::find()
            ->where(['product_id' => $product->id])
            ->andWhere(Yii::$app->user->isGuest
                ? ['session_id' => Yii::$app->session->id]
                : ['user_id' => Yii::$app->user->id])
            ->exists();

        // SEO
        $this->view->title = $product->getMetaTitle();
        
        // Формируем продающий заголовок для соцсетей: "Бренд Модель"
        $socialTitle = $product->brand_name 
            ? $product->brand_name . ' ' . $product->name 
            : $product->name;
        
        // Формируем УТП-описание для соцсетей
        $socialDescription = $this->generateProductUTP($product);
        
        // Получаем абсолютный URL изображения
        $imageUrl = $product->getMainImageUrl();
        if (strpos($imageUrl, 'http') !== 0) {
            $imageUrl = Yii::$app->request->hostInfo . $imageUrl;
        }
        
        $this->registerMetaTags([
            'description' => $product->getMetaDescription(),
            'keywords' => $product->name . ', ' . ($product->brand_name ?? ($product->brand->name ?? '')) . ', купить, оригинал',
            // Open Graph для Facebook, VK, LinkedIn
            'og:title' => $socialTitle,
            'og:description' => $socialDescription,
            'og:type' => 'product',
            'og:url' => Yii::$app->request->absoluteUrl,
            'og:image' => $imageUrl,
            'og:image:width' => '1200',
            'og:image:height' => '630',
            'og:image:alt' => $product->name,
            'og:site_name' => 'СНИКЕРХЭД',
            'og:locale' => 'ru_RU',
            // Product-specific Open Graph
            'product:price:amount' => $product->price,
            'product:price:currency' => 'BYN',
            'product:availability' => $product->stock_status === 'in_stock' ? 'in stock' : 'out of stock',
            'product:condition' => 'new',
            'product:brand' => $product->brand_name ?? '',
            // Twitter Cards
            'twitter:card' => 'summary_large_image',
            'twitter:title' => $socialTitle,
            'twitter:description' => $socialDescription,
            'twitter:image' => $imageUrl,
            'twitter:image:alt' => $product->name,
        ]);

        return $this->render('product', [
            'product' => $product,
            'similarProducts' => $similarProducts,
            'isFavorite' => $isFavorite,
        ]);
    }


    // Методы actionAddFavorite, actionRemoveFavorite, actionFavoritesCount
    // перенесены в FavoriteController с использованием FavoriteService

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

    // Методы applyFilters, getCachedCount, shouldBypassCatalogCache, getFiltersData,
    // invalidateFiltersCache, invalidateCatalogCache перенесены в CatalogFiltersTrait

    // Метод checkIsFavorite удален - заменен на FavoriteService::isFavorite()

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
    
    // Удалены: actionFilterSef, applyParsedFilters, getAvailableFilters, getAvailableSizes,
    // registerJsonLd, registerSchemaItemList, registerSchemaBreadcrumbs, registerSchemaWebSite,
    // registerPaginationLinks - перенесены в CatalogSeoTrait
    
    /**
     * AJAX фильтрация товаров (без перезагрузки)
     * ИСПРАВЛЕНО: Передаём фильтры напрямую, без мутации $_GET
     */
    public function actionFilter()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $request = Yii::$app->request;
        
        // Собираем фильтры из POST
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
        
        // Формируем массив фильтров для передачи в applyFilters
        $filters = [
            'brands' => $brands ?: [],
            'categories' => $categories ?: [],
            'sizes' => $sizes ?: [],
            'size_system' => $sizeSystem,
            'price_from' => $priceFrom,
            'price_to' => $priceTo,
            'colors' => $request->post('colors') ? json_decode($request->post('colors'), true) : [],
            'discount_any' => $request->post('discount_any'),
            'discount_range' => $request->post('discount_range') ? json_decode($request->post('discount_range'), true) : [],
            'rating' => $request->post('rating'),
            'conditions' => $request->post('conditions') ? json_decode($request->post('conditions'), true) : [],
            'sort' => $sort,
        ];
        
        // Характеристики из POST
        foreach ($request->post() as $key => $value) {
            if (strpos($key, 'char_') === 0 && !empty($value)) {
                $decoded = is_string($value) ? json_decode($value, true) : $value;
                $filters[$key] = $decoded ?: [];
            }
        }
        
        // Применяем фильтры (ОПТИМИЗИРОВАНО: только нужные поля)
        $query = Product::find()
            ->select([
                'id', 'name', 'slug', 'brand_id', 'brand_name', 'category_name',
                'main_image_url', 'price', 'old_price', 'stock_status',
                'is_featured', 'rating', 'reviews_count', 'views_count', 'created_at'
            ])
            ->where(['product.is_active' => true])
            ->andWhere(['!=', 'product.stock_status', Product::STOCK_OUT_OF_STOCK]);
        
        // ИСПРАВЛЕНО: Передаём фильтры напрямую вместо мутации $_GET
        $query = $this->applyFilters($query, $filters);
        
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
        
        // Текущие фильтры (используем уже собранный $filters)
        $currentFilters = $filters;
        
        // Обновленные данные фильтров (умное сужение)
        // ИСПРАВЛЕНО: Передаем текущие фильтры для корректного подсчета
        $filters = $this->getFiltersData($currentFilters);
        
        // Получаем активные фильтры для отображения
        // ИСПРАВЛЕНО: Используем FilterBuilder вместо удаленного метода getActiveFilters
        $activeFilters = FilterBuilder::formatActiveFilters($currentFilters);
        
        // Рендерим только список товаров
        $html = $this->renderPartial('_products', [
            'products' => $products,
        ]);
        
        // Рендерим активные фильтры
        $activeFiltersHtml = '';
        if (!empty($activeFilters)) {
            $activeFiltersHtml = $this->renderPartial('_active_filters', [
                'activeFilters' => $activeFilters,
            ]);
        }
        
        // Рендерим пагинацию (если нужна)
        $paginationHtml = '';
        if ($pagination->pageCount > 1) {
            $paginationHtml = \yii\widgets\LinkPager::widget([
                'pagination' => $pagination,
                'prevPageLabel' => '<i class="bi bi-chevron-left"></i>',
                'nextPageLabel' => '<i class="bi bi-chevron-right"></i>',
                'maxButtonCount' => 7,
                'options' => ['class' => 'pagination'],
            ]);
        }
        
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
            'activeFiltersHtml' => $activeFiltersHtml,
            'activeFilters' => $activeFilters,
            'paginationHtml' => $paginationHtml,
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
            ->where(['id' => $ids, 'is_active' => true])
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
            ->leftJoin('product', 'product.brand_id = brand.id AND product.is_active = true')
            ->groupBy(['brand.id', 'brand.name', 'brand.slug'])
            ->having('COUNT(product.id) > 0')
            ->orderBy(['products_count' => SORT_DESC, 'brand.name' => SORT_ASC])
            ->asArray()
            ->all();

        return $brands;
    }

    /**
     * Загрузка дополнительных товаров (для infinite scroll)
     * ОПТИМИЗИРОВАНО: Используем buildProductQuery для единообразия
     */
    public function actionLoadMore()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        
        $request = Yii::$app->request;
        
        // Получаем номер страницы
        $page = (int)$request->get('page', 1);
        $perPage = 24;
        
        // Строим базовый query (DRY - используем тот же метод, что и в actionIndex)
        $query = $this->buildProductQuery();
        
        // Применяем фильтры
        $query = $this->applyFilters($query);
        
        // Подсчитываем общее количество (с кэшированием)
        $totalCount = $this->getCachedCount($query);
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
        
        // ДИАГНОСТИКА (только в dev режиме)
        if (YII_ENV_DEV) {
            \Yii::info(sprintf(
                'LoadMore: page=%d, loaded=%d products, total=%d, hasMore=%s',
                $page,
                count($products),
                $totalCount,
                ($page < $totalPages) ? 'yes' : 'no'
            ), 'infinite_scroll');
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

        $message .= "\n🔗 Ссылка: " . \yii\helpers\Url::to(['/catalog/catalog/product', 'slug' => $product->slug], true);

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
     * Отправка отзыва с публичной страницы товара (гостевой доступ)
     */
    public function actionSubmitReview()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        if (!Yii::$app->request->isPost) {
            return ['success' => false, 'message' => 'Метод не поддерживается'];
        }

        $data = Yii::$app->request->post();

        if (empty($data['product_id']) || empty($data['name']) || empty($data['text']) || empty($data['rating'])) {
            return ['success' => false, 'message' => 'Пожалуйста, заполните все обязательные поля'];
        }

        $product = Product::findOne($data['product_id']);
        if (!$product) {
            return ['success' => false, 'message' => 'Товар не найден'];
        }

        $message = "⭐ НОВЫЙ ОТЗЫВ\n\n";
        $message .= "👤 Автор: " . $data['name'] . "\n";
        if (!empty($data['email'])) {
            $message .= "📧 Email: " . $data['email'] . "\n";
        }
        $message .= "⭐ Оценка: " . $data['rating'] . "/5\n\n";
        $message .= "🛍 Товар: " . $product->brand_name . ' ' . $product->name . "\n";
        $message .= "💬 Отзыв:\n" . $data['text'] . "\n";
        $message .= "\n🔗 " . \yii\helpers\Url::to(['/catalog/catalog/product', 'slug' => $product->slug], true);

        try {
            Yii::$app->mailer->compose()
                ->setFrom([Yii::$app->params['senderEmail'] => Yii::$app->params['senderName']])
                ->setTo(Yii::$app->params['adminEmail'])
                ->setSubject('⭐ Новый отзыв: ' . $product->name)
                ->setTextBody($message)
                ->send();

            return ['success' => true, 'message' => 'Спасибо за ваш отзыв!'];
        } catch (\Exception $e) {
            Yii::error('Submit review email error: ' . $e->getMessage(), __METHOD__);
            return ['success' => true, 'message' => 'Отзыв получен, спасибо!'];
        }
    }

    /**
     * Отправка вопроса с публичной страницы товара (гостевой доступ)
     */
    public function actionSubmitQuestion()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        if (!Yii::$app->request->isPost) {
            return ['success' => false, 'message' => 'Метод не поддерживается'];
        }

        $data = Yii::$app->request->post();

        if (empty($data['product_id']) || empty($data['name']) || empty($data['question'])) {
            return ['success' => false, 'message' => 'Пожалуйста, заполните все обязательные поля'];
        }

        $product = Product::findOne($data['product_id']);
        if (!$product) {
            return ['success' => false, 'message' => 'Товар не найден'];
        }

        $message = "❓ НОВЫЙ ВОПРОС\n\n";
        $message .= "👤 Автор: " . $data['name'] . "\n";
        if (!empty($data['email'])) {
            $message .= "📧 Email: " . $data['email'] . "\n";
        }
        $message .= "\n🛍 Товар: " . $product->brand_name . ' ' . $product->name . "\n";
        $message .= "❓ Вопрос:\n" . $data['question'] . "\n";
        $message .= "\n🔗 " . \yii\helpers\Url::to(['/catalog/catalog/product', 'slug' => $product->slug], true);

        try {
            Yii::$app->mailer->compose()
                ->setFrom([Yii::$app->params['senderEmail'] => Yii::$app->params['senderName']])
                ->setTo(Yii::$app->params['adminEmail'])
                ->setSubject('❓ Новый вопрос о товаре: ' . $product->name)
                ->setTextBody($message)
                ->send();

            return ['success' => true, 'message' => 'Спасибо! Ваш вопрос отправлен.'];
        } catch (\Exception $e) {
            Yii::error('Submit question email error: ' . $e->getMessage(), __METHOD__);
            return ['success' => true, 'message' => 'Вопрос получен, спасибо!'];
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
    
    // Методы generateFilteredDescription, generateFilteredTitle, getFirstProductImage, generateProductUTP
    // перенесены в CatalogSeoTrait
}
