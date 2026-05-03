<?php

namespace app\frontend\controllers;

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

class OrderController extends Controller
{
    public $layout = 'main';

    public function beforeAction($action)
    {
        // CSRF защита включена для всех действий
        // Для публичных форм используем встроенные механизмы Yii2
        return parent::beforeAction($action);
    }

    /**
     * Страница оформления заказа (GET /checkout)
     */
    public function actionIndex()
    {
        $items                 = [];
        $total                 = 0;
        $customer              = null;
        $shippingMethods       = [];
        $europochtaPoints      = [];
        $paymentMethods        = [];
        $freeDeliveryThreshold = 100;

        try {
            $items = Cart::getItems();
            $total = Cart::getTotal();

            if (empty($items)) {
                Yii::$app->session->setFlash('warning', 'Корзина пуста. Добавьте товары перед оформлением заказа.');
                return $this->redirect(['/catalog']);
            }

            $customerId = Yii::$app->session->get('customer_id');
            if ($customerId) {
                $customer = \app\backend\modules\account\models\Customer::findOne($customerId);
            }

            $rawShipping = Yii::$app->settings->get('checkout', 'shipping_methods');
            if ($rawShipping) {
                $decoded = json_decode($rawShipping, true);
                if (is_array($decoded)) {
                    $shippingMethods = array_values(array_filter($decoded, fn($m) => ($m['status'] ?? '') === 'active'));
                }
            }
            if (empty($shippingMethods)) {
                $shippingMethods = self::defaultShippingMethods();
            }

            $rawPoints = Yii::$app->settings->get('shipping', 'europochta_points', '');
            if ($rawPoints) {
                $decoded = json_decode($rawPoints, true);
                if (is_array($decoded)) {
                    $europochtaPoints = $decoded;
                }
            }

            $company        = \app\backend\modules\admin\models\CompanySettings::getSettings();
            $pickupAddress  = Yii::$app->settings->get('checkout', 'pickup_address', $company['address'] ?? 'пр.Победителей 5 офис 9');
            $pickupWorkTime = $company['work_time'] ?? Yii::$app->settings->get('company', 'work_time', 'Пн-Вс: 10:00-22:00');

            $rawPayment = Yii::$app->settings->get('checkout', 'payment_methods', '');
            if ($rawPayment) {
                $decoded = json_decode($rawPayment, true);
                if (is_array($decoded)) {
                    $paymentMethods = array_values(array_filter($decoded, fn($m) => ($m['status'] ?? '') === 'active'));
                }
            }
            if (empty($paymentMethods)) {
                $paymentMethods = self::defaultPaymentMethods();
            }

            $freeDeliveryThreshold = (float) Yii::$app->settings->get('checkout', 'free_delivery_threshold', 100);
        } catch (\Exception $e) {
            Yii::warning('Checkout page error: ' . $e->getMessage(), 'checkout');
        }

        return $this->render('//checkout/index', [
            'items'                 => $items,
            'total'                 => $total,
            'customer'              => $customer,
            'shippingMethods'       => $shippingMethods,
            'europochtaPoints'      => $europochtaPoints,
            'pickupAddress'         => $pickupAddress  ?? 'пр.Победителей 5 офис 9',
            'pickupWorkTime'        => $pickupWorkTime ?? 'Пн-Вс: 10:00-22:00',
            'paymentMethods'        => $paymentMethods ?? [],
            'freeDeliveryThreshold' => $freeDeliveryThreshold ?? 100,
        ]);
    }

