<?php

namespace app\backend\modules\account;

use yii\base\Module;
use Yii;

class AccountModule extends Module
{
    public $controllerNamespace = 'app\backend\modules\account\controllers';
    public $layout = 'main';
    
    public function init()
    {
        parent::init();

        // Используем глобальные frontend/views
        $this->setViewPath(Yii::getAlias('@frontend/views'));
        Yii::setAlias('@account', $this->getBasePath());
    }
}
