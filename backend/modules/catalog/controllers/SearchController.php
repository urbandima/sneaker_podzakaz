<?php

/**
 * SearchController — Контроллер поиска товаров
 * 
 * НАЗНАЧЕНИЕ:
 * Отдельный контроллер для поиска товаров в каталоге.
 * Вынесен из CatalogController для лучшей организации кода.
 * 
 * ФУНКЦИИ:
 * - Страница результатов поиска (index)
 * - AJAX поиск для автодополнения (ajax)
 * - Поиск по тегам (tag)
 * - Живой поиск в шапке (live)
 * 
 * СВЯЗИ:
 * - Product (модель товара)
 * - ProductTag (модель тега)
 * - SearchService (сервис поиска)
 */

namespace app\backend\modules\catalog\controllers;

use Yii;
use yii\web\Controller;
use yii\web\Response;
use yii\data\Pagination;
use app\backend\modules\catalog\models\Product;
use app\backend\modules\catalog\models\ProductTag;
use app\backend\modules\catalog\models\ProductTagAssignment;

class SearchController extends Controller
{
    public $layout = 'main';

    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        return [
            [
                'class' => \yii\filters\PageCache::class,
                'only' => ['index'],
                'duration' => 60,
                'variations' => [
                    Yii::$app->request->get('q'),
                    Yii::$app->request->get('page'),
                ],
            ],
        ];
    }

    /**
     * Страница результатов поиска
     * URL: /search?q=query
     */
    public function actionIndex()
    {
        $query = Yii::$app->request->get('q', '');
        $tagSlug = Yii::$app->request->get('tag', '');
        
        $productQuery = Product::find()
            ->where(['is_active' => true])
            ->with(['images', 'sizes', 'brand', 'category']);

        // Поиск по тексту
        if (!empty($query)) {
            $productQuery->andWhere([
                'or',
                ['like', 'name', $query],
                ['like', 'description', $query],
                ['like', 'brand_name', $query],
                ['like', 'sku', $query],
                ['like', 'model_name', $query],
            ]);
        }

        // Фильтрация по тегу
        $currentTag = null;
        if (!empty($tagSlug)) {
            $currentTag = ProductTag::findBySlug($tagSlug);
            if ($currentTag) {
                $productIds = ProductTagAssignment::getProductIdsByTag($currentTag->id);
                $productQuery->andWhere(['id' => $productIds]);
            }
        }

        // Сортировка
        $sort = Yii::$app->request->get('sort', 'relevance');
        switch ($sort) {
            case 'price_asc':
                $productQuery->orderBy(['price' => SORT_ASC]);
                break;
            case 'price_desc':
                $productQuery->orderBy(['price' => SORT_DESC]);
                break;
            case 'new':
                $productQuery->orderBy(['created_at' => SORT_DESC]);
                break;
            case 'popular':
                $productQuery->orderBy(['views_count' => SORT_DESC]);
                break;
            default:
                // relevance - сортировка по релевантности (умолчанию по ID)
                $productQuery->orderBy(['id' => SORT_DESC]);
        }

        // Пагинация
        $pagination = new Pagination([
            'defaultPageSize' => 24,
            'pageSizeLimit' => [1, 48],
            'totalCount' => $productQuery->count(),
        ]);

        $products = $productQuery->offset($pagination->offset)
            ->limit($pagination->limit)
            ->all();

        // Популярные теги для боковой панели
        $popularTags = ProductTag::find()
            ->where(['is_active' => true])
            ->orderBy(['sort_order' => SORT_ASC])
            ->limit(20)
            ->all();

        return $this->render('index', [
            'products' => $products,
            'pagination' => $pagination,
            'query' => $query,
            'currentTag' => $currentTag,
            'popularTags' => $popularTags,
            'sort' => $sort,
            'totalCount' => $pagination->totalCount,
        ]);
    }

    /**
     * AJAX поиск для автодополнения
     * URL: /search/ajax?q=query
     */
    public function actionAjax()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        
        $query = Yii::$app->request->get('q', '');
        $limit = min((int)Yii::$app->request->get('limit', 5), 10);

        if (empty($query) || strlen($query) < 2) {
            return ['results' => [], 'total' => 0];
        }

        $products = Product::find()
            ->select(['id', 'name', 'slug', 'price', 'main_image_url', 'brand_name'])
            ->where(['is_active' => true])
            ->andWhere([
                'or',
                ['like', 'name', $query],
                ['like', 'sku', $query],
                ['like', 'brand_name', $query],
                ['like', 'model_name', $query],
            ])
            ->orderBy(['views_count' => SORT_DESC])
            ->limit($limit)
            ->asArray()
            ->all();

        // Форматируем результаты
        $results = array_map(function($product) {
            return [
                'id' => $product['id'],
                'name' => $product['name'],
                'slug' => $product['slug'],
                'price' => $product['price'],
                'image' => $product['main_image_url'],
                'brand' => $product['brand_name'],
                'url' => Yii::$app->urlManager->createUrl(['/catalog/catalog/product', 'slug' => $product['slug']]),
            ];
        }, $products);

        return [
            'results' => $results,
            'total' => count($results),
            'query' => $query,
        ];
    }

    /**
     * Поиск по тегу
     * URL: /search/tag/{slug}
     */
    public function actionTag($slug)
    {
        $tag = ProductTag::findBySlug($slug);
        
        if (!$tag) {
            throw new \yii\web\NotFoundHttpException('Тег не найден');
        }

        $productIds = ProductTagAssignment::getProductIdsByTag($tag->id);
        
        $query = Product::find()
            ->where(['id' => $productIds, 'is_active' => true])
            ->with(['images', 'sizes', 'brand']);

        // Пагинация
        $pagination = new Pagination([
            'defaultPageSize' => 24,
            'pageSizeLimit' => [1, 48],
            'totalCount' => $query->count(),
        ]);

        $products = $query->offset($pagination->offset)
            ->limit($pagination->limit)
            ->orderBy(['created_at' => SORT_DESC])
            ->all();

        // Другие теги
        $relatedTags = ProductTag::find()
            ->where(['is_active' => true])
            ->andWhere(['!=', 'id', $tag->id])
            ->orderBy(['sort_order' => SORT_ASC])
            ->limit(15)
            ->all();

        return $this->render('tag', [
            'tag' => $tag,
            'products' => $products,
            'pagination' => $pagination,
            'relatedTags' => $relatedTags,
            'totalCount' => $pagination->totalCount,
        ]);
    }

    /**
     * Живой поиск для шапки сайта
     * URL: /search/live?q=query
     */
    public function actionLive()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        
        $query = Yii::$app->request->get('q', '');
        
        if (empty($query) || strlen($query) < 2) {
            return ['html' => '', 'count' => 0];
        }

        $products = Product::find()
            ->where(['is_active' => true])
            ->andWhere([
                'or',
                ['like', 'name', $query],
                ['like', 'sku', $query],
                ['like', 'brand_name', $query],
            ])
            ->orderBy(['views_count' => SORT_DESC])
            ->limit(8)
            ->all();

        $html = $this->renderPartial('_search-results', [
            'products' => $products,
            'query' => $query,
        ]);

        return [
            'html' => $html,
            'count' => count($products),
            'query' => $query,
        ];
    }

    /**
     * Подсказки для поиска (suggestions)
     * URL: /search/suggestions?q=query
     */
    public function actionSuggestions()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        
        $query = Yii::$app->request->get('q', '');
        
        if (empty($query) || strlen($query) < 2) {
            return ['suggestions' => []];
        }

        // Поиск товаров для подсказок
        $products = Product::find()
            ->select(['name'])
            ->where(['is_active' => true])
            ->andWhere(['like', 'name', $query])
            ->orderBy(['views_count' => SORT_DESC])
            ->limit(5)
            ->column();

        // Поиск тегов для подсказок
        $tags = ProductTag::find()
            ->select(['name'])
            ->where(['is_active' => true])
            ->andWhere(['like', 'name', $query])
            ->limit(3)
            ->column();

        // Объединяем и убираем дубликаты
        $suggestions = array_unique(array_merge($products, $tags));

        return ['suggestions' => array_values($suggestions)];
    }
}
