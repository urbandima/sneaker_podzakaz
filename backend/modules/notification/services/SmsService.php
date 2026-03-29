<?php

/**
 * SmsService — Сервис SMS-уведомлений
 * 
 * Поддерживает провайдеров: Twilio, SMSC.ru, SMS.ru (настраивается)
 */
namespace app\backend\modules\notification\services;

use Yii;
use yii\base\Component;

class SmsService extends Component
{
    /** @var string Провайдер (twilio, smsc, smsru) */
    public $provider;

    /** @var array Ключи API */
    public $apiKeys = [];

    /**
     * Отправить SMS
     * 
     * @param string $phone Номер телефона
     * @param string $message Сообщение
     * @return bool
     */
    public function send(string $phone, string $message): bool
    {
        $phone = $this->normalizePhone($phone);
        
        if (empty($phone)) {
            Yii::error('Invalid phone number', 'sms');
            return false;
        }

        // Обрезаем сообщение до 160 символов (1 SMS)
        if (strlen($message) > 160) {
            $message = substr($message, 0, 157) . '...';
        }

        switch ($this->provider) {
            case 'smsc':
                return $this->sendViaSmsc($phone, $message);
            case 'twilio':
                return $this->sendViaTwilio($phone, $message);
            case 'smsru':
                return $this->sendViaSmsru($phone, $message);
            default:
                // Тестовый режим - логируем
                Yii::info("[SMS TEST] To: {$phone}, Message: {$message}", 'sms');
                return true;
        }
    }

    /**
     * Отправить SMS через SMSC.ru
     */
    private function sendViaSmsc(string $phone, string $message): bool
    {
        $url = 'https://smsc.ru/sys/send.php';
        $params = [
            'login' => $this->apiKeys['smsc_login'] ?? '',
            'psw' => $this->apiKeys['smsc_password'] ?? '',
            'phones' => $phone,
            'mes' => $message,
            'fmt' => 3, // JSON ответ
            'charset' => 'utf-8',
        ];

        try {
            $response = $this->httpGet($url, $params);
            $data = json_decode($response, true);
            
            if (isset($data['error'])) {
                Yii::error('SMSC error: ' . $data['error'], 'sms');
                return false;
            }

            return true;
        } catch (\Exception $e) {
            Yii::error('SMSC exception: ' . $e->getMessage(), 'sms');
            return false;
        }
    }

    /**
     * Отправить SMS через Twilio
     */
    private function sendViaTwilio(string $phone, string $message): bool
    {
        $sid = $this->apiKeys['twilio_sid'] ?? '';
        $token = $this->apiKeys['twilio_token'] ?? '';
        $from = $this->apiKeys['twilio_from'] ?? '';

        $url = "https://api.twilio.com/2010-04-01/Accounts/{$sid}/Messages.json";
        
        $params = [
            'To' => $phone,
            'From' => $from,
            'Body' => $message,
        ];

        try {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_USERPWD, "{$sid}:{$token}");
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($httpCode === 201) {
                return true;
            }

            Yii::error('Twilio error: ' . $response, 'sms');
            return false;
        } catch (\Exception $e) {
            Yii::error('Twilio exception: ' . $e->getMessage(), 'sms');
            return false;
        }
    }

    /**
     * Отправить SMS через SMS.ru
     */
    private function sendViaSmsru(string $phone, string $message): bool
    {
        $url = 'https://sms.ru/sms/send';
        $params = [
            'api_id' => $this->apiKeys['smsru_api_id'] ?? '',
            'to' => $phone,
            'msg' => $message,
            'json' => 1,
        ];

        try {
            $response = $this->httpGet($url, $params);
            $data = json_decode($response, true);

            if (isset($data['status']) && $data['status'] === 'OK') {
                return true;
            }

            Yii::error('SMS.ru error: ' . json_encode($data), 'sms');
            return false;
        } catch (\Exception $e) {
            Yii::error('SMS.ru exception: ' . $e->getMessage(), 'sms');
            return false;
        }
    }

    /**
     * Нормализовать номер телефона
     */
    private function normalizePhone(string $phone): string
    {
        // Убираем всё кроме цифр
        $phone = preg_replace('/[^0-9]/', '', $phone);
        
        // Если номер начинается с 8 и длина 11, заменяем на 7
        if (strlen($phone) === 11 && $phone[0] === '8') {
            $phone = '7' . substr($phone, 1);
        }
        
        // Если номер 10 цифр, добавляем 7
        if (strlen($phone) === 10) {
            $phone = '7' . $phone;
        }

        return $phone;
    }

    /**
     * HTTP GET запрос
     */
    private function httpGet(string $url, array $params): string
    {
        $url .= '?' . http_build_query($params);
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        
        $response = curl_exec($ch);
        curl_close($ch);

        return $response;
    }
}
