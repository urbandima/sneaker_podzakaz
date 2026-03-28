<?php

/**
 * CouponService — Сервис купонов
 * 
 * НАЗНАЧЕНИЕ:
 * Валидация, применение и отмена купонов для заказов.
 * 
 * ФУНКЦИИ:
 * - validateCoupon() - валидация купона
 * - applyCoupon() - применение купона к заказу
 * - removeCoupon() - отмена купона из заказа
 * - calculateDiscount() - расчёт скидки
 * - getAvailableCoupons() - получение доступных купонов
 * 
 * ИСПОЛЬЗОВАНИЕ:
 * $service = new CouponService();
 * $result = $service->applyCoupon('SUMMER2024', $order);
 * 
 * ОСОБЕННОСТИ:
 * - Поддержка всех типов скидок
 * - Проверка применимости к товарам
 * - Автоматический расчёт скидки
 * - Интеграция с корзиной и заказом
 */
namespace app\backend\modules\coupon\services;

use Yii;
use yii\base\Component;
use app\backend\modules\coupon\models\Coupon;
use app\backend\modules\coupon\models\CouponUsage;
use app\backend\modules\checkout\models\Order;
use app\backend\modules\checkout\models\OrderItem;

class CouponService extends Component
{
    /** @var string|null Сообщение об ошибке */
    private $errorMessage = null;

    /**
     * Валидация купона
     * 
     * @param string $code Код купона
     * @param float $orderAmount Сумма заказа
     * @param int|null $userId ID пользователя
     * @return Coupon|null
     */
    public function validateCoupon(string $code, float $orderAmount, ?int $userId = null): ?Coupon
    {
        $this->errorMessage = null;
        
        $coupon = Coupon::findByCode($code);
        if (!$coupon) {
            $this->errorMessage = 'Купон не найден';
            return null;
        }
        
        list($isValid, $error) = $coupon->isValidForOrder($orderAmount, $userId);
        if (!$isValid) {
            $this->errorMessage = $error;
            return null;
        }
        
        return $coupon;
    }

    /**
     * Применить купон к заказу
     * 
     * @param string $code Код купона
     * @param Order $order Заказ
     * @return array [success, discount, message]
     */
    public function applyCoupon(string $code, Order $order): array
    {
        $this->errorMessage = null;
        
        // Валидация купона
        $coupon = $this->validateCoupon($code, $order->total_amount, $order->customer_id);
        if (!$coupon) {
            return [
                'success' => false,
                'discount' => 0,
                'message' => $this->errorMessage,
            ];
        }
        
        // Проверяем применимость к товарам заказа
        if (!$this->checkApplicability($coupon, $order)) {
            return [
                'success' => false,
                'discount' => 0,
                'message' => 'Купон не применим к товарам в заказе',
            ];
        }
        
        // Рассчитываем скидку
        $discount = $this->calculateDiscount($coupon, $order);
        
        // Применяем купон к заказу
        $order->coupon_id = $coupon->id;
        $order->coupon_code = $coupon->code;
        $order->discount_amount = $discount;
        $order->total_amount = $order->product_price + $order->delivery_cost - $discount;
        
        if (!$order->save()) {
            return [
                'success' => false,
                'discount' => 0,
                'message' => 'Ошибка при применении купона',
            ];
        }
        
        // Увеличиваем счётчик использований купона
        $coupon->apply();
        
        // Фиксируем использование
        CouponUsage::record(
            $coupon->id,
            $order->id,
            $discount,
            $order->customer_id
        );
        
        return [
            'success' => true,
            'discount' => $discount,
            'message' => "Купон применён! Скидка: {$discount} BYN",
            'coupon' => $coupon,
        ];
    }

    /**
     * Отменить купон из заказа
     * 
     * @param Order $order Заказ
     * @return bool
     */
    public function removeCoupon(Order $order): bool
    {
        if (!$order->coupon_id) {
            $this->errorMessage = 'Купон не применён к заказу';
            return false;
        }
        
        $coupon = Coupon::findOne($order->coupon_id);
        if ($coupon) {
            // Уменьшаем счётчик использований
            $coupon->revert();
        }
        
        // Возвращаем сумму заказа
        $order->total_amount = $order->product_price + $order->delivery_cost;
        $order->discount_amount = 0;
        $order->coupon_id = null;
        $order->coupon_code = null;
        
        if (!$order->save()) {
            $this->errorMessage = 'Ошибка при отмене купона';
            return false;
        }
        
        return true;
    }

