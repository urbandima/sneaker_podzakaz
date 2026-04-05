<?php
/**
 * TrackingController — Отслеживание доставки
 *
 * B11.2: API трекинга Европочта / Белпочта / СДЭК
 */

namespace app\backend\modules\admin\controllers;

use Yii;
use yii\web\Response;
use app\backend\modules\checkout\models\Order;

class TrackingController extends BaseAdminController
{
    /**
     * AJAX: Проверить статус доставки
     */
    public function actionCheck($orderId)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $order = Order::findOne($orderId);
        if (!$order || empty($order->track_number)) {
            return ['success' => false, 'message' => 'Трек-номер не указан'];
        }

        $carrier = $this->detectCarrier($order->track_number);
        $status = $this->fetchTrackingStatus($carrier, $order->track_number);

        // Логируем проверку
        Yii::info("Tracking checked for order #{$orderId}: {$status['status']}", 'admin');

        return [
            'success' => true,
            'carrier' => $carrier,
            'status' => $status,
        ];
    }

    /**
     * Определить службу доставки по треку
     */
    private function detectCarrier($trackNumber)
    {
        // Простая эвристика
        if (preg_match('/^E[A-Z]\d{9}BY$/', $trackNumber)) {
            return 'belpost'; // Белпочта EMS
        } elseif (preg_match('/^\d{14}$/', $trackNumber)) {
            return 'europost'; // Европочта
        } elseif (preg_match('/^\d{10,12}$/', $trackNumber)) {
            return 'cdek'; // СДЭК
        }

        return 'unknown';
    }

    /**
     * Получить статус от API
     */
    private function fetchTrackingStatus($carrier, $trackNumber)
    {
        // Заглушка - в реальности интеграция с API
        $mockStatuses = [
            'belpost' => [
                'status' => 'in_transit',
                'status_text' => 'В пути',
                'location' => 'Минск МСЦ',
                'date' => date('Y-m-d H:i:s'),
                'history' => [
                    ['date' => date('Y-m-d'), 'status' => 'Отправлено', 'location' => 'Минск'],
                    ['date' => date('Y-m-d', strtotime('-1 day')), 'status' => 'Принято', 'location' => 'Минск'],
                ],
            ],
            'europost' => [
                'status' => 'delivered',
                'status_text' => 'Доставлено',
                'location' => 'Пункт выдачи',
                'date' => date('Y-m-d H:i:s'),
            ],
            'cdek' => [
                'status' => 'in_transit',
                'status_text' => 'В пути',
                'location' => 'Склад СДЭК',
                'date' => date('Y-m-d H:i:s'),
            ],
        ];

        return $mockStatuses[$carrier] ?? [
            'status' => 'unknown',
            'status_text' => 'Статус неизвестен',
        ];
    }

    /**
     * Публичный трекер по токену (B20.1)
     */
    public function actionPublic($token)
    {
        $order = Order::find()
            ->where(['public_token' => $token])
            ->one();

        if (!$order) {
            throw new \yii\web\NotFoundHttpException('Заказ не найден');
        }

        $this->layout = '@frontend/views/layouts/main';

        return $this->render('@frontend/views/order/tracker', [
            'order' => $order,
        ]);
    }
}
