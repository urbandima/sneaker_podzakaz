<?php

namespace app\backend\modules\checkout\services;

use app\backend\modules\checkout\models\Order;
use app\backend\modules\checkout\models\OrderItem;

/**
 * OrderTotalCalculator — сервис расчета суммы заказа
 * 
 * Рекомендация #15: Order Total Calculator
 * 
 * Учитывает:
 * - Стоимость товаров
 * - Скидки (купоны)
 * - Доставка
 * - Налоги
 */
class OrderTotalCalculator
{
    /**
     * Рассчитать итоговую сумму заказа
     */
    public function calculate(Order $order): array
    {
        $items = $order->orderItems;
        
        // Сумма товаров
        $subtotal = $this->calculateSubtotal($items);
        
        // Скидка по купону
        $discount = $this->calculateDiscount($order, $subtotal);
        
        // Доставка
        $shipping = $this->calculateShipping($order);
        
        // Налог (если применимо)
        $tax = $this->calculateTax($subtotal - $discount);
        
        // Итого
        $total = $subtotal - $discount + $shipping + $tax;
        
        return [
            'subtotal' => round($subtotal, 2),
            'discount' => round($discount, 2),
            'discount_percent' => $subtotal > 0 ? round(($discount / $subtotal) * 100, 1) : 0,
            'shipping' => round($shipping, 2),
            'tax' => round($tax, 2),
            'total' => round($total, 2),
        ];
    }
    
    /**
     * Сумма товаров
     */
    private function calculateSubtotal(array $items): float
    {
        $subtotal = 0;
        foreach ($items as $item) {
            $subtotal += $item->price * $item->quantity;
        }
        return $subtotal;
    }
    
    /**
     * Расчет скидки
     */
    private function calculateDiscount(Order $order, float $subtotal): float
    {
        // Если применен купон
        if ($order->coupon_id && $order->coupon) {
            $coupon = $order->coupon;
            
            if ($coupon->type === 'percent') {
                return $subtotal * ($coupon->value / 100);
            } else {
                return min($coupon->value, $subtotal);
            }
        }
        
        return 0;
    }
    
    /**
     * Расчет доставки
     */
    private function calculateShipping(Order $order): float
    {
        // Бесплатная доставка при сумме > 500 BYN
        if ($order->total_amount >= 500) {
            return 0;
        }
        
        return $order->delivery_cost ?? 15;
    }
    
    /**
     * Расчет налога
     */
    private function calculateTax(float $amount): float
    {
        // НДС 20%
        return $amount * 0.2;
    }
    
    /**
     * Обновить сумму заказа
     */
    public function updateOrderTotal(Order $order): void
    {
        $calculated = $this->calculate($order);
        
        $order->total_amount = $calculated['total'];
        $order->discount_amount = $calculated['discount'];
        
        // Сохраняем детали в meta
        $order->calculation_details = json_encode($calculated);
    }
}
