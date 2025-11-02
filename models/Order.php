<?php

namespace app\models;

use Yii;
use yii\db\ActiveRecord;
use yii\behaviors\TimestampBehavior;

class Order extends ActiveRecord
{
    public $items = []; // Для формы создания заказа

    public static function tableName()
    {
        return '{{%order}}';
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
            [['client_name', 'created_by'], 'required'],
            [['order_number', 'token'], 'string', 'max' => 100],
            [['client_name', 'client_email', 'delivery_date', 'payment_proof'], 'string', 'max' => 255],
            [['client_phone'], 'string', 'max' => 50],
            [['comment'], 'string'],
            [['total_amount'], 'number'],
            [['status', 'source'], 'string', 'max' => 50],
            [['source_id'], 'integer'],
            [['offer_accepted'], 'boolean'],
            [['created_by', 'assigned_logist', 'payment_uploaded_at', 'offer_accepted_at'], 'integer'],
            ['client_email', 'email'],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'order_number' => 'Номер заказа',
            'token' => 'Токен',
            'client_name' => 'ФИО клиента',
            'client_phone' => 'Телефон',
            'client_email' => 'Email',
            'total_amount' => 'Сумма заказа',
            'status' => 'Статус',
            'delivery_date' => 'Срок доставки',
            'comment' => 'Комментарий',
            'payment_proof' => 'Подтверждение оплаты',
            'offer_accepted' => 'Оферта принята',
            'created_by' => 'Создал',
            'assigned_logist' => 'Логист',
            'source' => 'Источник',
            'source_id' => 'ID источника',
            'created_at' => 'Создан',
            'updated_at' => 'Обновлен',
        ];
    }

    public function beforeSave($insert)
    {
        if (parent::beforeSave($insert)) {
            if ($insert) {
                // Генерируем номер заказа
                if (empty($this->order_number)) {
                    $this->order_number = $this->generateOrderNumber();
                }
                // Генерируем токен
                if (empty($this->token)) {
                    $this->token = $this->generateToken();
                }
                // Устанавливаем начальный статус
                if (empty($this->status)) {
                    $this->status = 'created';
                }
            }
            return true;
        }
        return false;
    }

    public function afterSave($insert, $changedAttributes)
    {
        parent::afterSave($insert, $changedAttributes);

        // Логируем изменение статуса
        if (!$insert && isset($changedAttributes['status'])) {
            $history = new OrderHistory();
            $history->order_id = $this->id;
            $history->old_status = $changedAttributes['status'];
            $history->new_status = $this->status;
            // Устанавливаем changed_by только в веб-приложении
            if (!Yii::$app instanceof \yii\console\Application && !Yii::$app->user->isGuest) {
                $history->changed_by = Yii::$app->user->id;
            }
            $history->save();
        }

        // Отправляем уведомление при создании заказа
        if ($insert) {
            $this->sendNotification();
        }
    }

    protected function generateOrderNumber()
    {
        $year = date('Y');
        $maxRetries = 5;
        $attempt = 0;

        while ($attempt < $maxRetries) {
            try {
                // Используем транзакцию с блокировкой для атомарности
                $transaction = Yii::$app->db->beginTransaction();
                
                // Получаем последний заказ с блокировкой строки (FOR UPDATE)
                $lastOrder = self::find()
                    ->where(['like', 'order_number', $year])
                    ->orderBy(['id' => SORT_DESC])
                    ->limit(1)
                    ->createCommand()
                    ->queryOne();

                if ($lastOrder) {
                    $lastNumber = (int)substr($lastOrder['order_number'], -5);
                    $newNumber = $lastNumber + 1;
                } else {
                    $newNumber = 1;
                }

                $orderNumber = sprintf('%s-%05d', $year, $newNumber);
                
                // Проверяем уникальность перед коммитом
                $exists = self::find()->where(['order_number' => $orderNumber])->exists();
                if (!$exists) {
                    $transaction->commit();
                    return $orderNumber;
                }
                
                $transaction->rollBack();
                $attempt++;
                
                // Небольшая задержка перед повторной попыткой
                usleep(rand(10000, 50000)); // 10-50ms
                
            } catch (\Exception $e) {
                if (isset($transaction)) {
                    $transaction->rollBack();
                }
                $attempt++;
                usleep(rand(10000, 50000));
            }
        }

        // Если все попытки исчерпаны, генерируем номер с микросекундами
        return sprintf('%s-%05d-%s', $year, rand(1, 99999), substr(microtime(true) * 10000, -4));
    }

    protected function generateToken()
    {
        return Yii::$app->security->generateRandomString(32);
    }

    public function getPublicUrl()
    {
        return Yii::$app->urlManager->createAbsoluteUrl(['order/view', 'token' => $this->token]);
    }

    public function getStatusLabel()
    {
        $statuses = Yii::$app->settings->getStatuses();
        return $statuses[$this->status] ?? $this->status;
    }

    public function canChangeStatus($newStatus)
    {
        $user = Yii::$app->user->identity;
        
        if (!$user) {
            return false;
        }

        // Админ и менеджер могут менять любой статус
        if ($user->isAdmin() || $user->isManager()) {
            return true;
        }

        // Логист может менять только определенные статусы
        if ($user->isLogist()) {
            $logistStatuses = array_keys(Yii::$app->settings->getLogistStatuses());
            return in_array($newStatus, $logistStatuses);
        }

        return false;
    }

    public function sendNotification()
    {
        // Не отправляем email из консольного приложения
        if (Yii::$app instanceof \yii\console\Application) {
            return false;
        }
        
        if ($this->client_email) {
            try {
                $sent = Yii::$app->mailer->compose('order-created', ['order' => $this])
                    ->setFrom([Yii::$app->params['senderEmail'] => Yii::$app->params['senderName']])
                    ->setTo($this->client_email)
                    ->setSubject('Создан заказ №' . $this->order_number)
                    ->send();
                
                if ($sent) {
                    Yii::info('Email успешно отправлен клиенту для заказа #' . $this->id, 'order');
                    return true;
                } else {
                    Yii::warning('Не удалось отправить email клиенту для заказа #' . $this->id, 'order');
                    return false;
                }
            } catch (\Exception $e) {
                Yii::error('Ошибка отправки email клиенту: ' . $e->getMessage() . ' (заказ #' . $this->id . ')', 'order');
                return false;
            }
        }
        
        return false;
    }

    // Relations
    public function getCreator()
    {
        return $this->hasOne(User::class, ['id' => 'created_by']);
    }

    public function getLogist()
    {
        return $this->hasOne(User::class, ['id' => 'assigned_logist']);
    }

    public function getOrderItems()
    {
        return $this->hasMany(OrderItem::class, ['order_id' => 'id']);
    }

    public function getHistory()
    {
        return $this->hasMany(OrderHistory::class, ['order_id' => 'id'])->orderBy(['created_at' => SORT_DESC]);
    }
}
