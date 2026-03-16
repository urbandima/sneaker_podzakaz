<?php

namespace app\backend\modules\checkout;

use yii\base\Module;
use Yii;

class CheckoutModule extends Module
{
    public $controllerNamespace = 'app\backend\modules\checkout\controllers';
    public $layout = 'public';
    
    public function init()
    {
        parent::init();

        // Используем глобальные frontend/views
        $this->setViewPath(Yii::getAlias('@frontend/views'));
        Yii::setAlias('@checkout', $this->getBasePath());
    }
}
