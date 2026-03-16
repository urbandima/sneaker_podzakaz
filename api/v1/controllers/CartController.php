<?php

namespace app\api\v1\controllers;

use yii\rest\Controller;
use yii\web\Response;
use app\backend\modules\cart\services\CartService;

/**
 * API контроллер корзины
 */
class CartController extends Controller
{
    private CartService $cartService;

    public function __construct($id, $module, CartService $cartService, $config = [])
    {
        $this->cartService = $cartService;
        parent::__construct($id, $module, $config);
    }

    public function behaviors()
    {
        $behaviors = parent::behaviors();
        $behaviors['contentNegotiator']['formats'] = [
            'application/json' => Response::FORMAT_JSON,
        ];
        return $behaviors;
    }

    /**
     * GET /api/v1/cart - Получить корзину
     */
    public function actionIndex()
    {
        return $this->cartService->getCart();
    }

    /**
     * POST /api/v1/cart/add - Добавить товар
     */
    public function actionAdd()
    {
        $productId = \Yii::$app->request->post('product_id');
        $sizeId = \Yii::$app->request->post('size_id');
        $quantity = \Yii::$app->request->post('quantity', 1);

        return $this->cartService->add($productId, $sizeId, $quantity);
    }

    /**
     * DELETE /api/v1/cart/remove - Удалить товар
     */
    public function actionRemove()
    {
        $itemId = \Yii::$app->request->post('item_id');
        return $this->cartService->remove($itemId);
    }

    /**
     * POST /api/v1/cart/clear - Очистить корзину
     */
    public function actionClear()
    {
        return $this->cartService->clear();
    }
}
