<?php

namespace app\frontend\widgets;

/**
 * HeaderWidget - Виджет для рендеринга хедера сайта
 *
 * @deprecated Используйте LayoutPartWidget с viewName='header'
 */
class HeaderWidget extends LayoutPartWidget
{
    public function init()
    {
        parent::init();
        $this->viewName = 'header';
    }
}
