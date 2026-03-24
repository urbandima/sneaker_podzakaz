<?php

/**
 * NotificationService — Сервис уведомлений
 * 
 * НАЗНАЧЕНИЕ:
 * Централизованная отправка уведомлений пользователям через различные каналы.
 * 
 * ФУНКЦИИ:
 * - sendEmail() - отправка email
 * - sendPush() - отправка push уведомления
 * - createInternal() - создание внутреннего уведомления
 * - markAsRead() - отметить как прочитанное
 */
namespace app\backend\modules\notification\services;

use Yii;
use yii\base\Component;
use app\backend\modules\notification\models\Notification;

class NotificationService extends Component
{
    /**
     * Отправить email уведомление
     * 
     * @param string $to Email получателя
     * @param string $subject Тема письма
     * @param string $body Текст письма
     * @param array $params Дополнительные параметры
     * @return bool
     */
    public function sendEmail(string $to, string $subject, string $body, array $params = []): bool
    {
        try {
            return Yii::$app->mailer->compose()
                ->setTo($to)
                ->setFrom([Yii::$app->params['adminEmail'] => Yii::$app->name])
                ->setSubject($subject)
                ->setHtmlBody($body)
                ->send();
        } catch (\Exception $e) {
            Yii::error("Email send error: " . $e->getMessage(), __METHOD__);
            return false;
        }
    }

    /**
     * Создать внутреннее уведомление
     * 
     * @param int $customerId ID покупателя
     * @param string $type Тип уведомления
     * @param string $title Заголовок
     * @param string $message Сообщение
     * @param array $data Дополнительные данные
     * @return Notification|null
     */
    public function createInternal(int $customerId, string $type, string $title, string $message, array $data = []): ?Notification
    {
        $notification = new Notification();
        $notification->customer_id = $customerId;
        $notification->type = $type;
        $notification->title = $title;
        $notification->message = $message;
        $notification->data = json_encode($data);
        $notification->is_read = 0;
        $notification->created_at = date('Y-m-d H:i:s');
        
        if ($notification->save()) {
            return $notification;
        }
        
        return null;
    }

    /**
     * Отметить уведомление как прочитанное
     * 
     * @param int $notificationId
     * @return bool
     */
    public function markAsRead(int $notificationId): bool
    {
        $notification = Notification::findOne($notificationId);
        if ($notification) {
            $notification->is_read = 1;
            $notification->read_at = date('Y-m-d H:i:s');
            return $notification->save(false);
        }
        
        return false;
    }

    /**
     * Отметить все уведомления пользователя как прочитанные
     * 
     * @param int $customerId
     * @return int Количество обновлённых записей
     */
    public function markAllAsRead(int $customerId): int
    {
        return Notification::updateAll(
            ['is_read' => 1, 'read_at' => date('Y-m-d H:i:s')],
            ['customer_id' => $customerId, 'is_read' => 0]
        );
    }

    /**
     * Получить непрочитанные уведомления
     * 
     * @param int $customerId
     * @param int $limit
     * @return array
     */
    public function getUnread(int $customerId, int $limit = 10): array
    {
        return Notification::find()
            ->where(['customer_id' => $customerId, 'is_read' => 0])
            ->orderBy(['created_at' => SORT_DESC])
            ->limit($limit)
            ->all();
    }

    /**
     * Отправить уведомление о новом заказе
     * 
     * @param int $orderId
     * @param int $customerId
     */
    public function notifyNewOrder(int $orderId, int $customerId): void
    {
        $this->createInternal(
            $customerId,
            'order',
            'Заказ оформлен',
            "Ваш заказ #{$orderId} успешно оформлен и принят в обработку.",
            ['order_id' => $orderId]
        );
    }

    /**
     * Отправить уведомление об изменении статуса заказа
     * 
     * @param int $orderId
     * @param int $customerId
     * @param string $status
     */
    public function notifyOrderStatus(int $orderId, int $customerId, string $status): void
    {
        $statusMessages = [
            'processing' => 'Ваш заказ в обработке',
            'shipped' => 'Ваш заказ отправлен',
            'delivered' => 'Ваш заказ доставлен',
            'cancelled' => 'Ваш заказ отменён',
        ];
        
        $message = $statusMessages[$status] ?? 'Статус заказа изменён';
        
        $this->createInternal(
            $customerId,
            'order_status',
            'Статус заказа изменён',
            "{$message}: заказ #{$orderId}",
            ['order_id' => $orderId, 'status' => $status]
        );
    }

    /**
     * Отправить уведомление о начислении баллов лояльности
     * 
     * @param int $customerId
     * @param int $points
     * @param string $reason
     */
    public function notifyLoyaltyPoints(int $customerId, int $points, string $reason): void
    {
        $this->createInternal(
            $customerId,
            'loyalty',
            'Начислены баллы',
            "Вам начислено {$points} баллов. {$reason}",
            ['points' => $points, 'reason' => $reason]
        );
    }
}
