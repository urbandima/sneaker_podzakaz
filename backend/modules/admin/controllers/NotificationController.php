<?php

/**
 * NotificationController — Уведомления для шапки админ-панели
 *
 * НАЗНАЧЕНИЕ:
 * Возвращает количество непрочитанных/необработанных уведомлений для
 * колокольчика в шапке. Опрашивается каждые 30 секунд через AJAX (admin.js).
 *
 * ФУНКЦИИ:
 * - actionIndex() — кол-во необработанных заказов (статус 'new')
 */
namespace app\backend\modules\admin\controllers;

use Yii;
use yii\web\Response;
use app\backend\modules\checkout\models\Order;

class NotificationController extends BaseAdminController
{
    /**
     * Возвращает JSON с количеством необработанных уведомлений.
     * Используется колокольчиком в шапке (polls /admin/notification/index).
     */
    public function actionIndex()
    {
        // If called via AJAX/XHR, return JSON for the bell badge
        if (Yii::$app->request->isAjax) {
            Yii::$app->response->format = Response::FORMAT_JSON;

            $count = 0;
            try {
                $count = (int)Order::find()
                    ->where(['status' => 'new'])
                    ->count();
            } catch (\Exception $e) {
                // При ошибке возвращаем 0, не ломаем фронт
            }

            return ['count' => $count];
        }

        // Direct browser access — redirect to activity log
        return $this->redirect(['/admin/activity-log/index']);
    }
}
