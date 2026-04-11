<?php

namespace app\api\v1\controllers;

use yii\rest\Controller;
use yii\web\Response;
use app\backend\modules\catalog\models\Product;
use app\api\v1\resources\ProductResource;

/**
 * API контроллер товаров
 */
class ProductController extends Controller
{
    public $modelClass = Product::class;

    public function behaviors()
    {
        $behaviors = parent::behaviors();
        $behaviors['contentNegotiator']['formats'] = [
            'application/json' => Response::FORMAT_JSON,
        ];
        $behaviors['rateLimiter'] = [
            'class' => \yii\filters\RateLimiter::class,
            'enableRateLimitHeaders' => true,
        ];
        return $behaviors;
    }

    /**
     * GET /api/v1/products - Список товаров
     */
    public function actionIndex()
    {
        $query = Product::find()
            ->active()
            ->with(['brand', 'category', 'sizes']);

        // Фильтрация (приведение к int для защиты от SQL-инъекций)
        if ($categoryId = \Yii::$app->request->get('category_id')) {
            $query->andWhere(['category_id' => (int) $categoryId]);
        }
        if ($brandId = \Yii::$app->request->get('brand_id')) {
            $query->andWhere(['brand_id' => (int) $brandId]);
        }

        // Пагинация
        $dataProvider = new \yii\data\ActiveDataProvider([
            'query' => $query,
            'pagination' => [
                'pageSize' => 20,
            ],
        ]);

        return ProductResource::collection($dataProvider);
    }

    /**
     * GET /api/v1/products/{id} - Детали товара
     */
    public function actionView($id)
    {
        $product = Product::find()
            ->active()
            ->with(['brand', 'category', 'sizes', 'images'])
            ->andWhere(['id' => $id])
            ->one();

        if (!$product) {
            throw new \yii\web\NotFoundHttpException('Товар не найден');
        }

        return new ProductResource($product);
    }

    /**
     * GET /api/v1/products/search - Поиск товаров
     */
    public function actionSearch()
    {
        $query = \Yii::$app->request->get('q', '');
        
        if (strlen($query) < 2) {
            return [];
        }

        $products = Product::find()
            ->active()
            ->andWhere(['or',
                ['like', 'name', $query],
                ['like', 'sku', $query],
            ])
            ->limit(10)
            ->all();

        return ProductResource::collection($products);
    }

    /**
     * GET /api/v1/product/{id}/quick-view - Quick View данные
     */
    public function actionQuickView($id)
    {
        $product = Product::find()
            ->active()
            ->with(['brand', 'category', 'sizes', 'images'])
            ->andWhere(['id' => $id])
            ->one();

        if (!$product) {
            return [
                'success' => false,
                'message' => 'Товар не найден'
            ];
        }

        $images = [];
        foreach ($product->images as $img) {
            $images[] = $img->getUrl();
        }

        $sizes = [];
        foreach ($product->sizes as $size) {
            $sizes[] = [
                'value' => $size->size_value,
                'stock' => $size->stock,
            ];
        }

        return [
            'success' => true,
            'product' => [
                'id' => $product->id,
                'name' => $product->name,
                'brand' => $product->brand->name,
                'price' => $product->price,
                'old_price' => $product->old_price,
                'image' => $product->getMainImageUrl(),
                'images' => $images,
                'sizes' => $sizes,
                'description' => $product->description,
                'url' => $product->getUrl(),
                'rating' => $product->rating ?? 4.5,
                'reviews_count' => $product->reviews_count ?? 0,
            ],
        ];
    }
}
