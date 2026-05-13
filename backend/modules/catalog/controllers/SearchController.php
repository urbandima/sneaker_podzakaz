<?php

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
    public $layout = 'public';

    public function getViewPath(): string
    {
        return Yii::getAlias('@frontend/views/catalog');
    }

    /**
     * Страница результатов поиска с FULLTEXT поиском, фасетами, логированием, <mark>-подсветкой.
     * URL: /search?q=query[&brand[]=&category[]=&price_from=&price_to=&sort=]
     */
    public function actionIndex()
    {
        $q           = trim(Yii::$app->request->get('q', ''));
        $tagSlug     = Yii::$app->request->get('tag', '');
        $sort        = Yii::$app->request->get('sort', 'relevance');
        $brandIds    = array_filter(array_map('intval', (array)Yii::$app->request->get('brand', [])));
        $categoryIds = array_filter(array_map('intval', (array)Yii::$app->request->get('category', [])));
        $priceFrom   = Yii::$app->request->get('price_from');
        $priceTo     = Yii::$app->request->get('price_to');

        $productQuery = Product::find()
            ->where(['is_active' => true])
            ->with(['images', 'brand', 'category']);

        // FULLTEXT search with LIKE fallback
        $useFulltext = false;
        if (!empty($q) && strlen($q) >= 2) {
            $useFulltext = $this->tryFulltext($productQuery, $q, $sort);
            if (!$useFulltext) {
                $this->applyLikeSearch($productQuery, $q);
            }
        }

        // Tag filter
        $currentTag = null;
        if (!empty($tagSlug)) {
            $currentTag = ProductTag::findBySlug($tagSlug);
            if ($currentTag) {
                $ids = ProductTagAssignment::getProductIdsByTag($currentTag->id);
                $productQuery->andWhere(['product.id' => $ids]);
            }
        }

        // Facet filters
        if (!empty($brandIds)) {
            $productQuery->andWhere(['brand_id' => $brandIds]);
        }
        if (!empty($categoryIds)) {
            $productQuery->andWhere(['category_id' => $categoryIds]);
        }
        if ($priceFrom !== null && $priceFrom !== '') {
            $productQuery->andWhere(['>=', 'price', (float)$priceFrom]);
        }
        if ($priceTo !== null && $priceTo !== '') {
            $productQuery->andWhere(['<=', 'price', (float)$priceTo]);
        }

        // Sort (when not using FULLTEXT relevance ordering)
        if (!$useFulltext || $sort !== 'relevance') {
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
                    if (!$useFulltext) {
                        $productQuery->orderBy(['views_count' => SORT_DESC, 'id' => SORT_DESC]);
                    }
            }
        }

        $totalCount = $productQuery->count();

        // Log the search query
        if (!empty($q)) {
            $this->logSearch($q, (int)$totalCount);
        }

        $pagination = new Pagination([
            'defaultPageSize' => 24,
            'pageSizeLimit'   => [1, 48],
            'totalCount'      => $totalCount,
        ]);

        $products = $productQuery
            ->offset($pagination->offset)
            ->limit($pagination->limit)
            ->all();

        // <mark>-highlight for display
        $highlightedNames = [];
        if (!empty($q)) {
            foreach ($products as $product) {
                $highlightedNames[$product->id] = $this->highlight($product->name, $q);
            }
        }

        // Levenshtein suggestion when no results
        $suggestion = null;
        if ($totalCount === 0 && !empty($q)) {
            $suggestion = $this->getLevenshteinSuggestion($q);
        }

        // Facet data for sidebar
        $facets = $this->buildFacets($q, $tagSlug);

        // Popular tags for sidebar
        $popularTags = ProductTag::find()
            ->where(['is_active' => true])
            ->orderBy(['sort_order' => SORT_ASC])
            ->limit(20)
            ->all();

        // Popular searches from search_log
        $popularSearches = $this->getPopularSearches();

        return $this->render('search/index', [
            'products'         => $products,
            'pagination'       => $pagination,
            'query'            => $q,
            'currentTag'       => $currentTag,
            'popularTags'      => $popularTags,
            'sort'             => $sort,
            'totalCount'       => $totalCount,
            'highlightedNames' => $highlightedNames,
            'suggestion'       => $suggestion,
            'facets'           => $facets,
            'currentBrandIds'  => $brandIds,
            'currentCatIds'    => $categoryIds,
            'priceFrom'        => $priceFrom,
            'priceTo'          => $priceTo,
            'popularSearches'  => $popularSearches,
        ]);
    }

    /**
     * AJAX autocomplete
     */
    public function actionAjax()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $q     = Yii::$app->request->get('q', '');
        $limit = min((int)Yii::$app->request->get('limit', 5), 10);

        if (empty($q) || strlen($q) < 2) {
            return ['results' => [], 'total' => 0];
        }

        $products = Product::find()
            ->select(['id', 'name', 'slug', 'price', 'main_image_url', 'brand_name'])
            ->where(['is_active' => true])
            ->andWhere([
                'or',
                ['like', 'name', $q],
                ['like', 'sku', $q],
                ['like', 'brand_name', $q],
                ['like', 'model_name', $q],
            ])
            ->orderBy(['views_count' => SORT_DESC])
            ->limit($limit)
            ->asArray()
            ->all();

        $results = array_map(function ($p) {
            return [
                'id'    => $p['id'],
                'name'  => $p['name'],
                'slug'  => $p['slug'],
                'price' => $p['price'],
                'image' => $p['main_image_url'],
                'brand' => $p['brand_name'],
                'url'   => Yii::$app->urlManager->createUrl(['/catalog/catalog/product', 'slug' => $p['slug']]),
            ];
        }, $products);

        return ['results' => $results, 'total' => count($results), 'query' => $q];
    }

    /**
     * Browse by tag
     */
    public function actionTag($slug)
    {
        $tag = ProductTag::findBySlug($slug);
        if (!$tag) {
            throw new \yii\web\NotFoundHttpException('Тег не найден');
        }

        $ids   = ProductTagAssignment::getProductIdsByTag($tag->id);
        $query = Product::find()
            ->where(['id' => $ids, 'is_active' => true])
            ->with(['images', 'brand']);

        $pagination = new Pagination([
            'defaultPageSize' => 24,
            'pageSizeLimit'   => [1, 48],
            'totalCount'      => $query->count(),
        ]);

        $products = $query
            ->offset($pagination->offset)
            ->limit($pagination->limit)
            ->orderBy(['created_at' => SORT_DESC])
            ->all();

        $relatedTags = ProductTag::find()
            ->where(['is_active' => true])
            ->andWhere(['!=', 'id', $tag->id])
            ->orderBy(['sort_order' => SORT_ASC])
            ->limit(15)
            ->all();

        return $this->render('search/tag', [
            'tag'         => $tag,
            'products'    => $products,
            'pagination'  => $pagination,
            'relatedTags' => $relatedTags,
            'totalCount'  => $pagination->totalCount,
        ]);
    }

    /**
     * Live search header dropdown
     */
    public function actionLive()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $q = Yii::$app->request->get('q', '');
        if (empty($q) || strlen($q) < 2) {
            return ['html' => '', 'count' => 0];
        }

        $products = Product::find()
            ->where(['is_active' => true])
            ->andWhere([
                'or',
                ['like', 'name', $q],
                ['like', 'sku', $q],
                ['like', 'brand_name', $q],
            ])
            ->orderBy(['views_count' => SORT_DESC])
            ->limit(8)
            ->all();

        $html = $this->renderPartial('search/_search-results', [
            'products' => $products,
            'query'    => $q,
        ]);

        return ['html' => $html, 'count' => count($products), 'query' => $q];
    }

    /**
     * Suggestions endpoint
     */
    public function actionSuggestions()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $q = Yii::$app->request->get('q', '');
        if (empty($q) || strlen($q) < 2) {
            return ['suggestions' => [], 'popular' => $this->getPopularSearches(5)];
        }

        $products = Product::find()
            ->select(['name'])
            ->where(['is_active' => true])
            ->andWhere(['like', 'name', $q])
            ->orderBy(['views_count' => SORT_DESC])
            ->limit(5)
            ->column();

        $tags = ProductTag::find()
            ->select(['name'])
            ->where(['is_active' => true])
            ->andWhere(['like', 'name', $q])
            ->limit(3)
            ->column();

        $suggestions = array_values(array_unique(array_merge($products, $tags)));

        return ['suggestions' => $suggestions, 'popular' => $this->getPopularSearches(5)];
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    /**
     * Try FULLTEXT MATCH AGAINST. Returns true if query was applied, false if index unavailable.
     * Probes the index with a dummy query so the exception is caught here, not at count() time.
     */
    private function tryFulltext(\yii\db\ActiveQuery $query, string $q, string $sort): bool
    {
        try {
            $escaped = Yii::$app->db->quoteValue('+' . implode('* +', preg_split('/\s+/', trim($q))) . '*');
            $relevanceExpr = "MATCH(`name`, `description`, `brand_name`, `model_name`) AGAINST({$escaped} IN BOOLEAN MODE)";
            // Probe the FULLTEXT index before modifying the caller's query; catches error 1191 here.
            Yii::$app->db->createCommand(
                "SELECT {$relevanceExpr} FROM {{%product}} LIMIT 1"
            )->queryScalar();
            $query->addSelect(['product.*', new \yii\db\Expression("{$relevanceExpr} AS relevance_score")]);
            $query->andWhere(new \yii\db\Expression("{$relevanceExpr} > 0"));
            if ($sort === 'relevance') {
                $query->orderBy(['relevance_score' => SORT_DESC, 'views_count' => SORT_DESC]);
            }
            return true;
        } catch (\Exception $e) {
            Yii::warning('FULLTEXT search unavailable, falling back to LIKE: ' . $e->getMessage(), __METHOD__);
            return false;
        }
    }

    private function applyLikeSearch(\yii\db\ActiveQuery $query, string $q): void
    {
        $query->andWhere([
            'or',
            ['like', 'name', $q],
            ['like', 'description', $q],
            ['like', 'brand_name', $q],
            ['like', 'model_name', $q],
        ]);
    }

    /**
     * Wrap query terms in <mark> tags for display.
     */
    private function highlight(string $text, string $q): string
    {
        $words   = preg_split('/\s+/', trim($q));
        $pattern = implode('|', array_map(fn($w) => preg_quote($w, '/'), $words));
        return preg_replace("/({$pattern})/ui", '<mark>$1</mark>', htmlspecialchars($text, ENT_QUOTES, 'UTF-8'));
    }

    /**
     * Find closest product name via Levenshtein distance.
     */
    private function getLevenshteinSuggestion(string $q): ?string
    {
        $names = Product::find()
            ->select(['name'])
            ->where(['is_active' => true])
            ->limit(500)
            ->column();

        $best      = null;
        $bestScore = PHP_INT_MAX;
        $qLower    = mb_strtolower($q);

        foreach ($names as $name) {
            $nameLower = mb_strtolower($name);
            // Compare first word to query for speed
            $firstWord = explode(' ', $nameLower)[0];
            $dist      = levenshtein($qLower, $firstWord);
            if ($dist < $bestScore && $dist <= max(2, intval(strlen($q) / 4))) {
                $bestScore = $dist;
                $best      = $name;
            }
        }

        return $best;
    }

    /**
     * Build facet counts for brand and category filters.
     */
    private function buildFacets(string $q, string $tagSlug): array
    {
        $base = Product::find()->select(['brand_id', 'category_id'])->where(['is_active' => true]);
        if (!empty($q)) {
            try {
                $escaped = Yii::$app->db->quoteValue('+' . implode('* +', preg_split('/\s+/', trim($q))) . '*');
                $expr    = "MATCH(`name`, `description`, `brand_name`, `model_name`) AGAINST({$escaped} IN BOOLEAN MODE)";
                $base->andWhere(new \yii\db\Expression("{$expr} > 0"));
            } catch (\Exception $e) {
                $base->andWhere(['or', ['like', 'name', $q], ['like', 'brand_name', $q]]);
            }
        }

        // Brand facets
        $brandRows = (clone $base)
            ->select(['brand_id', 'brand_name', 'COUNT(*) AS cnt'])
            ->groupBy(['brand_id', 'brand_name'])
            ->asArray()
            ->all();

        // Category facets
        $catRows = (clone $base)
            ->select(['category_id', 'category_name', 'COUNT(*) AS cnt'])
            ->groupBy(['category_id', 'category_name'])
            ->asArray()
            ->all();

        // Price range
        $priceRow = (clone $base)
            ->select(['MIN(price) AS min_price', 'MAX(price) AS max_price'])
            ->asArray()
            ->one();

        return [
            'brands'     => $brandRows,
            'categories' => $catRows,
            'priceMin'   => (float)($priceRow['min_price'] ?? 0),
            'priceMax'   => (float)($priceRow['max_price'] ?? 100000),
        ];
    }

    /**
     * Log a search query to search_log table.
     */
    private function logSearch(string $q, int $resultsCount): void
    {
        try {
            Yii::$app->db->createCommand()->insert('search_log', [
                'query'         => mb_substr($q, 0, 255),
                'results_count' => $resultsCount,
                'user_id'       => Yii::$app->user->isGuest ? null : Yii::$app->user->id,
                'session_id'    => substr(Yii::$app->session->getId(), 0, 64),
                'ip'            => Yii::$app->request->userIP,
                'created_at'    => time(),
            ])->execute();
        } catch (\Exception $e) {
            // Table may not exist yet — fail silently
        }
    }

    /**
     * Popular search queries from search_log (last 7 days).
     */
    private function getPopularSearches(int $limit = 8): array
    {
        try {
            return Yii::$app->db->createCommand(
                'SELECT query, COUNT(*) AS cnt
                 FROM search_log
                 WHERE created_at >= :since AND results_count > 0
                 GROUP BY query
                 ORDER BY cnt DESC
                 LIMIT :lim',
                [':since' => time() - 7 * 86400, ':lim' => $limit]
            )->queryAll();
        } catch (\Exception $e) {
            return [];
        }
    }
}
