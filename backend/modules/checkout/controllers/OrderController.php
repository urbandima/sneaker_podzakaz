<?php

/**
 * OrderController — Контроллер заказов
 * 
 * НАЗНАЧЕНИЕ:
 * Создание и управление заказами покупателей: оформление, просмотр,
 * загрузка подтверждения оплаты.
 * 
 * ФУНКЦИИ:
 * - Создание заказа из корзины (create)
 * - Страница успешного оформления (success)
 * - Просмотр заказа по токену (view)
 * - Загрузка подтверждения оплаты (upload-payment)
 * - Скачивание подтверждения оплаты (download-payment)
 * 
 * СВЯЗИ:
 * - Order (модель заказа)
 * - OrderItem (модель позиции заказа)
 * - OrderHistory (модель истории заказа)
 * - Cart (модель корзины)
 * - Customer (модель покупателя)
 * 
 * БЕЗОПАСНОСТЬ:
 * - Доступ к заказу по уникальному токену (не по ID)
 * - Файлы оплаты хранятся вне web root
 * - Rate limiting для загрузки файлов (5 попыток за 15 минут)
 * - Валидация MIME-типа и magic bytes загружаемых файлов
 * 
 * ОСОБЕННОСТИ:
 * - Транзакционное создание заказа
 * - Email-уведомления клиенту и менеджеру
 * - Рекомендованные товары на странице успеха (upsell)
 */
namespace app\backend\modules\checkout\controllers;

use Yii;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\web\UploadedFile;
use yii\web\BadRequestHttpException;
use yii\web\Response;
use app\backend\modules\checkout\models\Order;
use app\backend\modules\checkout\models\OrderItem;
use app\backend\modules\checkout\models\OrderHistory;
use app\backend\modules\cart\models\Cart;
use app\backend\modules\checkout\services\ShippingService;
use app\backend\modules\coupon\services\CouponService;
use app\backend\modules\loyalty\services\LoyaltyService;
use app\backend\modules\notification\services\NotificationService;
use app\backend\modules\notification\services\SmsService;
use app\backend\modules\notification\services\WebhookService;

class OrderController extends Controller
{
    public $layout = 'main';
    
    /** @var NotificationService */
    private $notificationService;
    
    /** @var SmsService */
    private $smsService;
    
    /** @var WebhookService */
    private $webhookService;
    
    public function init()
    {
        parent::init();
        $this->notificationService = new NotificationService();
        // Используем зарегистрированный компонент Yii::$app->sms (RocketSMS и др.)
        $this->smsService = Yii::$app->has('sms') ? Yii::$app->sms : new SmsService(['provider' => 'test']);
        $this->webhookService = new WebhookService([
            'secret' => Yii::$app->params['webhook_secret'] ?? 'default-secret',
            'endpoints' => Yii::$app->params['webhook_endpoints'] ?? [],
        ]);
    }

    public function beforeAction($action)
    {
        // CSRF защита включена для всех действий
        // Для публичных форм используем встроенные механизмы Yii2
        return parent::beforeAction($action);
    }

    /**
     * Создание заказа из корзины
     */
    public function actionCreate()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        
        if (!Yii::$app->request->isPost) {
            Yii::error('Попытка создания заказа не POST методом', 'order');
            return ['success' => false, 'message' => 'Недопустимый метод запроса'];
        }
        
        Yii::info('Начало создания заказа', 'order');
        
        // Получаем ID покупателя из сессии (не из POST — защита от IDOR)
        $customerId = Yii::$app->session->get('customer_id');
        $useSavedAddress = Yii::$app->request->post('use_saved_address');
        $saveToProfile = Yii::$app->request->post('save_to_profile');
        
        // Получаем данные формы
        $name = Yii::$app->request->post('name');
        $phone = Yii::$app->request->post('phone');
        $email = Yii::$app->request->post('email');
        $country = Yii::$app->request->post('country', 'belarus');
        $delivery = Yii::$app->request->post('delivery');
        $address = Yii::$app->request->post('address');
        $pickupPoint = Yii::$app->request->post('pickup_point'); // Europochta PVZ
        $comment = Yii::$app->request->post('comment');
        