    /**
     * Defaults shown when DB has no shipping methods configured.
     * Keep parity with hardcoded JS price map in views/checkout/index.php
     * (pickup_minsk=0, courier_minsk=10, europochta=5, belpochta=4).
     */
    private static function defaultShippingMethods(): array
    {
        return [
            [
                'id'            => 'pickup_minsk',
                'country'       => 'belarus',
                'name'          => 'Самовывоз из магазина',
                'description'   => 'пр.Победителей 5, офис 9',
                'delivery_time' => 'Сегодня после 16:00',
                'price'         => 0.0,
                'price_label'   => 'Бесплатно',
                'icon'          => 'shop',
                'status'        => 'active',
            ],
            [
                'id'            => 'courier_minsk',
                'country'       => 'belarus',
                'name'          => 'Курьер по Минску',
                'description'   => 'Доставка до двери',
                'delivery_time' => '1 рабочий день',
                'price'         => 10.0,
                'price_label'   => '10 BYN',
                'icon'          => 'truck',
                'status'        => 'active',
            ],
            [
                'id'            => 'europochta',
                'country'       => 'belarus',
                'name'          => 'Европочта',
                'description'   => 'В пункт выдачи в любом городе',
                'delivery_time' => '1–2 рабочих дня',
                'price'         => 5.0,
                'price_label'   => '5 BYN',
                'icon'          => 'box-seam',
                'status'        => 'active',
            ],
            [
                'id'            => 'belpochta',
                'country'       => 'belarus',
                'name'          => 'Белпочта',
                'description'   => 'В отделение по адресу',
                'delivery_time' => '2–5 рабочих дней',
                'price'         => 4.0,
                'price_label'   => '4 BYN',
                'icon'          => 'envelope',
                'status'        => 'active',
            ],
            [
                'id'            => 'sdek',
                'country'       => 'russia',
                'name'          => 'СДЭК (Россия)',
                'description'   => 'Пункт выдачи или курьер',
                'delivery_time' => '5–10 дней',
                'price'         => 0.0,
                'price_label'   => 'По тарифам СДЭК',
                'icon'          => 'truck',
                'status'        => 'active',
            ],
        ];
    }

    /**
     * Defaults shown when DB has no payment methods configured.
     */
    private static function defaultPaymentMethods(): array
    {
        return [
            [
                'id'          => 'bank_details',
                'name'        => 'Оплата по реквизитам',
                'description' => 'Реквизиты пришлём после оформления',
                'icon'        => 'bank',
                'status'      => 'active',
            ],
            [
                'id'          => 'pickup_cash',
                'name'        => 'При получении (только самовывоз)',
                'description' => 'Наличными или картой в магазине',
                'icon'        => 'cash-coin',
                'status'      => 'active',
            ],
        ];
    }

    /**
     * Создание заказа из корзины (POST /order/create)
     */
    public function actionCreate()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        
        if (!Yii::$app->request->isPost) {
            Yii::error('Попытка создания заказа не POST методом', 'order');
            return ['success' => false, 'message' => 'Недопустимый метод запроса'];
        }
        
        Yii::info('Начало создания заказа', 'order');
        
        // Получаем и очищаем данные формы
        $name     = trim(strip_tags(Yii::$app->request->post('name', '')));
        $phone    = trim(strip_tags(Yii::$app->request->post('phone', '')));
        $email    = trim(Yii::$app->request->post('email', ''));
        $country  = trim(Yii::$app->request->post('country', 'belarus'));
        $delivery = trim(Yii::$app->request->post('delivery', ''));
        $address  = trim(strip_tags(Yii::$app->request->post('address', '')));
        $comment  = trim(strip_tags(Yii::$app->request->post('comment', '')));

        // Список допустимых методов доставки
        $allowedDeliveryMethods = ['pickup_minsk', 'courier_minsk', 'europochta', 'belpochta', 'sdek'];
        $allowedCountries       = ['belarus', 'russia', 'kazakhstan', 'ukraine', 'other'];

