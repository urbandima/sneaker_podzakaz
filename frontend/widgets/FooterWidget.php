<?php

namespace app\frontend\widgets;

/**
 * FooterWidget - Виджет для рендеринга футера сайта
 *
 * @deprecated Используйте LayoutPartWidget с viewName='footer'
 */
class FooterWidget extends LayoutPartWidget
{
    public function init()
    {
        parent::init();
        $this->viewName = 'footer';
    }
}
