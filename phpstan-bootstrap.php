<?php

/**
 * PHPStan needs the Yii2 class hierarchy loaded to know `Yii` is an alias
 * for \yii\BaseYii — Yii2 wires that up at runtime (see web/index.php),
 * not via composer's autoloader, so PHPStan never sees it otherwise.
 */

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/vendor/yiisoft/yii2/Yii.php';
