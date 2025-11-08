<?php

namespace app\controllers;

use Yii;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\web\UploadedFile;
use yii\web\BadRequestHttpException;
use yii\web\Response;
use app\models\Order;
use app\models\OrderItem;
use app\models\OrderHistory;
use app\models\Cart;

class OrderController extends Controller
{
    public $layout = 'public'; // Специальный layout для публичной части

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
            return ['success' => false, 'message' => 'Недопустимый метод запроса'];
        }
        
        // Получаем данные формы
        $name = Yii::$app->request->post('name');
        $phone = Yii::$app->request->post('phone');
        $email = Yii::$app->request->post('email');
        $country = Yii::$app->request->post('country', 'belarus');
        $delivery = Yii::$app->request->post('delivery');
        $address = Yii::$app->request->post('address');
        $comment = Yii::$app->request->post('comment');
        
        // Валидация обязательных полей
        if (empty($name) || empty($phone) || empty($delivery)) {
            return ['success' => false, 'message' => 'Заполните все обязательные поля'];
        }
        
        // Проверяем адрес (обязателен для всех кроме самовывоза)
        if ($delivery !== 'pickup_minsk' && empty($address)) {
            return ['success' => false, 'message' => 'Укажите адрес доставки'];
        }
        
        // Получаем товары из корзины
        $cartItems = Cart::getItems();
        if (empty($cartItems)) {
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
                'message' => 'Заказ успешно оформлен',
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'token' => $order->token
            ];
            
        } catch (\Exception $e) {
            $transaction->rollBack();
            Yii::error('Ошибка создания заказа: ' . $e->getMessage(), 'order');
            
            return [
                'success' => false,
                'message' => 'Ошибка при оформлении заказа. Попробуйте позже.'
            ];
        }
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
}
