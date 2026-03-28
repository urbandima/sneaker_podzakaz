<?php

namespace app\api\v1\controllers;

use Yii;
use yii\rest\Controller;
use yii\web\Response;
use app\backend\modules\returns\models\ReturnRequest;
use app\backend\modules\returns\models\ReturnPolicy;

/**
 * Return API Controller
 * 
 * Endpoints:
 * - GET /api/v1/returns - Список возвратов клиента
 * - GET /api/v1/returns/{id} - Детали возврата
 * - POST /api/v1/returns/create - Создать заявку на возврат
 * - POST /api/v1/returns/{id}/cancel - Отменить заявку
 */
class ReturnController extends Controller
{
    /**
     * Список возвратов клиента
     * 
     * GET /api/v1/returns
     */
    public function actionIndex()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        
        $customerId = Yii::$app->user->id;
        
        if (!$customerId) {
            return [
                'success' => false,
                'message' => 'Требуется авторизация'
            ];
        }
        
        try {
            $returns = ReturnRequest::find()
                ->where(['customer_id' => $customerId])
                ->orderBy(['created_at' => SORT_DESC])
                ->all();
            
            $result = [];
            foreach ($returns as $return) {
                $result[] = [
                    'id' => $return->id,
                    'return_number' => $return->return_number,
                    'order_id' => $return->order_id,
                    'status' => $return->status,
                    'status_name' => $return->getStatusName(),
                    'reason' => $return->reason,
                    'refund_amount' => $return->refund_amount,
                    'created_at' => $return->created_at,
                    'tracking_number' => $return->tracking_number,
                ];
            }
            
            return [
                'success' => true,
                'returns' => $result
            ];
        } catch (\Exception $e) {
            Yii::error('Returns list error: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Ошибка при загрузке списка возвратов'
            ];
        }
    }
    
    /**
     * Детали возврата
     * 
     * GET /api/v1/returns/{id}
     */
    public function actionView($id)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        
        try {
            $return = ReturnRequest::findOne($id);
            
            if (!$return) {
                return [
                    'success' => false,
                    'message' => 'Заявка не найдена'
                ];
            }
            
            return [
                'success' => true,
                'return' => [
                    'id' => $return->id,
                    'return_number' => $return->return_number,
                    'order_id' => $return->order_id,
                    'status' => $return->status,
                    'status_name' => $return->getStatusName(),
                    'reason' => $return->reason,
                    'reason_details' => $return->reason_details,
                    'refund_amount' => $return->refund_amount,
                    'items' => $return->getItems(),
                    'tracking_number' => $return->tracking_number,
                    'admin_comment' => $return->admin_comment,
                    'created_at' => $return->created_at,
                    'updated_at' => $return->updated_at,
                ],
            ];
        } catch (\Exception $e) {
            Yii::error('Return view error: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Ошибка при загрузке заявки'
            ];
        }
    }
    
    /**
     * Создать заявку на возврат
     * 
     * POST /api/v1/returns/create
     * 
     * Body:
     * {
     *   "order_id": 123,
     *   "reason": "size_not_fit",
     *   "reason_details": "Мал размер",
     *   "items": [{"product_id": 1, "quantity": 1}]
     * }
     */
    public function actionCreate()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        
        $customerId = Yii::$app->user->id;
        
        if (!$customerId) {
            return [
                'success' => false,
                'message' => 'Требуется авторизация'
            ];
        }
        
        $request = Yii::$app->request;
        $orderId = $request->post('order_id');
        $reason = $request->post('reason');
        $reasonDetails = $request->post('reason_details');
        $items = $request->post('items', []);
        
        if (empty($orderId) || empty($reason) || empty($items)) {
            return [
                'success' => false,
                'message' => 'Неверные параметры'
            ];
        }
        
        try {
            $return = new ReturnRequest();
            $return->customer_id = $customerId;
            $return->order_id = $orderId;
            $return->reason = $reason;
            $return->reason_details = $reasonDetails;
            $return->items = json_encode($items);
            $return->status = 'pending';
            $return->return_number = 'R' . date('Ymd') . rand(1000, 9999);
            
            // Рассчитываем сумму возврата
            $policy = ReturnPolicy::findOne(['is_default' => true]);
            if ($policy) {
                $return->refund_amount = $policy->calculateRefundAmount($orderId, $items);
            }
            
            if ($return->save()) {
                return [
                    'success' => true,
                    'return_id' => $return->id,
                    'return_number' => $return->return_number,
                    'message' => 'Заявка создана'
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Ошибка при создании заявки',
                    'errors' => $return->errors
                ];
            }
        } catch (\Exception $e) {
            Yii::error('Return create error: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Ошибка при создании заявки'
            ];
        }
    }
    
    /**
     * Отменить заявку
     * 
     * POST /api/v1/returns/{id}/cancel
     */
    public function actionCancel($id)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        
        try {
            $return = ReturnRequest::findOne($id);
            
            if (!$return) {
                return [
                    'success' => false,
                    'message' => 'Заявка не найдена'
                ];
            }
            
            if ($return->customer_id !== Yii::$app->user->id) {
                return [
                    'success' => false,
                    'message' => 'Нет доступа к этой заявке'
                ];
            }
            
            if ($return->status !== 'pending') {
                return [
                    'success' => false,
                    'message' => 'Заявку нельзя отменить'
                ];
            }
            
            $return->status = 'cancelled';
            
            if ($return->save()) {
                return [
                    'success' => true,
                    'message' => 'Заявка отменена'
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Ошибка при отмене заявки'
                ];
            }
        } catch (\Exception $e) {
            Yii::error('Return cancel error: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Ошибка при отмене заявки'
            ];
        }
    }
    
    /**
     * Политика возврата
     * 
     * GET /api/v1/returns/policy
     */
    public function actionPolicy()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        
        try {
            $policy = ReturnPolicy::findOne(['is_default' => true]);
            
            if (!$policy) {
                return [
                    'success' => false,
                    'message' => 'Политика не найдена'
                ];
            }
            
            return [
                'success' => true,
                'policy' => [
                    'return_period_days' => $policy->return_period_days,
                    'requires_original_packaging' => $policy->requires_original_packaging,
                    'requires_tags' => $policy->requires_tags,
                    'restocking_fee' => $policy->restocking_fee,
                    'free_return_shipping' => $policy->free_return_shipping,
                    'conditions_text' => $policy->conditions_text,
                ],
            ];
        } catch (\Exception $e) {
            Yii::error('Return policy error: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Ошибка при загрузке политики'
            ];
        }
    }
}
