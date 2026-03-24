<?php

/**
 * ReviewController — Публичный контроллер отзывов о товарах
 * 
 * НАЗНАЧЕНИЕ:
 * Позволяет клиентам оставлять отзывы о товарах, просматривать отзывы других покупателей.
 * 
 * ФУНКЦИИ:
 * - create() - создание отзыва о товаре
 * - list() - список отзывов товара
 * - helpful() - отметить отзыв полезным
 * 
 * ДОСТУП:
 * - Авторизованные пользователи (для создания)
 * - Все пользователи (для просмотра)
 */
namespace app\backend\modules\catalog\controllers;

use Yii;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\web\Response;
use app\backend\modules\catalog\models\ProductReview;
use app\backend\modules\catalog\models\Product;

class ReviewController extends Controller
{
    public $layout = 'main';

    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'only' => ['create', 'helpful'],
                'rules' => [
                    [
                        'allow' => true,
                        'actions' => ['create', 'helpful'],
                        'roles' => ['@'],
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'create' => ['POST'],
                    'helpful' => ['POST'],
                ],
            ],
        ];
    }

    /**
     * Создание отзыва о товаре
     */
    public function actionCreate()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        
        $productId = Yii::$app->request->post('product_id');
        $rating = Yii::$app->request->post('rating');
        $comment = Yii::$app->request->post('comment');
        $advantages = Yii::$app->request->post('advantages');
        $disadvantages = Yii::$app->request->post('disadvantages');
        
        // Валидация
        if (!$productId || !$rating || !$comment) {
            return [
                'success' => false,
                'message' => 'Заполните все обязательные поля',
            ];
        }
        
        $product = Product::findOne($productId);
        if (!$product) {
            return [
                'success' => false,
                'message' => 'Товар не найден',
            ];
        }
        
        // Проверка, не оставлял ли пользователь уже отзыв
        $customerId = Yii::$app->session->get('customer_id');
        if (!$customerId) {
            return [
                'success' => false,
                'message' => 'Необходимо авторизоваться',
            ];
        }
        
        $existingReview = ProductReview::find()
            ->where(['product_id' => $productId, 'customer_id' => $customerId])
            ->one();
            
        if ($existingReview) {
            return [
                'success' => false,
                'message' => 'Вы уже оставили отзыв на этот товар',
            ];
        }
        
        // Создание отзыва
        $review = new ProductReview();
        $review->product_id = $productId;
        $review->customer_id = $customerId;
        $review->rating = (int)$rating;
        $review->comment = $comment;
        $review->advantages = $advantages;
        $review->disadvantages = $disadvantages;
        $review->status = 'pending'; // На модерации
        $review->created_at = date('Y-m-d H:i:s');
        
        if ($review->save()) {
            return [
                'success' => true,
                'message' => 'Спасибо за отзыв! Он будет опубликован после модерации.',
            ];
        }
        
        return [
            'success' => false,
            'message' => 'Ошибка при сохранении отзыва',
            'errors' => $review->errors,
        ];
    }

    /**
     * Список отзывов товара
     */
    public function actionList($productId)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        
        $reviews = ProductReview::find()
            ->where(['product_id' => $productId, 'status' => 'published'])
            ->with(['customer'])
            ->orderBy(['created_at' => SORT_DESC])
            ->all();
        
        $result = [];
        foreach ($reviews as $review) {
            $result[] = [
                'id' => $review->id,
                'rating' => $review->rating,
                'comment' => $review->comment,
                'advantages' => $review->advantages,
                'disadvantages' => $review->disadvantages,
                'customer_name' => $review->customer->name ?? 'Покупатель',
                'created_at' => Yii::$app->formatter->asDate($review->created_at, 'php:d.m.Y'),
                'helpful_count' => $review->helpful_count ?? 0,
            ];
        }
        
        return [
            'success' => true,
            'reviews' => $result,
            'total' => count($result),
        ];
    }

    /**
     * Отметить отзыв как полезный
     */
    public function actionHelpful()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        
        $reviewId = Yii::$app->request->post('review_id');
        
        $review = ProductReview::findOne($reviewId);
        if (!$review) {
            return [
                'success' => false,
                'message' => 'Отзыв не найден',
            ];
        }
        
        // Увеличиваем счётчик полезности
        $review->helpful_count = ($review->helpful_count ?? 0) + 1;
        $review->save(false);
        
        return [
            'success' => true,
            'helpful_count' => $review->helpful_count,
        ];
    }
}
