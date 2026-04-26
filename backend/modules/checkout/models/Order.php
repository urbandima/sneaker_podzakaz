<?php

/**
 * Order — Модель заказа
 * 
 * НАЗНАЧЕНИЕ:
 * Заказы покупателей: создание, отслеживание, статусы, доставка,
 * оплата, логистика. Основная сущность для бизнес-процессов.
 * 
 * ОСНОВНЫЕ СВОЙСТВА:
 * - Идентификация: id, order_number, token
 * - Клиент: client_name, client_email, client_phone, customer_id
 * - Доставка: delivery_method, delivery_address, delivery_country, delivery_cost
 * - Финансы: total_amount, product_price, logistics_price, commission_price
 * - Статус: status, is_processed, is_shipped, customs_cleared
 * - Логистика: china_track_number, recipient_*, passport_*, inn
 * - Оплата: payment_proof, offer_accepted
 * - Связи: created_by (менеджер), assigned_logist
 * 
 * СТАТУСЫ:
 * - new: новый заказ
 * - paid: оплачен
 * - processing: в обработке
 * - shipped: отправлен
 * - delivered: доставлен
 * - cancelled: отменён
 * 
 * СВЯЗИ:
 * - Customer (покупатель)
 * - User (creator, logist)
 * - OrderItem[] (позиции заказа)
 * - OrderHistory[] (история изменений)
 * - DeliveryTracking (отслеживание)
 * 
 * БЕЗОПАСНОСТЬ:
 * - Доступ по токену для покупателей (не по ID)
 * - Файлы оплаты вне web root
 */
namespace app\backend\modules\checkout\models;

use Yii;
use yii\db\ActiveRecord;
use yii\behaviors\TimestampBehavior;
use app\backend\modules\admin\models\User;

class Order extends ActiveRecord
{
    public $items = []; // Для формы создания заказа

    public static function tableName()
    {
        return 'order';
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
            [['client_name'], 'required'],
            // created_by необязательно для заказов с сайта
            [['created_by'], 'integer'],
            [['order_number', 'token', 'china_track_number'], 'string', 'max' => 100],
            [['client_name', 'client_email', 'delivery_date', 'payment_proof'], 'string', 'max' => 255],
            [['client_phone', 'delivery_country', 'delivery_method'], 'string', 'max' => 50],
            [['comment', 'delivery_address', 'full_address', 'customs_description'], 'string'],
            [['total_amount', 'delivery_cost', 'shipment_value_cny', 'item_price_cny', 'product_price', 'logistics_price', 'commission_price'], 'number'],
            [['total_amount'], 'default', 'value' => 0],
            [['status', 'source', 'amocrm_source'], 'string', 'max' => 50],
            [['source_id', 'item_quantity', 'amocrm_deal_id', 'amocrm_lead_id', 'amocrm_last_sync_at'], 'integer'],
            [['offer_accepted', 'is_processed', 'is_shipped', 'customs_cleared'], 'boolean'],
            [['created_by', 'assigned_logist', 'payment_uploaded_at', 'offer_accepted_at'], 'integer'],
            ['client_email', 'email'],
            // Новые поля логистики
            [['recipient_last_name', 'recipient_first_name', 'recipient_middle_name', 'city', 'region'], 'string', 'max' => 100],
            [['passport_series', 'inn', 'postal_code', 'ms_number'], 'string', 'max' => 50],
            [['passport_number', 'dobropost_tariff'], 'string', 'max' => 100],
            [['product_link', 'sneakerhead_order_link'], 'string', 'max' => 500],
            [['passport_issue_date', 'birth_date'], 'safe'],
            // DP fields
            [['dp_shipment_id', 'dp_sent_at', 'passport_submitted_at'], 'integer'],
            [['dp_track_number', 'dp_status', 'local_delivery_status'], 'string', 'max' => 100],
            [['dp_status_date', 'estimated_delivery_date'], 'safe'],
            [['passport_validated'], 'boolean'],
            [['dp_response'], 'safe'],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'order_number' => 'Номер заказа',
            'token' => 'Токен',
            'china_track_number' => 'Китайский трек-номер',
            'shipment_value_cny' => 'Ценность шипмента (¥)',
            'client_name' => 'ФИО клиента',
            'recipient_last_name' => 'Фамилия получателя',
            'recipient_first_name' => 'Имя получателя',
            'recipient_middle_name' => 'Отчество получателя',
            'passport_series' => 'Серия паспорта',
            'passport_number' => 'Номер паспорта',
            'passport_issue_date' => 'Дата выдачи паспорта',
            'birth_date' => 'Дата рождения',
            'inn' => 'ИНН получателя',
            'client_phone' => 'Телефон',
            'client_email' => 'Email',
            'delivery_country' => 'Страна доставки',
            'delivery_method' => 'Способ доставки',
            'delivery_address' => 'Адрес доставки',
            'full_address' => 'Полный адрес',
            'city' => 'Город',
            'region' => 'Область',
            'postal_code' => 'Индекс',
            'delivery_cost' => 'Стоимость доставки',
            'total_amount' => 'Сумма заказа',
            'product_price' => 'Цена товара',
            'logistics_price' => 'Цена логистики',
            'commission_price' => 'Цена комиссии',
            'status' => 'Статус',
            'is_processed' => 'Обработано',
            'is_shipped' => 'Выслано',
            'customs_cleared' => 'Таможня',
            'ms_number' => '№ МС',
            'customs_description' => 'Описание товара',
            'item_quantity' => 'Кол-во товара',
            'item_price_cny' => 'Стоимость товара (¥)',
            'product_link' => 'Ссылка на товар',
            'dobropost_tariff' => 'Тариф Таможня:ДП',
            'sneakerhead_order_link' => 'Ссылка на заказ',
            'delivery_date' => 'Срок доставки',
            'comment' => 'Комментарий',
            'payment_proof' => 'Подтверждение оплаты',
            'offer_accepted' => 'Оферта принята',
            'created_by' => 'Создал',
            'assigned_logist' => 'Логист',
            'source' => 'Источник',
            'source_id' => 'ID источника',
            'amocrm_source'         => 'Источник AmoCRM',
            'amocrm_deal_id'        => 'ID сделки AmoCRM',
            'amocrm_lead_id'        => 'ID лида AmoCRM',
            'amocrm_last_sync_at'   => 'Последняя синхр. AmoCRM',
            'created_at'              => 'Создан',
            'updated_at'              => 'Обновлен',
            // DP fields
            'dp_shipment_id'          => 'ID шипмента ДП',
            'dp_track_number'         => 'Трек Таможня:ДП',
            'dp_status'               => 'Статус Таможня:ДП',
            'dp_status_date'          => 'Дата статуса ДП',
            'dp_sent_at'              => 'Отправлен в ДП',
            'dp_response'             => 'Ответ API ДП',
            'passport_submitted_at'   => 'Паспорт подан',
            'passport_validated'      => 'Паспорт проверен',
            'estimated_delivery_date' => 'Ожидаемая дата доставки',
            'local_delivery_status'   => 'Статус местной доставки',
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
                    $this->status = 'new';
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
            // Бросаем исключение, чтобы внешняя транзакция откатилась при ошибке
            if (!$history->save()) {
                throw new \RuntimeException(
                    'Не удалось сохранить историю статусов заказа #' . $this->id . ': ' . json_encode($history->errors)
                );
            }
        }

