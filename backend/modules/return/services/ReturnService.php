<?php

/**
 * ReturnService — Сервис обработки возвратов
 * 
 * НАЗНАЧЕНИЕ:
 * Бизнес-логика обработки возвратов товаров: создание заявок,
 * обработка, возврат средств, обновление остатков.
 * 
 * ФУНКЦИИ:
 * - createReturn() - создание заявки на возврат
 * - processReturn() - обработка возврата
 * - refundPayment() - возврат средств
 * - updateInventory() - обновление остатков
 * - canReturn() - проверка возможности возврата
 */
namespace app\backend\modules\return\services;

use Yii;
use yii\base\Component;
use app\backend\modules\return\models\ReturnRequest;
use app\backend\modules\return\models\ReturnPolicy;
use app\backend\modules\checkout\models\Order;
use app\backend\modules\catalog\models\Product;

class ReturnService extends Component
{
    /** @var string|null Сообщение об ошибке */
    private $errorMessage = null;

    /**
     * Создать заявку на возврат
     * 
     * @param int $orderId ID заказа
     * @param array $items Массив товаров для возврата
     * @param string $reason Причина возврата
     * @param string|null $comment Комментарий клиента
     * @param string|null $pickupAddress Адрес для забора
     * @return ReturnRequest|null
     */
    public function createReturn(int $orderId, array $items, string $reason, ?string $comment = null, ?string $pickupAddress = null): ?ReturnRequest
    {
        $this->errorMessage = null;
        
        $order = Order::findOne($orderId);
        if (!$order) {
            $this->errorMessage = 'Заказ не найден';
            return null;
        }
        
        // Проверяем политику возврата
        $policy = ReturnPolicy::getDefault();
        if (!$policy) {
            $this->errorMessage = 'Политика возврата не настроена';
            return null;
        }
        
        list($canReturn, $error) = $policy->canReturn($order);
        if (!$canReturn) {
            $this->errorMessage = $error;
            return null;
        }
        
        // Валидация товаров
        if (empty($items)) {
            $this->errorMessage = 'Укажите товары для возврата';
            return null;
        }
        
        // Проверяем, что все товары из заказа
        $orderItemIds = array_map(fn($item) => $item->id, $order->orderItems);
        foreach ($items as $item) {
            if (!isset($item['order_item_id']) || !in_array($item['order_item_id'], $orderItemIds)) {
                $this->errorMessage = 'Некорректный товар в списке возврата';
                return null;
            }
        }
        
        // Создаём заявку
        $request = new ReturnRequest();
        $request->order_id = $orderId;
        $request->customer_id = $order->customer_id;
        $request->reason = $reason;
        $request->comment = $comment;
        $request->pickup_address = $pickupAddress ?? $order->delivery_address;
        $request->items_json = json_encode($items, JSON_UNESCAPED_UNICODE);
        
        // Рассчитываем сумму возврата
        $totalRefund = 0;
        foreach ($items as $item) {
            $orderItem = array_filter($order->orderItems, fn($oi) => $oi->id == $item['order_item_id']);
            $orderItem = reset($orderItem);
            if ($orderItem) {
                $quantity = $item['quantity'] ?? 1;
                $totalRefund += $orderItem->price * $quantity;
            }
        }
        
        $request->refund_amount = $policy->calculateRefund($totalRefund);
        $request->refund_method = 'original'; // Возврат на исходный способ оплаты
        
        if ($request->save()) {
            Yii::info("Создана заявка на возврат #{$request->id} для заказа #{$orderId}", 'return');
            return $request;
        }
        
        $this->errorMessage = 'Ошибка при создании заявки: ' . json_encode($request->errors);
        return null;
    }

    /**
     * Обработать возврат (одобрить/отклонить)
     * 
     * @param ReturnRequest $request
     * @param bool $approve Одобрить или отклонить
     * @param string|null $comment Комментарий админа
     * @return bool
     */
    public function processReturn(ReturnRequest $request, bool $approve, ?string $comment = null): bool
    {
        $this->errorMessage = null;
        
        if ($request->status !== ReturnRequest::STATUS_PENDING) {
            $this->errorMessage = 'Заявка уже обработана';
            return false;
        }
        
        if ($approve) {
            if ($request->approve($comment)) {
                Yii::info("Заявка на возврат #{$request->id} одобрена", 'return');
                
                // Отправляем email клиенту
                $this->sendApprovalEmail($request);
                
                return true;
            }
        } else {
            if ($request->reject($comment ?? 'Возврат отклонён')) {
                Yii::info("Заявка на возврат #{$request->id} отклонена", 'return');
                
                // Отправляем email клиенту
                $this->sendRejectionEmail($request);
                
                return true;
            }
        }
        
        $this->errorMessage = 'Ошибка при обработке заявки';
        return false;
    }

