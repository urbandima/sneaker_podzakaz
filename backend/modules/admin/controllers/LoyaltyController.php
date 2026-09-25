<?php

namespace app\backend\modules\admin\controllers;

class LoyaltyController extends BaseAdminController
{
    public function actionIndex()
    {
        /**
         * CMP-470: было `render('//loyalty/index')` — синтаксис Yii2 «абсолютный
         * путь от корня view-приложения» резолвился в `frontend/views/loyalty/index.php`
         * (клиентская страница программы лояльности, которую не рендерит ни один
         * фронтенд-контроллер — сама по себе orphaned view), а не в реально
         * существующий `backend/modules/admin/views/loyalty/index.php` (админская
         * страница настроек программы лояльности, под которую написан JS
         * `saveLoyaltySettings()` в admin-settings.js). Живым прогоном подтверждено:
         * GET /admin/loyalty/index падал 500 (`Undefined variable $info` — вьюха
         * ждёт параметры, которые admin-контроллер не передаёт). Обычный
         * относительный render резолвится в admin-вьюху этого же модуля.
         */
        return $this->render('index');
    }
}
