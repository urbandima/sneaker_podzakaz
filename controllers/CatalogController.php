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
use app\repositories\ProductRepository;
use app\services\Catalog\FilterBuilder;

/**
 * Контроллер каталога товаров
 */
class CatalogController extends Controller
{
    public $layout = 'public';
    
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
     * Найти товар для behaviors
     */
    protected function findProduct($slug)
    {
        static $product = null;
        if ($product === null) {
            $product = $this->productRepository->findBySlug($slug);
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
        // SEO: Редирект с trailing slash на URL без слеша
        // ИСПРАВЛЕНО: Проверяем URL напрямую для надёжности
        $currentUrl = Yii::$app->request->url;
        
        // Проверяем, заканчивается ли URL на /catalog/ (с trailing slash)
        if (preg_match('#/catalog/(\?.*)?$#', $currentUrl)) {
            // Убираем trailing slash, сохраняя query параметры
            $cleanUrl = preg_replace('#/catalog/(\?.*)?$#', '/catalog$1', $currentUrl);
            return $this->redirect($cleanUrl, 301);
        }
        
        // Установка HTTP Cache headers
        HttpCacheHeaders::setCatalogHeaders(Yii::$app->response);
        
        $query = $this->buildProductQuery();
        
        // Получаем активные фильтры для динамического описания
        $request = Yii::$app->request;
        $currentFilters = [
            'brands' => $request->get('brands') ? explode(',', $request->get('brands')) : [],
            'categories' => $request->get('categories') ? explode(',', $request->get('categories')) : [],
            'price_from' => $request->get('price_from'),
            'price_to' => $request->get('price_to'),
        ];
        
        // Генерируем динамическое описание на основе фильтров
        $description = $this->generateFilteredDescription($currentFilters, 'Оригинальные товары из США и Европы');
        $title = $this->generateFilteredTitle($currentFilters, 'Каталог товаров');
        
        // Получаем изображение первого товара или дефолтное
        $ogImage = $this->getFirstProductImage($query) ?: Yii::$app->request->hostInfo . '/images/og-default.jpg';
        
        return $this->renderCatalogPage(
            $query,
            'Каталог товаров',
            [
                'title' => $title . ' | СНИКЕРХЭД',
                'description' => $description,
                'keywords' => 'купить кроссовки, оригинальная обувь, nike, adidas, интернет-магазин',
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
        
        // Пагинация с кэшированным COUNT
        // ИСПРАВЛЕНО: Временно отключаем кэш для диагностики
        $totalCount = $query->count();
        
        // ДИАГНОСТИКА (только в dev)
        if (YII_ENV_DEV) {
            \Yii::info(sprintf(
                'actionIndex: totalCount=%d, defaultPageSize=24, expectedPages=%d',
                $totalCount,
                ceil($totalCount / 24)
            ), 'catalog_pagination');
        }
        
        $pagination = new Pagination([
            'defaultPageSize' => 24,
            'totalCount' => $totalCount,
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
        $currentSizeSystem = $request->get('size_system', 'eu');
        
        $currentFilters = [
            'brands' => $request->get('brands') ? explode(',', $request->get('brands')) : [],
            'categories' => $request->get('categories') ? explode(',', $request->get('categories')) : [],
            'sizes' => $request->get('sizes') ? explode(',', $request->get('sizes')) : [],
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

        // Похожие товары - используем репозиторий с многоуровневой стратегией
        $similarProducts = $this->productRepository->findSimilarProducts($product, 12);

        // Проверка - в избранном ли
        $isFavorite = $this->checkIsFavorite($product->id);

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
            'keywords' => $product->name . ', ' . $product->brand->name . ', купить, оригинал',
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

        // Фильтр по наличию
        if ($stockStatus = $request->get('stock_status')) {
            $query->andWhere(['stock_status' => $stockStatus]);
        }

        // Поиск
        if ($search = $request->get('search')) {
            $query->andWhere(['like', 'name', $search]);
        }
        
        // РЕФАКТОРИНГ: Применяем фильтры через FilterBuilder
        // Собираем все фильтры из запроса
        $brands = $request->get('brands');
        $categories = $request->get('categories');
        $sizes = $request->get('sizes');
        $priceFrom = $request->get('price_from');
        $priceTo = $request->get('price_to');
        $conditions = $request->get('conditions');
        
        $filters = [
            'brands' => $brands ? (is_array($brands) ? $brands : explode(',', $brands)) : [],
            'categories' => $categories ? (is_array($categories) ? $categories : explode(',', $categories)) : [],
            'sizes' => $sizes ? (is_array($sizes) ? $sizes : explode(',', $sizes)) : [],
            'size_system' => $request->get('size_system', 'eu'),
            'price_from' => $priceFrom,
            'price_to' => $priceTo,
            'colors' => $request->get('colors'),
            'discount_any' => $request->get('discount_any'),
            'discount_range' => $request->get('discount_range'),
            'rating' => $request->get('rating'),
            'conditions' => $conditions ? (is_array($conditions) ? $conditions : explode(',', $conditions)) : [],
        ];
        
        // Характеристики
        foreach ($request->queryParams as $key => $value) {
            if (strpos($key, 'char_') === 0 && !empty($value)) {
                $filters[$key] = is_array($value) ? $value : explode(',', $value);
            }
        }
        
        // Применяем через FilterBuilder
        FilterBuilder::applyFiltersToProductQuery($query, $filters);

        // Сортировка
        $sortBy = $request->get('sort', 'popular');
        switch ($sortBy) {
            case 'price_asc':
                // ИСПРАВЛЕНО: Сортировка по минимальной цене из product_size с исключением нулевых цен
                $query->addSelect([
                    'min_price' => '(SELECT MIN(price_byn) FROM product_size WHERE product_size.product_id = product.id AND product_size.is_available = 1 AND product_size.price_byn > 0)'
                ]);
                // Исключаем товары без цен
                $query->andWhere([
                    'product.id' => new \yii\db\Expression(
                        'SELECT DISTINCT product_id FROM product_size WHERE is_available = 1 AND price_byn > 0'
                    )
                ]);
                $query->orderBy(['min_price' => SORT_ASC]);
                break;
            case 'price_desc':
                // ИСПРАВЛЕНО: Сортировка по максимальной цене из product_size с исключением нулевых цен
                $query->addSelect([
                    'max_price' => '(SELECT MAX(price_byn) FROM product_size WHERE product_size.product_id = product.id AND product_size.is_available = 1 AND product_size.price_byn > 0)'
                ]);
                // Исключаем товары без цен
                $query->andWhere([
                    'product.id' => new \yii\db\Expression(
                        'SELECT DISTINCT product_id FROM product_size WHERE is_available = 1 AND price_byn > 0'
                    )
                ]);
                $query->orderBy(['max_price' => SORT_DESC]);
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
     * РЕФАКТОРИНГ: Используем FilterBuilder для построения всех фильтров
     * ИСПРАВЛЕНО: Поддержка передачи готовых currentFilters для AJAX запросов
     */
    protected function getFiltersData($currentFiltersOrBaseCondition = [])
    {
        // ИСПРАВЛЕНО: Определяем, что передано - currentFilters или baseCondition
        // Если передан массив с ключами фильтров (brands, categories и т.д.) - это currentFilters
        // Иначе - это baseCondition (brand_id, category_id)
        $isCurrentFilters = isset($currentFiltersOrBaseCondition['brands']) || 
                           isset($currentFiltersOrBaseCondition['categories']) ||
                           isset($currentFiltersOrBaseCondition['sizes']) ||
                           isset($currentFiltersOrBaseCondition['price_from']);
        
        if ($isCurrentFilters) {
            // Используем переданные фильтры (для AJAX)
            $currentFilters = $currentFiltersOrBaseCondition;
            $baseCondition = [];
        } else {
            // Читаем фильтры из request (для обычных запросов)
            $baseCondition = $currentFiltersOrBaseCondition;
            $request = Yii::$app->request;
            $currentFilters = [
                'brands' => $request->get('brands') ? (is_array($request->get('brands')) ? $request->get('brands') : explode(',', $request->get('brands'))) : [],
                'categories' => $request->get('categories') ? (is_array($request->get('categories')) ? $request->get('categories') : explode(',', $request->get('categories'))) : [],
                'sizes' => $request->get('sizes') ? (is_array($request->get('sizes')) ? $request->get('sizes') : explode(',', $request->get('sizes'))) : [],
                'size_system' => $request->get('size_system', 'eu'),
                'price_from' => $request->get('price_from'),
                'price_to' => $request->get('price_to'),
                'colors' => $request->get('colors') ? (is_array($request->get('colors')) ? $request->get('colors') : explode(',', $request->get('colors'))) : [],
            ];
            
            // Характеристики (формат: char_{id} => [value_ids])
            foreach ($request->queryParams as $key => $value) {
                if (strpos($key, 'char_') === 0 && !empty($value)) {
                    $currentFilters[$key] = is_array($value) ? $value : explode(',', $value);
                }
            }
        }
        
        // НОВОЕ: Используем FilterBuilder для построения всех фильтров
        return FilterBuilder::buildFilters($currentFilters, $baseCondition);
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
     * УДАЛЕНО: actionFilterSef() - 74 строки
     * ПРИЧИНА: Не подключен к роутингу, не используется
     * ДАТА: 2025-11-10
     * 
     * SEF URL фильтрация реализована через SmartFilter::generateSefUrl()
     * и используется в formatActiveFilters() с callback функцией.
     * 
     * Если понадобится SEF URL роутинг, добавить в config/web.php:
     * 'catalog/filter/<filters:.+>' => 'catalog/filter-sef'
     */
    
    /**
     * УДАЛЕНО: applyParsedFilters() - 43 строки
     * ПРИЧИНА: Использовался только в actionFilterSef
     * ЗАМЕНА: FilterBuilder::applyFiltersToProductQuery()
     */
    
    /**
     * УДАЛЕНО: getAvailableFilters() - 56 строк
     * ПРИЧИНА: Использовался только в actionFilterSef
     * ЗАМЕНА: FilterBuilder::buildFilters()
     */
    
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
            // ИСПРАВЛЕНО: фильтруем некорректные размеры (нормальный диапазон EU: 20-50)
            $euQuery = clone $baseQuery;
            $result['eu'] = $euQuery
                ->select([
                    'product_size.eu_size as size',
                    'COUNT(DISTINCT product_size.product_id) as count'
                ])
                ->andWhere(['IS NOT', 'product_size.eu_size', null])
                ->andWhere(['>=', 'CAST(product_size.eu_size AS DECIMAL)', 20])
                ->andWhere(['<=', 'CAST(product_size.eu_size AS DECIMAL)', 50])
                ->groupBy(['product_size.eu_size'])
                ->having(['>', 'count', 0])
                ->asArray()
                ->all();
            
            // Получаем размеры US
            // ИСПРАВЛЕНО: фильтруем некорректные размеры (нормальный диапазон US: 3-18)
            $usQuery = clone $baseQuery;
            $result['us'] = $usQuery
                ->select([
                    'product_size.us_size as size',
                    'COUNT(DISTINCT product_size.product_id) as count'
                ])
                ->andWhere(['IS NOT', 'product_size.us_size', null])
                ->andWhere(['>=', 'CAST(product_size.us_size AS DECIMAL)', 3])
                ->andWhere(['<=', 'CAST(product_size.us_size AS DECIMAL)', 18])
                ->groupBy(['product_size.us_size'])
                ->having(['>', 'count', 0])
                ->asArray()
                ->all();
            
            // Получаем размеры UK
            // ИСПРАВЛЕНО: фильтруем некорректные размеры (нормальный диапазон UK: 2-15)
            $ukQuery = clone $baseQuery;
            $result['uk'] = $ukQuery
                ->select([
                    'product_size.uk_size as size',
                    'COUNT(DISTINCT product_size.product_id) as count'
                ])
                ->andWhere(['IS NOT', 'product_size.uk_size', null])
                ->andWhere(['>=', 'CAST(product_size.uk_size AS DECIMAL)', 2])
                ->andWhere(['<=', 'CAST(product_size.uk_size AS DECIMAL)', 15])
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
                // ИСПРАВЛЕНО: Фильтруем некорректные размеры (нормальный диапазон 20-35 см)
                ->andWhere(['>=', 'CAST(product_size.cm_size AS DECIMAL)', 20])
                ->andWhere(['<=', 'CAST(product_size.cm_size AS DECIMAL)', 35])
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
     * Helper метод для регистрации JSON-LD в head
     * 
     * @param array $schema Массив схемы для JSON-LD
     * @param string $key Уникальный ключ для схемы
     */
    protected function registerJsonLd($schema, $key)
    {
        $jsonLd = json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
        
        // Регистрируем через специальный блок в head
        // Yii2 не имеет прямого способа добавить custom HTML в head, поэтому используем workaround
        $this->view->registerJs(
            '', 
            \yii\web\View::POS_HEAD,
            $key
        );
        
        // Сохраняем JSON-LD в params для вывода в layout
        if (!isset($this->view->params['jsonLdSchemas'])) {
            $this->view->params['jsonLdSchemas'] = [];
        }
        $this->view->params['jsonLdSchemas'][$key] = $jsonLd;
    }
    
    /**
     * Регистрация Schema.org ItemList с расширенными данными Product
     * ИСПРАВЛЕНО: Используем правильный метод для JSON-LD
     * ДОБАВЛЕНО: description, sku, aggregateRating
     * 
     * @param array $products Массив товаров
     * @param int $totalCount Общее количество товаров
     * @param array $filters Активные фильтры для SEO-данных
     */
    protected function registerSchemaItemList($products, $totalCount, $filters = [])
    {
        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'ItemList',
            'numberOfItems' => $totalCount,
            'itemListElement' => []
        ];
        
        // Добавляем информацию о фильтрах как часть ItemList
        if (!empty($filters)) {
            $schema['description'] = $this->generateFilteredDescription($filters, '');
        }
        
        foreach ($products as $index => $product) {
            // Формируем полный объект Product для каждого товара
            $productSchema = [
                '@type' => 'Product',
                'name' => $product->name,
                'url' => Yii::$app->request->hostInfo . $product->getUrl(),
                'image' => Yii::$app->request->hostInfo . $product->getMainImageUrl(),
                'sku' => $product->id, // Используем ID как SKU
            ];
            
            // Добавляем описание если есть
            if (!empty($product->description)) {
                $productSchema['description'] = mb_substr(strip_tags($product->description), 0, 200);
            }
            
            // Бренд
            if ($product->brand) {
                $productSchema['brand'] = [
                    '@type' => 'Brand',
                    'name' => $product->brand_name ?? $product->brand->name
                ];
            }
            
            // Offers с расширенными данными
            $availability = 'https://schema.org/OutOfStock';
            if ($product->stock_status === 'in_stock') {
                $availability = 'https://schema.org/InStock';
            } elseif ($product->stock_status === 'pre_order') {
                $availability = 'https://schema.org/PreOrder';
            }
            
            $productSchema['offers'] = [
                '@type' => 'Offer',
                'price' => (string)$product->price,
                'priceCurrency' => 'BYN',
                'availability' => $availability,
                'url' => Yii::$app->request->hostInfo . $product->getUrl(),
                'priceValidUntil' => date('Y-m-d', strtotime('+1 year')), // Цена действительна год
            ];
            
            // Добавляем старую цену если есть скидка
            if ($product->old_price && $product->old_price > $product->price) {
                $productSchema['offers']['priceSpecification'] = [
                    '@type' => 'PriceSpecification',
                    'price' => (string)$product->price,
                    'priceCurrency' => 'BYN',
                    'valueAddedTaxIncluded' => true
                ];
            }
            
            // Добавляем рейтинг если есть
            if (!empty($product->rating) && $product->rating > 0) {
                $productSchema['aggregateRating'] = [
                    '@type' => 'AggregateRating',
                    'ratingValue' => (string)$product->rating,
                    'reviewCount' => (int)($product->reviews_count ?? 1),
                    'bestRating' => '5',
                    'worstRating' => '1'
                ];
            }
            
            // Добавляем в список
            $schema['itemListElement'][] = [
                '@type' => 'ListItem',
                'position' => $index + 1,
                'item' => $productSchema
            ];
        }
        
        // Регистрируем JSON-LD через helper метод
        $this->registerJsonLd($schema, 'schema-itemlist');
    }
    
    /**
     * Регистрация Schema.org BreadcrumbList с учетом фильтров
     * Хлебные крошки показывают путь навигации включая активные фильтры
     * 
     * @param array $breadcrumbs Массив хлебных крошек [['name' => 'Название', 'url' => '/url']]
     * @param array $filters Активные фильтры для добавления в хлебные крошки
     */
    protected function registerSchemaBreadcrumbs($breadcrumbs = [], $filters = [])
    {
        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => []
        ];
        
        // Всегда начинаем с главной страницы
        $position = 1;
        $schema['itemListElement'][] = [
            '@type' => 'ListItem',
            'position' => $position++,
            'name' => 'Главная',
            'item' => Yii::$app->request->hostInfo
        ];
        
        // Добавляем каталог
        $schema['itemListElement'][] = [
            '@type' => 'ListItem',
            'position' => $position++,
            'name' => 'Каталог',
            'item' => Yii::$app->request->hostInfo . '/catalog'
        ];
        
        // Добавляем переданные хлебные крошки (бренд, категория)
        foreach ($breadcrumbs as $crumb) {
            $schema['itemListElement'][] = [
                '@type' => 'ListItem',
                'position' => $position++,
                'name' => $crumb['name'],
                'item' => Yii::$app->request->hostInfo . $crumb['url']
            ];
        }
        
        // Добавляем информацию о фильтрах как часть навигации
        if (!empty($filters)) {
            $filterLabels = [];
            
            // Бренды
            if (!empty($filters['brands'])) {
                $brands = Brand::find()
                    ->where(['id' => $filters['brands']])
                    ->select('name')
                    ->column();
                if (!empty($brands)) {
                    $filterLabels[] = implode(', ', $brands);
                }
            }
            
            // Цена
            if (!empty($filters['price_from']) || !empty($filters['price_to'])) {
                $priceLabel = 'Цена: ';
                if (!empty($filters['price_from'])) $priceLabel .= 'от ' . $filters['price_from'] . ' ';
                if (!empty($filters['price_to'])) $priceLabel .= 'до ' . $filters['price_to'];
                $filterLabels[] = trim($priceLabel) . ' BYN';
            }
            
            // Если есть фильтры, добавляем их как последний элемент
            if (!empty($filterLabels)) {
                $schema['itemListElement'][] = [
                    '@type' => 'ListItem',
                    'position' => $position++,
                    'name' => implode(' • ', $filterLabels),
                    'item' => Yii::$app->request->absoluteUrl
                ];
            }
        }
        
        // Регистрируем JSON-LD через helper метод
        $this->registerJsonLd($schema, 'schema-breadcrumbs');
    }
    
    /**
     * Регистрация Schema.org WebSite для поиска
     * Добавляет разметку для поисковой строки сайта
     */
    protected function registerSchemaWebSite()
    {
        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'WebSite',
            'name' => 'СНИКЕРХЭД',
            'url' => Yii::$app->request->hostInfo,
            'potentialAction' => [
                '@type' => 'SearchAction',
                'target' => [
                    '@type' => 'EntryPoint',
                    'urlTemplate' => Yii::$app->request->hostInfo . '/catalog?search={search_term_string}'
                ],
                'query-input' => 'required name=search_term_string'
            ]
        ];
        
        // Регистрируем JSON-LD через helper метод
        $this->registerJsonLd($schema, 'schema-website');
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
        
        // Характеристики из POST (ВАЖНО: должно быть ДО applyFilters())
        foreach ($request->post() as $key => $value) {
            if (strpos($key, 'char_') === 0 && !empty($value)) {
                $decoded = is_string($value) ? json_decode($value, true) : $value;
                $_GET[$key] = $decoded ?: [];
            }
        }
        
        // Применяем фильтры (ОПТИМИЗИРОВАНО: только нужные поля)
        $query = Product::find()
            ->select([
                'id', 'name', 'slug', 'brand_id', 'brand_name', 'category_name',
                'main_image_url', 'price', 'old_price', 'stock_status',
                'is_featured', 'rating', 'reviews_count', 'views_count', 'created_at'
            ])
            ->where(['product.is_active' => 1])
            ->andWhere(['!=', 'product.stock_status', Product::STOCK_OUT_OF_STOCK]);
        
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
            'colors' => $request->post('colors') ? json_decode($request->post('colors'), true) : [],
            'discount_any' => $request->post('discount_any'),
            'discount_range' => $request->post('discount_range') ? json_decode($request->post('discount_range'), true) : [],
            'rating' => $request->post('rating'),
            'conditions' => $request->post('conditions') ? json_decode($request->post('conditions'), true) : [],
        ];
        
        // Добавляем характеристики в currentFilters
        foreach ($_GET as $key => $value) {
            if (strpos($key, 'char_') === 0) {
                $currentFilters[$key] = $value;
            }
        }
        
        // Обновленные данные фильтров (умное сужение)
        // ИСПРАВЛЕНО: Передаем текущие фильтры для корректного подсчета
        $filters = $this->getFiltersData($currentFilters);
        
        // Получаем активные фильтры для отображения
        $activeFilters = $this->getActiveFilters($currentFilters);
        
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
    
    /**
     * Генерация динамического описания на основе активных фильтров
     * Используется для Open Graph и Twitter Cards
     * 
     * @param array $filters Активные фильтры (brands, categories, price_from, price_to)
     * @param string $baseDescription Базовое описание
     * @return string
     */
    protected function generateFilteredDescription($filters, $baseDescription = '')
    {
        $parts = [];
        
        // Добавляем информацию о брендах
        if (!empty($filters['brands'])) {
            $brands = Brand::find()
                ->where(['id' => $filters['brands']])
                ->select('name')
                ->column();
            if (!empty($brands)) {
                $parts[] = implode(', ', $brands);
            }
        }
        
        // Добавляем информацию о категориях
        if (!empty($filters['categories'])) {
            $categories = Category::find()
                ->where(['id' => $filters['categories']])
                ->select('name')
                ->column();
            if (!empty($categories) && empty($filters['brands'])) {
                // Только если бренды не указаны, чтобы не перегружать
                $parts[] = implode(', ', $categories);
            }
        }
        
        // Добавляем информацию о ценовом диапазоне
        if (!empty($filters['price_from']) && !empty($filters['price_to'])) {
            $parts[] = "от {$filters['price_from']} до {$filters['price_to']} BYN";
        } elseif (!empty($filters['price_from'])) {
            $parts[] = "от {$filters['price_from']} BYN";
        } elseif (!empty($filters['price_to'])) {
            $parts[] = "до {$filters['price_to']} BYN";
        }
        
        // Формируем итоговое описание
        if (!empty($parts)) {
            $filterInfo = implode('. ', $parts);
            return $filterInfo . '. ' . $baseDescription;
        }
        
        return $baseDescription ?: 'Оригинальные товары из США и Европы с доставкой по Беларуси';
    }
    
    /**
     * Генерация динамического заголовка на основе активных фильтров
     * 
     * @param array $filters Активные фильтры
     * @param string $baseTitle Базовый заголовок
     * @return string
     */
    protected function generateFilteredTitle($filters, $baseTitle = 'Каталог')
    {
        $parts = [$baseTitle];
        
        // Добавляем бренды в заголовок (но не более 2, чтобы не был слишком длинным)
        if (!empty($filters['brands'])) {
            $brands = Brand::find()
                ->where(['id' => array_slice($filters['brands'], 0, 2)])
                ->select('name')
                ->column();
            if (!empty($brands)) {
                $parts[] = implode(', ', $brands);
            }
        }
        
        // Добавляем ценовой диапазон
        if (!empty($filters['price_from']) || !empty($filters['price_to'])) {
            if (!empty($filters['price_from']) && !empty($filters['price_to'])) {
                $parts[] = "{$filters['price_from']}-{$filters['price_to']} BYN";
            } elseif (!empty($filters['price_from'])) {
                $parts[] = "от {$filters['price_from']} BYN";
            } elseif (!empty($filters['price_to'])) {
                $parts[] = "до {$filters['price_to']} BYN";
            }
        }
        
        return implode(' - ', $parts);
    }
    
    /**
     * Получение URL изображения первого товара из выборки
     * Используется как fallback для Open Graph изображения
     * 
     * @param \yii\db\ActiveQuery $query Запрос товаров
     * @return string|null URL изображения или null
     */
    protected function getFirstProductImage($query)
    {
        // Клонируем запрос, чтобы не изменять оригинальный
        $productQuery = clone $query;
        
        $product = $productQuery
            ->select(['main_image_url'])
            ->limit(1)
            ->one();
        
        if ($product && $product->main_image_url) {
            $imageUrl = $product->main_image_url;
            
            // Делаем URL абсолютным, если он относительный
            if (strpos($imageUrl, 'http') !== 0) {
                $imageUrl = Yii::$app->request->hostInfo . '/' . ltrim($imageUrl, '/');
            }
            
            return $imageUrl;
        }
        
        return null;
    }
    
    /**
     * Генерация УТП (уникального торгового предложения) для товара
     * Используется в Open Graph и Twitter Cards для привлекательного описания
     * 
     * @param Product $product Товар
     * @return string Продающее описание
     */
    protected function generateProductUTP($product)
    {
        $utp = [];
        
        // Основное УТП - оригинальный товар
        $utp[] = '✓ 100% оригинал';
        
        // Бренд и модель
        if ($product->brand_name) {
            $utp[] = $product->brand_name . ' ' . $product->name;
        }
        
        // Цена и скидка
        if ($product->old_price && $product->old_price > $product->price) {
            $discount = round((($product->old_price - $product->price) / $product->old_price) * 100);
            $utp[] = "Скидка {$discount}%! Цена: {$product->price} BYN (было {$product->old_price} BYN)";
        } else {
            $utp[] = "Цена: {$product->price} BYN";
        }
        
        // Наличие
        if ($product->stock_status === 'in_stock') {
            $utp[] = '✓ В наличии';
        } elseif ($product->stock_status === 'pre_order') {
            $utp[] = '✓ Под заказ 7-14 дней';
        }
        
        // Доставка
        $utp[] = '✓ Доставка по Беларуси';
        
        // Гарантия
        $utp[] = '✓ Гарантия подлинности';
        
        // Рейтинг
        if (!empty($product->rating) && $product->rating >= 4) {
            $stars = str_repeat('⭐', min(5, (int)$product->rating));
            $utp[] = "Рейтинг: {$stars}";
        }
        
        return implode(' • ', $utp);
    }
}
