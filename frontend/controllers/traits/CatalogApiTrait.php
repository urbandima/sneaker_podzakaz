<?php

namespace app\controllers\traits;

use Yii;
use yii\web\Response;
use yii\data\Pagination;
use app\models\Product;
use app\models\Brand;
use app\services\Catalog\FilterBuilder;

/**
 * Trait CatalogApiTrait
 * API методы каталога (AJAX endpoints)
 */
trait CatalogApiTrait
{
    /**
     * AJAX фильтрация товаров
     */
    public function actionFilter()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $request = Yii::$app->request;
        
        // Читаем параметры из POST
        $brands = $this->decodeJsonParam($request->post('brands'));
        $categories = $this->decodeJsonParam($request->post('categories'));
        $sizes = $this->decodeJsonParam($request->post('sizes'));
        $sizeSystem = $request->post('sizeSystem', 'eu');
        $priceFrom = $request->post('price_from');
        $priceTo = $request->post('price_to');
        $sort = $request->post('sort', 'popular');
        $page = (int)$request->post('page', 1);
        $perPage = (int)$request->post('perPage', 24);
        
        // Временно добавляем в GET для совместимости с applyFilters()
        $_GET['brands'] = $brands;
        $_GET['categories'] = $categories;
        $_GET['sizes'] = $sizes;
        $_GET['size_system'] = $sizeSystem;
        $_GET['price_from'] = $priceFrom;
        $_GET['price_to'] = $priceTo;
        $_GET['sort'] = $sort;
        
        // Характеристики из POST
        foreach ($request->post() as $key => $value) {
            if (strpos($key, 'char_') === 0 && !empty($value)) {
                $_GET[$key] = $this->decodeJsonParam($value);
            }
        }
        
        // Строим запрос
        $query = Product::find()
            ->select([
                'id', 'name', 'slug', 'brand_id', 'brand_name', 'category_name',
                'main_image_url', 'price', 'old_price', 'stock_status',
                'is_featured', 'rating', 'reviews_count', 'views_count', 'created_at'
            ])
            ->where(['product.is_active' => 1])
            ->andWhere(['!=', 'product.stock_status', Product::STOCK_OUT_OF_STOCK]);
        
        $query = $this->applyFilters($query);
        $query = $this->applySorting($query, $sort);
        
        // Пагинация
        $totalCount = (clone $query)->count();
        $pagination = new Pagination([
            'defaultPageSize' => $perPage,
            'totalCount' => $totalCount,
            'page' => $page - 1,
        ]);
        
        $products = $query
            ->with(['brand' => fn($q) => $q->select(['id', 'name', 'slug'])])
            ->offset($pagination->offset)
            ->limit($pagination->limit)
            ->all();
        
        // Текущие фильтры
        $currentFilters = $this->buildCurrentFilters($request);
        $filters = $this->getFiltersData($currentFilters);
        $activeFilters = FilterBuilder::formatActiveFilters($currentFilters);
        
        // Рендерим HTML
        $html = $this->renderPartial('_products', ['products' => $products]);
        $activeFiltersHtml = !empty($activeFilters) 
            ? $this->renderPartial('_active_filters', ['activeFilters' => $activeFilters])
            : '';
        
        $paginationHtml = $pagination->pageCount > 1 
            ? \yii\widgets\LinkPager::widget([
                'pagination' => $pagination,
                'prevPageLabel' => '<i class="bi bi-chevron-left"></i>',
                'nextPageLabel' => '<i class="bi bi-chevron-right"></i>',
                'maxButtonCount' => 7,
                'options' => ['class' => 'pagination'],
            ])
            : '';
        
        return [
            'success' => true,
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
     * Загрузка дополнительных товаров (infinite scroll)
     */
    public function actionLoadMore()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        
        $page = (int)Yii::$app->request->get('page', 1);
        $perPage = 24;
        
        $query = $this->buildProductQuery();
        $query = $this->applyFilters($query);
        
        $totalCount = $this->getCachedCount($query);
        $totalPages = ceil($totalCount / $perPage);
        
        $products = $query
            ->offset(($page - 1) * $perPage)
            ->limit($perPage)
            ->all();
        
        $html = !empty($products) 
            ? $this->renderPartial('_products', ['products' => $products])
            : '';
        
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
     * Получить товары по списку IDs
     */
    public function actionProductsByIds()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

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
            ->where(['id' => $ids, 'is_active' => 1])
            ->andWhere(['!=', 'stock_status', Product::STOCK_OUT_OF_STOCK])
            ->limit(20)
            ->all();

        return array_map(fn($product) => [
            'id' => $product->id,
            'name' => $product->name,
            'brand' => $product->brand->name,
            'price' => Yii::$app->formatter->asCurrency($product->price, 'BYN'),
            'image' => $product->getMainImageUrl(),
            'url' => $product->getUrl(),
        ], $products);
    }