        // Уведомление при создании заказа отправляется в OrderController::actionCreate()
        // чтобы избежать двойной отправки email клиенту.
    }

    protected function generateOrderNumber()
    {
        $year = date('Y');
        $maxRetries = 5;
        $attempt = 0;

        while ($attempt < $maxRetries) {
            try {
                // Транзакция + SELECT FOR UPDATE — блокируем строку на время генерации
                $transaction = Yii::$app->db->beginTransaction();

                $lastOrder = Yii::$app->db->createCommand(
                    'SELECT order_number FROM {{%order}} WHERE order_number LIKE :prefix ORDER BY id DESC LIMIT 1 FOR UPDATE',
                    [':prefix' => $year . '-%']
                )->queryOne();

                if ($lastOrder) {
                    $lastNumber = (int)substr($lastOrder['order_number'], -5);
                    $newNumber = $lastNumber + 1;
                } else {
                    $newNumber = 1;
                }

                $orderNumber = sprintf('%s-%05d', $year, $newNumber);

                // Финальная проверка уникальности под блокировкой
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
        $base = rtrim(Yii::$app->params['frontendUrl'] ?? Yii::$app->urlManager->createAbsoluteUrl('/'), '/');
        return $base . '/order/' . $this->token;
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

    public function getCustomer()
    {
        return $this->hasOne(\app\backend\modules\account\models\Customer::class, ['id' => 'customer_id']);
    }

    public function getLogist()
    {
        return $this->hasOne(User::class, ['id' => 'assigned_logist']);
    }

    /**
     * Alias для china_track_number для совместимости
     */
    public function getTrack_number()
    {
        return $this->china_track_number;
    }

    /**
     * Alias для getLogist() для совместимости
     */
    public function getAssignedLogist()
    {
        return $this->getLogist();
    }

    public function getOrderItems()
    {
        return $this->hasMany(OrderItem::class, ['order_id' => 'id']);
    }

    public function getHistory()
    {
        return $this->hasMany(OrderHistory::class, ['order_id' => 'id'])->orderBy(['created_at' => SORT_DESC]);
    }

    // -------------------------------------------------------------------------
    // Таможня:ДП helpers
    // -------------------------------------------------------------------------

    /**
     * Все обязательные паспортные поля заполнены.
     */
    public function isPassportComplete(): bool
    {
        return !empty($this->recipient_last_name)
            && !empty($this->recipient_first_name)
            && !empty($this->passport_series)
            && !empty($this->passport_number)
            && !empty($this->passport_issue_date)
            && !empty($this->inn)
            && !empty($this->full_address)
            && !empty($this->city)
            && !empty($this->region)
            && !empty($this->postal_code);
    }

    /**
     * Заказ готов к отправке в Таможня:ДП.
     */
    public function canSubmitToDP(): bool
    {
        return $this->isPassportComplete()
            && !empty($this->china_track_number)
            && !$this->isSubmittedToDP();
    }

    /**
     * Шипмент уже был создан в Таможня:ДП.
     */
    public function isSubmittedToDP(): bool
    {
        return !empty($this->dp_shipment_id);
    }
}
