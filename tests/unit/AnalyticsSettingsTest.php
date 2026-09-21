<?php

namespace tests\unit;

use PHPUnit\Framework\TestCase;
use app\backend\shared\components\AnalyticsSettings;

/**
 * CMP-411: пустое или placeholder-значение — валидное состояние (скрипт не рендерится),
 * реальный ID проходит валидацию.
 */
class AnalyticsSettingsTest extends TestCase
{
    public function testGa4IdEmptyIsInvalid(): void
    {
        $this->assertFalse(AnalyticsSettings::isValidGa4Id(''));
        $this->assertFalse(AnalyticsSettings::isValidGa4Id(null));
    }

    public function testGa4IdPlaceholderIsInvalid(): void
    {
        $this->assertFalse(AnalyticsSettings::isValidGa4Id('G-XXXXXXXXXX'));
    }

    public function testGa4IdRealValueIsValid(): void
    {
        $this->assertTrue(AnalyticsSettings::isValidGa4Id('G-ABCD123456'));
    }

    public function testMetrikaIdEmptyIsInvalid(): void
    {
        $this->assertFalse(AnalyticsSettings::isValidMetrikaId(''));
        $this->assertFalse(AnalyticsSettings::isValidMetrikaId(null));
    }

    public function testMetrikaIdPlaceholderIsInvalid(): void
    {
        $this->assertFalse(AnalyticsSettings::isValidMetrikaId('12345678'));
    }

    public function testMetrikaIdNonNumericIsInvalid(): void
    {
        $this->assertFalse(AnalyticsSettings::isValidMetrikaId('abc12345'));
    }

    public function testMetrikaIdRealValueIsValid(): void
    {
        $this->assertTrue(AnalyticsSettings::isValidMetrikaId('98765432'));
    }

    public function testMetaPixelIdEmptyIsInvalid(): void
    {
        $this->assertFalse(AnalyticsSettings::isValidMetaPixelId(''));
        $this->assertFalse(AnalyticsSettings::isValidMetaPixelId(null));
    }

    public function testMetaPixelIdPlaceholderIsInvalid(): void
    {
        $this->assertFalse(AnalyticsSettings::isValidMetaPixelId('META_PIXEL_ID_PLACEHOLDER'));
        $this->assertFalse(AnalyticsSettings::isValidMetaPixelId('XXXXXXXXXXXXXXX'));
    }

    public function testMetaPixelIdWrongLengthIsInvalid(): void
    {
        $this->assertFalse(AnalyticsSettings::isValidMetaPixelId('123')); // too short
        $this->assertFalse(AnalyticsSettings::isValidMetaPixelId('12345678901234567')); // too long
    }

    public function testMetaPixelIdRealValueIsValid(): void
    {
        $this->assertTrue(AnalyticsSettings::isValidMetaPixelId('1234567890123'));
    }
}
