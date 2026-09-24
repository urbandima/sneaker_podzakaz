<?php

namespace tests\unit;

use PHPUnit\Framework\TestCase;
use app\backend\modules\checkout\services\DobroPostService;

/**
 * CMP-430/H: testCredentials() шлёт email/password на /api/shipment/sign-in
 * через тот же request(), что и authenticate() — до этой правки
 * SENSITIVE_PAYLOAD_FIELDS не включал 'password'/'email', поэтому каждый
 * логин-запрос писал пароль от DobroPost в лог plaintext-ом.
 */
class DobroPostServicePiiRedactionTest extends TestCase
{
    public function testPasswordAndEmailAreRedacted(): void
    {
        $service = new DobroPostService();

        $method = new \ReflectionMethod(DobroPostService::class, 'redactSensitivePayload');
        $method->setAccessible(true);

        $redacted = $method->invoke($service, [
            'email' => 'ops@sneaker-head.by',
            'password' => 'super-secret-value',
        ]);

        $this->assertStringNotContainsString('super-secret-value', $redacted['password']);
        $this->assertStringNotContainsString('ops@sneaker-head.by', $redacted['email']);
        $this->assertStringStartsWith('[REDACTED:', $redacted['password']);
        $this->assertStringStartsWith('[REDACTED:', $redacted['email']);
    }
}