    /**
     * Рассчитать скидку для заказа
     * 
     * @param Coupon $coupon Купон
     * @param Order $order Заказ
     * @return float
     */
    public function calculateDiscount(Coupon $coupon, Order $order): float
    {
        // Для типа buy_x_get_y нужна особая логика
        if ($coupon->type === Coupon::TYPE_BUY_X_GET_Y) {
            return $this->calculateBuyXGetY($coupon, $order);
        }
        
        // Для остальных типов используем стандартный расчёт
        return $coupon->calculateDiscount($order->product_price, $order->delivery_cost);
    }

    /**
     * Рассчитать скидку по типу "Купи X получи Y"
     * 
     * @param Coupon $coupon
     * @param Order $order
     * @return float
     */
    protected function calculateBuyXGetY(Coupon $coupon, Order $order): float
    {
        $buyQuantity = $coupon->buy_quantity ?? 1;
        $getQuantity = $coupon->get_quantity ?? 1;

        // Получаем товары заказа через relation (не через публичное свойство $order->items)
        $items = $order->orderItems;
        if (empty($items)) {
            return 0;
        }
        
        // Сортируем по цене (дешёвые бесплатно)
        usort($items, function($a, $b) {
            return $a->price - $b->price;
        });
        
        $discount = 0;
        $totalItems = count($items);
        
        // Каждые X товаров даём Y бесплатно
        $freeItemsCount = floor($totalItems / ($buyQuantity + $getQuantity)) * $getQuantity;
        
        for ($i = 0; $i < $freeItemsCount && $i < $totalItems; $i++) {
            $discount += $items[$i]->price;
        }
        
        return round($discount, 2);
    }

    /**
     * Проверить применимость купона к товарам заказа
     * 
     * @param Coupon $coupon
     * @param Order $order
     * @return bool
     */
    protected function checkApplicability(Coupon $coupon, Order $order): bool
    {
        // Используем relation orderItems, а не публичное свойство items (которое всегда [])
        $items = $order->orderItems;
        if (empty($items)) {
            return false;
        }
        
        // Проверяем каждый товар
        foreach ($items as $item) {
            if ($coupon->isApplicableToProduct($item->product_id, $item->category_id ?? null)) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Получить доступные купоны для пользователя
     * 
     * @param int|null $userId
     * @param float $orderAmount
     * @return Coupon[]
     */
    public function getAvailableCoupons(?int $userId = null, float $orderAmount = 0): array
    {
        $query = Coupon::find()
            ->where(['is_active' => Coupon::STATUS_ACTIVE])
            ->andWhere(['<=', 'valid_from', date('Y-m-d')])
            ->andWhere(['>=', 'valid_until', date('Y-m-d')])
            ->orderBy(['created_at' => SORT_DESC]);
        
        $coupons = $query->all();
        
        // Фильтруем по валидности
        $validCoupons = [];
        foreach ($coupons as $coupon) {
            list($isValid, $error) = $coupon->isValidForOrder($orderAmount, $userId);
            if ($isValid) {
                $validCoupons[] = $coupon;
            }
        }
        
        return $validCoupons;
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

    /**
     * Получить рекомендуемые купоны для заказа
     * 
     * @param Order $order
     * @return array
     */
    public function getRecommendedCoupons(Order $order): array
    {
        $coupons = $this->getAvailableCoupons($order->customer_id, $order->total_amount);
        
        $recommended = [];
        foreach ($coupons as $coupon) {
            $discount = $this->calculateDiscount($coupon, $order);
            if ($discount > 0) {
                $recommended[] = [
                    'coupon' => $coupon,
                    'discount' => $discount,
                    'description' => $coupon->getDiscountDescription(),
                ];
            }
        }
        
        // Сортируем по размеру скидки
        usort($recommended, function($a, $b) {
            return $b['discount'] - $a['discount'];
        });
        
        return $recommended;
    }
}
