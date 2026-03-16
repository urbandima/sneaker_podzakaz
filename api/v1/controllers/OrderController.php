<?php

namespace app\api\v1\controllers;

use yii\rest\Controller;
use yii\web\Response;
use app\backend\modules\checkout\services\OrderService;
use app\api\v1\resources\OrderResource;

/**
 * API контроллер заказов
 */
class OrderController extends Controller
{
    private OrderService $orderService;

    public function __construct($id, $module, OrderService $orderService, $config = [])
    {
        $this->orderService = $orderService;
        parent::__construct($id, $module, $config);
    }

    public function behaviors()
    {
        $behaviors = parent::behaviors();
        $behaviors['contentNegotiator']['formats'] = [
            'application/json' => Response::FORMAT_JSON,
        ];
        $behaviors['authenticator'] = [
            'class' => \yii\filters\auth\HttpBearerAuth::class,
        ];
        return $behaviors;
    }

    /**
     * GET /api/v1/orders - Список заказов пользователя
     */
    public function actionIndex()
    {
        $orders = $this->orderService->getUserOrders(\Yii::$app->user->id);
        return OrderResource::collection($orders);
    }

    /**
     * GET /api/v1/orders/{id} - Детали заказа
     */
    public function actionView($id)
    {
        $order = $this->orderService->getOrder($id, \Yii::$app->user->id);
        
        if (!$order) {
            throw new \yii\web\NotFoundHttpException('Заказ не найден');
        }

        return new OrderResource($order);
    }

    /**
     * POST /api/v1/orders/create - Создать заказ
     */
    public function actionCreate()
    {
        $data = \Yii::$app->request->bodyParams;
        $order = $this->orderService->create($data);

        if ($order) {
            return new OrderResource($order);
        }

        return ['error' => 'Ошибка создания заказа'];
    }
}
