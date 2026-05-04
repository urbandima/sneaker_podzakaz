<?php

/**
 * SmsService — Сервис SMS-уведомлений
 *
 * Поддерживает провайдеров: Twilio, SMSC.ru, SMS.ru, RocketSMS.by
 */
namespace app\backend\modules\notification\services;

use Yii;
use yii\base\Component;

class SmsService extends Component
{
    /** @var string Провайдер (twilio, smsc, smsru, rocketsms) */
    public $provider;

    /** @var array Ключи API */
    public $apiKeys = [];

    /** @var string|null Sender / alphaname для RocketSMS */
    public $senderName;

    public function init()
    {
        parent::init();
        // Overlay из БД (plugin/rocketsms_config) — если администратор изменил настройки через UI
        try {
            if (Yii::$app->has('settings')) {
                $raw = Yii::$app->settings->get('plugin', 'rocketsms_config', '');
                if ($raw) {
                    $cfg = json_decode($raw, true);
                    if (is_array($cfg)) {
                        if (!empty($cfg['username'])) {
                            $this->apiKeys['rocketsms_username'] = $cfg['username'];
                        }
                        if (!empty($cfg['password'])) {
                            $this->apiKeys['rocketsms_password'] = $cfg['password'];
                        }
                        if (!empty($cfg['sender'])) {
                            $this->apiKeys['rocketsms_sender'] = $cfg['sender'];
                            if (empty($this->senderName)) {
                                $this->senderName = $cfg['sender'];
                            }
                        }
                        // Если в БД активен — переключаем провайдера на rocketsms
                        if (!empty($cfg['active']) && !empty($cfg['username']) && !empty($cfg['password'])) {
                            $this->provider = 'rocketsms';
                        } elseif (isset($cfg['active']) && !$cfg['active'] && $this->provider === 'rocketsms') {
                            $this->provider = 'test';
                        }
                    }
                }
            }
        } catch (\Throwable $e) {
            Yii::warning('SmsService::init() settings overlay failed: ' . $e->getMessage(), 'sms');
        }
    }

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
            case 'rocketsms':
                return $this->sendViaRocketSms($phone, $message);
            default:
                // Тестовый режим - логируем
                Yii::info("[SMS TEST] To: {$phone}, Message: {$message}", 'sms');
                return true;
        }
    }

    /**
     * Получить баланс у RocketSMS (возвращает массив ['credits' => .., 'currency' => ..] или null при ошибке)
     */
    public function getRocketSmsBalance(): ?array
    {
        $username = $this->apiKeys['rocketsms_username'] ?? '';
        $password = $this->apiKeys['rocketsms_password'] ?? '';
        if (empty($username) || empty($password)) {
            return null;
        }
        try {
            $response = $this->httpGet('https://api.rocketsms.by/simple/balance', [
                'username' => $username,
                'password' => $password,
            ]);
            $data = json_decode($response, true);
            if (is_array($data) && isset($data['credits'])) {
                return [
                    'credits' => (float) $data['credits'],
                    'currency' => $data['currency'] ?? 'BYN',
                ];
            }
            Yii::error('RocketSMS balance error: ' . $response, 'sms');
            return null;
        } catch (\Exception $e) {
            Yii::error('RocketSMS balance exception: ' . $e->getMessage(), 'sms');
            return null;
        }
    }

    /**
     * Отправить SMS через RocketSMS.by
     *
     * API docs: https://rocketsms.by/storage/rocketsms_api.pdf
     * Endpoint: POST https://api.rocketsms.by/simple/send
     */
    private function sendViaRocketSms(string $phone, string $message): bool
    {
        $username = $this->apiKeys['rocketsms_username'] ?? '';
        $password = $this->apiKeys['rocketsms_password'] ?? '';
        $sender   = $this->senderName ?: ($this->apiKeys['rocketsms_sender'] ?? null);

        if (empty($username) || empty($password)) {
            Yii::error('RocketSMS: пустой username/password', 'sms');
            return false;
        }

        $url = 'https://api.rocketsms.by/simple/send';
        $params = [
            'username' => $username,
            'password' => $password,
            'phone'    => $phone,
            'text'     => $message,
            'priority' => 'true',
        ];
        if (!empty($sender)) {
            $params['sender'] = $sender;
        }

        try {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 15);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            $data = json_decode($response, true);

            // RocketSMS при успехе возвращает {"status": "SENT", "id": ..., "cost": ...}
            if ($httpCode >= 200 && $httpCode < 300 && is_array($data)
                && isset($data['status']) && in_array(strtoupper($data['status']), ['SENT','QUEUED','OK'], true)) {
                $cost = is_array($data['cost']) ? ($data['cost']['credits'] ?? 0) . ' кред.' : $data['cost'];
                Yii::info("RocketSMS sent OK: id={$data['id']}, cost={$cost}", 'sms');
                return true;
            }

            Yii::error('RocketSMS error (HTTP ' . $httpCode . '): ' . $response, 'sms');
            return false;
        } catch (\Exception $e) {
            Yii::error('RocketSMS exception: ' . $e->getMessage(), 'sms');
            return false;
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

        // BY: 375XXYYYYYYY уже корректен (12 цифр)
        if (strlen($phone) === 12 && str_starts_with($phone, '375')) {
            return $phone;
        }

        // Если номер начинается с 8 и длина 11, заменяем на 7
        if (strlen($phone) === 11 && $phone[0] === '8') {
            $phone = '7' . substr($phone, 1);
        }

        // BY без кода страны: 29XXXXXXX (9 цифр) → 37529XXXXXXX
        if (strlen($phone) === 9 && in_array(substr($phone, 0, 2), ['25','29','33','44'], true)) {
            $phone = '375' . $phone;
        }

        // RU без кода страны: XXXYYYYYYY (10 цифр) → 7XXXYYYYYYY
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
