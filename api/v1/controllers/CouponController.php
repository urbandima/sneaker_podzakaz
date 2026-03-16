<?php

namespace app\api\v1\controllers;

use Yii;
use yii\rest\Controller;
use yii\web\Response;
use app\backend\modules\coupon\services\CouponService;
use app\backend\modules\coupon\models\Coupon;

/**
 * Coupon API Controller
 * 
 * Endpoints:
 * - POST /api/v1/coupon/validate - Валидация промокода
 * - GET /api/v1/coupon/list - Список доступных промокодов
 */
class CouponController extends Controller
{
    private $couponService;
    
    public function init()
    {
        parent::init();
        $this->couponService = new CouponService();
    }
    
    /**
     * Валидация промокода
     * 
     * POST /api/v1/coupon/validate
     * 
     * Body:
     * {
     *   "code": "PROMO123",
     *   "order_amount": 150.00,
     *   "customer_id": 123
     * }
     */
    public function actionValidate()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        
        $request = Yii::$app->request;
        $code = $request->post('code');
        $orderAmount = (float) $request->post('order_amount', 0);
        $customerId = $request->post('customer_id');
        
        if (empty($code)) {
            return [
                'success' => false,
                'message' => 'Введите промокод'
            ];
        }
        
        try {
            $result = $this->couponService->validateCoupon($code, $orderAmount, $customerId);
            
            if ($result['valid']) {
                $coupon = $result['coupon'];
                $discount = $this->couponService->calculateDiscount($coupon, $orderAmount);
                
                return [
                    'success' => true,
                    'coupon' => [
                        'id' => $coupon->id,
                        'code' => $coupon->code,
                        'type' => $coupon->type,
                        'value' => $coupon->value,
                        'discount' => $discount,
                        'description' => $coupon->description,
                        'min_order_amount' => $coupon->min_order_amount,
                        'max_discount_amount' => $coupon->max_discount_amount,
                    ],
                    'discount' => $discount,
                    'message' => 'Промокод применён'
                ];
            } else {
                return [
                    'success' => false,
                    'message' => $result['message'] ?? 'Недействительный промокод'
                ];
            }
        } catch (\Exception $e) {
            Yii::error('Coupon validation error: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Ошибка при проверке промокода'
            ];
        }
    }
    
    /**
     * Список доступных промокодов
     * 
     * GET /api/v1/coupon/list
     */
    public function actionList()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        
        try {
            $coupons = Coupon::find()
                ->where(['status' => 'active'])
                ->andWhere(['<=', 'start_date', date('Y-m-d')])
                ->andWhere(['>=', 'end_date', date('Y-m-d')])
                ->all();
            
            $result = [];
            foreach ($coupons as $coupon) {
                $result[] = [
                    'id' => $coupon->id,
                    'code' => $coupon->code,
                    'type' => $coupon->type,
                    'value' => $coupon->value,
                    'description' => $coupon->description,
                    'min_order_amount' => $coupon->min_order_amount,
                    'end_date' => $coupon->end_date,
                ];
            }
            
            return [
                'success' => true,
                'coupons' => $result
            ];
        } catch (\Exception $e) {
            Yii::error('Coupon list error: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Ошибка при загрузке промокодов'
            ];
        }
    }
    
    /**
     * Применить промокод к заказу
     * 
     * POST /api/v1/coupon/apply
     */
    public function actionApply()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        
        $request = Yii::$app->request;
        $code = $request->post('code');
        $orderId = $request->post('order_id');
        
        if (empty($code) || empty($orderId)) {
            return [
                'success' => false,
                'message' => 'Неверные параметры'
            ];
        }
        
        try {
            $result = $this->couponService->applyCoupon($orderId, $code);
            
            return [
                'success' => $result['success'],
                'message' => $result['message'] ?? '',
                'discount' => $result['discount'] ?? 0
            ];
        } catch (\Exception $e) {
            Yii::error('Coupon apply error: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Ошибка при применении промокода'
            ];
        }
    }
}