        // Купон и баллы лояльности
        $couponCode = Yii::$app->request->post('coupon_code');
        $loyaltyPoints = (int)Yii::$app->request->post('loyalty_points', 0);
        
        // Если используется сохранённый адрес, загружаем данные покупателя
        if ($customerId && $useSavedAddress) {
            $customer = \app\backend\modules\account\models\Customer::findOne($customerId);
            if ($customer) {
                $name = $customer->getFullName();
                $phone = $customer->phone;
                $email = $customer->email;
                $address = $customer->default_address;
                $country = $customer->default_country ?? 'belarus';
            }
        }
        
        // Валидация обязательных полей
        if (empty($name) || empty($phone) || empty($delivery)) {
            return ['success' => false, 'message' => 'Заполните все обязательные поля'];
        }
        
        // Проверяем адрес (обязателен для всех кроме самовывоза)
        // Для Европочты адрес = выбранный ПВЗ (приходит как address или pickup_point)
        if ($delivery === 'europochta' && empty($address) && empty($pickupPoint)) {
            return ['success' => false, 'message' => 'Выберите пункт выдачи Европочты'];
        }
        if ($delivery !== 'pickup_minsk' && $delivery !== 'europochta' && empty($address)) {
            return ['success' => false, 'message' => 'Укажите адрес доставки'];
        }
        
        // Получаем товары из корзины
        try {
            Yii::info('Попытка получения корзины', 'order');
            $cartItems = Cart::getItems();
            Yii::info('Корзина получена: ' . count($cartItems) . ' товаров', 'order');
        } catch (\Throwable $e) {
            Yii::error('Ошибка получения корзины: ' . $e->getMessage() . "\n" . $e->getTraceAsString(), 'order');
            return ['success' => false, 'message' => 'Ошибка загрузки корзины: ' . $e->getMessage()];
        }
        
        if (empty($cartItems)) {
            Yii::warning('Попытка создания заказа с пустой корзиной', 'order');
            return ['success' => false, 'message' => 'Корзина пуста'];
        }
        
        // Начинаем транзакцию
        $transaction = Yii::$app->db->beginTransaction();
        