    /**
     * Получить список брендов
     */
    public function actionGetBrands()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        return Brand::find()
            ->select(['brand.id', 'brand.name', 'brand.slug', 'COUNT(product.id) as products_count'])
            ->leftJoin('product', 'product.brand_id = brand.id AND product.is_active = 1')
            ->groupBy(['brand.id', 'brand.name', 'brand.slug'])
            ->having('COUNT(product.id) > 0')
            ->orderBy(['products_count' => SORT_DESC, 'brand.name' => SORT_ASC])
            ->asArray()
            ->all();
    }

    /**
     * Быстрый заказ в 1 клик
     */
    public function actionQuickOrder()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        if (!Yii::$app->request->isPost) {
            return ['success' => false, 'message' => 'Метод не поддерживается'];
        }

        $rawBody = Yii::$app->request->getRawBody();
        $data = json_decode($rawBody, true) ?: Yii::$app->request->post();
        
        if (empty($data['product_id']) || empty($data['name']) || empty($data['phone'])) {
            return [
                'success' => false,
                'message' => 'Пожалуйста, заполните все обязательные поля'
            ];
        }

        $product = Product::findOne($data['product_id']);
        if (!$product) {
            return ['success' => false, 'message' => 'Товар не найден'];
        }

        $sizeInfo = !empty($data['size']) ? " (Размер: {$data['size']})" : '';

        $message = "📱 БЫСТРЫЙ ЗАКАЗ\n\n";
        $message .= "👤 Клиент: " . $data['name'] . "\n";
        $message .= "📞 Телефон: " . $data['phone'] . "\n\n";
        $message .= "🛍 Товар: " . $product->brand_name . ' ' . $product->name . $sizeInfo . "\n";
        $message .= "💰 Цена: " . Yii::$app->formatter->asCurrency($product->price, 'BYN') . "\n";
        
        if (!empty($data['comment'])) {
            $message .= "\n💬 Комментарий: " . $data['comment'] . "\n";
        }

        $message .= "\n🔗 Ссылка: " . \yii\helpers\Url::to(['catalog/product', 'slug' => $product->slug], true);

        try {
            Yii::$app->mailer->compose()
                ->setFrom([Yii::$app->params['senderEmail'] => Yii::$app->params['senderName']])
                ->setTo(Yii::$app->params['adminEmail'])
                ->setSubject('⚡ Быстрый заказ: ' . $product->name)
                ->setTextBody($message)
                ->send();

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
     * Декодирование JSON параметра
     */
    protected function decodeJsonParam($value)
    {
        if (!$value) {
            return null;
        }
        if (is_string($value)) {
            $decoded = json_decode($value, true);
            return $decoded !== null ? $decoded : $value;
        }
        return $value;
    }

    /**
     * Применить сортировку
     */
    protected function applySorting($query, string $sort)
    {
        switch ($sort) {
            case 'price_asc':
                return $query->orderBy(['price' => SORT_ASC]);
            case 'price_desc':
                return $query->orderBy(['price' => SORT_DESC]);
            case 'new':
                return $query->orderBy(['created_at' => SORT_DESC]);
            case 'rating':
                return $query->orderBy(['rating' => SORT_DESC]);
            case 'discount':
                return $query->andWhere(['>', 'old_price', 0])
                             ->orderBy(['(old_price - price) / old_price' => SORT_DESC]);
            case 'popular':
            default:
                return $query->orderBy(['views_count' => SORT_DESC]);
        }
    }

    /**
     * Построить текущие фильтры из запроса
     */
    protected function buildCurrentFilters($request): array
    {
        return [
            'brands' => $this->decodeJsonParam($request->post('brands')) ?: [],
            'categories' => $this->decodeJsonParam($request->post('categories')) ?: [],
            'sizes' => $this->decodeJsonParam($request->post('sizes')) ?: [],
            'size_system' => $request->post('sizeSystem', 'eu'),
            'price_from' => $request->post('price_from'),
            'price_to' => $request->post('price_to'),
            'colors' => $this->decodeJsonParam($request->post('colors')) ?: [],
            'discount_any' => $request->post('discount_any'),
            'discount_range' => $this->decodeJsonParam($request->post('discount_range')) ?: [],
            'rating' => $request->post('rating'),
            'conditions' => $this->decodeJsonParam($request->post('conditions')) ?: [],
        ];
    }
}
