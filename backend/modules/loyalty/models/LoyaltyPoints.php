<?php

/**
 * LoyaltyPoints — Модель баллов лояльности
 * 
 * НАЗНАЧЕНИЕ:
 * История баллов лояльности: начисление, списание, баланс.
 * 
 * ОСНОВНЫЕ СВОЙСТВА:
 * - customer_id: ID клиента
 * - points: количество баллов (положительные - начисление, отрицательные - списание)
 * - balance: баланс после операции
 * - type: тип операции
 * - description: описание
 * - order_id: связанный заказ
 * 
 * ТИПЫ ОПЕРАЦИЙ:
 * - purchase: начисление за покупку
 * - redeem: списание при оплате
 * - bonus: бонусное начисление
 * - referral: начисление за реферала
 * - expired: списание просроченных
 * - admin: ручное начисление/списание
 * 
 * ИСПОЛЬЗОВАНИЕ:
 * LoyaltyPoints::earn($customerId, 100, 'purchase', $orderId);
 * LoyaltyPoints::redeem($customerId, 50, $orderId);
 */
namespace app\backend\modules\loyalty\models;

use Yii;
use yii\db\ActiveRecord;
use yii\behaviors\TimestampBehavior;
use app\backend\modules\account\models\Customer;

/**
 * Модель баллов лояльности
 *
 * @property int $id
 * @property int $customer_id ID клиента
 * @property int $points Количество баллов (+/-)
 * @property int $balance Баланс после операции
 * @property string $type Тип операции
 * @property string $description Описание
 * @property int|null $order_id Связанный заказ
 * @property int|null $related_customer_id Реферал (кто привёл)
 * @property string|null $expires_at Дата истечения
 * @property string $created_at
 *
 * @property \app\backend\modules\account\models\Customer $customer
 */
class LoyaltyPoints extends ActiveRecord
{
    // Типы операций
    const TYPE_PURCHASE = 'purchase';
    const TYPE_REDEEM = 'redeem';
    const TYPE_BONUS = 'bonus';
    const TYPE_REFERRAL = 'referral';
    const TYPE_EXPIRED = 'expired';
    const TYPE_ADMIN = 'admin';
    const TYPE_SIGNUP = 'signup';
    const TYPE_REVIEW = 'review';

    public static function tableName()
    {
        return '{{%loyalty_points}}';
    }

    public function behaviors()
    {
        return [
            [
                'class' => TimestampBehavior::class,
                'updatedAtAttribute' => false,
            ],
        ];
    }

    public function rules()
    {
        return [
            [['customer_id', 'points', 'type'], 'required'],
            [['customer_id', 'order_id', 'related_customer_id'], 'integer'],
            [['points', 'balance'], 'integer'],
            [['description'], 'string', 'max' => 255],
            [['type'], 'string', 'max' => 50],
            [['expires_at'], 'safe'],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'customer_id' => 'Клиент',
            'points' => 'Баллы',
            'balance' => 'Баланс',
            'type' => 'Тип',
            'description' => 'Описание',
            'order_id' => 'Заказ',
            'expires_at' => 'Истекает',
            'created_at' => 'Дата',
        ];
    }

    /**
     * Получить текущий баланс клиента
     *
     * @param int $customerId
     * @return int
     */
    public static function getBalance(int $customerId): int
    {
        $lastRecord = self::find()
            ->where(['customer_id' => $customerId])
            ->orderBy(['id' => SORT_DESC])
            ->one();

        return $lastRecord ? $lastRecord->balance : 0;
    }

    /**
     * Получить суммарное количество заработанных баллов (lifetime earned).
     * Используется для определения уровня лояльности — уровень не должен
     * снижаться при списании баллов.
     *
     * @param int $customerId
     * @return int
     */
    public static function getTotalEarned(int $customerId): int
    {
        return (int) self::find()
            ->where(['customer_id' => $customerId])
            ->andWhere(['>', 'points', 0])
            ->sum('points') ?: 0;
    }

    /**
     * Начислить баллы
     * 
     * @param int $customerId ID клиента
     * @param int $points Количество баллов
     * @param string $type Тип операции
     * @param int|null $orderId ID заказа
     * @param string $description Описание
     * @param int|null $expiresDays Через сколько дней истекут
     * @return bool
     */
    public static function earn(int $customerId, int $points, string $type, ?int $orderId = null, string $description = '', ?int $expiresDays = null): bool
    {
        if ($points <= 0) {
            return false;
        }
        
        $balance = self::getBalance($customerId);
        $newBalance = $balance + $points;
        
        $record = new self();
        $record->customer_id = $customerId;
        $record->points = $points;
        $record->balance = $newBalance;
        $record->type = $type;
        $record->order_id = $orderId;
        $record->description = $description ?: self::getTypeDescription($type);
        
        if ($expiresDays) {
            $record->expires_at = date('Y-m-d H:i:s', strtotime("+{$expiresDays} days"));
        }
        
        return $record->save();
    }