    /**
     * Завершить возврат с возвратом средств
     * 
     * @param ReturnRequest $request
     * @return bool
     */
    public function completeReturn(ReturnRequest $request): bool
    {
        $this->errorMessage = null;
        
        if ($request->status !== ReturnRequest::STATUS_APPROVED && $request->status !== ReturnRequest::STATUS_PROCESSING) {
            $this->errorMessage = 'Заявка должна быть одобрена';
            return false;
        }
        
        $transaction = Yii::$app->db->beginTransaction();
        
        try {
            // Возврат средств
            $transactionId = $this->refundPayment($request);
            if (!$transactionId) {
                throw new \Exception($this->errorMessage ?? 'Ошибка возврата средств');
            }
            
            // Обновляем остатки товаров
            $this->updateInventory($request);
            
            // Завершаем заявку
            if (!$request->complete($transactionId)) {
                throw new \Exception('Ошибка при завершении заявки');
            }
            
            // Отправляем email клиенту
            $this->sendCompletionEmail($request);
            
            $transaction->commit();
            
            Yii::info("Возврат #{$request->id} завершён, транзакция: {$transactionId}", 'return');
            return true;
            
        } catch (\Exception $e) {
            $transaction->rollBack();
            $this->errorMessage = $e->getMessage();
            Yii::error("Ошибка завершения возврата #{$request->id}: " . $e->getMessage(), 'return');
            return false;
        }
    }

    /**
     * Возврат средств
     * 
     * @param ReturnRequest $request
     * @return string|null ID транзакции возврата
     */
    protected function refundPayment(ReturnRequest $request): ?string
    {
        // Архитектурная заглушка: интеграция с платёжной системой будет добавлена при подключении платёжного шлюза
        // Текущая реализация: генерация ID транзакции для аудита
        
        $transactionId = 'REFUND-' . date('Ymd') . '-' . strtoupper(Yii::$app->security->generateRandomString(8));
        
        Yii::info("Возврат средств: {$request->refund_amount} BYN, транзакция: {$transactionId}", 'return');
        
        return $transactionId;
    }

    /**
     * Обновление остатков товаров
     * 
     * @param ReturnRequest $request
     * @return bool
     */
    protected function updateInventory(ReturnRequest $request): bool
    {
        $items = $request->getItems();
        
        foreach ($items as $item) {
            if (!isset($item['product_id'])) {
                continue;
            }
            
            $product = Product::findOne($item['product_id']);
            if ($product) {
                // Увеличиваем количество на складе
                $quantity = $item['quantity'] ?? 1;
                
                // Обновляем остатки товара
                $product->stock_quantity += $quantity;
                
                // Если товар был не в наличии, делаем его доступным
                if ($product->stock_quantity > 0 && $product->stock_status === Product::STOCK_OUT_OF_STOCK) {
                    $product->stock_status = Product::STOCK_IN_STOCK;
                }
                
                if ($product->save()) {
                    Yii::info("Возврат товара #{$product->id} на склад, количество: {$quantity}. Новый остаток: {$product->stock_quantity}", 'return');
                } else {
                    Yii::error("Ошибка обновления остатков для товара #{$product->id}: " . implode(', ', $product->getFirstErrors()), 'return');
                }
            }
        }
        
        return true;
    }

    /**
     * Проверка возможности возврата
     * 
     * @param Order $order
     * @return array [canReturn, errorMessage]
     */
    public function canReturn(Order $order): array
    {
        $policy = ReturnPolicy::getDefault();
        if (!$policy) {
            return [false, 'Политика возврата не настроена'];
        }
        
        return $policy->canReturn($order);
    }

    /**
     * Отправка email об одобрении возврата
     */
    protected function sendApprovalEmail(ReturnRequest $request): void
    {
        try {
            $order = $request->order;
            if ($order && $order->client_email) {
                Yii::$app->mailer->compose('return-approved', ['request' => $request])
                    ->setFrom([Yii::$app->params['senderEmail'] => Yii::$app->params['senderName']])
                    ->setTo($order->client_email)
                    ->setSubject('Возврат одобрен - ' . $request->return_number)
                    ->send();
            }
        } catch (\Exception $e) {
            Yii::warning('Ошибка отправки email об одобрении возврата: ' . $e->getMessage(), 'return');
        }
    }

    /**
     * Отправка email об отклонении возврата
     */
    protected function sendRejectionEmail(ReturnRequest $request): void
    {
        try {
            $order = $request->order;
            if ($order && $order->client_email) {
                Yii::$app->mailer->compose('return-rejected', ['request' => $request])
                    ->setFrom([Yii::$app->params['senderEmail'] => Yii::$app->params['senderName']])
                    ->setTo($order->client_email)
                    ->setSubject('Возврат отклонён - ' . $request->return_number)
                    ->send();
            }
        } catch (\Exception $e) {
            Yii::warning('Ошибка отправки email об отклонении возврата: ' . $e->getMessage(), 'return');
        }
    }

    /**
     * Отправка email о завершении возврата
     */
    protected function sendCompletionEmail(ReturnRequest $request): void
    {
        try {
            $order = $request->order;
            if ($order && $order->client_email) {
                Yii::$app->mailer->compose('return-completed', ['request' => $request])
                    ->setFrom([Yii::$app->params['senderEmail'] => Yii::$app->params['senderName']])
                    ->setTo($order->client_email)
                    ->setSubject('Возврат завершён - ' . $request->return_number)
                    ->send();
            }
        } catch (\Exception $e) {
            Yii::warning('Ошибка отправки email о завершении возврата: ' . $e->getMessage(), 'return');
        }
    }

    /**
     * Получить сообщение об ошибке
     * 
     * @return string|null
     */
    public function getErrorMessage(): ?string
    {
        return $this->errorMessage;
    }
}
