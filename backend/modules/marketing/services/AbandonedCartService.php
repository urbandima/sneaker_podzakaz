<?php

namespace app\backend\modules\marketing\services;

use Yii;
use app\backend\modules\cart\models\Cart;
use app\backend\modules\catalog\models\Customer;

class AbandonedCartService
{
    const ABANDONED_THRESHOLD_HOURS = 24;
    const REMINDER_INTERVALS = [24, 48, 72]; // часы
    
    /**
     * Проверка существования таблицы cart
     */
    private function tableExists(): bool
    {
        try {
            Yii::$app->db->createCommand("SELECT 1 FROM cart LIMIT 1")->execute();
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
    
    /**
     * Получить брошенные корзины
     */
    public function getAbandonedCarts(int $limit = 50): array
    {
        if (!$this->tableExists()) {
            return [];
        }
        
        try {
            $threshold = time() - (self::ABANDONED_THRESHOLD_HOURS * 3600);
            
            return Cart::find()
                ->where(['<', 'updated_at', $threshold])
                ->andWhere(['>', 'items_count', 0])
                ->with('customer')
                ->orderBy(['updated_at' => SORT_DESC])
                ->limit($limit)
                ->all();
        } catch (\Exception $e) {
            Yii::error('Ошибка получения брошенных корзин: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Получить статистику брошенных корзин
     */
    public function getAbandonedCartStats(): array
    {
        if (!$this->tableExists()) {
            return [
                'total_abandoned' => 0,
                'total_value' => 0,
                'avg_value' => 0,
                'recovered_today' => 0,
                'recovery_rate' => 0,
            ];
        }
        
        try {
            $threshold = time() - (self::ABANDONED_THRESHOLD_HOURS * 3600);
            
            $totalAbandoned = Cart::find()
                ->where(['<', 'updated_at', $threshold])
                ->andWhere(['>', 'items_count', 0])
                ->count();
            
            $totalValue = Cart::find()
                ->where(['<', 'updated_at', $threshold])
                ->andWhere(['>', 'items_count', 0])
                ->sum('total_amount') ?: 0;
            
            $avgValue = $totalAbandoned > 0 ? $totalValue / $totalAbandoned : 0;
            
            $recoveredToday = 0; // TODO: добавить отслеживание восстановленных корзин
            
            return [
                'total_abandoned' => $totalAbandoned,
                'total_value' => $totalValue,
                'avg_value' => $avgValue,
                'recovered_today' => $recoveredToday,
                'recovery_rate' => $totalAbandoned > 0 ? ($recoveredToday / $totalAbandoned * 100) : 0,
            ];
        } catch (\Exception $e) {
            Yii::error('Ошибка получения статистики корзин: ' . $e->getMessage());
            return [
                'total_abandoned' => 0,
                'total_value' => 0,
                'avg_value' => 0,
                'recovered_today' => 0,
                'recovery_rate' => 0,
            ];
        }
    }
    
    /**
     * Отправить напоминание о брошенной корзине
     */
    public function sendAbandonedCartEmail(Cart $cart): bool
    {
        if (!$cart->customer || !$cart->customer->email) {
            return false;
        }
        
        $customer = $cart->customer;
        
        try {
            Yii::$app->mailer->compose('abandoned-cart', [
                'customer' => $customer,
                'cart' => $cart,
                'recoveryUrl' => Yii::$app->urlManager->createAbsoluteUrl(['/cart/index']),
            ])
            ->setFrom([Yii::$app->params['senderEmail'] ?? 'info@sneakerhead.by' => Yii::$app->params['senderName'] ?? 'Sneakerhead'])
            ->setTo($customer->email)
            ->setSubject('Вы забыли товары в корзине!')
            ->send();
            
            return true;
        } catch (\Exception $e) {
            Yii::error('Ошибка отправки письма о брошенной корзине: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Автоматическая отправка напоминаний
     */
    public function sendAutomaticReminders(): int
    {
        if (!$this->tableExists()) {
            return 0;
        }
        
        $sent = 0;
        
        try {
            foreach (self::REMINDER_INTERVALS as $hours) {
                $threshold = time() - ($hours * 3600);
                $nextThreshold = time() - (($hours + 1) * 3600);
                
                $carts = Cart::find()
                    ->where(['between', 'updated_at', $nextThreshold, $threshold])
                    ->andWhere(['>', 'items_count', 0])
                    ->with('customer')
                    ->limit(100)
                    ->all();
                
                foreach ($carts as $cart) {
                    if ($this->sendAbandonedCartEmail($cart)) {
                        $sent++;
                    }
                }
            }
        } catch (\Exception $e) {
            Yii::error('Ошибка автоматической отправки напоминаний: ' . $e->getMessage());
        }
        
        return $sent;
    }
    
    /**
     * Создать промокод для восстановления корзины
     */
    public function createRecoveryDiscount(Cart $cart, float $discountPercent = 10): ?string
    {
        // TODO: интеграция с системой купонов
        $code = 'CART' . strtoupper(substr(md5($cart->id . time()), 0, 8));
        
        return $code;
    }
}