        try {
            // Создаем заказ
            $order = new Order();
            $order->client_name = $name;
            $order->client_phone = $phone;
            $order->client_email = $email;
            $order->delivery_country = $country;
            $order->delivery_method = $delivery;
            $order->delivery_address = $address ?? '';
            // Save Europochta PVZ as pickup_point (if model has the field)
            if ($pickupPoint && $order->hasAttribute('pickup_point')) {
                $order->pickup_point = $pickupPoint;
            }
            $order->comment = $comment;
            $order->status = 'new';

            if ($order->hasAttribute('source')) {
                $order->source = 'website';
            } else {
                Yii::warning('Поле source отсутствует в таблице order, пропускаем установку источника.', 'order');
            }
            
            // Рассчитываем стоимость доставки через ShippingService
            $shippingService = new ShippingService();
            $totalAmount = Cart::getTotal();
            $shippingResult = $shippingService->calculateShippingCost(
                $delivery,
                $country,
                null,
                $totalAmount,
                1.0 // Вес по умолчанию
            );
            
            $deliveryCost = $shippingResult['cost'];
            $order->delivery_cost = $deliveryCost;
            
            // Рассчитываем итоговую сумму
            $totalAmount = Cart::getTotal();
            $order->product_price = $totalAmount;
            $discountAmount = 0;
            
            // Применяем купон если указан
            if (!empty($couponCode)) {
                $couponService = new CouponService();
                $coupon = $couponService->validateCoupon($couponCode, $totalAmount, $customerId);

                if ($coupon) {
                    // Блокируем строку купона (SELECT FOR UPDATE) внутри транзакции,
                    // чтобы исключить race condition при одновременном применении.
                    $lockedCoupon = \app\backend\modules\coupon\models\Coupon::find()
                        ->where(['id' => $coupon->id])
                        ->limit(1)
                        ->createCommand()
                        ->setSql(
                            'SELECT * FROM {{%coupon}} WHERE id = :id FOR UPDATE',
                            [':id' => $coupon->id]
                        )
                        ->queryOne();

                    // Повторная проверка лимита уже под блокировкой
                    if ($lockedCoupon && $coupon->max_uses && $lockedCoupon['current_uses'] >= $coupon->max_uses) {
                        Yii::warning("Купон {$couponCode}: лимит исчерпан после блокировки", 'order');
                    } else {
                        $discountAmount = $coupon->calculateDiscount($totalAmount, $deliveryCost);
                        $order->coupon_id = $coupon->id;
                        $order->coupon_code = $coupon->code;
                        $order->discount_amount = $discountAmount;

                        Yii::info("Применён купон {$couponCode}, скидка: {$discountAmount}", 'order');
                    }
                } else {
                    Yii::warning("Купон {$couponCode} невалиден: " . $couponService->getErrorMessage(), 'order');
                }
            }
            
            // Применяем баллы лояльности если указаны
            if ($loyaltyPoints > 0 && $customerId) {
                $loyaltyService = new LoyaltyService();
                $maxPoints = $loyaltyService->getMaxRedeemPoints($customerId, $totalAmount);
                
                if ($loyaltyPoints > $maxPoints) {
                    $loyaltyPoints = $maxPoints;
                }
                
                if ($loyaltyPoints >= $loyaltyService->minPointsToRedeem) {
                    $loyaltyDiscount = $loyaltyService->calculateRedeemDiscount($loyaltyPoints);
                    $discountAmount += $loyaltyDiscount;
                    $order->loyalty_points_used = $loyaltyPoints;
                    $order->loyalty_discount = $loyaltyDiscount;
                    
                    Yii::info("Применены баллы лояльности: {$loyaltyPoints}, скидка: {$loyaltyDiscount}", 'order');
                }
            }
            
            // Сумма не может быть отрицательной (купон + баллы могут превысить стоимость)
            $order->total_amount = max(0, $totalAmount + $deliveryCost - $discountAmount);
            
            // Генерируем номер заказа и токен
            $order->order_number = 'WEB-' . date('Ymd') . '-' . strtoupper(Yii::$app->security->generateRandomString(6));
            $order->token = Yii::$app->security->generateRandomString(32);
            
            if (!$order->save()) {
                throw new \Exception('Ошибка сохранения заказа: ' . json_encode($order->errors));
            }
            
            // Добавляем товары в заказ
            foreach ($cartItems as $cartItem) {
                if (!$cartItem->product) {
                    throw new \RuntimeException('Товар ID ' . $cartItem->product_id . ' недоступен. Обновите корзину.');
                }
                $orderItem = new OrderItem();
                $orderItem->order_id = $order->id;
                $orderItem->product_id = $cartItem->product_id;
                $orderItem->product_name = $cartItem->product->name;
                $orderItem->product_article = $cartItem->product->article ?? '';
                $orderItem->quantity = $cartItem->quantity;
                $orderItem->price = $cartItem->price;
                $orderItem->size = $cartItem->size;
                $orderItem->color = $cartItem->color;
                
                if (!$orderItem->save()) {
                    throw new \Exception('Ошибка сохранения товара: ' . json_encode($orderItem->errors));
                }
            }
            
            // Добавляем запись в историю
            $history = new OrderHistory();
            $history->order_id = $order->id;
            $history->old_status = null;
            $history->new_status = 'new';
            $history->comment = 'Заказ создан через сайт';
            
            if (!$history->save()) {
                throw new \Exception('Ошибка сохранения истории: ' . json_encode($history->errors));
            }
            
            // Сохраняем данные в профиль покупателя, если запрошено
            if ($customerId && $saveToProfile) {
                $customer = \app\backend\modules\account\models\Customer::findOne($customerId);
                if ($customer) {
                    // Обновляем только если данные изменились
                    if ($customer->phone !== $phone) {
                        $customer->phone = $phone;
                    }
                    if ($customer->email !== $email) {
                        $customer->email = $email;
                    }
                    if ($customer->default_address !== $address) {
                        $customer->default_address = $address;
                    }
                    if ($customer->default_country !== $country) {
                        $customer->default_country = $country;
                    }
                    
                    $customer->save(false); // Сохраняем без валидации, т.к. данные уже проверены
                    Yii::info("Обновлены данные покупателя #{$customerId}", 'order');
                }
            }
            
            // Привязываем заказ к покупателю, если авторизован
            $autoAccountPassword = null;
            $autoAccountPhone = null;
            if ($customerId) {
                $order->customer_id = $customerId;
                $order->save(false);
            } else {
                // Гостевой заказ: найти или создать покупателя по email/телефону
                try {
                    $CustomerClass = \app\backend\modules\account\models\Customer::class;
                    $existingCustomer = null;
                    if (!empty($email)) {
                        $existingCustomer = $CustomerClass::find()->where(['email' => $email])->one();
                    }
                    if (!$existingCustomer && !empty($phone)) {
                        $existingCustomer = $CustomerClass::find()->where(['phone' => $phone])->one();
                    }
                    if (!$existingCustomer && (!empty($email) || !empty($phone))) {
                        $newCustomer = new $CustomerClass();
                        $newCustomer->email      = $email ?: null;
                        $newCustomer->phone      = $phone ?: null;
                        $newCustomer->first_name = $name ?: null;
                        $newCustomer->created_at = time();
                        $newCustomer->updated_at = time();
                        $newCustomer->is_active  = 1;

                        // Generate password and set it on the new customer
                        $autoAccountPassword = Yii::$app->security->generateRandomString(8);
                        $newCustomer->setPassword($autoAccountPassword);

                        if ($newCustomer->save(false)) {
                            $existingCustomer = $newCustomer;
                            $autoAccountPhone = $phone;
                            Yii::info('Авто-создан покупатель #' . $newCustomer->id . ' из гостевого заказа #' . $order->id, 'order');

                            // Send SMS with credentials
                            try {
                                $smsCredentials = "Ваш заказ #{$order->order_number} оформлен! Логин: {$phone}, Пароль: {$autoAccountPassword}. Личный кабинет: https://sneaker-head.by/account/login";
                                $this->smsService->send($phone, $smsCredentials);
                            } catch (\Exception $smsEx) {
                                Yii::warning('Не удалось отправить SMS с данными аккаунта: ' . $smsEx->getMessage(), 'order');
                            }
                        } else {
                            $autoAccountPassword = null;
                        }
                    }
                    if ($existingCustomer) {
                        $order->customer_id = $existingCustomer->id;
                        $order->save(false);
                    }
                } catch (\Exception $e) {
                    Yii::warning('Не удалось авто-создать покупателя: ' . $e->getMessage(), 'order');
                    $autoAccountPassword = null;
                }
            }

            // Финализируем купон (увеличиваем счётчик использований)
            if (!empty($order->coupon_id)) {
                $couponService = new CouponService();
                $coupon = \app\backend\modules\coupon\models\Coupon::findOne($order->coupon_id);
                if ($coupon) {
                    $coupon->apply();
                    \app\backend\modules\coupon\models\CouponUsage::record(
                        $coupon->id,
                        $order->id,
                        $order->discount_amount,
                        $customerId
                    );
                }
            }
            
            // Списываем баллы лояльности
            if (!empty($order->loyalty_points_used) && $customerId) {
                $loyaltyService = new LoyaltyService();
                $loyaltyService->redeemPoints($customerId, $order->loyalty_points_used, $order->id);
            }
            
            // Очищаем корзину
            Cart::clear();
            
            // Отправляем email уведомления (опционально)
            try {
                // Клиенту
                if ($email) {
                    Yii::$app->mailer->compose('order-created', ['order' => $order])
                        ->setFrom([Yii::$app->params['senderEmail'] => Yii::$app->params['senderName']])
                        ->setTo($email)
                        ->setSubject('Заказ №' . $order->order_number . ' оформлен')
                        ->send();
                }
                
                // Менеджеру
                if (!empty(Yii::$app->params['adminEmail'])) {
                    Yii::$app->mailer->compose('order-created-manager', ['order' => $order])
                        ->setFrom([Yii::$app->params['senderEmail'] => Yii::$app->params['senderName']])
                        ->setTo(Yii::$app->params['adminEmail'])
                        ->setSubject('Новый заказ №' . $order->order_number)
                        ->send();
                }
            } catch (\Exception $e) {
                Yii::warning('Ошибка отправки email: ' . $e->getMessage(), 'order');
            }
            
            $transaction->commit();

            // Отправляем уведомления через сервисы
            $this->sendOrderNotifications($order, $customerId);

            if (Yii::$app->has('automation')) {
                Yii::$app->automation->fireEvent('order.created', ['order' => $order]);
            }

            Yii::info('Создан заказ #' . $order->id . ' через корзину', 'order');

            // Store auto-created account info in session flash for the success page
            if ($autoAccountPassword && $autoAccountPhone) {
                Yii::$app->session->setFlash('auto_account', [
                    'phone' => $autoAccountPhone,
                    'password' => $autoAccountPassword,
                ]);
            }

            $response = [
                'success' => true,
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'token' => $order->token,
            ];

            if ($autoAccountPassword && $autoAccountPhone) {
                $response['auto_account'] = [
                    'phone' => $autoAccountPhone,
                    'password' => $autoAccountPassword,
                ];
            }

            return $response;
            
        } catch (\Throwable $e) {
            $transaction->rollBack();
            Yii::error('Ошибка создания заказа: ' . $e->getMessage() . "\n" . $e->getTraceAsString(), 'order');
            
            return [
                'success' => false,
                'message' => 'Ошибка при оформлении заказа: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Страница успешного оформления заказа
     * Показывается сразу после создания заказа
{{ ... }}
     */
    public function actionSuccess($token)
    {
        $model = Order::findOne(['token' => $token]);

        if ($model === null) {
            throw new NotFoundHttpException('Заказ не найден.');
        }

        // Check for auto-created account credentials
        $autoAccount = Yii::$app->session->getFlash('auto_account');

        // Получаем рекомендованные товары для upsell
        $recommendedProducts = $this->getRecommendedProducts($model);

        return $this->render('success', [
            'model' => $model,
            'recommendedProducts' => $recommendedProducts,
            'autoAccount' => $autoAccount ?: null,
        ]);
    }

    /**
     * Получить рекомендованные товары для upsell
     * Логика: товары из тех же брендов + популярные
     */
    private function getRecommendedProducts($order, $limit = 8)
    {
        $orderItems = $order->orderItems;
        if (empty($orderItems)) {
            // Если по какой-то причине товаров нет, показываем популярные
            return \app\backend\modules\catalog\models\Product::find()
                ->where(['is_active' => true])
                ->orderBy(['created_at' => SORT_DESC])
                ->limit($limit)
                ->all();
        }

        // Собираем ID брендов из заказа
        $brandIds = [];
        $excludeProductIds = [];
        foreach ($orderItems as $item) {
            if ($item->product && $item->product->brand_id) {
                $brandIds[] = $item->product->brand_id;
            }
            if ($item->product_id) {
                $excludeProductIds[] = $item->product_id;
            }
        }

        $brandIds = array_unique($brandIds);
        $query = \app\backend\modules\catalog\models\Product::find()
            ->where(['is_active' => true]);

        // Если есть бренды, показываем товары из тех же брендов
        if (!empty($brandIds)) {
            $query->andWhere(['brand_id' => $brandIds]);
        }

        // Исключаем уже заказанные товары
        if (!empty($excludeProductIds)) {
            $query->andWhere(['not in', 'id', $excludeProductIds]);
        }

        $products = $query
            ->orderBy(['created_at' => SORT_DESC])
            ->limit($limit)
            ->all();

        // Если товаров недостаточно, добавляем популярные
        if (count($products) < $limit) {
            $need = $limit - count($products);
            $existingIds = array_merge($excludeProductIds, array_map(fn($p) => $p->id, $products));
            
            $popularProducts = \app\backend\modules\catalog\models\Product::find()
                ->where(['is_active' => true])
                ->andWhere(['not in', 'id', $existingIds])
                ->orderBy(['created_at' => SORT_DESC])
                ->limit($need)
                ->all();

            $products = array_merge($products, $popularProducts);
        }

        return $products;
    }

    public function actionView($token)
    {
        $model = Order::findOne(['token' => $token]);

        if ($model === null) {
            throw new NotFoundHttpException('Заказ не найден.');
        }

        return $this->render('view', [
            'model' => $model,
        ]);
    }

    /**
     * AJAX валидация купона
     */
    public function actionValidateCoupon()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        
        $code = Yii::$app->request->post('code');
        $orderAmount = (float)Yii::$app->request->post('order_amount', 0);
        $customerId = Yii::$app->request->post('customer_id');
        
        if (empty($code)) {
            return ['success' => false, 'message' => 'Введите код купона'];
        }
        
        $couponService = new CouponService();
        $coupon = $couponService->validateCoupon($code, $orderAmount, $customerId);

        if (!$coupon) {
            return [
                'success' => false,
                'message' => $couponService->getErrorMessage()
            ];
        }

        // Передаём реальную стоимость доставки для корректного расчёта free_shipping купонов
        $deliveryCostForPreview = (float)Yii::$app->request->post('delivery_cost', 0);
        $discount = $coupon->calculateDiscount($orderAmount, $deliveryCostForPreview);
        
        return [
            'success' => true,
            'message' => 'Купон применён',
            'coupon' => [
                'code' => $coupon->code,
                'name' => $coupon->name,
                'description' => $coupon->getDiscountDescription(),
                'discount' => $discount,
            ],
        ];
    }

    /**
     * AJAX расчёт баллов лояльности
     */
    public function actionCalculateLoyalty()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        
        $customerId = Yii::$app->request->post('customer_id');
        $orderAmount = (float)Yii::$app->request->post('order_amount', 0);
        
        if (!$customerId) {
            return ['success' => false, 'message' => 'Авторизуйтесь для использования баллов'];
        }
        
        $loyaltyService = new LoyaltyService();
        $balance = $loyaltyService->getCustomerBalance($customerId);
        $maxPoints = $loyaltyService->getMaxRedeemPoints($customerId, $orderAmount);
        
        if ($balance < $loyaltyService->minPointsToRedeem) {
            return [
                'success' => false,
                'message' => "Минимум для списания: {$loyaltyService->minPointsToRedeem} баллов"
            ];
        }
        
        return [
            'success' => true,
            'balance' => $balance,
            'maxPoints' => $maxPoints,
            'minPoints' => $loyaltyService->minPointsToRedeem,
            'pointValue' => $loyaltyService->pointValue,
        ];
    }

