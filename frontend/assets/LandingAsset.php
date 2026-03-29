<?php

namespace app\frontend\assets;

use app\frontend\assets\VersionedAssetBundle;

/**
 * Landing Asset Bundle - Стили лендинга
 * 
 * РЕФАКТОРИНГ 2026:
 * - CSS объединены в landing-bundle.css
 * - Автоматическое версионирование
 */
class LandingAsset extends VersionedAssetBundle
{
    public $sourcePath = '@frontend';  // Источник файлов
    public $baseUrl = '@web';
    
    public $css = [
        'css/pages.css',  // Стили для landing page (hero, sections)
    ];
    
    public $depends = [
        'app\frontend\assets\AppAsset',
    ];
}
