<?php

namespace app\api\v1\controllers;

use Yii;
use yii\rest\Controller;
use yii\web\Response;
use app\backend\modules\checkout\services\TrackingService;
use app\backend\modules\checkout\models\Order;
use app\backend\modules\checkout\models\DeliveryTracking;

/**
 * Tracking API Controller
 * 
 * Endpoints:
 * - GET /api/v1/tracking/{order_id} - Отслеживание заказа
 * - POST /api/v1/tracking/refresh/{order_id} - Обновить данные отслеживания
 * - GET /api/v1/tracking/url/{tracking_number}/{carrier} - URL отслеживания
 */
class TrackingController extends Controller
{
    private $trackingService;
    
    public function init()
    {
        parent::init();
        $this->trackingService = new TrackingService();
    }
    
    /**
     * Отслеживание заказа
     * 
     * GET /api/v1/tracking/{order_id}
     */
    public function actionIndex($orderId)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        
        try {
            $order = Order::findOne($orderId);
            
            if (!$order) {
                return [
                    'success' => false,
                    'message' => 'Заказ не найден'
                ];
            }
            
            $tracking = DeliveryTracking::findOne(['order_id' => $orderId]);
            
            if (!$tracking) {
                return [
                    'success' => true,
                    'has_tracking' => false,
                    'message' => 'Трек-номер ещё не присвоен'
                ];
            }
            
            return [
                'success' => true,
                'has_tracking' => true,
                'tracking' => [
                    'tracking_number' => $tracking->tracking_number,
                    'carrier' => $tracking->carrier,
                    'status' => $tracking->status,
                    'status_description' => $tracking->status_description,
                    'location' => $tracking->location,
                    'estimated_delivery' => $tracking->estimated_delivery,
                    'actual_delivery' => $tracking->actual_delivery,
                    'last_check_at' => $tracking->last_check_at,
                    'events' => $tracking->getEvents(),
                ],
                'tracking_url' => TrackingService::getTrackingUrl(
                    $tracking->tracking_number,
                    $tracking->carrier
                ),
            ];
        } catch (\Exception $e) {
            Yii::error('Tracking error: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Ошибка при загрузке данных отслеживания'
            ];
        }
    }
    
    /**
     * Обновить данные отслеживания
     * 
     * POST /api/v1/tracking/refresh/{order_id}
     */
    public function actionRefresh($orderId)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        
        try {
            $order = Order::findOne($orderId);
            
            if (!$order) {
                return [
                    'success' => false,
                    'message' => 'Заказ не найден'
                ];
            }
            
            $result = $this->trackingService->trackOrder($orderId);
            
            if ($result) {
                $tracking = DeliveryTracking::findOne(['order_id' => $orderId]);
                
                return [
                    'success' => true,
                    'tracking' => [
                        'status' => $tracking->status,
                        'status_description' => $tracking->status_description,
                        'location' => $tracking->location,
                        'events' => $tracking->getEvents(),
                    ],
                    'message' => 'Данные обновлены'
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Не удалось обновить данные'
                ];
            }
        } catch (\Exception $e) {
            Yii::error('Tracking refresh error: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Ошибка при обновлении данных'
            ];
        }
    }
    
    /**
     * URL отслеживания
     * 
     * GET /api/v1/tracking/url/{tracking_number}/{carrier}
     */
    public function actionUrl($trackingNumber, $carrier)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        
        try {
            $url = TrackingService::getTrackingUrl($trackingNumber, $carrier);
            
            return [
                'success' => true,
                'url' => $url,
            ];
        } catch (\Exception $e) {
            Yii::error('Tracking URL error: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Ошибка при получении URL'
            ];
        }
    }
}