    /**
     * Списать баллы
     * 
     * @param int $customerId ID клиента
     * @param int $points Количество баллов
     * @param int|null $orderId ID заказа
     * @param string $description Описание
     * @return bool
     */
    public static function redeem(int $customerId, int $points, ?int $orderId = null, string $description = ''): bool
    {
        if ($points <= 0) {
            return false;
        }
        
        $balance = self::getBalance($customerId);
        if ($balance < $points) {
            return false;
        }
        
        $newBalance = $balance - $points;
        
        $record = new self();
        $record->customer_id = $customerId;
        $record->points = -$points;
        $record->balance = $newBalance;
        $record->type = self::TYPE_REDEEM;
        $record->order_id = $orderId;
        $record->description = $description ?: 'Списание баллов';
        
        return $record->save();
    }

    /**
     * Начислить баллы за покупку
     * 
     * @param int $customerId
     * @param float $orderAmount
     * @param int $orderId
     * @param float $multiplier Множитель (зависит от уровня)
     * @return bool
     */
    public static function earnForPurchase(int $customerId, float $orderAmount, int $orderId, float $multiplier = 1.0): bool
    {
        // 10 баллов за 1 BYN * множитель уровня
        $points = (int)($orderAmount * 10 * $multiplier);
        
        return self::earn(
            $customerId,
            $points,
            self::TYPE_PURCHASE,
            $orderId,
            "Начисление за заказ #{$orderId}",
            365 // Баллы действительны 1 год
        );
    }

    /**
     * Начислить бонус за регистрацию
     * 
     * @param int $customerId
     * @param int $points
     * @return bool
     */
    public static function earnForSignup(int $customerId, int $points = 100): bool
    {
        return self::earn(
            $customerId,
            $points,
            self::TYPE_SIGNUP,
            null,
            'Бонус за регистрацию',
            90 // Действительны 3 месяца
        );
    }

    /**
     * Начислить за отзыв
     * 
     * @param int $customerId
     * @param int $points
     * @return bool
     */
    public static function earnForReview(int $customerId, int $points = 50): bool
    {
        return self::earn(
            $customerId,
            $points,
            self::TYPE_REVIEW,
            null,
            'Бонус за отзыв'
        );
    }

    /**
     * Начислить за реферала
     * 
     * @param int $customerId Кто привёл
     * @param int $referralId Кого привели
     * @param int $points
     * @return bool
     */
    public static function earnForReferral(int $customerId, int $referralId, int $points = 200): bool
    {
        return self::earn(
            $customerId,
            $points,
            self::TYPE_REFERRAL,
            null,
            'Бонус за приглашённого друга',
            180 // Действительны 6 месяцев
        );
    }

    /**
     * Списать просроченные баллы
     * 
     * @return int Количество списаний
     */
    public static function expireOldPoints(): int
    {
        $expired = self::find()
            ->where(['<', 'expires_at', date('Y-m-d H:i:s')])
            ->andWhere(['>', 'points', 0])
            ->all();
        
        $count = 0;
        foreach ($expired as $record) {
            if (self::redeem(
                $record->customer_id,
                $record->points,
                null,
                'Списание просроченных баллов'
            )) {
                $count++;
            }
        }
        
        return $count;
    }

    /**
     * Связь с клиентом
     */
    public function getCustomer()
    {
        return $this->hasOne(Customer::class, ['id' => 'customer_id']);
    }

    /**
     * Список типов операций
     */
    public static function getTypeList(): array
    {
        return [
            self::TYPE_PURCHASE => 'Покупка',
            self::TYPE_REDEEM => 'Списание',
            self::TYPE_BONUS => 'Бонус',
            self::TYPE_REFERRAL => 'Реферал',
            self::TYPE_EXPIRED => 'Истекли',
            self::TYPE_ADMIN => 'Админ',
            self::TYPE_SIGNUP => 'Регистрация',
            self::TYPE_REVIEW => 'Отзыв',
        ];
    }

    /**
     * Название типа
     */
    public function getTypeName(): string
    {
        return self::getTypeList()[$this->type] ?? $this->type;
    }

    /**
     * Описание типа
     */
    public static function getTypeDescription(string $type): string
    {
        $descriptions = [
            self::TYPE_PURCHASE => 'Начисление за покупку',
            self::TYPE_REDEEM => 'Списание баллов',
            self::TYPE_BONUS => 'Бонусное начисление',
            self::TYPE_REFERRAL => 'Бонус за приглашение друга',
            self::TYPE_EXPIRED => 'Списание просроченных баллов',
            self::TYPE_ADMIN => 'Ручное начисление',
            self::TYPE_SIGNUP => 'Бонус за регистрацию',
            self::TYPE_REVIEW => 'Бонус за отзыв',
        ];
        
        return $descriptions[$type] ?? '';
    }

    /**
     * История баллов клиента
     * 
     * @param int $customerId
     * @param int $limit
     * @return LoyaltyPoints[]
     */
    public static function getHistory(int $customerId, int $limit = 50): array
    {
        return self::find()
            ->where(['customer_id' => $customerId])
            ->orderBy(['created_at' => SORT_DESC])
            ->limit($limit)
            ->all();
    }
}
