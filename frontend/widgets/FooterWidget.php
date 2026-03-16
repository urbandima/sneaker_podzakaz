<?php

namespace app\frontend\widgets;

use yii\base\Widget;
use yii\helpers\Html;

/**
 * FooterWidget - Виджет для рендеринга футера сайта
 * 
 * Заменяет inline HTML футера в layout/main.php
 */
class FooterWidget extends Widget
{
    public $company = [];
    
    public function run()
    {
        return $this->render('footer', [
            'company' => $this->company,
        ]);
    }
}
