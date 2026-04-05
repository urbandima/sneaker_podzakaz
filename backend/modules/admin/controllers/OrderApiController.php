<?php
/**
 * OrderApiController — AJAX endpoints для карточки заказа
 *
 * B5.5: Inline-редактирование полей заказа
 * B5.7: История изменений
 */

namespace app\backend\modules\admin\controllers;

use Yii;
use yii\web\Response;
use yii\filters\VerbFilter;
use app\backend\modules\checkout\models\Order;
use app\backend\modules\checkout\models\OrderHistory;

class OrderApiController extends BaseAdminController
{
    public function behaviors()
    {
        return array_merge(parent::behaviors(), [
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'update-field' => ['POST'],
                    'add-note' => ['POST'],
                    'change-status' => ['POST'],
                ],
            ],
        ]);
    }

    /**
     * B5.5: Inline-редактирование поля заказа
     */
    public function actionUpdateField($id)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $order = Order::findOne($id);
        if (!$order) {
            return ['success' => false, 'message' => 'Заказ не найден'];
        }

        $field = Yii::$app->request->post('field');
        $value = Yii::$app->request->post('value');

        $allowedFields = ['client_name', 'client_phone', 'client_email', 'address', 'comment', 'track_number'];
        if (!in_array($field, $allowedFields)) {
            return ['success' => false, 'message' => 'Недопустимое поле'];
        }

        $oldValue = $order->$field;
        $order->$field = $value;

        if ($order->save(false)) {
            // Логируем изменение
            $this->logChange($order->id, $field, $oldValue, $value);

            return [
                'success' => true,
                'message' => 'Сохранено',
                'value' => $value
            ];
        }

        return ['success' => false, 'message' => 'Ошибка сохранения'];
    }

    /**
     * B5.5: Добавление заметки к заказу
     */
    public function actionAddNote($id)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $order = Order::findOne($id);
        if (!$order) {
            return ['success' => false, 'message' => 'Заказ не найден'];
        }

        $note = Yii::$app->request->post('note');
        if (empty($note)) {
            return ['success' => false, 'message' => 'Заметка не может быть пустой'];
        }

        // Сохраняем в историю как комментарий
        $history = new OrderHistory();
        $history->order_id = $order->id;
        $history->status = $order->status;
        $history->comment = $note;
        $history->created_by = Yii::$app->user->id;
        $history->created_at = time();

        if ($history->save(false)) {
            return [
                'success' => true,
                'message' => 'Заметка добавлена',
                'note' => $note,
                'date' => Yii::$app->formatter->asDatetime(time(), 'short'),
                'user' => Yii::$app->user->identity->username ?? 'Admin'
            ];
        }

        return ['success' => false, 'message' => 'Ошибка сохранения'];
    }

    /**
     * B5.7: Получение истории изменений заказа
     */
    public function actionHistory($id)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $history = OrderHistory::find()
            ->where(['order_id' => $id])
            ->orderBy(['created_at' => SORT_DESC])
            ->all();

        $result = [];
        foreach ($history as $h) {
            $result[] = [
                'id' => $h->id,
                'status' => $h->status,
                'status_label' => $h->getStatusLabel(),
                'comment' => $h->comment,
                'created_at' => Yii::$app->formatter->asDatetime($h->created_at, 'short'),
                'created_by' => $h->creator ? $h->creator->username : 'Система',
            ];
        }

        return ['history' => $result];
    }

    /**
     * B5.4: Изменение статуса заказа
     */
    public function actionChangeStatus($id)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $order = Order::findOne($id);
        if (!$order) {
            return ['success' => false, 'message' => 'Заказ не найден'];
        }

        $newStatus = Yii::$app->request->post('status');
        $comment = Yii::$app->request->post('comment', '');

        $allowedStatuses = array_keys(Yii::$app->settings->getStatuses());
        if (!in_array($newStatus, $allowedStatuses)) {
            return ['success' => false, 'message' => 'Недопустимый статус'];
        }

        $oldStatus = $order->status;
        $order->status = $newStatus;

        if ($order->save(false)) {
            // Логируем изменение статуса
            $history = new OrderHistory();
            $history->order_id = $order->id;
            $history->status = $newStatus;
            $history->old_status = $oldStatus;
            $history->comment = $comment ?: 'Статус изменен на: ' . $order->getStatusLabel();
            $history->created_by = Yii::$app->user->id;
            $history->created_at = time();
            $history->save(false);

            // Telegram notification (B10.1)
            $this->sendTelegramNotification($order, $oldStatus, $newStatus);

            return [
                'success' => true,
                'message' => 'Статус обновлен',
                'status' => $newStatus,
                'status_label' => $order->getStatusLabel(),
            ];
        }

        return ['success' => false, 'message' => 'Ошибка сохранения'];
    }

    /**
     * Логирование изменений
     */
    private function logChange($orderId, $field, $oldValue, $newValue)
    {
        $fieldLabels = [
            'client_name' => 'Имя клиента',
            'client_phone' => 'Телефон',
            'client_email' => 'Email',
            'address' => 'Адрес',
            'comment' => 'Комментарий',
            'track_number' => 'Трек-номер',
        ];

        $history = new OrderHistory();
        $history->order_id = $orderId;
        $history->status = 'modified';
        $history->comment = sprintf(
            'Изменено поле "%s": "%s" → "%s"',
            $fieldLabels[$field] ?? $field,
            $oldValue ?: '(пусто)',
            $newValue ?: '(пусто)'
        );
        $history->created_by = Yii::$app->user->id;
        $history->created_at = time();
        $history->save(false);
    }

    /**
     * B10.1: Telegram уведомление о смене статуса
     */
    private function sendTelegramNotification($order, $oldStatus, $newStatus)
    {
        // Реализация будет в TelegramBotController
        // Здесь только триггер события
        Yii::info("Telegram notification triggered for order #{$order->id}: {$oldStatus} -> {$newStatus}", 'admin');
    }
}
