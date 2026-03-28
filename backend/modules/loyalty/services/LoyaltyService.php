<?php

/**
 * LoyaltyService — Сервис программы лояльности
 * 
 * НАЗНАЧЕНИЕ:
 * Управление программой лояльности: расчёт баллов, уровней, скидок.
 * 
 * ФУНКЦИИ:
 * - calculateEarnPoints() - расчёт начисляемых баллов
 * - calculateRedeemDiscount() - расчёт скидки при списании
 * - getCustomerLevel() - получение уровня клиента
 * - processOrderCompletion() - обработка завершения заказа
 * - redeemPoints() - списание баллов при оплате
 * 
 * ИСПОЛЬЗОВАНИЕ:
 * $service = new LoyaltyService();
 * $points = $service->calculateEarnPoints($customerId, $orderAmount);
 */
namespace app\backend\modules\loyalty\services;

use Yii;
use yii\base\Component;
use app\backend\modules\loyalty\models\LoyaltyProgram;
use app\backend\modules\loyalty\models\LoyaltyPoints;

class LoyaltyService extends Component
{
    /** @var int Баллы за 1 BYN */
    public $pointsPerByn = 10;
    
    /** @var float Стоимость 1 балла в BYN */
    public $pointValue = 0.01;
    
    /** @var int Минимум баллов для списания */
    public $minPointsToRedeem = 100;
    
    /** @var int Максимум % заказа, который можно оплатить баллами */
    public $maxRedeemPercent = 50;

    /**
     * Рассчитать начисляемые баллы за заказ
     * 
     * @param int $customerId ID клиента
     * @param float $orderAmount Сумма заказа
     * @return int
     */
    public function calculateEarnPoints(int $customerId, float $orderAmount): int
    {
        // Получаем уровень клиента
        $level = $this->getCustomerLevel($customerId);
        $multiplier = $level ? $level->points_multiplier : 1.0;
        
        // Базовые баллы * множитель уровня
        $points = (int)($orderAmount * $this->pointsPerByn * $multiplier);
        
        return $points;
    }

    /**
     * Рассчитать скидку при списании баллов
     * 
     * @param int $points Количество баллов
     * @return float
     */
    public function calculateRedeemDiscount(int $points): float
    {
        return round($points * $this->pointValue, 2);
    }

    /**
     * Получить уровень клиента.
     * Уровень определяется по сумме ЗАРАБОТАННЫХ баллов за всё время (lifetime earned),
     * а не по текущему остатку — чтобы уровень не снижался при расходовании баллов.
     *
     * @param int $customerId
     * @return LoyaltyProgram|null
     */
    public function getCustomerLevel(int $customerId): ?LoyaltyProgram
    {
        $totalEarned = LoyaltyPoints::getTotalEarned($customerId);
        return LoyaltyProgram::getLevelByPoints($totalEarned);
    }

    /**
     * Получить баланс клиента
     * 
     * @param int $customerId
     * @return int
     */
    public function getCustomerBalance(int $customerId): int
    {
        return LoyaltyPoints::getBalance($customerId);
    }

    /**
     * Обработать завершение заказа (начислить баллы)
     * 
     * @param int $customerId ID клиента
     * @param float $orderAmount Сумма заказа
     * @param int $orderId ID заказа
     * @return int Количество начисленных баллов
     */
    public function processOrderCompletion(int $customerId, float $orderAmount, int $orderId): int
    {
        $points = $this->calculateEarnPoints($customerId, $orderAmount);
        
        if ($points > 0) {
            $level = $this->getCustomerLevel($customerId);
            $multiplier = $level ? $level->points_multiplier : 1.0;
            
            LoyaltyPoints::earnForPurchase($customerId, $orderAmount, $orderId, $multiplier);
        }
        
        return $points;
    }

