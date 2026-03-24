<?php

/**
 * Coupon — Модель купона
 * 
 * НАЗНАЧЕНИЕ:
 * Купоны и промо-коды: создание, валидация, применение скидок.
 * 
 * ОСНОВНЫЕ СВОЙСТВА:
 * - code: код купона (уникальный)
 * - type: тип скидки (percentage, fixed, free_shipping, buy_x_get_y)
 * - value: значение скидки
 * - min_order_amount: минимальная сумма заказа
 * - max_uses: максимальное количество использований
 * - current_uses: текущее количество использований
 * - valid_from: дата начала действия
 * - valid_until: дата окончания действия
 * - is_active: активность купона
 * 
 * ТИПЫ СКИДОК:
 * - percentage: процентная скидка (value = 10 = 10%)
 * - fixed: фиксированная сумма (value = 500 = 500 BYN)
 * - free_shipping: бесплатная доставка
 * - buy_x_get_y: купи X получи Y бесплатно
 * 
 * ПРАВИЛА:
 * - Ограничение по сумме заказа
 * - Ограничение по количеству использований
 * - Ограничение по дате
 * - Ограничение по товарам/категориям
 * - Ограничение по пользователям
 * 
 * ИСПОЛЬЗОВАНИЕ:
 * $coupon = Coupon::findByCode('SUMMER2024');
 * $isValid = $coupon->isValid($orderAmount, $userId);
 * $discount = $coupon->calculateDiscount($orderAmount);
 */
namespace app\backend\modules\coupon\models;

use Yii;
use yii\db\ActiveRecord;
use yii\behaviors\TimestampBehavior;

/**
 * Модель купона
 *
 * @property int $id
 * @property string $code Код купона
 * @property string $name Название купона
 * @property string $description Описание
 * @property string $type Тип скидки
 * @property float $value Значение скидки
 * @property float|null $min_order_amount Минимальная сумма заказа
 * @property float|null $max_discount Максимальная скидка
 * @property int|null $max_uses Макс. использований
 * @property int $current_uses Текущее использований
 * @property int|null $max_uses_per_user Макс. использований на пользователя
 * @property string|null $valid_from Дата начала
 * @property string|null $valid_until Дата окончания
 * @property bool $is_active Активность
 * @property bool $is_first_order Только для первого заказа
 * @property string|null $applicable_products Применимые товары (JSON)
 * @property string|null $applicable_categories Применимые категории (JSON)
 * @property string|null $excluded_products Исключённые товары (JSON)
 * @property string|null $excluded_categories Исключённые категории (JSON)
 * @property string $created_at
 * @property string $updated_at
 *
 * @property CouponUsage[] $usages
 */
class Coupon extends ActiveRecord
{
    // Типы скидок
    const TYPE_PERCENTAGE = 'percentage';
    const TYPE_FIXED = 'fixed';
    const TYPE_FREE_SHIPPING = 'free_shipping';
    const TYPE_BUY_X_GET_Y = 'buy_x_get_y';
    
    // Статусы
    const STATUS_INACTIVE = 0;
    const STATUS_ACTIVE = 1;

    public static function tableName()
    {
        return '{{%coupon}}';
    }

    public function behaviors()
    {
        return [
            TimestampBehavior::class,
        ];
    }

