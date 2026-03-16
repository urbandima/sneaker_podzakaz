<?php

/**
 * CouponUsage — История использования купонов
 * 
 * НАЗНАЧЕНИЕ:
 * Отслеживание использования купонов: кто, когда, какой заказ.
 * 
 * ОСНОВНЫЕ СВОЙСТВА:
 * - coupon_id: ID купона
 * - order_id: ID заказа
 * - user_id: ID пользователя
 * - discount_amount: сумма скидки
 * - used_at: дата использования
 * 
 * ИСПОЛЬЗОВАНИЕ:
 * - Отчёты по использованию купонов
 * - Проверка лимитов на пользователя
 * - Аналитика эффективности купонов
 */
namespace app\backend\modules\coupon\models;

use Yii;
use yii\db\ActiveRecord;
use app\backend\modules\checkout\models\Order;
use app\backend\modules\coupon\models\Coupon;

/**
 * Модель истории использования купонов
 *
 * @property int $id
 * @property int $coupon_id ID купона
 * @property int $order_id ID заказа
 * @property int|null $user_id ID пользователя
 * @property float $discount_amount Сумма скидки
 * @property string $used_at Дата использования
 *
 * @property Coupon $coupon
 * @property Order $order
 */
class CouponUsage extends ActiveRecord
{
    public static function tableName()
    {
        return '{{%coupon_usage}}';
    }

    public function rules()
    {
        return [
            [['coupon_id', 'order_id', 'discount_amount'], 'required'],
            [['coupon_id', 'order_id', 'user_id'], 'integer'],
            [['discount_amount'], 'number'],
            [['used_at'], 'safe'],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'coupon_id' => 'Купон',
            'order_id' => 'Заказ',
            'user_id' => 'Пользователь',
            'discount_amount' => 'Сумма скидки',
            'used_at' => 'Дата использования',
        ];
    }

    /**
     * Связь с купоном
     */
    public function getCoupon()
    {
        return $this->hasOne(Coupon::class, ['id' => 'coupon_id']);
    }

    /**
     * Связь с заказом
     */
    public function getOrder()
    {
        return $this->hasOne(Order::class, ['id' => 'order_id']);
    }

    /**
     * Зафиксировать использование купона
     * 
     * @param int $couponId
     * @param int $orderId
     * @param float $discountAmount
     * @param int|null $userId
     * @return bool
     */
    public static function record(int $couponId, int $orderId, float $discountAmount, ?int $userId = null): bool
    {
        $usage = new self();
        $usage->coupon_id = $couponId;
        $usage->order_id = $orderId;
        $usage->discount_amount = $discountAmount;
        $usage->user_id = $userId;
        $usage->used_at = date('Y-m-d H:i:s');
        
        return $usage->save();
    }
}