    /**
     * Списать баллы при оплате заказа
     * 
     * @param int $customerId ID клиента
     * @param int $points Количество баллов
     * @param int $orderId ID заказа
     * @return array [success, discount, message]
     */
    public function redeemPoints(int $customerId, int $points, int $orderId): array
    {
        // Проверка минимума
        if ($points < $this->minPointsToRedeem) {
            return [
                'success' => false,
                'discount' => 0,
                'message' => "Минимум для списания: {$this->minPointsToRedeem} баллов",
            ];
        }
        
        // Проверка баланса
        $balance = $this->getCustomerBalance($customerId);
        if ($balance < $points) {
            return [
                'success' => false,
                'discount' => 0,
                'message' => "Недостаточно баллов. Ваш баланс: {$balance}",
            ];
        }
        
        // Рассчитываем скидку
        $discount = $this->calculateRedeemDiscount($points);
        
        // Списываем баллы
        if (LoyaltyPoints::redeem($customerId, $points, $orderId)) {
            return [
                'success' => true,
                'discount' => $discount,
                'message' => "Списано {$points} баллов. Скидка: {$discount} BYN",
            ];
        }
        
        return [
            'success' => false,
            'discount' => 0,
            'message' => 'Ошибка при списании баллов',
        ];
    }

    /**
     * Получить максимальное количество баллов для списания
     * 
     * @param int $customerId ID клиента
     * @param float $orderAmount Сумма заказа
     * @return int
     */
    public function getMaxRedeemPoints(int $customerId, float $orderAmount): int
    {
        $balance = $this->getCustomerBalance($customerId);
        $maxByOrder = (int)(($orderAmount * $this->maxRedeemPercent / 100) / $this->pointValue);
        
        return min($balance, $maxByOrder);
    }

    /**
     * Получить информацию о программе лояльности для клиента
     * 
     * @param int $customerId
     * @return array
     */
    public function getCustomerInfo(int $customerId): array
    {
        $balance = $this->getCustomerBalance($customerId);
        $level = $this->getCustomerLevel($customerId);
        $nextLevel = $level ? $level->getNextLevel() : null;
        
        return [
            'balance' => $balance,
            'level' => $level,
            'nextLevel' => $nextLevel,
            'pointsToNextLevel' => $nextLevel ? $level->getPointsToNextLevel($balance) : 0,
            'canRedeem' => $balance >= $this->minPointsToRedeem,
            'redeemValue' => $this->calculateRedeemDiscount($balance),
        ];
    }

    /**
     * Начислить бонус за регистрацию
     * 
     * @param int $customerId
     * @return bool
     */
    public function processSignup(int $customerId): bool
    {
        return LoyaltyPoints::earnForSignup($customerId, 100);
    }

    /**
     * Начислить бонус за отзыв
     * 
     * @param int $customerId
     * @return bool
     */
    public function processReview(int $customerId): bool
    {
        return LoyaltyPoints::earnForReview($customerId, 50);
    }

    /**
     * Начислить бонус за реферала
     * 
     * @param int $referralId Кто пригласил
     * @param int $newCustomerId Новый клиент
     * @return bool
     */
    public function processReferral(int $referralId, int $newCustomerId): bool
    {
        // Начисляем пригласившему
        $result = LoyaltyPoints::earnForReferral($referralId, $newCustomerId, 200);
        
        // Начисляем новому клиенту
        if ($result) {
            LoyaltyPoints::earn(
                $newCustomerId,
                100,
                LoyaltyPoints::TYPE_BONUS,
                null,
                'Бонус за регистрацию по приглашению',
                90
            );
        }
        
        return $result;
    }

    /**
     * Получить статистику программы лояльности
     * 
     * @return array
     */
    public function getStatistics(): array
    {
        return [
            'totalCustomers' => (int)LoyaltyPoints::find()
                ->select('customer_id')
                ->distinct()
                ->count(),
            'totalPointsIssued' => (int)LoyaltyPoints::find()
                ->where(['>', 'points', 0])
                ->sum('points'),
            'totalPointsRedeemed' => abs((int)LoyaltyPoints::find()
                ->where(['<', 'points', 0])
                ->sum('points')),
            'totalDiscounts' => abs((int)LoyaltyPoints::find()
                ->where(['type' => LoyaltyPoints::TYPE_REDEEM])
                ->sum('points')) * $this->pointValue,
        ];
    }
}
