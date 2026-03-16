<?php

namespace app\frontend\widgets;

use yii\base\Widget;
use yii\helpers\Html;

/**
 * HeaderWidget - Виджет для рендеринга хедера сайта
 * 
 * Заменяет inline HTML хедера в layout/main.php
 */
class HeaderWidget extends Widget
{
    public $company = [];
    
    public function run()
    {
        return $this->render('header', [
            'company' => $this->company,
        ]);
    }
}