        // Валидация обязательных полей
        if (empty($name) || mb_strlen($name) > 100) {
            return ['success' => false, 'message' => 'Укажите корректное имя (до 100 символов)'];
        }
        if (empty($phone) || !preg_match('/^[\+]?[\d\s\-\(\)]{7,50}$/', $phone)) {
            return ['success' => false, 'message' => 'Укажите корректный номер телефона'];
        }
        if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'message' => 'Некорректный формат email'];
        }
        if (!in_array($delivery, $allowedDeliveryMethods, true)) {
            return ['success' => false, 'message' => 'Выберите способ доставки'];
        }
        if (!in_array($country, $allowedCountries, true)) {
            $country = 'belarus';
        }
        if (mb_strlen($comment) > 1000) {
            $comment = mb_substr($comment, 0, 1000);
        }

        // Проверяем адрес (обязателен для всех кроме самовывоза)
        if ($delivery !== 'pickup_minsk' && empty($address)) {
            return ['success' => false, 'message' => 'Укажите адрес доставки'];
        }
        if (mb_strlen($address) > 500) {
            return ['success' => false, 'message' => 'Адрес слишком длинный (максимум 500 символов)'];
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
            $order->comment = $comment;
            $order->status = 'new';

            if ($order->hasAttribute('source')) {
                $order->source = 'website';
            } else {
                Yii::warning('Поле source отсутствует в таблице order, пропускаем установку источника.', 'order');
            }
            
            // Рассчитываем стоимость доставки
            $deliveryCost = 0;
            switch ($delivery) {
                case 'courier_minsk':
                    $deliveryCost = 10;
                    break;
                case 'europochta':
                    $deliveryCost = 5;
                    break;
                case 'belpochta':
                    $deliveryCost = 4;
                    break;
                case 'sdek':
                    $deliveryCost = 0; // Рассчитывается отдельно
                    break;
                default:
                    $deliveryCost = 0;
            }
            
            $order->delivery_cost = $deliveryCost;
            
            // Рассчитываем итоговую сумму
            $totalAmount = Cart::getTotal();
            $order->total_amount = $totalAmount + $deliveryCost;
            
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
            
            Yii::info('Создан заказ #' . $order->id . ' через корзину', 'order');
            
            return [
                'success' => true,
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'token' => $order->token
            ];
            
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

        // Получаем рекомендованные товары для upsell
        $recommendedProducts = $this->getRecommendedProducts($model);

        return $this->render('success', [
            'model' => $model,
            'recommendedProducts' => $recommendedProducts,
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

    /**
     * Публичный трекер заказа по tracking_token
     */
    public function actionTrack($token)
    {
        $order = Order::findOne(['tracking_token' => $token]);
        if ($order === null) {
            // Fallback: try token field
            $order = Order::findOne(['token' => $token]);
        }
        if ($order === null) {
            throw new NotFoundHttpException('Заказ не найден.');
        }

        return $this->render('order/track', [
            'order' => $order,
        ]);
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
        $attempts    = (int)$session->get($key, 0);
        $lastAttempt = (int)$session->get($key . '_time', 0);

        // Сброс счетчика через 15 минут
        if (time() - $lastAttempt > 900) {
            $attempts = 0;
        }

        if ($attempts >= 5) {
            Yii::warning('Превышен лимит попыток загрузки для токена: ' . $token, 'security');
            throw new BadRequestHttpException('Превышено количество попыток. Попробуйте через 15 минут.');
        }

        $session->set($key, $attempts + 1);
        $session->set($key . '_time', (int)time());
    }

    /**
     * AJAX: Сохранение паспортных данных (публичная страница заказа)
     * POST /order/save-passport?token=XXX
     */
    public function actionSavePassport($token)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        if (!Yii::$app->request->isPost) {
            return ['success' => false, 'message' => 'Недопустимый метод'];
        }

        $model = Order::findOne(['token' => $token]);
        if ($model === null) {
            return ['success' => false, 'message' => 'Заказ не найден'];
        }

        if ($model->status !== 'paid') {
            return ['success' => false, 'message' => 'Паспортные данные принимаются только для оплаченных заказов'];
        }

        $post        = Yii::$app->request->post();
        $citizenship = in_array($post['citizenship'] ?? '', ['by', 'ru'], true) ? $post['citizenship'] : 'by';
        $errors = [];

        // BY uses one combined Серия+Номер in passport_series (MP1234567).
        // RU keeps Серия (4 digits) + Номер (6 digits) separate.
        $required = [
            'recipient_last_name'  => 'Фамилия',
            'recipient_first_name' => 'Имя',
            'birth_date'           => 'Дата рождения',
            'passport_series'      => $citizenship === 'ru' ? 'Серия паспорта' : 'Серия+Номер паспорта',
            'passport_issue_date'  => 'Дата выдачи паспорта',
            'passport_issued_by'   => 'Кем выдан',
            'inn'                  => $citizenship === 'ru' ? 'ИНН' : 'Идентификационный номер',
            'full_address'         => 'Адрес',
            'city'                 => 'Город',
            'postal_code'          => 'Почтовый индекс',
        ];
        if ($citizenship === 'ru') {
            $required['passport_number'] = 'Номер паспорта';
        }

        foreach ($required as $field => $label) {
            $val = trim(strip_tags($post[$field] ?? ''));
            if (empty($val)) {
                $errors[$field] = $label . ' обязателен';
            }
        }

        $series = trim(strtoupper($post['passport_series'] ?? ''));
        if (!empty($series)) {
            if ($citizenship === 'ru') {
                if (!preg_match('/^[0-9]{4}$/', $series)) {
                    $errors['passport_series'] = 'Серия РФ: ровно 4 цифры';
                }
            } else {
                if (!preg_match('/^[A-Z]{2}[0-9]{7}$/', $series)) {
                    $errors['passport_series'] = 'Формат: 2 латинские буквы + 7 цифр (например MP1234567)';
                }
            }
        }

        if ($citizenship === 'ru') {
            $passNum = trim($post['passport_number'] ?? '');
            if (!empty($passNum) && !preg_match('/^[0-9]{6}$/', $passNum)) {
                $errors['passport_number'] = 'Номер паспорта: ровно 6 цифр';
            }
        }

        $inn = trim($post['inn'] ?? '');
        if (!empty($inn)) {
            if ($citizenship === 'ru') {
                if (!preg_match('/^[0-9]{12}$/', $inn)) {
                    $errors['inn'] = 'ИНН: ровно 12 цифр';
                }
            } else {
                if (mb_strlen($inn) !== 14) {
                    $errors['inn'] = 'Идентификационный номер: ровно 14 символов';
                }
            }
        }

        if (!empty($errors)) {
            return ['success' => false, 'message' => 'Проверьте введённые данные', 'errors' => $errors];
        }

        $fields = array_merge(
            array_keys($required),
            ['recipient_middle_name', 'region', 'passport_division_code', 'citizenship']
        );
        foreach ($fields as $field) {
            if ($model->hasAttribute($field)) {
                $model->$field = trim(strip_tags($post[$field] ?? ''));
            }
        }
        // Force uppercase on series
        if ($model->hasAttribute('passport_series')) {
            $model->passport_series = strtoupper($model->passport_series);
        }
        // BY stores Серия+Номер in passport_series; clear passport_number to avoid stale RU data.
        if ($citizenship === 'by' && $model->hasAttribute('passport_number')) {
            $model->passport_number = '';
        }
        if ($model->hasAttribute('passport_submitted_at')) {
            $model->passport_submitted_at = time();
        }

        if (!$model->save(false)) {
            Yii::error('Ошибка сохранения паспортных данных заказа #' . $model->id . ': ' . json_encode($model->errors), 'passport');
            return ['success' => false, 'message' => 'Ошибка сохранения данных'];
        }

        Yii::info('Паспортные данные сохранены для заказа #' . $model->id, 'passport');
        return ['success' => true, 'message' => 'Паспортные данные сохранены. Ваш заказ будет отправлен в ближайшее время.'];
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
}