    public function actionUploadPayment($token)
    {
        $model = Order::findOne(['token' => $token]);

        if ($model === null) {
            throw new NotFoundHttpException('Заказ не найден.');
        }

        // Защита от повторной загрузки
        if ($model->payment_proof) {
            Yii::$app->session->setFlash('error', 'Подтверждение оплаты уже загружено.');
            return $this->redirect(['view', 'token' => $token]);
        }

        // Rate limiting: проверка количества попыток
        $this->checkRateLimit($token);

        if (Yii::$app->request->isPost) {
            $file = UploadedFile::getInstanceByName('payment_proof');
            $offerAccepted = Yii::$app->request->post('offer_accepted');

            if (!$offerAccepted) {
                Yii::$app->session->setFlash('error', 'Необходимо принять условия публичной оферты.');
                return $this->redirect(['view', 'token' => $token]);
            }

            if ($file) {
                // Валидация файла
                $validationErrors = $this->validateUploadedFile($file);
                if (!empty($validationErrors)) {
                    Yii::$app->session->setFlash('error', implode('<br>', $validationErrors));
                    return $this->redirect(['view', 'token' => $token]);
                }

                // Начинаем транзакцию
                $transaction = Yii::$app->db->beginTransaction();
                try {
                    // ИСПРАВЛЕНО: Файлы хранятся ВНЕ web root для безопасности
                    $uploadPath = Yii::getAlias('@app/runtime/uploads/payments/');
                    if (!is_dir($uploadPath)) {
                        mkdir($uploadPath, 0755, true);
                    }

                    // Генерируем безопасное имя файла (UUID + расширение)
                    $fileName = $model->id . '_' . time() . '_' . Yii::$app->security->generateRandomString(32) . '.' . $file->extension;
                    $filePath = $uploadPath . $fileName;

                    if ($file->saveAs($filePath)) {
                        $oldStatus = $model->status;
                        // ИСПРАВЛЕНО: Сохраняем только имя файла (не путь)
                        $model->payment_proof = $fileName;
                        $model->payment_uploaded_at = time();
                        $model->status = 'paid';
                        $model->offer_accepted = true;
                        $model->offer_accepted_at = time();

                        if ($model->save()) {
                            // Логируем изменение статуса
                            $history = new OrderHistory();
                            $history->order_id = $model->id;
                            $history->old_status = $oldStatus;
                            $history->new_status = 'paid';
                            $history->comment = 'Загружено подтверждение оплаты покупателем';
                            if (!$history->save()) {
                                throw new \Exception('Ошибка сохранения истории');
                            }

                            // Отправляем уведомление менеджеру
                            if ($model->creator && $model->creator->email) {
                                try {
                                    $sent = Yii::$app->mailer->compose('payment-uploaded', ['order' => $model])
                                        ->setFrom([Yii::$app->params['senderEmail'] => Yii::$app->params['senderName']])
                                        ->setTo($model->creator->email)
                                        ->setSubject('Загружено подтверждение оплаты для заказа №' . $model->order_number)
                                        ->send();
                                    
                                    if (!$sent) {
                                        Yii::warning('Не удалось отправить email менеджеру для заказа #' . $model->id, 'order');
                                    }
                                } catch (\Exception $e) {
                                    Yii::error('Ошибка отправки email: ' . $e->getMessage(), 'order');
                                }
                            }

                            $transaction->commit();
                            
                            Yii::info('Загружено подтверждение оплаты для заказа #' . $model->id . ' (токен: ' . $token . ')', 'order');
                            Yii::$app->session->setFlash('success', 'Подтверждение оплаты загружено. Ожидайте проверки менеджером.');
                            return $this->redirect(['view', 'token' => $token]);
                        } else {
                            throw new \Exception('Ошибка сохранения заказа: ' . json_encode($model->errors));
                        }
                    } else {
                        throw new \Exception('Не удалось сохранить файл на диск');
                    }
                } catch (\Exception $e) {
                    $transaction->rollBack();
                    
                    // Удаляем файл если он был создан
                    if (isset($filePath) && file_exists($filePath)) {
                        @unlink($filePath);
                    }
                    
                    Yii::error('Ошибка загрузки подтверждения оплаты: ' . $e->getMessage(), 'order');
                    Yii::$app->session->setFlash('error', 'Ошибка при загрузке файла. Попробуйте позже.');
                }
            } else {
                Yii::$app->session->setFlash('error', 'Пожалуйста, выберите файл для загрузки.');
            }
        }

        return $this->redirect(['view', 'token' => $token]);
    }

