<?php

namespace app\backend\modules\admin\controllers;

class LoyaltyController extends BaseAdminController
{
    public function actionIndex()
    {
        return $this->render('//loyalty/index');
    }
}
