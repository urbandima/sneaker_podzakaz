<?php

/**
 * TwoFactorAuth — Двухфакторная аутентификация (TOTP)
 * 
 * НАЗНАЧЕНИЕ:
 * Реализация 2FA через TOTP (Time-based One-Time Password).
 * Совместимо с Google Authenticator, Authy, Microsoft Authenticator.
 * 
 * ИСПОЛЬЗОВАНИЕ:
 * $tfa = new TwoFactorAuth();
 * $secret = $tfa->generateSecret(); // Сохранить в user.totp_secret
 * $qrUrl = $tfa->getQRCodeUrl($user->email, $secret);
 * $isValid = $tfa->verifyCode($secret, $userInputCode);
 */
namespace app\backend\modules\admin\components;

use Yii;
use yii\base\Component;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;

class TwoFactorAuth extends Component
{
    /**
     * Длина секретного ключа (байт)
     */
    public int $secretLength = 16;
    
    /**
     * Окно проверки кода (количество периодов до/после)
     */
    public int $codeWindow = 1;
    
    /**
     * Период генерации кода (секунды)
     */
    public int $period = 30;
    
    /**
     * Длина кода (6 или 8 цифр)
     */
    public int $digits = 6;
    
    /**
     * Алгоритм хеширования
     */
    public string $algorithm = 'sha1';
    
    /**
     * Название приложения в Authenticator
     */
    public string $issuer = 'Sneaker Store';
    
    /**
     * Сгенерировать секретный ключ
     * 
     * @return string Base32 encoded secret
     */
    public function generateSecret(): string
    {
        $bytes = random_bytes($this->secretLength);
        return $this->base32Encode($bytes);
    }
    
    /**
     * Проверить код TOTP
     * 
     * @param string $secret Base32 секрет
     * @param string $code Код от пользователя
     * @param int|null $timestamp Временная метка (null = текущее время)
     * @return bool
     */
    public function verifyCode(string $secret, string $code, ?int $timestamp = null): bool
    {
        $timestamp = $timestamp ?? time();
        $secret = $this->base32Decode($secret);
        
        // Проверяем коды в окне (текущий + window до/после)
        for ($i = -$this->codeWindow; $i <= $this->codeWindow; $i++) {
            $counter = (int)floor(($timestamp + ($i * $this->period)) / $this->period);
            $expectedCode = $this->generateTotp($secret, $counter);
            
            if (hash_equals($expectedCode, $code)) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Получить URL для QR-кода
     * 
     * @param string $email Email пользователя
     * @param string $secret Base32 секрет
     * @return string
     */
    public function getQRCodeUrl(string $email, string $secret): string
    {
        $issuer = urlencode($this->issuer);
        $email = urlencode($email);
        
        return "otpauth://totp/{$issuer}:{$email}?secret={$secret}&issuer={$issuer}&digits={$this->digits}&period={$this->period}";
    }
    
    /**
     * Сгенерировать QR-код как SVG
     * 
     * @param string $email
     * @param string $secret
     * @return string SVG код
     */
    public function getQRCodeSvg(string $email, string $secret): string
    {
        $url = $this->getQRCodeUrl($email, $secret);
        
        $renderer = new ImageRenderer(
            new RendererStyle(200),
            new SvgImageBackEnd()
        );
        
        $writer = new Writer($renderer);
        
        return $writer->writeString($url);
    }
    
    /**
     * Сгенерировать резервные коды
     * 
     * @param int $count Количество кодов
     * @return array
     */
    public function generateBackupCodes(int $count = 8): array
    {
        $codes = [];
        
        for ($i = 0; $i < $count; $i++) {
            $codes[] = $this->generateBackupCode();
        }
        
        return $codes;
    }
    
    /**
     * Сгенерировать один резервный код
     */
    private function generateBackupCode(): string
    {
        $bytes = random_bytes(4);
        $code = strtoupper(bin2hex($bytes));
        
        // Формат: XXXX-XXXX
        return substr($code, 0, 4) . '-' . substr($code, 4, 4);
    }
    
    /**
     * Проверить резервный код
     * 
     * @param array $hashedCodes Хешированные коды из БД
     * @param string $inputCode Введённый код
     * @return array ['valid' => bool, 'remaining' => array]
     */
    public function verifyBackupCode(array $hashedCodes, string $inputCode): array
    {
        $inputCode = strtoupper(str_replace('-', '', $inputCode));
        $inputCode = substr($inputCode, 0, 4) . '-' . substr($inputCode, 4, 4);
        
        foreach ($hashedCodes as $index => $hashedCode) {
            if (password_verify($inputCode, $hashedCode)) {
                // Удаляем использованный код
                unset($hashedCodes[$index]);
                return [
                    'valid' => true,
                    'remaining' => array_values($hashedCodes),
                ];
            }
        }
        
        return [
            'valid' => false,
            'remaining' => $hashedCodes,
        ];
    }
    
    /**
     * Хешировать резервные коды для хранения
     */
    public function hashBackupCodes(array $codes): array
    {
        return array_map(function($code) {
            return password_hash($code, PASSWORD_BCRYPT);
        }, $codes);
    }
    
    /**
     * Генерация TOTP кода
     */
    private function generateTotp(string $secret, int $counter): string
    {
        // Pack counter as 64-bit big-endian
        $counterPack = pack('N*', 0) . pack('N*', $counter);
        
        // HMAC
        $hash = hash_hmac($this->algorithm, $counterPack, $secret, true);
        
        // Dynamic truncation
        $offset = ord($hash[strlen($hash) - 1]) & 0x0F;
        $code = (
            ((ord($hash[$offset + 0]) & 0x7F) << 24) |
            ((ord($hash[$offset + 1]) & 0xFF) << 16) |
            ((ord($hash[$offset + 2]) & 0xFF) << 8) |
            (ord($hash[$offset + 3]) & 0xFF)
        );
        
        // Get last N digits
        $code = $code % (10 ** $this->digits);
        
        return str_pad($code, $this->digits, '0', STR_PAD_LEFT);
    }
    
    /**
     * Base32 кодирование
     */
    private function base32Encode(string $data): string
    {
        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $result = '';
        
        $buffer = 0;
        $bufferSize = 0;
        
        for ($i = 0; $i < strlen($data); $i++) {
            $buffer = ($buffer << 8) | ord($data[$i]);
            $bufferSize += 8;
            
            while ($bufferSize >= 5) {
                $bufferSize -= 5;
                $index = ($buffer >> $bufferSize) & 0x1F;
                $result .= $alphabet[$index];
            }
        }
        
        if ($bufferSize > 0) {
            $index = ($buffer << (5 - $bufferSize)) & 0x1F;
            $result .= $alphabet[$index];
        }
        
        return $result;
    }
    
    /**
     * Base32 декодирование
     */
    private function base32Decode(string $data): string
    {
        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $map = array_flip(str_split($alphabet));
        
        $data = strtoupper($data);
        $data = preg_replace('/[^A-Z2-7]/', '', $data);
        
        $result = '';
        $buffer = 0;
        $bufferSize = 0;
        
        for ($i = 0; $i < strlen($data); $i++) {
            $char = $data[$i];
            
            if (!isset($map[$char])) {
                continue;
            }
            
            $buffer = ($buffer << 5) | $map[$char];
            $bufferSize += 5;
            
            if ($bufferSize >= 8) {
                $bufferSize -= 8;
                $result .= chr(($buffer >> $bufferSize) & 0xFF);
            }
        }
        
        return $result;
    }
}
