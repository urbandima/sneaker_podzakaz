<?php

namespace app\frontend\widgets;

use yii\base\Widget;

/**
 * LayoutPartWidget — Базовый виджет для частей layout (header, footer)
 *
 * Устраняет дублирование HeaderWidget и FooterWidget
 */
class LayoutPartWidget extends Widget
{
    /** @var string Название view для рендеринга */
    public $viewName;

    /** @var array Данные компании */
    public $company = [];

    /** @var array Дополнительные параметры для view */
    public $params = [];

    public function run()
    {
        if (empty($this->viewName)) {
            return '';
        }

        return $this->render($this->viewName, array_merge(
            ['company' => $this->company],
            $this->params
        ));
    }
}
