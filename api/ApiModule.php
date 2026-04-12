<?php

namespace app\api;

use yii\base\Module;

/**
 * ApiModule — маршрутизация для внешних API-эндпоинтов (вебхуки, healthcheck, docs).
 * Контроллеры: app\api\controllers\*
 */
class ApiModule extends Module
{
    public $controllerNamespace = 'app\api\controllers';
}
