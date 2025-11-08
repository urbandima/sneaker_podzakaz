<?php

namespace app\models;

use Yii;
use yii\db\ActiveRecord;
use yii\behaviors\TimestampBehavior;

/**
 * Модель CatalogInquiry (Заявка из каталога)
 *
 * @property int $id
 * @property int $product_id
 * @property string $name
 * @property string $phone
 * @property string|null $email
 * @property string|null $message
 * @property string|null $size
 * @property string|null $color
 * @property string $status
 * @property string $created_at
 * @property string $updated_at
 * 
 * @property Product $product
 * @property Order $order
 */
class CatalogInquiry extends ActiveRecord
{
    const STATUS_NEW = 'new';
    const STATUS_PROCESSING = 'processing';
    const STATUS_COMPLETED = 'completed';
    const STATUS_CANCELLED = 'cancelled';

    public static function tableName()
    {
        return 'catalog_inquiry';
    }

    public function behaviors()
    {
        return [
            [
                'class' => TimestampBehavior::class,
                'value' => function() { return date('Y-m-d H:i:s'); },
            ],
        ];
    }

    public function rules()
    {
        return [
            [['product_id', 'name', 'phone'], 'required'],
            [['product_id'], 'integer'],
            [['name', 'email'], 'string', 'max' => 255],
            [['color'], 'string', 'max' => 100],
            [['message'], 'string'],
            [['email'], 'email'],
            [['status'], 'in', 'range' => [self::STATUS_NEW, self::STATUS_PROCESSING, self::STATUS_COMPLETED, self::STATUS_CANCELLED]],
            [['status'], 'default', 'value' => self::STATUS_NEW],
            [['product_id'], 'exist', 'targetClass' => Product::class, 'targetAttribute' => 'id'],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'product_id' => 'Товар',
            'name' => 'Имя',
            'phone' => 'Телефон',
            'email' => 'Email',
            'message' => 'Комментарий',
            'size' => 'Размер',
            'color' => 'Цвет',
            'status' => 'Статус',
            'created_at' => 'Создана',
            'updated_at' => 'Обновлена',
        ];
    }

    public function getProduct()
    {
        return $this->hasOne(Product::class, ['id' => 'product_id']);
    }

    public function getOrder()
    {
        return $this->hasOne(Order::class, ['source_id' => 'id'])
            ->where(['source' => 'catalog']);
    }

    public function getStatusLabel()
    {
        $labels = [
            self::STATUS_NEW => 'Новая',
            self::STATUS_PROCESSING => 'В обработке',
            self::STATUS_COMPLETED => 'Завершена',
            self::STATUS_CANCELLED => 'Отменена',
        ];
        return $labels[$this->status] ?? 'Неизвестно';
    }

    public function createOrder()
    {
        $transaction = Yii::$app->db->beginTransaction();
        
        try {
            $order = new Order();
            $order->client_name = $this->name;
            $order->client_phone = $this->phone;
            $order->client_email = $this->email;

            if ($order->hasAttribute('source') && $order->hasAttribute('source_id')) {
                $order->source = 'catalog';
                $order->source_id = $this->id;
            }
            $order->comment = $this->message;
            
            if (!$order->save()) {
                throw new \Exception('Не удалось создать заказ');
            }
            
            $orderItem = new OrderItem();
            $orderItem->order_id = $order->id;
            $orderItem->product_name = $this->product->name . 
                ($this->size ? ' (размер: ' . $this->size . ')' : '') .
                ($this->color ? ' (цвет: ' . $this->color . ')' : '');
            $orderItem->quantity = 1;
            $orderItem->price = $this->product->price;
            
            if (!$orderItem->save()) {
                throw new \Exception('Не удалось создать позицию заказа');
            }
            
            $this->status = self::STATUS_PROCESSING;
            $this->save(false);
            
            $transaction->commit();
            return $order;
            
        } catch (\Exception $e) {
            $transaction->rollBack();
            Yii::error('Ошибка создания заказа из заявки: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * После создания заявки - отправка уведомлений
     */
    public function afterSave($insert, $changedAttributes)
    {
        parent::afterSave($insert, $changedAttributes);
        
        if ($insert) {
            $this->sendNotificationToManagers();
            if ($this->email) {
                $this->sendConfirmationToCustomer();
            }
        }
    }

    /**
     * Отправка уведомления менеджерам
     */
    protected function sendNotificationToManagers()
    {
        try {
            $managers = User::find()
                ->where(['or', ['role' => 'admin'], ['role' => 'manager']])
                ->all();
            
            foreach ($managers as $manager) {
                if ($manager->email) {
                    Yii::$app->mailer->compose('catalog-inquiry-manager', [
                        'inquiry' => $this,
                        'product' => $this->product,
                        'order' => $this->order,
                    ])
                    ->setFrom([Yii::$app->params['senderEmail'] => Yii::$app->params['senderName']])
                    ->setTo($manager->email)
                    ->setSubject('🛍️ Новая заявка из каталога - ' . $this->product->name)
                    ->send();
                }
            }
            
            Yii::info('Email уведомления отправлены менеджерам о заявке #' . $this->id);
        } catch (\Exception $e) {
            Yii::error('Ошибка отправки email менеджерам: ' . $e->getMessage());
        }
    }

    /**
     * Отправка подтверждения клиенту
     */
    protected function sendConfirmationToCustomer()
    {
        if (!$this->email) {
            return;
        }
        
        try {
            Yii::$app->mailer->compose('catalog-inquiry-customer', [
                'inquiry' => $this,
                'product' => $this->product,
            ])
            ->setFrom([Yii::$app->params['senderEmail'] => Yii::$app->params['senderName']])
            ->setTo($this->email)
            ->setSubject('✅ Ваша заявка принята - СНИКЕРХЭД')
            ->send();
            
            Yii::info('Email подтверждение отправлено клиенту: ' . $this->email);
        } catch (\Exception $e) {
            Yii::error('Ошибка отправки email клиенту: ' . $e->getMessage());
        }
    }
}
