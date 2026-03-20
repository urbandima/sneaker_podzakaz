<?php

/**
 * OrderService — Сервис управления заказами
 * 
 * НАЗНАЧЕНИЕ:
 * Инкапсуляция бизнес-логики заказов: создание, обновление, расчёт стоимости.
 * 
 * МЕТОДЫ:
 * - create(): создание заказа из корзины
 * - calculateTotal(): расчёт итоговой суммы
 * - validateItems(): валидация товаров
 * - applyDiscount(): применение скидки
 * 
 * ИСПОЛЬЗОВАНИЕ:
 * $orderService = new OrderService();
 * $order = $orderService->create($cartItems, $customerData);
 */
namespace app\backend\modules\checkout\services;

use Yii;
use yii\db\Transaction;
use app\backend\modules\checkout\models\Order;
use app\backend\modules\checkout\models\OrderItem;
use app\backend\modules\cart\models\Cart;
use app\backend\modules\catalog\models\Product;

class OrderService
{
    /**
     * Создать заказ из корзины
     * 
     * @param array $items Товары корзины
     * @param array $customerData Данные покупателя
     * @return Order|null
     */
    public function create(array $items, array $customerData): ?Order
    {
        if (empty($items)) {
            return null;
        }
        
        // Валидация товаров
        $validationResult = $this->validateItems($items);
        if (!$validationResult['valid']) {
            Yii::error('Order validation failed: ' . implode(', ', $validationResult['errors']), 'order');
            return null;
        }
        
        $transaction = Yii::$app->db->beginTransaction(Transaction::READ_COMMITTED);
        
        try {
            // Создаём заказ
            $order = new Order();
            $order->customer_name = $customerData['name'];
            $order->customer_phone = $customerData['phone'];
            $order->customer_email = $customerData['email'] ?? null;
            $order->customer_address = $customerData['address'] ?? null;
            $order->notes = $customerData['notes'] ?? null;
            $order->status = Order::STATUS_NEW;
            $order->total = 0;
            
            if (!$order->save()) {
                throw new \RuntimeException('Failed to create order: ' . json_encode($order->errors));
            }
            
            // Добавляем товары
            $total = 0;
            foreach ($items as $item) {
                $orderItem = new OrderItem();
                $orderItem->order_id = $order->id;
                $orderItem->product_id = $item->product_id;
                $orderItem->product_name = $item->product->name;
                $orderItem->quantity = $item->quantity;
                $orderItem->price = $item->price;
                $orderItem->size = $item->size;
                $orderItem->color = $item->color;
                
                if (!$orderItem->save()) {
                    throw new \RuntimeException('Failed to create order item: ' . json_encode($orderItem->errors));
                }
                
                $total += $item->price * $item->quantity;
            }
            
            // Применяем скидку если есть
            if (!empty($customerData['promo_code'])) {
                $discount = $this->applyDiscount($total, $customerData['promo_code']);
                $total -= $discount;
                $order->discount = $discount;
                $order->promo_code = $customerData['promo_code'];
            }
            
            // Обновляем итоговую сумму
            $order->total = $total;
            $order->save(false);
            
            // Очищаем корзину
            Cart::clear();
            
            // Логируем создание
            $this->logOrderCreation($order);
            
            $transaction->commit();
            
            return $order;
            
        } catch (\Exception $e) {
            $transaction->rollBack();
            Yii::error('Order creation failed: ' . $e->getMessage(), 'order');
            return null;
        }
    }
    
    /**
     * Валидация товаров заказа
     * 
     * @param array $items
     * @return array ['valid' => bool, 'errors' => array]
     */
    public function validateItems(array $items): array
    {
        $errors = [];
        
        foreach ($items as $item) {
            // Проверка товара
            $product = $item->product ?? Product::findOne($item->product_id);
            if (!$product) {
                $errors[] = "Товар #{$item->product_id} не найден";
                continue;
            }
            
            // Проверка активности
            if (!$product->is_active) {
                $errors[] = "Товар «{$product->name}» недоступен для заказа";
                continue;
            }
            
            // Проверка наличия
            if ($product->stock_status === Product::STOCK_OUT_OF_STOCK) {
                $errors[] = "Товар «{$product->name}» отсутствует на складе";
                continue;
            }
            
            // Проверка количества
            if ($item->quantity < 1 || $item->quantity > 99) {
                $errors[] = "Некорректное количество для товара «{$product->name}»";
            }
            
            // Проверка размера если указан
            if ($item->size !== null) {
                $sizeAvailable = \app\backend\modules\catalog\models\ProductSize::find()
                    ->where(['product_id' => $product->id, 'size_value' => $item->size])
                    ->andWhere(['is_available' => true])
                    ->exists();
                
                if (!$sizeAvailable) {
                    $errors[] = "Размер {$item->size} недоступен для товара «{$product->name}»";
                }
            }
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors,
        ];
    }
    
    /**
     * Рассчитать итоговую сумму
     * 
     * @param array $items
     * @return float
     */
    public function calculateTotal(array $items): float
    {
        $total = 0;
        
        foreach ($items as $item) {
            $total += $item->price * $item->quantity;
        }
        
        return (float) $total;
    }
    
    /**
     * Применить скидку по промокоду
     * 
     * @param float $total
     * @param string $promoCode
     * @return float Сумма скидки
     */
    public function applyDiscount(float $total, string $promoCode): float
    {
        // TODO: Интеграция с системой промокодов
        // Сейчас заглушка - 10% скидка для тестовых кодов
        $testCodes = ['TEST10', 'WELCOME10', 'FIRST10'];
        
        if (in_array(strtoupper($promoCode), $testCodes)) {
            return round($total * 0.1, 2);
        }
        
        return 0;
    }
    
    /**
     * Изменить статус заказа
     * 
     * @param Order $order
     * @param string $newStatus
     * @param string|null $comment
     * @return bool
     */
    public function changeStatus(Order $order, string $newStatus, ?string $comment = null): bool
    {
        $oldStatus = $order->status;
        
        if (!in_array($newStatus, Order::STATUSES)) {
            return false;
        }
        
        $order->status = $newStatus;
        
        if (!$order->save(false)) {
            return false;
        }
        
        // Логируем изменение статуса
        $this->logStatusChange($order, $oldStatus, $newStatus, $comment);
        
        return true;
    }
    
    /**
     * Логирование создания заказа
     */
    private function logOrderCreation(Order $order): void
    {
        Yii::info([
            'order_id' => $order->id,
            'customer' => $order->customer_name,
            'total' => $order->total,
            'items_count' => count($order->items),
        ], 'order_created');
    }
    
    /**
     * Логирование изменения статуса
     */
    private function logStatusChange(Order $order, string $oldStatus, string $newStatus, ?string $comment): void
    {
        Yii::info([
            'order_id' => $order->id,
            'old_status' => $oldStatus,
            'new_status' => $newStatus,
            'comment' => $comment,
        ], 'order_status_changed');
    }
}
