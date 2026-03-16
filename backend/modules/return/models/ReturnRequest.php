<?php

/**
 * ReturnRequest — Модель заявки на возврат
 * 
 * НАЗНАЧЕНИЕ:
 * Заявки на возврат товаров: создание, обработка, статусы.
 * 
 * ОСНОВНЫЕ СВОЙСТВА:
 * - order_id: ID заказа
 * - customer_id: ID клиента
 * - status: статус заявки
 * - reason: причина возврата
 * - items_json: возвращаемые товары (JSON)
 * - refund_amount: сумма возврата
 * 
 * СТАТУСЫ:
 * - pending: ожидает обработки
 * - approved: одобрено
 * - rejected: отклонено
 * - processing: в обработке
 * - completed: завершено
 * 
 * ИСПОЛЬЗОВАНИЕ:
 * $request = ReturnRequest::create($orderId, $items, $reason);
 */
namespace app\backend\modules\return\models;

use Yii;
use yii\db\ActiveRecord;
use yii\behaviors\TimestampBehavior;
use app\backend\modules\checkout\models\Order;
use app\backend\modules\return\models\ReturnPolicy;

/**
 * Модель заявки на возврат
 *
 * @property int $id
 * @property int $order_id ID заказа
 * @property int|null $customer_id ID клиента
 * @property string $return_number Номер заявки
 * @property string $status Статус
 * @property string $reason Причина возврата
 * @property string|null $comment Комментарий клиента
 * @property string|null $admin_comment Комментарий админа
 * @property string $items_json Возвращаемые товары (JSON)
 * @property float $refund_amount Сумма возврата
 * @property string|null $refund_method Способ возврата
 * @property string|null $refund_transaction ID транзакции возврата
 * @property string|null $pickup_address Адрес забора
 * @property string|null $pickup_date Дата забора
 * @property string|null $tracking_number Трек-номер возврата
 * @property string|null $processed_at Дата обработки
 * @property string|null $completed_at Дата завершения
 * @property string $created_at
 * @property string $updated_at
 *
 * @property \app\backend\modules\checkout\models\Order $order
 */
class ReturnRequest extends ActiveRecord
{
    // Статусы
    const STATUS_PENDING = 'pending';
    const STATUS_APPROVED = 'approved';
    const STATUS_REJECTED = 'rejected';
    const STATUS_PROCESSING = 'processing';
    const STATUS_COMPLETED = 'completed';
    
    // Причины возврата
    const REASON_DEFECT = 'defect';
    const REASON_WRONG_ITEM = 'wrong_item';
    const REASON_NOT_AS_DESCRIBED = 'not_as_described';
    const REASON_SIZE_ISSUE = 'size_issue';
    const REASON_CHANGED_MIND = 'changed_mind';
    const REASON_DAMAGED = 'damaged';
    const REASON_OTHER = 'other';

