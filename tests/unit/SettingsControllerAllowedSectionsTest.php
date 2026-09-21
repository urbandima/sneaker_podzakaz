<?php

namespace tests\unit;

use PHPUnit\Framework\TestCase;
use app\backend\modules\admin\controllers\SettingsController;

/**
 * CMP-411 / CMP-139: до этой правки 'analytics' отсутствовал в ALLOWED_SAVE_SECTIONS,
 * поэтому SettingsController::actionSave() всегда отвечал 400 на сохранение
 * GA4 / Яндекс.Метрика / Meta Pixel из админ-панели, и поля оставались пустыми
 * независимо от того, что вводил админ.
 */
class SettingsControllerAllowedSectionsTest extends TestCase
{
    public function testAnalyticsSectionIsAllowed(): void
    {
        $ref = new \ReflectionClassConstant(SettingsController::class, 'ALLOWED_SAVE_SECTIONS');
        $this->assertContains('analytics', $ref->getValue());
    }
}
