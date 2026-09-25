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
        // CMP-470-Д: column was 'public_token' — that column doesn't exist in `order`
        // (schema only has `token`, a crypto-random string set by
        // frontend\controllers\OrderController::actionCreate via
        // Yii::$app->security->generateRandomString(32)). Every call to this action
        // threw a SQL "Unknown column" error. Fixed to match the real, already-secure
        // token column instead of inventing a new one.
        $order = Order::find()
            ->where(['token' => $token])
            ->one();

        if (!$order) {
            throw new \yii\web\NotFoundHttpException('Заказ не найден');
        }

        $this->layout = '@frontend/views/layouts/main';

        // CMP-470-Д: view was '@frontend/views/order/tracker', which doesn't exist
        // anywhere in the repo (ViewNotFoundException on every call). The real,
        // already-working customer tracking view is 'track.php', used by the
        // equivalent frontend\controllers\OrderController::actionTrack($token) —
        // reused it here instead of inventing a new template.
        return $this->render('@frontend/views/order/track', [
            'order' => $order,
        ]);
    }
}