    /**
     * Валидация загруженного файла
     */
    private function validateUploadedFile($file): array
    {
        $errors = [];

        // Проверка размера (максимум 10 МБ)
        $maxSize = 10 * 1024 * 1024; // 10 MB
        if ($file->size > $maxSize) {
            $errors[] = 'Размер файла не должен превышать 10 МБ.';
        }

        // Проверка расширения
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'pdf', 'gif', 'webp'];
        if (!in_array(strtolower($file->extension), $allowedExtensions)) {
            $errors[] = 'Допустимые форматы: JPG, PNG, PDF, GIF, WEBP.';
        }

        // Проверка MIME-типа
        $allowedMimeTypes = [
            'image/jpeg',
            'image/jpg', 
            'image/png',
            'image/gif',
            'image/webp',
            'application/pdf',
        ];
        if (!in_array($file->type, $allowedMimeTypes)) {
            $errors[] = 'Недопустимый тип файла.';
        }

        // Дополнительная проверка на реальный тип файла (magic bytes)
        if ($file->tempName) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $realMimeType = finfo_file($finfo, $file->tempName);
            finfo_close($finfo);

            if (!in_array($realMimeType, $allowedMimeTypes)) {
                $errors[] = 'Файл не соответствует заявленному типу.';
            }
        }

        return $errors;
    }

    /**
     * Простая защита от злоупотреблений (rate limiting)
     */
    private function checkRateLimit($token): void
    {
        $session = Yii::$app->session;
        $key = 'upload_attempts_' . $token;
        $attempts = $session->get($key, 0);
        $lastAttempt = $session->get($key . '_time', 0);

        // Сброс счетчика через 15 минут
        if (time() - $lastAttempt > 900) {
            $attempts = 0;
        }

        if ($attempts >= 5) {
            Yii::warning('Превышен лимит попыток загрузки для токена: ' . $token, 'security');
            throw new BadRequestHttpException('Превышено количество попыток. Попробуйте через 15 минут.');
        }

        $session->set($key, $attempts + 1);
        $session->set($key . '_time', time());
    }

    /**
     * НОВОЕ: Безопасное скачивание подтверждения оплаты
     * Файлы хранятся вне web root и отдаются через контроллер
     */
    public function actionDownloadPayment($token)
    {
        $model = Order::findOne(['token' => $token]);

        if ($model === null) {
            throw new NotFoundHttpException('Заказ не найден.');
        }

        if (!$model->payment_proof) {
            throw new NotFoundHttpException('Подтверждение оплаты не загружено.');
        }

        $filePath = Yii::getAlias('@app/runtime/uploads/payments/' . $model->payment_proof);

        if (!file_exists($filePath)) {
            Yii::error('Файл подтверждения не найден: ' . $filePath . ' (заказ #' . $model->id . ')', 'order');
            throw new NotFoundHttpException('Файл не найден.');
        }

        // Проверка прав доступа
        // Могут скачать: клиент (по токену), менеджер, админ
        if (!Yii::$app->user->isGuest) {
            $user = Yii::$app->user->identity;
            // Админ и менеджер могут скачать любой файл
            // Логист может скачать только для своих заказов
            if ($user->isLogist() && $model->assigned_logist != $user->id) {
                throw new NotFoundHttpException('Доступ запрещен.');
            }
        }

        Yii::info('Скачивание подтверждения оплаты для заказа #' . $model->id, 'order');

        return Yii::$app->response->sendFile($filePath, 'payment_proof_' . $model->order_number . '.' . pathinfo($model->payment_proof, PATHINFO_EXTENSION), [
            'inline' => true // Показать в браузере вместо скачивания
        ]);
    }
    
    /**
     * Отправить уведомления о заказе
     */
    private function sendOrderNotifications($order, $customerId): void
    {
        try {
            // Внутреннее уведомление клиенту
            if ($customerId) {
                $this->notificationService->notifyNewOrder($order->id, $customerId);
            }
            
            // SMS уведомление
            if ($order->client_phone) {
                $smsText = "Заказ {$order->order_number} оформлен. Сумма: {$order->total_amount} BYN. Спасибо за покупку!";
                $this->smsService->send($order->client_phone, $smsText);
            }
            
            // Webhook уведомление для внешних систем
            $this->webhookService->sendOrderCreated($order);
            
        } catch (\Exception $e) {
            Yii::error('Ошибка отправки уведомлений: ' . $e->getMessage(), 'order');
        }
    }
}