    public function rules()
    {
        return [
            [['code', 'type', 'value'], 'required'],
            [['code'], 'unique'],
            [['code'], 'string', 'max' => 50],
            [['name', 'description'], 'string', 'max' => 255],
            [['type'], 'in', 'range' => [
                self::TYPE_PERCENTAGE,
                self::TYPE_FIXED,
                self::TYPE_FREE_SHIPPING,
                self::TYPE_BUY_X_GET_Y,
            ]],
            [['value', 'min_order_amount', 'max_discount'], 'number', 'min' => 0],
            [['max_uses', 'current_uses', 'max_uses_per_user'], 'integer', 'min' => 0],
            [['valid_from', 'valid_until'], 'safe'],
            [['is_active', 'is_first_order'], 'boolean'],
            [['is_active'], 'default', 'value' => self::STATUS_ACTIVE],
            [['current_uses'], 'default', 'value' => 0],
            [['applicable_products', 'applicable_categories', 'excluded_products', 'excluded_categories'], 'string'],
            // Для типа buy_x_get_y требуется дополнительное поле
            [['buy_quantity', 'get_quantity'], 'integer', 'min' => 1],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'code' => 'Код купона',
            'name' => 'Название',
            'description' => 'Описание',
            'type' => 'Тип скидки',
            'value' => 'Значение скидки',
            'min_order_amount' => 'Мин. сумма заказа',
            'max_discount' => 'Макс. скидка',
            'max_uses' => 'Макс. использований',
            'current_uses' => 'Использовано',
            'max_uses_per_user' => 'Макс. на пользователя',
            'valid_from' => 'Действует с',
            'valid_until' => 'Действует до',
            'is_active' => 'Активен',
            'is_first_order' => 'Только первый заказ',
            'created_at' => 'Создан',
            'updated_at' => 'Обновлён',
        ];
    }

    /**
     * Найти купон по коду
     * 
     * @param string $code
     * @return Coupon|null
     */
    public static function findByCode(string $code): ?Coupon
    {
        return self::find()
            ->where(['code' => strtoupper($code), 'is_active' => self::STATUS_ACTIVE])
            ->one();
    }

    /**
     * Проверить валидность купона для заказа
     * 
     * @param float $orderAmount Сумма заказа
     * @param int|null $userId ID пользователя
     * @return array [isValid, errorMessage]
     */
    public function isValidForOrder(float $orderAmount, ?int $userId = null): array
    {
        // Проверка активности
        if (!$this->is_active) {
            return [false, 'Купон неактивен'];
        }
        
        // Проверка даты
        $now = time();
        if ($this->valid_from && strtotime($this->valid_from) > $now) {
            return [false, 'Купон ещё не действует'];
        }
        if ($this->valid_until && strtotime($this->valid_until) < $now) {
            return [false, 'Срок действия купона истёк'];
        }
        
        // Проверка минимальной суммы
        if ($this->min_order_amount && $orderAmount < $this->min_order_amount) {
            return [false, "Минимальная сумма заказа: {$this->min_order_amount} BYN"];
        }
        
        // Проверка общего лимита использований
        if ($this->max_uses && $this->current_uses >= $this->max_uses) {
            return [false, 'Лимит использований купона исчерпан'];
        }
        
        // Проверка лимита на пользователя
        if ($userId && $this->max_uses_per_user) {
            $userUses = CouponUsage::find()
                ->where(['coupon_id' => $this->id, 'user_id' => $userId])
                ->count();
            if ($userUses >= $this->max_uses_per_user) {
                return [false, 'Вы уже использовали этот купон максимальное количество раз'];
            }
        }
        
        // Проверка первого заказа
        if ($this->is_first_order && $userId) {
            $ordersCount = \app\backend\modules\checkout\models\Order::find()
                ->where(['customer_id' => $userId])
                ->count();
            if ($ordersCount > 0) {
                return [false, 'Купон действует только для первого заказа'];
            }
        }
        
        return [true, null];
    }

    /**
     * Рассчитать скидку
     * 
     * @param float $orderAmount Сумма заказа
     * @param float $shippingCost Стоимость доставки
     * @return float Сумма скидки
     */
    public function calculateDiscount(float $orderAmount, float $shippingCost = 0): float
    {
        $discount = 0;
        
        switch ($this->type) {
            case self::TYPE_PERCENTAGE:
                $discount = $orderAmount * ($this->value / 100);
                // Ограничение максимальной скидки
                if ($this->max_discount && $discount > $this->max_discount) {
                    $discount = $this->max_discount;
                }
                break;
                
            case self::TYPE_FIXED:
                $discount = $this->value;
                // Скидка не может превышать сумму заказа
                if ($discount > $orderAmount) {
                    $discount = $orderAmount;
                }
                break;
                
            case self::TYPE_FREE_SHIPPING:
                $discount = $shippingCost;
                break;
                
            case self::TYPE_BUY_X_GET_Y:
                // Логика "купи X получи Y" реализуется в CouponService
                $discount = 0;
                break;
        }
        
        return round($discount, 2);
    }