    public static function tableName()
    {
        return '{{%return_request}}';
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
            [['order_id', 'reason'], 'required'],
            [['order_id', 'customer_id'], 'integer'],
            [['refund_amount'], 'number'],
            [['items_json', 'comment', 'admin_comment'], 'string'],
            [['return_number', 'status', 'reason', 'refund_method', 'refund_transaction', 'tracking_number'], 'string', 'max' => 255],
            [['pickup_address'], 'string', 'max' => 500],
            [['pickup_date', 'processed_at', 'completed_at'], 'safe'],
            [['status'], 'default', 'value' => self::STATUS_PENDING],
            [['return_number'], 'default', 'value' => function() {
                return 'R' . date('Ymd') . str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
            }],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'order_id' => 'Заказ',
            'customer_id' => 'Клиент',
            'return_number' => 'Номер заявки',
            'status' => 'Статус',
            'reason' => 'Причина возврата',
            'comment' => 'Комментарий',
            'admin_comment' => 'Комментарий админа',
            'refund_amount' => 'Сумма возврата',
            'refund_method' => 'Способ возврата',
            'pickup_address' => 'Адрес забора',
            'pickup_date' => 'Дата забора',
            'tracking_number' => 'Трек-номер',
            'created_at' => 'Создана',
        ];
    }

    /**
     * Создать заявку на возврат
     * 
     * @param int $orderId ID заказа
     * @param array $items Массив товаров [['product_id' => 1, 'quantity' => 1, 'price' => 100]]
     * @param string $reason Причина
     * @param string|null $comment Комментарий
     * @return ReturnRequest|null
     */
    public static function create(int $orderId, array $items, string $reason, ?string $comment = null): ?ReturnRequest
    {
        $order = Order::findOne($orderId);
        if (!$order) {
            return null;
        }
        
        // Проверяем политику возврата
        $policy = ReturnPolicy::getDefault();
        if (!$policy) {
            return null;
        }
        
        list($canReturn, $error) = $policy->canReturn($order);
        if (!$canReturn) {
            return null;
        }
        
        // Создаём заявку
        $request = new self();
        $request->order_id = $orderId;
        $request->customer_id = $order->customer_id;
        $request->reason = $reason;
        $request->comment = $comment;
        $request->items_json = json_encode($items, JSON_UNESCAPED_UNICODE);
        
        // Рассчитываем сумму возврата
        $totalRefund = 0;
        foreach ($items as $item) {
            $totalRefund += ($item['price'] ?? 0) * ($item['quantity'] ?? 1);
        }
        $request->refund_amount = $policy->calculateRefund($totalRefund);
        
        if ($request->save()) {
            return $request;
        }
        
        return null;
    }

    /**
     * Одобрить заявку
     * 
     * @param string|null $adminComment Комментарий админа
     * @return bool
     */
    public function approve(?string $adminComment = null): bool
    {
        $this->status = self::STATUS_APPROVED;
        $this->admin_comment = $adminComment;
        $this->processed_at = date('Y-m-d H:i:s');
        
        return $this->save(false);
    }

    /**
     * Отклонить заявку
     * 
     * @param string $reason Причина отклонения
     * @return bool
     */
    public function reject(string $reason): bool
    {
        $this->status = self::STATUS_REJECTED;
        $this->admin_comment = $reason;
        $this->processed_at = date('Y-m-d H:i:s');
        
        return $this->save(false);
    }

    /**
     * Начать обработку
     * 
     * @return bool
     */
    public function startProcessing(): bool
    {
        $this->status = self::STATUS_PROCESSING;
        return $this->save(false);
    }

    /**
     * Завершить возврат
     * 
     * @param string $transaction ID транзакции возврата
     * @return bool
     */
    public function complete(string $transaction): bool
    {
        $this->status = self::STATUS_COMPLETED;
        $this->refund_transaction = $transaction;
        $this->completed_at = date('Y-m-d H:i:s');
        
        return $this->save(false);
    }

    /**
     * Получить товары заявки
     * 
     * @return array
     */
    public function getItems(): array
    {
        if (empty($this->items_json)) {
            return [];
        }
        return json_decode($this->items_json, true) ?: [];
    }

    /**
     * Связь с заказом
     */
    public function getOrder()
    {
        return $this->hasOne(Order::class, ['id' => 'order_id']);
    }

    /**
     * Список статусов
     */
    public static function getStatusList(): array
    {
        return [
            self::STATUS_PENDING => 'Ожидает',
            self::STATUS_APPROVED => 'Одобрено',
            self::STATUS_REJECTED => 'Отклонено',
            self::STATUS_PROCESSING => 'В обработке',
            self::STATUS_COMPLETED => 'Завершено',
        ];
    }

    /**
     * Название статуса
     */
    public function getStatusName(): string
    {
        return self::getStatusList()[$this->status] ?? $this->status;
    }

    /**
     * CSS класс статуса
     */
    public function getStatusClass(): string
    {
        $classes = [
            self::STATUS_PENDING => 'warning',
            self::STATUS_APPROVED => 'success',
            self::STATUS_REJECTED => 'danger',
            self::STATUS_PROCESSING => 'info',
            self::STATUS_COMPLETED => 'primary',
        ];
        return $classes[$this->status] ?? 'secondary';
    }

    /**
     * Список причин
     */
    public static function getReasonList(): array
    {
        return [
            self::REASON_DEFECT => 'Дефект товара',
            self::REASON_WRONG_ITEM => 'Не тот товар',
            self::REASON_NOT_AS_DESCRIBED => 'Не соответствует описанию',
            self::REASON_SIZE_ISSUE => 'Не подошёл размер',
            self::REASON_CHANGED_MIND => 'Передумал',
            self::REASON_DAMAGED => 'Повреждён при доставке',
            self::REASON_OTHER => 'Другая причина',
        ];
    }

    /**
     * Название причины
     */
    public function getReasonName(): string
    {
        return self::getReasonList()[$this->reason] ?? $this->reason;
    }
}
