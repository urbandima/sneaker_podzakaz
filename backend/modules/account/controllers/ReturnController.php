<?php

/**
 * ReturnController — Контроллер возвратов для клиентов
 * 
 * НАЗНАЧЕНИЕ:
 * Управление возвратами в личном кабинете: создание заявок,
 * просмотр статуса, список возвратов.
 * 
 * ФУНКЦИИ:
 * - index() - список заявок на возврат
 * - view() - просмотр заявки
 * - create() - создание новой заявки
 */
namespace app\backend\modules\account\controllers;

use Yii;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\AccessControl;
use yii\data\ActiveDataProvider;
use app\backend\modules\return\models\ReturnRequest;
use app\backend\modules\return\services\ReturnService;
use app\backend\modules\checkout\models\Order;

class ReturnController extends Controller
{
    public $layout = 'main';

    /**
     * Behaviors
     */
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    [
                        'allow' => true,
                        'roles' => ['@'], // Только для авторизованных
                    ],
                ],
            ],
        ];
    }

    /**
     * Список заявок на возврат
     */
    public function actionIndex()
    {
        $customerId = Yii::$app->user->id;
        
        $query = ReturnRequest::find()
            ->where(['customer_id' => $customerId])
            ->orderBy(['created_at' => SORT_DESC]);
        
        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => [
                'pageSize' => 10,
            ],
        ]);

        return $this->render('index', [
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * Просмотр заявки на возврат
     */
    public function actionView($id)
    {
        $model = $this->findModel($id);
        
        // Проверяем, что заявка принадлежит текущему пользователю
        if ($model->customer_id != Yii::$app->user->id) {
            throw new NotFoundHttpException('Заявка не найдена');
        }

        return $this->render('view', [
            'model' => $model,
        ]);
    }

    /**
     * Создание заявки на возврат
     */
    public function actionCreate($order_id)
    {
        $order = Order::findOne($order_id);
        
        if (!$order) {
            throw new NotFoundHttpException('Заказ не найден');
        }
        
        // Проверяем, что заказ принадлежит текущему пользователю
        if ($order->customer_id != Yii::$app->user->id) {
            throw new NotFoundHttpException('Заказ не найден');
        }
        
        // Проверяем возможность возврата
        $returnService = new ReturnService();
        list($canReturn, $error) = $returnService->canReturn($order);
        
        if (!$canReturn) {
            Yii::$app->session->setFlash('error', $error);
            return $this->redirect(['/account/orders/view', 'id' => $order_id]);
        }
        
        if (Yii::$app->request->isPost) {
            $items = Yii::$app->request->post('items', []);
            $reason = Yii::$app->request->post('reason');
            $comment = Yii::$app->request->post('comment');
            $pickupAddress = Yii::$app->request->post('pickup_address');
            
            if (empty($items)) {
                Yii::$app->session->setFlash('error', 'Выберите товары для возврата');
            } elseif (empty($reason)) {
                Yii::$app->session->setFlash('error', 'Укажите причину возврата');
            } else {
                $request = $returnService->createReturn(
                    $order_id,
                    $items,
                    $reason,
                    $comment,
                    $pickupAddress
                );
                
                if ($request) {
                    Yii::$app->session->setFlash('success', 'Заявка на возврат создана. Номер заявки: ' . $request->return_number);
                    return $this->redirect(['view', 'id' => $request->id]);
                } else {
                    Yii::$app->session->setFlash('error', $returnService->getErrorMessage());
                }
            }
        }

        return $this->render('create', [
            'order' => $order,
        ]);
    }

    /**
     * Найти модель по ID
     */
    protected function findModel($id)
    {
        if (($model = ReturnRequest::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('Заявка не найдена');
    }
}
