<?php

namespace app\controllers;

use Yii;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\web\UploadedFile;
use yii\web\BadRequestHttpException;
use app\models\Order;
use app\models\OrderHistory;

class OrderController extends Controller
{
    public $layout = 'public'; // Специальный layout для публичной части

    public function beforeAction($action)
    {
        // CSRF защита включена для всех действий
        // Для публичных форм используем встроенные механизмы Yii2
        return parent::beforeAction($action);
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
                    $uploadPath = Yii::getAlias('@app/web/uploads/payments/');
                    if (!is_dir($uploadPath)) {
                        mkdir($uploadPath, 0755, true);
                    }

                    // Генерируем безопасное имя файла
                    $fileName = $model->id . '_' . time() . '_' . Yii::$app->security->generateRandomString(8) . '.' . $file->extension;
                    $filePath = $uploadPath . $fileName;

                    if ($file->saveAs($filePath)) {
                        $oldStatus = $model->status;
                        $model->payment_proof = '/uploads/payments/' . $fileName;
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

        // Проверка размера (максимум 5 МБ)
        $maxSize = 5 * 1024 * 1024; // 5 MB
        if ($file->size > $maxSize) {
            $errors[] = 'Размер файла не должен превышать 5 МБ.';
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
}
