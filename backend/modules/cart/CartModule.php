<?php

namespace app\backend\modules\cart;

use yii\base\Module;
use Yii;

class CartModule extends Module
{
    public $controllerNamespace = 'app\backend\modules\cart\controllers';
    public $layout = 'public';
    
    public function init()
    {
        parent::init();

        // Используем глобальные frontend/views
        $this->setViewPath(Yii::getAlias('@frontend/views'));
        Yii::setAlias('@cart', $this->getBasePath());
    }
}