    /**
     * Применить купон (увеличить счётчик использований)
     * 
     * @return bool
     */
    public function apply(): bool
    {
        $this->current_uses++;
        return $this->save(false, ['current_uses', 'updated_at']);
    }

    /**
     * Отменить применение купона (уменьшить счётчик)
     * 
     * @return bool
     */
    public function revert(): bool
    {
        if ($this->current_uses > 0) {
            $this->current_uses--;
            return $this->save(false, ['current_uses', 'updated_at']);
        }
        return true;
    }

    /**
     * Получить список типов скидок
     * 
     * @return array
     */
    public static function getTypeList(): array
    {
        return [
            self::TYPE_PERCENTAGE => 'Процентная скидка',
            self::TYPE_FIXED => 'Фиксированная сумма',
            self::TYPE_FREE_SHIPPING => 'Бесплатная доставка',
            self::TYPE_BUY_X_GET_Y => 'Купи X получи Y',
        ];
    }

    /**
     * Получить название типа
     * 
     * @return string
     */
    public function getTypeName(): string
    {
        return self::getTypeList()[$this->type] ?? $this->type;
    }

    /**
     * Получить описание скидки
     * 
     * @return string
     */
    public function getDiscountDescription(): string
    {
        switch ($this->type) {
            case self::TYPE_PERCENTAGE:
                return "Скидка {$this->value}%";
            case self::TYPE_FIXED:
                return "Скидка {$this->value} BYN";
            case self::TYPE_FREE_SHIPPING:
                return 'Бесплатная доставка';
            case self::TYPE_BUY_X_GET_Y:
                return "Купи {$this->buy_quantity}, получи {$this->get_quantity} бесплатно";
            default:
                return '';
        }
    }

    /**
     * Связь с историей использований
     */
    public function getUsages()
    {
        return $this->hasMany(CouponUsage::class, ['coupon_id' => 'id']);
    }

    /**
     * Проверить применимость к товару
     * 
     * @param int $productId
     * @param int|null $categoryId
     * @return bool
     */
    public function isApplicableToProduct(int $productId, ?int $categoryId = null): bool
    {
        // Проверяем исключённые товары
        $excludedProducts = $this->getExcludedProducts();
        if (in_array($productId, $excludedProducts)) {
            return false;
        }
        
        // Проверяем исключённые категории
        if ($categoryId) {
            $excludedCategories = $this->getExcludedCategories();
            if (in_array($categoryId, $excludedCategories)) {
                return false;
            }
        }
        
        // Если нет ограничений, купон применим ко всем товарам
        $applicableProducts = $this->getApplicableProducts();
        $applicableCategories = $this->getApplicableCategories();
        
        if (empty($applicableProducts) && empty($applicableCategories)) {
            return true;
        }
        
        // Проверяем применимые товары
        if (in_array($productId, $applicableProducts)) {
            return true;
        }
        
        // Проверяем применимые категории
        if ($categoryId && in_array($categoryId, $applicableCategories)) {
            return true;
        }
        
        return false;
    }

    /**
     * Получить применимые товары
     * 
     * @return array
     */
    public function getApplicableProducts(): array
    {
        if (empty($this->applicable_products)) {
            return [];
        }
        return json_decode($this->applicable_products, true) ?: [];
    }

    /**
     * Получить применимые категории
     * 
     * @return array
     */
    public function getApplicableCategories(): array
    {
        if (empty($this->applicable_categories)) {
            return [];
        }
        return json_decode($this->applicable_categories, true) ?: [];
    }

    /**
     * Получить исключённые товары
     * 
     * @return array
     */
    public function getExcludedProducts(): array
    {
        if (empty($this->excluded_products)) {
            return [];
        }
        return json_decode($this->excluded_products, true) ?: [];
    }

    /**
     * Получить исключённые категории
     * 
     * @return array
     */
    public function getExcludedCategories(): array
    {
        if (empty($this->excluded_categories)) {
            return [];
        }
        return json_decode($this->excluded_categories, true) ?: [];
    }
}
