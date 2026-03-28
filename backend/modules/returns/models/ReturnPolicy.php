<?php

/**
 * ReturnPolicy — Модель политики возврата
 * 
 * НАЗНАЧЕНИЕ:
 * Определение правил возврата товаров: сроки, условия, категории.
 * 
 * ОСНОВНЫЕ СВОЙСТВА:
 * - name: название политики
 * - description: описание
 * - return_period_days: срок возврата (дней)
 * - is_active: активность
 * - conditions: условия возврата (JSON)
 * 
 * УСЛОВИЯ ВОЗВРАТА:
 * - Товар не был в употреблении
 * - Сохранена упаковка
 * - Сохранены ярлыки
 * - Товар не повреждён
 * 
 * ИСПОЛЬЗОВАНИЕ:
 * $policy = ReturnPolicy::getDefault();
 * $canReturn = $policy->canReturn($order, $product);
 */
namespace app\backend\modules\returns\models;

use Yii;
use yii\db\ActiveRecord;
use yii\behaviors\TimestampBehavior;

/**
 * Модель политики возврата
 *
 * @property int $id
 * @property string $name Название
 * @property string $description Описание
 * @property int $return_period_days Срок возврата (дней)
 * @property bool $requires_original_packaging Требуется оригинальная упаковка
 * @property bool $requires_tags Требуются ярлыки
 * @property bool $allows_worn_items Разрешены ношенные товары
 * @property float|null $restocking_fee Процент за возврат
 * @property bool $free_return_shipping Бесплатная обратная доставка
 * @property string|null $excluded_categories Исключённые категории (JSON)
 * @property string|null $excluded_products Исключённые товары (JSON)
 * @property bool $is_default Политика по умолчанию
 * @property bool $is_active Активность
 * @property string $created_at
 * @property string $updated_at
 */
class ReturnPolicy extends ActiveRecord
{
    public static function tableName()
    {
        return '{{%return_policy}}';
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
            [['name', 'return_period_days'], 'required'],
            [['name'], 'string', 'max' => 255],
            [['description'], 'string'],
            [['return_period_days'], 'integer', 'min' => 1, 'max' => 365],
            [['requires_original_packaging', 'requires_tags', 'allows_worn_items', 'free_return_shipping', 'is_default', 'is_active'], 'boolean'],
            [['restocking_fee'], 'number', 'min' => 0, 'max' => 100],
            [['excluded_categories', 'excluded_products'], 'string'],
            [['is_default'], 'default', 'value' => false],
            [['is_active'], 'default', 'value' => true],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'name' => 'Название',
            'description' => 'Описание',
            'return_period_days' => 'Срок возврата (дней)',
            'requires_original_packaging' => 'Требуется оригинальная упаковка',
            'requires_tags' => 'Требуются ярлыки',
            'allows_worn_items' => 'Разрешены ношенные товары',
            'restocking_fee' => 'Процент за возврат',
            'free_return_shipping' => 'Бесплатная обратная доставка',
            'is_default' => 'По умолчанию',
            'is_active' => 'Активна',
        ];
    }

    /**
     * Получить политику по умолчанию
     * 
     * @return ReturnPolicy|null
     */
    public static function getDefault(): ?ReturnPolicy
    {
        return self::find()
            ->where(['is_default' => true, 'is_active' => true])
            ->one() ?? self::find()
            ->where(['is_active' => true])
            ->one();
    }

    /**
     * Проверить возможность возврата
     * 
     * @param \app\backend\modules\checkout\models\Order $order
     * @param \app\backend\modules\catalog\models\Product $product
     * @return array [canReturn, reason]
     */
    public function canReturn($order, $product = null): array
    {
        // Проверка срока
        $daysSinceOrder = (time() - strtotime($order->created_at)) / 86400;
        if ($daysSinceOrder > $this->return_period_days) {
            return [false, "Срок возврата истёк ({$this->return_period_days} дней)"];
        }
        
        // Проверка статуса заказа
        if ($order->status !== 'delivered') {
            return [false, 'Возврат возможен только для доставленных заказов'];
        }
        
        // Проверка исключённых категорий
        if ($product) {
            $excludedCategories = $this->getExcludedCategories();
            if (in_array($product->category_id, $excludedCategories)) {
                return [false, 'Товар этой категории не подлежит возврату'];
            }
            
            $excludedProducts = $this->getExcludedProducts();
            if (in_array($product->id, $excludedProducts)) {
                return [false, 'Этот товар не подлежит возврату'];
            }
        }
        
        return [true, null];
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
     * Рассчитать сумму возврата
     * 
     * @param float $productPrice Цена товара
     * @param float $shippingCost Стоимость доставки
     * @return float
     */
    public function calculateRefund(float $productPrice, float $shippingCost = 0): float
    {
        $refund = $productPrice;
        
        // Вычитаем процент за возврат
        if ($this->restocking_fee > 0) {
            $refund -= $productPrice * ($this->restocking_fee / 100);
        }
        
        // Вычитаем стоимость обратной доставки
        if (!$this->free_return_shipping && $shippingCost > 0) {
            $refund -= $shippingCost;
        }
        
        return max(0, round($refund, 2));
    }

    /**
     * Получить текст условий возврата
     * 
     * @return string
     */
    public function getConditionsText(): string
    {
        $conditions = [];
        
        $conditions[] = "Срок возврата: {$this->return_period_days} дней";
        
        if ($this->requires_original_packaging) {
            $conditions[] = "Требуется оригинальная упаковка";
        }
        
        if ($this->requires_tags) {
            $conditions[] = "Требуются ярлыки и бирки";
        }
        
        if (!$this->allows_worn_items) {
            $conditions[] = "Товар не должен быть в употреблении";
        }
        
        if ($this->restocking_fee > 0) {
            $conditions[] = "Комиссия за возврат: {$this->restocking_fee}%";
        }
        
        if ($this->free_return_shipping) {
            $conditions[] = "Бесплатная обратная доставка";
        }
        
        return implode('. ', $conditions);
    }
}
