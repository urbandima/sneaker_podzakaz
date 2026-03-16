<?php

namespace app\backend\modules\admin;

use yii\base\Module;
use Yii;

class AdminModule extends Module
{
    public $controllerNamespace = 'app\backend\modules\admin\controllers';
    public $layout = 'admin';
    public $defaultRoute = 'dashboard';
    
    public function init()
    {
        parent::init();

        // Админка использует свои views
        $this->setViewPath($this->getBasePath() . '/views');
        Yii::setAlias('@admin', $this->getBasePath());
    }
}
