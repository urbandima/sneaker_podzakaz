<?php

namespace app\api\v1\controllers;

use Yii;
use yii\rest\Controller;
use yii\web\Response;
use app\backend\modules\loyalty\services\LoyaltyService;
use app\backend\modules\loyalty\models\LoyaltyPoints;
use app\backend\modules\account\models\Customer;

/**
 * Loyalty API Controller
 * 
 * Endpoints:
 * - GET /api/v1/loyalty/balance - Баланс баллов клиента
 * - GET /api/v1/loyalty/history - История баллов
 * - GET /api/v1/loyalty/level - Уровень клиента
 * - POST /api/v1/loyalty/redeem - Списать баллы
 */
class LoyaltyController extends Controller
{
    private $loyaltyService;
    
    public function init()
    {
        parent::init();
        $this->loyaltyService = new LoyaltyService();
    }
    
    /**
     * Баланс баллов клиента
     * 
     * GET /api/v1/loyalty/balance
     */
    public function actionBalance()
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
            $balance = $this->loyaltyService->getBalance($customerId);
            
            return [
                'success' => true,
                'balance' => $balance,
                'currency' => 'BYN',
                'rate' => 0.01 // 1 балл = 0.01 BYN
            ];
        } catch (\Exception $e) {
            Yii::error('Loyalty balance error: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Ошибка при загрузке баланса'
            ];
        }
    }
    
    /**
     * История баллов
     * 
     * GET /api/v1/loyalty/history
     */
    public function actionHistory()
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
            $history = LoyaltyPoints::find()
                ->where(['customer_id' => $customerId])
                ->orderBy(['created_at' => SORT_DESC])
                ->limit(50)
                ->all();
            
            $result = [];
            foreach ($history as $item) {
                $result[] = [
                    'id' => $item->id,
                    'type' => $item->type,
                    'points' => $item->points,
                    'balance' => $item->balance,
                    'description' => $item->description,
                    'created_at' => $item->created_at,
                    'expires_at' => $item->expires_at,
                ];
            }
            
            return [
                'success' => true,
                'history' => $result
            ];
        } catch (\Exception $e) {
            Yii::error('Loyalty history error: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Ошибка при загрузке истории'
            ];
        }
    }
    
    /**
     * Уровень клиента
     * 
     * GET /api/v1/loyalty/level
     */
    public function actionLevel()
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
            $level = $this->loyaltyService->getCustomerLevel($customerId);
            $nextLevel = $this->loyaltyService->getNextLevel($customerId);
            
            $balance = $this->loyaltyService->getBalance($customerId);
            
            return [
                'success' => true,
                'level' => [
                    'id' => $level->id,
                    'name' => $level->name,
                    'level' => $level->level,
                    'points_multiplier' => $level->points_multiplier,
                    'discount_percent' => $level->discount_percent,
                    'benefits' => $level->getBenefitsList(),
                ],
                'next_level' => $nextLevel ? [
                    'name' => $nextLevel->name,
                    'min_points' => $nextLevel->min_points,
                    'points_to_next' => $nextLevel->min_points - $balance,
                ] : null,
                'balance' => $balance,
            ];
        } catch (\Exception $e) {
            Yii::error('Loyalty level error: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Ошибка при загрузке уровня'
            ];
        }
    }
    
    /**
     * Списать баллы
     * 
     * POST /api/v1/loyalty/redeem
     * 
     * Body:
     * {
     *   "points": 100,
     *   "order_id": 123
     * }
     */
    public function actionRedeem()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        
        $customerId = Yii::$app->user->id;
        
        if (!$customerId) {
            return [
                'success' => false,
                'message' => 'Требуется авторизация'
            ];
        }
        
        $points = (int) Yii::$app->request->post('points', 0);
        $orderId = Yii::$app->request->post('order_id');
        
        if ($points <= 0) {
            return [
                'success' => false,
                'message' => 'Неверное количество баллов'
            ];
        }
        
        try {
            $result = $this->loyaltyService->redeemPoints($customerId, $points, $orderId);
            
            if ($result['success']) {
                return [
                    'success' => true,
                    'points_redeemed' => $result['points_redeemed'],
                    'discount_amount' => $result['discount_amount'],
                    'new_balance' => $result['new_balance'],
                ];
            } else {
                return [
                    'success' => false,
                    'message' => $result['message'] ?? 'Ошибка при списании баллов'
                ];
            }
        } catch (\Exception $e) {
            Yii::error('Loyalty redeem error: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Ошибка при списании баллов'
            ];
        }
    }
    
    /**
     * Начислить баллы за покупку
     * 
     * POST /api/v1/loyalty/earn
     * 
     * Body:
     * {
     *   "order_id": 123,
     *   "customer_id": 456
     * }
     */
    public function actionEarn()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        
        $orderId = Yii::$app->request->post('order_id');
        $customerId = Yii::$app->request->post('customer_id');
        
        if (empty($orderId) || empty($customerId)) {
            return [
                'success' => false,
                'message' => 'Неверные параметры'
            ];
        }
        
        try {
            $result = $this->loyaltyService->earnPoints($customerId, $orderId);
            
            return [
                'success' => $result['success'],
                'points_earned' => $result['points_earned'] ?? 0,
                'new_balance' => $result['new_balance'] ?? 0,
                'message' => $result['message'] ?? ''
            ];
        } catch (\Exception $e) {
            Yii::error('Loyalty earn error: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Ошибка при начислении баллов'
            ];
        }
    }
}
