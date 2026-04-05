<?php

namespace app\backend\modules\checkout\services;

use yii\db\Expression;

/**
 * OrderNumberGenerator — генератор номеров заказов
 * 
 * Рекомендация #20: Order Number Generator
 * 
 * Формат: YYYYMMDD-XXXXX (например: 20260330-00042)
 */
class OrderNumberGenerator
{
    /**
     * Сгенерировать номер заказа
     */
    public function generate(): string
    {
        $date = date('Ymd');
        $counter = $this->getNextCounter();
        
        return sprintf('%s-%05d', $date, $counter);
    }
    
    /**
     * Получить следующий счетчик
     */
    private function getNextCounter(): int
    {
        $db = \Yii::$app->db;
        
        // Используем таблицу для хранения счетчика
        // или просто считаем заказы за сегодня
        $today = date('Y-m-d');
        $count = (new \yii\db\Query())
            ->from('{{%order}}')
            ->where(['>=', 'created_at', strtotime($today)])
            ->count();
        
        return (int)$count + 1;
    }
    
    /**
     * Проверить уникальность номера
     */
    public function isUnique(string $number): bool
    {
        return !(new \yii\db\Query())
            ->from('{{%order}}')
            ->where(['order_number' => $number])
            ->exists();
    }
    
    /**
     * Сгенерировать уникальный номер с проверкой
     */
    public function generateUnique(int $maxAttempts = 10): ?string
    {
        for ($i = 0; $i < $maxAttempts; $i++) {
            $number = $this->generate();
            
            if ($this->isUnique($number)) {
                return $number;
            }
            
            // Небольшая задержка для изменения времени
            usleep(100000); // 0.1 сек
        }
        
        return null;
    }
}
