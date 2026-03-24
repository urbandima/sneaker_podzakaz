<?php

/**
 * CompareController — Контроллер сравнения товаров
 * 
 * НАЗНАЧЕНИЕ:
 * Управление списком сравнения товаров: добавление, удаление, просмотр.
 * 
 * ФУНКЦИИ:
 * - index() - страница сравнения товаров
 * - add() - добавить товар в сравнение
 * - remove() - удалить товар из сравнения
 * - clear() - очистить список сравнения
 * - count() - получить количество товаров в сравнении
 */
namespace app\backend\modules\compare\controllers;

use Yii;
use yii\web\Controller;
use yii\web\Response;
use app\backend\modules\catalog\models\Product;

class CompareController extends Controller
{
    public $layout = 'main';

    /**
     * Страница сравнения товаров
     */
    public function actionIndex()
    {
        $compareIds = Yii::$app->session->get('compare', []);
        
        $products = [];
        if (!empty($compareIds)) {
            $products = Product::find()
                ->where(['id' => $compareIds, 'status' => 1])
                ->with(['brand', 'category', 'images', 'characteristics'])
                ->all();
        }
        
        return $this->render('index', [
            'products' => $products,
        ]);
    }

    /**
     * Добавить товар в сравнение
     */
    public function actionAdd()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        
        $productId = Yii::$app->request->post('product_id');
        
        if (!$productId) {
            return [
                'success' => false,
                'message' => 'ID товара не указан',
            ];
        }
        
        $product = Product::findOne($productId);
        if (!$product) {
            return [
                'success' => false,
                'message' => 'Товар не найден',
            ];
        }
        
        $compare = Yii::$app->session->get('compare', []);
        
        if (in_array($productId, $compare)) {
            return [
                'success' => false,
                'message' => 'Товар уже в сравнении',
            ];
        }
        
        // Ограничение: максимум 4 товара для сравнения
        if (count($compare) >= 4) {
            return [
                'success' => false,
                'message' => 'Можно сравнивать не более 4 товаров',
            ];
        }
        
        $compare[] = $productId;
        Yii::$app->session->set('compare', $compare);
        
        return [
            'success' => true,
            'message' => 'Товар добавлен в сравнение',
            'count' => count($compare),
        ];
    }

    /**
     * Удалить товар из сравнения
     */
    public function actionRemove()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        
        $productId = Yii::$app->request->post('product_id');
        
        $compare = Yii::$app->session->get('compare', []);
        $compare = array_diff($compare, [$productId]);
        Yii::$app->session->set('compare', array_values($compare));
        
        return [
            'success' => true,
            'message' => 'Товар удалён из сравнения',
            'count' => count($compare),
        ];
    }

    /**
     * Очистить список сравнения
     */
    public function actionClear()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        
        Yii::$app->session->remove('compare');
        
        return [
            'success' => true,
            'message' => 'Список сравнения очищен',
            'count' => 0,
        ];
    }

    /**
     * Получить количество товаров в сравнении
     */
    public function actionCount()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        
        $compare = Yii::$app->session->get('compare', []);
        
        return [
            'success' => true,
            'count' => count($compare),
        ];
    }
}
